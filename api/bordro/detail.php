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
require_once ROOT . '/Model/Puantaj.php';
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
    $PuantajModel = new Puantaj();

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

    $sql_pt = "SELECT pt.*, tr.PuantajAdi as puantaj_adi, tr.PuantajKod, tr.Turu as pt_turu, tr.ArkaPlanRengi, tr.FontRengi
               FROM puantaj pt 
               LEFT JOIN puantajturu tr ON tr.id = pt.puantaj_id 
               WHERE pt.person = :person_id 
               AND CAST(REPLACE(pt.gun, '-', '') AS UNSIGNED) >= :start_date 
               AND CAST(REPLACE(pt.gun, '-', '') AS UNSIGNED) <= :end_date 
               ORDER BY CAST(REPLACE(pt.gun, '-', '') AS UNSIGNED) ASC";
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

<style>
.puantaj-calendar {
    display: table;
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
    table-layout: fixed;
}
.puantaj-calendar-row {
    display: table-row;
}
.puantaj-calendar-cell {
    display: table-cell;
    width: 14.28%;
    border: 1px solid var(--tblr-border-color);
    padding: 6px;
    vertical-align: top;
    height: 65px;
    background-color: var(--tblr-bg-surface);
}
.puantaj-calendar-header {
    font-weight: bold;
    text-align: center;
    background-color: var(--tblr-bg-light);
    height: auto !important;
    padding: 8px 4px;
}
.bg-light-lt {
    background-color: rgba(var(--tblr-light-rgb), 0.4) !important;
}
@media print {
    .no-print {
        display: none !important;
    }
}
</style>

