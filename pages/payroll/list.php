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

$year = isset($_POST['year']) ? $_POST['year'] : date('Y');
$month = isset($_POST['months']) ? $_POST['months'] : date('m');
$period_is_visible = $bordro->getPeriodVisibility($firm_id, $year, $month);
// Ayın ilk gününü bulma (20240901) şeklinde döner
$firstDay = Date::firstDay($month, $year);
$last_day = Date::Ymd(Date::lastDay($month, $year));
$project_id = isset($_POST['projects']) ? $_POST['projects'] : 0;
$team_id = isset($_POST['team_id']) ? $_POST['team_id'] : '';
$action = $_POST['action'] ?? '';
$Teams = new Teams();

// Personelleri Güncelle işlemi için auto-assignment mantığı
if ($action == 'update_personnel' && $project_id > 0) {
    // Bu dönemde bu projede puantajı olan ama projeye atanmamış personelleri bul ve ata
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

if ($project_id == 0 || $project_id == '') {
    // Proje id boş ise Firma id'sine göre personelleri getirir
    // Personelleri Güncelle veya Hesapla butonu tıklandıysa tüm personelleri getirir (yeni eklenenleri yakalamak veya hesaplamak için)
    $show_all = ($action == 'update_personnel' || $action == 'payroll_calculate');
    $persons = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDay, $last_day, $show_all, $team_id);
} else {
    // Proje id dolu ise projeye ait personelleri getirir
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

// Set the default timezone to your local timezone

// Ayın son gününü bulma (20240930) şeklinde döner
$lastDay = Date::lastDay($month, $year);

$case_id = $Cases->getDefaultCaseIdByFirm();

$total_gelir = 0;
$total_odeme = 0;
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

                // Resmi tatilde çalışılan günler için firma politikasına göre ayrı ilave gelir oluştur.
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

    if (!empty($person->icra_kesintisi_aktif)) {
        $stmt_check_icra = $bordro->connect()->prepare("SELECT COUNT(id) FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? AND kategori = 15 AND (aciklama LIKE '%İcra%' OR aciklama LIKE '%icra%' OR turu = 'İcra Kesintisi')");
        $stmt_check_icra->execute([$person->id, $month, $year]);
        $icra_count = (int)$stmt_check_icra->fetchColumn();
        if (isset($_POST["action"]) || $icra_count === 0) {
            $stmt_calc_inc = $bordro->connect()->prepare("SELECT SUM(tutar) FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? AND kategori IN (1, 16, 17)");
            $stmt_calc_inc->execute([$person->id, $month, $year]);
            $earned_inc = (float)($stmt_calc_inc->fetchColumn() ?? 0);
            if ($earned_inc > 0) {
                $personIcra->calculateAndApplyIcraDeduction($person->id, $month, $year, $earned_inc);
            }
        }
    }

    if ($isPayrollCalculation || !empty($person->icra_kesintisi_aktif)) {
        $res = $bordro->getPersonSalaryAndWageCut($person->id, $firstDay, $lastDay);
        $stmt_icra_calc = $bordro->connect()->prepare("SELECT tutar FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? AND kategori = 15 AND (aciklama LIKE '%İcra%' OR aciklama LIKE '%icra%' OR turu = 'İcra Kesintisi')");
        $stmt_icra_calc->execute([$person->id, $month, $year]);
        $p_icra = (float)($stmt_icra_calc->fetchColumn() ?? 0);
    } else {
        $res = $salaryAndWageCutMap[(int) $person->id] ?? (object) ['gelir' => null, 'odeme' => 0];
        $p_icra = $icraAmountMap[(int) $person->id] ?? 0;
    }

    $total_gelir += ($res->gelir ?? 0);
    $total_odeme += (($res->odeme ?? 0) - $p_icra);
    $total_icra = ($total_icra ?? 0) + $p_icra;
    $total_persons++;
}
$total_kalan = $total_gelir - ($total_odeme + $total_icra);
?>

