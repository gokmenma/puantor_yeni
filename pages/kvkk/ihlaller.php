<?php
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/VeriIhlalerModel.php';

$authsObj = new Auths();
$db       = $authsObj->getDb();

$sqlAuthCheck = $db->prepare("SELECT id FROM auths WHERE auth_name = 'kvkk_ihlal_yonet' LIMIT 1");
$sqlAuthCheck->execute();
$authNode = $sqlAuthCheck->fetch(PDO::FETCH_OBJ);

if (!$authNode) {
    $db->prepare("INSERT INTO auths (title, auth_name, description, parent_id, is_active) VALUES ('KVKK Veri İhlali Yönetimi', 'kvkk_ihlal_yonet', 'KVKK veri ihlali kaydetme ve yönetme yetkisi.', 0, 1)")->execute();
    $authNodeId = (int) $db->lastInsertId();
} else {
    $authNodeId = (int) $authNode->id;
}

$sqlMenuCheck = $db->prepare("SELECT id FROM menu WHERE page_link = 'kvkk/ihlaller' LIMIT 1");
$sqlMenuCheck->execute();
if (!$sqlMenuCheck->fetch()) {
    $db->prepare("INSERT INTO menu (page_name, page_link, icon, parent_id, isActive, isMenu, index_no, is_authorize) VALUES ('KVKK Veri İhlalleri', 'kvkk/ihlaller', 'shield-x', 0, 1, 1, 91, 1)")->execute();
}

if (isset($_SESSION['user']->user_roles)) {
    $role_id = (int)(explode(',', $_SESSION['user']->user_roles)[0] ?? 0);
    if ($role_id > 0) {
        $rStmt = $db->prepare("SELECT auth_ids FROM role_auths WHERE role_id = ? LIMIT 1");
        $rStmt->execute([$role_id]);
        $roleAuth = $rStmt->fetch(PDO::FETCH_OBJ);
        if ($roleAuth) {
            $ids = array_filter(explode(',', $roleAuth->auth_ids));
            if (!in_array((string)$authNodeId, $ids)) {
                $ids[] = $authNodeId;
                $db->prepare("UPDATE role_auths SET auth_ids = ? WHERE role_id = ?")->execute([implode(',', $ids), $role_id]);
            }
        }
    }
}

$authsObj->checkAuthorize('kvkk_ihlal_yonet');

$Model    = new VeriIhlalerModel();
$firma_id = (int) $_SESSION['firm_id'];
$ihlaller = $Model->getByFirm($firma_id);
$bekleyenler = $Model->getBekleyenBildirimler($firma_id);
?>

<div class="container-xl mt-3">

    <?php if (!empty($bekleyenler)): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="ti ti-alert-triangle" style="font-size:1.5rem"></i>
        <div>
            <strong><?php echo count($bekleyenler); ?> adet ihlal</strong> için 72 saatlik KVKK bildirim süresi dolmak üzere veya geçmiş durumda!
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Veri İhlali Kayıtları</h3>
            <div class="ms-auto">
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#yeniIhlalModal">
                    <i class="ti ti-plus me-1"></i> İhlal Kaydet
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table card-table table-hover" id="ihlalTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>İhlal Tarihi</th>
                        <th>Tespit Tarihi</th>
                        <th>İhlal Türü</th>
                        <th>Etkilenen Kişi</th>
                        <th>Bildirim Durumu</th>
                        <th>KVKK Ref No</th>
                        <th>Kaydeden</th>
                        <th class="no-export">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ihlaller as $ih): ?>
                    <?php
                        $saatFarki = (time() - strtotime($ih->tespit_tarihi)) / 3600;
                        $kritik = $ih->bildiri_durum === 'bekliyor' && $saatFarki >= 60;
                    ?>
                    <tr class="<?php echo $kritik ? 'table-danger' : ''; ?>">
                        <td><?php echo $ih->id; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($ih->ihlal_tarihi)); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($ih->tespit_tarihi)); ?>
                            <?php if ($kritik): ?>
                            <span class="badge bg-danger ms-1">72s Uyarısı!</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($ih->ihlal_turu, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$ih->etkilenen_kisi_sayisi; ?></td>
                        <td>
                            <?php if ($ih->bildiri_durum === 'kvkk_bildirildi'): ?>
                            <span class="badge bg-success-lt">KVKK'ya Bildirildi</span>
                            <?php else: ?>
                            <span class="badge bg-warning-lt">Bekliyor</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($ih->kvkk_referans_no ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($ih->olusturan_ad ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ($ih->bildiri_durum === 'bekliyor'): ?>
                            <button class="btn btn-sm btn-outline-success btn-bildir"
                                data-id="<?php echo $ih->id; ?>"
                                data-bs-toggle="modal" data-bs-target="#bildirModal">
                                <i class="ti ti-send"></i> Bildir
                            </button>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="yeniIhlalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Veri İhlali Kaydı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="yeniIhlalForm">
                <div class="modal-body">
                    <div class="alert alert-warning py-2">
                        <i class="ti ti-clock me-1"></i>
                        <strong>Yasal hatırlatma:</strong> Veri ihlali tespitinden itibaren <strong>72 saat</strong> içinde KVKK Kuruluna bildirim yapılması zorunludur.
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label required">İhlal Tarihi</label>
                            <input type="datetime-local" name="ihlal_tarihi" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label required">Tespit Tarihi</label>
                            <input type="datetime-local" name="tespit_tarihi" class="form-control" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">İhlal Türü</label>
                            <input type="text" name="ihlal_turu" class="form-control" placeholder="Örn: Yetkisiz erişim, veri sızıntısı, kayıp..." required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Etkilenen Kişi Sayısı</label>
                            <input type="number" name="etkilenen_kisi_sayisi" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Etkilenen Veri Kategorileri</label>
                            <textarea name="etkilenen_veri" class="form-control" rows="2" placeholder="Örn: TC Kimlik, IBAN, telefon..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alınan Önlemler</label>
                            <textarea name="onlem_alinan" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="bildirModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">KVKK Kuruluna Bildirim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bildirForm">
                <input type="hidden" name="id" id="bildirId">
                <div class="modal-body">
                    <label class="form-label required">KVKK Referans / Başvuru Numarası</label>
                    <input type="text" name="referans_no" class="form-control" required placeholder="KVKK sistemindeki başvuru numarası">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Bildirimi Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#ihlalTable')) {
        window.createDataTable('#ihlalTable', { pageLength: 25, order: [[1, 'desc']] });
    }

    $('#yeniIhlalForm').on('submit', function (e) {
        e.preventDefault();
        $.post('/api/kvkk/ihlaller.php', $(this).serialize() + '&action=kaydet', function (res) {
            if (res.status === 'success') {
                location.reload();
            } else {
                alert(res.message);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-bildir', function () {
        $('#bildirId').val($(this).data('id'));
    });

    $('#bildirForm').on('submit', function (e) {
        e.preventDefault();
        $.post('/api/kvkk/ihlaller.php', $(this).serialize() + '&action=bildir', function (res) {
            if (res.status === 'success') {
                location.reload();
            } else {
                alert(res.message);
            }
        }, 'json');
    });
});
</script>
