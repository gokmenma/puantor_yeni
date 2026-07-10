<?php
require_once "App/Helper/helper.php";
require_once "Model/IcraDaireleriModel.php";

use App\Helper\Helper;
use App\Helper\Security;

// Yetki kontrolü
$perm->checkAuthorize("icra_daireleri_list");

$IcraDaireleri = new IcraDaireleriModel();
$icraDaireleri = $IcraDaireleri->all();
?>
<div class="container-xl mt-3">
    <div class="alert alert-info bg-white alert-dismissible d-flex">
        <div class="d-flex">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon alert-icon text-info">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                    <path d="M12 9h.01"></path>
                    <path d="M11 12h1v4h1"></path>
                </svg>
            </div>
            <div class="ms-3">
                <h4 class="alert-title">İcra Daireleri Tanımlama!</h4>
                <div class="text-secondary">İcra dosyalarında kullanılmak üzere icra dairelerini burada tanımlayabilirsiniz!</div>
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">İcra Daireleri Listesi</h3>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#icraDairesiModal" id="btnNewIcraDairesi">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table card-table text-nowrap" id="icra-daireleri-table">
                        <thead>
                            <tr>
                                <th style="width:7%">Sıra</th>
                                <th>Daire Adı</th>
                                <th>Şehir</th>
                                <th>IBAN</th>
                                <th>Durum</th>
                                <th>Eklenme Tarihi</th>
                                <th style="width:7%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($icraDaireleri as $daire):
                                $id = Security::encrypt($daire->id);
                                $formatted_date = !empty($daire->kayit_tarihi) ? date('d.m.Y', strtotime($daire->kayit_tarihi)) : '';
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td>
                                        <a class="btn btn-ghost-info btn-sm btn-edit-icra-dairesi" href="#"
                                            data-id="<?php echo $id ?>"
                                            data-name="<?php echo htmlspecialchars($daire->daire_adi, ENT_QUOTES, 'UTF-8') ?>"
                                            data-city="<?php echo htmlspecialchars($daire->sehir ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-iban="<?php echo htmlspecialchars($daire->iban ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?php echo htmlspecialchars($daire->durum ?? 'Aktif', ENT_QUOTES, 'UTF-8') ?>">
                                            <?php echo htmlspecialchars($daire->daire_adi, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($daire->sehir ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($daire->iban ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if (($daire->durum ?? 'Aktif') == 'Aktif'): ?>
                                            <span class="badge bg-success-lt">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt"><?php echo htmlspecialchars($daire->durum, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start"><?php echo $formatted_date; ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item btn-edit-icra-dairesi" href="#"
                                                    data-id="<?php echo $id ?>"
                                                    data-name="<?php echo htmlspecialchars($daire->daire_adi, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-city="<?php echo htmlspecialchars($daire->sehir ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-iban="<?php echo htmlspecialchars($daire->iban ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-status="<?php echo htmlspecialchars($daire->durum ?? 'Aktif', ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-icra-dairesi" href="#"
                                                    data-id="<?php echo $id ?>">
                                                    <i class="ti ti-trash icon me-3"></i> Sil
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Ekle/Düzenle Formu -->
<div class="modal modal-blur fade" id="icraDairesiModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="icraDairesiModalTitle"><i class="ti ti-building-bank text-primary me-2"></i>Yeni İcra Dairesi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="icraDairesiForm">
                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="action" value="saveIcraDairesi">

                    <div class="mb-3">
                        <label class="form-label required">Daire Adı</label>
                        <input type="text" name="daire_adi" id="daire_adi" class="form-control" placeholder="Örn: Ankara 3.İcra Müdürlüğü">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şehir</label>
                        <input type="text" name="sehir" id="sehir" class="form-control" placeholder="Örn: Ankara">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" id="iban" class="form-control" placeholder="TR00 0000 0000 0000 0000 0000 00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="durum" id="durum" class="form-select select2">
                            <option value="Aktif">Aktif</option>
                            <option value="Pasif">Pasif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary ms-auto" id="saveIcraDairesi">
                    <i class="ti ti-device-floppy icon me-2"></i>Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<style>
#icra-daireleri-table td,
#icra-daireleri-table th {
    padding-top: 0.4rem;
    padding-bottom: 0.4rem;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.createDataTable) {
        window.createDataTable('#icra-daireleri-table', {
            pageLength: 25
        });
    }
});
</script>