<div class="container-xl mt-2 mb-2">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h2 class="page-title m-0">Bordro</h2>
    </div>
    <form action="" method="post" id="bordroInfoForm">
        <div class="row">
            <div class="col-3">
                <label for="projects" class="form-label">Proje:</label>
                <?php echo $projectHelper->getProjectSelect('projects', $project_id, 'Tüm Projeler'); ?>
            </div>
            <div class="col-2">
                <label for="team_id" class="form-label">Ekip:</label>
                <?php echo $Teams->teamsSelect('team_id', $team_id, 'Tüm Ekipler'); ?>
            </div>
            <div class="col-2">
                <label for="period_picker" class="form-label">Dönem:</label>
                <div class="input-group input-group-flat border rounded shadow-none bg-white">
                    <button type="button" class="btn btn-ghost-secondary btn-icon border-0" id="prevPeriodBtn" title="Önceki Ay">
                        <i class="ti ti-chevron-left icon m-0"></i>
                    </button>
                    <input type="text" class="form-control text-center fw-bold bg-transparent border-0 px-0 cursor-pointer" id="period_picker" style="cursor: pointer; font-size: 0.88rem;" readonly placeholder="Dönem">
                    <button type="button" class="btn btn-ghost-secondary btn-icon border-0" id="nextPeriodBtn" title="Sonraki Ay">
                        <i class="ti ti-chevron-right icon m-0"></i>
                    </button>
                </div>
                <input type="hidden" name="months" id="months" value="<?php echo sprintf('%02d', $month); ?>">
                <input type="hidden" name="year" id="year" value="<?php echo $year; ?>">
            </div>

            <div class="col-auto ms-auto mt-auto d-flex align-items-center">
                <?php if ($Auths->hasPermission('toggle_payroll_period_status')): ?>
                <div class="d-flex align-items-center me-3" data-bs-toggle="tooltip" data-bs-placement="top" title="Dönem Durumu: <?php echo Date::monthName($month) . ' ' . $year; ?> dönemi <?php echo $period_is_visible == 1 ? 'KAPALI (PWA Personellere Açık, Puantaj Kilitli)' : 'AÇIK (PWA Personellere Kapalı, Puantaj Düzenlenebilir)'; ?>">
                    <div class="form-check form-switch mb-0 p-0 d-flex align-items-center cursor-pointer">
                        <input class="form-check-input cursor-pointer m-0 me-1" type="checkbox" id="pwa-visibility-toggle" data-year="<?php echo $year; ?>" data-month="<?php echo $month; ?>" <?php echo $period_is_visible == 1 ? 'checked' : ''; ?>>
                        <span id="pwa-visibility-status" class="badge <?php echo $period_is_visible == 1 ? 'bg-danger-lt text-danger' : 'bg-success-lt text-success'; ?> cursor-pointer">
                            <i class="ti <?php echo $period_is_visible == 1 ? 'ti-lock' : 'ti-lock-open'; ?> icon me-1" id="pwa-visibility-icon"></i><span id="pwa-visibility-text"><?php echo $period_is_visible == 1 ? 'Dönem Kapalı' : 'Dönem Açık'; ?></span>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="dropdown me-2">
                    <button type="button" class="btn btn-icon" data-bs-toggle="dropdown" title="Sütunları Seç" id="colvisDropdownBtn">
                        <i class="ti ti-columns icon"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-2" id="bordroColvisMenu"
                        style="min-width: 200px; max-height: 300px; overflow-y: auto;">
                    </div>
                </div>
                <?php
                if ($Auths->hasPermission('payroll_export_excel')) { ?>
                    <label for=""></label>
                    <a href="pages/payroll/xls/payroll-list.php?month=<?php echo urlencode((string) $month); ?>&year=<?php echo urlencode((string) $year); ?>&project_id=<?php echo urlencode((string) $project_id); ?>&team_id=<?php echo urlencode((string) $team_id); ?>"
                        class="btn btn-icon me-2" data-tooltip="Excele Aktar">
                        <i class="ti ti-file-excel icon"></i>
                    </a>
                <?php } ?>



                <label for="" class="form-label"></label>

                <div class="dropdown">
                    <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                        <i class="ti ti-list-details icon me-2"></i>
                        İşlemler</button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <?php if ($Auths->hasPermission('upload_payment_permission')) { ?>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#load-payment-modal"
                                data-tooltip="Personellere yapılan ödemeleri excelden yükleyin" data-tooltip-location="left">
                                <i class="ti ti-table-import icon me-3 text-info"></i> Ödeme Yükle
                            </a>
                        <?php } ?>
                        <?php if ($Auths->hasPermission('update_fees_permission')) { ?>
                            <a class="dropdown-item" data-tooltip="Günlük Ücretleri güncelleyin"
                                data-tooltip-location="left" href="#" data-bs-toggle="modal" data-bs-target="#bulk-wages-modal">
                                <i class="ti ti-user-dollar icon me-3"></i> Ücretleri Güncelle
                            </a>
                        <?php } ?>

                        <?php if ($Auths->hasPermission('payroll_export_excel')) { ?>
                            <a class="dropdown-item add-income"
                                data-tooltip="Personellere yapılacak ödeme listesini indirin" data-tooltip-location="left"
                                href="pages/payroll/xls/bank-list-for-payments.php">
                                <i class="ti ti-checklist icon me-3"></i> Banka Listesi İndir
                            </a>
                        <?php } ?>
                        <?php if ($Auths->hasPermission('income_expense_add_update')) { ?>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulk-income-modal">
                                <i class="ti ti-circle-plus icon me-3 text-success"></i> Toplu Gelir Ekle
                            </a>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulk-wage-cut-modal">
                                <i class="ti ti-circle-minus icon me-3 text-danger"></i> Toplu Kesinti Ekle
                            </a>
                        <?php } ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" id="update_personnel">
                            <i class="ti ti-users-plus icon me-3"></i> Personelleri Güncelle
                        </a>
                    </div>
                </div>
                <a class="btn btn-primary ms-2" href="#" id="payroll_calculate">
                    <i class="ti ti-calculator icon me-2"></i> Hesapla
                </a>


            </div>
        </div>
    </form>
