<?php
require_once "Model/Cari.php";
require_once "Model/CariHareketleri.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/date.php";
require_once "App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

//Kullanıcının firmasını kontrol eder
$Auths->checkFirmReturn();

//Yetki kontrolü
$perm->checkAuthorize("cari_hareketleri");

$cari_id_enc = $_GET['id'] ?? null;
if (!$cari_id_enc) {
    Helper::redirect("?p=cari/list");
}

$cari_id = Security::decrypt($cari_id_enc);
$cariModel = new Cari();
$cari = $cariModel->find($cari_id);

if (!$cari || $cari->firma != $_SESSION['firm_id']) {
    Helper::redirect("?p=cari/list");
}

$moveModel = new CariHareketleri();
$movements = $moveModel->getMovementsByCari($cari_id);

require_once "App/Helper/company.php";
$companyHelper = new CompanyHelper();
$firm_name = $companyHelper->getFirmName($_SESSION['firm_id']);

$total_borc = 0;
$total_alacak = 0;
foreach ($movements as $m) {
    $total_borc += $m->borc;
    $total_alacak += $m->alacak;
}
$net_balance = $total_borc - $total_alacak;
?>

<style>
@media print {
    /* Hide general layout elements */
    .navbar, aside, #navbar, .page-header.d-print-none, 
    .footer, .fab-menu, .dropdown-toggle, 
    .dataTables_wrapper .row:first-child, 
    .dataTables_wrapper .row:last-child,
    .datatable thead tr:has(input), .datatable thead tr + tr:has(input),
    .datatable thead input,
    header.navbar-expand-md {
        display: none !important;
    }
    
    /* Standard trick to hide the search row if :has() isn't fully supported by print engines */
    thead tr:nth-child(2) {
        display: none !important;
    }
    
    /* Ensure print row 1 is visible though */
    thead tr:first-child {
        display: table-row !important;
    }

    body {
        background-color: #fff !important;
        color: #000 !important;
        font-size: 12px !important;
    }

    .page-wrapper {
        padding: 0 !important;
        margin: 0 !important;
    }

    .container-xl {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    table.table {
        border: 1px solid #dee2e6 !important;
        width: 100% !important;
        border-collapse: collapse !important;
    }

    table.table th, table.table td {
        border: 1px solid #dee2e6 !important;
        padding: 6px !important;
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
    
    .text-success {
        color: #28a745 !important;
    }
}
</style>

<div class="container-xl mt-3">
    
    <!-- Yazdırma Başlığı (Sadece Yazdırırken Görünür) -->
    <div class="d-none d-print-block mb-4" style="border-bottom: 2px solid #000; padding-bottom: 10px;">
        <div class="row align-items-center">
            <div class="col-4">
                <img src="./static/Logo-aiv5.svg" height="45" alt="Logo" style="filter: grayscale(1);">
            </div>
            <div class="col-8 text-end">
                <h2 class="mb-1" style="color: #333; font-weight: 700;"><?php echo htmlspecialchars($firm_name); ?></h2>
                <h3 class="mb-0 text-muted" style="letter-spacing: 1px;">CARİ HESAP EKSTRESİ</h3>
            </div>
        </div>
        
        <div class="row mt-4 pt-3" style="border-top: 1px dashed #ccc;">
            <div class="col-6">
                <p class="mb-1"><strong>CARİ BİLGİLERİ</strong></p>
                <div style="font-size: 1.1rem; font-weight: 600;"><?php echo htmlspecialchars($cari->FirmaAdi); ?></div>
                <?php if($cari->YetkiliAdi): ?><div>Yetkili: <?php echo htmlspecialchars($cari->YetkiliAdi); ?></div><?php endif; ?>
                <div>Tel: <?php echo $cari->Telefon ?: '-'; ?></div>
                <div>E-Posta: <?php echo $cari->Email ?: '-'; ?></div>
            </div>
            <div class="col-6 text-end">
                <p class="mb-1"><strong>DÖKÜM DETAYLARI</strong></p>
                <div>Belge Tarihi: <?php echo date('d.m.Y H:i'); ?></div>
                <div class="mt-2">
                    <strong style="font-size: 1rem;">GÜNCEL BAKİYE:</strong><br>
                    <span style="font-size: 1.3rem; font-weight: bold;" class="<?php echo $net_balance < 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo Helper::formattedMoney(abs($net_balance)); ?> 
                        (<?php echo $net_balance < 0 ? 'Borçlu' : ($net_balance > 0 ? 'Alacaklı' : 'Dengede'); ?>)
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Cari Hareketleri: <?php echo $cari->FirmaAdi . ($cari->YetkiliAdi ? " ({$cari->YetkiliAdi})" : ""); ?>
                </h2>
                <div class="text-muted mt-1"><?php echo $cari->Telefon; ?> | <?php echo $cari->Email; ?></div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="?p=cari/list" class="btn btn-secondary">
                        <i class="ti ti-arrow-left icon"></i> Geri
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#movement-modal">
                        <i class="ti ti-plus icon"></i> Yeni Hareket
                    </button>
                    <button class="btn btn-info" onclick="window.print()">
                        <i class="ti ti-printer icon"></i> Yazdır / Ekstre
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-3 d-print-none">
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-red text-white avatar"><i class="ti ti-arrow-up-right icon"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Toplam Verilen (Borç)</div>
                            <div class="text-muted"><?php echo Helper::formattedMoney($total_borc); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-green text-white avatar"><i class="ti ti-arrow-down-left icon"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Toplam Alınan (Alacak)</div>
                            <div class="text-muted"><?php echo Helper::formattedMoney($total_alacak); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="<?php echo $net_balance < 0 ? 'bg-red' : ($net_balance > 0 ? 'bg-green' : 'bg-secondary'); ?> text-white avatar"><i class="ti ti-scale icon"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Güncel Bakiye</div>
                            <div class="text-muted">
                                <?php echo Helper::formattedMoney(abs($net_balance)) . ($net_balance < 0 ? ' (Borçlu)' : ($net_balance > 0 ? ' (Alacaklı)' : ' (Dengede)')); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mt-2">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Belge No</th>
                                <th>Açıklama</th>
                                <th class="text-end">Borç</th>
                                <th class="text-end">Alacak</th>
                                <th class="text-end">Bakiye</th>
                                <th class="text-end d-print-none">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $running_balance = 0;
                            foreach ($movements as $m): 
                                $mid = Security::encrypt($m->id);
                                $running_balance += ($m->borc - $m->alacak);
                            ?>
                            <tr>
                                <td><?php echo Date::dmY($m->islem_tarihi); ?></td>
                                <td><?php echo $m->belge_no; ?></td>
                                <td><?php echo $m->aciklama; ?></td>
                                <td class="text-end text-danger"><?php echo $m->borc > 0 ? Helper::formattedMoney($m->borc) : '-'; ?></td>
                                <td class="text-end text-success"><?php echo $m->alacak > 0 ? Helper::formattedMoney($m->alacak) : '-'; ?></td>
                                <td class="text-end <?php echo $running_balance < 0 ? 'text-danger' : ($running_balance > 0 ? 'text-success' : ''); ?>">
                                    <?php echo Helper::formattedMoney(abs($running_balance)) . ($running_balance < 0 ? ' (A)' : ($running_balance > 0 ? ' (B)' : '')); ?>
                                </td>
                                <td class="text-end d-print-none">
                                    <div class="dropdown">
                                        <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">İşlem</button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item edit-movement" href="#" data-id="<?php echo $mid; ?>">
                                                <i class="ti ti-edit icon me-3"></i> Güncelle
                                            </a>
                                            <a class="dropdown-item delete-movement" href="#" data-id="<?php echo $mid; ?>">
                                                <i class="ti ti-trash icon me-3"></i> Sil
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="strong">
                                <td colspan="3" class="text-end">TOPLAM</td>
                                <td class="text-end text-danger"><?php echo Helper::formattedMoney($total_borc); ?></td>
                                <td class="text-end text-success"><?php echo Helper::formattedMoney($total_alacak); ?></td>
                                <td class="text-end <?php echo $running_balance < 0 ? 'text-danger' : ($running_balance > 0 ? 'text-success' : ''); ?>">
                                    <?php echo Helper::formattedMoney(abs($running_balance)) . ($running_balance < 0 ? ' (A)' : ($running_balance > 0 ? ' (B)' : '')); ?>
                                </td>
                                <td class="d-print-none"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "modals/movement-modal.php"; ?>

<script>
$(document).ready(function() {
    $(document).on('click', '.edit-movement', function() {
        var id = $(this).data('id');
        $.ajax({
            url: '/api/cari/get_movement.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                var data = JSON.parse(response);
                if(data.status === 'success') {
                    $('#movement_id').val(id);
                    $('#islem_tarihi').val(data.movement.islem_tarihi_fmt);
                    $('#belge_no').val(data.movement.belge_no);
                    $('#aciklama').val(data.movement.aciklama);
                    
                    var amount = 0;
                    if(data.movement.borc > 0) {
                        $('input[name="mType"][value="borc"]').prop('checked', true);
                        amount = data.movement.borc;
                    } else {
                        $('input[name="mType"][value="alacak"]').prop('checked', true);
                        amount = data.movement.alacak;
                    }
                    $('#mAmount').val(amount);
                    
                    $('#movement-modal .modal-title').text('Hareket Güncelle');
                    $('#movement-modal').modal('show');
                }
            }
        });
    });

    $('#saveMovement').click(function() {
        var type = $('input[name="mType"]:checked').val();
        var amount = $('#mAmount').val();
        
        var formData = {
            id: $('#movement_id').val(),
            cari_id: '<?php echo $_GET['id']; ?>',
            islem_tarihi: $('#islem_tarihi').val(),
            belge_no: $('#belge_no').val(),
            aciklama: $('#aciklama').val(),
            borc: type === 'borc' ? amount : 0,
            alacak: type === 'alacak' ? amount : 0
        };

        $.ajax({
            url: '/api/cari/save_movement.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                var data = JSON.parse(response);
                if(data.status === 'success') {
                    Swal.fire('Başarılı', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Hata', data.message, 'error');
                }
            }
        });
    });

    $(document).on('click', '.delete-movement', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Hareket kaydı silinecektir!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api/cari/delete_movement.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if(data.status === 'success') {
                            Swal.fire('Silindi', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Hata', data.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>
