<?php
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once 'Model/Persons.php';
require_once 'Model/Projects.php';
require_once 'Model/Bordro.php';
require_once 'App/Helper/security.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

if (isset($perm)) {
    $perm->checkAuthorize('raporlar');
}

$year = (int) ($_SESSION['period_year'] ?? date('Y'));
$month = (int) ($_SESSION['period_month'] ?? date('m'));
$firm_id = $_SESSION['firm_id'] ?? 0;
$report_type = $_GET['report'] ?? '';

$personObj = new Persons();
$firstDayStr = Date::firstDay($month, $year);
$lastDayStr = Date::lastDay($month, $year);

// DB compatibility formats
$startDate = date('Y-m-d', strtotime($firstDayStr));
$endDate = date('Y-m-d', strtotime($lastDayStr));

$bordroObj = new Bordro();
$displayMonth = mb_strtoupper(Date::monthName($month), 'UTF-8');
$periodTitle = Date::monthName($month) . ' ' . $year;

// Function to render report card
function renderReportCard($title, $desc, $icon, $colorClass, $viewUrl = "#", $isActive = false) {
    $btnClass = "btn-primary";
    $cardOpacity = $isActive ? '' : 'opacity-75';
    $linkUrl = $isActive ? $viewUrl : 'javascript:void(0);';
    $badge = $isActive 
        ? '<span class="badge bg-success-lt ms-auto">Aktif</span>' 
        : '<span class="badge bg-secondary-lt ms-auto">Çok Yakında</span>';
    $disabledClass = $isActive ? '' : 'disabled bg-light text-muted border-0 shadow-none';
    $cursorStyle = $isActive ? '' : 'cursor: not-allowed; pointer-events: none;';

    echo '
    <div class="card report-card h-100 shadow-none border ' . $cardOpacity . '">
        <div class="card-body d-flex flex-column p-3">
            <div class="d-flex align-items-center mb-2">
                <div class="avatar avatar-md rounded-2 ' . $colorClass . ' me-3">
                    <i class="ti ' . $icon . ' fs-2"></i>
                </div>
                <div class="d-flex flex-column">
                    <h4 class="card-title text-dark fw-bold mb-0" style="font-size: 14.5px;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h4>
                </div>
                ' . $badge . '
            </div>
            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 12px; line-height: 1.4;">' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '</p>
            <div class="d-flex gap-2 mt-auto pt-2 border-top">
                <a href="' . $linkUrl . '" class="btn btn-sm ' . $btnClass . ' ' . $disabledClass . ' flex-fill fw-bold" style="height: 32px; font-size: 12.5px; ' . $cursorStyle . '">
                    <i class="ti ti-eye me-1"></i> ' . ($isActive ? 'Görüntüle' : 'Hazırlanıyor') . '
                </a>';
                if ($isActive) {
                    echo '<a href="' . $linkUrl . '" class="btn btn-sm btn-outline-secondary btn-icon" style="height: 32px; width: 32px;" title="Raporu Aç">
                            <i class="ti ti-arrow-right"></i>
                          </a>';
                }
    echo '  </div>
        </div>
    </div>';
}
?>

<div class="container-xl mt-3">