</div>

<div class="container-xl mt-2">
    <div class="row row-cards">
        <div class="col-md-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <i class="ti ti-download icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                <?php echo Helper::formattedMoney($total_gelir); ?>
                            </div>
                            <div class="text-secondary">
                                Toplam Brüt
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-orange text-white avatar">
                                <i class="ti ti-cash-register icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                <?php echo Helper::formattedMoney($total_odeme); ?>
                            </div>
                            <div class="text-secondary">
                                Toplam Ödenen/Kesinti
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-green text-white avatar">
                                <i class="ti ti-credit-card-pay icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                <?php echo Helper::formattedMoney($total_kalan); ?>
                            </div>
                            <div class="text-secondary">
                                Toplam Ödenecek
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-azure text-white avatar">
                                <i class="ti ti-users icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                <?php echo $total_persons; ?>
                            </div>
                            <div class="text-secondary">
                                Personel Sayısı
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    .dropdown-menu {
  position: absolute;
  z-index: 9999;
}

</style>
<style>
#bordroTable th:last-child,
#bordroTable td:last-child {
    width: 110px !important;
    min-width: 110px !important;
    white-space: nowrap;
}

#bordroTable td:last-child .dropdown,
#bordroTable td:last-child .dropdown-toggle {
    width: 100%;
    min-width: 88px;
}
</style>
<div class="container-xl mt-2">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">




                    <table class="table card-table table-responsive table-hover text-nowrap" id="bordroTable"
                    >
                        <thead>
                            <tr>
                                <th style="width:1%">Sıra</th>
                                <th>Personel Adı</th>
                                <th>Ücret Türü</th>
                                <th>Görevi</th>
                                <th>Ekip</th>
                                <th>Proje</th>
                                <th>IBAN</th>
                                <th>İşe Başlama Tarihi</th>
                                <th style="width:10%" class="text-center">Brüt Ücret</th>
                                <th style="width:10%" class="text-center">İcra Kesintisi</th>
                                <th style="width:10%" class="text-center">Ödenen/Kesinti</th>
                                <th style="width:10%" class="text-center">Ödenecek</th>
                                <th style="width:1%" class="text-center no-export">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            $i = 1;
                            foreach ([] as $item):


                                $payrollRow = $payrollRows[(int) $item->id] ?? null;
                                if (!$payrollRow) {
                                    continue;
                                }
                                $person = $payrollRow['person'];
                                $person_id = Security::encrypt($person->id);
                                $id = Security::encrypt($person->id);

                                //personelin görevden ayrılma tarihi firstday'den küçükse (bu aydan önce ayrıldıysa) personeli getirme
                                if ($person->job_end_date != null && $person->job_end_date != '') {
                                    $job_end_date_ymd = Date::Ymd($person->job_end_date);
                                    if ($job_end_date_ymd < $firstDay) {
                                        continue;
                                    }
                                }

                                $gelir = $payrollRow['gelir'];
                                $odeme = $payrollRow['odeme'];
                                $kalan = $gelir - $odeme;

                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td> <a href="#" data-tooltip="Detay/Güncelle"
                                            data-page="persons/manage&id=<?php echo $id ?>"
                                            class="nav-item route-link"><?php echo $person->full_name; ?></a></td>
                                    <td><?php echo $person->wage_type == 1 ? 'Beyaz Yaka' : 'Mavi Yaka'; ?></td>
                                    <td><?php echo $person->job; ?></td>
                                    <td><?php echo $person->ekip ?: '-'; ?></td>
                                    <td><?php
                                        $pIds = $personProjectMap[$person->id] ?? [];
                                        $pNames = array_filter(array_map(fn($pid) => $projectMap[$pid] ?? '', $pIds));
                                        echo $pNames ? implode(', ', $pNames) : '-';
                                    ?></td>
                                    <td><?php echo Security::safeDecrypt($person->iban_number ?? '') ?: '-'; ?></td>
                                    <td><?php echo $person->job_start_date; ?></td>

                                    <!-- Gelir -->
                                    <?php
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
                                        <span class='badge bg-blue-lite text-blue'>" . htmlspecialchars($wage_type_text, ENT_QUOTES, 'UTF-8') . "</span>
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
                                    <td class="text-end gross-salary-popover" 
                                        data-bs-toggle="popover" 
                                        data-bs-trigger="hover" 
                                        data-bs-html="true" 
                                        data-bs-placement="top"
                                        title="Ücret Bilgileri"
                                        data-bs-content="<?php echo htmlspecialchars($popover_content, ENT_QUOTES, 'UTF-8'); ?>"
                                        style="cursor: pointer;">
                                        <?php echo Helper::formattedMoney(($gelir) ?? 0) ?>
                                        <i class="ti ti-download icon text-green"></i>
                                    </td>

                                    <!-- İcra Kesintisi -->
                                    <?php
                                    $icra_month_amount = (float) $payrollRow['icra'];
                                    $odeme_haric_icra = max(0, $odeme - $icra_month_amount);
                                    ?>
                                    <td class="text-end text-purple fw-semibold">
                                        <?php echo $icra_month_amount > 0 ? Helper::formattedMoney($icra_month_amount) : '0,00 ₺'; ?>
                                    </td>

                                    <!-- Ödenen / Kesinti (İcra Hariç) -->
                                    <td class="text-end view-payroll-detail"
                                        data-id="<?php echo $id ?>"
                                        data-month="<?php echo $month ?>"
                                        data-year="<?php echo $year ?>"
                                        role="button" tabindex="0" title="Bordro detayını görüntüle"
                                        style="cursor: pointer;"
                                        data-bs-toggle="modal" data-bs-target="#payroll-detail-modal">
                                        <?php echo Helper::formattedMoney($odeme_haric_icra ?? 0); ?>
                                        <i class="ti ti-cash-register icon color-green"></i>
                                    </td>



                                    <!-- Bakiye rengini belirle ve göster -->
                                    <td class="text-end <?php echo Helper::balanceColor($kalan) ?> view-payroll-detail"
                                        data-id="<?php echo $id ?>"
                                        data-month="<?php echo $month ?>"
                                        data-year="<?php echo $year ?>"
                                        role="button" tabindex="0" title="Bordro detayını görüntüle"
                                        style="cursor: pointer;"
                                        data-bs-toggle="modal" data-bs-target="#payroll-detail-modal">
                                        <!-- //Bakiyesini yazdır -->
                                        <?php echo Helper::formattedMoney($kalan ?? 0); ?>
                                        <i class="ti ti-credit-card-pay icon"></i>
                                    </td>


                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <?php if ($Auths->hasPermission('make_staff_payment')) { ?>
                                                    <a class="dropdown-item add-payment" data-id="<?php echo $id ?>" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#payment-modal">
                                                        <i class="ti ti-cash-register icon me-3"></i> Ödeme Yap
                                                    </a>
                                                <?php } ?>

                                                <?php if ($Auths->hasPermission("income_expense_add_update")) {
                                                    ; ?>
                                                    <a class="dropdown-item add-wage-cut" data-id="<?php echo $id ?>"
                                                        data-tooltip="Avans,Ceza veya Bes gibi" data-tooltip-location="left"
                                                        href="#">
                                                        <i class="ti ti-cut icon me-3"></i> Kesinti Ekle
                                                    </a>

                                                    <a class="dropdown-item add-income" data-id="<?php echo $id ?>"
                                                        data-tooltip="Prim,İkramiye veya Ödül gibi" data-tooltip-location="left"
                                                        href="#" data-bs-toggle="modal" data-bs-target="#income_modal">
                                                        <i class="ti ti-download icon me-3"></i> Gelir Ekle
                                                    </a>
                                                <?php } ?>

                                                <?php
                                                $link =  $id . "&month=" . Security::encrypt($month) . "&year=" . Security::encrypt($year);
                                                ?>

                                                <a class="dropdown-item" target="_blank"
                                                    href="index.php?p=payroll/pay-slip&id=<?php echo $link ?>">
                                                    <i class="ti ti-file-dollar icon me-3"></i> Bordro Göster
                                                </a>

                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item delete-monthly-payroll text-danger" 
                                                   data-id="<?php echo $id ?>" 
                                                   data-month="<?php echo $month ?>" 
                                                   data-year="<?php echo $year ?>" 
                                                   data-project-id="<?php echo $project_id ?>"
                                                   href="#">
                                                    <i class="ti ti-trash icon me-3"></i> Sil
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

