<?php
// Bu modal list.php tarafından çağrılır ve list.php içerisindeki değişkenler kullanılabilir.

use App\Helper\Date;

$db = $puantajObj->getDb();
$start_date = Date::firstDay($month, $year);
$end_date = Date::lastDay($month, $year);

// 1. TOPLAM PROJE HESAPLAMA
if ($project_id > 0) {
    $total_projects = 1;
    $project_name = $projectHelper->getProjectName($project_id);
    $project_desc = "Seçili: " . htmlspecialchars($project_name);
} else {
    // Bu ay çalışılan projeler
    $proj_sql = $db->prepare("
        SELECT COUNT(DISTINCT p.project_id) as active_projects 
        FROM puantaj p 
        INNER JOIN projects pr ON p.project_id = pr.id 
        WHERE p.gun >= ? AND p.gun <= ? AND pr.firm_id = ?
    ");
    $proj_sql->execute([$start_date, $end_date, $_SESSION['firm_id']]);
    $active_proj_count = $proj_sql->fetch(PDO::FETCH_OBJ)->active_projects ?? 0;
    
    if ($active_proj_count == 0) {
        $active_proj_count = count($projects->getProjectsByFirm($_SESSION['firm_id']));
    }
    $total_projects = $active_proj_count;
    $project_desc = "Firma Aktif Projeleri";
}

// 2. TOPLAM PERSONEL HESAPLAMA
$total_personnel = count($persons);
$personnel_desc = "Aktif Çalışan";

// 3. TOPLAM ÇALIŞMA (GÜN / SAAT) HESAPLAMA
if ($project_id > 0) {
    $work_sql = $db->prepare("
        SELECT COUNT(*) as total_days, SUM(saat) as total_hours 
        FROM puantaj 
        WHERE project_id = ? AND gun >= ? AND gun <= ?
    ");
    $work_sql->execute([$project_id, $start_date, $end_date]);
} else {
    $work_sql = $db->prepare("
        SELECT COUNT(*) as total_days, SUM(saat) as total_hours 
        FROM puantaj p
        INNER JOIN projects pr ON p.project_id = pr.id
        WHERE pr.firm_id = ? AND p.gun >= ? AND p.gun <= ?
    ");
    $work_sql->execute([$_SESSION['firm_id'], $start_date, $end_date]);
}
$work_res = $work_sql->fetch(PDO::FETCH_OBJ);
$total_days = $work_res->total_days ?? 0;
$total_hours = $work_res->total_hours ?? 0;

// PUANTAJ DAĞILIMI
if ($project_id > 0) {
    $breakdown_sql = $db->prepare("
        SELECT pt.PuantajAdi, pt.PuantajKod, pt.Turu, pt.ArkaPlanRengi, pt.FontRengi, COUNT(p.id) as count 
        FROM puantaj p
        INNER JOIN puantajturu pt ON p.puantaj_id = pt.id
        WHERE p.project_id = ? AND p.gun >= ? AND p.gun <= ?
        GROUP BY p.puantaj_id
        ORDER BY count DESC
    ");
    $breakdown_sql->execute([$project_id, $start_date, $end_date]);
} else {
    $breakdown_sql = $db->prepare("
        SELECT pt.PuantajAdi, pt.PuantajKod, pt.Turu, pt.ArkaPlanRengi, pt.FontRengi, COUNT(p.id) as count 
        FROM puantaj p
        INNER JOIN puantajturu pt ON p.puantaj_id = pt.id
        INNER JOIN projects pr ON p.project_id = pr.id
        WHERE pr.firm_id = ? AND p.gun >= ? AND p.gun <= ?
        GROUP BY p.puantaj_id
        ORDER BY count DESC
    ");
    $breakdown_sql->execute([$_SESSION['firm_id'], $start_date, $end_date]);
}
$breakdowns = $breakdown_sql->fetchAll(PDO::FETCH_OBJ);
?>

<div class="modal modal-blur fade" id="modal-statistics" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="ti ti-chart-bar text-indigo me-2" style="font-size: 1.25rem;"></i>
                    <?php echo Date::monthName($month) . " " . $year; ?> Dönemi Genel Özeti
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- 3 Large Stat Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-sm shadow-sm border-0 bg-indigo-lt h-100">
                            <div class="card-body d-flex align-items-center">
                                <span class="bg-indigo text-white avatar me-3">
                                    <i class="ti ti-building icon"></i>
                                </span>
                                <div>
                                    <div class="font-weight-medium text-secondary" style="font-size: 0.85rem;">Toplam Proje</div>
                                    <div class="text-indigo" style="font-size: 1.5rem; font-weight: 700;"><?php echo $total_projects; ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?php echo $project_desc; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-sm shadow-sm border-0 bg-green-lt h-100">
                            <div class="card-body d-flex align-items-center">
                                <span class="bg-green text-white avatar me-3">
                                    <i class="ti ti-users icon"></i>
                                </span>
                                <div>
                                    <div class="font-weight-medium text-secondary" style="font-size: 0.85rem;">Toplam Personel</div>
                                    <div class="text-green" style="font-size: 1.5rem; font-weight: 700;"><?php echo $total_personnel; ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?php echo $personnel_desc; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-sm shadow-sm border-0 bg-orange-lt h-100">
                            <div class="card-body d-flex align-items-center">
                                <span class="bg-orange text-white avatar me-3">
                                    <i class="ti ti-calendar-stats icon"></i>
                                </span>
                                <div>
                                    <div class="font-weight-medium text-secondary" style="font-size: 0.85rem;">Toplam Çalışma</div>
                                    <div class="text-orange" style="font-size: 1.1rem; font-weight: 700; line-height: 1.2;">
                                        <?php echo number_format($total_days); ?> Gün <span style="font-size: 0.8rem; font-weight: 400;" class="text-muted">/</span> <?php echo number_format($total_hours); ?> Saat
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Kayıtlı İşlem Sayısı</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Puantaj Dağılımı Listesi -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h4 class="card-title" style="font-size: 0.95rem; font-weight: 600;">
                            <i class="ti ti-chart-donut text-indigo me-2"></i> Puantaj Türlerine Göre Dağılım
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($breakdowns)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="ti ti-alert-circle text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                Bu dönem için kaydedilmiş puantaj kaydı bulunmuyor.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table card-table table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>Kod</th>
                                            <th>Puantaj Adı</th>
                                            <th>Tür</th>
                                            <th class="w-50">Dağılım</th>
                                            <th class="text-end">Kayıt Sayısı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($breakdowns as $row): 
                                            $percentage = $total_days > 0 ? round(($row->count / $total_days) * 100, 1) : 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($row->ArkaPlanRengi); ?>; color: <?php echo htmlspecialchars($row->FontRengi); ?>; font-weight: 700; width: 35px; text-align: center; display: inline-block;">
                                                        <?php echo htmlspecialchars($row->PuantajKod); ?>
                                                    </span>
                                                </td>
                                                <td class="font-weight-medium"><?php echo htmlspecialchars($row->PuantajAdi); ?></td>
                                                <td class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($row->Turu); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2" style="font-size: 0.8rem; width: 35px;"><?php echo $percentage; ?>%</span>
                                                        <div class="progress progress-xs w-100" style="height: 6px;">
                                                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%; background-color: <?php echo htmlspecialchars($row->ArkaPlanRengi); ?>;"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end font-weight-medium"><?php echo number_format($row->count); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary ms-auto" data-bs-dismiss="modal">
                    <i class="ti ti-check icon me-1"></i> Kapat
                </button>
            </div>
        </div>
    </div>
</div>
