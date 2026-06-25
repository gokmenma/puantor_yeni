<?php
require_once "Model/CaseTransactions.php";
require_once "App/Helper/helper.php";

use App\Helper\Helper;

$ctObj = new CaseTransactions();
$db = $ctObj->getDb();

// 1. Get all cases of this firm
$cases_stmt = $db->prepare("SELECT id, case_name, start_budget FROM cases WHERE firm_id = ?");
$cases_stmt->execute([$id]);
$firm_cases = $cases_stmt->fetchAll(PDO::FETCH_OBJ);

$case_ids = array_map(function($c) { return $c->id; }, $firm_cases);

$totalIncome = 0;
$totalExpense = 0;
$total_start_budget = 0;

foreach ($firm_cases as $c) {
    $total_start_budget += floatval($c->start_budget);
}

$all_transactions = [];
if (!empty($case_ids)) {
    $placeholders = implode(',', array_fill(0, count($case_ids), '?'));
    $tx_stmt = $db->prepare("SELECT amount, type_id, date, case_id FROM case_transactions WHERE case_id IN ($placeholders)");
    $tx_stmt->execute($case_ids);
    $all_transactions = $tx_stmt->fetchAll(PDO::FETCH_OBJ);
}

foreach ($all_transactions as $t) {
    if ($t->type_id == 1) {
        $totalIncome += floatval($t->amount);
    } else if ($t->type_id == 2) {
        $totalExpense += floatval($t->amount);
    }
}

// Current Net Cash Balance across cases (including their initial budgets)
$balance = $total_start_budget + $totalIncome - $totalExpense;

// Get Active Projects Count
$proj_stmt = $db->prepare("SELECT COUNT(*) as count FROM projects WHERE firm_id = ?");
$proj_stmt->execute([$id]);
$active_projects_count = $proj_stmt->fetch(PDO::FETCH_OBJ)->count ?? 0;

// Function to handle the three date formats: YYYY-MM-DD, YYYYMMDD, DD.MM.YYYY
if (!function_exists('parseTransactionDate')) {
    function parseTransactionDate($dateStr) {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) {
            return null;
        }
        // format 1: YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }
        // format 2: YYYYMMDD
        if (preg_match('/^\d{8}$/', $dateStr)) {
            return substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
        }
        // format 3: DD.MM.YYYY
        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $dateStr)) {
            $parts = explode('.', $dateStr);
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        
        $time = strtotime($dateStr);
        if ($time !== false) {
            return date('Y-m-d', $time);
        }
        return null;
    }
}

// 2. parse dates & group chronologically
$monthly_data = [];
foreach ($all_transactions as $t) {
    $amountVal = floatval($t->amount);
    $standardDate = parseTransactionDate($t->date);
    if ($standardDate) {
        $year = substr($standardDate, 0, 4);
        $month = substr($standardDate, 5, 2);
        $key = "$year-$month";
        
        if (!isset($monthly_data[$key])) {
            $monthly_data[$key] = ['income' => 0, 'expense' => 0];
        }
        
        if ($t->type_id == 1) {
            $monthly_data[$key]['income'] += $amountVal;
        } elseif ($t->type_id == 2) {
            $monthly_data[$key]['expense'] += $amountVal;
        }
    }
}
ksort($monthly_data);

$timeline_categories = [];
$timeline_income = [];
$timeline_expense = [];
$timeline_balance = [];

$turkish_months = [
    '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
    '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
    '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
];

foreach ($monthly_data as $key => $data) {
    list($y, $m) = explode('-', $key);
    $month_name = isset($turkish_months[$m]) ? $turkish_months[$m] : $m;
    $timeline_categories[] = "$month_name $y";
    $timeline_income[] = $data['income'];
    $timeline_expense[] = $data['expense'];
    $timeline_balance[] = $data['income'] - $data['expense'];
}

if (empty($timeline_categories)) {
    $timeline_categories = ['Mevcut Ay'];
    $timeline_income = [0];
    $timeline_expense = [0];
    $timeline_balance = [0];
}

$categories_json = json_encode($timeline_categories);
$income_json = json_encode($timeline_income);
$expense_json = json_encode($timeline_expense);
$balance_json = json_encode($timeline_balance);

// 3. Calculate case balances
$case_names = [];
$case_balances = [];
foreach ($firm_cases as $c) {
    $c_balance = floatval($c->start_budget);
    foreach ($all_transactions as $t) {
        if ($t->case_id == $c->id) {
            if ($t->type_id == 1) {
                $c_balance += floatval($t->amount);
            } else if ($t->type_id == 2) {
                $c_balance -= floatval($t->amount);
            }
        }
    }
    $case_names[] = $c->case_name;
    $case_balances[] = $c_balance;
}

if (empty($case_names)) {
    $case_names = ['Kasa Yok'];
    $case_balances = [0];
}

$case_names_json = json_encode($case_names);
$case_balances_json = json_encode($case_balances);

