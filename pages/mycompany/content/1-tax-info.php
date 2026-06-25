<?php
require_once "Model/CaseTransactions.php";
require_once "App/Helper/helper.php";

use App\Helper\Helper;

$ctObj = new CaseTransactions();
$db = $ctObj->getDb();

// Fetch cases with calculated transactions summary
$cases_stmt = $db->prepare("SELECT 
                                c.*, 
                                COALESCE(SUM(CASE WHEN ct.type_id = 1 THEN ct.amount ELSE 0 END), 0) AS total_income,
                                COALESCE(SUM(CASE WHEN ct.type_id = 2 THEN ct.amount ELSE 0 END), 0) AS total_expense
                            FROM cases c
                            LEFT JOIN case_transactions ct ON ct.case_id = c.id
                            WHERE c.firm_id = ?
                            GROUP BY c.id
                            ORDER BY c.isDefault DESC, c.case_name ASC");
$cases_stmt->execute([$id]);
$cases_list = $cases_stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0">
            <div class="card-header bg-transparent border-0 pt-3 pb-0 px-3 d-flex align-items-center">
                <div class="bg-warning-lt p-2 rounded-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="ti ti-wallet text-warning fs-3"></i>
                </div>
                <h4 class="mb-0 fw-bold text-dark">Kasa ve Hesap Bakiyeleri</h4>
            </div>
            
            <div class="card-body px-3 pt-3">
                <div class="table-responsive">
                    <table class="table card-table table-hover table-vcenter text-nowrap">
                        <thead>
                            <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="width: 50px;">Durum</th>
                                <th>Kasa / Hesap Adı</th>
                                <th>Banka / Şube</th>
                                <th class="text-end">Başlangıç Bütçesi</th>
                                <th class="text-end">Toplam Giriş (Gelir)</th>
                                <th class="text-end">Toplam Çıkış (Gider)</th>
                                <th class="text-end pe-3">Güncel Bakiye</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (empty($cases_list)) {
                                echo '<tr><td colspan="7" class="text-center text-muted py-4">Bu firmaya bağlı tanımlanmış kasa bulunmamaktadır.</td></tr>';
                            } else {
                                foreach ($cases_list as $c) {
                                    $initial_budget = floatval($c->start_budget ?? 0);
                                    $income = floatval($c->total_income ?? 0);
                                    $expense = floatval($c->total_expense ?? 0);
                                    $c_balance = $initial_budget + $income - $expense;
                                    
                                    $is_default = ($c->isDefault ?? 0) == 1;
                                    $balance_color = $c_balance >= 0 ? 'text-success' : 'text-danger';
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($is_default): ?>
                                                <span class="badge bg-green-lt px-2 py-1 text-uppercase-dist" data-bs-toggle="tooltip" title="Varsayılan Kasa">Varsayılan</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-lt px-2 py-1 text-uppercase-dist">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <i class="ti ti-wallet text-secondary me-2"></i>
                                            <?php echo htmlspecialchars($c->case_name); ?>
                                            <?php if (!empty($c->case_money_unit)): ?>
                                                <small class="text-muted ms-1">(<?php echo htmlspecialchars($c->case_money_unit); ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($c->bank_name)) {
                                                echo htmlspecialchars($c->bank_name) . (!empty($c->branch_name) ? " / " . htmlspecialchars($c->branch_name) : "");
                                            } else {
                                                echo '<span class="text-muted small">Nakit Kasası</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-end text-secondary fw-semibold"><?php echo Helper::formattedMoney($initial_budget); ?> ₺</td>
                                        <td class="text-end text-success fw-semibold">+<?php echo Helper::formattedMoney($income); ?> ₺</td>
                                        <td class="text-end text-danger fw-semibold">-<?php echo Helper::formattedMoney($expense); ?> ₺</td>
                                        <td class="text-end pe-3 fw-bold <?php echo $balance_color; ?>"><?php echo Helper::formattedMoney($c_balance); ?> ₺</td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>