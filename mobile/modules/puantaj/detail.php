<?php
// Puantor Mobil - Detaylı Puantaj Listesi (Aylık Özet)
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Puantaj.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Date;
use App\Helper\Security;

$personsModel = new Persons();
$puantajModel = new Puantaj();

$firm_id = $_SESSION['firm_id'] ?? 0;
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

$person_filter_id = 0;
$id_encrypted = $_GET['person_id'] ?? '';
if ($id_encrypted) {
    $person_filter_id = Security::decrypt($id_encrypted);
}

// O ayın başlangıç ve bitiş tarihlerini belirle
$start_date = "$year-$month-01";
$end_date = date("Y-m-t", strtotime($start_date));

// Personelleri getir
if ($person_filter_id > 0) {
    $persons = [$personsModel->find($person_filter_id)];
} else {
    // Sadece o ay aktif olan veya puantajı olan personelleri getirmek daha mantıklı olabilir
    // Ancak mevcut yapı tüm firmayı getiriyor.
    $persons = $personsModel->getPersonsByFirm($firm_id);
}

// Tüm personellerin ID'lerini topla
$person_ids = array_map(function($p) { return $p->id; }, $persons);

// Tüm puantaj verilerini ve türlerini tek seferde çek (Performans için)
$allPuantajData = $puantajModel->getAllPuantajForPersons($person_ids, $start_date, $end_date);
$puantajTypes = $puantajModel->getAllPuantajTurleri();

$months = [
    '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
    '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
    '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
];
?>

<div class="container px-0">
  <div class="d-flex align-items-center justify-content-between mb-3 px-2">
    <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Aylık Puantaj Özeti</h2>
    <div class="d-flex gap-2">
      <select class="form-select form-select-sm border-0 bg-secondary-lt" onchange="location.href='?route=puantaj-detail&month='+this.value+'<?php echo $id_encrypted ? '&person_id='.$id_encrypted : ''; ?>'">
        <?php foreach ($months as $m => $name): ?>
          <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>><?php echo $name; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="list-group list-group-mobile">
    <?php foreach ($persons as $person): 
      if (!$person) continue;
      
      $p_id = $person->id;
      $stats = [];
      
      // Bu personelin o aydaki kayıtlarını işle
      if (isset($allPuantajData[$p_id])) {
          foreach ($allPuantajData[$p_id] as $p_row) {
              $type = $puantajTypes[$p_row->puantaj_id] ?? null;
              if ($type) {
                  $cat = $type->Turu;
                  $color = $type->ArkaPlanRengi;
                  $textColor = $type->FontRengi;
                  
                  // Kısaltma oluştur (Normal Çalışma -> NÇ)
                  $words = explode(' ', $cat);
                  $short = '';
                  foreach($words as $w) $short .= mb_substr($w, 0, 1, 'UTF-8');
                  
                  if (!isset($stats[$cat])) {
                      $stats[$cat] = (object)[
                          'count' => 0,
                          'short' => $short,
                          'color' => $color,
                          'textColor' => $textColor
                      ];
                  }
                  $stats[$cat]->count++;
              }
          }
      }
      
      // Önem sırasına göre sırala (Normal Çalışma en üstte)
      uksort($stats, function($a, $b) {
          if ($a == 'Normal Çalışma') return -1;
          if ($b == 'Normal Çalışma') return 1;
          return strcmp($a, $b);
      });
?>
      <div class="list-group-item d-flex align-items-center justify-content-between py-3 cursor-pointer" onclick="openCalendarModal('<?php echo $person->id; ?>', '<?php echo htmlspecialchars($person->full_name); ?>')">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-sm rounded-circle bg-primary-lt text-primary font-weight-bold">
            <?php echo mb_substr($person->full_name, 0, 1, 'UTF-8'); ?>
          </div>
          <div>
            <div class="text-bold text-sm"><?php echo htmlspecialchars($person->full_name); ?></div>
            <div class="text-muted text-xs"><?php echo htmlspecialchars($person->job ?? 'Personel'); ?></div>
          </div>
        </div>
        
        <div class="d-flex gap-1 flex-wrap justify-content-end" style="max-width: 150px;">
          <?php 
          $limit = 3;
          $i = 0;
          foreach ($stats as $catName => $stat): 
              if ($i >= $limit) break;
              // Çok az olanları (0 veya 1) bazen göstermemek isteyebiliriz ama burada hepsini gösterelim
              if ($stat->count == 0) continue;
          ?>
            <div class="text-center px-1.5 py-1 rounded" style="min-width: 32px; background-color: <?php echo $stat->color; ?>20; border: 1px solid <?php echo $stat->color; ?>40;">
              <div class="text-bold" style="font-size: 0.75rem; color: <?php echo $stat->color; ?>;"><?php echo $stat->count; ?></div>
              <div style="font-size: 8px; color: <?php echo $stat->color; ?>; font-weight: 800; opacity: 0.8;"><?php echo $stat->short; ?></div>
            </div>
          <?php 
            $i++;
          endforeach; 
          ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-4 p-3 mobile-card bg-primary-lt border-0 mx-2">
    <div class="d-flex align-items-center gap-3">
      <i class="ti ti-calendar-event text-primary" style="font-size: 1.5rem;"></i>
      <p class="mb-0 text-xs text-primary">
        <strong>Bilgi:</strong> Personel satırlarına tıklayarak o kişinin <strong>aylık takvim detayını</strong> görüntüleyebilirsiniz.
      </p>
    </div>
  </div>
