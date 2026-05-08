<?php
require_once "Model/MissionHeaders.php";
$headersObj = new MissionHeaders();
$id = $_GET['id'] ?? 0;
$header = $headersObj->find($id);

$headers = $headersObj->getMissionHeadersFirm($firm_id);

$pageTitle = $id > 0 ? "Görev Başlığı Güncelleme" : "Yeni Görev Başlığı";

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
                    <button type="button" class="btn btn-outline-secondary route-link"
                        data-page="missions/headers/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="saveMissionHeader">
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
                        <form action="" id="missionHeadersForm">
                            <!--********** HIDDEN ROW************** -->
                            <div class="row d-none">
                                <div class="col-md-4">
                                    <input type="text" name="id" class="form-control"
                                        value="<?php echo $header->id ?? '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="action" value="saveMissionHeaders" class="form-control">
                                </div>
                            </div>
                            <!--********** HIDDEN ROW************** -->
                            <div class="row">


                                <div class="col-md-3">
                                    <!-- Buraya sürec adı ve sırası getirilecek -->
                                    <style>
                                        .headers {
                                            display: column;
                                            justify-content: space-between;
                                            border: 1px solid #ccc;
                                            padding: 10px;
                                            margin-bottom: 10px;
                                            border-radius: 6px;
                                        }
                                        .header span {
                                            font-size: 14px;
                                        }

                                        .header-item{
                                            border: 1px solid #ccc;
                                            padding: 10px;
                                            border-radius: 2px;
                                            margin: 2px;
                                        }

                                        .header-item:hover{
                                            background-color: #f5f5f5;

                                        }
                                    </style>

                                    <!--  -->



                                </div>

                                <div class="col-md-12">

                                    <div class="row mb-3">
                                        <?php $status = $header->status ?? 1; ?>
                                        <div class="col-md-2">
                                            <label class="form-label">Durum</label>

                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check me-5">
                                                <input class="form-check-input" type="radio" name="status" id="active"
                                                    value="1" <?php echo $status == 1 ? 'checked' : '' ?>>
                                                <label class="form-check" for="active">
                                                    Aktif
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">

                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="status" id="passive"
                                                    value="0" <?php echo $status == 0 ? 'checked' : '' ?>>
                                                <label class="form-check" for="passive">
                                                    Pasif
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-2">
                                            <label class="form-label">Süreç Adı</label>
                                        </div>
                                        <div class="col-md-10">
                                            <input type="text" name="header_name" class="form-control"
                                                value="<?php echo $header->header_name ?? '' ?>">
                                        </div>

                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-2">
                                            <label class="form-label">Açıklama</label>
                                        </div>
                                        <div class="col-md-10">
                                            <input type="text" name="description" class="form-control"
                                                value="<?php echo $header->description ?? '' ?>">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </form>
                        <!-- **************FORM**************** -->

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

