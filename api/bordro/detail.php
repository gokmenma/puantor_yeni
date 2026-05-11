<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/Model/MyFirmModel.php';
require_once ROOT . '/Model/DefinesModel.php';
require_once ROOT . '/App/Helper/security.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/date.php';

use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;

try {
    $Persons = new Persons();
    $Bordro = new Bordro();
    $MyFirm = new MyFirmModel();
    $Defines = new DefinesModel();

    $firm_id = $_SESSION['firm_id'] ?? 0;
    
    $id_raw = $_POST['id'] ?? '';
    $personel_id = Security::decrypt($id_raw);
    $ay = $_POST['month'] ?? date('m');
    $yil = $_POST['year'] ?? date('Y');

    // Debug info (will be visible in modal if something is wrong)
    // echo "<!-- Debug: ID: $personel_id, Ay: $ay, Yil: $yil, Firm: $firm_id -->";

    if (!$personel_id) {
        throw new Exception("Geçersiz personel kimliği.");
    }

    $person = $Persons->find($personel_id);
    if (!$person) {
        throw new Exception("Personel bulunamadı.");
    }

    // Personel Gelir Bilgileri
    $incomes = $Bordro->getPersonIncome($personel_id, $ay, $yil);

    // Personel Gider Bilgileri
    $expenses = $Bordro->getPersonExpense($personel_id, $ay, $yil);

    // Personel Puantaj Detayları (Günlük)
    $firstDay = Date::firstDay($ay, $yil);
    $lastDay = Date::lastDay($ay, $yil);

    $sql_pt = "SELECT pt.*, tr.PuantajAdi as puantaj_adi, tr.PuantajKod 
               FROM puantaj pt 
               LEFT JOIN puantajturu tr ON tr.id = pt.puantaj_id 
               WHERE pt.person = :person_id 
               AND CAST(REPLACE(pt.gun, '-', '') AS UNSIGNED) >= :start_date 
               AND CAST(REPLACE(pt.gun, '-', '') AS UNSIGNED) <= :end_date 
               ORDER BY pt.gun ASC";
    $stmt_pt = $db->prepare($sql_pt);
    $stmt_pt->execute([
        ':person_id' => $personel_id,
        ':start_date' => $firstDay,
        ':end_date' => $lastDay
    ]);
    $puantaj_details = $stmt_pt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    ob_clean();
    echo '<div class="alert alert-danger p-3 mb-0">' . $e->getMessage() . '</div>';
    exit;
}
?>

<div class="row g-3">
    <!-- Özet Kartları -->
    <div class="col-6">
        <div class="card card-sm bg-blue-lt border-0 shadow-none">
            <div class="card-body p-2 text-center">
                <div class="text-xs text-uppercase font-weight-bold opacity-75">Toplam Gelir</div>
                <div class="h3 mb-0 text-blue" id="modal-total-income">...</div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card card-sm bg-red-lt border-0 shadow-none">
            <div class="card-body p-2 text-center">
                <div class="text-xs text-uppercase font-weight-bold opacity-75">Toplam Kesinti</div>
                <div class="h3 mb-0 text-red" id="modal-total-expense">...</div>
            </div>
        </div>
    </div>

    <!-- Gelir & Gider Listesi -->
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 14px; overflow: hidden;">
            <div class="card-header bg-light py-2">
                <h4 class="card-title text-sm">Finansal Özet</h4>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm mb-0" style="font-size: 0.85rem;">
                    <tbody>
                        <?php
                        $total_income = 0;
                        $total_expense = 0;
                        
                        // Gelirleri Listele
                        if (!empty($incomes)) {
                            foreach ($incomes as $income) {
                                $total_income += $income->tutar;
                                echo "<tr>
                                    <td><i class='ti ti-circle-plus text-success me-1'></i> {$income->turu}</td>
                                    <td class='text-end fw-bold'>₺" . Helper::formattedMoneyWithoutCurrency($income->tutar) . "</td>
                                </tr>";
                            }
                        }

                        // Giderleri Listele
                        if (!empty($expenses)) {
                            foreach ($expenses as $expense) {
                                $total_expense += $expense->tutar;
                                $name = $Defines->getTypeNameById($expense->kategori ?? 0) ?: $expense->turu;
                                echo "<tr>
                                    <td><i class='ti ti-circle-minus text-danger me-1'></i> {$name}</td>
                                    <td class='text-end fw-bold text-danger'>-₺" . Helper::formattedMoneyWithoutCurrency($expense->tutar) . "</td>
                                </tr>";
                            }
                        }
                        
                        if (empty($incomes) && empty($expenses)) {
                            echo "<tr><td colspan='2' class='text-center py-3 text-muted'>Kayıt bulunamadı</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Günlük Puantaj Detayları -->
    <?php if (!empty($puantaj_details)): ?>
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 14px; overflow: hidden;">
            <div class="card-header bg-primary-lt py-2">
                <h4 class="card-title text-sm">Günlük Puantaj Detayları</h4>
            </div>
            <div class="table-responsive" style="max-height: 250px;">
                <table class="table table-vcenter table-sm mb-0" style="font-size: 0.8rem;">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th>Tarih</th>
                            <th>Tür</th>
                            <th class="text-end">Saat</th>
                            <th class="text-end">Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($puantaj_details as $pt): ?>
                            <tr>
                                <td class="text-muted"><?php echo Date::dmY($pt['gun']); ?></td>
                                <td><span class="badge bg-azure-lt"><?php echo $pt['PuantajKod'] ?: $pt['puantaj_adi']; ?></span></td>
                                <td class="text-end"><?php echo number_format($pt['saat'], 1, ',', '.'); ?></td>
                                <td class="text-end fw-bold">₺<?php echo Helper::formattedMoneyWithoutCurrency($pt['tutar']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    $('#modal-total-income').text('₺<?php echo Helper::formattedMoneyWithoutCurrency($total_income); ?>');
    $('#modal-total-expense').text('₺<?php echo Helper::formattedMoneyWithoutCurrency($total_expense); ?>');
</script>
