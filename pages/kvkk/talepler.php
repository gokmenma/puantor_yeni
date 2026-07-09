<?php
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/KvkkTalepModel.php';
require_once ROOT . '/Model/UserModel.php';

$authsObj = new Auths();
$db       = $authsObj->getDb();

$sqlAuthCheck = $db->prepare("SELECT id FROM auths WHERE auth_name = 'kvkk_talepler_yonet' LIMIT 1");
$sqlAuthCheck->execute();
$authNode = $sqlAuthCheck->fetch(PDO::FETCH_OBJ);

if (!$authNode) {
    $db->prepare("INSERT INTO auths (title, auth_name, description, parent_id, is_active) VALUES ('KVKK Talep Yönetimi', 'kvkk_talepler_yonet', 'KVKK başvurularını görüntüleme ve yönetme yetkisi.', 0, 1)")->execute();
    $authNodeId = (int) $db->lastInsertId();
} else {
    $authNodeId = (int) $authNode->id;
}

$sqlMenuCheck = $db->prepare("SELECT id FROM menu WHERE page_link = 'kvkk/talepler' LIMIT 1");
$sqlMenuCheck->execute();
if (!$sqlMenuCheck->fetch()) {
    $db->prepare("INSERT INTO menu (page_name, page_link, icon, parent_id, isActive, isMenu, index_no, is_authorize) VALUES ('KVKK Talepleri', 'kvkk/talepler', 'shield-lock', 0, 1, 1, 90, 1)")->execute();
}