<script>
window.bordroServerSideOptions = {
    processing: true,
    serverSide: true,
    autoWidth: false,
    ordering: true,
    searchDelay: 400,
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    order: [[1, 'asc']],
    ajax: {
        url: 'api/bordro/list.php',
        type: 'POST',
        data: function(data) {
            data.month = <?php echo json_encode((int) $month); ?>;
            data.year = <?php echo json_encode((int) $year); ?>;
            data.project_id = <?php echo json_encode((int) $project_id); ?>;
            data.team_id = <?php echo json_encode((string) $team_id, JSON_UNESCAPED_UNICODE); ?>;
        }
    },
    columnDefs: [
        { targets: 0, className: 'text-center' },
        { targets: [8, 9, 10, 11, 12], className: 'text-end' },
        { targets: 12, width: '110px', orderable: false, searchable: false, className: 'text-end no-export actions-column' }
    ],
    language: {
        url: 'src/tr.json',
        processing: '<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...'
    }
};
</script>

<?php include_once 'content/wage_cut-modal.php'; ?>
<?php include_once 'content/income-modal.php'; ?>
<?php include_once 'content/payment-modal.php'; ?>
<?php include_once 'content/payment-load-modal.php'; ?>
<?php include_once 'content/payroll-detail-modal.php'; ?>
<?php include_once 'content/bulk-income-modal.php'; ?>
<?php include_once 'content/bulk-wage-cut-modal.php'; ?>
<?php include_once 'content/bulk-wages-modal.php'; ?>


