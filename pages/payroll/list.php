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

$year = isset($_POST['year']) ? $_POST['year'] : date('Y');
$month = isset($_POST['months']) ? $_POST['months'] : date('m');
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

// Set the default timezone to your local timezone

// Ayın son gününü bulma (20240930) şeklinde döner
$lastDay = Date::lastDay($month, $year);

$case_id = $Cases->getDefaultCaseIdByFirm();

// Proje haritası (id => project_name) - sütun gösterimi için
$allProjects = $projects->getProjectsByFirm($firm_id);
$projectMap = [];
foreach ($allProjects as $proj) {
    $projectMap[$proj->id] = $proj->project_name;
}

// Kişi → proje ilişkisi haritası
$ppQuery = $personObj->getDb()->prepare(
    "SELECT pp.person_id, pp.project_id FROM project_person pp
     JOIN projects pr ON pr.id = pp.project_id AND pr.firm_id = ?"
);
$ppQuery->execute([$firm_id]);
$personProjectMap = [];
foreach ($ppQuery->fetchAll(PDO::FETCH_OBJ) as $row) {
    $personProjectMap[$row->person_id][] = $row->project_id;
}

$total_gelir = 0;
$total_odeme = 0;
$total_persons = 0;

foreach ($persons as $item) {
    $person = $personObj->find($item->id);
    if ($person->job_end_date != null && $person->job_end_date != '') {
        $job_end_date_ymd = Date::Ymd($person->job_end_date);
        if ($job_end_date_ymd < $firstDay) {
            continue;
        }
    }

    if (isset($_POST["action"]) && ($_POST["action"] == 'payroll_calculate' || $_POST["action"] == 'update_personnel')) {
        if ($firstDay <= Date::Ymd(date('Y-m-d'))) {
            if (Date::isBetween($person->job_start_date, $firstDay, $lastDay) || Date::isBefore($person->job_start_date, $firstDay)) {
                $bordro->connect()->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? AND kategori = 16")->execute([$person->id, $month, $year]);
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
                                }
                                $defined_wage = $wages->getWageByPersonIdAndDate($person->id, $p_record->gun)->amount ?? 0;
                                $eff_hourly = $defined_wage > 0 ? (($defined_wage / 30) / floatval($work_hour)) : $hourly_rate;
                                $tutar = round($pay_saat * $eff_hourly, 2);
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
                            $current_daily_wage = $defined_wage > 0 ? ($defined_wage / floatval($work_hour)) : $ucret;
                            $puantaj_turu = $puantajObj->getPuantajTuruById($p_record->puantaj_id);
                            if ($puantaj_turu->Turu != 'Saatlik') {
                                $saat = $puantajObj->getPuantajSaatiByfirm($p_record->puantaj_id);
                                $tutar = floatval($saat) * $current_daily_wage;
                            } else {
                                $saat = $puantaj_turu->PuantajSaati;
                                $tutar = floatval($saat) * $current_daily_wage;
                            }
                            $puantajObj->saveWithAttr(['id' => $p_record->id, 'tutar' => $tutar, 'saat' => $saat]);
                        }
                    }
                }
            }
        }
    }

    $res = $bordro->getPersonSalaryAndWageCut($person->id, $firstDay, $lastDay);
    $total_gelir += ($res->gelir ?? 0);
    $total_odeme += ($res->odeme ?? 0);
    $total_persons++;
}
$total_kalan = $total_gelir - $total_odeme;
?>

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Bordro</h2>
            </div>
        </div>
    </div>
</div>

<div class="container-xl mt-3">
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
                <label for="months" class="form-label">Ay:</label>
                <?php echo Date::getMonthsSelect('months', $month); ?>
            </div>
            <div class="col-2">
                <label for="year" class="form-label">Yıl:</label>
                <?php echo Date::getYearsSelect('year', $year); ?>
            </div>

            <div class="col-auto ms-auto mt-auto d-flex">
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
                    <a href="#" class="btn btn-icon me-2" id="export_excel" data-tooltip="Excele Aktar">
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

<div class="container-xl mt-3">
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
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">




                    <table class="table card-table table-responsive table-hover text-nowrap datatable" id="bordroTable"
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
                                <th style="width:10%" class="text-center">Ödenen/Kesinti</th>
                                <th style="width:10%" class="text-center">Ödenecek</th>
                                <th style="width:1%" class="text-center no-export">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            $i = 1;
                            foreach ($persons as $item):


                                // Personel id'sine göre personel bilgilerini getirir
                                $person = $personObj->find($item->id);
                                $person_id = Security::encrypt($person->id);
                                $id = Security::encrypt($person->id);

                                //personelin görevden ayrılma tarihi firstday'den küçükse (bu aydan önce ayrıldıysa) personeli getirme
                                if ($person->job_end_date != null && $person->job_end_date != '') {
                                    $job_end_date_ymd = Date::Ymd($person->job_end_date);
                                    if ($job_end_date_ymd < $firstDay) {
                                        continue;
                                    }
                                }

                                // Personel id'sine göre personelin maaş ve kesinti bilgilerini getirir(Örnek: 20240901-20240930 arası)
                                $gelir = $bordro->getPersonSalaryAndWageCut($person->id, $firstDay, $lastDay)->gelir;
                                $odeme = $bordro->getPersonSalaryAndWageCut($person->id, $firstDay, $lastDay)->odeme;
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


                                    <td class="text-end view-payroll-detail" 
                                        data-id="<?php echo $id ?>" 
                                        data-month="<?php echo $month ?>" 
                                        data-year="<?php echo $year ?>"
                                        style="cursor: pointer;"
                                        data-bs-toggle="modal" data-bs-target="#payroll-detail-modal">
                                        <?php echo Helper::formattedMoney($odeme ?? 0); ?>
                                        <i class="ti ti-cash-register icon color-green"></i>

                                    </td>



                                    <!-- Bakiye rengini belirle ve göster -->
                                    <td class="text-end <?php echo Helper::balanceColor($kalan) ?> view-payroll-detail" 
                                        data-id="<?php echo $id ?>" 
                                        data-month="<?php echo $month ?>" 
                                        data-year="<?php echo $year ?>"
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

<?php include_once 'content/wage_cut-modal.php'; ?>
<?php include_once 'content/income-modal.php'; ?>
<?php include_once 'content/payment-modal.php'; ?>
<?php include_once 'content/payment-load-modal.php'; ?>
<?php include_once 'content/payroll-detail-modal.php'; ?>
<?php include_once 'content/bulk-income-modal.php'; ?>
<?php include_once 'content/bulk-wage-cut-modal.php'; ?>
<?php include_once 'content/bulk-wages-modal.php'; ?>