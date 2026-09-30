<?php

require_once __DIR__ . '/BaseModel.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

class GlobalSearchModel extends Model
{
    public function __construct()
    {
        parent::__construct('persons');
    }

    /**
     * Puantor modüllerinde (Personel, Proje, Kasa & Cari, İcra, İzin, Görev) sadece oturumdaki kullanıcının/firmanın verilerini arar
     *
     * @param string $query Arama kelimesi
     * @param string $category Modül filtresi ('all', 'persons', 'projects', 'financial', 'icra', 'izin', 'tasks')
     * @param int $limit Modül başına maksimum sonuç
     * @param int|null $firmId Firma ID
     * @param int|null $userId Kullanıcı ID
     * @return array
     */
    public function search(string $query, string $category = 'all', int $limit = 8, ?int $firmId = null, ?int $userId = null): array
    {
        $rawQuery = trim($query);
        if (mb_strlen($rawQuery, 'UTF-8') < 1) {
            return [
                'counts' => [
                    'all'       => 0,
                    'persons'   => 0,
                    'projects'  => 0,
                    'financial' => 0,
                    'icra'      => 0,
                    'izin'      => 0,
                    'tasks'     => 0
                ],
                'results' => []
            ];
        }

        $firmId = $firmId ?? (int)($_SESSION['firm_id'] ?? 0);
        $userId = $userId ?? (int)($_SESSION['user']->id ?? 0);
        $parentId = (int)($_SESSION['user']->parent_id ?? 0);
        $ownerId = ($parentId > 0) ? $parentId : $userId;
        $param = '%' . $rawQuery . '%';

        $results = [
            'persons'   => [],
            'projects'  => [],
            'financial' => [],
            'icra'      => [],
            'izin'      => [],
            'tasks'     => []
        ];

        // 1. PERSONELLER (Sadece aktif firmanın veya kullanıcının personelleri)
        if ($category === 'all' || $category === 'persons') {
            try {
                $sql = "SELECT 
                            p.id,
                            p.full_name,
                            p.kimlik_no,
                            p.phone,
                            p.job,
                            p.ekip,
                            p.daily_wages,
                            p.wage_type,
                            p.state,
                            p.email,
                            p.firm_id
                        FROM persons p
                        WHERE (p.deleted_at IS NULL)
                          AND (
                              (:firm_id > 0 AND p.firm_id = :firm_id)
                              OR (:firm_id <= 0 AND p.firm_id IN (SELECT mf.id FROM myfirms mf WHERE mf.user_id = :owner_id AND (mf.deleted_at IS NULL OR mf.deleted_at = '0' OR mf.deleted_at = '')))
                          )
                          AND (
                              p.full_name LIKE :q1
                              OR p.kimlik_no LIKE :q2
                              OR p.phone LIKE :q3
                              OR p.job LIKE :q4
                              OR p.ekip LIKE :q5
                              OR p.email LIKE :q6
                          )
                        ORDER BY p.id DESC
                        LIMIT " . (int)$limit;

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':firm_id'  => $firmId,
                    ':owner_id' => $ownerId,
                    ':q1'       => $param,
                    ':q2'       => $param,
                    ':q3'       => $param,
                    ':q4'       => $param,
                    ':q5'       => $param,
                    ':q6'       => $param
                ]);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $state = (int)($row['state'] ?? 1);
                    $statusText = $state === 1 ? 'Aktif' : ($state === 2 ? 'Ayrıldı' : 'Pasif');
                    $statusClass = $state === 1 ? 'badge-success' : ($state === 2 ? 'badge-danger' : 'badge-secondary');

                    $initials = '';
                    $nameParts = preg_split('/\s+/', trim((string)$row['full_name']));
                    if (!empty($nameParts)) {
                        $initials .= mb_substr($nameParts[0], 0, 1, 'UTF-8');
                        if (count($nameParts) > 1) {
                            $initials .= mb_substr(end($nameParts), 0, 1, 'UTF-8');
                        }
                    }
                    $initials = mb_strtoupper($initials ?: 'PE', 'UTF-8');

                    $tcNo = Security::safeDecrypt($row['kimlik_no'] ?? '');
                    $phone = Security::safeDecrypt($row['phone'] ?? '');

                    $subtitleParts = [];
                    if (!empty($row['job'])) $subtitleParts[] = $row['job'];
                    if (!empty($row['ekip'])) $subtitleParts[] = $row['ekip'];
                    if (!empty($phone) && strlen($phone) <= 25) $subtitleParts[] = $phone;

                    $extraInfo = '';
                    if (!empty($tcNo) && strlen($tcNo) <= 15) {
                        $extraInfo = 'TC: ' . $tcNo;
                    }

                    $encId = Security::encrypt((string)$row['id']);

                    $results['persons'][] = [
                        'id'          => (int)$row['id'],
                        'enc_id'      => $encId,
                        'type'        => 'person',
                        'type_label'  => 'Personel',
                        'title'       => $row['full_name'] ?: 'İsimsiz Personel #' . $row['id'],
                        'subtitle'    => implode(' · ', $subtitleParts) ?: 'Detay bilgisi yok',
                        'badge'       => $statusText,
                        'badge_class' => $statusClass,
                        'extra_info'  => $extraInfo,
                        'date'        => '',
                        'url'         => 'index.php?p=persons/manage&id=' . $encId,
                        'icon'        => 'ti ti-user',
                        'initial'     => $initials,
                        'color_theme' => 'blue'
                    ];
                }
            } catch (\Throwable $e) {
                error_log("GlobalSearchModel persons error: " . $e->getMessage());
            }
        }

        // 2. PROJELER (Sadece aktif firmanın veya kullanıcının projeleri)
        if ($category === 'all' || $category === 'projects') {
            try {
                $sql = "SELECT 
                            pr.id,
                            pr.project_name,
                            pr.budget,
                            pr.status,
                            pr.city,
                            pr.town,
                            pr.start_date,
                            pr.end_date,
                            pr.firm_id
                        FROM projects pr
                        WHERE (pr.deleted_at IS NULL)
                          AND (
                              (:firm_id > 0 AND pr.firm_id = :firm_id)
                              OR (:firm_id <= 0 AND (pr.account_id = :user_id OR pr.firm_id IN (SELECT mf.id FROM myfirms mf WHERE mf.user_id = :owner_id AND (mf.deleted_at IS NULL OR mf.deleted_at = '0' OR mf.deleted_at = ''))))
                          )
                          AND (
                              pr.project_name LIKE :q1
                              OR pr.city LIKE :q2
                              OR pr.town LIKE :q3
                              OR pr.notes LIKE :q4
                          )
                        ORDER BY pr.id DESC
                        LIMIT " . (int)$limit;

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':firm_id'  => $firmId,
                    ':user_id'  => $userId,
                    ':owner_id' => $ownerId,
                    ':q1'       => $param,
                    ':q2'       => $param,
                    ':q3'       => $param,
                    ':q4'       => $param
                ]);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $locParts = [];
                    if (!empty($row['city'])) $locParts[] = $row['city'];
                    if (!empty($row['town'])) $locParts[] = $row['town'];

                    $budgetStr = '';
                    if (!empty($row['budget']) && (float)$row['budget'] > 0) {
                        $budgetStr = number_format((float)$row['budget'], 2, ',', '.') . ' TL';
                    }

                    $encId = Security::encrypt((string)$row['id']);

                    $results['projects'][] = [
                        'id'          => (int)$row['id'],
                        'enc_id'      => $encId,
                        'type'        => 'project',
                        'type_label'  => 'Proje',
                        'title'       => $row['project_name'] ?: 'İsimsiz Proje #' . $row['id'],
                        'subtitle'    => implode(' / ', $locParts) ?: 'Lokasyon Belirtilmemiş',
                        'badge'       => !empty($row['status']) ? $row['status'] : 'Proje',
                        'badge_class' => 'badge-primary',
                        'extra_info'  => $budgetStr,
                        'date'        => !empty($row['start_date']) ? date('d.m.Y', strtotime($row['start_date'])) : '',
                        'url'         => 'index.php?p=projects/manage&id=' . $encId,
                        'icon'        => 'ti ti-folders',
                        'initial'     => 'PR',
                        'color_theme' => 'purple'
                    ];
                }
            } catch (\Throwable $e) {
                error_log("GlobalSearchModel projects error: " . $e->getMessage());
            }
        }

        // 3. KASA & CARİ (Sadece kullanıcının / aktif firmanın kasaları ve cari firmaları)
        if ($category === 'all' || $category === 'financial') {
            try {
                // Kasalar
                $sqlCases = "SELECT 
                                c.id,
                                c.case_name,
                                c.bank_name,
                                c.branch_name,
                                c.case_money_unit,
                                c.description,
                                c.firm_id
                            FROM cases c
                            WHERE (c.deleted_at IS NULL)
                              AND (
                                  (:firm_id > 0 AND c.firm_id = :firm_id)
                                  OR (:firm_id <= 0 AND (c.account_id = :user_id OR c.firm_id IN (SELECT mf.id FROM myfirms mf WHERE mf.user_id = :owner_id AND (mf.deleted_at IS NULL OR mf.deleted_at = '0' OR mf.deleted_at = ''))))
                              )
                              AND (
                                  c.case_name LIKE :q1
                                  OR c.bank_name LIKE :q2
                                  OR c.branch_name LIKE :q3
                                  OR c.description LIKE :q4
                              )
                            ORDER BY c.id DESC
                            LIMIT " . (int)$limit;

                $stmt = $this->db->prepare($sqlCases);
                $stmt->execute([
                    ':firm_id'  => $firmId,
                    ':user_id'  => $userId,
                    ':owner_id' => $ownerId,
                    ':q1'       => $param,
                    ':q2'       => $param,
                    ':q3'       => $param,
                    ':q4'       => $param
                ]);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $details = [];
                    if (!empty($row['bank_name'])) $details[] = $row['bank_name'];
                    if (!empty($row['branch_name'])) $details[] = $row['branch_name'];
                    if (empty($details) && !empty($row['description'])) $details[] = $row['description'];

                    $encId = Security::encrypt((string)$row['id']);

                    $results['financial'][] = [
                        'id'          => (int)$row['id'],
                        'enc_id'      => $encId,
                        'type'        => 'case',
                        'type_label'  => 'Kasa',
                        'title'       => $row['case_name'] ?: 'Kasa #' . $row['id'],
                        'subtitle'    => implode(' - ', $details) ?: 'Kasa Hesabı',
                        'badge'       => 'Kasa',
                        'badge_class' => 'badge-emerald',
                        'extra_info'  => !empty($row['case_money_unit']) ? $row['case_money_unit'] : 'TL',
                        'date'        => '',
                        'url'         => 'index.php?p=financial/case/manage&id=' . $encId,
                        'icon'        => 'ti ti-wallet',
                        'initial'     => 'KS',
                        'color_theme' => 'emerald'
                    ];
                }

                // Cari Firmalar (Sadece oturumdaki kullanıcının veya aktif firmanın cari kayıtları)
                $sqlComp = "SELECT DISTINCT
                                cp.id,
                                cp.company_name,
                                cp.yetkili,
                                cp.phone,
                                cp.email,
                                cp.tax_number
                            FROM companies cp
                            WHERE (
                                cp.user_id = :user_id 
                                OR cp.user_id = :owner_id 
                                OR (:firm_id > 0 AND cp.user_id IN (SELECT mf.user_id FROM myfirms mf WHERE mf.id = :firm_id))
                            )
                            AND (
                                cp.company_name LIKE :q1
                                OR cp.yetkili LIKE :q2
                                OR cp.phone LIKE :q3
                                OR cp.email LIKE :q4
                                OR cp.tax_number LIKE :q5
                            )
                            ORDER BY cp.id DESC
                            LIMIT " . (int)$limit;

                $stmtComp = $this->db->prepare($sqlComp);
                $stmtComp->execute([
                    ':user_id'  => $userId,
                    ':owner_id' => $ownerId,
                    ':firm_id'  => $firmId,
                    ':q1'       => $param,
                    ':q2'       => $param,
                    ':q3'       => $param,
                    ':q4'       => $param,
                    ':q5'       => $param
                ]);

                while ($row = $stmtComp->fetch(PDO::FETCH_ASSOC)) {
                    $details = [];
                    if (!empty($row['yetkili'])) $details[] = 'Yetkili: ' . $row['yetkili'];
                    if (!empty($row['phone'])) $details[] = $row['phone'];
                    if (!empty($row['tax_number'])) $details[] = 'VN: ' . $row['tax_number'];

                    $encId = Security::encrypt((string)$row['id']);

                    $results['financial'][] = [
                        'id'          => (int)$row['id'],
                        'enc_id'      => $encId,
                        'type'        => 'company',
                        'type_label'  => 'Cari Firma',
                        'title'       => $row['company_name'] ?: 'Firma #' . $row['id'],
                        'subtitle'    => implode(' · ', $details) ?: 'Cari Firma Bilgisi',
                        'badge'       => 'Cari',
                        'badge_class' => 'badge-info',
                        'extra_info'  => !empty($row['tax_number']) ? 'VN: ' . $row['tax_number'] : '',
                        'date'        => '',
                        'url'         => 'index.php?p=companies/manage&id=' . $encId,
                        'icon'        => 'ti ti-building',
                        'initial'     => 'CF',
                        'color_theme' => 'cyan'
                    ];
                }
            } catch (\Throwable $e) {
                error_log("GlobalSearchModel financial error: " . $e->getMessage());
            }
        }

        // 4. İCRA DOSYALARI (Sadece kullanıcının / aktif firmanın icra dosyaları)
        if ($category === 'all' || $category === 'icra') {
            try {
                $sql = "SELECT 
                            ic.id,
                            ic.person_id,
                            ic.dosya_no,
                            ic.icra_dairesi,
                            ic.alacakli,
                            ic.toplam_borc,
                            ic.durum,
                            p.full_name as person_name
                        FROM person_icra_files ic
                        LEFT JOIN persons p ON p.id = ic.person_id
                        WHERE (ic.deleted_at IS NULL)
                          AND (
                              (:firm_id > 0 AND (ic.firm_id = :firm_id OR p.firm_id = :firm_id2))
                              OR (:firm_id <= 0 AND (ic.firm_id IN (SELECT mf.id FROM myfirms mf WHERE mf.user_id = :owner_id AND (mf.deleted_at IS NULL OR mf.deleted_at = '0' OR mf.deleted_at = '')) OR p.firm_id IN (SELECT mf2.id FROM myfirms mf2 WHERE mf2.user_id = :owner_id2 AND (mf2.deleted_at IS NULL OR mf2.deleted_at = '0' OR mf2.deleted_at = ''))))
                          )
                          AND (
                              ic.dosya_no LIKE :q1
                              OR ic.icra_dairesi LIKE :q2
                              OR ic.alacakli LIKE :q3
                              OR p.full_name LIKE :q4
                          )
                        ORDER BY ic.id DESC
                        LIMIT " . (int)$limit;

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':firm_id'    => $firmId,
                    ':firm_id2'   => $firmId,
                    ':owner_id'   => $ownerId,
                    ':owner_id2'  => $ownerId,
                    ':q1'         => $param,
                    ':q2'         => $param,
                    ':q3'         => $param,
                    ':q4'         => $param
                ]);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $statusText = ($row['durum'] ?? '') === 'aktif' ? 'Açık Dosya' : 'Kapalı';
                    $statusClass = ($row['durum'] ?? '') === 'aktif' ? 'badge-warning' : 'badge-secondary';

                    $borcStr = '';
                    if (!empty($row['toplam_borc']) && (float)$row['toplam_borc'] > 0) {
                        $borcStr = number_format((float)$row['toplam_borc'], 2, ',', '.') . ' TL';
                    }

                    $subtitleParts = [];
                    if (!empty($row['person_name'])) $subtitleParts[] = 'Personel: ' . $row['person_name'];
                    if (!empty($row['alacakli'])) $subtitleParts[] = 'Alacaklı: ' . $row['alacakli'];

                    $results['icra'][] = [
                        'id'          => (int)$row['id'],
                        'type'        => 'icra',
                        'type_label'  => 'İcra Dosyası',
                        'title'       => ($row['dosya_no'] ?: 'Dosya #' . $row['id']) . (!empty($row['icra_dairesi']) ? ' · ' . $row['icra_dairesi'] : ''),
                        'subtitle'    => implode(' · ', $subtitleParts) ?: 'İcra Dosyası Detayı',
                        'badge'       => $statusText,
                        'badge_class' => $statusClass,
                        'extra_info'  => $borcStr,
                        'date'        => '',
                        'url'         => 'index.php?p=persons/icra-list',
                        'icon'        => 'ti ti-scale',
                        'initial'     => 'İC',
                        'color_theme' => 'amber'
                    ];
                }
            } catch (\Throwable $e) {
                error_log("GlobalSearchModel icra error: " . $e->getMessage());
            }
        }

        // 5. İZİN TALEPLERİ (Sadece kullanıcının / aktif firmanın izin talepleri)
        if ($category === 'all' || $category === 'izin') {
            try {
                $sql = "SELECT 
                            iz.id,
                            iz.personel_id,
                            iz.baslangic_tarihi,
                            iz.bitis_tarihi,
                            iz.gun_sayisi,
                            iz.durum,
                            iz.aciklama,
                            p.full_name as person_name,
                            it.ad as tur_adi
                        FROM izin_talepler iz
                        LEFT JOIN persons p ON p.id = iz.personel_id
                        LEFT JOIN izin_turler it ON it.id = iz.tur_id
                        WHERE (iz.deleted_at IS NULL)
                          AND (
                              (:firm_id > 0 AND (iz.firma_id = :firm_id OR p.firm_id = :firm_id2))
                              OR (:firm_id <= 0 AND (iz.olusturan_id = :user_id OR iz.firma_id IN (SELECT mf.id FROM myfirms mf WHERE mf.user_id = :owner_id AND (mf.deleted_at IS NULL OR mf.deleted_at = '0' OR mf.deleted_at = ''))))
                          )
                          AND (
                              p.full_name LIKE :q1
                              OR iz.aciklama LIKE :q2
                              OR it.ad LIKE :q3
                          )
                        ORDER BY iz.id DESC
                        LIMIT " . (int)$limit;

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':firm_id'  => $firmId,
                    ':firm_id2' => $firmId,
                    ':user_id'  => $userId,
                    ':owner_id' => $ownerId,
                    ':q1'       => $param,
                    ':q2'       => $param,
                    ':q3'       => $param
                ]);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $durum = strtolower((string)($row['durum'] ?? 'bekliyor'));
                    $statusText = $durum === 'onaylandi' ? 'Onaylandı' : ($durum === 'reddedildi' ? 'Reddedildi' : 'Bekliyor');
                    $statusClass = $durum === 'onaylandi' ? 'badge-success' : ($durum === 'reddedildi' ? 'badge-danger' : 'badge-warning');

                    $dateRange = '';
                    if (!empty($row['baslangic_tarihi'])) {
                        $dateRange = date('d.m.Y', strtotime($row['baslangic_tarihi']));
                        if (!empty($row['bitis_tarihi'])) {
                            $dateRange .= ' - ' . date('d.m.Y', strtotime($row['bitis_tarihi']));
                        }
                    }

                    $subtitleParts = [];
                    if (!empty($dateRange)) $subtitleParts[] = $dateRange;
                    if (!empty($row['aciklama'])) $subtitleParts[] = $row['aciklama'];

                    $results['izin'][] = [
                        'id'          => (int)$row['id'],
                        'type'        => 'izin',
                        'type_label'  => 'İzin Talebi',
                        'title'       => ($row['person_name'] ?: 'Personel') . (!empty($row['tur_adi']) ? ' · ' . $row['tur_adi'] : ''),
                        'subtitle'    => implode(' · ', $subtitleParts) ?: 'İzin Detayı',
                        'badge'       => $statusText,
                        'badge_class' => $statusClass,
                        'extra_info'  => !empty($row['gun_sayisi']) ? $row['gun_sayisi'] . ' Gün' : '',
                        'date'        => !empty($row['baslangic_tarihi']) ? date('d.m.Y', strtotime($row['baslangic_tarihi'])) : '',
                        'url'         => 'index.php?p=izin/list',
                        'icon'        => 'ti ti-calendar-time',
                        'initial'     => 'İZ',
                        'color_theme' => 'indigo'
                    ];
                }
            } catch (\Throwable $e) {
                error_log("GlobalSearchModel izin error: " . $e->getMessage());
            }
        }

        // 6. GÖREVLER (Sadece kullanıcının / aktif firmanın görevleri)
        if ($category === 'all' || $category === 'tasks') {
            try {
                $sql = "SELECT 
                            g.id,
                            g.baslik,
                            g.aciklama,
                            g.tarih,
                            g.saat,
                            g.tamamlandi,
                            g.firma_id
                        FROM gorevler g
                        WHERE (g.deleted_at IS NULL)
                          AND (
                              (:firm_id > 0 AND g.firma_id = :firm_id)
                              OR (:firm_id <= 0 AND (g.olusturan_id = :user_id OR g.firma_id IN (SELECT mf.id FROM myfirms mf WHERE mf.user_id = :owner_id AND (mf.deleted_at IS NULL OR mf.deleted_at = '0' OR mf.deleted_at = ''))))
                          )
                          AND (
                              g.baslik LIKE :q1
                              OR g.aciklama LIKE :q2
                          )
                        ORDER BY g.id DESC
                        LIMIT " . (int)$limit;

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':firm_id'  => $firmId,
                    ':user_id'  => $userId,
                    ':owner_id' => $ownerId,
                    ':q1'       => $param,
                    ':q2'       => $param
                ]);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $isDone = (int)($row['tamamlandi'] ?? 0) === 1;
                    $statusText = $isDone ? 'Tamamlandı' : 'Açık Görev';
                    $statusClass = $isDone ? 'badge-success' : 'badge-primary';

                    $results['tasks'][] = [
                        'id'          => (int)$row['id'],
                        'type'        => 'task',
                        'type_label'  => 'Görev',
                        'title'       => $row['baslik'] ?: 'Görev #' . $row['id'],
                        'subtitle'    => $row['aciklama'] ?: 'Görev açıklaması yok',
                        'badge'       => $statusText,
                        'badge_class' => $statusClass,
                        'extra_info'  => !empty($row['tarih']) ? date('d.m.Y', strtotime($row['tarih'])) : '',
                        'date'        => !empty($row['tarih']) ? date('d.m.Y', strtotime($row['tarih'])) : '',
                        'url'         => 'index.php?p=gorevler/list',
                        'icon'        => 'ti ti-checkbox',
                        'initial'     => 'GR',
                        'color_theme' => 'amber'
                    ];
                }
            } catch (\Throwable $e) {
                error_log("GlobalSearchModel tasks error: " . $e->getMessage());
            }
        }

        $counts = [
            'all'       => count($results['persons']) + count($results['projects']) + count($results['financial']) + count($results['icra']) + count($results['izin']) + count($results['tasks']),
            'persons'   => count($results['persons']),
            'projects'  => count($results['projects']),
            'financial' => count($results['financial']),
            'icra'      => count($results['icra']),
            'izin'      => count($results['izin']),
            'tasks'     => count($results['tasks'])
        ];

        return [
            'counts'  => $counts,
            'results' => $results
        ];
    }
}