<script>
$(document).ready(function() {
    $(document).off('change.pwaVis').on('change.pwaVis', '#pwa-visibility-toggle', function() {
        var isChecked = $(this).is(':checked') ? 1 : 0;
        var year = $(this).data('year');
        var month = $(this).data('month');
        var $statusBadge = $('#pwa-visibility-status');
        var $icon = $('#pwa-visibility-icon');
        
        $.ajax({
            url: 'api/bordro/toggle_visibility.php',
            type: 'POST',
            data: { year: year, month: month, is_closed: isChecked },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    if (isChecked === 1) {
                        $statusBadge.removeClass('bg-success-lt text-success').addClass('bg-danger-lt text-danger');
                        $icon.removeClass('ti-lock-open').addClass('ti-lock');
                        $('#pwa-visibility-text').text('Dönem Kapalı');
                    } else {
                        $statusBadge.removeClass('bg-danger-lt text-danger').addClass('bg-success-lt text-success');
                        $icon.removeClass('ti-lock').addClass('ti-lock-open');
                        $('#pwa-visibility-text').text('Dönem Açık');
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: res.message,
                            showConfirmButton: false,
                            timer: 2500
                        });
                    }
                } else {
                    $('#pwa-visibility-toggle').prop('checked', !isChecked);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Yetkisiz Erişim', res.message || 'Bir hata oluştu.', 'error');
                    }
                }
            },
            error: function() {
                $('#pwa-visibility-toggle').prop('checked', !isChecked);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Hata', 'Sunucuya ulaşılamadı.', 'error');
                }
            }
        });
    });
});
</script>