// Business intelligence ratios for My Company
$margin_rate = ($totalIncome > 0) ? round((($totalIncome - $totalExpense) / $totalIncome) * 100, 1) : 0;
$expense_rate = ($totalIncome > 0) ? round(($totalExpense / $totalIncome) * 100, 1) : 0;
$firm_cases_count = count($firm_cases);
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

<div class="container-xl">
    <!-- Top Stat Cards -->
    <div class="row row-cards mb-4">
        <!-- Card 1: Toplam Kasa Bakiyesi -->
        <div class="col-sm-6 col-lg-3">
            <?php 
            $is_profit = $balance >= 0;
            $bg_gradient = $is_profit ? 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)' : 'linear-gradient(135deg, #fff9db 0%, #fff3bf 100%)';
            $text_color = $is_profit ? '#0369a1' : '#e67700';
            $balance_icon = $is_profit ? 'ti ti-scale' : 'ti ti-alert-triangle';
            ?>
            <div class="card card-sm border-0 shadow-sm summary-card" style="background: <?php echo $bg_gradient; ?>; color: <?php echo $text_color; ?>;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar rounded-circle me-3" style="background-color: rgba(3, 105, 161, 0.1); color: <?php echo $text_color; ?>; width: 44px; height: 44px;">
                            <i class="<?php echo $balance_icon; ?> fs-2"></i>
                        </span>
                        <div>
                            <div class="font-weight-medium text-uppercase-dist text-secondary opacity-75">KASA BAKİYESİ</div>
                            <div class="h2 mb-0 font-weight-bold text-dark mt-1" style="font-size: 1.35rem;"><?php echo Helper::formattedMoney($balance); ?> ₺</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Toplam Gelir -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm summary-card" style="background: linear-gradient(135deg, #f4fbf7 0%, #e6f7ed 100%); color: #2b8a3e;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar rounded-circle me-3" style="background-color: rgba(43, 138, 62, 0.1); color: #2b8a3e; width: 44px; height: 44px;">
                            <i class="ti ti-arrow-up-right fs-2"></i>
                        </span>
                        <div>
                            <div class="font-weight-medium text-uppercase-dist text-secondary opacity-75">TOPLAM GELİR</div>
                            <div class="h2 mb-0 font-weight-bold text-dark mt-1" style="font-size: 1.35rem;"><?php echo Helper::formattedMoney($totalIncome); ?> ₺</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card 3: Toplam Gider -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm summary-card" style="background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%); color: #c92a2a;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar rounded-circle me-3" style="background-color: rgba(201, 42, 42, 0.1); color: #c92a2a; width: 44px; height: 44px;">
                            <i class="ti ti-arrow-down-left fs-2"></i>
                        </span>
                        <div>
                            <div class="font-weight-medium text-uppercase-dist text-secondary opacity-75">TOPLAM GİDER</div>
                            <div class="h2 mb-0 font-weight-bold text-dark mt-1" style="font-size: 1.35rem;"><?php echo Helper::formattedMoney($totalExpense); ?> ₺</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Aktif Projeler -->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm summary-card" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); color: #b45309;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <span class="avatar rounded-circle me-3" style="background-color: rgba(180, 83, 9, 0.1); color: #b45309; width: 44px; height: 44px;">
                            <i class="ti ti-folders fs-2"></i>
                        </span>
                        <div>
                            <div class="font-weight-medium text-uppercase-dist text-secondary opacity-75">AKTİF PROJELER</div>
                            <div class="h2 mb-0 font-weight-bold text-dark mt-1" style="font-size: 1.35rem;"><?php echo $active_projects_count; ?> Adet</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Analisys Row -->
    <div class="row row-cards mb-4">
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

        <!-- Case Breakdown & Metrics (Right: col-lg-4) -->
        <div class="col-lg-4 col-sm-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; height: 100%;">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-1">
                    <h3 class="card-title font-weight-bold text-dark m-0" style="font-size: 1.1rem;">Kasalara Göre Dağılım</h3>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div id="case_breakdown_chart" style="min-height: 250px;"></div>
                    
                    <!-- Business Intelligence Metrics List -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="bi-metric-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-medium text-dark" style="font-size: 0.85rem;">Net Bakiye Oranı</div>
                                <div class="text-muted" style="font-size: 0.75rem;">(Gelir - Gider) / Toplam Gelir</div>
                            </div>
                            <span class="badge bg-blue-lt px-2.5 py-1 text-uppercase-dist font-weight-bold">%<?php echo $margin_rate; ?></span>
                        </div>
                        <div class="bi-metric-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-medium text-dark" style="font-size: 0.85rem;">Gider Oranı</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Toplam Gider / Toplam Gelir</div>
                            </div>
                            <span class="badge bg-purple-lt px-2.5 py-1 text-uppercase-dist font-weight-bold">%<?php echo $expense_rate; ?></span>
                        </div>
                        <div class="bi-metric-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-medium text-dark" style="font-size: 0.85rem;">Toplam Kasa Sayısı</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Firmaya Bağlı Tanımlı Kasa</div>
                            </div>
                            <span class="badge bg-green-lt px-2.5 py-1 text-uppercase-dist font-weight-bold"><?php echo $firm_cases_count; ?> Kasa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Cards Row -->
    <div class="row row-cards">
        <!-- Sol Kart: Firma Künye Bilgileri -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0" style="border-radius: 12px; min-height: 310px;">
                <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex align-items-center">
                    <div class="bg-primary-lt p-2 rounded-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="ti ti-id text-primary fs-3"></i>
                    </div>
                    <h4 class="mb-0 fw-bold text-dark">Firma Künye Bilgileri</h4>
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2" style="width: 35%;">Firma Adı:</td>
                                    <td class="text-dark fw-bold py-2"><?php echo htmlspecialchars($myfirm->firm_name); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Yetkilisi:</td>
                                    <td class="text-dark py-2"><?php echo htmlspecialchars($myfirm->yetkili_adi ?? 'Belirtilmemiş'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Telefon:</td>
                                    <td class="text-dark py-2"><?php echo htmlspecialchars($myfirm->phone ?? 'Belirtilmemiş'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">E-posta:</td>
                                    <td class="text-dark py-2">
                                        <?php if (!empty($myfirm->email)) { ?>
                                            <a href="mailto:<?php echo htmlspecialchars($myfirm->email); ?>" class="text-primary"><?php echo htmlspecialchars($myfirm->email); ?></a>
                                        <?php } else {
                                            echo 'Belirtilmemiş';
                                        } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Başlangıç Bütçesi:</td>
                                    <td class="text-dark py-2"><?php echo Helper::formattedMoney($myfirm->start_budget ?? 0); ?> ₺</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Açıklama:</td>
                                    <td class="text-dark py-2"><?php echo htmlspecialchars($myfirm->description ?? 'Belirtilmemiş'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sağ Kart: Vergi ve Resmi Bilgiler -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0" style="border-radius: 12px; min-height: 310px;">
                <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex align-items-center">
                    <div class="bg-warning-lt p-2 rounded-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="ti ti-wallet text-warning fs-3"></i>
                    </div>
                    <h4 class="mb-0 fw-bold text-dark">Vergi ve Resmi Bilgiler</h4>
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2" style="width: 35%;">Vergi Dairesi:</td>
                                    <td class="text-dark py-2"><?php echo htmlspecialchars($myfirm->tax_office ?? 'Belirtilmemiş'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Vergi Numarası:</td>
                                    <td class="text-dark py-2"><?php echo htmlspecialchars($myfirm->tax_number ?? 'Belirtilmemiş'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold py-2">Oluşturulma Tarihi:</td>
                                    <td class="text-dark py-2"><?php echo htmlspecialchars($myfirm->created_at ?? 'Belirtilmemiş'); ?></td>
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
                name: 'Toplam Alınan (Gelir)',
                data: <?php echo $income_json; ?>
            }, {
                name: 'Toplam Ödenen (Gider)',
                data: <?php echo $expense_json; ?>
            }, {
                name: 'Net Finansal Bakiye',
                data: <?php echo $balance_json; ?>
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
            colors: ['#2fb344', '#d63939', '#206bc4'],
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
                    <?php echo floatval($totalIncome); ?>,
                    <?php echo floatval($totalExpense); ?>,
                    <?php echo floatval($totalIncome - $totalExpense); ?>
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
                    columnWidth: '40%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: ['#2fb344', '#d63939', '#206bc4'],
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
                categories: ['Toplam Gelir', 'Toplam Gider', 'Net Bakiye'],
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


        // --- 3. Case Breakdown Donut Chart ---
        var caseSeries = <?php echo $case_balances_json; ?>;
        var caseLabels = <?php echo $case_names_json; ?>;
        var emptyData = false;

        if (caseSeries.length === 1 && caseSeries[0] === 0) {
            emptyData = true;
            caseSeries = [1];
            caseLabels = ['Kayıtlı Kasa Yok'];
        }

        var caseBreakdownOptions = {
            series: caseSeries,
            chart: {
                type: 'donut',
                height: 250,
                fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, sans-serif'
            },
            labels: caseLabels,
            colors: emptyData ? ['#cbd5e1'] : ['#206bc4', '#4299e1', '#2fb344', '#f76707', '#ae3ec9', '#d63939'],
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
                                label: 'Bakiye',
                                color: '#64748b',
                                fontSize: '13px',
                                formatter: function (w) {
                                    if (emptyData) return '0,00 ₺';
                                    var sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(sum);
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                enabled: true,
                y: {
                    formatter: function (val) {
                        if (emptyData) return '0 ₺';
                        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(val);
                    }
                }
            }
        };

        var caseBreakdownChart = new ApexCharts(document.querySelector("#case_breakdown_chart"), caseBreakdownOptions);
        caseBreakdownChart.render();
    });
</script>
