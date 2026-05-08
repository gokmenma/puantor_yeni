<?php
require_once "Model/DefinesModel.php";

$defineObj = new DefinesModel();
$id = $_GET["id"] ?? 0;
$define = $defineObj->find($id);



?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Yeni Ürün
                    </h2>
                </div>

                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-outline-secondary route-link" data-page="defines/service-head/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="saveButton">
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
                        <form action="" id="serviceHeadForm">
                            <div class="row mt-3">
                                <input type="hidden" class="form-control mb-3" id="id" name="id" value="<?php echo $id ?>">
                                <div class="col-md-2">
                                    <label class="form-label">Servis Konusu Adı</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" class="form-control" name="service_head" value="<?php echo $define->title ?? "" ?>">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-2">
                                    <label class="form-label">Açıklama</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" class="form-control" name="description" value="<?php echo $define->description ?? "" ?>">
                                </div>
                            </div>
                        </form>
                    </div>


                </div>
            </div>
        </div>
    </div>
</div>