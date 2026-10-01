<?php
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once 'Model/Persons.php';
require_once 'Model/Projects.php';
require_once 'Model/Bordro.php';
require_once 'Model/DefinesModel.php';
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
    $btnClass = $isActive ? "btn-primary" : "btn-light";
    $cardOpacity = $isActive ? '' : 'opacity-75';
    $linkUrl = $isActive ? $viewUrl : 'javascript:void(0);';
    $badge = $isActive 
        ? '<span class="badge bg-success-lt ms-auto">Aktif</span>' 
        : '<span class="badge bg-secondary-lt ms-auto">Çok Yakında</span>';
    $disabledClass = $isActive ? '' : 'disabled bg-light text-muted border-0 shadow-none';
    $cursorStyle = $isActive ? '' : 'cursor: not-allowed; pointer-events: none;';

    echo '
    <div class="card report-card h-100 border ' . $cardOpacity . '" style="border-radius: 12px;">
        <div class="card-body d-flex flex-column p-3">
            <div class="d-flex align-items-center mb-2">
                <div class="avatar avatar-md rounded-2 ' . $colorClass . ' me-3">
                    <i class="ti ' . $icon . ' fs-2"></i>
                </div>
                <div class="d-flex flex-column">
                    <h4 class="card-title text-dark fw-bold mb-0" style="font-size: 15px; letter-spacing: -0.2px;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h4>
                </div>
                ' . $badge . '
            </div>
            <p class="text-secondary mb-3 flex-grow-1" style="font-size: 13px; line-height: 1.45;">' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '</p>
            <div class="d-flex gap-2 mt-auto pt-2 border-top">
                <a href="' . $linkUrl . '" class="btn btn-sm ' . $btnClass . ' ' . $disabledClass . ' flex-fill fw-semibold" style="height: 34px; font-size: 13px; ' . $cursorStyle . '">
                    <i class="ti ti-eye me-1"></i> ' . ($isActive ? 'Görüntüle' : 'Hazırlanıyor') . '
                </a>';
                if ($isActive) {
                    echo '<a href="' . $linkUrl . '" class="btn btn-sm btn-outline-secondary btn-icon" style="height: 34px; width: 34px;" title="Raporu Aç">
                            <i class="ti ti-arrow-right"></i>
                          </a>';
                }
    echo '  </div>
        </div>
    </div>';
}
?>

