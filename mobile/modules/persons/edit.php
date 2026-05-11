<?php
ob_start();
?>
<style>
/* Floating Select2 Styling */
.form-floating-select2 {
    position: relative;
    height: 58px;
}
.form-floating-select2 .select2-container--default .select2-selection--single {
    height: 58px !important;
    padding-top: 1.25rem !important;
    border-radius: 12px !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
    background-color: #fff !important;
}
body[data-bs-theme="dark"] .form-floating-select2 .select2-container--default .select2-selection--single {
    background-color: #1e293b !important;
    border-color: rgba(255,255,255,0.1) !important;
}
.form-floating-select2 .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.5 !important;
    padding-left: 12px !important;
    padding-top: 8px !important;
    font-size: 0.95rem !important;
    font-weight: 500 !important;
}
.form-floating-select2 .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 58px !important;
}
.form-floating-select2 label {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 5;
    height: 100%;
    padding: 1rem 0.75rem;
    pointer-events: none;
    transform-origin: 0 0;
    transition: opacity .1s ease-in-out, transform .1s ease-in-out;
    color: rgba(var(--tblr-body-color-rgb), .65);
    font-size: 0.9rem;
    opacity: 1;
}
.form-floating-select2.has-value label,
.form-floating-select2.is-focused label {
    transform: scale(.85) translateY(-.6rem) translateX(.15rem);
    opacity: .75;
}
/* Hide FAB dropdown caret */
.mobile-fab.dropdown-toggle::after {
    display: none !important;
}
</style>
<?php
// Puantor Mobil - Personel Düzenleme
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/Model/Puantaj.php";
require_once ROOT . "/Model/CaseTransactions.php";
require_once ROOT . "/Model/Bordro.php";
require_once ROOT . "/Model/Cases.php";
require_once ROOT . "/App/Helper/financial.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;

require_once ROOT . "/App/Helper/jobs.php";
require_once ROOT . "/App/Helper/teams.php";

$jobGroupsHelper = new Jobs();
$teamsHelper = new Teams();

$personsModel = new Persons();
$projectsModel = new Projects();
$puantajModel = new Puantaj();
$ctModel = new CaseTransactions();

$firm_id = $_SESSION['firm_id'] ?? 0;

$id_encrypted = $_GET['id'] ?? '';
$id = Security::decrypt($id_encrypted);

if (!$id) {
    header("Location: persons");
    exit();
}

$person = $personsModel->find($id);

if (!$person || $person->firm_id != $firm_id) {
    header("Location: persons");
    exit();
}

$personProjects = $projectsModel->getProjectsByPerson($id);
$personProjectsIds = array_map(function ($project) {
    return $project->project_id;
}, $personProjects);

$projects = $projectsModel->getProjectsByFirm($firm_id);

// Ay ve Yıl Navigasyonu
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
if (strlen($month) == 1) $month = '0' . $month;

$firstDayOfMonth = Date::firstDay($month, $year);
$lastDayOfMonth = Date::lastDay($month, $year);

$personPuantaj = $puantajModel->getPuantajByPersonAndDate($id, $firstDayOfMonth, $lastDayOfMonth);
$puantajMap = [];
foreach ($personPuantaj as $p) {
    $puantajMap[$p->gun] = $p;
}

// Finans verileri (Sadece bu personelin işlemleri)
$allTransactions = $ctModel->allTransactionByFirm($firm_id);
$personTransactions = array_filter($allTransactions, function($t) use ($id) {
    return $t->person_id == $id;
});

