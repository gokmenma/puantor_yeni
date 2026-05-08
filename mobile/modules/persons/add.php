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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_person'])) {
    $tc_no = trim($_POST['tc_no'] ?? '');
    if (strlen($tc_no) > 11) {
        $tc_no = substr($tc_no, 0, 11);
    }
    $data = [
        'firm_id' => $firm_id,
        'full_name' => $_POST['full_name'],
        'kimlik_no' => Security::encrypt($tc_no),
        'phone' => $_POST['phone'],
        'daily_wages' => $_POST['daily_wage'],
        'job_start_date' => !empty($_POST['job_start_date']) ? date('d.m.Y', strtotime($_POST['job_start_date'])) : date('d.m.Y'),
        'project_id' => $_POST['project_id']
    ];
    
    try {
        $personsModel->saveWithAttr($data);
        $message = "Personel başarıyla eklendi.";
        $status = "success";
    } catch (Exception $e) {
        $message = "Hata: " . $e->getMessage();
        $status = "danger";
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
    <form method="POST" action="">
      <div class="form-floating mb-3">
        <input type="text" name="full_name" class="form-control" id="floatingFullName" placeholder="Ad Soyad" required>
        <label for="floatingFullName">Ad Soyad</label>
      </div>

      <div class="form-floating mb-3">
        <input type="text" name="tc_no" class="form-control" id="floatingTcNo" placeholder="11 Haneli" inputmode="numeric" pattern="[0-9]*" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);">
        <label for="floatingTcNo">T.C. Kimlik No</label>
      </div>

      <div class="form-floating mb-3">
        <input type="tel" name="phone" class="form-control" id="floatingPhone" placeholder="05XX XXX XX XX">
        <label for="floatingPhone">Telefon</label>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <div class="form-floating">
            <input type="number" name="daily_wage" class="form-control" id="floatingDailyWage" placeholder="0.00">
            <label for="floatingDailyWage">Yevmiye</label>
          </div>
        </div>
        <div class="col-6">
          <div class="form-floating">
            <input type="date" name="job_start_date" class="form-control" id="floatingStartDate" value="<?php echo date('Y-m-d'); ?>" placeholder="Giriş Tarihi">
            <label for="floatingStartDate">Giriş Tarihi</label>
          </div>
        </div>
      </div>

      <div class="form-floating mb-4">
        <select name="project_id" id="floatingProject" class="form-select select2-init">
          <option value="0">Proje Seçin</option>
          <?php foreach ($projects as $project): ?>
            <option value="<?php echo $project->id; ?>"><?php echo htmlspecialchars($project->project_name); ?></option>
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