<div class="container-xl mt-1" id="raporlarPage">

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

    <!-- Page Header (Standart Başlık Alanı) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-azure-lt text-azure shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-clock-check" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Puantaj İcmal Raporu
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Personel bazlı çalışma günleri, saatlik mesailer, izinler ve proje dağılımı
                        </div>
                    </div>
                </div>
            </div>
            <!-- Header Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="/raporlar" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-arrow-left me-1"></i> Raporlara Dön
                    </a>
                    <a href="pages/raporlar/puantaj-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary" data-tooltip="Excel Dosyası İndir" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-file-excel text-success me-1"></i> Excel
                    </a>
                    <button type="button" id="customBtnPdf" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-file-type-pdf text-danger me-1"></i> PDF
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-layout-columns me-1"></i> Sütunlar
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="customColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3">
        <!-- Kart 1: Toplam Personel -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM PERSONEL</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_person_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Dönem Personeli</span>
                        <span class="badge bg-secondary-lt fw-semibold" style="font-size: 10px;"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Normal Çalışma -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">NORMAL ÇALIŞMA</span>
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-calendar-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_normal_gun, 1, ',', '.') ?> <span class="fs-5 fw-normal text-muted">Gün</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Toplam Fiili Gün</span>
                        <span class="badge bg-primary-lt fw-semibold" style="font-size: 10px;">Tam Çalışma</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Fazla Mesai -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">FAZLA MESAİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-clock-bolt" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_fazla_mesai, 1, ',', '.') ?> <span class="fs-5 fw-normal text-muted">Saat</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Ek Mesai Toplamı</span>
                        <span class="badge bg-danger-lt fw-semibold" style="font-size: 10px;">Fazla Çalışma</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: İzin ve Rapor -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">İZİN VE RAPOR</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-umbrella" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_izin_gun, 1, ',', '.') ?> <span class="fs-5 fw-normal text-muted">Gün</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Ücretli / Ücretsiz / Rapor</span>
                        <span class="badge bg-warning-lt fw-semibold" style="font-size: 10px;">Tüm İzinler</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card" style="border-radius: 12px;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                            <i class="ti ti-clock-check" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Puantaj İcmal Tablosu</h4>
                                <span class="badge bg-blue-lt"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Personel bazlı çalışma günleri, mesai saatleri, izinler ve proje dağılımı</p>
                        </div>
                    </div>

                    <!-- Fast Instant Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <div class="input-icon" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="puantaj-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off" style="height: 32px; font-size: 13px;">
                            <button type="button" id="puantaj-search-clear" class="btn btn-sm btn-icon btn-ghost-secondary d-none position-absolute end-0 top-0" style="height: 32px; width: 32px;" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
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
                                        <span class="avatar avatar-xs rounded-circle bg-blue-lt text-blue fw-bold me-2" style="font-size: 12px;">
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
                                <td><span class="text-secondary"><?= htmlspecialchars($r->job ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
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

    <!-- Page Header (Standart Başlık Alanı) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-info-lt text-info shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-building-bank" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Banka Ödeme Listesi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Banka maaş transferleri için IBAN ve ödenecek net hak ediş listesi
                        </div>
                    </div>
                </div>
            </div>
            <!-- Header Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="/raporlar" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-arrow-left me-1"></i> Raporlara Dön
                    </a>
                    <a href="pages/raporlar/bank-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary" data-tooltip="Excel Dosyası İndir" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-file-excel text-success me-1"></i> Excel
                    </a>
                    <button type="button" id="customBankBtnPdf" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-file-type-pdf text-danger me-1"></i> PDF
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-layout-columns me-1"></i> Sütunlar
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="customBankColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3">
        <!-- Kart 1: Toplam Ödenecek Net Tutar -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM NET TUTAR</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-credit-card-pay" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_bank_pay) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Ödenecek Net Tutar</span>
                        <span class="badge bg-success-lt fw-semibold" style="font-size: 10px;">Net Maaş</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Ödeme Yapılacak Personel -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ÖDENECEK KİŞİ SAYISI</span>
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_bank_persons, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Hak Edişli Personel</span>
                        <span class="badge bg-primary-lt fw-semibold" style="font-size: 10px;">Aktif Liste</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Ortalama Ödeme -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ORTALAMA ÖDEME</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-calculator" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($avg_bank_pay) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Kişi Başı Ortalama</span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;">Ortalama</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Dönem Bilgisi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">DÖNEM BİLGİSİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-calendar" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Banka Ödeme Dönemi</span>
                        <span class="badge bg-teal-lt fw-semibold" style="font-size: 10px;">Banka Listesi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card" style="border-radius: 12px;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                            <i class="ti ti-building-bank" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Banka Ödeme Listesi Tablosu</h4>
                                <span class="badge bg-info-lt"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Banka maaş transferleri için IBAN ve ödenecek net hak ediş listesi</p>
                        </div>
                    </div>

                    <!-- Fast Instant Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <div class="input-icon" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="bank-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off" style="height: 32px; font-size: 13px;">
                            <button type="button" id="bank-search-clear" class="btn btn-sm btn-icon btn-ghost-secondary d-none position-absolute end-0 top-0" style="height: 32px; width: 32px;" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
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
                                        <span class="avatar avatar-xs rounded-circle bg-info-lt text-info fw-bold me-2" style="font-size: 12px;">
                                            <?= htmlspecialchars(mb_substr($b->full_name, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($b->full_name, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($b->kimlik_no ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code class="text-body fw-medium"><?= htmlspecialchars($b->iban_number ?: '-', ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars($b->team_name ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($b->project_name ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="text-secondary"><?= htmlspecialchars($b->job ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
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

    <!-- Page Header (Standart Başlık Alanı) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-teal-lt text-teal shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-file-invoice" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Bordro Yazdırma Listesi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Yazdırmak istediğiniz personelleri seçip toplu veya tekli ücret pusulası çıktısı alabilirsiniz
                        </div>
                    </div>
                </div>
            </div>
            <!-- Header Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="/raporlar" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-arrow-left me-1"></i> Raporlara Dön
                    </a>
                    <button type="button" class="btn btn-sm btn-dark" id="btnPrintSelectedBordro" style="height: 32px; padding: 4px 14px; font-size: 12.5px; background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-printer me-1"></i> Seçilileri Yazdır
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3">
        <!-- Kart 1: Toplam Bordro Personeli -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM PERSONEL</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_bordro_persons, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Bordro Hesaplanacak</span>
                        <span class="badge bg-secondary-lt fw-semibold" style="font-size: 10px;">Toplam Liste</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Seçilen Personel -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">SEÇİLEN PERSONEL</span>
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-checkbox" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <span id="selectedCountBadge">0</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Yazdırılacak Seçili Kişi</span>
                        <span class="badge bg-primary-lt fw-semibold" style="font-size: 10px;">Seçim Durumu</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Dönem Bilgisi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">HESAP DÖNEMİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-teal-lt text-teal" style="width: 32px; height: 32px;">
                            <i class="ti ti-calendar" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Aktif Çalışma Periyodu</span>
                        <span class="badge bg-teal-lt fw-semibold" style="font-size: 10px;">Maaş Ayı</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Döküm Türü -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">DÖKÜM TÜRÜ</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-file-text" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        Ücret Pusulası
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">A4 Formatında Toplu Çıktı</span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;">Resmi Format</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card" style="border-radius: 12px;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                            <i class="ti ti-file-invoice" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Personel Seçim Tablosu</h4>
                                <span class="badge bg-teal-lt"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Yazdırmak istediğiniz personelleri seçip toplu çıktı alabilirsiniz</p>
                        </div>
                    </div>

                    <!-- Fast Instant Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <div class="input-icon" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="bordro-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off" style="height: 32px; font-size: 13px;">
                            <button type="button" id="bordro-search-clear" class="btn btn-sm btn-icon btn-ghost-secondary d-none position-absolute end-0 top-0" style="height: 32px; width: 32px;" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="padding: 4px !important;">
                    <table class="table data-table table-hover text-nowrap w-100" id="bordroSelectionTable" style="width: 100% !important;">
                        <thead>
                            <tr>
                                <th style="width: 40px; min-width: 40px;" class="text-center no-export" data-orderable="false">
                                    <input type="checkbox" class="form-check-input" id="selectAllBordro" style="width: 18px; height: 18px;">
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
                                        <input type="checkbox" class="form-check-input row-check" value="<?= Security::encrypt($p->id) ?>" style="width: 18px; height: 18px;">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-xs rounded-circle bg-teal-lt text-teal fw-bold me-2" style="font-size: 12px;">
                                                <?= htmlspecialchars(mb_substr($p->full_name, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($p->full_name, ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars(Security::safeDecrypt($p->kimlik_no ?? '') ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($p->ekip ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="text-secondary"><?= htmlspecialchars($p->job ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="text-center">
                                        <a href="/hesap-pusulasi?id=<?= Security::encrypt($p->id) ?>&month=<?= Security::encrypt($month) ?>&year=<?= Security::encrypt($year) ?>" target="_blank" class="btn btn-sm btn-icon btn-ghost-primary" style="width: 28px; height: 28px;" title="Pusula Önizle">
                                            <i class="ti ti-eye" style="font-size: 15px;"></i>
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
                    var url = '/bordro-yazdir?ids=' + encodeURIComponent(ids) + '&month=<?= Security::encrypt($month) ?>&year=<?= Security::encrypt($year) ?>';
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
    $kesintiPersons = [];
    foreach ($kesintiData as $k) {
        $total_kesinti_tutari += (float) ($k->tutar ?? 0);
        $kesintiPersons[$k->full_name] = true;
    }
    $total_kesinti_adedi = count($kesintiData);
    $total_kesinti_person_count = count($kesintiPersons);
    ?>

    <!-- Page Header (Standart Başlık Alanı) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-danger-lt text-danger shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-scissors" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Kesinti Detay Raporu
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Personel bazlı avans, icra, nafaka ve özel kesinti hareketlerinin dökümü
                        </div>
                    </div>
                </div>
            </div>
            <!-- Header Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="/raporlar" class="btn btn-sm btn-outline-secondary" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-arrow-left me-1"></i> Raporlara Dön
                    </a>
                    <a href="pages/raporlar/kesinti-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary" data-tooltip="Excel Dosyası İndir" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-file-excel text-success me-1"></i> Excel
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print();" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-printer me-1"></i> Yazdır
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3">
        <!-- Kart 1: Toplam Kesinti Tutarı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM KESİNTİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-cash-off" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_kesinti_tutari) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Netten Kesilen Toplam</span>
                        <span class="badge bg-danger-lt fw-semibold" style="font-size: 10px;">Kesintiler</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Toplam Kesinti Kaydı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">KESİNTİ HAREKETİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-list-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_kesinti_adedi, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">İşlem / Kayıt Sayısı</span>
                        <span class="badge bg-warning-lt fw-semibold" style="font-size: 10px;">Hareketler</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Kesinti Yapılan Kişi Sayısı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">KESİNTİLİ PERSONEL</span>
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_kesinti_person_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Kesinti Uygulanan Kişi</span>
                        <span class="badge bg-primary-lt fw-semibold" style="font-size: 10px;">Personel</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Dönem Bilgisi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">DÖNEM BİLGİSİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-calendar" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Kesinti Takip Dönemi</span>
                        <span class="badge bg-teal-lt fw-semibold" style="font-size: 10px;">Maaş Ayı</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablo Kartı -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card" style="border-radius: 12px;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                            <i class="ti ti-scissors" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Kesinti Hareketleri Tablosu</h4>
                                <span class="badge bg-danger-lt"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Personel bazlı avans, icra, nafaka ve özel kesinti hareketleri</p>
                        </div>
                    </div>

                    <!-- Fast Instant Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <div class="input-icon" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="kesinti-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off" style="height: 32px; font-size: 13px;">
                            <button type="button" id="kesinti-search-clear" class="btn btn-sm btn-icon btn-ghost-secondary d-none position-absolute end-0 top-0" style="height: 32px; width: 32px;" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
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
                                        <span class="avatar avatar-xs rounded-circle bg-danger-lt text-danger fw-bold me-2" style="font-size: 12px;">
                                            <?= htmlspecialchars(mb_substr($k->full_name, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($k->full_name, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td><?= date('d.m.Y', strtotime($k->gun)) ?></td>
                                <td><span class="badge bg-light text-dark fw-semibold"><?= htmlspecialchars($k->kategori_adi ?? 'Diğer', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><span class="text-secondary"><?= htmlspecialchars($k->turu, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><span class="text-muted"><?= htmlspecialchars($k->aciklama ?: '-', ENT_QUOTES, 'UTF-8') ?></span></td>
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
    <?php
    $db = $personObj->connect();
    $activePersonsStmt = $db->prepare("SELECT COUNT(*) FROM persons WHERE firm_id = ? AND deleted_at IS NULL");
    $activePersonsStmt->execute([$firm_id]);
    $activePersonsCount = (int) $activePersonsStmt->fetchColumn();

    $bordroPersons = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);
    $totalBordroCount = count($bordroPersons);
    ?>

    <!-- Page Header (Standart Başlık Alanı) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-chart-dots-3" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Raporlar & Analiz Merkezi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Maaş, bordro, puantaj, banka ödeme ve kesinti analiz raporları
                        </div>
                    </div>
                </div>
            </div>
            <!-- Header Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-blue-lt px-3 py-2 fw-semibold" style="font-size: 12.5px;">
                        <i class="ti ti-calendar me-1"></i> <?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3">
        <!-- Kart 1: Kayıtlı Personel -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">KAYITLI PERSONEL</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($activePersonsCount, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Firma Personel Sayısı</span>
                        <span class="badge bg-secondary-lt fw-semibold" style="font-size: 10px;">Aktif Kadro</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Dönem Bordro Sayısı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">DÖNEM BORDROLARI</span>
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-file-invoice" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($totalBordroCount, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Hesaplanan Bordro</span>
                        <span class="badge bg-primary-lt fw-semibold" style="font-size: 10px;"><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Aktif Modüller -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">HAZIR RAPORLAR</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-chart-bar" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        4 Modül
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Puantaj, Banka, Bordro, Kesinti</span>
                        <span class="badge bg-success-lt fw-semibold" style="font-size: 10px;">Kullanıma Hazır</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Dışa Aktarma -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">DIŞA AKTARMA</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-download" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        Excel & PDF
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">Tek Tıkla İndirme</span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;">Hızlı Çıktı</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modüller Ana Kartı -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card" style="border-radius: 12px;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                            <i class="ti ti-layout-grid" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Rapor Modülleri</h4>
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
                            <?php renderReportCard("Puantaj İcmal Raporu", "Toplam çalışma günleri, saatlik çalışma, fazla mesai, izin ve devamsızlık dökümü.", "ti-clock-check", "bg-purple-lt text-purple", "/raporlar?report=puantaj&year=$year&months=$month", true) ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Banka Ödeme Listesi", "Bankaya gönderilecek personellere ait IBAN ve net maaş hakediş tutarları listesi.", "ti-building-bank", "bg-info-lt text-info", "/raporlar?report=banka&year=$year&months=$month", true) ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Bordro Yazdırma", "Personel bazlı detaylı ücret pusulalarını görüntüleyin ve toplu olarak yazdırın.", "ti-file-invoice", "bg-teal-lt text-teal", "/raporlar?report=bordro&year=$year&months=$month", true) ?>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <?php renderReportCard("Kesinti Raporu", "Personel bazlı avans, icra, nafaka ve diğer kesinti hareketlerinin detaylı dökümü.", "ti-scissors", "bg-danger-lt text-danger", "/raporlar?report=kesinti&year=$year&months=$month", true) ?>
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
                    <div class="card border mt-4" style="border-radius: 10px;">
                        <div class="card-header bg-light py-2 px-3 border-bottom">
                            <h4 class="card-title text-muted mb-0 small fw-bold" style="font-size: 12.5px;">
                                <i class="ti ti-download me-1"></i> Tek Tıkla Hızlı Dışa Aktarma (<?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?>)
                            </h4>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-md-3 col-6">
                                    <a href="pages/raporlar/puantaj-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-outline-purple w-100 text-nowrap fw-semibold" style="height: 36px; font-size: 13px;">
                                        <i class="ti ti-file-spreadsheet me-1"></i> Puantaj İcmal (Excel)
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="pages/raporlar/bank-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-outline-info w-100 text-nowrap fw-semibold" style="height: 36px; font-size: 13px;">
                                        <i class="ti ti-building-bank me-1"></i> Banka Listesi (Excel)
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="pages/raporlar/kesinti-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-outline-danger w-100 text-nowrap fw-semibold" style="height: 36px; font-size: 13px;">
                                        <i class="ti ti-scissors me-1"></i> Kesinti Listesi (Excel)
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="/raporlar?report=bordro&year=<?= $year ?>&months=<?= $month ?>" class="btn btn-outline-dark w-100 text-nowrap fw-semibold" style="height: 36px; font-size: 13px;">
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
    font-weight: 500 !important;
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
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(32, 107, 196, 0.1);
    color: var(--tblr-primary, #206bc4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

/* Rapor Kartları */
.report-card {
    transition: all 0.2s ease-in-out;
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
        var bordroDt = window.createDataTable("#bordroSelectionTable", {
            pageLength: 25,
            order: [[1, "asc"]],
            columnDefs: [
                { targets: [0, 5], orderable: false, searchable: false }
            ]
        });

        $('#bordro-fast-search').on('keyup', function() {
            bordroDt.search(this.value).draw();
        });
        $('#bordro-search-clear').on('click', function() {
            $('#bordro-fast-search').val('');
            bordroDt.search('').draw();
            $(this).addClass('d-none');
        });
        $('#bordro-fast-search').on('input', function() {
            $('#bordro-search-clear').toggleClass('d-none', !this.value);
        });
    }

    // Kesinti Data Table
    if ($("#kesintiDataTable").length > 0 && typeof window.createDataTable === "function") {
        var kesintiDt = window.createDataTable("#kesintiDataTable", {
            pageLength: 25,
            order: [[1, "desc"]],
            columnDefs: [
                { targets: 5, className: "text-end" }
            ]
        });

        $('#kesinti-fast-search').on('keyup', function() {
            kesintiDt.search(this.value).draw();
        });
        $('#kesinti-search-clear').on('click', function() {
            $('#kesinti-fast-search').val('');
            kesintiDt.search('').draw();
            $(this).addClass('d-none');
        });
        $('#kesinti-fast-search').on('input', function() {
            $('#kesinti-search-clear').toggleClass('d-none', !this.value);
        });
    }

    // Puantaj Fast Search Binding
    if ($("#puantajDataTable").length > 0) {
        $('#puantaj-fast-search').on('keyup', function() {
            if ($.fn.dataTable.isDataTable('#puantajDataTable')) {
                $('#puantajDataTable').DataTable().search(this.value).draw();
            }
        });
        $('#puantaj-search-clear').on('click', function() {
            $('#puantaj-fast-search').val('');
            if ($.fn.dataTable.isDataTable('#puantajDataTable')) {
                $('#puantajDataTable').DataTable().search('').draw();
            }
            $(this).addClass('d-none');
        });
        $('#puantaj-fast-search').on('input', function() {
            $('#puantaj-search-clear').toggleClass('d-none', !this.value);
        });
    }

    // Banka Fast Search Binding
    if ($("#bankDataTable").length > 0) {
        $('#bank-fast-search').on('keyup', function() {
            if ($.fn.dataTable.isDataTable('#bankDataTable')) {
                $('#bankDataTable').DataTable().search(this.value).draw();
            }
        });
        $('#bank-search-clear').on('click', function() {
            $('#bank-fast-search').val('');
            if ($.fn.dataTable.isDataTable('#bankDataTable')) {
                $('#bankDataTable').DataTable().search('').draw();
            }
            $(this).addClass('d-none');
        });
        $('#bank-fast-search').on('input', function() {
            $('#bank-search-clear').toggleClass('d-none', !this.value);
        });
    }
});
</script>
