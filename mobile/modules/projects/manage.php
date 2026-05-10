<?php
// Puantor Mobil - Proje Detay & Güncelleme Sayfası
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/Model/ProjectIncomeExpense.php";
require_once ROOT . "/Model/Puantaj.php";
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/App/Helper/company.php";
require_once ROOT . "/App/Helper/cities.php";
require_once ROOT . "/App/Helper/projects.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;

$projectsModel = new Projects();
$incexpModel = new ProjectIncomeExpense();
$puantajModel = new Puantaj();
$personsModel = new Persons();

$companyHelper = new CompanyHelper();
$cityHelper = new Cities();
$projectHelper = new ProjectHelper();

$firm_id = $_SESSION['firm_id'] ?? 0;

$id_encrypted = $_GET['id'] ?? '';
$id = $id_encrypted ? Security::decrypt($id_encrypted) : 0;

$project = $id > 0 ? $projectsModel->find($id) : null;
$type = $project->type ?? 1; // 1: Alınan, 2: Verilen
$pageTitle = $id > 0 ? "Proje Detay / Güncelle" : "Yeni Proje";

// Hakediş / Ödeme Verileri
$income_expenses = $id > 0 ? $incexpModel->getAllIncomeExpenseByProject($id) : [];
$summary = $id > 0 ? $incexpModel->sumAllIncomeExpense($id) : (object)['hakedis' => 0, 'gelir' => 0, 'kesinti' => 0, 'odeme' => 0];
$hakedis = $summary->hakedis ?? 0;
$total_income = $summary->gelir ?? 0;
$total_expense = $summary->kesinti ?? 0;
$total_payment = $summary->odeme ?? 0;
$balance = $hakedis - $total_income - $total_expense - $total_payment;

// Çalışma / Puantaj Verileri
$puantaj_info = $id > 0 ? $puantajModel->getPuantajInfoByProject($id) : [];
$total_person = $id > 0 ? $puantajModel->getTotalWorksPersonByProject($id) : 0;
$total_hours = $id > 0 ? $puantajModel->getTotalWorksHourByProject($id) : 0;
$total_amount = $id > 0 ? $puantajModel->getTotalWorksBalanceByProject($id) : 0;

$project_persons = $id > 0 ? $projectsModel->getPersontoProject($firm_id, $id) : [];

$budget = $project->budget ?? 0;
if ($hakedis > $budget) {
    $range = 100;
} else {
    $range = ($hakedis != 0) ? number_format(($hakedis / ($budget ?? 1)) * 100, 0) : 0;
}

// Gelişmiş İş Zekası (BI) Maliyet ve Kâr Hesaplamaları
$labor_cost = $total_amount;
$total_cost = $labor_cost + $total_expense + $total_payment;
$net_profit = $hakedis - $total_cost;
$profit_margin = ($hakedis > 0) ? round(($net_profit / $hakedis) * 100, 1) : 0;
$cost_ratio = ($hakedis > 0) ? round(($total_cost / $hakedis) * 100, 1) : 0;

$budget_utilization = ($budget > 0) ? round(($hakedis / $budget) * 100, 1) : 0;
$labor_cost_ratio = ($total_cost > 0) ? round(($labor_cost / $total_cost) * 100, 1) : 0;
$other_expense_ratio = ($total_cost > 0) ? round((($total_expense + $total_payment) / $total_cost) * 100, 1) : 0;
$collection_rate = ($hakedis > 0) ? round(($total_income / $hakedis) * 100, 1) : 0;

// Zaman Serisi Grafik Verisi Hazırlama
$monthly_data = [];
if (is_array($income_expenses)) {
    foreach ($income_expenses as $tx) {
        $year = $tx->yil ?? date('Y');
        $month = str_pad($tx->ay ?? date('m'), 2, '0', STR_PAD_LEFT);
        $key = "$year-$month";
        
        if (!isset($monthly_data[$key])) {
            $monthly_data[$key] = ['hakedis' => 0, 'gelir' => 0, 'gider' => 0, 'isclik' => 0];
        }
        
        if (($tx->turu ?? 0) == 10) {
            $monthly_data[$key]['hakedis'] += floatval($tx->tutar ?? 0);
        } elseif (($tx->turu ?? 0) == 5) {
            $monthly_data[$key]['gelir'] += floatval($tx->tutar ?? 0);
        } elseif (in_array(($tx->turu ?? 0), [11, 12, 14])) {
            $monthly_data[$key]['gider'] += floatval($tx->tutar ?? 0);
        } elseif (($tx->turu ?? 0) == 6) {
            $monthly_data[$key]['gider'] += floatval($tx->tutar ?? 0);
        }
    }
}

if (is_array($puantaj_info)) {
    foreach ($puantaj_info as $p) {
        if (!empty($p->gun)) {
            $parts = explode('-', $p->gun);
            if (count($parts) >= 2) {
                $key = $parts[0] . '-' . $parts[1]; // YYYY-MM
                if (!isset($monthly_data[$key])) {
                    $monthly_data[$key] = ['hakedis' => 0, 'gelir' => 0, 'gider' => 0, 'isclik' => 0];
                }
                $monthly_data[$key]['isclik'] += floatval($p->tutar ?? 0);
            }
        }
    }
}