<?php if ($report_type == 'puantaj'): ?>
    <!-- ==========================================
         PUANTAJ İCMAL RAPORU
         ========================================== -->
    <?php
    $start_dash = $startDate;
    $end_dash = $endDate;
    $start_nodash = str_replace('-', '', $startDate);
    $end_nodash = str_replace('-', '', $endDate);

    $db = $personObj->connect();
    $queryStr = "
    SELECT 
        p.id, 
        p.full_name, 
        p.job,
        p.kimlik_no,
        p.iban_number,
        p.job_start_date,
        p.job_end_date,
        p.ekip as team_name,
        pr.project_name,
        SUM(CASE WHEN pt.Turu = 'Normal Çalışma' THEN 1 ELSE 0 END) as n_calisma,
        SUM(CASE WHEN pt.Turu = 'Saatlik' THEN pua.saat ELSE 0 END) as s_calisma,
        SUM(CASE WHEN pt.Turu = 'Fazla Çalışma' THEN pua.saat ELSE 0 END) as f_mesai,
        SUM(CASE WHEN pt.Turu = 'Ücretli İzin' THEN 1 ELSE 0 END) as u_izin,
        SUM(CASE WHEN pt.PuantajKod = 'Uİ' THEN 1 ELSE 0 END) as ucr_izin,
        SUM(CASE WHEN pt.PuantajKod = 'DVZ' THEN 1 ELSE 0 END) as dvz,
        SUM(CASE WHEN pt.PuantajKod IN ('R', 'R-', 'R+') THEN 1 ELSE 0 END) as rapor
    FROM persons p
    LEFT JOIN projects pr ON p.project_id = pr.id
    LEFT JOIN puantaj pua ON p.id = pua.person AND ((pua.gun >= ? AND pua.gun <= ?) OR (pua.gun >= ? AND pua.gun <= ?))
    LEFT JOIN puantajturu pt ON pua.puantaj_id = pt.id
    WHERE p.firm_id = ? AND p.deleted_at IS NULL
    GROUP BY p.id
    ORDER BY p.full_name ASC
    ";
    $stmt = $db->prepare($queryStr);
    $stmt->execute([$start_dash, $end_dash, $start_nodash, $end_nodash, $firm_id]);
    $raporData = $stmt->fetchAll(PDO::FETCH_OBJ);
     
    // Verileri işleme (Şifreli alanları çözme)
    $total_person_count = count($raporData);
    $total_normal_gun = 0;
    $total_fazla_mesai = 0;
    $total_izin_gun = 0;

    foreach ($raporData as $row) {
        $row->iban_number = Security::safeDecrypt($row->iban_number ?? '');
        $row->kimlik_no = Security::safeDecrypt($row->kimlik_no ?? '');
        $total_normal_gun += (float) ($row->n_calisma ?? 0);
        $total_fazla_mesai += (float) ($row->f_mesai ?? 0);
        $total_izin_gun += (float) ($row->u_izin ?? 0) + (float) ($row->ucr_izin ?? 0) + (float) ($row->rapor ?? 0);
    }

    // Projeleri ve proje bazlı normal çalışma günlerini çek
    $projectObj = new Projects();
    $projects = $projectObj->getProjectsByFirm($firm_id);

    $projectDaysQuery = "
    SELECT 
        pua.person, 
        pua.project_id, 
        SUM(CASE WHEN pt.Turu = 'Normal Çalışma' THEN 1 ELSE 0 END) as n_calisma
    FROM puantaj pua
    LEFT JOIN puantajturu pt ON pua.puantaj_id = pt.id
    INNER JOIN persons p ON p.id = pua.person
    WHERE p.firm_id = ? 
      AND p.deleted_at IS NULL
      AND ((pua.gun >= ? AND pua.gun <= ?) OR (pua.gun >= ? AND pua.gun <= ?))
    GROUP BY pua.person, pua.project_id
    ";
    $stmtProjectDays = $db->prepare($projectDaysQuery);
    $stmtProjectDays->execute([$firm_id, $start_dash, $end_dash, $start_nodash, $end_nodash]);
    $projectDaysData = $stmtProjectDays->fetchAll(PDO::FETCH_OBJ);

    $personProjectDays = [];
    foreach ($projectDaysData as $pData) {
        $personProjectDays[$pData->person][$pData->project_id] = (float) $pData->n_calisma;
    }
    ?>

    <!-- KPI Özet Metrik Kartları -->
    <div class="row row-deck row-cards mb-3 g-2">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-azure-lt text-azure avatar avatar-md rounded-2">
                                <i class="ti ti-users fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= $total_person_count ?></div>
                            <div class="text-muted small" style="font-size: 11.5px;">Toplam Personel</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary-lt text-primary avatar avatar-md rounded-2">
                                <i class="ti ti-calendar-check fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= number_format($total_normal_gun, 1, ',', '.') ?> <span class="fs-6 fw-normal text-muted">Gün</span></div>
                            <div class="text-muted small" style="font-size: 11.5px;">Normal Çalışma</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger-lt text-danger avatar avatar-md rounded-2">
                                <i class="ti ti-clock-bolt fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= number_format($total_fazla_mesai, 1, ',', '.') ?> <span class="fs-6 fw-normal text-muted">Saat</span></div>
                            <div class="text-muted small" style="font-size: 11.5px;">Fazla Mesai</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning-lt text-warning avatar avatar-md rounded-2">
                                <i class="ti ti-umbrella fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= number_format($total_izin_gun, 1, ',', '.') ?> <span class="fs-6 fw-normal text-muted">Gün</span></div>
                            <div class="text-muted small" style="font-size: 11.5px;">İzin / Rapor Toplamı</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="card-header-icon">
                            <i class="ti ti-clock-check"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Puantaj İcmal Raporu</h4>
                                <span class="badge bg-blue-lt"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Personel bazlı çalışma günleri, mesai saatleri, izinler ve proje dağılımı</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <a href="index.php?p=raporlar/list" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-arrow-left me-1"></i> Raporlara Dön
                        </a>
                        <a href="pages/raporlar/puantaj-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary" data-tooltip="Excel Dosyası İndir" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-file-excel text-success me-1"></i> Excel
                        </a>
                        <button type="button" id="customBtnPdf" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-file-type-pdf text-danger me-1"></i> PDF
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                                <i class="ti ti-columns me-1"></i> Sütunlar
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2" id="customColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="padding: 4px !important;">
                    <table class="table data-table table-hover text-nowrap w-100" id="puantajDataTable" style="width: 100% !important;">
                        <thead>
                            <tr>
                                <th>Personel Adı</th>
                                <th>TC Kimlik No</th>
                                <th>IBAN No</th>
                                <th>İşe Giriş</th>
                                <th>İşten Çıkış</th>
                                <th>Ekip</th>
                                <th>Proje</th>
                                <th>Ünvan / Meslek</th>
                                <th class="text-center">Çalışma (Gün)</th>
                                <th class="text-center">Saatlik Çal.</th>
                                <th class="text-center">Fazla Mesai</th>
                                <th class="text-center">Ücretli İzin</th>
                                <th class="text-center">Ücretsiz İzin</th>
                                <th class="text-center">Rapor</th>
                                <th class="text-center">Devamsız</th>
                                <?php foreach ($projects as $proj): ?>
                                    <th class="text-center"><?= htmlspecialchars($proj->project_name, ENT_QUOTES, 'UTF-8') ?></th>
                                <?php endforeach; ?>
                                <th class="text-center">Proje Yok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($raporData as $r): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-xs rounded-circle bg-blue-lt text-blue fw-bold me-2" style="font-size: 11px;">
                                            <?= htmlspecialchars(mb_substr($r->full_name, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($r->full_name, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($r->kimlik_no ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r->iban_number ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r->job_start_date ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r->job_end_date ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r->team_name ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($r->project_name ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small text-secondary"><?= htmlspecialchars($r->job ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center"><span class="badge bg-azure-lt fw-bold"><?= (float) $r->n_calisma ?></span></td>
                                <td class="text-center fw-medium"><?= (float) $r->s_calisma ?: '-' ?></td>
                                <td class="text-center text-danger fw-bold"><?= (float) $r->f_mesai ?: '-' ?></td>
                                <td class="text-center text-info"><?= (float) $r->u_izin ?: '-' ?></td>
                                <td class="text-center text-warning"><?= (float) $r->ucr_izin ?: '-' ?></td>
                                <td class="text-center text-purple"><?= (float) $r->rapor ?: '-' ?></td>
                                <td class="text-center text-red"><?= (float) $r->dvz ?: '-' ?></td>
                                <?php foreach ($projects as $proj): ?>
                                    <?php $days = $personProjectDays[$r->id][$proj->id] ?? 0; ?>
                                    <td class="text-center fw-medium"><?= (float) $days ?: '-' ?></td>
                                <?php endforeach; ?>
                                <?php $noProjDays = ($personProjectDays[$r->id][0] ?? 0) + ($personProjectDays[$r->id][''] ?? 0); ?>
                                <td class="text-center fw-medium text-warning"><?= (float) $noProjDays ?: '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($report_type == 'banka'): ?>
    <!-- ==========================================
         BANKA ÖDEME LİSTESİ
         ========================================== -->
    <?php
    $personsForBank = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);
    $bankData = [];
    $db = $personObj->connect();
    $total_bank_pay = 0;

    foreach ($personsForBank as $p_item) {
        $p = $personObj->find($p_item->id);
        $res = $bordroObj->getPersonSalaryAndWageCut($p->id, $firstDayStr, $lastDayStr);
        $netPay = ($res->gelir ?? 0) - ($res->odeme ?? 0);
        
        if ($netPay > 0) {
            $total_bank_pay += $netPay;
            $projName = '-';
            if (!empty($p->project_id)) {
                $pstmt = $db->prepare("SELECT project_name FROM projects WHERE id = ?");
                $pstmt->execute([$p->project_id]);
                $prow = $pstmt->fetch(PDO::FETCH_OBJ);
                if ($prow) {
                    $projName = $prow->project_name;
                }
            }
            
            $bankData[] = (object) [
                'id' => $p->id,
                'full_name' => $p->full_name,
                'kimlik_no' => Security::safeDecrypt($p->kimlik_no ?? ''),
                'iban_number' => Security::safeDecrypt($p->iban_number ?? ''),
                'team_name' => $p->ekip ?? '-',
                'project_name' => $projName,
                'job' => $p->job ?? '-',
                'amount' => $netPay
            ];
        }
    }
    $total_bank_persons = count($bankData);
    $avg_bank_pay = $total_bank_persons > 0 ? ($total_bank_pay / $total_bank_persons) : 0;
    ?>

    <!-- KPI Özet Metrik Kartları -->
    <div class="row row-deck row-cards mb-3 g-2">
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success-lt text-success avatar avatar-md rounded-2">
                                <i class="ti ti-credit-card-pay fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= Helper::formattedMoney($total_bank_pay) ?></div>
                            <div class="text-muted small" style="font-size: 11.5px;">Toplam Ödenecek Net Tutar</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-azure-lt text-azure avatar avatar-md rounded-2">
                                <i class="ti ti-users fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= $total_bank_persons ?></div>
                            <div class="text-muted small" style="font-size: 11.5px;">Ödeme Yapılacak Personel</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-info-lt text-info avatar avatar-md rounded-2">
                                <i class="ti ti-calculator fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= Helper::formattedMoney($avg_bank_pay) ?></div>
                            <div class="text-muted small" style="font-size: 11.5px;">Ortalama Ödeme Tutarı</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="card-header-icon">
                            <i class="ti ti-building-bank"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Banka Ödeme Listesi</h4>
                                <span class="badge bg-info-lt"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Banka maaş transferleri için IBAN ve ödenecek net hak ediş listesi</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <a href="index.php?p=raporlar/list" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-arrow-left me-1"></i> Raporlara Dön
                        </a>
                        <a href="pages/raporlar/bank-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary" data-tooltip="Excel Dosyası İndir" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-file-excel text-success me-1"></i> Excel
                        </a>
                        <button type="button" id="customBankBtnPdf" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-file-type-pdf text-danger me-1"></i> PDF
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                                <i class="ti ti-columns me-1"></i> Sütunlar
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2" id="customBankColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="padding: 4px !important;">
                    <table class="table data-table table-hover text-nowrap w-100" id="bankDataTable" style="width: 100% !important;">
                        <thead>
                            <tr>
                                <th>Personel Adı</th>
                                <th>TC Kimlik No</th>
                                <th>IBAN No</th>
                                <th>Ekip</th>
                                <th>Proje</th>
                                <th>Ünvan / Meslek</th>
                                <th class="text-end">Ödenecek Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bankData as $b): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-xs rounded-circle bg-info-lt text-info fw-bold me-2" style="font-size: 11px;">
                                            <?= htmlspecialchars(mb_substr($b->full_name, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($b->full_name, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($b->kimlik_no ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code class="text-body fw-medium"><?= htmlspecialchars($b->iban_number ?: '-', ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars($b->team_name ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($b->project_name ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small text-secondary"><?= htmlspecialchars($b->job ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end fw-bold text-success" data-order="<?= (float) $b->amount ?>"><?= Helper::formattedMoney($b->amount) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($report_type == 'bordro'): ?>
    <!-- ==========================================
         BORDRO YAZDIRMA LİSTESİ
         ========================================== -->
    <?php
    $personsForBordro = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);
    $total_bordro_persons = count($personsForBordro);
    ?>

    <!-- KPI Özet Metrik Kartları -->
    <div class="row row-deck row-cards mb-3 g-2">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-teal-lt text-teal avatar avatar-md rounded-2">
                                <i class="ti ti-users fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= $total_bordro_persons ?></div>
                            <div class="text-muted small" style="font-size: 11.5px;">Toplam Bordro Personeli</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary-lt text-primary avatar avatar-md rounded-2">
                                <i class="ti ti-checkbox fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark" id="selectedCountBadge">0</div>
                            <div class="text-muted small" style="font-size: 11.5px;">Seçilen Personel Sayısı</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="card-header-icon">
                            <i class="ti ti-file-invoice"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Bordro Yazdırma Listesi</h4>
                                <span class="badge bg-teal-lt"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Yazdırmak istediğiniz personelleri seçip toplu çıktı alabilirsiniz</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <a href="index.php?p=raporlar/list" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-arrow-left me-1"></i> Raporlara Dön
                        </a>
                        <button type="button" class="btn btn-sm btn-primary" id="btnPrintSelectedBordro" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                            <i class="ti ti-printer me-1"></i> Seçilileri Yazdır
                        </button>
                    </div>
                </div>

                <div class="table-responsive" style="padding: 4px !important;">
                    <table class="table data-table table-hover text-nowrap w-100" id="bordroSelectionTable" style="width: 100% !important;">
                        <thead>
                            <tr>
                                <th style="width: 40px; min-width: 40px;" class="text-center no-export" data-orderable="false">
                                    <input type="checkbox" class="form-check-input" id="selectAllBordro">
                                </th>
                                <th>Personel Adı</th>
                                <th>TC Kimlik No</th>
                                <th>Ekip</th>
                                <th>Ünvan / Meslek</th>
                                <th style="width: 80px;" class="text-center no-export" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($personsForBordro)): ?>
                                <?php foreach ($personsForBordro as $p_item): 
                                    $p = $personObj->find($p_item->id);
                                    if (!$p) continue;
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input row-check" value="<?= Security::encrypt($p->id) ?>">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-xs rounded-circle bg-teal-lt text-teal fw-bold me-2" style="font-size: 11px;">
                                                <?= htmlspecialchars(mb_substr($p->full_name, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($p->full_name, ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars(Security::safeDecrypt($p->kimlik_no ?? '') ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($p->ekip ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="small text-secondary"><?= htmlspecialchars($p->job ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center">
                                        <a href="index.php?p=payroll/pay-slip&id=<?= Security::encrypt($p->id) ?>&month=<?= Security::encrypt($month) ?>&year=<?= Security::encrypt($year) ?>" target="_blank" class="btn btn-sm btn-icon btn-ghost-primary" title="Pusula Önizle">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectAll = document.getElementById('selectAllBordro');
            var checks = document.querySelectorAll('.row-check');
            var badge = document.getElementById('selectedCountBadge');

            function updateCount() {
                var checkedCount = document.querySelectorAll('.row-check:checked').length;
                if (badge) {
                    badge.innerText = checkedCount;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checks.forEach(function(cb) { cb.checked = selectAll.checked; });
                    updateCount();
                });
            }

            checks.forEach(function(cb) {
                cb.addEventListener('change', function() {
                    updateCount();
                    if (!this.checked && selectAll) {
                        selectAll.checked = false;
                    }
                });
            });

            var btnPrint = document.getElementById('btnPrintSelectedBordro');
            if (btnPrint) {
                btnPrint.addEventListener('click', function() {
                    var selected = Array.from(document.querySelectorAll('.row-check:checked')).map(function(cb) { return cb.value; });
                    if (selected.length === 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Personel Seçilmedi',
                                text: 'Lütfen yazdırmak istediğiniz en az bir personel seçiniz.',
                                confirmButtonText: 'Tamam',
                                customClass: { confirmButton: 'btn btn-primary' }
                            });
                        } else {
                            alert('Lütfen en az bir personel seçiniz.');
                        }
                        return;
                    }
                    
                    var ids = selected.join(',');
                    var url = 'index.php?p=raporlar/bordro-yazdir&ids=' + encodeURIComponent(ids) + '&month=<?= Security::encrypt($month) ?>&year=<?= Security::encrypt($year) ?>';
                    window.open(url, '_blank');
                });
            }
        });
    </script>

<?php elseif ($report_type == 'kesinti'): ?>
    <!-- ==========================================
         KESİNTİ DETAY RAPORU
         ========================================== -->
    <?php
    require_once 'Model/DefinesModel.php';
    $definesObj = new DefinesModel();
    $db = $personObj->connect();
    $kesinti_ids = $definesObj->getExpenseTypes(2);
    
    $queryStr = "
    SELECT 
        p.full_name,
        mgk.turu,
        mgk.tutar,
        mgk.gun,
        mgk.aciklama,
        dt.name as kategori_adi
    FROM maas_gelir_kesinti mgk
    JOIN persons p ON mgk.person_id = p.id
    LEFT JOIN defines dt ON mgk.kategori = dt.id
    WHERE p.firm_id = ? 
      AND mgk.kategori IN ($kesinti_ids)
      AND CAST(REPLACE(mgk.gun, '-', '') AS UNSIGNED) >= ? 
      AND CAST(REPLACE(mgk.gun, '-', '') AS UNSIGNED) <= ?
    ORDER BY mgk.gun DESC
    ";
    $stmt = $db->prepare($queryStr);
    $stmt->execute([$firm_id, $firstDayStr, $lastDayStr]);
    $kesintiData = $stmt->fetchAll(PDO::FETCH_OBJ);

    $total_kesinti_tutari = 0;
    foreach ($kesintiData as $k) {
        $total_kesinti_tutari += (float) ($k->tutar ?? 0);
    }
    $total_kesinti_adedi = count($kesintiData);
    ?>

    <!-- KPI Özet Metrik Kartları -->
    <div class="row row-deck row-cards mb-3 g-2">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger-lt text-danger avatar avatar-md rounded-2">
                                <i class="ti ti-cash-off fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= Helper::formattedMoney($total_kesinti_tutari) ?></div>
                            <div class="text-muted small" style="font-size: 11.5px;">Toplam Kesinti Tutarı</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-none border">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning-lt text-warning avatar avatar-md rounded-2">
                                <i class="ti ti-list-check fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="fw-bold fs-3 text-dark"><?= $total_kesinti_adedi ?></div>
                            <div class="text-muted small" style="font-size: 11.5px;">Toplam Kesinti Kaydı</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="card-header-icon">
                            <i class="ti ti-scissors"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Kesinti Detay Raporu</h4>
                                <span class="badge bg-danger-lt"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Personel bazlı avans, icra, nafaka ve özel kesinti hareketleri</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <a href="index.php?p=raporlar/list" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-arrow-left me-1"></i> Raporlara Dön
                        </a>
                        <a href="pages/raporlar/kesinti-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary" data-tooltip="Excel Dosyası İndir" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-file-excel text-success me-1"></i> Excel
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print();" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-printer me-1"></i> Yazdır
                        </button>
                    </div>
                </div>

                <div class="table-responsive" style="padding: 4px !important;">
                    <table class="table data-table table-hover text-nowrap w-100" id="kesintiDataTable" style="width: 100% !important;">
                        <thead>
                            <tr>
                                <th>Personel Adı</th>
                                <th>Tarih</th>
                                <th>Kategori</th>
                                <th>Kesinti Türü</th>
                                <th>Açıklama</th>
                                <th class="text-end">Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kesintiData as $k): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-xs rounded-circle bg-danger-lt text-danger fw-bold me-2" style="font-size: 11px;">
                                            <?= htmlspecialchars(mb_substr($k->full_name, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($k->full_name, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td><?= date('d.m.Y', strtotime($k->gun)) ?></td>
                                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($k->kategori_adi ?? 'Diğer', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="small text-secondary"><?= htmlspecialchars($k->turu, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($k->aciklama ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end fw-bold text-danger" data-order="<?= (float) $k->tutar ?>">-<?= Helper::formattedMoney($k->tutar) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ==========================================
         RAPORLAR ANA SAYFA (DASHBOARD)
         ========================================== -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="card-header-icon">
                            <i class="ti ti-chart-dots-3"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Raporlar & Analiz Merkezi</h4>
                                <span class="badge bg-blue-lt"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Maaş, bordro, puantaj, banka ödeme ve kesinti analiz raporları</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3">
                    <!-- Modül Kartları Izgarası -->
                    <div class="row row-cards g-3">
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Puantaj İcmal Raporu", "Toplam çalışma günleri, saatlik çalışma, fazla mesai, izin ve devamsızlık dökümü.", "ti-clock-check", "bg-purple-lt text-purple", "index.php?p=raporlar/list&report=puantaj&year=$year&months=$month", true) ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Banka Ödeme Listesi", "Bankaya gönderilecek personellere ait IBAN ve net maaş hakediş tutarları listesi.", "ti-building-bank", "bg-info-lt text-info", "index.php?p=raporlar/list&report=banka&year=$year&months=$month", true) ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Bordro Yazdırma", "Personel bazlı detaylı ücret pusulalarını görüntüleyin ve toplu olarak yazdırın.", "ti-file-invoice", "bg-teal-lt text-teal", "index.php?p=raporlar/list&report=bordro&year=$year&months=$month", true) ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Kesinti Raporu", "Personel bazlı avans, icra, nafaka ve diğer kesinti hareketlerinin detaylı dökümü.", "ti-scissors", "bg-danger-lt text-danger", "index.php?p=raporlar/list&report=kesinti&year=$year&months=$month", true) ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Maaş İcmal Raporu", "Dönem bazlı personel maaş özet raporu. Brüt hak ediş, kesintiler ve net bilgileri.", "ti-chart-bar", "bg-dark-lt text-dark") ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("SGK Prim Bildirgesi", "SGK prim bildirge raporu. Personel prim tutarları ve işveren payları analizi.", "ti-shield-check", "bg-warning-lt text-warning") ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Vergi Raporu", "Gelir vergisi ve damga vergisi detaylı raporu. Vergi matrahları ve istisnalar.", "ti-receipt-2", "bg-danger-lt text-danger") ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Maliyet Raporu", "İşveren maliyet analizi raporu. Toplam personel ve yan hak maliyet dağılımı.", "ti-chart-pie", "bg-dark-lt text-secondary") ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Sodexo / Yemek Raporu", "Personel yemek kartı (Sodexo vb.) hak edişlerinin listesi ve dışa aktarımı.", "ti-tools-kitchen-2", "bg-success-lt text-success") ?>
                        </div>
                    </div>

                    <!-- Hızlı İndirme Bölümü -->
                    <div class="card shadow-none border mt-4">
                        <div class="card-header bg-light py-2 px-3 border-bottom">
                            <h4 class="card-title text-muted mb-0 small fw-bold" style="font-size: 12px;">
                                <i class="ti ti-download me-1"></i> Tek Tıkla Hızlı Dışa Aktarma (<?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?>)
                            </h4>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-md-3 col-6">
                                    <a href="pages/raporlar/puantaj-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-outline-purple w-100 text-nowrap" style="height: 34px; font-size: 12.5px;">
                                        <i class="ti ti-file-spreadsheet me-1"></i> Puantaj İcmal (Excel)
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="pages/raporlar/bank-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-outline-info w-100 text-nowrap" style="height: 34px; font-size: 12.5px;">
                                        <i class="ti ti-building-bank me-1"></i> Banka Listesi (Excel)
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="pages/raporlar/kesinti-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-outline-danger w-100 text-nowrap" style="height: 34px; font-size: 12.5px;">
                                        <i class="ti ti-scissors me-1"></i> Kesinti Listesi (Excel)
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="index.php?p=raporlar/list&report=bordro&year=<?= $year ?>&months=<?= $month ?>" class="btn btn-outline-dark w-100 text-nowrap" style="height: 34px; font-size: 12.5px;">
                                        <i class="ti ti-printer me-1"></i> Bordroları Yazdır
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

</div>

<style>
/* Tablonun etrafındaki eşit 4px dış boşluk ve tam genişlik */
.card .table-responsive {
    padding: 4px !important;
    margin: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
    overflow-x: auto !important;
}

/* Belirgin dış çerçeve, yuvarlak 10px köşeler ve tam %100 genişlik */
table.data-table,
table.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border-radius: 10px !important;
    border: 1px solid var(--tblr-border-color, #cbd5e1) !important;
    overflow: hidden !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
}

table.data-table thead tr:first-child th:first-child { border-top-left-radius: 9px !important; }
table.data-table thead tr:first-child th:last-child { border-top-right-radius: 9px !important; }
table.data-table tbody tr:last-child td:first-child { border-bottom-left-radius: 9px !important; }
table.data-table tbody tr:last-child td:last-child { border-bottom-right-radius: 9px !important; }

/* Başlık hücreleri ve iç kenarlıklar */
table.data-table thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 12px !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 12px !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    vertical-align: middle !important;
}
table.data-table thead th:last-child {
    border-right: none !important;
}

/* Gövde satırları */
table.data-table tbody td {
    padding: 8px 12px !important;
    font-size: 13.5px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table.data-table tbody td:last-child {
    border-right: none !important;
}
table.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Standart Card Header İkon Kutusu */
.card-header-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: rgba(32, 107, 196, 0.1);
    color: var(--tblr-primary, #206bc4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

/* Rapor Kartları */
.report-card {
    transition: all 0.2s ease-in-out;
    border-radius: 10px !important;
}
.report-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06) !important;
}

/* Dark Mode Uyumları */
[data-bs-theme="dark"] table.data-table {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table.data-table thead th {
    background: #1e293b !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table.data-table tbody td {
    color: #e2e8f0 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table.data-table tbody tr:hover td {
    background-color: #1e293b !important;
}
</style>

<script>
$(document).ready(function() {
    // Bordro Selection Table
    if ($("#bordroSelectionTable").length > 0 && typeof window.createDataTable === "function") {
        window.createDataTable("#bordroSelectionTable", {
            pageLength: 25,
            order: [[1, "asc"]],
            columnDefs: [
                { targets: [0, 5], orderable: false, searchable: false }
            ]
        });
    }

    // Kesinti Data Table
    if ($("#kesintiDataTable").length > 0 && typeof window.createDataTable === "function") {
        window.createDataTable("#kesintiDataTable", {
            pageLength: 25,
            order: [[1, "desc"]],
            columnDefs: [
                { targets: 5, className: "text-end" }
            ]
        });
    }
});
</script>
