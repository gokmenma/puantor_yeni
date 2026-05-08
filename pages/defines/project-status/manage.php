<?php

require_once ROOT . "/Model/DefinesModel.php";
use App\Helper\Security;

$ProjectStatus = new DefinesModel();  

//Sayfa başlarında eklenecek alanlar
$perm->checkAuthorize("project_status_add_update");
$id = isset($_GET["id"]) ? Security::decrypt($_GET['id']) : 0;
$new_id = isset($_GET["id"]) ? $_GET['id'] : 0;

//Eğer url'den id yazılmışsa veya id boş ise projeler sayfasına gider
if($id == null && isset($_GET['id'])) {
    header("Location: /index.php?p=defines/project-status/list");
    exit;
}

$project_status = $ProjectStatus->find($id);

$pageTitle = $id > 0 ? "Proje Durumu Güncelleme" : "Yeni Proje Durumu";

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
                        data-page="defines/project-status/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="saveProjectStatus">
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
                        <form action="" id="projectStatusForm">
                            <!--********** HIDDEN ROW************** -->
                            <div class="row d-none">
                                <div class="col-md-4">
                                    <input type="text" name="id" id="id" class="form-control"
                                        value="<?php echo $new_id ?? '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="action" value="saveProjectStatus" class="form-control">
                                </div>
                            </div>
                            <!--********** HIDDEN ROW************** -->
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="form-label">İş Grubu Adı</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" name="statu_name" class="form-control"
                                        value="<?php echo $project_status->name ?? '' ?>"
                                        placeholder="Örn: Devam ediyor">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="form-label">Açıklama</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" name="description" class="form-control" 
                                        value="<?php echo $project_status->description ?? '' ?>">
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