<div class="row g-3">
    <!-- Personel Bilgi Başlığı -->
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, rgba(var(--tblr-primary-rgb), 0.05), rgba(var(--tblr-primary-rgb), 0.01));">
            <div class="card-body p-3 d-flex align-items-center">
                <span class="avatar avatar-md bg-primary-lt me-3 fw-bold" style="border-radius: 8px; width: 45px; height: 45px;">
                    <?= mb_substr($person->full_name, 0, 2, 'UTF-8') ?>
                </span>
                <div>
                    <h3 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($person->full_name) ?></h3>
                    <div class="text-muted small mt-0.5">
                        <i class="ti ti-briefcase me-1"></i> <?= htmlspecialchars($person->job ?: 'Personel') ?> • 
                        <i class="ti ti-id me-1"></i> <?= $person->wage_type == 1 ? 'Aylık (Beyaz Yaka)' : 'Günlük (Mavi Yaka)' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Özet Kartları -->
    <div class="col-4">
        <div class="card card-sm bg-primary-lt border-0 shadow-sm" style="border-radius: 10px;">
            <div class="card-body p-3 text-center">
                <div class="text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.05em; opacity: 0.8;">TOPLAM GELİR</div>
                <div class="h2 mb-0 text-primary fw-bold" id="modal-total-income">...</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-sm bg-danger-lt border-0 shadow-sm" style="border-radius: 10px;">
            <div class="card-body p-3 text-center">
                <div class="text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.05em; opacity: 0.8;">TOPLAM KESİNTİ</div>
                <div class="h2 mb-0 text-danger fw-bold" id="modal-total-expense">...</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card card-sm bg-success-lt border-0 shadow-sm" style="border-radius: 10px;">
            <div class="card-body p-3 text-center">
                <div class="text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.05em; opacity: 0.8;">NET ÖDENECEK</div>
                <div class="h2 mb-0 text-success fw-bold" id="modal-net-payment">...</div>
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
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 14px; overflow: hidden;">
            <div class="card-header bg-primary-lt py-2 d-flex justify-content-between align-items-center">
                <h4 class="card-title text-sm text-primary mb-0">Günlük Puantaj Detayları</h4>
                <div class="btn-group btn-group-sm no-print" role="group">
                    <button type="button" class="btn btn-outline-primary active" id="btn-view-list" onclick="togglePuantajView('list')">
                        <i class="ti ti-list me-1"></i> Liste
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btn-view-calendar" onclick="togglePuantajView('calendar')">
                        <i class="ti ti-calendar me-1"></i> Takvim
                    </button>
                </div>
            </div>
            
            <?php
            // Generate dates array for all days of the month
            $days_in_month = Date::daysInMonth($ay, $yil);
            $dates = [];
            for ($d = 1; $d <= $days_in_month; $d++) {
                $dates[] = sprintf('%04d-%02d-%02d', $yil, $ay, $d);
            }
            
            // Group puantaj records by normalized date
            $puantaj_by_date = [];
            if (!empty($puantaj_details)) {
                foreach ($puantaj_details as $pt) {
                    $dateYmd = date('Y-m-d', strtotime($pt['gun']));
                    $puantaj_by_date[$dateYmd] = $pt;
                }
            }
            ?>
            
            <!-- LIST VIEW -->
            <div class="table-responsive" id="puantaj-list-view" style="max-height: 250px;">
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
                        <?php foreach ($dates as $dateStr): ?>
                            <?php 
                            $pt = $puantaj_by_date[$dateStr] ?? null; 
                            $isWeekend = (date('N', strtotime($dateStr)) >= 6);
                            ?>
                            <tr class="<?php echo $isWeekend ? 'table-light' : ''; ?>">
                                <td class="text-muted"><?php echo date('d.m.Y', strtotime($dateStr)); ?></td>
                                <td>
                                    <?php if ($pt): ?>
                                        <?php 
                                        $bgColor = $pt['ArkaPlanRengi'] ?: '#d4edda';
                                        $fontColor = $pt['FontRengi'] ?: '#155724';
                                        ?>
                                        <span class="badge" style="background-color: <?php echo $bgColor; ?> !important; color: <?php echo $fontColor; ?> !important;">
                                            <?php echo htmlspecialchars($pt['PuantajKod'] ?: $pt['puantaj_adi']); ?>
                                        </span>
                                    <?php elseif ($isWeekend): ?>
                                        <span class="badge bg-light text-muted">HT</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php 
                                    if ($pt) {
                                        $saatVal = ($pt['pt_turu'] != 'Saatlik') ? $PuantajModel->getPuantajSaatiByfirm($pt['puantaj_id']) : $pt['saat'];
                                        echo number_format($saatVal, 1, ',', '.');
                                    } else {
                                        echo '0,0';
                                    }
                                    ?>
                                </td>
                                <td class="text-end fw-bold">
                                    <?php echo $pt && floatval($pt['tutar']) > 0 ? '₺' . Helper::formattedMoneyWithoutCurrency($pt['tutar']) : '₺0,00'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- CALENDAR VIEW -->
            <div class="table-responsive p-2" id="puantaj-calendar-view" style="display: none;">
                <div class="puantaj-calendar mb-0">
                    <!-- Weekday headers -->
                    <div class="puantaj-calendar-row bg-light font-weight-bold text-center">
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Pzt</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Sal</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Çar</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Per</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Cum</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Cmt</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Paz</div>
                    </div>
                    
                    <?php
                    $firstDayOfWeek = (int) date('N', strtotime($dates[0]));
                    $weeks = [];
                    $currentWeek = array_fill(1, 7, null);
                    
                    for ($i = 1; $i < $firstDayOfWeek; $i++) {
                        $currentWeek[$i] = null;
                    }
                    
                    $dayIndex = $firstDayOfWeek;
                    foreach ($dates as $dateStr) {
                        $currentWeek[$dayIndex] = $dateStr;
                        if ($dayIndex == 7) {
                            $weeks[] = $currentWeek;
                            $currentWeek = array_fill(1, 7, null);
                            $dayIndex = 1;
                        } else {
                            $dayIndex++;
                        }
                    }
                    if ($dayIndex > 1) {
                        $weeks[] = $currentWeek;
                    }
                    
                    foreach ($weeks as $week) {
                        echo '<div class="puantaj-calendar-row">';
                        for ($i = 1; $i <= 7; $i++) {
                            $dateStr = $week[$i];
                            if ($dateStr) {
                                $pt = $puantaj_by_date[$dateStr] ?? null;
                                $dayNum = date('j', strtotime($dateStr));
                                $isWeekend = ($i >= 6);
                                $cellBg = $isWeekend ? 'background-color: rgba(var(--tblr-danger-rgb), 0.02);' : '';
                                
                                echo '<div class="puantaj-calendar-cell" style="' . $cellBg . '">';
                                echo '<div class="d-flex flex-column justify-content-between h-100">';
                                echo '<div class="d-flex justify-content-between align-items-center mb-1">';
                                echo '<span class="fw-bold text-muted" style="font-size: 0.7rem;">' . $dayNum . '</span>';
                                
                                if ($pt) {
                                    $bgColor = $pt['ArkaPlanRengi'] ?: '#d4edda';
                                    $fontColor = $pt['FontRengi'] ?: '#155724';
                                    echo '<span class="badge" style="font-size: 0.65rem; padding: 2px 4px; background-color: ' . $bgColor . ' !important; color: ' . $fontColor . ' !important;">' . htmlspecialchars($pt['PuantajKod'] ?: $pt['puantaj_adi']) . '</span>';
                                } elseif ($isWeekend) {
                                    echo '<span class="badge bg-light text-muted" style="font-size: 0.65rem; padding: 2px 4px;">HT</span>';
                                }
                                
                                echo '</div>';
                                echo '<div class="text-end mt-auto">';
                                
                                if ($pt) {
                                    $saatVal = ($pt['pt_turu'] != 'Saatlik') ? $PuantajModel->getPuantajSaatiByfirm($pt['puantaj_id']) : $pt['saat'];
                                    if (floatval($saatVal) > 0) {
                                        echo '<span class="text-secondary d-block" style="font-size: 0.65rem;">' . number_format($saatVal, 1, ',', '.') . ' Sa</span>';
                                    }
                                }
                                if ($pt && floatval($pt['tutar']) > 0) {
                                    echo '<span class="fw-bold text-success d-block" style="font-size: 0.65rem;">₺' . Helper::formattedMoneyWithoutCurrency($pt['tutar']) . '</span>';
                                }
                                
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            } else {
                                echo '<div class="puantaj-calendar-cell bg-light-lt"></div>';
                            }
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('#modal-total-income').text('₺<?php echo Helper::formattedMoneyWithoutCurrency($total_income); ?>');
    $('#modal-total-expense').text('₺<?php echo Helper::formattedMoneyWithoutCurrency($total_expense); ?>');
    $('#modal-net-payment').text('₺<?php echo Helper::formattedMoneyWithoutCurrency(max(0, $total_income - $total_expense)); ?>');
    
    window.togglePuantajView = function(view) {
        if (view === 'list') {
            $('#puantaj-list-view').show();
            $('#puantaj-calendar-view').hide();
            $('#btn-view-list').addClass('active');
            $('#btn-view-calendar').removeClass('active');
        } else {
            $('#puantaj-list-view').hide();
            $('#puantaj-calendar-view').show();
            $('#btn-view-list').removeClass('active');
            $('#btn-view-calendar').addClass('active');
        }
    }
</script>
