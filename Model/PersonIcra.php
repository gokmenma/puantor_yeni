<?php

require_once "BaseModel.php";

class PersonIcra extends Model
{
    protected $table = "person_icra_files";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Get all active execution files of a person, ordered by priority
     *
     * @param int $person_id
     * @return array
     */
    public function getByPersonId($person_id)
    {
        $sql = "SELECT * FROM $this->table WHERE person_id = :person_id AND deleted_at IS NULL ORDER BY icra_sirasi ASC";
        $query = $this->db->prepare($sql);
        $query->execute(['person_id' => $person_id]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get statistics for a person's execution files
     *
     * @param int $person_id
     * @return array
     */
    public function getStats($person_id)
    {
        $sql = "SELECT 
                    COUNT(id) as total_files,
                    SUM(CASE WHEN durum = 'Kesilen' THEN 1 ELSE 0 END) as active_files,
                    SUM(toplam_borc) as total_debt
                FROM $this->table 
                WHERE person_id = :person_id AND deleted_at IS NULL";
        
        $query = $this->db->prepare($sql);
        $query->execute(['person_id' => $person_id]);
        $res = $query->fetch(PDO::FETCH_OBJ);

        // Kalan borcu hesapla
        // Şimdilik yapılan kesinti sıfır kabul edilerek toplam borçtan çıkarılır.
        // İleride bordro ile entegrasyon yapıldığında kesintiler toplamı buraya yansıtılır.
        $total_debt = $res->total_debt ?? 0.00;
        $total_deductions = $this->getTotalDeductions($person_id);
        $remaining_debt = max(0, $total_debt - $total_deductions);

        return [
            'total_files' => (int)($res->total_files ?? 0),
            'active_files' => (int)($res->active_files ?? 0),
            'total_debt' => (float)$total_debt,
            'total_deductions' => (float)$total_deductions,
            'remaining_debt' => (float)$remaining_debt
        ];
    }

    /**
     * Get total deductions made for a person's execution files from wage_cut or other logs
     *
     * @param int $person_id
     * @return float
     */
    public function getTotalDeductions($person_id)
    {
        try {
            $sql = "SELECT SUM(tutar) as total FROM maas_gelir_kesinti 
                    WHERE person_id = :person_id AND kategori = 15 AND (aciklama LIKE '%İcra%' OR aciklama LIKE '%icra%' OR turu = 'İcra Kesintisi')";
            $query = $this->db->prepare($sql);
            $query->execute(['person_id' => $person_id]);
            $res = $query->fetch(PDO::FETCH_OBJ);
            return (float)($res->total ?? 0.00);
        } catch (\Throwable $ex) {
            error_log("Failed to fetch total deductions: " . $ex->getMessage());
            return 0.00;
        }
    }

    /**
     * Parse garnishment rate string (e.g., '%25', '1/4', '25', '%50', '1/3') into float multiplier
     *
     * @param string|null $rateStr
     * @return float
     */
    public static function parseRate($rateStr)
    {
        if (empty($rateStr)) {
            return 0.0;
        }
        $rateStr = trim($rateStr);
        if (strpos($rateStr, '/') !== false) {
            $parts = explode('/', $rateStr);
            $num = floatval(trim($parts[0]));
            $den = floatval(trim($parts[1] ?? 1));
            return $den != 0 ? ($num / $den) : 0.0;
        }
        $clean = str_replace(['%', ' '], '', $rateStr);
        $val = floatval($clean);
        if ($val > 1) {
            return $val / 100.0;
        }
        return $val;
    }

    /**
     * Calculate and apply garnishment deduction for a given person and period
     *
     * @param int $person_id
     * @param int $month
     * @param int $year
     * @param float $period_income
     * @return float Total deduction applied
     */
    public function calculateAndApplyIcraDeduction($person_id, $month, $year, $period_income)
    {
        if ($period_income <= 0) {
            return 0.0;
        }

        // Check if person's icra deduction is active
        $stmt_person = $this->db->prepare("SELECT icra_kesintisi_aktif FROM persons WHERE id = ?");
        $stmt_person->execute([$person_id]);
        $person = $stmt_person->fetch(PDO::FETCH_OBJ);

        if (!$person || (int)($person->icra_kesintisi_aktif ?? 0) !== 1) {
            return 0.0;
        }

        // Delete existing icra deduction entries for this month/year before recalculating
        $stmt_del = $this->db->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? AND kategori = 15 AND (aciklama LIKE '%İcra%' OR aciklama LIKE '%icra%' OR turu = 'İcra Kesintisi')");
        $stmt_del->execute([$person_id, $month, $year]);

        // Period date range
        $periodStart = sprintf('%04d-%02d-01', $year, $month);
        $periodEnd = date('Y-m-t', strtotime($periodStart));

        // Get prior total deductions up to this month (excluding current month)
        $stmt_prior = $this->db->prepare("SELECT SUM(tutar) as total FROM maas_gelir_kesinti 
            WHERE person_id = :person_id AND kategori = 15 
            AND (aciklama LIKE '%İcra%' OR aciklama LIKE '%icra%' OR turu = 'İcra Kesintisi')
            AND (yil < :year OR (yil = :year AND ay < :month))");
        $stmt_prior->execute(['person_id' => $person_id, 'year' => $year, 'month' => $month]);
        $prior_deductions = (float)($stmt_prior->fetch(PDO::FETCH_OBJ)->total ?? 0.0);

        // Fetch active/pending execution files ordered by priority
        $sql_files = "SELECT * FROM $this->table WHERE person_id = :person_id AND deleted_at IS NULL AND durum IN ('Kesilen', 'Aktif', 'Bekliyor', 'Güncellendi') ORDER BY icra_sirasi ASC";
        $q_files = $this->db->prepare($sql_files);
        $q_files->execute(['person_id' => $person_id]);
        $files = $q_files->fetchAll(PDO::FETCH_OBJ);

        $total_applied = 0.0;

        foreach ($files as $f) {
            $toplam_borc = (float)$f->toplam_borc;

            // Determine how much of prior deductions was absorbed by this file
            $file_paid = min($toplam_borc, $prior_deductions);
            $prior_deductions -= $file_paid;
            $file_remaining = max(0.0, $toplam_borc - $file_paid);

            if ($file_remaining <= 0) {
                continue; // Fully paid off
            }

            // Check if file is valid for this period based on dates
            $baslama = !empty($f->baslama_tarihi) ? $f->baslama_tarihi : null;
            $bitis = !empty($f->bitis_tarihi) ? $f->bitis_tarihi : null;

            $is_date_valid = true;
            if ($baslama !== null && $baslama > $periodEnd) {
                $is_date_valid = false;
            }
            if ($bitis !== null && $bitis < $periodStart) {
                $is_date_valid = false;
            }

            if (!$is_date_valid) {
                continue;
            }

            // Calculate proposed deduction
            $deduction = 0.0;
            if ($f->kesinti_yontemi === 'oran') {
                $rate = self::parseRate($f->kesinti_orani);
                $deduction = round($period_income * $rate, 2);
            } else {
                $deduction = round((float)($f->kesinti_tutari ?? 0), 2);
            }

            // Cap at remaining debt
            $deduction = min($deduction, $file_remaining);

            if ($deduction > 0) {
                $gun = sprintf('%04d%02d01', $year, $month);
                $desc = "İcra Kesintisi (Dosya No: {$f->dosya_no})";

                $stmt_ins = $this->db->prepare("INSERT INTO maas_gelir_kesinti (person_id, gun, ay, yil, tutar, kategori, turu, aciklama) VALUES (?, ?, ?, ?, ?, 15, 'İcra Kesintisi', ?)");
                $stmt_ins->execute([$person_id, $gun, $month, $year, $deduction, $desc]);

                $total_applied += $deduction;
                break; // Only apply primary active file per period
            }
        }

        return $total_applied;
    }

    /**
     * Get all centralized execution file statuses with titles and UI badges
     *
     * @return array
     */
    public static function getStatuses()
    {
        return [
            'Kesilen' => [
                'title' => 'Kesilen',
                'badge_class' => 'bg-success-lt text-success',
                'icon' => 'ti-scissors'
            ],
            'Bekliyor' => [
                'title' => 'Bekliyor',
                'badge_class' => 'bg-warning-lt text-warning',
                'icon' => 'ti-clock'
            ],
            'Güncellendi' => [
                'title' => 'Güncellendi',
                'badge_class' => 'bg-info-lt text-info',
                'icon' => 'ti-refresh'
            ],
            'Durduruldu' => [
                'title' => 'Durduruldu',
                'badge_class' => 'bg-secondary-lt text-secondary',
                'icon' => 'ti-player-pause'
            ],
            'Durduruldu(Bekleyen)' => [
                'title' => 'Durduruldu (Bekleyen)',
                'badge_class' => 'bg-warning-lt text-warning',
                'icon' => 'ti-pause'
            ],
            'Fekki Geldi' => [
                'title' => 'Fekki Geldi',
                'badge_class' => 'bg-success-lt text-success',
                'icon' => 'ti-check'
            ],
            'Kesinti Bitti' => [
                'title' => 'Kesinti Bitti',
                'badge_class' => 'bg-muted-lt text-muted',
                'icon' => 'ti-circle-check'
            ]
        ];
    }

    /**
     * Get all execution files for a firm with personnel info and status filter
     *
     * @param int|string $firm_id
     * @param string|array|null $status_filter
     * @return array
     */
    public function getFirmIcraFiles($firm_id, $status_filter = ['Kesilen'])
    {
        $params = ['firm_id' => $firm_id];
        $whereStatus = "";

        if (!empty($status_filter)) {
            if (is_array($status_filter)) {
                $status_filter = array_values(array_filter($status_filter));
                if (!empty($status_filter) && !in_array('Tümü', $status_filter) && !in_array('all', $status_filter)) {
                    $inPlaceholders = [];
                    foreach ($status_filter as $idx => $st) {
                        $pKey = "st_" . $idx;
                        $inPlaceholders[] = ":$pKey";
                        $params[$pKey] = $st;
                    }
                    if (!empty($inPlaceholders)) {
                        $whereStatus = " AND f.durum IN (" . implode(', ', $inPlaceholders) . ")";
                    }
                }
            } elseif ($status_filter !== 'Tümü' && $status_filter !== 'all') {
                $whereStatus = " AND f.durum = :status";
                $params['status'] = $status_filter;
            }
        }

        $sql = "SELECT f.*, 
                       p.full_name, 
                       p.kimlik_no, 
                       p.sigorta_no, 
                       p.job,
                       p.icra_kesintisi_aktif
                FROM $this->table f
                INNER JOIN persons p ON p.id = f.person_id
                WHERE p.firm_id = :firm_id 
                  AND f.deleted_at IS NULL 
                  AND p.deleted_at IS NULL
                  $whereStatus
                ORDER BY p.full_name ASC, f.icra_sirasi ASC";

        $query = $this->db->prepare($sql);
        $query->execute($params);
        $files = $query->fetchAll(PDO::FETCH_OBJ);

        // Compute deductions sequentially per person
        $personDeductions = [];
        $result = [];

        foreach ($files as $f) {
            $pid = $f->person_id;
            if (!isset($personDeductions[$pid])) {
                $personDeductions[$pid] = $this->getTotalDeductions($pid);
            }

            $toplam_borc = (float)$f->toplam_borc;
            $yapilan_kesinti = 0.0;

            if ($personDeductions[$pid] > 0) {
                if ($personDeductions[$pid] >= $toplam_borc) {
                    $yapilan_kesinti = $toplam_borc;
                    $personDeductions[$pid] -= $toplam_borc;
                } else {
                    $yapilan_kesinti = $personDeductions[$pid];
                    $personDeductions[$pid] = 0.0;
                }
            }

            $kalan_borc = max(0.0, $toplam_borc - $yapilan_kesinti);
            $f->yapilan_kesinti = $yapilan_kesinti;
            $f->kalan_borc = $kalan_borc;

            $result[] = $f;
        }

        return $result;
    }

    /**
     * Get aggregate statistics for all execution files of a firm
     *
     * @param int|string $firm_id
     * @return array
     */
    public function getFirmIcraStats($firm_id)
    {
        $sql = "SELECT 
                    COUNT(f.id) as total_files,
                    SUM(CASE WHEN f.durum IN ('Kesilen', 'Aktif') THEN 1 ELSE 0 END) as active_files,
                    SUM(CASE WHEN f.durum IN ('Bekliyor', 'Bekleyen', 'Durduruldu(Bekleyen)') THEN 1 ELSE 0 END) as pending_files,
                    SUM(CASE WHEN f.durum IN ('Fekki Geldi', 'Fekki Gelmiş', 'Kesinti Bitti', 'Kapatıldı', 'Ödendi') THEN 1 ELSE 0 END) as finished_files,
                    SUM(f.toplam_borc) as total_debt
                FROM $this->table f
                INNER JOIN persons p ON p.id = f.person_id
                WHERE p.firm_id = :firm_id 
                  AND f.deleted_at IS NULL 
                  AND p.deleted_at IS NULL";

        $query = $this->db->prepare($sql);
        $query->execute(['firm_id' => $firm_id]);
        $res = $query->fetch(PDO::FETCH_OBJ);

        // Get total firm icra deductions
        $sql_ded = "SELECT SUM(m.tutar) as total 
                    FROM maas_gelir_kesinti m
                    INNER JOIN persons p ON p.id = m.person_id
                    WHERE p.firm_id = :firm_id 
                      AND m.kategori = 15 
                      AND (m.aciklama LIKE '%İcra%' OR m.aciklama LIKE '%icra%' OR m.turu = 'İcra Kesintisi')";
        $q_ded = $this->db->prepare($sql_ded);
        $q_ded->execute(['firm_id' => $firm_id]);
        $res_ded = $q_ded->fetch(PDO::FETCH_OBJ);

        $total_debt = (float)($res->total_debt ?? 0.00);
        $total_deductions = (float)($res_ded->total ?? 0.00);
        $remaining_debt = max(0.00, $total_debt - $total_deductions);

        return [
            'total_files' => (int)($res->total_files ?? 0),
            'active_files' => (int)($res->active_files ?? 0),
            'pending_files' => (int)($res->pending_files ?? 0),
            'finished_files' => (int)($res->finished_files ?? 0),
            'total_debt' => $total_debt,
            'total_deductions' => $total_deductions,
            'remaining_debt' => $remaining_debt
        ];
    }

    /**
     * Get list of all execution deductions for a person (and optional file number)
     *
     * @param int $person_id
     * @param string|null $dosya_no
     * @return array
     */
    public function getDeductionsHistory($person_id, $dosya_no = null)
    {
        $params = ['person_id' => $person_id];
        $whereFile = "";

        if (!empty($dosya_no)) {
            $whereFile = " AND (m.aciklama LIKE :dosya_no OR m.aciklama LIKE '%İcra%')";
            $params['dosya_no'] = '%' . $dosya_no . '%';
        }

        $sql = "SELECT m.*, p.full_name
                FROM maas_gelir_kesinti m
                INNER JOIN persons p ON p.id = m.person_id
                WHERE m.person_id = :person_id 
                  AND m.kategori = 15 
                  AND (m.aciklama LIKE '%İcra%' OR m.aciklama LIKE '%icra%' OR m.turu = 'İcra Kesintisi')
                  $whereFile
                ORDER BY m.yil DESC, m.ay DESC, m.id DESC";

        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }
}