ksort($monthly_data);

$timeline_categories = [];
$timeline_hakedis = [];
$timeline_gelir = [];
$timeline_maliyet = [];
$timeline_profit = [];

$turkish_months = [
    '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
    '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
    '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
];

foreach ($monthly_data as $key => $data) {
    list($y, $m) = explode('-', $key);
    $month_name = isset($turkish_months[$m]) ? $turkish_months[$m] : $m;
    $timeline_categories[] = "$month_name $y";
    
    $timeline_hakedis[] = $data['hakedis'];
    $timeline_gelir[] = $data['gelir'];
    
    $maliyet = $data['gider'] + $data['isclik'];
    $timeline_maliyet[] = $maliyet;
    $timeline_profit[] = $data['hakedis'] - $maliyet;
}

if (empty($timeline_categories)) {
    $timeline_categories = ['Mevcut Ay'];
    $timeline_hakedis = [floatval($hakedis)];
    $timeline_gelir = [floatval($total_income)];
    $timeline_maliyet = [floatval($total_cost)];
    $timeline_profit = [floatval($net_profit)];
}

$categories_json = json_encode($timeline_categories);
$hakedis_json = json_encode($timeline_hakedis);
$income_json = json_encode($timeline_gelir);
$maliyet_json = json_encode($timeline_maliyet);
$profit_json = json_encode($timeline_profit);
?>

<style>
:root {
    --project-card-bg: #ffffff;
    --project-card-border: rgba(0, 0, 0, 0.08);
    --project-text-main: #1d273b;
    --project-text-muted: #64748b;
}

body[data-bs-theme="dark"] {
    --project-card-bg: #1e293b;
    --project-card-border: rgba(255, 255, 255, 0.1);
    --project-text-main: #f4f6fa;
    --project-text-muted: #94a3b8;
}

.tab-trigger.active {
    background-color: rgba(32, 107, 196, 0.1) !important;
    color: var(--mobile-primary) !important;
}

body[data-bs-theme="dark"] .text-dark {
    color: #f4f6fa !important;
}
</style>

