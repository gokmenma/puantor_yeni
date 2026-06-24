<?php
// Puantor Mobil - Raporlar Modülü (Premium Mobil Arayüz)
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$personObj = new Persons();
$bordroObj = new Bordro();

$view = $_GET['view'] ?? 'menu';
$year = isset($_GET['year']) ? intval($_GET['year']) : intval($_COOKIE['p_year'] ?? date('Y'));
$project_base = preg_replace('/\/mobile\/index\.php$/i', '', $_SERVER['SCRIPT_NAME']);
$month = isset($_GET['month']) ? intval($_GET['month']) : intval($_COOKIE['p_months'] ?? date('m'));
$firm_id = $_SESSION['firm_id'];

$firstDayStr = Date::firstDay($month, $year);
$lastDayStr = Date::lastDay($month, $year);

// DB compatibility formats
$startDate = date('Y-m-d', strtotime($firstDayStr));
$endDate = date('Y-m-d', strtotime($lastDayStr));

$personList = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);
$personCount = count($personList);
$displayMonth = mb_strtoupper(Date::monthName($month), 'UTF-8');

// Başlık ve Geri Butonu Kontrolü
$back_url = "index.php?route=more";
if ($view == 'puantaj') {
    $title = "Puantaj Raporu";
    $back_url = "index.php?route=reports&year=$year&month=$month";
} elseif ($view == 'banka') {
    $title = "Banka Listesi";
    $back_url = "index.php?route=reports&year=$year&month=$month";
} elseif ($view == 'kesinti') {
    $title = "Kesinti Raporu";
    $back_url = "index.php?route=reports&year=$year&month=$month";
} elseif ($view == 'bordro') {
    $title = "Bordro Listesi";
    $back_url = "index.php?route=reports&year=$year&month=$month";
} else {
    $title = "Raporlar";
}
?>

