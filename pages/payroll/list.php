<?php
require_once 'App/Helper/helper.php';
require_once 'Model/Persons.php';
require_once 'Model/Bordro.php';
require_once 'Model/Projects.php';
require_once 'App/Helper/date.php';
require_once 'App/Helper/projects.php';
require_once "App/Helper/financial.php";
require_once "App/Helper/security.php";
require_once "Model/Cases.php";
require_once 'Model/Puantaj.php';
require_once 'Model/Wages.php';
require_once 'Model/SettingsModel.php';
require_once 'Model/HolidayWorkService.php';
require_once 'Model/PersonIcra.php';
require_once 'App/Helper/teams.php';

if (!isset($Auths)) {
    require_once ROOT . '/Model/Auths.php';
    $Auths = new Auths();
}
$Auths->checkAuthorize('payroll_page');

use App\Helper\Security;
use App\Helper\Date;
use App\Helper\Helper;

$Cases = new Cases();
$projects = new Projects();
$projectHelper = new ProjectHelper();
$personObj = new Persons();
$bordro = new Bordro();
$FinancialHelper = new Financial();
$puantajObj = new Puantaj();
$wages = new Wages();
$Settings = new SettingsModel();
$HolidayWorkService = new HolidayWorkService();
$personIcra = new PersonIcra();
$Teams = new Teams();

$firm_id = (int) ($_SESSION['firm_id'] ?? 0);
$year = (int) ($_SESSION['period_year'] ?? date('Y'));
$month = (int) ($_SESSION['period_month'] ?? date('m'));
$period_is_visible = $bordro->getPeriodVisibility($firm_id, $year, $month);

// Ayın ilk ve son gününü bulma
$firstDay = Date::firstDay($month, $year);
$last_day = Date::Ymd(Date::lastDay($month, $year));
$lastDay = Date::lastDay($month, $year);

$project_id = isset($_POST['projects']) ? (int)$_POST['projects'] : 0;
$team_id = isset($_POST['team_id']) ? trim((string)$_POST['team_id']) : '';
$action = $_POST['action'] ?? '';

// Personelleri Güncelle işlemi için auto-assignment mantığı
if ($action == 'update_personnel' && $project_id > 0) {
    $p_sql = "SELECT DISTINCT person FROM puantaj WHERE project_id = ? AND gun >= ? AND gun <= ?";
    $p_q = $personObj->getDb()->prepare($p_sql);
    $p_q->execute([$project_id, $firstDay, $last_day]);
    $p_list = $p_q->fetchAll(PDO::FETCH_OBJ);
    foreach ($p_list as $p_item) {
        if ($projects->isExistPersonInProject($project_id, $p_item->person) == 0) {
            $projects->addPersontoProject([
                'project_id' => $project_id,
                'person_id' => $p_item->person,
                'state' => 1,
                'user_id' => $_SESSION['user']->id
            ]);
        }
    }
}

if ($project_id == 0 || $project_id === '') {
    $show_all = ($action == 'update_personnel' || $action == 'payroll_calculate');
    $persons = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDay, $last_day, $show_all, $team_id);
} else {
    $persons = $projects->getPersonIdByFromProjectCurrentMonth($project_id, $firstDay, $last_day, 0, $team_id, true);
}

$personIds = array_map(static function ($item) {
    return (int) $item->id;
}, $persons);
$personDetails = $personObj->getPersonsByIds($personIds);
$personDetailsMap = [];
foreach ($personDetails as $personDetail) {
    $personDetailsMap[(int) $personDetail->id] = $personDetail;
}
$isPayrollCalculation = in_array($action, ['payroll_calculate', 'update_personnel'], true);
$salaryAndWageCutMap = $isPayrollCalculation
    ? []
    : $bordro->getPersonsSalaryAndWageCut($personIds, $firstDay, Date::lastDay($month, $year));
$icraAmountMap = $isPayrollCalculation
    ? []
    : $bordro->getIcraAmounts($personIds, $month, $year);
$personProjectMap = $projects->getProjectNamesByPersonIds($personIds, $firm_id);
$payrollRows = [];

$case_id = $Cases->getDefaultCaseIdByFirm();

$total_gelir = 0;
$total_odeme = 0;
$total_icra = 0;
$total_persons = 0;