</div>

<!-- Takvim Modalı -->
<div class="modal modal-blur fade" id="calendarModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold" id="calendarModalTitle">Aylık Takvim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div id="calendarGrid" class="d-grid" style="grid-template-columns: repeat(7, 1fr); gap: 5px;">
                    <!-- Takvim günleri buraya JS ile dolacak -->
                </div>
                
                <div class="mt-4 d-flex flex-wrap gap-2 justify-content-center" id="calendarLegend">
                    <!-- Renk açıklamaları -->
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal" style="border-radius: 12px;">Kapat</button>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
      --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    /* Global Premium Modal Styling for Mobile */
    .modal-content {
        border-radius: 24px !important;
        border: none !important;
        overflow: hidden !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.15) !important;
    }
    .modal-header {
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        padding: 1.25rem 1.5rem !important;
    }
    .modal-footer {
        border-top: 1px solid rgba(0,0,0,0.05) !important;
        padding: 1rem 1.5rem !important;
    }
    body[data-bs-theme="dark"] .modal-content {
        background-color: #1a2234 !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4) !important;
    }
    body[data-bs-theme="dark"] .modal-header,
    body[data-bs-theme="dark"] .modal-footer {
        border-color: rgba(255,255,255,0.05) !important;
    }

    .calendar-day {
        aspect-ratio: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid rgba(0,0,0,0.03);
    }
    .calendar-day .day-num {
        font-size: 0.65rem;
        font-weight: 500;
        color: #94a3b8;
        margin-bottom: 2px;
    }
    .calendar-day .day-code {
        font-size: 0.75rem;
        font-weight: 700;
    }
    .calendar-day.empty {
        background: transparent;
        border: none;
    }
    body[data-bs-theme="dark"] .calendar-day {
        background: #1e293b;
        border-color: rgba(255,255,255,0.05);
    }
</style>

<script>
function openCalendarModal(personId, personName) {
    const month = '<?php echo $month; ?>';
    const year = '<?php echo $year; ?>';
    const monthName = '<?php echo $months[$month]; ?>';
    
    document.getElementById('calendarModalTitle').innerText = `${personName} - ${monthName} ${year}`;
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '<div class="text-center py-5 w-100" style="grid-column: span 7;"><div class="spinner-border text-primary" role="status"></div></div>';
    
    const modal = new bootstrap.Modal(document.getElementById('calendarModal'));
    modal.show();

    fetch(`modules/puantaj/api/get-person-monthly-puantaj.php?person_id=${personId}&month=${month}&year=${year}`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                renderCalendar(res.data, res.days_in_month, year, month);
            } else {
                grid.innerHTML = `<div class="alert alert-danger" style="grid-column: span 7;">${res.message}</div>`;
            }
        })
        .catch(err => {
            grid.innerHTML = '<div class="alert alert-danger" style="grid-column: span 7;">Bağlantı hatası oluştu.</div>';
        });
}

function renderCalendar(data, daysInMonth, year, month) {
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';
    
    // Gün başlıkları (Pzt, Sal...)
    const dayNames = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
    dayNames.forEach(name => {
        const h = document.createElement('div');
        h.className = 'text-center text-xs font-weight-bold text-muted pb-2';
        h.innerText = name;
        grid.appendChild(h);
    });

    // İlk günün haftanın hangi günü olduğunu bul
    const firstDay = new Date(year, parseInt(month) - 1, 1).getDay();
    const startOffset = (firstDay === 0 ? 6 : firstDay - 1); // Pazartesi 0, Pazar 6 yapalım

    // Boşluklar
    for (let i = 0; i < startOffset; i++) {
        const empty = document.createElement('div');
        empty.className = 'calendar-day empty';
        grid.appendChild(empty);
    }

    // Günler
    for (let day = 1; day <= daysInMonth; day++) {
        const dayBox = document.createElement('div');
        dayBox.className = 'calendar-day';
        
        const dayNum = document.createElement('span');
        dayNum.className = 'day-num';
        dayNum.innerText = day;
        dayBox.appendChild(dayNum);
        
        const dayCode = document.createElement('span');
        dayCode.className = 'day-code';
        
        if (data[day]) {
            dayCode.innerText = data[day].code;
            dayBox.style.backgroundColor = data[day].bg;
            dayCode.style.color = data[day].color;
            if (data[day].bg !== '#f8fafc' && data[day].bg !== 'transparent') {
                dayNum.style.color = data[day].color;
                dayNum.style.opacity = '0.7';
            }
        } else {
            dayCode.innerText = '-';
            dayCode.style.color = '#94a3b8';
        }
        
        dayBox.appendChild(dayCode);
        grid.appendChild(dayBox);
    }
}
</script>
