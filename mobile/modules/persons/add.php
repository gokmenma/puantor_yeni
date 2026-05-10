<?php
// Puantor Mobil - Yeni Personel Ekleme
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
</style>
<?php
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/jobs.php";
require_once ROOT . "/App/Helper/teams.php";

use App\Helper\Security;

$jobGroupsHelper = new Jobs();
$teamsHelper = new Teams();

$personsModel = new Persons();
$projectsModel = new Projects();
$firm_id = $_SESSION['firm_id'] ?? 0;

$projects = $projectsModel->getProjectsByFirm($firm_id);

$message = "";
$status = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tc_no = trim($_POST['tc_no'] ?? '');
    if (strlen($tc_no) > 11) {
        $tc_no = substr($tc_no, 0, 11);
    }
    
    if (empty($_POST['full_name'])) {
        $message = "Hata: Ad Soyad zorunludur.";
        $status = "danger";
    } elseif (empty($_POST['tc_no'])) {
        $message = "Hata: T.C. Kimlik No zorunludur.";
        $status = "danger";
    } elseif (empty($_POST['job_start_date'])) {
        $message = "Hata: İşe giriş tarihi zorunludur.";
        $status = "danger";
    } else {
        $job_group = $_POST['job_group'] ?? '';
        // Eğer iş grubu sayısal değilse (yeni bir tag girilmişse) yeni grup oluştur
        if (!empty($job_group) && !is_numeric($job_group)) {
            $db = $personsModel->getDb();
            $stmt = $db->prepare("INSERT INTO job_groups (firm_id, group_name) VALUES (?, ?)");
            $stmt->execute([$firm_id, $job_group]);
            $job_group = $db->lastInsertId();
        }

        $team_val = !empty($_POST['team_id']) ? $_POST['team_id'] : 0;

        $data = [
            'firm_id' => $firm_id,
            'full_name' => trim($_POST['full_name']),
            'kimlik_no' => Security::encrypt($tc_no),
            'sigorta_no' => 0,
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'project_id' => strval($_POST['project_id'] ?? '0'),
            'company_id' => 0,
            'daily_wages' => !empty($_POST['daily_wage']) ? floatval(str_replace(',', '.', $_POST['daily_wage'])) : 0,
            'iban_number' => Security::encrypt($_POST['iban_number'] ?? ''),
            'wage_type' => $_POST['wage_type'] ?? 2,
            'job_start_date' => date('d.m.Y', strtotime($_POST['job_start_date'])),
            'job_end_date' => !empty($_POST['job_end_date']) ? date('d.m.Y', strtotime($_POST['job_end_date'])) : '',
            'job' => trim($_POST['job'] ?? ''),
            'state' => 1,
            'email' => trim($_POST['email'] ?? ''),
            'job_group' => $job_group,
            'team_id' => $team_val,
            'ekip' => $team_val,
            'description' => trim($_POST['description'] ?? '')
        ];
        
        $raw_date = $_POST['job_start_date']; // Input için sakla
        
        try {
            $personsModel->saveWithAttr($data);
            $message = "Personel başarıyla eklendi.";
            $status = "success";
            // Başarı durumunda form verilerini temizleyelim
            $_POST = [];
        } catch (Exception $e) {
            $message = "Sistem Hatası: " . $e->getMessage() . " (Kod: " . $e->getCode() . ")";
            $status = "danger";
        }
    }
}
?>