foreach ($persons as $item) {
    $person = $personDetailsMap[(int) $item->id] ?? null;
    if (!$person) {
        continue;
    }
    if ($person->job_end_date != null && $person->job_end_date != '') {
        $job_end_date_ymd = Date::Ymd($person->job_end_date);
        if ($job_end_date_ymd < $firstDay) {
            continue;
        }
    }

    if (isset($_POST["action"]) && ($_POST["action"] == 'payroll_calculate' || $_POST["action"] == 'update_personnel')) {
        if ($firstDay <= Date::Ymd(date('Y-m-d'))) {
            if (Date::isBetween($person->job_start_date, $firstDay, $lastDay) || Date::isBefore($person->job_start_date, $firstDay)) {
                $bordro->connect()->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? AND kategori IN (16, 17)")->execute([$person->id, $month, $year]);
                $bordro->connect()->prepare("UPDATE puantaj SET tutar = 0 WHERE person = ? AND REPLACE(gun, '-', '') >= ? AND REPLACE(gun, '-', '') <= ?")->execute([$person->id, $firstDay, $lastDay]);
                $show_white_collar = $Settings->getSettings("show_white_collar_in_puantaj")->set_value ?? 0;
                if ($person->wage_type == 1 && $show_white_collar != 1) {
                    $description = Date::monthName($month) . ' ' . $year . ' Maaş';
                    $job_start = str_replace('.', '-', $person->job_start_date);
                    $job_start_timestamp = strtotime($job_start);
                    $month_start_timestamp = strtotime("$year-$month-01");
                    if ($job_start_timestamp > $month_start_timestamp) {
                        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                        $start_day = (int) date('d', $job_start_timestamp);
                        $worked_days = $days_in_month - $start_day + 1;
                        $daily_rate = $person->daily_wages / 30;
                        $calculated_salary = $daily_rate * $worked_days;
                        $bordro->addPersonMonthlyIncome($person->id, $month, $year, $calculated_salary, $description . " (Kıst Maaş)");
                    } else {
                        $bordro->addPersonMonthlyIncome($person->id, $month, $year, $person->daily_wages, $description);
                    }
                } else {
                    $puantajRecords = $puantajObj->getPuantajByPersonAndDate($person->id, $firstDay, $lastDay);
                    $work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
                    $work_hour = str_replace(',', '.', $work_hour);
                    $overtime_rate = floatval($Settings->getSettings("overtime_rate")->set_value ?? 50);
                    if ($overtime_rate < 50) { $overtime_rate = 50; }
                    $overtime_multiplier = 1 + ($overtime_rate / 100);

                    if ($person->wage_type == 1) {
                        $puantajObj->insertDefaultWeekendRecords($person->id, $firstDay, $lastDay, $project_id, $firm_id);
                        $puantajRecords = $puantajObj->getPuantajByPersonAndDate($person->id, $firstDay, $lastDay);
                        $daily_rate = $person->daily_wages / 30;
                        $job_start = str_replace('.', '-', $person->job_start_date);
                        $job_start_ts = strtotime($job_start);
                        $month_start_ts = strtotime("$year-$month-01");
                        if ($job_start_ts > $month_start_ts) {
                            $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                            $start_day = (int) date('d', $job_start_ts);
                            $base_salary = $daily_rate * ($days_in_month - $start_day + 1);
                            $desc = Date::monthName($month) . ' ' . $year . ' Maaş (Kıst Maaş)';
                        } else {
                            $base_salary = $person->daily_wages;
                            $desc = Date::monthName($month) . ' ' . $year . ' Maaş';
                        }
                        $hourly_rate = $daily_rate / floatval($work_hour);
                        $deduction_days = 0;
                        foreach ($puantajRecords as $p_record) {
                            $puantaj_turu = $puantajObj->getPuantajTuruById($p_record->puantaj_id);
                            $is_deduction = !empty($p_record->is_deductable) || ($puantaj_turu && !empty($puantaj_turu->beyaz_yaka_kesinti));
                            $is_extra_pay = $puantaj_turu && in_array($puantaj_turu->Turu, ['Fazla Çalışma', 'Saatlik']);
                            if ($is_deduction) {
                                $deduction_days++;
                                $tutar = 0;
                                $saat = floatval($work_hour);
                            } elseif ($is_extra_pay) {
                                if ($puantaj_turu->Turu == 'Saatlik') {
                                    $saat = floatval($puantaj_turu->PuantajSaati);
                                    $pay_saat = $saat;
                                    $mult = 1;
                                } else {
                                    $raw_saat = $puantajObj->getPuantajSaatiByfirm($p_record->puantaj_id);
                                    $saat = is_numeric($raw_saat) ? floatval($raw_saat) : 0;
                                    if ($puantaj_turu->operant == '+') {
                                        $pay_saat = floatval($puantaj_turu->EklenecekSaat ?? 0);
                                    } elseif ($puantaj_turu->operant == '*') {
                                        $pay_saat = max(0, (floatval($puantaj_turu->EklenecekSaat ?? 0) - 1) * floatval($work_hour));
                                    } else {
                                        $pay_saat = $saat;
                                    }
                                    $mult = $overtime_multiplier;
                                }
                                $defined_wage = $wages->getWageByPersonIdAndDate($person->id, $p_record->gun)->amount ?? 0;
                                $eff_hourly = $defined_wage > 0 ? (($defined_wage / 30) / floatval($work_hour)) : $hourly_rate;
                                $tutar = round($pay_saat * $eff_hourly * $mult, 2);
                            } else {
                                if ($puantaj_turu && $puantaj_turu->Turu != 'Saatlik') {
                                    $raw_saat = $puantajObj->getPuantajSaatiByfirm($p_record->puantaj_id);
                                    $saat = is_numeric($raw_saat) ? floatval($raw_saat) : 0;
                                } else {
                                    $saat = $puantaj_turu ? floatval($puantaj_turu->PuantajSaati) : 0;
                                }
                                $tutar = 0;
                            }
                            $puantajObj->saveWithAttr(['id' => $p_record->id, 'tutar' => $tutar, 'saat' => $saat]);
                        }
                        $net = max(0, round($base_salary - ($deduction_days * $daily_rate), 2));
                        $gun = sprintf('%d%02d01', $year, $month);
                        $bordro->connect()->prepare("INSERT INTO maas_gelir_kesinti SET person_id=?, gun=?, ay=?, yil=?, tutar=?, kategori=16, turu=?, aciklama=?")
                            ->execute([$person->id, $gun, $month, $year, $net, $desc, $desc]);
                    } else {
                        $ucret = $person->daily_wages / floatval($work_hour);
                        foreach ($puantajRecords as $p_record) {
                            $defined_wage = $wages->getWageByPersonIdAndDate($person->id, $p_record->gun)->amount ?? 0;
                            $current_hourly_wage = $defined_wage > 0 ? ($defined_wage / floatval($work_hour)) : $ucret;
                            $puantaj_turu = $puantajObj->getPuantajTuruById($p_record->puantaj_id);
                            $is_overtime = $puantaj_turu && $puantaj_turu->Turu == 'Fazla Çalışma';
                            if ($puantaj_turu->Turu != 'Saatlik') {
                                $saat = $puantajObj->getPuantajSaatiByfirm($p_record->puantaj_id);
                                if ($is_overtime) {
                                    if (!empty($puantaj_turu->EklenecekSaat)) {
                                        if (($puantaj_turu->operant ?? '+') == '+') {
                                            $extra_hours = floatval($puantaj_turu->EklenecekSaat);
                                        } elseif (($puantaj_turu->operant ?? '+') == '*') {
                                            $extra_hours = max(0, (floatval($puantaj_turu->EklenecekSaat) - 1) * floatval($work_hour));
                                        } else {
                                            $extra_hours = floatval($puantaj_turu->EklenecekSaat);
                                        }
                                    } else {
                                        $extra_hours = max(0, floatval($saat) - floatval($work_hour));
                                    }
                                    $normal_pay = floatval($work_hour) * $current_hourly_wage;
                                    $overtime_pay = $extra_hours * $current_hourly_wage * $overtime_multiplier;
                                    $tutar = round($normal_pay + $overtime_pay, 2);
                                } else {
                                    $tutar = round(floatval($saat) * $current_hourly_wage, 2);
                                }
                            } else {
                                $saat = $puantaj_turu->PuantajSaati;
                                $tutar = round(floatval($saat) * $current_hourly_wage, 2);
                            }
                            $puantajObj->saveWithAttr(['id' => $p_record->id, 'tutar' => $tutar, 'saat' => $saat]);
                        }
                    }
                }

                // Resmi tatilde çalışılan günler için ilave gelir hesaplama
                $holidayWorkHour = (float) str_replace(',', '.', $Settings->getSettings("work_hour")->set_value ?? 8);
                $holidayAttendanceRecords = $puantajObj->getPuantajByPersonAndDate($person->id, $firstDay, $lastDay);
                foreach ($holidayAttendanceRecords as $holidayAttendance) {
                    $definedWage = $wages->getWageByPersonIdAndDate($person->id, $holidayAttendance->gun)->amount ?? 0;
                    $baseWage = $definedWage > 0 ? (float) $definedWage : (float) ($person->daily_wages ?? 0);
                    $holidayDailyRate = ($person->wage_type == 1) ? ($baseWage / 30) : $baseWage;
                    $holidayWork = $HolidayWorkService->calculate(
                        $firm_id,
                        $holidayAttendance,
                        $holidayDailyRate,
                        $holidayWorkHour
                    );

                    if (!$holidayWork || $holidayWork->amount <= 0) {
                        continue;
                    }

                    $typeLabel = [
                        'national' => 'Resmî / Millî',
                        'religious' => 'Dini',
                        'other' => 'Diğer',
                    ][$holidayWork->holiday_type] ?? 'Diğer';
                    $basisLabel = $holidayWork->calculation_basis === 'full_day' ? 'tam gün' : 'saatle orantılı';
                    $description = sprintf(
                        '%s | %s | +%s gün | %s | %.2f gün',
                        $holidayWork->holiday_name,
                        $typeLabel,
                        rtrim(rtrim(number_format($holidayWork->additional_day_rate, 2, '.', ''), '0'), '.'),
                        $basisLabel,
                        $holidayWork->worked_day_fraction
                    );
                    $holidayDate = str_replace('-', '', $holidayWork->date);
                    $bordro->connect()->prepare(
                        "INSERT INTO maas_gelir_kesinti
                            (person_id, project_id, gun, ay, yil, tutar, kategori, turu, aciklama)
                         VALUES (?, ?, ?, ?, ?, ?, 17, ?, ?)"
                    )->execute([
                        $person->id,
                        (int) ($holidayAttendance->project_id ?? 0),
                        $holidayDate,
                        $month,
                        $year,
                        $holidayWork->amount,
                        'Resmi Tatil Çalışması - ' . $holidayWork->holiday_name,
                        $description,
                    ]);
                }
            }
        }
    }

    if ($isPayrollCalculation) {
        if (!empty($person->icra_kesintisi_aktif)) {
            $stmt_calc_inc = $bordro->connect()->prepare("SELECT SUM(tutar) FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? AND kategori IN (1, 16, 17)");
            $stmt_calc_inc->execute([$person->id, $month, $year]);
            $earned_inc = (float)($stmt_calc_inc->fetchColumn() ?? 0);
            if ($earned_inc > 0) {
                $personIcra->calculateAndApplyIcraDeduction($person->id, $month, $year, $earned_inc);
            }
        } else {
            $personIcra->calculateAndApplyIcraDeduction($person->id, $month, $year, 0);
        }

        $res = $bordro->getPersonSalaryAndWageCut($person->id, $firstDay, $lastDay);
        if (!empty($person->icra_kesintisi_aktif)) {
            $stmt_icra_calc = $bordro->connect()->prepare("SELECT tutar FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? AND kategori = 15 AND (aciklama LIKE '%İcra%' OR aciklama LIKE '%icra%' OR turu = 'İcra Kesintisi')");
            $stmt_icra_calc->execute([$person->id, $month, $year]);
            $p_icra = (float)($stmt_icra_calc->fetchColumn() ?? 0);
        } else {
            $p_icra = 0;
        }
    } else {
        $res = $salaryAndWageCutMap[(int) $person->id] ?? (object) ['gelir' => null, 'odeme' => 0];
        $p_icra = !empty($person->icra_kesintisi_aktif) ? ($icraAmountMap[(int) $person->id] ?? 0) : 0;
    }

    $payrollRows[(int) $person->id] = [
        'person' => $person,
        'gelir' => $res->gelir ?? 0,
        'odeme' => $res->odeme ?? 0,
        'icra' => $p_icra,
    ];

    $total_gelir += ($res->gelir ?? 0);
    $total_odeme += (($res->odeme ?? 0) - $p_icra);
    $total_icra += $p_icra;
    $total_persons++;
}
$total_kalan = $total_gelir - ($total_odeme + $total_icra);
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'payroll-summary-collapsed',
            localStorage.getItem('payroll_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>
<style>
html.payroll-summary-collapsed #payrollSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="payrollPage">

    <!-- Form Container for Filters & Actions -->
    <form action="" method="post" id="bordroInfoForm" class="m-0">
        <input type="hidden" name="months" id="months" value="<?= sprintf('%02d', $month) ?>">
        <input type="hidden" name="year" id="year" value="<?= $year ?>">

        <!-- Page Header (Hero Banner) -->
        <div class="page-header d-print-none mb-3">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                            <i class="ti ti-calculator" style="font-size: 24px;"></i>
                        </div>
                        <div>
                            <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                                Bordro Yönetimi
                            </h2>
                            <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                                <?= Date::monthName($month) . ' ' . $year ?> dönemi maaş, hakediş ve kesinti hesaplamaları
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Primary Actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <?php if ($Auths->hasPermission('toggle_payroll_period_status')): ?>
                        <div class="d-flex align-items-center me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Dönem Durumu: <?= Date::monthName($month) . ' ' . $year ?> dönemi <?= $period_is_visible == 1 ? 'KAPALI (PWA Personellere Açık, Puantaj Kilitli)' : 'AÇIK (PWA Personellere Kapalı, Puantaj Düzenlenebilir)' ?>">
                            <div class="form-check form-switch mb-0 p-0 d-flex align-items-center cursor-pointer">
                                <input class="form-check-input cursor-pointer m-0 me-1" type="checkbox" id="pwa-visibility-toggle" data-year="<?= $year ?>" data-month="<?= $month ?>" <?= $period_is_visible == 1 ? 'checked' : '' ?>>
                                <span id="pwa-visibility-status" class="badge <?= $period_is_visible == 1 ? 'bg-danger-lt text-danger' : 'bg-success-lt text-success' ?> cursor-pointer py-1 px-2">
                                    <i class="ti <?= $period_is_visible == 1 ? 'ti-lock' : 'ti-lock-open' ?> icon me-1" id="pwa-visibility-icon"></i><span id="pwa-visibility-text"><?= $period_is_visible == 1 ? 'Dönem Kapalı' : 'Dönem Açık' ?></span>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon payroll-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle" id="colvisDropdownBtn">
                                <i class="ti ti-columns"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2" id="bordroColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                                <!-- Checkboxes will be rendered dynamically by JS -->
                            </div>
                        </div>

                        <a href="#" class="btn btn-sm btn-dark shadow-sm payroll-header-action" id="payroll_calculate" style="background-color: #1e293b; border-color: #1e293b;">
                            <i class="ti ti-calculator me-1"></i> Hesapla
                        </a>

                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle payroll-header-action" data-bs-toggle="dropdown">
                                <i class="ti ti-settings me-1"></i> İşlemler
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <?php if ($Auths->hasPermission('payroll_export_excel')): ?>
                                    <a href="pages/payroll/xls/payroll-list.php?month=<?= urlencode((string) $month) ?>&year=<?= urlencode((string) $year) ?>&project_id=<?= urlencode((string) $project_id) ?>&team_id=<?= urlencode((string) $team_id) ?>" class="dropdown-item">
                                        <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                                    </a>
                                    <a class="dropdown-item" href="pages/payroll/xls/bank-list-for-payments.php">
                                        <i class="ti ti-checklist icon me-2 text-info"></i> Banka Listesi İndir
                                    </a>
                                <?php endif; ?>
                                <?php if ($Auths->hasPermission('make_staff_payment')): ?>
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#pay_to_persons-modal">
                                        <i class="ti ti-users icon me-2 text-primary"></i> Toplu Personel Ödemesi
                                    </a>
                                <?php endif; ?>
                                <?php if ($Auths->hasPermission('upload_payment_permission')): ?>
                                    <a class="dropdown-item" href="#" data-bs-target="#load-payment-modal" data-bs-toggle="modal">
                                        <i class="ti ti-table-import icon me-2 text-primary"></i> Ödeme Yükle
                                    </a>
                                <?php endif; ?>
                                <?php if ($Auths->hasPermission('update_fees_permission')): ?>
                                    <a class="dropdown-item" href="#" data-bs-target="#bulk-wages-modal" data-bs-toggle="modal">
                                        <i class="ti ti-user-dollar icon me-2 text-warning"></i> Ücretleri Güncelle
                                    </a>
                                <?php endif; ?>
                                <?php if ($Auths->hasPermission('income_expense_add_update')): ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#" data-bs-target="#bulk-income-modal" data-bs-toggle="modal">
                                        <i class="ti ti-circle-plus icon me-2 text-success"></i> Toplu Gelir Ekle
                                    </a>
                                    <a class="dropdown-item" href="#" data-bs-target="#bulk-wage-cut-modal" data-bs-toggle="modal">
                                        <i class="ti ti-circle-minus icon me-2 text-danger"></i> Toplu Kesinti Ekle
                                    </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" id="update_personnel">
                                    <i class="ti ti-users-plus icon me-2 text-secondary"></i> Personelleri Güncelle
                                </a>
                                <a class="dropdown-item" href="#" id="btnPrintBulkPayrolls">
                                    <i class="ti ti-printer icon me-2 text-dark"></i> Seçilenlerin Bordrolarını Yazdır
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI / İstatistik Özet Kartları -->
        <div class="row row-cards g-3 mb-3" id="payrollSummaryCards">
            <!-- Kart 1: Toplam Brüt -->
            <div class="col-sm-6 col-xl-3">
                <div class="card card-sm border payroll-summary-card" style="border-color: #e2e8f0 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM BRÜT</span>
                            <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                                <i class="ti ti-download" style="font-size: 18px;"></i>
                            </div>
                        </div>
                        <div class="h1 mb-2 fw-bold text-dark" id="payroll-total-income" data-amount="<?= htmlspecialchars((string) $total_gelir, ENT_QUOTES, 'UTF-8') ?>" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                            <?= Helper::formattedMoney($total_gelir) ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                            <span class="text-muted" style="font-size: 11.5px;">
                                Hakediş + İlave Gelirler
                            </span>
                            <span class="badge bg-secondary-lt text-secondary fw-semibold" style="font-size: 10px; padding: 3px 8px;">Brüt Tutar</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kart 2: Toplam Ödenen / Kesinti -->
            <div class="col-sm-6 col-xl-3">
                <div class="card card-sm border payroll-summary-card" style="border-color: #e2e8f0 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ÖDENEN / KESİNTİ</span>
                            <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                                <i class="ti ti-cash-register" style="font-size: 18px;"></i>
                            </div>
                        </div>
                        <div class="h1 mb-2 fw-bold text-dark" id="payroll-total-expense" data-amount="<?= htmlspecialchars((string) $total_odeme, ENT_QUOTES, 'UTF-8') ?>" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                            <?= Helper::formattedMoney($total_odeme) ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                            <span class="text-muted" style="font-size: 11.5px;">
                                İcra: <strong class="text-purple"><?= Helper::formattedMoney($total_icra) ?></strong>
                            </span>
                            <span class="badge bg-warning-lt text-warning fw-semibold" style="font-size: 10px; padding: 3px 8px;">Toplam Çıkış</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kart 3: Kalan Ödenecek -->
            <div class="col-sm-6 col-xl-3">
                <div class="card card-sm border payroll-summary-card" style="border-color: #e2e8f0 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">KALAN ÖDENECEK</span>
                            <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                                <i class="ti ti-credit-card-pay" style="font-size: 18px;"></i>
                            </div>
                        </div>
                        <div class="h1 mb-2 fw-bold text-dark" id="payroll-total-net" data-amount="<?= htmlspecialchars((string) $total_kalan, ENT_QUOTES, 'UTF-8') ?>" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                            <?= Helper::formattedMoney($total_kalan) ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                            <span class="text-muted" style="font-size: 11.5px;">
                                Net Bakiye
                            </span>
                            <span class="badge <?= $total_kalan > 0 ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' ?> fw-semibold" style="font-size: 10px; padding: 3px 8px;">
                                <?= $total_kalan > 0 ? 'Ödeme Bekleyen' : 'Tamamlandı' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kart 4: Personel Sayısı -->
            <div class="col-sm-6 col-xl-3">
                <div class="card card-sm border payroll-summary-card" style="border-color: #e2e8f0 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BORDROLU PERSONEL</span>
                            <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                                <i class="ti ti-users" style="font-size: 18px;"></i>
                            </div>
                        </div>
                        <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                            <?= number_format($total_persons, 0, ',', '.') ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                            <span class="text-muted" style="font-size: 11.5px;">
                                Dönem: <strong><?= Date::monthName($month) . ' ' . $year ?></strong>
                            </span>
                            <span class="badge bg-info-lt text-info fw-semibold" style="font-size: 10px; padding: 3px 8px;">Bu Dönem</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="row row-cards">
            <div class="col-12">
                <div class="card payroll-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                                <i class="ti ti-calculator text-secondary" style="font-size: 18px;"></i>
                            </div>
                            <div>
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Bordro Listesi</h4>
                                <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Anlık filtreleme, puantaj ve ödeme takibi</p>
                            </div>
                        </div>

                        <!-- Filtre Seçimleri -->
                        <div class="d-flex align-items-center flex-wrap gap-2 my-1 my-md-0">
                            <div style="min-width: 170px;">
                                <?= $projectHelper->getProjectSelect('projects', $project_id, 'Tüm Projeler') ?>
                            </div>
                            <div style="min-width: 150px;">
                                <?= $Teams->teamsSelect('team_id', $team_id, 'Tüm Ekipler') ?>
                            </div>
                        </div>

                        <!-- Actions & Search -->
                        <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                            <!-- Fast Instant Search -->
                            <div class="input-icon payroll-search-wrap" style="min-width: 170px;">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search text-muted"></i>
                                </span>
                                <input type="text" id="payroll-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                                <button type="button" id="payroll-search-clear" class="payroll-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                            <button type="button" id="togglePayrollSummary" class="btn btn-sm btn-outline-secondary btn-icon payroll-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                                <i class="ti ti-chevron-up"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Table Responsive Container (Seamless inside card) -->
                    <div class="table-responsive payroll-table-area" style="overflow-x: auto !important;">
                        <table class="table data-table table-hover text-nowrap w-100 mb-0" id="bordroTable" style="margin: 0 !important;">
                            <thead>
                                <tr>
                                    <th style="width: 40px; min-width: 40px;" class="text-center no-export" data-orderable="false">
                                        <input type="checkbox" class="form-check-input select-all-payrolls" title="Tümünü Seç">
                                    </th>
                                    <th style="width: 50px; min-width: 50px;" class="text-center">Sıra</th>
                                    <th>Personel Adı</th>
                                    <th>Ücret Türü</th>
                                    <th>Görevi</th>
                                    <th>Ekip</th>
                                    <th style="max-width: 135px; width: 125px;">Proje</th>
                                    <th>IBAN</th>
                                    <th>İşe Başlama Tarihi</th>
                                    <th style="width:10%" class="text-end">Brüt Ücret</th>
                                    <th style="width:10%" class="text-end">İcra Kesintisi</th>
                                    <th style="width:10%" class="text-end">Ödenen/Kesinti</th>
                                    <th style="width:10%" class="text-end">Ödenecek</th>
                                    <th style="width: 95px; min-width: 95px;" class="text-end no-export" data-orderable="false">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($persons as $item):
                                    $payrollRow = $payrollRows[(int) $item->id] ?? null;
                                    if (!$payrollRow) {
                                        continue;
                                    }
                                    $person = $payrollRow['person'];
                                    $person_id = Security::encrypt($person->id);
                                    $id = Security::encrypt($person->id);

                                    if ($person->job_end_date != null && $person->job_end_date != '') {
                                        $job_end_date_ymd = Date::Ymd($person->job_end_date);
                                        if ($job_end_date_ymd < $firstDay) {
                                            continue;
                                        }
                                    }

                                    $gelir = $payrollRow['gelir'];
                                    $odeme = $payrollRow['odeme'];
                                    $kalan = $gelir - $odeme;
                                    $icra_month_amount = (float) $payrollRow['icra'];
                                    $odeme_haric_icra = max(0, $odeme - $icra_month_amount);

                                    $wage_type_text = $person->wage_type == 1 ? 'Aylık' : 'Günlük';
                                    if ($person->wage_type == 1) {
                                        $monthly_wage = floatval($person->daily_wages ?? 0);
                                        $daily_wage = $monthly_wage / 30;
                                        $monthly_wage_text = Helper::formattedMoney($monthly_wage);
                                        $daily_wage_text = Helper::formattedMoney($daily_wage);
                                    } else {
                                        $daily_wage = floatval($person->daily_wages ?? 0);
                                        $monthly_wage_text = '-';
                                        $daily_wage_text = Helper::formattedMoney($daily_wage);
                                    }

                                    $popover_content = "
                                    <div class='p-1'>
                                      <div class='mb-2 pb-1 border-bottom d-flex justify-content-between align-items-center gap-4'>
                                        <span class='text-secondary small font-weight-medium'>Ücret Türü</span>
                                        <span class='badge bg-blue-lt text-blue'>" . htmlspecialchars($wage_type_text, ENT_QUOTES, 'UTF-8') . "</span>
                                      </div>
                                      <div class='d-flex justify-content-between py-1 gap-4'>
                                        <span class='text-secondary'>Aylık Ücret:</span>
                                        <span class='font-weight-bold text-dark'>" . htmlspecialchars($monthly_wage_text, ENT_QUOTES, 'UTF-8') . "</span>
                                      </div>
                                      <div class='d-flex justify-content-between py-1 gap-4'>
                                        <span class='text-secondary'>Günlük Ücret:</span>
                                        <span class='font-weight-bold text-dark'>" . htmlspecialchars($daily_wage_text, ENT_QUOTES, 'UTF-8') . "</span>
                                      </div>
                                    </div>";
                                ?>
                                <tr data-id="<?= $id ?>"
                                    data-raw-id="<?= (int) $person->id ?>"
                                    data-person-name="<?= htmlspecialchars($person->full_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-iban="<?= htmlspecialchars(Security::safeDecrypt($person->iban_number ?? '') ?: '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-balance="<?= htmlspecialchars(Helper::formattedMoney($kalan ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                                    data-balance-raw="<?= (float)($kalan ?? 0) ?>"
                                    data-month="<?= $month ?>"
                                    data-year="<?= $year ?>"
                                    data-project-id="<?= $project_id ?>"
                                    data-has-icra="<?= $icra_month_amount > 0 ? '1' : '0' ?>"
                                    data-icra-amount="<?= htmlspecialchars(Helper::formattedMoney($icra_month_amount), ENT_QUOTES, 'UTF-8') ?>"
                                    data-slip-url="index.php?p=payroll/pay-slip&id=<?= $link ?>"
                                    data-can-pay="<?= $Auths->hasPermission('make_staff_payment') ? '1' : '0' ?>"
                                    data-can-income="<?= $Auths->hasPermission('income_expense_add_update') ? '1' : '0' ?>">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input payroll-row-check" value="<?= (int) $person->id ?>" data-enc-id="<?= $id ?>">
                                    </td>
                                    <td class="text-center text-muted small"><?= $i ?></td>
                                    <td>
                                        <a href="#" data-tooltip="Personel Detayı" data-page="persons/manage&id=<?= $id ?>" class="nav-item route-link fw-semibold text-dark">
                                            <?= htmlspecialchars($person->full_name ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge <?= $person->wage_type == 1 ? 'bg-blue-lt text-blue' : 'bg-secondary-lt text-secondary' ?>">
                                            <?= $person->wage_type == 1 ? 'Beyaz Yaka' : 'Mavi Yaka' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($person->job ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($person->ekip ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-truncate" style="max-width: 135px;" title="<?php $pNames = $personProjectMap[(int) $person->id] ?? []; echo htmlspecialchars(!empty($pNames) ? implode(', ', $pNames) : '-', ENT_QUOTES, 'UTF-8'); ?>"><?php
                                        $pNames = $personProjectMap[(int) $person->id] ?? [];
                                        echo htmlspecialchars(!empty($pNames) ? implode(', ', $pNames) : '-', ENT_QUOTES, 'UTF-8');
                                    ?></td>
                                    <td><code><?= htmlspecialchars(Security::safeDecrypt($person->iban_number ?? '') ?: '-', ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td><?= htmlspecialchars($person->job_start_date ?? '-', ENT_QUOTES, 'UTF-8') ?></td>

                                    <!-- Brüt Gelir -->
                                    <td class="text-end gross-salary-popover" 
                                        data-bs-toggle="popover" 
                                        data-bs-trigger="hover" 
                                        data-bs-html="true" 
                                        data-bs-placement="top"
                                        title="Ücret Bilgileri"
                                        data-bs-content="<?= htmlspecialchars($popover_content, ENT_QUOTES, 'UTF-8') ?>"
                                        style="cursor: pointer;">
                                        <span class="fw-semibold text-dark"><?= Helper::formattedMoney(($gelir) ?? 0) ?></span>
                                        <i class="ti ti-download icon text-success ms-1"></i>
                                    </td>

                                    <!-- İcra Kesintisi -->
                                    <td class="text-end text-purple fw-semibold btn-view-icra-deductions"
                                        data-person-id="<?= $id ?>"
                                        role="button" tabindex="0" title="İcra kesintisi detayını görüntüle"
                                        style="cursor: pointer;">
                                        <?= $icra_month_amount > 0 ? Helper::formattedMoney($icra_month_amount) : '<span class="text-muted">0,00 ₺</span>' ?>
                                    </td>

                                    <!-- Ödenen / Kesinti (İcra Hariç) -->
                                    <td class="text-end view-payroll-detail"
                                        data-id="<?= $id ?>"
                                        data-month="<?= $month ?>"
                                        data-year="<?= $year ?>"
                                        role="button" tabindex="0" title="Bordro detayını görüntüle"
                                        style="cursor: pointer;"
                                        data-bs-toggle="modal" data-bs-target="#payroll-detail-modal">
                                        <span class="fw-semibold"><?= Helper::formattedMoney($odeme_haric_icra ?? 0) ?></span>
                                        <i class="ti ti-cash-register icon text-warning ms-1"></i>
                                    </td>

                                    <!-- Ödenecek / Kalan Bakiye -->
                                    <td class="text-end payroll-balance <?= Helper::balanceColor($kalan) ?> view-payroll-detail fw-bold"
                                        data-id="<?= $id ?>"
                                        data-month="<?= $month ?>"
                                        data-year="<?= $year ?>"
                                        role="button" tabindex="0" title="Bordro detayını görüntüle"
                                        style="cursor: pointer;"
                                        data-bs-toggle="modal" data-bs-target="#payroll-detail-modal">
                                        <?= Helper::formattedMoney($kalan ?? 0) ?>
                                        <i class="ti ti-credit-card-pay icon ms-1"></i>
                                    </td>

                                    <!-- İşlem Sütunu -->
                                    <td class="text-end actions-column">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" style="height: 28px; padding: 2px 8px; font-size: 12px;" data-bs-toggle="dropdown">
                                                İşlem
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <?php if ($Auths->hasPermission('make_staff_payment')): ?>
                                                    <a class="dropdown-item add-payment" data-id="<?= $id ?>"
                                                        data-name="<?= htmlspecialchars($person->full_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-balance="<?= htmlspecialchars(Helper::formattedMoney($kalan ?? 0), ENT_QUOTES, 'UTF-8') ?>" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#payment-modal">
                                                        <i class="ti ti-cash-register icon me-2 text-success"></i> Ödeme Yap
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($Auths->hasPermission("income_expense_add_update")): ?>
                                                    <a class="dropdown-item add-wage-cut" data-id="<?= $id ?>"
                                                        data-name="<?= htmlspecialchars($person->full_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-balance="<?= htmlspecialchars(Helper::formattedMoney($kalan ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-tooltip="Avans, Ceza veya BES gibi" data-tooltip-location="left"
                                                        href="#" data-bs-toggle="modal" data-bs-target="#wage_cut_modal">
                                                        <i class="ti ti-cut icon me-2 text-danger"></i> Kesinti Ekle
                                                    </a>

                                                    <a class="dropdown-item add-income" data-id="<?= $id ?>"
                                                        data-name="<?= htmlspecialchars($person->full_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-balance="<?= htmlspecialchars(Helper::formattedMoney($kalan ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-tooltip="Prim, İkramiye veya Ödül gibi" data-tooltip-location="left"
                                                        href="#" data-bs-toggle="modal" data-bs-target="#income_modal">
                                                        <i class="ti ti-download icon me-2 text-primary"></i> Gelir Ekle
                                                    </a>
                                                <?php endif; ?>

                                                <?php
                                                $link = $id . "&month=" . Security::encrypt($month) . "&year=" . Security::encrypt($year);
                                                ?>
                                                <a class="dropdown-item" target="_blank" href="index.php?p=payroll/pay-slip&id=<?= $link ?>">
                                                    <i class="ti ti-file-dollar icon me-2 text-info"></i> Bordro Pusulası
                                                </a>

                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item delete-monthly-payroll text-danger" 
                                                   data-id="<?= $id ?>" 
                                                   data-month="<?= $month ?>" 
                                                   data-year="<?= $year ?>" 
                                                   data-project-id="<?= $project_id ?>"
                                                   href="#">
                                                    <i class="ti ti-trash icon me-2"></i> Bordrodan Çıkar
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                $i++;
                                endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<style>
.payroll-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.payroll-header-icon-action,
.payroll-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.payroll-header-icon-action i,
.payroll-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#payrollPage .payroll-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#payrollSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

#payrollPage .payroll-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
}

.payroll-table-card > .payroll-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.payroll-table-card > .card-header {
    border-bottom: 0 !important;
}

.payroll-search-wrap { position: relative; }
#payroll-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.payroll-search-wrap,
.payroll-search-wrap.input-icon {
    height: 32px !important;
}
.payroll-search-clear {
    position: absolute;
    top: 50%;
    right: 6px;
    z-index: 3;
    display: inline-flex;
    width: 22px;
    height: 22px;
    padding: 0;
    align-items: center;
    justify-content: center;
    transform: translateY(-50%);
    border: 0;
    border-radius: 50%;
    color: #64748b;
    background: #f1f5f9;
    cursor: pointer;
}
.payroll-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#bordroTable_wrapper,
div.dt-container,
div.dt-container .dt-layout-row.dt-layout-table,
div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell {
    height: auto !important;
    min-height: 0 !important;
    min-height: unset !important;
    max-height: none !important;
    flex-grow: 0 !important;
    border: none !important;
    box-shadow: none !important;
}

div.dt-container .dt-layout-row.dt-layout-table {
    padding: 0 !important;
    margin: 0 !important;
}

div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell {
    padding: 0 !important;
    margin: 0 !important;
}

/* Tek Çerçeve (Kart ile Bütünleşik Tablo) */
table#bordroTable.data-table:not(.dtcr-cloned),
table#bordroTable.dataTable:not(.dtcr-cloned) {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#bordroTable.data-table tbody,
table#bordroTable.dataTable tbody,
table#bordroTable.data-table tbody tr:last-child,
table#bordroTable.dataTable tbody tr:last-child,
#bordroTable_wrapper .dt-layout-table,
#bordroTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Eski Arama Satırını Gizle */
#bordroTable .search-input-row,
table.dataTable thead tr.search-input-row {
    display: none !important;
}

/* Tablo Başlık Hücreleri */
table#bordroTable.data-table thead th,
table#bordroTable.dataTable thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 11.5px !important;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 9px 12px !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    vertical-align: middle !important;
}
table#bordroTable.data-table thead th:last-child,
table#bordroTable.dataTable thead th:last-child {
    border-right: none !important;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#bordroTable.data-table tbody td,
table#bordroTable.dataTable tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#bordroTable.data-table .form-check-input.select-all-payrolls,
table#bordroTable.data-table .form-check-input.payroll-row-check {
    width: 18px !important;
    min-width: 18px !important;
    height: 18px !important;
    min-height: 18px !important;
    margin: 0 !important;
    padding: 0 !important;
    vertical-align: middle !important;
    border-radius: 5px !important;
}
table#bordroTable.data-table td.actions-column {
    width: 95px !important;
    min-width: 95px !important;
    text-align: right !important;
    white-space: nowrap;
    padding-right: 12px !important;
}
table#bordroTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#bordroTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#bordroTable.dataTable > tbody > tr:last-child > *,
table#bordroTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#bordroTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Context Menu — Aktif Satır Vurgusu */
#bordroTable tbody tr.context-menu-active > td {
    background-color: #eff6ff !important;
}
[data-bs-theme="dark"] #bordroTable tbody tr.context-menu-active > td {
    background-color: #1e3a5f !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#bordroTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
div#bordroTable_wrapper .dt-layout-row:has(.dt-paging),
div#bordroTable_wrapper .dt-layout-row:has(.dt-info) {
    margin: 0 !important;
    margin-top: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
    position: static !important;
    flex-shrink: 0 !important;
}
</style>

<?php include_once 'content/wage_cut-modal.php'; ?>
<?php include_once 'content/income-modal.php'; ?>
<?php include_once 'content/payment-modal.php'; ?>
<?php include_once 'content/payment-load-modal.php'; ?>
<?php include_once 'content/payroll-detail-modal.php'; ?>
<?php include_once 'content/bulk-income-modal.php'; ?>
<?php include_once 'content/bulk-wage-cut-modal.php'; ?>
<?php include_once 'content/bulk-wages-modal.php'; ?>
<?php include_once 'content/icra-deductions-modal.php'; ?>
<?php include_once 'content/pay_to_persons-modal.php'; ?>
