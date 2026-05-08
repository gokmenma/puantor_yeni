<?php
require_once "Model/MissionProcess.php";
$missionsObj = new MissionProcess();
$id = $_GET['id'] ?? 0;
$process = $missionsObj->find($id);

$processes = $missionsObj->getMissionProcessFirm($firm_id);

$pageTitle = $id > 0 ? "Görev Süreci Güncelleme" : "Yeni Görev Süreci";

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
                        data-page="missions/process/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="saveMissionProcess">
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
                        <form action="" id="missionProcessForm">
                            <!--********** HIDDEN ROW************** -->
                            <div class="row d-none">
                                <div class="col-md-4">
                                    <input type="text" name="id" class="form-control"
                                        value="<?php echo $process->id ?? '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="action" value="saveMissionProcess" class="form-control">
                                </div>
                            </div>
                            <!--********** HIDDEN ROW************** -->
                            <div class="row">


                                <div class="col-md-3">
                                    <!-- Buraya sürec adı ve sırası getirilecek -->
                                    <style>
                                        .process {
                                            display: column;
                                            justify-content: space-between;
                                            border: 1px solid #ccc;
                                            padding: 10px;
                                            margin-bottom: 10px;
                                            border-radius: 6px;
                                        }
                                        .process span {
                                            font-size: 14px;
                                        }

                                        .process-item{
                                            border: 1px solid #ccc;
                                            padding: 10px;
                                            border-radius: 2px;
                                            margin: 2px;
                                        }

                                        .process-item:hover{
                                            background-color: #f5f5f5;

                                        }
                                    </style>

                                    <div class="process " id="sortable">
                                        <div class="row d-flex mb-3">
                                            <div class="col-md-8">
                                                <strong>Süreç Adı</strong>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <strong>Sırası</strong>
                                            </div>
                                        </div>
                                        <?php
                                        foreach ($processes as $item) { ?>
                                            <div class="row d-flex mb-1 process-item" id="item-<?php echo $item->id; ?>">
                                                <div class="col-md-8">
                                                    <span ><?php echo $item->process_name; ?></span>
                                                </div>
                                                <div class="col-md-4 text-center">
                                                    <span class="process-order"><?php echo $item->process_order; ?></span>
                                                </div>
                                            </div>

                                        <?php } ?>

                                    </div>



                                </div>

                                <div class="col-md-9">

                                    <div class="row mb-3">
                                        <?php $status = $process->status ?? 1; ?>
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
                                        <div class="col-md-4">
                                            <input type="text" name="process_name" class="form-control"
                                                value="<?php echo $process->process_name ?? '' ?>">
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label">Sırası</label>

                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="process_order" id="process_order"
                                                class="form-control"
                                                value="<?php echo $process->process_order ?? '' ?>">
                                        </div>

                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-2">
                                            <label class="form-label">Açıklama</label>
                                        </div>
                                        <div class="col-md-10">
                                            <input type="text" name="description" class="form-control"
                                                value="<?php echo $process->description ?? '' ?>">
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

