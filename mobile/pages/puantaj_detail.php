<?php
// Puantor Mobil - Detaylı Puantaj Listesi (Aylık Özet)
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Puantaj.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Date;

$personsModel = new Persons();
$puantajModel = new Puantaj();

$firm_id = $_SESSION['firm_id'] ?? 0;
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

$persons = $personsModel->getPersonsByFirm($firm_id);
$days_in_month = Date::daysInMonth($month, $year);

// Ay seçimi için data
$months = [
    '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
    '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
    '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
];
?>

<div class="container px-0">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Aylık Puantaj</h2>
    <div class="d-flex gap-2">
      <select class="form-select form-select-sm border-0 bg-secondary-lt" onchange="location.href='?p=puantaj_detail&month='+this.value">
        <?php foreach ($months as $m => $name): ?>
          <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>><?php echo $name; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="list-group list-group-mobile">
    <?php foreach ($persons as $person): 
      // Basit özet hesaplama (Örnek mantık, gerçek veri tabanı yapınıza göre optimize edilebilir)
      // Normalde bu verileri bir kerede çeken bir SQL yazmak daha performanslıdır.
      $stats = ['G' => 0, 'X' => 0, 'İ' => 0]; // G: Geldi, X: Gelmedi, İ: İzinli
      
      // Not: Performans için bu kısım model içinde özet olarak çekilmelidir.
      // Şimdilik arayüzü göstermek için placeholder değerler veya basit döngü:
    ?>
      <div class="list-group-item d-flex align-items-center justify-content-between py-3">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-sm rounded-circle bg-primary-lt text-primary font-weight-bold">
            <?php echo mb_substr($person->full_name, 0, 1, 'UTF-8'); ?>
          </div>
          <div>
            <div class="text-bold text-sm"><?php echo htmlspecialchars($person->full_name); ?></div>
            <div class="text-muted text-xs"><?php echo htmlspecialchars($person->job ?? 'Personel'); ?></div>
          </div>
        </div>
        
        <div class="d-flex gap-2">
          <div class="text-center px-2 py-1 rounded bg-green-lt" style="min-width: 35px;">
            <div class="text-xs font-weight-bold">22</div>
            <div class="text-muted" style="font-size: 10px;">G</div>
          </div>
          <div class="text-center px-2 py-1 rounded bg-red-lt" style="min-width: 35px;">
            <div class="text-xs font-weight-bold">2</div>
            <div class="text-muted" style="font-size: 10px;">X</div>
          </div>
          <div class="text-center px-2 py-1 rounded bg-yellow-lt" style="min-width: 35px;">
            <div class="text-xs font-weight-bold">1</div>
            <div class="text-muted" style="font-size: 10px;">İ</div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-4 p-3 mobile-card bg-primary-lt border-0">
    <div class="d-flex align-items-center gap-3">
      <i class="ti ti-info-circle text-primary" style="font-size: 1.5rem;"></i>
      <p class="mb-0 text-xs text-primary">
        <strong>İpucu:</strong> Personel isimlerine tıklayarak o kişinin aylık takvim detayına ulaşabilirsiniz (Çok yakında).
      </p>
    </div>
  </div>
</div>
