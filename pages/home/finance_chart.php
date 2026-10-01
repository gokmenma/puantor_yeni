<?php
if (!$perm->hasPermission('income_expense_operations')) {
    return;
}

require_once ROOT . "/Model/CaseTransactions.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Helper;

$caseTransModel = new CaseTransactions();
$trendData = $caseTransModel->getMonthlyIncomeExpenseTrend($firm_id, 6);
$firmCases = $caseTransModel->getFirmCasesWithBalance($firm_id);

$latestIncome = end($trendData['income']) ?: 0;
$latestExpense = end($trendData['expense']) ?: 0;
$latestBalance = $latestIncome - $latestExpense;
?>

<div class="col-12" data-id="widget-finance-chart">
    <div class="card resizable-card">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <span class="mac-title">FİNANSAL ANALİZ & NAKİT AKIŞI (SON 6 AY)</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="badge bg-blue-lt d-none d-sm-inline-block" style="font-size: 11px;">
                    <i class="ti ti-calendar me-1"></i> Son 6 Ay
                </span>
                <a href="/gelir-gider-islemleri" class="btn btn-sm btn-link text-decoration-none" style="font-size:11px; padding:0;">
                    Kasa Hareketleri <i class="ti ti-chevron-right ms-1"></i>
                </a>
                <i class="ti ti-grid-dots drag-handle text-muted ms-2"></i>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-center mb-3">
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center p-2 rounded-2 border bg-light-subtle">
                        <span class="avatar avatar-sm bg-success text-white me-2 rounded">
                            <i class="ti ti-arrow-up-right"></i>
                        </span>
                        <div>
                            <div class="text-secondary small">Bu Ay Toplam Gelir</div>
                            <div class="fw-bold text-success" style="font-size: 15px;"><?php echo Helper::formattedMoney($latestIncome); ?> ₺</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center p-2 rounded-2 border bg-light-subtle">
                        <span class="avatar avatar-sm bg-danger text-white me-2 rounded">
                            <i class="ti ti-arrow-down-left"></i>
                        </span>
                        <div>
                            <div class="text-secondary small">Bu Ay Toplam Gider</div>
                            <div class="fw-bold text-danger" style="font-size: 15px;"><?php echo Helper::formattedMoney($latestExpense); ?> ₺</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="d-flex align-items-center p-2 rounded-2 border bg-light-subtle">
                        <span class="avatar avatar-sm <?php echo $latestBalance >= 0 ? 'bg-primary' : 'bg-warning'; ?> text-white me-2 rounded">
                            <i class="ti ti-scale"></i>
                        </span>
                        <div>
                            <div class="text-secondary small">Bu Ay Net Nakit Akışı</div>
                            <div class="fw-bold <?php echo $latestBalance >= 0 ? 'text-primary' : 'text-warning'; ?>" style="font-size: 15px;">
                                <?php echo Helper::formattedMoney($latestBalance); ?> ₺
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Sol: ApexCharts Aylık Trend -->
                <div class="col-lg-8">
                    <div id="chart-finance-trend" style="min-height: 280px;"></div>
                </div>

                <!-- Sağ: Kasa Bakiyeleri Listesi -->
                <div class="col-lg-4">
                    <div class="border rounded-2 p-3 bg-light-subtle h-100 d-flex flex-direction-column flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <span class="fw-bold text-uppercase small text-muted">
                                <i class="ti ti-wallet me-1 text-primary"></i> Kasa Bakiyeleri
                            </span>
                            <a href="/kasalar" class="badge bg-primary-lt text-decoration-none">
                                Kasalar
                            </a>
                        </div>
                        <div class="overflow-auto flex-grow-1" style="max-height: 220px;">
                            <?php if (!empty($firmCases)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($firmCases as $case): 
                                        $bColor = ($case->balance ?? 0) >= 0 ? 'text-success' : 'text-danger';
                                    ?>
                                        <div class="list-group-item px-0 py-2 border-0 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-medium small"><?php echo htmlspecialchars($case->case_name); ?></div>
                                                    <div class="text-muted" style="font-size: 10px;">
                                                        <?php echo htmlspecialchars($case->bank_name ?: 'Nakit/Genel'); ?>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold <?php echo $bColor; ?> small">
                                                        <?php echo Helper::formattedMoney($case->balance ?? 0); ?> <?php echo htmlspecialchars($case->case_money_unit ?: '₺'); ?>
                                                    </div>
                                                    <div class="text-muted" style="font-size: 9px;">Net Bakiye</div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="ti ti-wallet-off mb-1 d-block" style="font-size: 24px; opacity: 0.5;"></i>
                                    Kasa kaydı bulunamadı.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    if (typeof ApexCharts === 'undefined') {
        return;
    }

    const categories = <?php echo json_encode($trendData['labels'] ?? []); ?>;
    const incomeSeries = <?php echo json_encode($trendData['income'] ?? []); ?>;
    const expenseSeries = <?php echo json_encode($trendData['expense'] ?? []); ?>;

    const options = {
        series: [
            {
                name: 'Gelir',
                data: incomeSeries
            },
            {
                name: 'Gider',
                data: expenseSeries
            }
        ],
        chart: {
            type: 'bar',
            height: 280,
            toolbar: {
                show: false
            },
            fontFamily: 'inherit',
            parentHeightOffset: 0
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '45%',
                borderRadius: 4
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return val.toLocaleString('tr-TR') + ' ₺';
                },
                style: {
                    fontSize: '11px'
                }
            }
        },
        fill: {
            opacity: 1
        },
        colors: ['#2fb344', '#d63939'],
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '12px'
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " ₺";
                }
            }
        },
        grid: {
            strokeDashArray: 4,
            padding: {
                top: -15,
                right: 0,
                left: 10,
                bottom: 0
            }
        }
    };

    const chartEl = document.querySelector("#chart-finance-trend");
    if (chartEl) {
        chartEl.innerHTML = '';
        const chart = new ApexCharts(chartEl, options);
        chart.render();
    }
});
</script>