<script>
$(document).ready(function() {
    var currentMonth = parseInt($('#months').val()) || (new Date().getMonth() + 1);
    var currentYear = parseInt($('#year').val()) || new Date().getFullYear();

    var fp = flatpickr('#period_picker', {
        locale: typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.tr ? flatpickr.l10ns.tr : 'tr',
        defaultDate: new Date(currentYear, currentMonth - 1, 1),
        dateFormat: "F Y",
        plugins: [
            typeof monthSelectPlugin === 'function' ? monthSelectPlugin({
                shorthand: false,
                dateFormat: "F Y",
                altFormat: "F Y"
            }) : null
        ].filter(Boolean),
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                var date = selectedDates[0];
                var m = (date.getMonth() + 1).toString().padStart(2, '0');
                var y = date.getFullYear();
                $('#months').val(m);
                $('#year').val(y);
                if (typeof Route === 'function') {
                    Route();
                } else {
                    $('#bordroInfoForm').submit();
                }
            }
        }
    });

    $(document).off('click.prevP').on('click.prevP', '#prevPeriodBtn', function(e) {
        e.preventDefault();
        var m = parseInt($('#months').val());
        var y = parseInt($('#year').val());
        if (m === 1) {
            m = 12;
            y = y - 1;
        } else {
            m = m - 1;
        }
        $('#months').val(m.toString().padStart(2, '0'));
        $('#year').val(y);
        if (typeof Route === 'function') {
            Route();
        } else {
            $('#bordroInfoForm').submit();
        }
    });

    $(document).off('click.nextP').on('click.nextP', '#nextPeriodBtn', function(e) {
        e.preventDefault();
        var m = parseInt($('#months').val());
        var y = parseInt($('#year').val());
        if (m === 12) {
            m = 1;
            y = y + 1;
        } else {
            m = m + 1;
        }
        $('#months').val(m.toString().padStart(2, '0'));
        $('#year').val(y);
        if (typeof Route === 'function') {
            Route();
        } else {
            $('#bordroInfoForm').submit();
        }
    });
});
</script>
