<?php
require_once "Model/Cari.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Security;

//Kullanıcının firmasını kontrol eder
$Auths->checkFirmReturn();

//Yetki kontrolü
$perm->checkAuthorize("cari_takip");

$cariModel = new Cari();
$cariler = $cariModel->getCariByFirm($_SESSION["firm_id"]);
$totals = $cariModel->getFirmTotals($_SESSION["firm_id"]);
$total_balance = ($totals->total_borc ?? 0) - ($totals->total_alacak ?? 0);

?>
<div class="container-xl mt-3">
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar"><i class="ti ti-users icon"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Toplam Cari</div>
                            <div class="text-muted"><?php echo count($cariler); ?> Kayıt</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-red text-white avatar"><i class="ti ti-arrow-up-right icon"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Toplam Borç (Verilen)</div>
                            <div class="text-muted"><?php echo Helper::formattedMoney($totals->total_borc ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-green text-white avatar"><i class="ti ti-arrow-down-left icon"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Toplam Alacak (Alınan)</div>
                            <div class="text-muted"><?php echo Helper::formattedMoney($totals->total_alacak ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-yellow text-white avatar"><i class="ti ti-scale icon"></i></span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Net Bakiye</div>
                            <div class="text-muted <?php echo $total_balance < 0 ? 'text-danger' : ($total_balance > 0 ? 'text-success' : ''); ?>">
                                <?php echo Helper::formattedMoney(abs($total_balance)) . ($total_balance < 0 ? ' (A)' : ($total_balance > 0 ? ' (B)' : '')); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cari Listesi</h3>
                    <div class="card-actions">
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cari-modal">
                            <i class="ti ti-plus icon me-2"></i> Yeni Cari Ekle
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="cariTable" class="table card-table text-nowrap table-hover datatable">
                        <thead>
                            <tr>
                                <th style="width:5%">ID</th>
                                <th>Firma Adı</th>
                                <th>Yetkili</th>
                                <th>Telefon</th>
                                <th>Email</th>
                                <th>Adres</th>
                                <th class="text-end">Bakiye</th>
                                <th style="width:7%" class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cariler as $cari): 
                                $id = Security::encrypt($cari->id);
                                $balance = $cariModel->getBalance($cari->id);
                                ?>
                                <tr>
                                    <td><?php echo $cari->id; ?></td>
                                    <td>
                                        <a href="?p=cari/movements&id=<?php echo $id; ?>" class="text-reset">
                                            <?php echo $cari->FirmaAdi; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $cari->YetkiliAdi; ?></td>
                                    <td><?php echo $cari->Telefon; ?></td>
                                    <td><?php echo $cari->Email; ?></td>
                                    <td><?php echo $cari->Adres; ?></td>
                                    <td class="text-end <?php echo $balance < 0 ? 'text-danger' : ($balance > 0 ? 'text-success' : ''); ?>">
                                        <?php echo Helper::formattedMoney(abs($balance)) . ($balance < 0 ? ' (A)' : ($balance > 0 ? ' (B)' : '')); ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item edit-cari" href="#" data-id="<?php echo $id; ?>">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item" href="?p=cari/movements&id=<?php echo $id; ?>">
                                                    <i class="ti ti-list icon me-3"></i> Hareketler
                                                </a>
                                                <a class="dropdown-item delete-cari" href="#" data-id="<?php echo $id; ?>">
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

<?php include_once "modals/cari-modal.php"; ?>

<script>
    $(document).ready(function() {
        // DataTables is initialized automatically by app.js because of the 'datatable' class
        
        $(document).on('click', '.edit-cari', function() {
            var id = $(this).data('id');
            $.ajax({
                url: '/api/cari/get_cari.php',
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    var data = JSON.parse(response);
                    if(data.status === 'success') {
                        $('#cari_id').val(id);
                        $('#FirmaAdi').val(data.cari.FirmaAdi);
                        $('#YetkiliAdi').val(data.cari.YetkiliAdi);
                        $('#Telefon').val(data.cari.Telefon);
                        $('#Email').val(data.cari.Email);
                        $('#Adres').val(data.cari.Adres);
                        $('#notlar').val(data.cari.notlar);
                        $('#cari-modal .modal-title').text('Cari Güncelle');
                        $('#cari-modal').modal('show');
                    }
                }
            });
        });

        $('#saveCari').click(function() {
            var formData = $('#cariForm').serialize();
            $.ajax({
                url: '/api/cari/save_cari.php',
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

        $(document).on('click', '.delete-cari', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Emin misiniz?',
                text: "Cari kaydı silinecektir!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Evet, sil!',
                cancelButtonText: 'Vazgeç'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/api/cari/delete_cari.php',
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