$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_person'])) {
    $tc_no = trim($_POST['tc_no'] ?? '');
    if (strlen($tc_no) > 11) {
        $tc_no = substr($tc_no, 0, 11);
    }
    
    $selectedProjects = $_POST['person_project'] ?? [];
    $primary_project_id = !empty($selectedProjects) ? $selectedProjects[0] : 0;

    $job_group = $_POST['job_group'] ?? ($person->job_group ?? '');
    // Eğer iş grubu sayısal değilse (yeni bir tag girilmişse) yeni grup oluştur
    if (!empty($job_group) && !is_numeric($job_group)) {
        $db = $personsModel->getDb();
        $stmt = $db->prepare("INSERT INTO job_groups (firm_id, group_name) VALUES (?, ?)");
        $stmt->execute([$firm_id, $job_group]);
        $job_group = $db->lastInsertId();
    }

    $team_val = !empty($_POST['team_id']) ? $_POST['team_id'] : ($person->ekip ?? ($person->team_id ?? null));

    $data = [
        'id' => $id,
        'firm_id' => $firm_id,
        'full_name' => $_POST['full_name'] ?? '',
        'kimlik_no' => Security::encrypt($tc_no),
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'daily_wages' => $_POST['daily_wage'] ?? 0.00,
        'wage_type' => $_POST['wage_type'] ?? ($person->wage_type ?? 2),
        'job_start_date' => !empty($_POST['job_start_date']) ? $_POST['job_start_date'] : null,
        'job_end_date' => !empty($_POST['job_end_date']) ? $_POST['job_end_date'] : null,
        'job' => $_POST['job'] ?? '',
        'job_group' => $job_group,
        'team_id' => $team_val,
        'ekip' => $team_val,
        'iban_number' => Security::encrypt($_POST['iban_number'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'project_id' => $primary_project_id,
        'address' => $_POST['address'] ?? ''
    ];

    // Şifre değişikliği varsa ekle
    if (!empty($_POST['password'])) {
        $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
    
    try {
        $personsModel->saveWithAttr($data);
        
        // Çoklu Proje Kaydetme
        if (isset($_POST['person_project'])) {
            $projectsModel->savePersonProjects($id, $_POST['person_project']);
        } else {
            $projectsModel->savePersonProjects($id, []);
        }

        $message = "Personel başarıyla güncellendi.";
        $status = "success";
        // Güncel veriyi tekrar çek
        $person = $personsModel->find($id);
        
        // Değişkenleri güncelle
        $personProjects = $projectsModel->getProjectsByPerson($id);
        $personProjectsIds = array_map(function ($project) {
            return $project->project_id;
        }, $personProjects);

    } catch (Exception $e) {
        $message = "Hata: " . $e->getMessage();
        $status = "danger";
    }
}

// Personel Silme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_person'])) {
    try {
        $personsModel->softDelete($id_encrypted); // softDelete genellikle şifreli ID bekliyor projede
        header("Location: persons");
        exit();
    } catch (Exception $e) {
        $message = "Silme hatası: " . $e->getMessage();
        $status = "danger";
    }
}
?>

<div class="container px-0 pb-5">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="persons" class="btn btn-icon btn-sm btn-outline-secondary border-0 bg-secondary-lt rounded-circle">
        <i class="ti ti-chevron-left" style="font-size: 1.2rem;"></i>
      </a>
      <div>
        <h2 class="mb-0 text-semibold" id="page-title" style="letter-spacing: -0.5px; line-height: 1.1;">Personel Düzenle</h2>
        <span class="text-muted text-xs font-weight-bold text-uppercase" style="letter-spacing: 0.5px; opacity: 0.8;"><?php echo htmlspecialchars($person->full_name); ?></span>
      </div>
    </div>

    <!-- Header Actions moved to FAB at bottom right -->
  </div>

  <style>
    /* Premium Select2 Multi-Select Styling */
    .select2-container--default .select2-selection--multiple {
      border: 1px solid var(--tblr-border-color, #e6e7e9) !important;
      border-radius: 12px !important;
      padding: 10px 14px !important;
      min-height: 56px !important;
      background-color: var(--tblr-bg-surface, #ffffff) !important;
      display: flex !important;
      align-items: center !important;
      flex-wrap: wrap !important;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
      border-color: #206bc4 !important;
      box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, .25) !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      background-color: rgba(32, 107, 196, 0.08) !important;
      border: 1px solid rgba(32, 107, 196, 0.18) !important;
      border-radius: 8px !important;
      color: #206bc4 !important;
      font-size: 0.85rem !important;
      font-weight: 600 !important;
      margin: 3px !important;
      padding: 4px 10px !important;
      display: inline-flex !important;
      align-items: center !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
      color: #206bc4 !important;
      border-right: none !important;
      margin-right: 6px !important;
      font-weight: bold !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
      background-color: transparent !important;
      color: #d63939 !important;
    }
    .select2-container--default .select2-search--inline .select2-search__field {
      margin-top: 0 !important;
      height: 26px !important;
      font-family: inherit !important;
      color: var(--tblr-body-color, #354052) !important;
    }
    body[data-bs-theme="dark"] .select2-container--default .select2-selection--multiple {
      background-color: #1a2234 !important;
      border-color: #2e394f !important;
    }
    body[data-bs-theme="dark"] .select2-dropdown {
      background-color: #1a2234 !important;
      border-color: #2e394f !important;
      color: #f4f6fa !important;
    }
    body[data-bs-theme="dark"] .select2-results__option--selectable {
      color: #f4f6fa !important;
    }
    body[data-bs-theme="dark"] .select2-results__option--highlighted[aria-selected] {
      background-color: #206bc4 !important;
      color: #ffffff !important;
    }
  </style>

  <!-- Tab: Personel Bilgileri -->
  <div id="tab-info" class="person-tab-content">
    <?php if ($message): ?>
      <div class="alert alert-<?php echo $status; ?> d-flex align-items-center mb-3" role="alert" style="border-radius: 14px;">
        <div class="alert-icon me-3">
          <?php if ($status == 'success'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon"><path d="M5 12l5 5l10 -10"></path></svg>
          <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
          <?php endif; ?>
        </div>
        <div class="text-sm"><?php echo $message; ?></div>
      </div>
    <?php endif; ?>

    <div class="mobile-card p-3 shadow-sm mb-4">
      <form method="POST" action="">
        <div class="row g-3">
          <!-- Temel Bilgiler Grubu -->
          <div class="col-12">
            <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-2">Genel Bilgiler</label>
            <div class="form-floating mb-3">
              <input type="text" name="full_name" class="form-control" id="floatingFullName" placeholder="Ad Soyad" value="<?php echo htmlspecialchars($person->full_name); ?>" required>
              <label for="floatingFullName">Ad Soyad</label>
            </div>
            
            <div class="form-floating mb-3">
              <input type="text" name="tc_no" class="form-control" id="floatingTcNo" placeholder="T.C. Kimlik No" value="<?php echo Security::safeDecrypt($person->kimlik_no ?? ''); ?>" inputmode="numeric" pattern="[0-9]*" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);">
              <label for="floatingTcNo">T.C. Kimlik No</label>
            </div>
          </div>

          <!-- İletişim Grubu -->
          <div class="col-12">
            <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-2">İletişim & Erişim</label>
            <div class="form-floating mb-3">
              <input type="tel" name="phone" class="form-control" id="floatingPhone" placeholder="Telefon" value="<?php echo htmlspecialchars($person->phone ?? ''); ?>">
              <label for="floatingPhone">Telefon</label>
            </div>
            <div class="form-floating mb-3">
              <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="E-posta" value="<?php echo htmlspecialchars($person->email ?? ''); ?>">
              <label for="floatingEmail">E-posta</label>
            </div>
            <div class="form-floating mb-3">
              <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="PWA Giriş Şifresi">
              <label for="floatingPassword">Yeni PWA Giriş Şifresi</label>
            </div>
            <div class="form-floating mb-3">
              <input type="text" name="iban_number" class="form-control" id="floatingIban" placeholder="TR..." value="<?php echo Security::safeDecrypt($person->iban_number ?? ''); ?>" maxlength="32">
              <label for="floatingIban">İban Numarası</label>
            </div>
          </div>

          <!-- Ücret & Çalışma Grubu -->
          <div class="col-12">
            <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-2">Çalışma & Ücret</label>
            
            <div class="d-flex gap-2 mb-3">
              <input type="radio" class="btn-check" name="wage_type" id="wage_mavi" value="2" <?php echo ($person->wage_type == 2) ? 'checked' : ''; ?>>
              <label class="btn btn-outline-primary w-50 py-2 border-2" for="wage_mavi" style="border-radius: 10px;">Mavi Yaka</label>

              <input type="radio" class="btn-check" name="wage_type" id="wage_beyaz" value="1" <?php echo ($person->wage_type == 1) ? 'checked' : ''; ?>>
              <label class="btn btn-outline-primary w-50 py-2 border-2" for="wage_beyaz" style="border-radius: 10px;">Beyaz Yaka</label>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-12">
                <div class="form-floating mb-3 form-floating-select2">
                  <?php echo $jobGroupsHelper->jobGroupsSelect("job_group", $person->job_group ?? ''); ?>
                  <label for="job_group">İş Grubu</label>
                </div>
              </div>
              <div class="col-12">
                <div class="form-floating mb-3 form-floating-select2">
                  <?php echo $teamsHelper->teamsSelect("team_id", $person->ekip ?? ($person->team_id ?? '')); ?>
                  <label for="team_id">Ekibi</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating">
                  <input type="number" step="0.01" name="daily_wage" class="form-control" id="floatingDailyWage" placeholder="0.00" value="<?php echo (float)$person->daily_wages; ?>">
                  <label for="floatingDailyWage">Yevmiye / Maaş</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating">
                  <input type="text" name="job" class="form-control" id="floatingJob" placeholder="Görevi" value="<?php echo htmlspecialchars($person->job ?? ''); ?>">
                  <label for="floatingJob">Görevi</label>
                </div>
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <div class="form-floating">
                  <input type="text" name="job_start_date" class="form-control flatpickr" id="floatingStartDate" value="<?php echo htmlspecialchars($person->job_start_date ?? ''); ?>" placeholder="İşe Giriş" readonly>
                  <label for="floatingStartDate">İşe Giriş</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating">
                  <input type="text" name="job_end_date" class="form-control flatpickr" id="floatingEndDate" value="<?php echo htmlspecialchars($person->job_end_date ?? ''); ?>" placeholder="İşten Çıkış" readonly>
                  <label for="floatingEndDate">İşten Çıkış</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Proje & Adres -->
          <div class="col-12">
            <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-2">Detaylar</label>
            <div class="form-group mb-3">
              <label for="floatingPersonProjects" class="form-label text-semibold text-xs text-muted text-uppercase mb-2">Çalıştığı Projeler (Çoklu Seçim)</label>
              <select name="person_project[]" id="floatingPersonProjects" class="form-select select2-init" multiple="multiple" data-placeholder="Projeleri Seçin" style="width: 100%;">
                <?php foreach ($projects as $project): ?>
                  <option value="<?php echo $project->id; ?>" <?php echo in_array($project->id, $personProjectsIds) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($project->project_name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-floating mb-3">
              <textarea name="address" id="floatingAddress" class="form-control" placeholder="Adres Bilgisi" style="height: 100px;"><?php echo htmlspecialchars($person->address ?? ''); ?></textarea>
              <label for="floatingAddress">Adres Bilgisi</label>
            </div>

            <div class="form-floating mb-3">
              <textarea name="description" id="floatingDescription" class="form-control" placeholder="Açıklama" style="height: 100px;"><?php echo htmlspecialchars($person->description ?? ''); ?></textarea>
              <label for="floatingDescription">Açıklama</label>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" name="save_person" class="btn btn-primary w-100 py-3 shadow-sm btn-active-scale" style="border-radius: 14px; font-weight: 700; letter-spacing: 0.5px;">
            <i class="ti ti-device-floppy me-2" style="font-size: 1.2rem;"></i> DEĞİŞİKLİKLERİ KAYDET
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tab: Puantaj Cetveli -->
  <?php
    $prevMonth = (int)$month - 1;
    $prevYear = (int)$year;
    if ($prevMonth == 0) {
        $prevMonth = 12;
        $prevYear--;
    }

    $nextMonth = (int)$month + 1;
    $nextYear = (int)$year;
    if ($nextMonth == 13) {
        $nextMonth = 1;
        $nextYear++;
    }
  ?>
  <div id="tab-puantaj" class="person-tab-content d-none">
    <div class="mobile-card p-4 shadow-sm mb-4 text-center">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <a href="person-edit?id=<?php echo $id_encrypted; ?>&month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>&tab=puantaj" class="btn btn-icon btn-ghost-secondary rounded-circle btn-calendar-nav"><i class="ti ti-chevron-left fs-2"></i></a>
        <h3 class="mb-0 font-weight-bold d-flex align-items-center gap-2" style="font-size: 1.15rem;">
          <?php echo Date::monthName($month); ?> <?php echo $year; ?>
          <?php 
          $targetDate = ($month == date('m') && $year == date('Y')) ? date('Y-m-d') : "$year-$month-01";
          ?>
          <a href="puantaj?date=<?php echo $targetDate; ?>" class="btn btn-icon btn-ghost-primary rounded-circle" style="width: 28px; height: 28px; min-height: 28px;">
            <i class="ti ti-external-link icon" style="font-size: 1.1rem;"></i>
          </a>
        </h3>
        <a href="person-edit?id=<?php echo $id_encrypted; ?>&month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>&tab=puantaj" class="btn btn-icon btn-ghost-secondary rounded-circle btn-calendar-nav"><i class="ti ti-chevron-right fs-2"></i></a>
      </div>

      <div class="calendar-grid">
        <div class="calendar-day-header">Pzt</div>
        <div class="calendar-day-header">Sal</div>
        <div class="calendar-day-header">Çar</div>
        <div class="calendar-day-header">Per</div>
        <div class="calendar-day-header">Cum</div>
        <div class="calendar-day-header">Cmt</div>
        <div class="calendar-day-header text-danger">Paz</div>

        <?php
        $daysInMonth = Date::daysInMonth($month, $year);
        $firstDayTimestamp = strtotime("$year-$month-01");
        $startDay = date('N', $firstDayTimestamp); // 1 (Pzt) - 7 (Paz)
        
        // Boş günler
        for ($i = 1; $i < $startDay; $i++) {
            echo '<div class="calendar-day empty"></div>';
        }

        // Ayın günleri
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDateYmd = sprintf("%s%s%02d", $year, $month, $day);
            $pData = $puantajMap[$currentDateYmd] ?? null;
            $class = "";
            $style = "";
            $displayContent = $day;
            if ($pData) {
                // Puantaj türüne göre renk
                $turu = $puantajModel->getPuantajTuruById($pData->puantaj_id);
                if ($turu) {
                    $style = "background-color: {$turu->ArkaPlanRengi}; color: {$turu->FontRengi};";
                    $class = "has-puantaj";
                    $displayContent = htmlspecialchars($turu->PuantajKod);
                }
            }
            $isSunday = (date('N', strtotime("$year-$month-$day")) == 7);
            echo '<div class="calendar-day '.$class.' '.($isSunday ? 'text-danger' : '').'" style="'.$style.'">'.$displayContent.'</div>';
        }
        ?>
      </div>

      <?php
      $totalHours = 0;
      $totalBalance = 0;
      foreach ($personPuantaj as $p) {
          $totalHours += $p->saat;
          $totalBalance += $p->tutar;
      }
      ?>

      <div class="mt-4 pt-3 border-top d-flex justify-content-around">
          <div class="text-center">
              <div class="text-xs text-muted text-uppercase font-weight-bold mb-1">Toplam Mesai</div>
              <div class="text-bold text-lg text-primary"><?php echo $totalHours; ?></div>
          </div>
          <div class="text-center">
              <div class="text-xs text-muted text-uppercase font-weight-bold mb-1">Hakediş</div>
              <div class="text-bold text-lg text-success"><?php echo Helper::formattedMoney($totalBalance); ?></div>
          </div>
      </div>
    </div>
  </div>

  <!-- Tab: Ödemeler & Finans -->
  <div id="tab-finance" class="person-tab-content d-none">
    <?php
    $financialHelper = new Financial();
    $bordroModel = new Bordro();
    
    // Özet Bilgiler (Bordro Modelini Kullanarak)
    $summary = $bordroModel->sumAllIncomeExpense($id);
    $total_income = $summary->total_income ?? 0;
    $total_expense = $summary->total_expense ?? 0;
    $balance = $total_income - $total_expense;
    
    // Gelir Gider Listesi (Bordro Modelini Kullanarak)
    $income_expenses = $bordroModel->getPersonWorkTransactions($id);
    
    // Hakedişleri aya göre grupla
    $hakedisler = [];
    $diger_islemler = [];
    
    foreach ($income_expenses as $item) {
        if ($item->kategori == 14) { // Puantaj
            $key = $item->yil . '-' . $item->ay;
            if (!isset($hakedisler[$key])) {
                $hakedisler[$key] = (object)[
                    'type' => 'hakedis',
                    'yil' => $item->yil,
                    'ay' => $item->ay,
                    'tutar' => 0,
                    'gun' => $item->gun,
                    'aciklama' => Date::monthName($item->ay) . ' ' . $item->yil . ' Hakedişi'
                ];
            }
            $hakedisler[$key]->tutar += $item->tutar;
        } else {
            $item->type = 'islem';
            $diger_islemler[] = $item;
        }
    }
    
    // Tüm işlemleri birleştir ve tarihe göre sırala
    $all_items = array_merge(array_values($hakedisler), $diger_islemler);
    usort($all_items, function($a, $b) {
        return strcmp($b->gun, $a->gun);
    });
    ?>

    <!-- Özet Kartları (Yeni Tasarım) -->
    <div class="row g-2 mb-4 px-1">
      <div class="col-4">
        <div class="mobile-card p-2 text-center border-0 shadow-sm" style="background: #e6f6ec; color: #2fb344; border-radius: 16px;">
          <div class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem; color: #2fb344;">GELİR</div>
          <div class="text-bold small" style="font-size: 0.85rem;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_income); ?></div>
        </div>
      </div>
      <div class="col-4">
        <div class="mobile-card p-2 text-center border-0 shadow-sm" style="background: #fbe9e9; color: #d63f3f; border-radius: 16px;">
          <div class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem; color: #d63f3f;">GİDER</div>
          <div class="text-bold small" style="font-size: 0.85rem;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_expense); ?></div>
        </div>
      </div>
      <div class="col-4">
        <div class="mobile-card p-2 text-center border-0 shadow-sm" style="background: #e8f1f9; color: #206bc4; border-radius: 16px;">
          <div class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem; color: #206bc4;">BAKİYE</div>
          <div class="text-bold small" style="font-size: 0.85rem;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($balance); ?></div>
        </div>
      </div>
    </div>

    <!-- İşlem Listesi (Kasa Tasarımı) -->
    <div id="person-finance-list" class="px-1">
      <div class="list-group list-group-mobile shadow-sm" style="border-radius: 20px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06);">
        <?php if (empty($all_items)): ?>
          <div class="p-5 text-center text-muted bg-white border-0">
            <i class="ti ti-receipt-off fs-1 mb-2 opacity-20"></i>
            <p class="mb-0 text-sm">Henüz finansal işlem bulunamadı.</p>
          </div>
        <?php else: ?>
          <?php foreach ($all_items as $index => $item): 
            $is_hakedis = ($item->type === 'hakedis');
            $is_income = false;
            if ($is_hakedis) {
                $is_income = true;
            } else {
                $type_info = $financialHelper->getTransactionTypeById($item->kategori);
                $is_income = ($type_info->type_id == 1);
            }
          ?>
            <div class="person-item-wrapper <?php echo $index > 0 ? 'border-top' : ''; ?>" style="border-radius: 0; margin-bottom: 0; box-shadow: none;">
              <?php if (!$is_hakedis): ?>
                <div class="person-item-actions">
                  <button class="btn-swipe-delete btn-delete-payment" data-id="<?php echo Security::encrypt($item->id); ?>" data-type="<?php echo $item->tablename ?? 'Diger'; ?>">
                    <i class="ti ti-trash"></i>
                    <span>Sil</span>
                  </button>
                </div>
              <?php endif; ?>
              <div class="person-item-content <?php echo $is_hakedis ? 'btn-hakedis-detail' : 'btn-edit-payment'; ?>" 
                   <?php if (!$is_hakedis): ?>data-id="<?php echo Security::encrypt($item->id); ?>"<?php endif; ?>
                   data-month="<?php echo $item->ay ?? ''; ?>" 
                   data-year="<?php echo $item->yil ?? ''; ?>"
                   style="cursor: pointer;">
                <div class="list-group-item border-0 py-3.5 px-3 w-100 bg-transparent d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 40px; height: 40px; border: none; background: <?php echo $is_income ? 'rgba(47, 179, 68, 0.12)' : 'rgba(214, 63, 63, 0.12)'; ?>; color: <?php echo $is_income ? '#2fb344' : '#d63f3f'; ?>;">
                      <i class="ti <?php echo $is_income ? 'ti-arrow-up-right' : 'ti-arrow-down-left'; ?>" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                      <div class="text-bold <?php echo $is_hakedis ? 'text-primary' : 'text-dark'; ?>" style="font-size: 0.9rem; margin-bottom: 1px;">
                        <?php echo htmlspecialchars($is_hakedis ? ($item->aciklama ?: 'Hakedişi') : ($item->turu ?: 'İşlem')); ?>
                      </div>
                      <div class="text-muted text-xs d-flex align-items-center gap-1">
                        <span><?php echo $is_hakedis ? 'Bordro İşlemi' : ($item->aciklama ?: 'İşlem Detayı'); ?></span>
                        <span class="opacity-50">•</span>
                        <span><?php echo Date::dmY($item->gun); ?></span>
                        <?php if ($is_hakedis): ?>
                          <span class="ms-1 px-1.5 py-0.5 bg-primary-lt text-uppercase font-weight-bold" style="font-size: 0.55rem; border-radius: 4px; letter-spacing: 0.2px;">HAKEDİŞ</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="text-bold <?php echo $is_income ? 'text-green' : 'text-red'; ?>" style="font-size: 0.95rem; letter-spacing: -0.3px;">
                      <?php echo $is_income ? '+' : '-'; ?> ₺<?php echo Helper::formattedMoneyWithoutCurrency($item->tutar); ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Floating Action Button for Finance (Moved higher to avoid overlap with Menu FAB) -->
    <a href="#" class="mobile-fab shadow-lg" data-bs-toggle="modal" data-bs-target="#add-person-transaction-modal" style="bottom: 155px; background-color: #2fb344; box-shadow: 0 4px 16px rgba(47, 179, 68, 0.4);">
      <i class="ti ti-plus"></i>
    </a>
  </div>

  <!-- Tab: Evraklar -->
  <div id="tab-documents" class="person-tab-content d-none">
     <div class="mobile-card p-5 text-center text-muted">
          <i class="ti ti-files fs-1 mb-3 opacity-20"></i>
          <h4 class="mb-1">Evrak Arşivi</h4>
          <p class="text-xs">Bu personele ait dökümanlar yakında burada listelenecek.</p>
          <button class="btn btn-outline-primary btn-sm rounded-pill mt-3">Yeni Evrak Yükle</button>
  </div>

  <!-- FAB code removed from here -->
</div>

<!-- Payroll Detail Modal -->
<div class="modal modal-blur fade" id="payroll-detail-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content" style="border-radius: 20px; border: none;">
      <div class="modal-header py-3" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
        <h5 class="modal-title text-semibold">Bordro Detayı</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3" id="payroll-detail-content">
        <!-- AJAX Content Here -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Yükleniyor...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Person Transaction Modal -->
<div class="modal modal-blur fade" id="add-person-transaction-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 20px; border: none;">
      <div class="modal-header py-3" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
        <h5 class="modal-title text-semibold">Yeni Ödeme / Gelir</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <form id="add-person-transaction-form">
          <input type="hidden" name="transaction_id" id="transaction_id" value="0">
          <input type="hidden" name="gm_person_name" value="<?php echo Security::encrypt($id); ?>">
          <input type="hidden" name="gm_amount_money" value="1">
          <input type="hidden" name="gm_project_id" value="0">
          <input type="hidden" name="gm_company" value="0">
          <input type="hidden" name="action" value="saveTransaction">

          <!-- İşlem Yönü -->
          <div class="mb-3">
            <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">İşlem Yönü</label>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-selectgroup-item w-100">
                  <input type="radio" name="transaction_type" value="1" class="form-selectgroup-input">
                  <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2.5 rounded-3 text-semibold text-xs border btn-type-income" style="cursor: pointer; transition: all 0.2s;">
                    <i class="ti ti-arrow-up-right text-success me-1"></i> Gelir
                  </span>
                </label>
              </div>
              <div class="col-6">
                <label class="form-selectgroup-item w-100">
                  <input type="radio" name="transaction_type" value="2" class="form-selectgroup-input" checked>
                  <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2.5 rounded-3 text-semibold text-xs border btn-type-expense" style="cursor: pointer; transition: all 0.2s;">
                    <i class="ti ti-arrow-down-left text-danger me-1"></i> Ödeme
                  </span>
                </label>
              </div>
            </div>
          </div>

          <!-- Kasa Seçimi -->
          <div class="form-floating mb-3">
            <select name="gm_case_id" id="gm_case_id" class="form-select select2-init" required>
              <option value="0">Kasa Seçiniz</option>
              <?php 
              $caseObj = new Cases();
              $active_cases = $caseObj->allCaseWithFirmId();
              foreach ($active_cases as $c): ?>
                <option value="<?php echo Security::encrypt($c->id); ?>" <?php echo $c->isDefault ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($c->case_name); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <label for="gm_case_id">Kasa <span class="text-danger">*</span></label>
          </div>

          <!-- Tutar -->
          <div class="form-floating mb-3">
            <input type="text" inputmode="decimal" name="amount" id="amount-input" class="form-control text-bold" placeholder="0,00" required>
            <label for="amount-input">Tutar (₺) <span class="text-danger">*</span></label>
          </div>

          <!-- Tarih & Tür -->
          <div class="row g-2 mb-3">
            <div class="col-6">
              <div class="form-floating">
                <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" placeholder="İşlem Tarihi">
                <label for="transaction_date">İşlem Tarihi</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-floating">
                <select name="gm_incexp_type" id="gm_incexp_type" class="form-select select2-init" required>
                  <option value="">Tür Seçiniz</option>
                </select>
                <label for="gm_incexp_type">İşlem Türü <span class="text-danger">*</span></label>
              </div>
            </div>
          </div>

          <!-- Açıklama -->
          <div class="form-floating">
            <textarea name="description" id="floatingDescription" class="form-control" placeholder="Açıklama" style="height: 80px;"></textarea>
            <label for="floatingDescription">Açıklama</label>
          </div>
        </form>
      </div>
      <div class="modal-footer py-2 d-flex justify-content-between" style="border-top: 1px solid rgba(0,0,0,0.06);">
        <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Vazgeç</button>
        <button type="button" class="btn btn-primary px-4" id="submit-person-transaction">Kaydet</button>
      </div>
    </div>
  </div>
</div>



<script>
$(document).ready(function() {
    // Select2'yi sayfa ilk yüklendiğinde de başlat
    if ($.fn.select2) {
        $('.select2-init').select2();
        
        $("#job_group").select2({
            tags: true,
            placeholder: "İş Grubu Seçiniz veya Yazınız",
            allowClear: true,
            width: '100%'
        });

        $("#team_id").select2({
            tags: true,
            placeholder: "Ekip Seçiniz veya Yazınız",
            allowClear: true,
            width: '100%'
        });

        // Floating label effect for Select2
        $('.form-floating-select2 select').on('select2:open', function() {
            $(this).closest('.form-floating-select2').addClass('is-focused');
        }).on('select2:close', function() {
            $(this).closest('.form-floating-select2').removeClass('is-focused');
            if ($(this).val()) {
                $(this).closest('.form-floating-select2').addClass('has-value');
            } else {
                $(this).closest('.form-floating-select2').removeClass('has-value');
            }
        }).on('change', function() {
            if ($(this).val()) {
                $(this).closest('.form-floating-select2').addClass('has-value');
            } else {
                $(this).closest('.form-floating-select2').removeClass('has-value');
            }
        });

        // Initial check
        $('.form-floating-select2 select').each(function() {
            if ($(this).val()) {
                $(this).closest('.form-floating-select2').addClass('has-value');
            }
        });
    }

    // URL'deki tab parametresine göre otomatik sekme açma
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab');
    if (initialTab) {
        $('.tab-trigger[data-tab="' + initialTab + '"]').trigger('click');
    } else if (urlParams.has('month') || urlParams.has('year')) {
        // URL'de ay/yıl parametresi varsa Puantaj sekmesini otomatik aç
        $('.tab-trigger[data-tab="puantaj"]').click();
    }

    // Dropdown Manuel Tetikleyici (Working pattern from projects/manage.php)
    $(document).on('click', '#personTabsDropdown', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var menu = $(this).next('.dropdown-menu');
        $('.dropdown-menu').not(menu).removeClass('show');
        menu.toggleClass('show');
    });

    // Sekme Değiştirme Mantığı
    $(document).on('click', '.tab-trigger', function(e) {
        e.preventDefault();
        var tabId = $(this).data('tab');
        var title = $(this).data('title');
        
        // Tüm sekmeleri gizle
        $('.person-tab-content').addClass('d-none');
        // Seçili sekmeyi göster
        $('#tab-' + tabId).removeClass('d-none');
        
        // Başlığı güncelle
        $('#page-title').text(title);
        
        // Dropdown'daki aktif durumu güncelle
        $('.tab-trigger').removeClass('active');
        $(this).addClass('active');
        
        // Dropdown'ı kapat
        $('.dropdown-menu').removeClass('show');
        
        // Eğer Select2 varsa ve yeni sekmede görünmüyorsa tekrar init et
        if (tabId === 'info' && $.fn.select2) {
            $('.select2-init').select2();
        }

        // Kasa alt türlerini yükle (Eğer finans sekmesi açıldıysa)
        if (tabId === 'finance') {
            fetchSubTypes($('input[name="transaction_type"]:checked').val());
        }
    });

    // 1. Kasa Alt Türlerini Getir
    function fetchSubTypes(type) {
        var select = $('#gm_incexp_type');
        select.html('<option value="">Yükleniyor...</option>').trigger('change');
        
        $.post('api/financial/transaction_v2.php', {
            action: 'getSubTypes',
            type: type
        }, function(response) {
            try {
                var res = typeof response === 'object' ? response : JSON.parse(response);
                select.empty();
                select.append('<option value="">Tür Seçiniz</option>');
                if (res.subTypes && res.subTypes.length > 0) {
                    res.subTypes.forEach(function(item) {
                        select.append('<option value="' + item.id + '">' + item.name + '</option>');
                    });
                } else if(res.status === 'error') {
                     console.error("Backend auth error:", res.message);
                }
                // Refresh Select2 if active
                if ($.fn.select2) {
                    select.trigger('change');
                }
            } catch (e) { 
                console.error("JSON Parse error:", e, "Response was:", response); 
            }
        });
    }

    // Watch radio switch within modal
    $(document).on('change', 'input[name="transaction_type"]', function() {
        fetchSubTypes($(this).val());
    });

    // Listen for edit clicks
    $(document).on('click', '.btn-edit-payment', function(e) {
        e.preventDefault();
        var transId = $(this).data('id');
        
        // Show loading indicator on the row or modal
        var modal = $('#add-person-transaction-modal');
        modal.find('.modal-title').text('İşlem Düzenle');
        modal.find('#transaction_id').val(transId);
        
        // Collect all current encrypted case IDs to help backend map the correct encrypted ID back to us
        var currentCases = [];
        $('#gm_case_id option').each(function() {
            var val = $(this).val();
            if (val && val != '0') currentCases.push(val);
        });

        $.post('api/financial/transaction_v2.php', {
            action: 'getTransaction',
            id: transId,
            cases: currentCases.join(','),
            projects: '',
            persons: '',
            companies: ''
        }, function(res) {
            try {
                var response = typeof res === 'object' ? res : JSON.parse(res);
                if (response.status === 'success' && response.transaction) {
                    var t = response.transaction;
                    
                    // 1. Radio buttons (Income/Expense)
                    $('input[name="transaction_type"][value="' + t.type_id + '"]').prop('checked', true).trigger('change');
                    
                    // 2. Case dropdown (Needs encrypted compare sometimes, handle both)
                    // Wait, backend getTransaction might map standard IDs. If select is select2 it needs careful trigger
                    $('#gm_case_id').val(t.case_id).trigger('change');
                    
                    // 3. Amount
                    $('#amount-input').val(t.amount);
                    
                    // 4. Date (Convert d.m.Y to Y-m-d)
                    if(t.date.indexOf('.') > -1) {
                        var d = t.date.split('.');
                        var isoDate = d[2] + '-' + d[1] + '-' + d[0];
                        $('#transaction_date').val(isoDate);
                    } else {
                        $('#transaction_date').val(t.date);
                    }
                    
                    // 5. Sub type (Load after a slight delay to allow fetchSubTypes ajax to run first!)
                    setTimeout(function() {
                        $('#gm_incexp_type').val(t.users_type_id).trigger('change');
                    }, 500);
                    
                    // 6. Description
                    $('#floatingDescription').val(t.description);
                    
                    modal.modal('show');
                } else {
                    Swal.fire('Hata', 'İşlem detayları yüklenemedi.', 'error');
                }
            } catch (err) { console.error(err); }
        });
    });

    // Clear modal when clicking the general add (+) FAB
    $('[data-bs-target="#add-person-transaction-modal"]').on('click', function() {
         var modal = $('#add-person-transaction-modal');
         if(!modal.hasClass('show')) { // only clear if not already triggered by JS click
             modal.find('.modal-title').text('Yeni Ödeme / Gelir');
             modal.find('#transaction_id').val(0);
             modal.find('#amount-input').val('');
             modal.find('#floatingDescription').val('');
             modal.find('#transaction_date').val(new Date().toISOString().split('T')[0]);
         }
    });

    // Preload sub-types when modal is opened to be absolutely sure it populated
    $('#add-person-transaction-modal').on('show.bs.modal', function () {
        var currentType = $('input[name="transaction_type"]:checked').val() || 2;
        fetchSubTypes(currentType);
        
        // Make sure select2 works inside Bootstrap modal container
        if ($.fn.select2) {
             $('.select2-init', this).select2({
                  dropdownParent: $('#add-person-transaction-modal')
             });
        }
    });

    // 2. Personel İşlemi Kaydet
    $('#submit-person-transaction').on('click', function(e) {
        e.preventDefault();
        try {
            var btn = $(this);
            var form = $('#add-person-transaction-form');
            
            // Normalize Amount value for robust validation
            var amountStr = $('#amount-input').val() || "";
            var amountNum = parseFloat(amountStr.toString().replace(',', '.'));
            
            // Validation Checks
            if(!$('#gm_incexp_type').val()){
                 Swal.fire('Uyarı', 'Lütfen İşlem Türü seçiniz!', 'warning');
                 return;
            }
            if(!amountStr || isNaN(amountNum) || amountNum <= 0){
                 Swal.fire('Uyarı', 'Lütfen geçerli bir tutar giriniz!', 'warning');
                 return;
            }
            
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Kaydediliyor...');
            
            // Serialize to Array to safely modify values
            var formData = form.serializeArray();
            
            // Standardize amount: backend EXPECTS comma format, convert all periods to commas for backend safe conversion
            $.each(formData, function(index, field) {
                if (field.name === 'amount') {
                    field.value = field.value.toString().replace('.', ',');
                }
            });
            
            // Critical debug logging before transmit
            console.log("Submitting data to transaction.php: ", formData);
            
            $.post('api/financial/transaction_v2.php', formData, function(res) {
            btn.prop('disabled', false).html('Kaydet');
            try {
                var response = typeof res === 'object' ? res : JSON.parse(res);
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Başarılı',
                        text: 'Personel Hareketi başarıyla kaydedildi.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                         // Persist context by reloading directly to the finance tab
                         var url = new URL(window.location.href);
                         url.searchParams.set('tab', 'finance');
                         window.location.href = url.toString();
                    });
                } else {
                    Swal.fire('Hata', response.message || 'Kaydetme başarısız.', 'error');
                }
            } catch (e) { 
                console.error("Submission Parse Error:", e, res);
                Swal.fire('Hata', 'Beklenmeyen bir sunucu yanıtı alındı.', 'error'); 
            }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('Kaydet');
                Swal.fire('Hata', 'Ağ bağlantı hatası oluştu.', 'error');
            });
        } catch (runtimeErr) {
            console.error("Runtime UI Crash: ", runtimeErr);
            Swal.fire('Yazılım Hatası', 'Bir arayüz hatası oluştu: ' + runtimeErr.message, 'error');
            $(this).prop('disabled', false).html('Kaydet');
        }
    });

    // 3. Ödeme/İşlem Silme (Swipe Actions)
    $(document).on('click', '.btn-delete-payment', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var btn = $(this);
        var id = btn.data('id');
        var type = btn.data('type');
        var personId = '<?php echo Security::encrypt($id); ?>';

        Swal.fire({
            title: 'Emin misiniz?',
            text: 'Bu işlemi silmek istediğinize emin misiniz?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#d63f3f',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/persons/person.php?person_id=' + personId + '&type=' + type,
                    type: 'POST',
                    data: {
                        action: 'deletePayment',
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            btn.closest('.person-item-wrapper').fadeOut(300, function() { $(this).remove(); });
                            Swal.fire({
                                title: 'Silindi',
                                text: response.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Hata', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Hata', 'İşlem başarısız veya bağlantı hatası oluştu.', 'error');
                    }
                });
            } else {
                // Reset swipe
                btn.closest('.person-item-wrapper').find('.person-item-content').css('transform', 'translateX(0)');
            }
        });
    });

    // 4. Bordro Detayı Görüntüleme
    $(document).on('click', '.btn-hakedis-detail', function() {
        var month = $(this).data('month');
        var year = $(this).data('year');
        var id = '<?php echo Security::encrypt($id); ?>';

        $('#payroll-detail-content').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Yükleniyor...</p></div>');
        $('#payroll-detail-modal').modal('show');

        // Yeni oluşturduğumuz mobil proxy API'sini kullanalım
        $.post('api/bordro/detail.php', { id: id, month: month, year: year }, function(html) {
            // Eğer dönen içerik içinde "DOCTYPE" varsa tam sayfa dönmüş demektir, uyarı verelim
            if (html.indexOf('<!DOCTYPE html>') !== -1 || html.indexOf('<html') !== -1) {
                $('#payroll-detail-content').html('<div class="alert alert-danger">Bordro detayı yüklenirken bir hata oluştu. Lütfen tekrar deneyin.</div>');
                console.error("API returned a full page instead of a fragment.");
            } else {
                $('#payroll-detail-content').html(html);
            }
        }).fail(function() {
            $('#payroll-detail-content').html('<div class="alert alert-danger">Sunucuyla iletişim kurulamadı.</div>');
        });
    });

    // 5. Swipe Logic for Finance Items
    let touchStartX = 0;
    let touchMoveX = 0;
    const swipeThreshold = 75;

    $(document).on('touchstart', '.person-item-content', function(e) {
        touchStartX = e.originalEvent.touches[0].clientX;
        touchMoveX = touchStartX;
        $('.person-item-content').not(this).css('transform', 'translateX(0)');
    });

    $(document).on('touchmove', '.person-item-content', function(e) {
        touchMoveX = e.originalEvent.touches[0].clientX;
        let diff = touchStartX - touchMoveX;
        
        // Only swipe left if not a hakedis/puantaj record
        if (diff > 0 && !$(this).hasClass('btn-hakedis-detail')) { 
            if (diff > swipeThreshold + 20) diff = swipeThreshold + 20;
            $(this).css('transition', 'none').css('transform', 'translateX(-' + diff + 'px)');
        } else if (diff < 0) {
            $(this).css('transform', 'translateX(0)');
        }
    });

    $(document).on('touchend', '.person-item-content', function(e) {
        let diff = touchStartX - touchMoveX;
        $(this).css('transition', 'transform 0.2s ease-out');
        
        if (diff > swipeThreshold / 2 && !$(this).hasClass('btn-hakedis-detail')) {
            $(this).css('transform', 'translateX(-' + swipeThreshold + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    // 6. Ajax Calendar Navigation
    $(document).on('click', '.btn-calendar-nav', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        
        var $tabPuantaj = $('#tab-puantaj');
        var originalContent = $tabPuantaj.html();
        
        $tabPuantaj.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Takvim Yükleniyor...</p></div>');
        
        $.get(url, function(data) {
            var newContent = $(data).find('#tab-puantaj').html();
            if(newContent) {
                $tabPuantaj.html(newContent);
                window.history.pushState({ path: url }, '', url);
            } else {
                $tabPuantaj.html(originalContent);
                Swal.fire('Hata', 'Takvim verisi alınamadı.', 'error');
            }
        }).fail(function() {
            $tabPuantaj.html(originalContent);
            Swal.fire('Hata', 'Bağlantı hatası oluştu.', 'error');
        });
    });

    // Duplicate urlParams removed for error fix

    // Dışarı tıklayınca kapatma
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown, .dropup').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
});
</script>

<style>
/* Unified Swipe to Delete Styles matching personnel and todos */
.person-item-wrapper {
    position: relative;
    overflow: hidden;
    background: #fff;
    border-radius: 18px;
    margin-bottom: 12px;
    user-select: none;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
body[data-bs-theme="dark"] .person-item-wrapper,
body[data-bs-theme="dark"] .person-item-content {
    background: #1e293b !important;
}
.person-item-actions {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    display: flex;
    align-items: center;
    background: #d63f3f;
    z-index: 1;
}
.person-item-content {
    position: relative;
    background: #fff;
    z-index: 2;
    transition: transform 0.2s ease-out;
    width: 100%;
}
.btn-swipe-delete {
    color: white;
    width: 75px;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    font-size: 0.7rem;
    font-weight: 600;
}
.btn-swipe-delete i {
    font-size: 1.2rem;
    margin-bottom: 2px;
}

.person-tab-content.d-none {
    display: none !important;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
    margin-bottom: 20px;
}

.calendar-day-header {
    font-size: 0.75rem;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    padding-bottom: 10px;
    text-align: center;
}

.calendar-day {
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 600;
    border-radius: 12px;
    background-color: #f8fafc;
    color: #1d273b;
    transition: all 0.2s ease;
}

.calendar-day.has-puantaj {
    font-weight: 700;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.calendar-day.empty {
    background-color: transparent;
}

.calendar-day.text-danger {
    color: #ef4444 !important;
}

.dropdown-menu {
    transition: all 0.2s ease-in-out;
    display: none;
    right: 0 !important;
    left: auto !important;
    border-radius: 16px !important;
}

.dropup .dropdown-menu {
    top: auto !important;
    bottom: 100% !important;
    margin-bottom: 12px !important;
    transform: translateY(15px) !important;
}

.dropup .dropdown-menu.show {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateY(0) !important;
}

.btn-check:checked + .btn-outline-primary {
    background-color: var(--tblr-primary-lt);
    color: var(--tblr-primary);
}

body[data-bs-theme="dark"] .calendar-day {
    background-color: #1e293b;
    color: #f4f6fa;
}

body[data-bs-theme="dark"] .calendar-day.empty {
    background-color: transparent;
}
</style>

<!-- Floating Action Button (FAB) for Personnel Menu -->
<div class="dropup position-fixed" style="right: 1.25rem; bottom: 100px; z-index: 1060;">
  <button class="mobile-fab border-0 shadow-lg dropdown-toggle no-caret" id="personTabsDropdown" type="button" aria-expanded="false" style="position: static; box-shadow: 0 4px 20px rgba(32, 107, 196, 0.5) !important;">
    <i class="ti ti-dots-vertical"></i>
  </button>
  <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 mb-3" style="border-radius: 16px; min-width: 220px; box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;">
    <li>
      <a class="dropdown-item active rounded-3 py-2 text-semibold mb-1 tab-trigger" href="#" data-tab="info" data-title="Personel Bilgileri">
        <i class="ti ti-user-circle me-2"></i> Personel Bilgileri
      </a>
    </li>
    <li>
      <a class="dropdown-item rounded-3 py-2 text-semibold mb-1 tab-trigger" href="#" data-tab="puantaj" data-title="Puantaj Cetveli">
        <i class="ti ti-calendar-event me-2"></i> Puantaj Cetveli
      </a>
    </li>
    <li>
      <a class="dropdown-item rounded-3 py-2 text-semibold mb-1 tab-trigger" href="#" data-tab="finance" data-title="Ödemeler & Finans">
        <i class="ti ti-cash-banknote me-2"></i> Ödemeler & Finans
      </a>
    </li>
    <li>
      <a class="dropdown-item rounded-3 py-2 text-semibold tab-trigger" href="#" data-tab="documents" data-title="Evraklar & Belgeler">
        <i class="ti ti-file-text me-2"></i> Evraklar & Belgeler
      </a>
    </li>
  </ul>
</div>
