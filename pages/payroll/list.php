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
$action = $_POST['action'] ?? '';

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
    $persons = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDay, $last_day, $show_all);
} else {
    // Proje id dolu ise projeye ait personelleri getirir
    $persons = $projects->getPersonIdByFromProjectCurrentMonth($project_id, $firstDay, $last_day, 0, 0, true);
}

// Set the default timezone to your local timezone

// Ayın son gününü bulma (20240930) şeklinde döner
$lastDay = Date::lastDay($month, $year);

$case_id = $Cases->getDefaultCaseIdByFirm();

?>
<div class="container-xl mt-3">
    <form action="" method="post" id="bordroInfoForm">
        <div class="row">
            <div class="col-3">
                <label for="projects" class="form-label">Proje:</label>
                <?php echo $projectHelper->getProjectSelect('projects', $project_id); ?>
            </div>
            <div class="col-3">
                <label for="months" class="form-label">Ay:</label>
                <?php echo Date::getMonthsSelect('months', $month); ?>
            </div>
            <div class="col-3">
                <label for="year" class="form-label">Yıl:</label>
                <?php echo Date::getYearsSelect('year', $year); ?>
            </div>

            <div class="col-auto ms-auto mt-auto d-flex">
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
                            <a class="dropdown-item add-income route-link" href="#"
                                data-tooltip="Personellere yapılan ödemeleri excelden yükleyin" data-tooltip-location="left"
                                data-page="payroll/xls/payment-load-from-xls">
                                <i class="ti ti-table-import icon me-3"></i> Ödeme Yükle
                            </a>
                        <?php } ?>
                        <?php if ($Auths->hasPermission('update_fees_permission')) { ?>
                            <a class="dropdown-item add-income" data-tooltip="Günlük Ücretleri güncelleyin"
                                data-tooltip-location="left" href="#" data-bs-toggle="modal" data-bs-target="#income_modal">
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
                <div class="card-header">
                    <h3 class="card-title">Bordro</h3>
                    <div class="col-auto ms-auto">
                        <!-- <a href="#" class="btn btn-primary route-link" data-page="defines/service-head/manage">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </a> -->
                    </div>
                </div>



                    <table class="table card-table table-responsive table-hover text-nowrap datatable" id="bordroTable"
                    >
                        <thead>
                            <tr>
                                <th style="width:1%">Sıra</th>
                                <th>Personel Adı</th>
                                <th>Ücret Türü</th>
                                <th>Görevi</th>
                                <th>İşe Başlama Tarihi</th>
                                <th style="width:10%" class="text-center">Brüt Ücret</th>
                                <th style="width:10%" class="text-center">Ödenen/Kesinti</th>
                                <!-- <th style="width:10%" class="text-center">Devreden</th> -->
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

                                // Hesaplama işlemi tetiklendiyse
                                if (isset($_POST["action"]) && ($_POST["action"] == 'payroll_calculate' || $_POST["action"] == 'update_personnel')) {
                                    // Eğer ayın ilk günü bugünden küçükse veya eşitse (geçmiş veya mevcut ay)
                                    if ($firstDay <= Date::Ymd(date('Y-m-d'))) {
                                        // Personel o tarihte çalışıyorsa
                                        if (Date::isBetween($person->job_start_date, $firstDay, $lastDay) || Date::isBefore($person->job_start_date, $firstDay)) {
                                            
                                            // TEMİZLİK: Personelin o aya ait mevcut maaş (Kat 16) ve puantaj tutarlarını temizleyelim
                                            // Tip değişikliği durumunda (Beyaz -> Mavi veya tersi) eski hesaplamaların kalmaması için gereklidir.
                                            $bordro->connect()->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? AND kategori = 16")->execute([$person->id, $month, $year]);
                                            $bordro->connect()->prepare("UPDATE puantaj SET tutar = 0 WHERE person = ? AND gun >= ? AND gun <= ?")->execute([$person->id, $firstDay, $lastDay]);

                                            if ($person->wage_type == 1) {
                                                // BEYAZ YAKA HESAPLAMA
                                                $description = Date::monthName($month) . ' ' . $year . ' Maaş';
                                                
                                                $job_start = str_replace('.', '-', $person->job_start_date);
                                                $job_start_timestamp = strtotime($job_start);
                                                $month_start_timestamp = strtotime("$year-$month-01");

                                                if ($job_start_timestamp > $month_start_timestamp) {
                                                    // Ay içinde işe başlamışsa Kıst Maaş hesabı yap
                                                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                                                    $start_day = (int) date('d', $job_start_timestamp);
                                                    $worked_days = $days_in_month - $start_day + 1;
                                                    $daily_rate = $person->daily_wages / 30;
                                                    $calculated_salary = $daily_rate * $worked_days;
                                                    
                                                    $bordro->addPersonMonthlyIncome($person->id, $month, $year, $calculated_salary, $description . " (Kıst Maaş)");
                                                } else {
                                                    // Tam maaş
                                                    $bordro->addPersonMonthlyIncome($person->id, $month, $year, $person->daily_wages, $description);
                                                }
                                            } else {
                                                // MAVİ YAKA HESAPLAMA
                                                $puantajRecords = $puantajObj->getPuantajByPersonAndDate($person->id, $firstDay, $lastDay);
                                                $work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
                                                $work_hour = str_replace(',', '.', $work_hour);
                                                $ucret = $person->daily_wages / $work_hour;

                                                foreach ($puantajRecords as $p_record) {
                                                    $defined_wage = $wages->getWageByPersonIdAndDate($person->id, $p_record->gun)->amount ?? 0;
                                                    $current_daily_wage = (($defined_wage > 0) ? ($defined_wage / $work_hour) : $ucret);

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
                                    <td><?php echo $person->job_start_date; ?></td>

                                    <!-- Gelir -->
                                    <td class="text-end ">
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