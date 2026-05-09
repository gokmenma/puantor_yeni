<?php
// Puantor Mobil - Yeni Personel Ekleme
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Security;

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
        $data = [
            'firm_id' => $firm_id,
            'full_name' => trim($_POST['full_name']),
            'kimlik_no' => Security::encrypt($tc_no),
            'sigorta_no' => 0,
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => '',
            'project_id' => strval($_POST['project_id'] ?? '0'),
            'company_id' => 0,
            'daily_wages' => !empty($_POST['daily_wage']) ? floatval(str_replace(',', '.', $_POST['daily_wage'])) : 0,
            'iban_number' => Security::encrypt(''),
            'wage_type' => 2,
            'job_start_date' => date('d.m.Y', strtotime($_POST['job_start_date'])),
            'job_end_date' => '',
            'job' => '',
            'state' => 1,
            'email' => '',
            'job_group' => '',
            'team_id' => 0,
            'description' => ''
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

      <div class="row g-3 mb-3">
        <div class="col-6">
          <div class="form-floating">
            <input type="number" name="daily_wage" class="form-control" id="floatingDailyWage" placeholder="0.00" value="<?php echo htmlspecialchars($_POST['daily_wage'] ?? ''); ?>">
            <label for="floatingDailyWage">Yevmiye</label>
          </div>
        </div>
        <div class="col-6">
          <div class="form-floating">
            <input type="date" name="job_start_date" class="form-control" id="floatingStartDate" placeholder="Giriş Tarihi" value="<?php echo htmlspecialchars($_POST['job_start_date'] ?? ''); ?>" required>
            <label for="floatingStartDate">Giriş Tarihi</label>
          </div>
        </div>
      </div>

      <div class="form-floating mb-4">
        <select name="project_id" id="floatingProject" class="form-select select2-init">
          <option value="0">Proje Seçin</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?php echo $project->id; ?>" <?php echo (isset($_POST['project_id']) && $_POST['project_id'] == $project->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($project->project_name); ?></option>
          <?php endforeach; ?>
        </select>
        <label for="floatingProject">Varsayılan Proje</label>
      </div>

      <button type="submit" name="save_person" class="btn btn-primary w-100 py-2" style="border-radius: 12px; font-weight: 600;">
        <i class="ti ti-check me-2"></i> Personeli Kaydet
      </button>
    </form>
  </div>
</div>

<script>
$(document).ready(function() {
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
