<?php
require_once "App/Helper/helper.php";
use App\Helper\Helper;

// Ensure required classes are available
if (!class_exists('ProjectIncomeExpense')) {
    require_once ROOT . '/Model/ProjectIncomeExpense.php';
}
if (!class_exists('Puantaj')) {
    require_once ROOT . '/Model/Puantaj.php';
}

$incexpObj = new ProjectIncomeExpense();
$puantajObj = new Puantaj();

$project_id = $id ?? 0;

// Fetch financial summaries
$summary = $incexpObj->sumAllIncomeExpense($project_id);
$hakedis = $summary->hakedis ?? 0;
$total_income = $summary->gelir ?? 0; // Alınan Ödeme (Collected Cash)
$total_expense = $summary->kesinti ?? 0; // Masraflar & Kesintiler
$total_payment = $summary->odeme ?? 0; // Projeye yapılan ödemeler

$budget = $project->budget ?? 0;

// Labor/Puantaj Cost
$labor_cost = $puantajObj->getTotalWorksBalanceByProject($project_id) ?? 0;

// Total Cost (Maliyet) = Giderler/Kesintiler + Labor Cost + Projeye Yapılan Ödemeler
$total_cost = $labor_cost + $total_expense + $total_payment;

// Net Profit (Net Kâr) = Hakediş - Toplam Maliyet
$net_profit = $hakedis - $total_cost;

// Kâr Marjı (Profit Margin) %
$profit_margin = ($hakedis > 0) ? round(($net_profit / $hakedis) * 100, 1) : 0;
// Maliyet Oranı %
$cost_ratio = ($hakedis > 0) ? round(($total_cost / $hakedis) * 100, 1) : 0;

// Calculate business intelligence metrics
$budget_utilization = ($budget > 0) ? round(($hakedis / $budget) * 100, 1) : 0;
$labor_cost_ratio = ($total_cost > 0) ? round(($labor_cost / $total_cost) * 100, 1) : 0;
$other_expense_ratio = ($total_cost > 0) ? round((($total_expense + $total_payment) / $total_cost) * 100, 1) : 0;
$collection_rate = ($hakedis > 0) ? round(($total_income / $hakedis) * 100, 1) : 0;

// Timeline data preparation
$monthly_data = [];
$raw_transactions = $incexpObj->getAllIncomeExpenseByProject($project_id);