<div class="container px-0">
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="persons" class="btn btn-icon btn-sm btn-outline-secondary border-0">
      <i class="ti ti-chevron-left" style="font-size: 1.5rem;"></i>
    </a>
    <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Yeni Personel</h2>
  </div>

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

  <div class="mobile-card p-3">
    <form method="POST" action="" id="addPersonForm">
      <div class="form-floating mb-3">
        <input type="text" name="full_name" class="form-control" id="floatingFullName" placeholder="Ad Soyad" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
        <label for="floatingFullName">Ad Soyad</label>
      </div>

      <div class="form-floating mb-3">
        <input type="text" name="tc_no" class="form-control" id="floatingTcNo" placeholder="11 Haneli" inputmode="numeric" pattern="[0-9]*" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);" value="<?php echo htmlspecialchars($_POST['tc_no'] ?? ''); ?>" required>
        <label for="floatingTcNo">T.C. Kimlik No</label>
      </div>

      <div class="form-floating mb-3">
        <input type="tel" name="phone" class="form-control" id="floatingPhone" placeholder="05XX XXX XX XX" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        <label for="floatingPhone">Telefon</label>
      </div>

      <div class="form-floating mb-3">
        <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="E-posta" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        <label for="floatingEmail">E-posta</label>
      </div>

      <div class="form-floating mb-3">
        <input type="text" name="iban_number" class="form-control" id="floatingIban" placeholder="TR..." value="<?php echo htmlspecialchars($_POST['iban_number'] ?? ''); ?>" maxlength="32">
        <label for="floatingIban">İban Numarası</label>
      </div>

      <div class="mb-3">
        <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-2">Çalışma Şekli</label>
        <div class="d-flex gap-2">
          <input type="radio" class="btn-check" name="wage_type" id="wage_mavi" value="2" checked>
          <label class="btn btn-outline-primary w-50 py-2 border-2" for="wage_mavi" style="border-radius: 10px;">Mavi Yaka</label>

          <input type="radio" class="btn-check" name="wage_type" id="wage_beyaz" value="1">
          <label class="btn btn-outline-primary w-50 py-2 border-2" for="wage_beyaz" style="border-radius: 10px;">Beyaz Yaka</label>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-12">
          <div class="form-floating">
            <input type="number" name="daily_wage" class="form-control" id="floatingDailyWage" placeholder="0.00" value="<?php echo htmlspecialchars($_POST['daily_wage'] ?? ''); ?>">
            <label for="floatingDailyWage">Yevmiye / Maaş</label>
          </div>
        </div>
        <div class="col-6">
          <div class="form-floating">
            <input type="text" name="job_start_date" class="form-control flatpickr" id="floatingStartDate" placeholder="Giriş Tarihi" value="<?php echo htmlspecialchars($_POST['job_start_date'] ?? date('d.m.Y')); ?>" required readonly>
            <label for="floatingStartDate">Giriş Tarihi</label>
          </div>
        </div>
        <div class="col-6">
          <div class="form-floating">
            <input type="text" name="job_end_date" class="form-control flatpickr" id="floatingEndDate" placeholder="Çıkış Tarihi" value="<?php echo htmlspecialchars($_POST['job_end_date'] ?? ''); ?>" readonly>
            <label for="floatingEndDate">Çıkış Tarihi</label>
          </div>
        </div>
      </div>

      <div class="form-floating mb-3">
        <select name="project_id" id="floatingProject" class="form-select select2-init">
          <option value="0">Proje Seçin</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?php echo $project->id; ?>" <?php echo (isset($_POST['project_id']) && $_POST['project_id'] == $project->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($project->project_name); ?></option>
          <?php endforeach; ?>
        </select>
        <label for="floatingProject">Varsayılan Proje</label>
      </div>

      <div class="form-floating mb-3 form-floating-select2">
        <?php echo $jobGroupsHelper->jobGroupsSelect("job_group", $_POST['job_group'] ?? ''); ?>
        <label for="job_group">İş Grubu</label>
      </div>

      <div class="form-floating mb-3 form-floating-select2">
        <?php echo $teamsHelper->teamsSelect("team_id", $_POST['team_id'] ?? ''); ?>
        <label for="team_id">Ekibi</label>
      </div>

      <div class="form-floating mb-3">
        <input type="text" name="job" class="form-control" id="floatingJob" placeholder="Görevi" value="<?php echo htmlspecialchars($_POST['job'] ?? ''); ?>">
        <label for="floatingJob">Görevi</label>
      </div>

      <div class="form-floating mb-3">
        <textarea name="address" id="floatingAddress" class="form-control" placeholder="Adres" style="height: 80px;"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
        <label for="floatingAddress">Adres</label>
      </div>

      <div class="form-floating mb-4">
        <textarea name="description" id="floatingDescription" class="form-control" placeholder="Açıklama" style="height: 80px;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        <label for="floatingDescription">Açıklama</label>
      </div>

      <button type="submit" name="save_person" class="btn btn-primary w-100 py-2" style="border-radius: 12px; font-weight: 600;">
        <i class="ti ti-check me-2"></i> Personeli Kaydet
      </button>
    </form>
  </div>
</div>

<script>
$(document).ready(function() {
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

    $('#addPersonForm').on('submit', function(e) {
        const fullName = $('#floatingFullName').val().trim();
        const tcNo = $('#floatingTcNo').val().trim();
        const startDate = $('#floatingStartDate').val();
        
        let errors = [];
        if (!fullName) errors.push("Ad Soyad alanı zorunludur.");
        if (!tcNo) errors.push("T.C. Kimlik No alanı zorunludur.");
        else if (tcNo.length !== 11) errors.push("T.C. Kimlik No 11 haneli olmalıdır.");
        if (!startDate) errors.push("İşe giriş tarihi zorunludur.");
        
        if (errors.length > 0) {
            e.preventDefault();
            // Basit ve şık bir uyarı gösterelim
            const errorHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 14px;">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-triangle me-2" style="font-size: 1.25rem;"></i>
                        <div>${errors.join('<br>')}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $('.alert').remove(); // Mevcut uyarıları temizle
            $('.container').first().prepend(errorHtml);
            
            // Hatalı alana odaklan
            if (!fullName) $('#floatingFullName').focus();
            else if (!startDate) $('#floatingStartDate').focus();
            
            return false;
        }
        
        // Form gönderilirken butonu pasif yapalım
        $(this).find('button[type="submit"]').attr('disabled', 'disabled').html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Kaydediliyor...');
    });
});
</script>