<div class="container px-0 pb-5">
  
  <!-- Üst Başlık & Dropdown Menü -->
  <div class="d-flex align-items-center justify-content-between mb-4 px-2">
    <div class="d-flex align-items-center gap-3">
      <a href="projects" class="btn btn-icon btn-sm btn-outline-secondary border-0 bg-secondary-lt rounded-circle">
        <i class="ti ti-chevron-left" style="font-size: 1.2rem;"></i>
      </a>
      <div>
        <h2 class="mb-0 text-semibold" id="page-title" style="letter-spacing: -0.5px; line-height: 1.1; font-size: 1.25rem;"><?php echo $pageTitle; ?></h2>
        <span class="text-muted text-xs font-weight-bold text-uppercase" style="letter-spacing: 0.5px; opacity: 0.8;"><?php echo htmlspecialchars($project->project_name ?? 'Yeni Kayıt'); ?></span>
      </div>
    </div>

  </div>

  <!-- 4 Sekmeli Yüzen Menü (FAB) -->
  <div class="dropdown dropup" style="position: fixed; right: 1.25rem; bottom: calc(var(--app-nav-height) + 1.25rem); z-index: 1020;">
    <button class="btn btn-primary rounded-circle shadow-lg btn-active-scale" type="button" id="projectTabsDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border: none;">
      <i class="ti ti-layers-intersect fs-1"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 mb-3" aria-labelledby="projectTabsDropdown" style="border-radius: 16px; min-width: 240px; z-index: 2000;">
      <?php if ($id > 0): ?>
      <li>
        <a class="dropdown-item active rounded-3 py-2 text-semibold mb-1 tab-trigger" href="#" data-tab="summary" data-title="Proje Özet Bilgileri">
          <i class="ti ti-chart-dots me-2"></i> Proje Özet Bilgileri
        </a>
      </li>
      <li>
        <a class="dropdown-item rounded-3 py-2 text-semibold mb-1 tab-trigger" href="#" data-tab="payments" data-title="Hakediş & Ödemeler">
          <i class="ti ti-cash-register me-2"></i> Hakediş & Ödemeler
        </a>
      </li>
      <li>
        <a class="dropdown-item rounded-3 py-2 text-semibold mb-1 tab-trigger" href="#" data-tab="puantaj" data-title="Çalışma & Puantaj">
          <i class="ti ti-calendar-month me-2"></i> Çalışma & Puantaj
        </a>
      </li>
      <li>
        <a class="dropdown-item rounded-3 py-2 text-semibold tab-trigger" href="#" data-tab="persons" data-title="Proje Personelleri">
          <i class="ti ti-users me-2"></i> Proje Personelleri
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </div>

  <!-- TAB SİSTEMİ İÇERİĞİ -->
  <?php if ($id > 0): ?>
  
  <!-- TAB: Proje Özet (VARSAYILAN) -->
  <div id="tab-summary" class="project-tab-content px-2">
    <!-- 4 Adet Görsel KPI Kartı -->
    <div class="row g-2 mb-3">
      <!-- Kart 1: Sözleşme Bütçesi -->
      <div class="col-6">
        <div class="mobile-card p-3 border-0 shadow-sm d-flex flex-column h-100" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); color: #0369a1; border-radius: 16px;">
          <span class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem;">SÖZLEŞME BÜTÇESİ</span>
          <span class="text-bold h4 mb-1 text-dark" style="font-size: 1.15rem;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($budget); ?></span>
          <span class="text-muted text-xs" style="font-size: 0.68rem; line-height: 1.2;">Hakediş: <strong>₺<?php echo Helper::formattedMoneyWithoutCurrency($hakedis); ?></strong></span>
        </div>
      </div>
      
      <!-- Kart 2: Toplam Maliyet -->
      <div class="col-6">
        <div class="mobile-card p-3 border-0 shadow-sm d-flex flex-column h-100" style="background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%); color: #c92a2a; border-radius: 16px;">
          <span class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem;">TOPLAM MALİYET</span>
          <span class="text-bold h4 mb-1 text-dark" style="font-size: 1.15rem;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_cost); ?></span>
          <span class="text-muted text-xs" style="font-size: 0.68rem; line-height: 1.2;">İşçilik: <strong>₺<?php echo Helper::formattedMoneyWithoutCurrency($labor_cost); ?></strong></span>
        </div>
      </div>
    </div>

    <div class="row g-2 mb-4">
      <!-- Kart 3: Net Proje Kârı -->
      <?php 
      $is_profit = $net_profit >= 0;
      $profit_bg = $is_profit ? 'linear-gradient(135deg, #f4fbf7 0%, #e6f7ed 100%)' : 'linear-gradient(135deg, #fff9db 0%, #fff3bf 100%)';
      $profit_color = $is_profit ? '#2b8a3e' : '#e67700';
      ?>
      <div class="col-6">
        <div class="mobile-card p-3 border-0 shadow-sm d-flex flex-column h-100" style="background: <?php echo $profit_bg; ?>; color: <?php echo $profit_color; ?>; border-radius: 16px;">
          <span class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem;">NET PROJE KÂRI</span>
          <span class="text-bold h4 mb-1 text-dark" style="font-size: 1.15rem;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($net_profit); ?></span>
          <span class="text-muted text-xs" style="font-size: 0.68rem; line-height: 1.2; color: <?php echo $profit_color; ?>;">Marj: <strong>%<?php echo $profit_margin; ?></strong></span>
        </div>
      </div>

      <!-- Kart 4: Toplam Tahsilat -->
      <div class="col-6">
        <div class="mobile-card p-3 border-0 shadow-sm d-flex flex-column h-100" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); color: #b45309; border-radius: 16px;">
          <span class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem;">TOPLAM TAHSİLAT</span>
          <span class="text-bold h4 mb-1 text-dark" style="font-size: 1.15rem;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_income); ?></span>
          <span class="text-muted text-xs" style="font-size: 0.68rem; line-height: 1.2;">Alacak: <strong>₺<?php echo Helper::formattedMoneyWithoutCurrency($hakedis - $total_income); ?></strong></span>
        </div>
      </div>
    </div>

    <!-- Grafik Sekmeleri ve Gösterimleri -->
    <div class="mobile-card p-3 shadow-sm mb-4" style="border-radius: 16px;">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-0">PERFORMANS GRAFİKLERİ</label>
        
        <div class="d-flex gap-1 bg-secondary-lt p-1" style="border-radius: 8px;">
          <button type="button" class="btn btn-xs py-1 px-2 border-0 shadow-none btn-active-scale chart-toggle active" data-chart="timeline" style="font-size: 0.7rem; font-weight: 600; border-radius: 6px;">Zaman</button>
          <button type="button" class="btn btn-xs py-1 px-2 border-0 shadow-none btn-active-scale chart-toggle" data-chart="breakdown" style="font-size: 0.7rem; font-weight: 600; border-radius: 6px;">Dağılım</button>
        </div>
      </div>

      <div id="mobile-chart-timeline-container">
        <div id="mobile_timeline_chart" style="min-height: 240px; width: 100%;"></div>
      </div>
      <div id="mobile-chart-breakdown-container" class="d-none">
        <div id="mobile_cost_breakdown_chart" style="min-height: 240px; width: 100%;"></div>
      </div>
    </div>

    <!-- İş Zekası (BI) Metrikleri -->
    <div class="mobile-card p-3 shadow-sm mb-4" style="border-radius: 16px;">
      <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-3">ANALİTİK DURUM METRİKLERİ</label>
      
      <!-- Metrik 1 -->
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="text-sm font-weight-medium text-dark">Bütçe Gerçekleşme Oranı</span>
          <span class="badge bg-blue-lt font-weight-bold" style="font-size: 0.7rem;">%<?php echo $budget_utilization; ?></span>
        </div>
        <div class="progress" style="height: 5px; border-radius: 10px;">
          <div class="progress-bar bg-blue" style="width: <?php echo $budget_utilization > 100 ? 100 : $budget_utilization; ?>%"></div>
        </div>
      </div>

      <!-- Metrik 2 -->
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="text-sm font-weight-medium text-dark">İşçilik Yoğunluğu Oranı</span>
          <span class="badge bg-purple-lt font-weight-bold" style="font-size: 0.7rem;">%<?php echo $labor_cost_ratio; ?></span>
        </div>
        <div class="progress" style="height: 5px; border-radius: 10px;">
          <div class="progress-bar bg-purple" style="width: <?php echo $labor_cost_ratio > 100 ? 100 : $labor_cost_ratio; ?>%"></div>
        </div>
      </div>

      <!-- Metrik 3 -->
      <div class="mb-0">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="text-sm font-weight-medium text-dark">Tahsilat Gerçekleşme Oranı</span>
          <span class="badge bg-green-lt font-weight-bold" style="font-size: 0.7rem;">%<?php echo $collection_rate; ?></span>
        </div>
        <div class="progress" style="height: 5px; border-radius: 10px;">
          <div class="progress-bar bg-green" style="width: <?php echo $collection_rate > 100 ? 100 : $collection_rate; ?>%"></div>
        </div>
      </div>
    </div>

    <!-- Detaylı Finansal Rapor Tablosu (Mobil Kart Görünümü) -->
    <div class="mobile-card p-3 shadow-sm mb-4" style="border-radius: 16px;">
      <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-3">DETAYLI FİNANSAL RAPOR</label>
      
      <div class="list-group list-group-flush">
        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-blue" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;">&nbsp;</span>
            <span class="text-sm font-weight-medium text-dark">Sözleşme Bütçesi</span>
          </div>
          <span class="text-bold text-sm text-dark">₺<?php echo Helper::formattedMoneyWithoutCurrency($budget); ?></span>
        </div>

        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-cyan" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;">&nbsp;</span>
            <span class="text-sm font-weight-medium text-dark">Hakediş Toplamı</span>
          </div>
          <span class="text-bold text-sm text-dark">₺<?php echo Helper::formattedMoneyWithoutCurrency($hakedis); ?></span>
        </div>

        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-orange" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;">&nbsp;</span>
            <span class="text-sm font-weight-medium text-dark">İşçilik Maliyeti</span>
          </div>
          <span class="text-bold text-sm text-dark">₺<?php echo Helper::formattedMoneyWithoutCurrency($labor_cost); ?></span>
        </div>

        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-yellow" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;">&nbsp;</span>
            <span class="text-sm font-weight-medium text-dark">Diğer Giderler</span>
          </div>
          <span class="text-bold text-sm text-dark">₺<?php echo Helper::formattedMoneyWithoutCurrency($total_expense + $total_payment); ?></span>
        </div>

        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-bottom" style="background-color: rgba(214, 63, 63, 0.03);">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-red" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;">&nbsp;</span>
            <span class="text-sm font-weight-bold text-danger">Toplam Maliyet</span>
          </div>
          <span class="text-bold text-sm text-danger">₺<?php echo Helper::formattedMoneyWithoutCurrency($total_cost); ?></span>
        </div>

        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-0" style="background-color: rgba(47, 179, 68, 0.03);">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-green" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;">&nbsp;</span>
            <span class="text-sm font-weight-bold text-green">Net Kâr / Zarar</span>
          </div>
          <span class="text-bold text-sm text-green">₺<?php echo Helper::formattedMoneyWithoutCurrency($net_profit); ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 3: Hakediş & Ödemeler -->
  <div id="tab-payments" class="project-tab-content d-none px-2">
    <!-- Özet Finans Kartları -->
    <div class="row g-2 mb-3">
      <div class="col-6">
        <div class="mobile-card p-3 shadow-sm border-0 d-flex flex-column" style="background: rgba(47, 179, 68, 0.08); color: #2fb344; border-radius: 16px;">
          <span class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem;">TOPLAM HAKEDİŞ</span>
          <span class="text-bold h4 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($hakedis); ?></span>
        </div>
      </div>
      <div class="col-6">
        <div class="mobile-card p-3 shadow-sm border-0 d-flex flex-column" style="background: rgba(32, 107, 196, 0.08); color: var(--mobile-primary); border-radius: 16px;">
          <span class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem;">ALINAN ÖDEMELER</span>
          <span class="text-bold h4 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_income); ?></span>
        </div>
      </div>
    </div>
    
    <div class="row g-2 mb-4">
      <div class="col-6">
        <div class="mobile-card p-3 shadow-sm border-0 d-flex flex-column" style="background: rgba(214, 63, 63, 0.08); color: #d63f3f; border-radius: 16px;">
          <span class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem;">KESİNTİ / GİDER</span>
          <span class="text-bold h4 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_expense); ?></span>
        </div>
      </div>
      <div class="col-6">
        <div class="mobile-card p-3 shadow-sm border-0 d-flex flex-column" style="background: rgba(32, 107, 196, 0.08); color: #206bc4; border-radius: 16px;">
          <span class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.65rem;">PROJE BAKİYESİ</span>
          <span class="text-bold h4 mb-0 <?php echo Helper::balanceColor($balance); ?>">₺ <?php echo Helper::formattedMoneyWithoutCurrency($balance); ?></span>
        </div>
      </div>
    </div>

    <!-- Hakediş Tamamlanma Durumu -->
    <div class="mobile-card p-3 shadow-sm mb-4" style="border-radius: 16px;">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-xs text-muted font-weight-bold">Hakediş Tamamlanma Durumu</span>
        <span class="badge bg-primary-lt font-weight-bold" id="progress-bar-percentage">%<?php echo $range; ?></span>
      </div>
      <div class="progress progress-sm" style="height: 6px;">
        <div class="progress-bar bg-primary" id="progress-bar-visual" style="width: <?php echo $range; ?>%" role="progressbar"></div>
      </div>
    </div>

    <!-- Gelir Gider Listesi -->
    <div id="project-financial-list">
      <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-3 px-1">GELİR GİDER HAREKETLERİ</label>
      <?php if (empty($income_expenses)): ?>
        <div class="text-center py-5 bg-white rounded-3 border shadow-sm" style="border-radius: 16px;">
          <i class="ti ti-receipt-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
          <p class="text-muted text-sm mb-0">Henüz finansal hareket bulunmuyor.</p>
        </div>
      <?php else: ?>
        <div class="list-group list-group-mobile shadow-sm">
          <?php foreach ($income_expenses as $item): 
            $item_id = Security::encrypt($item->id);
            $is_income = ($item->turu == 14 || $item->turu == 1); // 14: Puantaj/Hakediş, 1: Gelir
          ?>
            <div class="financial-item-wrapper" style="position: relative; overflow: hidden; background: #fff;">
              <?php if ($item->turu != 14): ?>
                <div class="financial-item-actions" style="position: absolute; right: 0; top: 0; height: 100%; display: flex; align-items: center; background: #d63f3f; z-index: 1;">
                  <button class="btn-swipe-delete btn-delete-project-action" data-id="<?php echo $item_id; ?>" data-project="<?php echo Security::encrypt($item->project_id); ?>" style="color: white; width: 70px; height: 100%; border: none; background: transparent; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600;">
                    <i class="ti ti-trash" style="font-size: 1.2rem; margin-bottom: 2px;"></i>
                    <span>Sil</span>
                  </button>
                </div>
              <?php endif; ?>
              <div class="financial-item-content" style="position: relative; background: #fff; z-index: 2; transition: transform 0.2s ease-out; width: 100%;">
                <div class="list-group-item py-3 px-3">
                  <div class="d-flex align-items-center justify-content-between w-100">
                    <div class="d-flex align-items-center gap-3">
                      <div class="avatar avatar-md rounded-circle <?php echo $is_income ? 'bg-green-lt text-green' : 'bg-red-lt text-red'; ?>" style="width: 44px; height: 44px; border: 1px solid rgba(0,0,0,0.05);">
                        <i class="ti <?php echo $is_income ? 'ti-arrow-up-right' : 'ti-arrow-down-left'; ?>" style="font-size: 1.1rem;"></i>
                      </div>
                      <div>
                        <div class="text-bold text-sm text-dark" style="font-weight: 700;"><?php echo htmlspecialchars($item->aciklama ?: 'İşlem'); ?></div>
                        <div class="text-muted text-xs mt-0.5"><?php echo Date::dmY($item->tarih); ?> • <?php echo $item->ay; ?>/<?php echo $item->yil; ?></div>
                      </div>
                    </div>
                    <div class="text-end">
                      <div class="text-bold text-sm <?php echo $is_income ? 'text-green' : 'text-red'; ?>" style="font-size: 0.95rem;">
                        ₺ <?php echo Helper::formattedMoneyWithoutCurrency($item->tutar); ?>
                      </div>
                      <div class="text-muted text-xs font-weight-bold opacity-75" style="font-size: 0.65rem;"><?php echo $is_income ? 'GELİR' : 'GİDER'; ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 4: Çalışma & Puantaj -->
  <div id="tab-puantaj" class="project-tab-content d-none px-2">
    <!-- Özet Puantaj Kartları -->
    <div class="row g-2 mb-4">
      <div class="col-4">
        <div class="mobile-card p-2 text-center border-0 shadow-sm" style="background: rgba(47, 179, 68, 0.08); color: #2fb344; border-radius: 16px;">
          <div class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.6rem;">ÇALIŞAN PERSONEL</div>
          <div class="text-bold small"><?php echo $total_person; ?> Kişi</div>
        </div>
      </div>
      <div class="col-4">
        <div class="mobile-card p-2 text-center border-0 shadow-sm" style="background: rgba(214, 63, 63, 0.08); color: #d63f3f; border-radius: 16px;">
          <div class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.6rem;">TOPLAM SAAT</div>
          <div class="text-bold small"><?php echo $total_hours; ?> Saat</div>
        </div>
      </div>
      <div class="col-4">
        <div class="mobile-card p-2 text-center border-0 shadow-sm" style="background: rgba(32, 107, 196, 0.08); color: #206bc4; border-radius: 16px;">
          <div class="text-xs font-weight-bold opacity-75 mb-1" style="font-size: 0.6rem;">ÇALIŞMA TUTARI</div>
          <div class="text-bold small" style="font-size: 0.75rem;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_amount); ?></div>
        </div>
      </div>
    </div>

    <!-- Puantaj Listesi -->
    <div class="mb-4" id="project-puantaj-list">
      <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-3 px-1">ÇALIŞMA GÜNLÜĞÜ</label>
      <?php if (empty($puantaj_info)): ?>
        <div class="text-center py-5 bg-white rounded-3 border shadow-sm" style="border-radius: 16px;">
          <i class="ti ti-calendar-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
          <p class="text-muted text-sm mb-0">Henüz puantaj kaydı bulunmuyor.</p>
        </div>
      <?php else: ?>
        <div class="list-group list-group-mobile shadow-sm">
          <?php foreach ($puantaj_info as $item): 
            $puantaj_turu = $puantajModel->getPuantajTuruById($item->puantaj_id);
            $full_name = $personsModel->getPersonByField($item->person, "full_name") ?? 'Bilinmeyen';
            $initials = mb_strtoupper(mb_substr($full_name, 0, 2));
          ?>
            <div class="list-group-item">
              <div class="d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center gap-3">
                  <div class="position-relative">
                    <div class="avatar avatar-md rounded-circle bg-primary-lt text-primary text-bold d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 0.9rem;">
                      <?php echo htmlspecialchars($initials); ?>
                    </div>
                    <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-primary border border-white" style="font-size: 0.6rem; padding: 0.2rem 0.4rem; transform: translate(25%, 25%);">
                      <?php echo htmlspecialchars($puantaj_turu->PuantajKod ?? 'X'); ?>
                    </span>
                  </div>
                  <div>
                    <div class="text-bold text-sm text-dark"><?php echo htmlspecialchars($full_name); ?></div>
                    <div class="text-muted text-xs mt-0.5"><?php echo Date::ymd($item->gun, "d.m.Y"); ?> • <?php echo $item->saat; ?> Saat</div>
                  </div>
                </div>
                <div class="text-end">
                  <div class="text-bold text-sm text-blue" style="font-size: 0.9rem;">
                    ₺ <?php echo Helper::formattedMoneyWithoutCurrency($item->tutar); ?>
                  </div>
                  <div class="text-muted text-xs font-weight-bold opacity-75" style="font-size: 0.65rem;">TUTAR</div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <!-- TAB 6: Proje Personelleri -->
  <?php if ($id > 0): ?>
  <div id="tab-persons" class="project-tab-content d-none px-2">
    <input type="hidden" id="decrypted_project_id" value="<?php echo $id; ?>">
    <!-- Arama ve Tümünü Seç -->
    <div class="mobile-card p-3 shadow-sm mb-3" style="border-radius: 16px;">
      <div class="input-icon mb-3">
        <span class="input-icon-addon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); z-index: 4;">
          <i class="ti ti-search text-muted"></i>
        </span>
        <input type="text" id="personSearchInput" class="form-control" placeholder="Personel ara..." style="border-radius: 10px; padding-left: 36px;">
      </div>
      <div class="form-check form-switch mb-0 ps-0 d-flex align-items-center justify-content-between">
        <label class="form-check-label text-bold text-sm text-dark" for="allPersonCheckMobile">Tümünü Seç / Kaldır</label>
        <input class="form-check-input ms-0" type="checkbox" id="allPersonCheckMobile" style="width: 40px; height: 20px;">
      </div>
    </div>

    <!-- Personel Listesi -->
    <label class="form-label text-muted text-xs text-uppercase font-weight-bold mb-3 px-1">PERSONEL LİSTESİ</label>
    <?php if (empty($project_persons)): ?>
      <div class="text-center py-5 bg-white rounded-3 border mb-4" style="border-radius: 16px;">
        <i class="ti ti-users-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
        <p class="text-muted text-sm mb-0">Henüz personel kaydı bulunmuyor.</p>
      </div>
    <?php else: ?>
      <div class="list-group list-group-mobile shadow-sm mb-4" id="project-persons-list">
        <?php foreach ($project_persons as $p): 
          $checked = $p->is_added == 1 ? "checked" : "";
          $initials = mb_strtoupper(mb_substr($p->full_name, 0, 2));
        ?>
          <div class="list-group-item">
            <div class="d-flex align-items-center justify-content-between w-100">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md rounded-circle bg-blue-lt text-blue text-bold text-uppercase d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 0.9rem; font-weight: 700; border: 1px solid rgba(32, 107, 196, 0.12);">
                  <?php echo htmlspecialchars($initials); ?>
                </div>
                <div>
                  <div class="text-dark" style="font-weight: 700; font-size: 0.95rem; letter-spacing: -0.2px;"><?php echo htmlspecialchars($p->full_name); ?></div>
                  <div class="text-muted text-xs mt-0.5" style="font-weight: 500; opacity: 0.8;"><?php echo $p->wage_type == 1 ? "Beyaz Yaka" : "Mavi Yaka"; ?></div>
                </div>
              </div>
              <div>
                <div class="form-check form-switch pe-0 mb-0">
                  <input class="form-check-input person-checkbox" type="checkbox" name="person_ids[]" value="<?php echo $p->id; ?>" <?php echo $checked; ?> style="width: 38px; height: 19px; cursor: pointer;">
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
$(document).ready(function() {
  // Dropdown Manuel Tetikleyici
  $(document).on('click', '#projectTabsDropdown', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var menu = $(this).next('.dropdown-menu');
      $('.dropdown-menu').not(menu).removeClass('show');
      menu.toggleClass('show');
  });

  let timelineChart = null;
  let breakdownChart = null;

  function initMobileCharts() {
      if (typeof ApexCharts === 'undefined') return;

      // Timeline Chart
      if (!timelineChart) {
          const timelineOptions = {
              series: [
                  { name: 'Hakediş', data: <?php echo $hakedis_json; ?> },
                  { name: 'Maliyet', data: <?php echo $maliyet_json; ?> },
                  { name: 'Kâr', data: <?php echo $profit_json; ?> }
              ],
              chart: {
                  type: 'area',
                  height: 240,
                  toolbar: { show: false },
                  sparkline: { enabled: false }
              },
              colors: ['#206bc4', '#d63f3f', '#2fb344'],
              dataLabels: { enabled: false },
              stroke: { curve: 'smooth', width: 2 },
              xaxis: {
                  categories: <?php echo $categories_json; ?>,
                  labels: { style: { fontSize: '9px', colors: '#94a3b8' } }
              },
              yaxis: {
                  labels: {
                      style: { fontSize: '9px', colors: '#94a3b8' },
                      formatter: function(val) { return '₺' + Math.round(val / 1000) + 'k'; }
                  }
              },
              legend: { position: 'top', horizontalAlign: 'center', fontSize: '10px' },
              grid: { strokeDashArray: 4 }
          };
          timelineChart = new ApexCharts(document.querySelector("#mobile_timeline_chart"), timelineOptions);
          timelineChart.render();
      } else {
          timelineChart.render();
      }

      // Breakdown Chart
      if (!breakdownChart) {
          const breakdownOptions = {
              series: [<?php echo $labor_cost; ?>, <?php echo $total_expense + $total_payment; ?>],
              chart: {
                  type: 'donut',
                  height: 240
              },
              labels: ['İşçilik', 'Diğer Giderler'],
              colors: ['#f59f00', '#f76707'],
              legend: { position: 'bottom', fontSize: '10px' },
              dataLabels: { enabled: true, formatter: function(val) { return Math.round(val) + '%'; } },
              tooltip: {
                  y: {
                      formatter: function(val) { return '₺' + val.toLocaleString('tr-TR'); }
                  }
              }
          };
          breakdownChart = new ApexCharts(document.querySelector("#mobile_cost_breakdown_chart"), breakdownOptions);
          breakdownChart.render();
      } else {
          breakdownChart.render();
      }
  }

  // Sekme Değiştirme Mantığı
  $(document).on('click', '.tab-trigger', function(e) {
      e.preventDefault();
      var tabId = $(this).data('tab');
      var title = $(this).data('title');
      
      // Tüm sekmeleri gizle
      $('.project-tab-content').addClass('d-none');
      // Seçili sekmeyi göster
      $('#tab-' + tabId).removeClass('d-none');
      
      // Başlığı güncelle
      $('#page-title').text(title);
      
      // Dropdown'daki aktif durumu güncelle
      $('.tab-trigger').removeClass('active');
      $(this).addClass('active');
      
      // Dropdown'ı kapat
      $('.dropdown-menu').removeClass('show');
      
      // Grafik sekmesinde ise grafikleri ilklendir
      if (tabId === 'summary') {
          setTimeout(initMobileCharts, 150);
      }
  });

  // İlk yüklemede grafikleri çalıştır
  setTimeout(initMobileCharts, 500);

  // Grafik Gösterim Değiştirici Butonları
  $(document).on('click', '.chart-toggle', function() {
      $('.chart-toggle').removeClass('active');
      $(this).addClass('active');

      const selectedChart = $(this).data('chart');
      if (selectedChart === 'timeline') {
          $('#mobile-chart-timeline-container').removeClass('d-none');
          $('#mobile-chart-breakdown-container').addClass('d-none');
      } else {
          $('#mobile-chart-timeline-container').addClass('d-none');
          $('#mobile-chart-breakdown-container').removeClass('d-none');
      }
  });

  // İl / İlçe dinamik yükleme
  $(document).on('change', '#project_city', function() {
      var cityId = $(this).val();
      var townSelect = $('#project_town');
      townSelect.empty().append('<option value="">Yükleniyor...</option>');

      $.post('api/il-ilce.php', {
          action: 'getTowns',
          city_id: cityId
      }, function(res) {
          townSelect.empty().append('<option value="">İlçe seçiniz</option>');
          if (res.status === 'success' && res.towns) {
              res.towns.forEach(function(town) {
                  townSelect.append('<option value="' + town.id + '">' + town.ilce_adi + '</option>');
              });
          }
      }, 'json');
  });

  // Mobil AJAX Kaydetme İşlemi
  $('#saveProjectMobile').on('click', function(e) {
      e.preventDefault();
      
      var name = $('#floatingProjectName').val();
      if (!name) {
          Swal.fire('Hata!', 'Proje adı alanı boş bırakılamaz.', 'error');
          return;
      }

      var form = $('#projectForm');
      var formData = new FormData(form[0]);

      Swal.fire({
          title: 'Kaydediliyor...',
          text: 'Lütfen bekleyin.',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading();
          }
      });

      fetch('api/projects/projects.php', {
          method: 'POST',
          body: formData
      })
      .then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              Swal.fire({
                  title: 'Başarılı!',
                  text: data.message,
                  icon: 'success',
                  confirmButtonText: 'Tamam'
              }).then(() => {
                  window.location.href = 'projects';
              });
          } else {
              Swal.fire('Hata!', data.message, 'error');
          }
      })
      .catch(error => {
          console.error('Error:', error);
          Swal.fire('Sistem Hatası!', 'Kayıt sırasında teknik bir hata oluştu.', 'error');
      });
  });

  // Swipe logic for financial items
  let touchStartX = 0;
  let touchMoveX = 0;
  const swipeThreshold = 70;

  $(document).on('touchstart', '#project-financial-list .swipe-content', function(e) {
      touchStartX = e.originalEvent.touches[0].clientX;
      $('#project-financial-list .swipe-content').not(this).css('transform', 'translateX(0)');
  });

  $(document).on('touchmove', '#project-financial-list .swipe-content', function(e) {
      touchMoveX = e.originalEvent.touches[0].clientX;
      let diff = touchStartX - touchMoveX;
      if (diff > 0) { // Swipe left
          if (diff > swipeThreshold + 20) diff = swipeThreshold + 20;
          $(this).css('transition', 'none').css('transform', 'translateX(-' + diff + 'px)');
      }
  });

  $(document).on('touchend', '#project-financial-list .swipe-content', function(e) {
      let diff = touchStartX - touchMoveX;
      $(this).css('transition', 'transform 0.2s ease-out');
      if (diff > swipeThreshold / 2) {
          $(this).css('transform', 'translateX(-' + swipeThreshold + 'px)');
      } else {
          $(this).css('transform', 'translateX(0)');
      }
  });

  // Delete Financial Action
  $(document).on('click', '.btn-delete-project-action', function(e) {
      e.preventDefault();
      var btn = $(this);
      var id = btn.data('id');
      var project_id = btn.data('project');

      Swal.fire({
          title: 'Emin misiniz?',
          text: 'Bu finansal hareketi silmek istediğinize emin misiniz?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d63f3f',
          confirmButtonText: 'Evet, Sil',
          cancelButtonText: 'Vazgeç'
      }).then((result) => {
          if (result.isConfirmed) {
              $.post('api/projects/projects.php?project_id=' + project_id, {
                  action: 'deleteProjectAction',
                  id: id
              }, function(res) {
                  if (res.status === 'success') {
                      btn.closest('.swipe-container').fadeOut(300, function() {
                          $(this).remove();
                      });
                      Swal.fire('Silindi!', res.message, 'success');
                      // Update balance summaries dynamically
                      setTimeout(() => {
                          location.reload();
                      }, 1000);
                  } else {
                      Swal.fire('Hata!', res.message, 'error');
                  }
              }, 'json');
          } else {
              btn.closest('.swipe-container').find('.swipe-content').css('transform', 'translateX(0)');
          }
      });
  });

  // Personel Arama
  $(document).on('input', '#personSearchInput', function() {
      var query = $(this).val().toLowerCase();
      $('.person-item-card').each(function() {
          var name = $(this).find('.person-name').text().toLowerCase();
          if (name.indexOf(query) !== -1) {
              $(this).removeClass('d-none');
          } else {
              $(this).addClass('d-none');
          }
      });
  });

  // Personel Tümünü Seç ve Otomatik Kaydet
  $(document).on('change', '#allPersonCheckMobile', function() {
      var isChecked = $(this).is(':checked');
      $('.person-checkbox').prop('checked', isChecked);
      saveProjectPersonsAuto();
  });

  // Tekil Personel Değişimi ve Otomatik Kaydet
  $(document).on('change', '.person-checkbox', function() {
      saveProjectPersonsAuto();
  });

  function saveProjectPersonsAuto() {
      var checkedItems = [];
      $('.person-checkbox:checked').each(function() {
          checkedItems.push($(this).val());
      });

      var projectId = $('#decrypted_project_id').val();
      if (!projectId || projectId === '0') {
          return;
      }

      $.post('api/projects/project-person.php', {
          project_id: projectId,
          person_id: checkedItems.join(','),
          action: 'addPersonToProject'
      }, function(res) {
          try {
              var data = typeof res === 'object' ? res : JSON.parse(res);
              if (data.status === "success") {
                  const Toast = Swal.mixin({
                      toast: true,
                      position: 'top-end',
                      showConfirmButton: false,
                      timer: 1000,
                      timerProgressBar: false
                  });
                  Toast.fire({
                      icon: 'success',
                      title: 'Güncellendi'
                  });
              } else {
                  Swal.fire('Hata!', data.message, 'error');
              }
          } catch (e) {
              console.error('JSON Parse Error:', e, res);
          }
      }).fail(function(xhr) {
          console.error('AJAX Error:', xhr.responseText);
      });
  }

  // Close swipe on clicking elsewhere
  $(document).on('touchstart', function(e) {
      if (!$(e.target).closest('.swipe-container').length) {
          $('.swipe-content').css('transform', 'translateX(0)');
      }
  });
});
</script>
