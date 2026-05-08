<?php


require_once "Model/JobGroupsModel.php";
$JobGroups = new JobGroupsModel();

use App\Helper\Security;




//Sayfa başlarında eklenecek alanlar
$perm->checkAuthorize("job_groups_add_update");
$id = isset($_GET["id"]) ? Security::decrypt($_GET['id']) : 0;
$new_id = isset($_GET["id"]) ? $_GET['id'] : 0;

//Eğer url'den id yazılmışsa veya id boş ise projeler sayfasına gider
if($id == null && isset($_GET['id'])) {
    header("Location: /index.php?p=defines/job-groups/list");
    exit;
}

$jobGroups = $JobGroups->find($id);

$pageTitle = $id > 0 ? "İş Grubu Güncelleme" : "Yeni İş Grubu";

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
                        data-page="defines/job-groups/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="saveJobGroups">
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
                        <form action="" id="jobGroupsForm">
                            <!--********** HIDDEN ROW************** -->
                            <div class="row d-none">
                                <div class="col-md-4">
                                    <input type="text" name="id" id="id" class="form-control"
                                        value="<?php echo $id ?? '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="action" value="saveJobGroups" class="form-control">
                                </div>
                            </div>
                            <!--********** HIDDEN ROW************** -->
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="form-label">İş Grubu Adı</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" name="job_group_name" class="form-control"
                                        value="<?php echo $jobGroups->group_name ?? '' ?>"
                                        placeholder="Örn: Alçıpancı, Sıvacı, Boyacı">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="form-label">Açıklama</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" name="description" class="form-control"
                                        value="<?php echo $jobGroups->description ?? '' ?>">
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