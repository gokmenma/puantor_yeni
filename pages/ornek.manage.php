<?php
require_once "Model/Defines.php";
$defineObj = new Cases();
$id = $_GET['id'] ?? 0;
$incexp = $defineObj->find($id);

$pageTitle = $id > 0 ? "Geir-Gider Türü Güncelleme" : "Yeni Gelir-Gider Türü";

?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <?php echo $pageTitle; ?>
                    </h2>
                </div>

                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-outline-secondary route-link" data-page="defines/incexp/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="saveIncExpType">
                        <i class="ti ti-device-floppy icon me-2"></i>
                        Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <!-- **************FORM**************** -->
                        <form action="" id="incExpForm">
                            <!--********** HIDDEN ROW************** -->
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" name="id" class="form-control" value="<?php echo $id ?? ''?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="action" value="saveIncExpType" class="form-control">
                                </div>
                            </div>
                            <!--********** HIDDEN ROW************** -->
                        </form>
                         <!-- **************FORM**************** -->

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>