if (is_array($raw_transactions)) {
    foreach ($raw_transactions as $tx) {
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

// Group Puantaj (Labor Cost) by month
$puantaj_records = $puantajObj->getPuantajInfoByProject($project_id);
if (is_array($puantaj_records)) {
    foreach ($puantaj_records as $p) {
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

// Sort chronological
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
    .summary-card {
        border-radius: 12px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .text-uppercase-dist {
        letter-spacing: 0.5px;
        font-weight: 600;
        font-size: 0.72rem !important;
    }
    .bi-metric-item {
        border-bottom: 1px dashed #e2e8f0;
        padding-bottom: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .bi-metric-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
        margin-bottom: 0;
    }
    .nav-tabs-summary .nav-link {
        font-weight: 600;
        color: #64748b;
        border-bottom: 2px solid transparent !important;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }
    .nav-tabs-summary .nav-link.active {
        color: #206bc4 !important;
        border-bottom-color: #206bc4 !important;
        background: transparent !important;
    }
</style>

<div class="container-xl mt-3">
    <!-- Top Stat Cards -->
    <div class="row row-cards mb-4">
        <!-- Card 1: Bütçe ve Hakediş -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm summary-card" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); color: #0369a1;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar rounded-circle me-3" style="background-color: rgba(3, 105, 161, 0.1); color: #0369a1; width: 44px; height: 44px;">
                            <i class="ti ti-currency-lira fs-2"></i>
                        </span>
                        <div>
                            <div class="font-weight-medium text-uppercase-dist text-secondary opacity-75">PROJE BÜTÇESİ</div>
                            <div class="h2 mb-1 font-weight-bold text-dark" style="font-size: 1.35rem;"><?php echo Helper::formattedMoney($budget); ?></div>
                            <div style="font-size: 0.75rem; color: #0369a1;">
                                Hakediş Toplamı: <strong class="text-dark"><?php echo Helper::formattedMoney($hakedis); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card 2: Toplam Maliyet -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm summary-card" style="background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%); color: #c92a2a;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar rounded-circle me-3" style="background-color: rgba(201, 42, 42, 0.1); color: #c92a2a; width: 44px; height: 44px;">
                            <i class="ti ti-receipt fs-2"></i>
                        </span>
                        <div>
                            <div class="font-weight-medium text-uppercase-dist text-secondary opacity-75">TOPLAM MALİYET</div>
                            <div class="h2 mb-1 font-weight-bold text-dark" style="font-size: 1.35rem;"><?php echo Helper::formattedMoney($total_cost); ?></div>
                            <div style="font-size: 0.75rem; color: #c92a2a;">
                                İşçilik: <strong class="text-dark"><?php echo Helper::formattedMoney($labor_cost); ?></strong> | Giderler: <strong class="text-dark"><?php echo Helper::formattedMoney($total_expense + $total_payment); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Net Finansal Kâr -->
        <div class="col-sm-6 col-lg-3">
            <?php 
            $is_profit = $net_profit >= 0;
            $bg_gradient = $is_profit ? 'linear-gradient(135deg, #f4fbf7 0%, #e6f7ed 100%)' : 'linear-gradient(135deg, #fff9db 0%, #fff3bf 100%)';
            $text_color = $is_profit ? '#2b8a3e' : '#e67700';
            $profit_icon = $is_profit ? 'ti ti-trending-up' : 'ti ti-trending-down';
            ?>
            <div class="card card-sm border-0 shadow-sm summary-card" style="background: <?php echo $bg_gradient; ?>; color: <?php echo $text_color; ?>;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar rounded-circle me-3" style="background-color: rgba(43, 138, 62, 0.1); color: <?php echo $text_color; ?>; width: 44px; height: 44px;">
                            <i class="<?php echo $profit_icon; ?> fs-2"></i>
                        </span>
                        <div>
                            <div class="font-weight-medium text-uppercase-dist text-secondary opacity-75">NET PROJE KÂRI</div>
                            <div class="h2 mb-1 font-weight-bold text-dark" style="font-size: 1.35rem;"><?php echo Helper::formattedMoney($net_profit); ?></div>
                            <div style="font-size: 0.75rem; color: <?php echo $text_color; ?>;">
                                Kâr Marjı Oranı: <strong class="text-dark">%<?php echo $profit_margin; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Tahsilat / Nakit Durumu -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm summary-card" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); color: #b45309;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar rounded-circle me-3" style="background-color: rgba(180, 83, 9, 0.1); color: #b45309; width: 44px; height: 44px;">
                            <i class="ti ti-wallet fs-2"></i>
                        </span>
                        <div>
                            <div class="font-weight-medium text-uppercase-dist text-secondary opacity-75">TOPLAM TAHSİLAT</div>
                            <div class="h2 mb-1 font-weight-bold text-dark" style="font-size: 1.35rem;"><?php echo Helper::formattedMoney($total_income); ?></div>
                            <div style="font-size: 0.75rem; color: #b45309;">
                                Kalan Alacak: <strong class="text-dark"><?php echo Helper::formattedMoney($hakedis - $total_income); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Analisys Row -->
    <div class="row row-cards">
        <!-- Main Visualizations (Left: col-lg-8) -->
        <div class="col-lg-8 col-sm-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-0 pt-4 px-4 pb-1">
                    <h3 class="card-title font-weight-bold text-dark m-0" style="font-size: 1.1rem;">Finansal Performans Analizi</h3>
                    
                    <ul class="nav nav-tabs border-0 nav-tabs-summary" role="tablist">
                        <li class="nav-item">
                            <a href="#summary-timeline-tab" class="nav-link active" data-bs-toggle="tab" role="tab">Zaman Serisi</a>
                        </li>
                        <li class="nav-item">
                            <a href="#summary-overview-tab" class="nav-link" data-bs-toggle="tab" role="tab">Genel Bakış</a>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-4">
                    <div class="tab-content">
                        <!-- Tab 1: Timeline -->
                        <div class="tab-pane active show" id="summary-timeline-tab" role="tabpanel">
                            <div id="timeline_chart" style="min-height: 350px;"></div>
                        </div>
                        <!-- Tab 2: Overview Grouped Column -->
                        <div class="tab-pane" id="summary-overview-tab" role="tabpanel">
                            <div id="overview_chart" style="min-height: 350px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Breakdown & Metrics (Right: col-lg-4) -->
        <div class="col-lg-4 col-sm-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; height: 100%;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-1">
                    <h3 class="card-title font-weight-bold text-dark m-0" style="font-size: 1.1rem;">Maliyet Dağılımı</h3>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div id="cost_breakdown_chart" style="min-height: 250px;"></div>
                    
                    <!-- Business Intelligence Metrics List -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="bi-metric-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-medium text-dark" style="font-size: 0.85rem;">Bütçe Gerçekleşme Oranı</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Hakediş / Toplam Proje Bütçesi</div>
                            </div>
                            <span class="badge bg-blue-lt px-2.5 py-1 text-uppercase-dist font-weight-bold">%<?php echo $budget_utilization; ?></span>
                        </div>
                        <div class="bi-metric-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-medium text-dark" style="font-size: 0.85rem;">İşçilik Yoğunluğu Oranı</div>
                                <div class="text-muted" style="font-size: 0.75rem;">İşçilik Maliyeti / Toplam Maliyet</div>
                            </div>
                            <span class="badge bg-purple-lt px-2.5 py-1 text-uppercase-dist font-weight-bold">%<?php echo $labor_cost_ratio; ?></span>
                        </div>
                        <div class="bi-metric-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-medium text-dark" style="font-size: 0.85rem;">Tahsilat Gerçekleşme Oranı</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Alınan Ödeme / Hakediş Toplamı</div>
                            </div>
                            <span class="badge bg-green-lt px-2.5 py-1 text-uppercase-dist font-weight-bold">%<?php echo $collection_rate; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Summary Table Row -->
    <div class="row row-cards mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-1">
                    <h3 class="card-title font-weight-bold text-dark m-0" style="font-size: 1.1rem;">Detaylı Finansal Raporlama Tablosu</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap">
                            <thead>
                                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                    <th class="ps-4">Finansal Kalem</th>
                                    <th>İlişkili Oran / Kategori</th>
                                    <th class="text-end pe-4">Toplam Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-4 font-weight-bold text-dark">
                                        <span class="badge bg-blue me-2">&nbsp;</span> Proje Bütçesi (Sözleşme Bedeli)
                                    </td>
                                    <td>Bütçe Tavan Tutarı</td>
                                    <td class="text-end pe-4 font-weight-bold text-dark"><?php echo Helper::formattedMoney($budget); ?></td>
                                </tr>
                                <tr>
                                    <td class="ps-4 font-weight-bold text-dark">
                                        <span class="badge bg-cyan me-2">&nbsp;</span> Toplam Hakediş Tutarı
                                        <span class="text-muted font-weight-normal ms-1" style="font-size: 0.75rem;">(Biten İş Değeri)</span>
                                    </td>
                                    <td>Bütçenin %<?php echo $budget_utilization; ?> kadarı tamamlandı</td>
                                    <td class="text-end pe-4 font-weight-bold text-dark"><?php echo Helper::formattedMoney($hakedis); ?></td>
                                </tr>
                                <tr>
                                    <td class="ps-4 font-weight-bold text-dark">
                                        <span class="badge bg-orange me-2">&nbsp;</span> Toplam İşçilik Maliyeti
                                        <span class="text-muted font-weight-normal ms-1" style="font-size: 0.75rem;">(Puantaj Hak Ediş)</span>
                                    </td>
                                    <td>Maliyetin %<?php echo $labor_cost_ratio; ?> kadarı işçilik</td>
                                    <td class="text-end pe-4 font-weight-bold text-dark"><?php echo Helper::formattedMoney($labor_cost); ?></td>
                                </tr>
                                <tr>
                                    <td class="ps-4 font-weight-bold text-dark">
                                        <span class="badge bg-yellow me-2">&nbsp;</span> Diğer Giderler &amp; Kesintiler
                                    </td>
                                    <td>Maliyetin %<?php echo $other_expense_ratio; ?> kadarı diğer giderler</td>
                                    <td class="text-end pe-4 font-weight-bold text-dark"><?php echo Helper::formattedMoney($total_expense + $total_payment); ?></td>
                                </tr>
                                <tr style="background-color: #fffaf0; font-weight: bold;">
                                    <td class="ps-4 text-danger font-weight-bold">
                                        <span class="badge bg-red me-2">&nbsp;</span> Toplam Proje Maliyeti
                                    </td>
                                    <td class="text-danger">Hakedişin %<?php echo $cost_ratio; ?> kadarı maliyet</td>
                                    <td class="text-end pe-4 text-danger font-weight-bold"><?php echo Helper::formattedMoney($total_cost); ?></td>
                                </tr>
                                <tr style="background-color: #f0fdf4; font-weight: bold; border-bottom: none;">
                                    <td class="ps-4 text-success font-weight-bold">
                                        <span class="badge bg-green me-2">&nbsp;</span> Net Finansal Kâr / Zarar
                                    </td>
                                    <td class="text-success">Hakediş üzerinden %<?php echo $profit_margin; ?> kâr marjı</td>
                                    <td class="text-end pe-4 text-success font-weight-bold"><?php echo Helper::formattedMoney($net_profit); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // --- 1. Chronological Timeline Area Chart ---
        var timelineOptions = {
            series: [{
                name: 'Toplam Hakediş',
                data: <?php echo $hakedis_json; ?>
            }, {
                name: 'Toplam Maliyet',
                data: <?php echo $maliyet_json; ?>
            }, {
                name: 'Net Kâr',
                data: <?php echo $profit_json; ?>
            }],
            chart: {
                type: 'area',
                height: 350,
                parentHeightOffset: 0,
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                },
                fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, sans-serif'
            },
            colors: ['#206bc4', '#d63939', '#2fb344'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                }
            },
            xaxis: {
                categories: <?php echo $categories_json; ?>,
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: '#64748b',
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(value);
                    },
                    style: {
                        colors: '#64748b',
                        fontSize: '11px'
                    }
                }
            },
            grid: {
                strokeDashArray: 4,
                padding: {
                    top: -20,
                    right: 0,
                    left: 10,
                    bottom: 0
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(value);
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                offsetY: -10,
                markers: {
                    radius: 12
                },
                itemMargin: {
                    horizontal: 10
                }
            }
        };

        var timelineChart = new ApexCharts(document.querySelector("#timeline_chart"), timelineOptions);
        timelineChart.render();


        // --- 2. Overview Bar Chart ---
        var overviewOptions = {
            series: [{
                name: 'Finansal Tutar',
                data: [
                    <?php echo floatval($budget); ?>,
                    <?php echo floatval($hakedis); ?>,
                    <?php echo floatval($total_income); ?>,
                    <?php echo floatval($total_cost); ?>,
                    <?php echo floatval($net_profit); ?>
                ]
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                },
                fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, sans-serif'
            },
            plotOptions: {
                bar: {
                    distributed: true,
                    borderRadius: 6,
                    columnWidth: '50%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: ['#206bc4', '#4299e1', '#2fb344', '#d63939', '#1db88e'],
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return new Intl.NumberFormat('tr-TR', { notation: "compact", compactDisplay: "short" }).format(val);
                },
                offsetY: -20,
                style: {
                    fontSize: '11px',
                    colors: ["#334155"],
                    fontWeight: 'bold'
                }
            },
            xaxis: {
                categories: ['Sözleşme Bütçesi', 'Hakediş Toplamı', 'Tahsil Edilen', 'Toplam Maliyet', 'Net Kâr'],
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: '#64748b',
                        fontSize: '11px',
                        fontWeight: 500
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(value);
                    },
                    style: {
                        colors: '#64748b',
                        fontSize: '11px'
                    }
                }
            },
            grid: {
                strokeDashArray: 4,
                padding: {
                    top: 10,
                    right: 0,
                    left: 10,
                    bottom: 0
                }
            },
            legend: {
                show: false
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(value);
                    }
                }
            }
        };

        var overviewChart = new ApexCharts(document.querySelector("#overview_chart"), overviewOptions);
        overviewChart.render();


        // --- 3. Cost Breakdown Donut Chart ---
        var costSeries = [<?php echo floatval($labor_cost); ?>, <?php echo floatval($total_expense + $total_payment); ?>];
        var costLabels = ['İşçilik Maliyeti', 'Diğer Giderler & Kesintiler'];
        var emptyData = false;

        if (costSeries[0] === 0 && costSeries[1] === 0) {
            costSeries = [1];
            costLabels = ['Kayıtlı Gider Yok'];
            emptyData = true;
        }

        var costOptions = {
            series: costSeries,
            chart: {
                type: 'donut',
                height: 250,
                fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, sans-serif'
            },
            labels: costLabels,
            colors: emptyData ? ['#cbd5e1'] : ['#4f46e5', '#f76707'],
            stroke: {
                width: 2,
                colors: ['#ffffff']
            },
            legend: {
                position: 'bottom',
                offsetY: 0,
                markers: {
                    radius: 12
                },
                labels: {
                    colors: '#475569'
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Maliyet',
                                color: '#64748b',
                                fontSize: '13px',
                                formatter: function (w) {
                                    if (emptyData) return '0,00 ₺';
                                    var sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(sum);
                                }
                            },
                            value: {
                                fontSize: '15px',
                                fontWeight: 'bold',
                                color: '#1e293b',
                                formatter: function (value) {
                                    if (emptyData) return '';
                                    return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(value);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                enabled: !emptyData,
                y: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(value);
                    }
                }
            }
        };

        var costChart = new ApexCharts(document.querySelector("#cost_breakdown_chart"), costOptions);
        costChart.render();

        // Fix chart size rendering issue inside hidden Bootstrap tabs on click
        document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function() {
                timelineChart.windowResizeHandler();
                overviewChart.windowResizeHandler();
                costChart.windowResizeHandler();
            });
        });
    });
</script>