<div class="container px-2">
  
  <!-- Üst Başlık Alanı -->
  <div class="mb-3 d-flex align-items-center justify-content-between pt-2 px-1">
    <div class="d-flex align-items-center">
      <?php if ($view != 'menu'): ?>
        <a href="<?php echo $back_url; ?>" class="btn btn-icon btn-ghost-secondary me-2 rounded-circle btn-active-scale">
          <i class="ti ti-chevron-left fs-2"></i>
        </a>
      <?php endif; ?>
      <div>
        <h2 class="mb-0 text-bold" style="letter-spacing: -0.8px; font-size: 1.5rem;"><?php echo $title; ?></h2>
        <p class="text-muted text-xs mb-0">
          <?php if ($view == 'menu'): ?>Tüm raporlama ve veri analitiği merkezi.<?php else: ?><?php echo $displayMonth; ?> <?php echo $year; ?> Dönemi<?php endif; ?>
        </p>
      </div>
    </div>
    
    <?php if ($view == 'menu'): ?>
      <div class="d-flex gap-1">
        <!-- Yıl Seçimi -->
        <div class="dropdown">
          <button class="btn btn-sm btn-white border shadow-sm rounded-pill px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <?php echo $year; ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4">
            <?php for($y = date('Y') + 1; $y >= 2022; $y--): ?>
              <li><a class="dropdown-item py-2 <?php echo ($y == $year) ? 'active' : ''; ?>" href="index.php?route=reports&year=<?php echo $y; ?>&month=<?php echo $month; ?>"><?php echo $y; ?></a></li>
            <?php endfor; ?>
          </ul>
        </div>
        
        <!-- Ay Seçimi -->
        <div class="dropdown">
          <button class="btn btn-sm btn-white border shadow-sm rounded-pill px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <?php echo Date::monthName($month); ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4" style="max-height: 250px; overflow-y: auto;">
            <?php for($m = 1; $m <= 12; $m++): ?>
              <li><a class="dropdown-item py-2 <?php echo ($m == $month) ? 'active' : ''; ?>" href="index.php?route=reports&year=<?php echo $year; ?>&month=<?php echo $m; ?>"><?php echo Date::monthName($m); ?></a></li>
            <?php endfor; ?>
          </ul>
        </div>
      </div>
    <?php elseif ($view == 'puantaj'): ?>
      <a href="download.php?type=puantaj&month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-sm btn-white border shadow-sm rounded-pill px-3 btn-active-scale text-success">
        <i class="ti ti-file-spreadsheet me-1"></i> Excel İndir
      </a>
    <?php elseif ($view == 'banka'): ?>
      <a href="download.php?type=banka&month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-sm btn-white border shadow-sm rounded-pill px-3 btn-active-scale text-success">
        <i class="ti ti-file-spreadsheet me-1"></i> Excel İndir
      </a>
    <?php elseif ($view == 'kesinti'): ?>
      <a href="download.php?type=kesinti&month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-sm btn-white border shadow-sm rounded-pill px-3 btn-active-scale text-success">
        <i class="ti ti-file-spreadsheet me-1"></i> Excel İndir
      </a>
    <?php elseif ($view == 'bordro'): ?>
      <?php
      $all_ids_arr = [];
      foreach ($personList as $p_item) {
          $all_ids_arr[] = Security::encrypt($p_item->id);
      }
      $all_ids_str = implode(',', $all_ids_arr);
      ?>
      <a href="<?php echo $project_base; ?>/index.php?p=raporlar/bordro-yazdir&ids=<?php echo urlencode($all_ids_str); ?>&month=<?php echo Security::encrypt($month); ?>&year=<?php echo Security::encrypt($year); ?>" target="_blank" class="btn btn-sm btn-white border shadow-sm rounded-pill px-3 btn-active-scale">
        <i class="ti ti-printer me-1 text-primary"></i> Tümünü Yazdır
      </a>
    <?php endif; ?>
  </div>

  <?php if ($view == 'menu'): ?>
    <!-- --- MENÜ GÖRÜNÜMÜ (RAPOR SEÇİMİ) --- -->
    <div class="mobile-card bg-primary text-white p-4 mb-4 position-relative overflow-hidden" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;">
      <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.1; pointer-events: none;">
        <i class="ti ti-chart-pie"></i>
      </div>
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem;">Seçili Dönem Özeti</span>
        <i class="ti ti-calendar" style="font-size: 1.3rem; opacity: 0.8;"></i>
      </div>
      <h3 class="mb-0 text-bold" style="font-size: 1.8rem; letter-spacing: -0.5px;"><?php echo $displayMonth; ?> <?php echo $year; ?></h3>
      
      <div class="row g-2 mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1) !important;">
        <div class="col-6">
          <div class="text-white-50 text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.6rem; opacity: 0.85;">Toplam Personel</div>
          <div class="h4 mb-0 text-bold text-white"><?php echo $personCount; ?> Kişi</div>
        </div>
        <div class="col-6 ps-3" style="border-left: 1px solid rgba(255,255,255,0.1) !important;">
          <div class="text-white-50 text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.6rem; opacity: 0.85;">Dönem Aralığı</div>
          <div class="h5 mb-0 text-bold text-white-50" style="font-size: 0.75rem;"><?php echo date('d.m', strtotime($startDate)); ?> - <?php echo date('d.m.Y', strtotime($endDate)); ?></div>
        </div>
      </div>
    </div>

    <!-- Rapor Listesi -->
    <h4 class="mb-3 text-semibold px-1" style="font-size: 0.95rem;">Rapor Çeşitleri</h4>
    <div class="row g-3 mb-4">
      
      <!-- Puantaj İcmali -->
      <div class="col-12">
        <a href="index.php?route=reports&view=puantaj&year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="mobile-card d-flex align-items-center gap-3 p-3 text-decoration-none text-reset btn-active-scale">
          <div class="avatar avatar-md rounded bg-purple-lt">
            <i class="ti ti-clock-check text-purple fs-2"></i>
          </div>
          <div class="flex-1">
            <h3 class="mb-0 text-semibold" style="font-size: 0.95rem;">Puantaj İcmal Raporu</h3>
            <p class="text-muted text-xxs mb-0">Çalışma, izin, devamsızlık ve fazla mesai toplamları.</p>
          </div>
          <i class="ti ti-chevron-right text-muted" style="opacity: 0.5;"></i>
        </a>
      </div>

      <!-- Banka Ödeme Listesi -->
      <div class="col-12">
        <a href="index.php?route=reports&view=banka&year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="mobile-card d-flex align-items-center gap-3 p-3 text-decoration-none text-reset btn-active-scale">
          <div class="avatar avatar-md rounded bg-info-lt">
            <i class="ti ti-building-bank text-info fs-2"></i>
          </div>
          <div class="flex-1">
            <h3 class="mb-0 text-semibold" style="font-size: 0.95rem;">Banka Ödeme Listesi</h3>
            <p class="text-muted text-xxs mb-0">Hakediş sonrası banka IBAN transfer miktarları.</p>
          </div>
          <i class="ti ti-chevron-right text-muted" style="opacity: 0.5;"></i>
        </a>
      </div>

      <!-- Kesinti Raporu -->
      <div class="col-12">
        <a href="index.php?route=reports&view=kesinti&year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="mobile-card d-flex align-items-center gap-3 p-3 text-decoration-none text-reset btn-active-scale">
          <div class="avatar avatar-md rounded bg-danger-lt">
            <i class="ti ti-scissors text-danger fs-2"></i>
          </div>
          <div class="flex-1">
            <h3 class="mb-0 text-semibold" style="font-size: 0.95rem;">Kesinti Detay Raporu</h3>
            <p class="text-muted text-xxs mb-0">Avans, icra ve diğer maaş kesintilerinin listesi.</p>
          </div>
          <i class="ti ti-chevron-right text-muted" style="opacity: 0.5;"></i>
        </a>
      </div>

      <!-- Bordro Listesi / Pay Slip -->
      <div class="col-12">
        <a href="index.php?route=reports&view=bordro&year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="mobile-card d-flex align-items-center gap-3 p-3 text-decoration-none text-reset btn-active-scale">
          <div class="avatar avatar-md rounded bg-teal-lt">
            <i class="ti ti-file-invoice text-teal fs-2"></i>
          </div>
          <div class="flex-1">
            <h3 class="mb-0 text-semibold" style="font-size: 0.95rem;">Bordrolar / Paylaşımlı</h3>
            <p class="text-muted text-xxs mb-0">Personel hesap bordro çıktıları ve yazdırma.</p>
          </div>
          <i class="ti ti-chevron-right text-muted" style="opacity: 0.5;"></i>
        </a>
      </div>

    </div>

  <?php elseif ($view == 'puantaj'): ?>
    <!-- --- PUANTAJ RAPORU GÖRÜNÜMÜ --- -->
    <?php
    $start_dash = $startDate;
    $end_dash = $endDate;
    $start_nodash = str_replace('-', '', $startDate);
    $end_nodash = str_replace('-', '', $endDate);

    $db = $personObj->connect();
    $queryStr = "
    SELECT 
        p.id, 
        p.full_name, 
        p.job,
        p.ekip as team_name,
        pr.project_name,
        SUM(CASE WHEN pt.Turu = 'Normal Çalışma' THEN 1 ELSE 0 END) as n_calisma,
        SUM(CASE WHEN pt.Turu = 'Saatlik' THEN pua.saat ELSE 0 END) as s_calisma,
        SUM(CASE WHEN pt.Turu = 'Fazla Çalışma' THEN pua.saat ELSE 0 END) as f_mesai,
        SUM(CASE WHEN pt.Turu = 'Ücretli İzin' THEN 1 ELSE 0 END) as u_izin,
        SUM(CASE WHEN pt.PuantajKod = 'Uİ' THEN 1 ELSE 0 END) as ucr_izin,
        SUM(CASE WHEN pt.PuantajKod = 'DVZ' THEN 1 ELSE 0 END) as dvz,
        SUM(CASE WHEN pt.PuantajKod IN ('R', 'R-', 'R+') THEN 1 ELSE 0 END) as rapor
    FROM persons p
    LEFT JOIN projects pr ON p.project_id = pr.id
    LEFT JOIN puantaj pua ON p.id = pua.person AND ((pua.gun >= ? AND pua.gun <= ?) OR (pua.gun >= ? AND pua.gun <= ?))
    LEFT JOIN puantajturu pt ON pua.puantaj_id = pt.id
    WHERE p.firm_id = ? AND p.deleted_at IS NULL
    GROUP BY p.id
    ORDER BY p.full_name ASC
    ";
    $stmt = $db->prepare($queryStr);
    $stmt->execute([$start_dash, $end_dash, $start_nodash, $end_nodash, $firm_id]);
    $raporData = $stmt->fetchAll(PDO::FETCH_OBJ);
    ?>

    <!-- Arama Çubuğu -->
    <div class="search-container mb-3 px-1">
      <i class="ti ti-search search-icon"></i>
      <input type="text" id="puantaj-search" class="search-input shadow-sm" placeholder="Personel veya Proje ara...">
    </div>

    <!-- Rapor Listesi -->
    <div class="row g-2 mb-5" id="puantaj-list">
      <?php if (empty($raporData)): ?>
        <div class="col-12 text-center py-4 text-muted">
          Bu dönem için puantaj kaydı bulunamadı.
        </div>
      <?php else: ?>
        <?php foreach($raporData as $r): ?>
          <div class="col-12 puantaj-card" data-name="<?php echo strtolower($r->full_name . ' ' . ($r->project_name ?? '')); ?>">
            <div class="mobile-card p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar avatar-xs rounded-circle bg-purple-lt text-purple font-weight-bold">
                    <?php echo Helper::getInitials($r->full_name); ?>
                  </div>
                  <div>
                    <h3 class="mb-0 text-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($r->full_name); ?></h3>
                    <p class="text-muted text-xxs mb-0"><?php echo htmlspecialchars($r->project_name ?? 'Proje Yok'); ?> • <?php echo htmlspecialchars($r->job ?? 'Ünvansız'); ?></p>
                  </div>
                </div>
                <span class="badge bg-purple-lt" style="font-size: 0.65rem; padding: 3px 8px;">
                  <?php echo (float)$r->n_calisma; ?> Gün Çal.
                </span>
              </div>
              
              <div class="row g-1 text-center mt-2 pt-2 border-top border-light">
                <div class="col-3">
                  <div class="text-muted text-xxs text-uppercase" style="font-size: 0.55rem;">F. MESAI</div>
                  <div class="text-bold text-xs <?php echo $r->f_mesai > 0 ? 'text-danger' : 'text-dark'; ?>"><?php echo (float)$r->f_mesai ?: '-'; ?> <span style="font-size:0.6rem">Sa</span></div>
                </div>
                <div class="col-3 border-start border-light">
                  <div class="text-muted text-xxs text-uppercase" style="font-size: 0.55rem;">SAATLIK</div>
                  <div class="text-bold text-xs text-dark"><?php echo (float)$r->s_calisma ?: '-'; ?> <span style="font-size:0.6rem">Sa</span></div>
                </div>
                <div class="col-3 border-start border-light">
                  <div class="text-muted text-xxs text-uppercase" style="font-size: 0.55rem;">İZİN (Ü/ÜS)</div>
                  <div class="text-bold text-xs text-dark"><?php echo (float)$r->u_izin ?: '0'; ?>/<?php echo (float)$r->ucr_izin ?: '0'; ?></div>
                </div>
                <div class="col-3 border-start border-light">
                  <div class="text-muted text-xxs text-uppercase" style="font-size: 0.55rem;">RAP/DEV</div>
                  <div class="text-bold text-xs text-dark"><?php echo (float)$r->rapor ?: '0'; ?>/<?php echo (float)$r->dvz ?: '0'; ?></div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <script>
      $(document).ready(function() {
        $('#puantaj-search').on('keyup', function() {
          var value = $(this).val().toLowerCase();
          $('#puantaj-list .puantaj-card').filter(function() {
            $(this).toggle($(this).data('name').indexOf(value) > -1)
          });
        });
      });
    </script>

  <?php elseif ($view == 'banka'): ?>
    <!-- --- BANKA RAPORU GÖRÜNÜMÜ --- -->
    <?php
    $personsForBank = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);
    $bankData = [];
    $totalBankAmount = 0;
    foreach($personsForBank as $p_item) {
        $p = $personObj->find($p_item->id);
        $res = $bordroObj->getPersonSalaryAndWageCut($p->id, $firstDayStr, $lastDayStr);
        $netPay = ($res->gelir ?? 0) - ($res->odeme ?? 0);
        
        if($netPay > 0) {
            $bankData[] = (object)[
                'id' => $p->id,
                'full_name' => $p->full_name,
                'kimlik_no' => Security::safeDecrypt($p->kimlik_no ?? ''),
                'iban_number' => Security::safeDecrypt($p->iban_number ?? ''),
                'amount' => $netPay
            ];
            $totalBankAmount += $netPay;
        }
    }
    ?>

    <!-- Toplam Kartı -->
    <div class="mobile-card bg-success text-white p-3 mb-3 border-0 shadow-sm" style="border-radius: 16px; background: linear-gradient(135deg, #2fb344 0%, #1e7e2c 100%) !important;">
      <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.9;">TOPLAM BANKA ÖDEMESİ</div>
      <div class="text-bold h2 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($totalBankAmount); ?></div>
      <div class="text-xxs mt-1 opacity-75"><?php echo count($bankData); ?> Personel için toplam ödeme.</div>
    </div>

    <!-- Arama Çubuğu -->
    <div class="search-container mb-3 px-1">
      <i class="ti ti-search search-icon"></i>
      <input type="text" id="banka-search" class="search-input shadow-sm" placeholder="Personel ara...">
    </div>

    <!-- Banka Ödeme Kartları -->
    <div class="row g-2 mb-5" id="banka-list">
      <?php if (empty($bankData)): ?>
        <div class="col-12 text-center py-4 text-muted">
          Bu dönem için banka ödeme verisi bulunamadı.
        </div>
      <?php else: ?>
        <?php foreach($bankData as $b): ?>
          <div class="col-12 banka-card" data-name="<?php echo strtolower($b->full_name); ?>">
            <div class="mobile-card p-3">
              <div class="d-flex align-items-start justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar avatar-xs rounded-circle bg-info-lt text-info font-weight-bold">
                    <?php echo Helper::getInitials($b->full_name); ?>
                  </div>
                  <div>
                    <h3 class="mb-0 text-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($b->full_name); ?></h3>
                    <p class="text-muted text-xxs mb-0">TC: <?php echo htmlspecialchars($b->kimlik_no ?: '-'); ?></p>
                  </div>
                </div>
                <div class="text-end">
                  <div class="text-bold text-sm text-green">₺ <?php echo Helper::formattedMoneyWithoutCurrency($b->amount); ?></div>
                  <span class="text-muted text-xxs font-weight-bold">NET MAAŞ</span>
                </div>
              </div>
              
              <?php if (!empty($b->iban_number)): ?>
                <div class="mt-3 p-2 bg-light rounded-3 d-flex align-items-center justify-content-between" style="border: 1px solid #e2e8f0;">
                  <code class="text-dark font-weight-bold text-xs" style="word-break: break-all;"><?php echo htmlspecialchars($b->iban_number); ?></code>
                  <button class="btn btn-icon btn-sm btn-ghost-primary rounded-circle ms-2 copy-iban-btn" data-iban="<?php echo htmlspecialchars($b->iban_number); ?>" title="Kopyala">
                    <i class="ti ti-copy fs-3"></i>
                  </button>
                </div>
              <?php else: ?>
                <div class="mt-3 p-2 text-center bg-light rounded-3 text-muted text-xxs" style="border: 1px dashed #cbd5e1;">
                  IBAN Tanımlanmamış
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- IBAN Copy Functionality -->
    <script>
      $(document).ready(function() {
        $('#banka-search').on('keyup', function() {
          var value = $(this).val().toLowerCase();
          $('#banka-list .banka-card').filter(function() {
            $(this).toggle($(this).data('name').indexOf(value) > -1)
          });
        });

        $('.copy-iban-btn').on('click', function(e) {
          e.preventDefault();
          var iban = $(this).data('iban');
          var $btn = $(this);
          
          navigator.clipboard.writeText(iban).then(function() {
            // Success indicator
            $btn.html('<i class="ti ti-check text-success fs-3"></i>');
            setTimeout(function() {
              $btn.html('<i class="ti ti-copy fs-3"></i>');
            }, 1500);
          }, function() {
            // Fallback method
            var tempInput = document.createElement("input");
            tempInput.value = iban;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand("copy");
            document.body.removeChild(tempInput);
            
            $btn.html('<i class="ti ti-check text-success fs-3"></i>');
            setTimeout(function() {
              $btn.html('<i class="ti ti-copy fs-3"></i>');
            }, 1500);
          });
        });
      });
    </script>

  <?php elseif ($view == 'kesinti'): ?>
    <!-- --- KESİNTİ RAPORU GÖRÜNÜMÜ --- -->
    <?php
    require_once ROOT . '/Model/DefinesModel.php';
    $definesObj = new DefinesModel();
    $db = $personObj->connect();
    $kesinti_ids = $definesObj->getExpenseTypes(2); // Get deduction IDs
    
    $queryStr = "
    SELECT 
        p.full_name,
        mgk.turu,
        mgk.tutar,
        mgk.gun,
        mgk.aciklama,
        dt.name as kategori_adi
    FROM maas_gelir_kesinti mgk
    JOIN persons p ON mgk.person_id = p.id
    LEFT JOIN defines dt ON mgk.kategori = dt.id
    WHERE p.firm_id = ? 
      AND mgk.kategori IN ($kesinti_ids)
      AND CAST(REPLACE(mgk.gun, '-', '') AS UNSIGNED) >= ? 
      AND CAST(REPLACE(mgk.gun, '-', '') AS UNSIGNED) <= ?
    ORDER BY mgk.gun DESC
    ";
    $stmt = $db->prepare($queryStr);
    $stmt->execute([$firm_id, $firstDayStr, $lastDayStr]);
    $kesintiData = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    $totalKesintiAmount = 0;
    foreach($kesintiData as $k) {
        $totalKesintiAmount += $k->tutar;
    }
    ?>

    <!-- Toplam Kartı -->
    <div class="mobile-card bg-danger text-white p-3 mb-3 border-0 shadow-sm" style="border-radius: 16px; background: linear-gradient(135deg, #d63f3f 0%, #a82d2d 100%) !important;">
      <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.9;">TOPLAM DÖNEM KESİNTİSİ</div>
      <div class="text-bold h2 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($totalKesintiAmount); ?></div>
      <div class="text-xxs mt-1 opacity-75"><?php echo count($kesintiData); ?> adet kesinti hareketi.</div>
    </div>

    <!-- Arama Çubuğu -->
    <div class="search-container mb-3 px-1">
      <i class="ti ti-search search-icon"></i>
      <input type="text" id="kesinti-search" class="search-input shadow-sm" placeholder="Personel veya Kategori ara...">
    </div>

    <!-- Kesinti Kartları -->
    <div class="row g-2 mb-5" id="kesinti-list">
      <?php if (empty($kesintiData)): ?>
        <div class="col-12 text-center py-4 text-muted">
          Bu dönem için kesinti kaydı bulunamadı.
        </div>
      <?php else: ?>
        <?php foreach($kesintiData as $k): ?>
          <div class="col-12 kesinti-card" data-name="<?php echo strtolower($k->full_name . ' ' . ($k->kategori_adi ?? '')); ?>">
            <div class="mobile-card p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar avatar-xs rounded-circle bg-danger-lt text-danger font-weight-bold">
                    <?php echo Helper::getInitials($k->full_name); ?>
                  </div>
                  <div>
                    <h3 class="mb-0 text-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($k->full_name); ?></h3>
                    <p class="text-muted text-xxs mb-0"><?php echo date('d.m.Y', strtotime($k->gun)); ?> • <span class="badge bg-light text-dark px-1 py-0.5 rounded"><?php echo htmlspecialchars($k->kategori_adi ?? 'Diğer'); ?></span></p>
                  </div>
                </div>
                <div class="text-end">
                  <div class="text-bold text-sm text-red">- ₺ <?php echo Helper::formattedMoneyWithoutCurrency($k->tutar); ?></div>
                  <span class="text-muted text-xxs font-weight-bold"><?php echo htmlspecialchars($k->turu); ?></span>
                </div>
              </div>
              <?php if (!empty($k->aciklama) && $k->aciklama != '0'): ?>
                <div class="mt-2 text-muted text-xxs border-top border-light pt-2" style="font-style: italic;">
                  Açıklama: <?php echo htmlspecialchars($k->aciklama); ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <script>
      $(document).ready(function() {
        $('#kesinti-search').on('keyup', function() {
          var value = $(this).val().toLowerCase();
          $('#kesinti-list .kesinti-card').filter(function() {
            $(this).toggle($(this).data('name').indexOf(value) > -1)
          });
        });
      });
    </script>

  <?php elseif ($view == 'bordro'): ?>
    <!-- --- BORDRO / PAY SLIP LIST GÖRÜNÜMÜ --- -->
    <?php
    $personsForBordro = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);
    ?>

    <!-- Arama Çubuğu -->
    <div class="search-container mb-3 px-1">
      <i class="ti ti-search search-icon"></i>
      <input type="text" id="bordro-search" class="search-input shadow-sm" placeholder="Personel ara...">
    </div>

    <!-- Bordro Kartları -->
    <div class="row g-2 mb-5" id="bordro-list">
      <?php if (empty($personsForBordro)): ?>
        <div class="col-12 text-center py-4 text-muted">
          Bu dönem için personel bulunamadı.
        </div>
      <?php else: ?>
        <?php foreach($personsForBordro as $p_item): 
          $p = $personObj->find($p_item->id);
        ?>
          <div class="col-12 bordro-card" data-name="<?php echo strtolower($p->full_name); ?>">
            <div class="mobile-card p-3 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-xs rounded-circle bg-teal-lt text-teal font-weight-bold">
                  <?php echo Helper::getInitials($p->full_name); ?>
                </div>
                <div>
                  <h3 class="mb-0 text-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($p->full_name); ?></h3>
                  <p class="text-muted text-xxs mb-0"><?php echo htmlspecialchars($p->job ?? 'Ünvansız'); ?></p>
                </div>
              </div>
              
              <!-- PDF / Görüntüle Butonu -->
              <a href="<?php echo $project_base; ?>/index.php?p=payroll/pay-slip&id=<?php echo Security::encrypt($p->id); ?>&month=<?php echo Security::encrypt($month); ?>&year=<?php echo Security::encrypt($year); ?>" 
                 target="_blank" class="btn btn-sm btn-white border shadow-sm rounded-pill px-3 btn-active-scale">
                <i class="ti ti-printer me-1 text-primary"></i> Bordro
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <script>
      $(document).ready(function() {
        $('#bordro-search').on('keyup', function() {
          var value = $(this).val().toLowerCase();
          $('#bordro-list .bordro-card').filter(function() {
            $(this).toggle($(this).data('name').indexOf(value) > -1)
          });
        });
      });
    </script>

  <?php endif; ?>

</div>

<style>
.text-bold { font-weight: 700 !important; }
.text-semibold { font-weight: 600 !important; }
.avatar-xs { width: 34px !important; height: 34px !important; font-size: 0.75rem !important; }
.avatar-md { width: 44px !important; height: 44px !important; }
.text-green { color: #2fb344 !important; }
.text-red { color: #d63f3f !important; }
.bg-purple-lt { background-color: rgba(126, 34, 206, 0.08) !important; color: rgb(126, 34, 206) !important; }
.bg-info-lt { background-color: rgba(3, 105, 161, 0.08) !important; color: rgb(3, 105, 161) !important; }
.bg-danger-lt { background-color: rgba(214, 63, 63, 0.08) !important; color: rgb(214, 63, 63) !important; }
.bg-teal-lt { background-color: rgba(15, 118, 110, 0.08) !important; color: rgb(15, 118, 110) !important; }
</style>