if (isset($_SESSION['user']->user_roles)) {
    $role_id = (int) (explode(',', $_SESSION['user']->user_roles)[0] ?? 0);
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

$authsObj->checkAuthorize('kvkk_talepler_yonet');

$Model     = new KvkkTalepModel();
$userModel = new UserModel();
$firma_id  = (int) $_SESSION['firm_id'];

$talepler = $Model->getByFirm($firma_id);
$ozet     = $Model->getOzet($firma_id);
$kullanicilar = $userModel->getUsersByFirm($firma_id);

$talep_turu_etiketler = [
    'erisim'   => ['Erişim Talebi', 'bg-blue-lt'],
    'duzeltme' => ['Düzeltme Talebi', 'bg-yellow-lt'],
    'silme'    => ['Silme/Unutulma', 'bg-red-lt'],
    'itiraz'   => ['İşlemeye İtiraz', 'bg-orange-lt'],
    'aktarim'  => ['Veri Taşınabilirliği', 'bg-teal-lt'],
];
$durum_etiketler = [
    'bekliyor'    => ['Bekliyor', 'bg-warning-lt'],
    'isleniyor'   => ['İşleniyor', 'bg-info-lt'],
    'tamamlandi'  => ['Tamamlandı', 'bg-success-lt'],
    'reddedildi'  => ['Reddedildi', 'bg-danger-lt'],
];
?>

<div class="container-xl mt-3">

    <div class="row row-deck row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body p-3 text-center">
                    <div class="h1 mb-0 text-warning"><?php echo (int)$ozet->bekliyor; ?></div>
                    <div class="text-muted">Bekliyor</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body p-3 text-center">
                    <div class="h1 mb-0 text-info"><?php echo (int)$ozet->isleniyor; ?></div>
                    <div class="text-muted">İşleniyor</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body p-3 text-center">
                    <div class="h1 mb-0 text-success"><?php echo (int)$ozet->tamamlandi; ?></div>
                    <div class="text-muted">Tamamlandı</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body p-3 text-center">
                    <div class="h1 mb-0 text-danger"><?php echo (int)$ozet->suresi_gecen; ?></div>
                    <div class="text-muted">Süresi Geçen</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">KVKK Başvuruları</h3>
            <div class="ms-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#yeniTalepModal">
                    <i class="ti ti-plus me-1"></i> Yeni Talep
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table card-table table-hover datatable" id="kvkkTalepTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Başvuran</th>
                        <th>Talep Türü</th>
                        <th>Durum</th>
                        <th>Talep Tarihi</th>
                        <th>Son Yanıt</th>
                        <th>Atanan</th>
                        <th class="no-export">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($talepler as $t): ?>
                    <?php
                        [$tur_label, $tur_badge]   = $talep_turu_etiketler[$t->talep_turu] ?? [$t->talep_turu, ''];
                        [$dur_label, $dur_badge]    = $durum_etiketler[$t->durum] ?? [$t->durum, ''];
                        $suresi_gecti = strtotime($t->son_yanit_tarihi) < time() && in_array($t->durum, ['bekliyor','isleniyor']);
                    ?>
                    <tr>
                        <td><?php echo $t->id; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($t->basvuran_ad, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($t->basvuran_email ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                        </td>
                        <td><span class="badge <?php echo $tur_badge; ?>"><?php echo $tur_label; ?></span></td>
                        <td><span class="badge <?php echo $dur_badge; ?>"><?php echo $dur_label; ?></span></td>
                        <td><?php echo date('d.m.Y', strtotime($t->talep_tarihi)); ?></td>
                        <td class="<?php echo $suresi_gecti ? 'text-danger fw-bold' : ''; ?>">
                            <?php echo date('d.m.Y', strtotime($t->son_yanit_tarihi)); ?>
                            <?php if ($suresi_gecti): ?><span class="badge bg-danger-lt ms-1">Gecikmeli</span><?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($t->atanan_kullanici_ad ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary btn-durum-guncelle"
                                data-id="<?php echo $t->id; ?>"
                                data-durum="<?php echo $t->durum; ?>"
                                data-yanit="<?php echo htmlspecialchars($t->yanit_notu ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-bs-toggle="modal" data-bs-target="#durumModal">
                                <i class="ti ti-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="yeniTalepModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni KVKK Başvurusu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="yeniTalepForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label required">Başvuran Adı Soyadı</label>
                            <input type="text" name="basvuran_ad" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label required">Talep Türü</label>
                            <select name="talep_turu" class="form-select" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($talep_turu_etiketler as $k => $v): ?>
                                <option value="<?php echo $k; ?>"><?php echo $v[0]; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="basvuran_email" class="form-control">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">T.C. Kimlik No</label>
                            <input type="text" name="basvuran_tc" class="form-control" maxlength="11" inputmode="numeric">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Atanan Kullanıcı</label>
                            <select name="atanan_kullanici" class="form-select">
                                <option value="">Atanmadı</option>
                                <?php foreach ($kullanicilar as $u): ?>
                                <option value="<?php echo $u->id; ?>"><?php echo htmlspecialchars($u->full_name, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama / Talep Detayı</label>
                            <textarea name="aciklama" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="durumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Durum Güncelle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="durumForm">
                <input type="hidden" name="id" id="durumId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Durum</label>
                        <select name="durum" id="durumSelect" class="form-select" required>
                            <?php foreach ($durum_etiketler as $k => $v): ?>
                            <option value="<?php echo $k; ?>"><?php echo $v[0]; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yanıt Notu</label>
                        <textarea name="yanit_notu" id="durumYanit" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#kvkkTalepTable')) {
        window.createDataTable('#kvkkTalepTable', { pageLength: 25 });
    }

    $('#yeniTalepForm').on('submit', function (e) {
        e.preventDefault();
        var data = $(this).serialize() + '&action=kaydet';
        $.post('/api/kvkk/talepler.php', data, function (res) {
            if (res.status === 'success') {
                location.reload();
            } else {
                alert(res.message);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-durum-guncelle', function () {
        $('#durumId').val($(this).data('id'));
        $('#durumSelect').val($(this).data('durum'));
        $('#durumYanit').val($(this).data('yanit'));
    });

    $('#durumForm').on('submit', function (e) {
        e.preventDefault();
        var data = $(this).serialize() + '&action=durum_guncelle';
        $.post('/api/kvkk/talepler.php', data, function (res) {
            if (res.status === 'success') {
                location.reload();
            } else {
                alert(res.message);
            }
        }, 'json');
    });
});
</script>
