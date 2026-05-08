<?php
require_once "App/Helper/helper.php";
use App\Helper\Helper;

require_once 'Model/DefinesModel.php';
$defineObj = new DefinesModel();
$id = $_GET['id'] ?? 0;
$incexp = $defineObj->find($id);

$pageTitle = $id > 0 ? 'Geir-Gider Türü Güncelleme' : 'Yeni Gelir-Gider Türü';

?>
<div class='page-wrapper'>
    <!-- Page header -->
    <div class='page-header d-print-none'>
        <div class='container-xl'>
            <div class='row g-2 align-items-center'>
                <div class='col'>
                    <h2 class='page-title'>
                        <?php echo $pageTitle;
                        ?>
                    </h2>
                </div>

                <!-- Page title actions -->
                <div class='col-auto ms-auto d-print-none'>
                    <button type='button' class='btn btn-outline-secondary route-link' data-page='defines/incexp/list'>
                        <i class='ti ti-list icon me-2'></i>
                        Listeye Dön
                    </button>
                </div>
                <div class='col-auto ms-auto d-print-none'>
                    <button type='button' class='btn btn-primary' id='saveIncExpType'>
                        <i class='ti ti-device-floppy icon me-2'></i>
                        Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class='page-body'>
        <div class='container-xl'>
            <div class='col-md-12'>
                <div class='card'>
                    <div class='card-body'>
                        <!-- **************FORM**************** -->
                        <form action='' id='incExpForm'>
                            <!--********** HIDDEN ROW************** -->
                            <div class='row d-none'>
                                <div class='col-md-4'>
                                    <input type='text' name='id' class='form-control'
                                        value="<?php echo $incexp->id ?? 0 ?>">
                                </div>
                                <div class='col-md-4'>
                                    <input type='text' name='action' value='saveIncExpType' class='form-control'>
                                </div>
                            </div>
                            <!--********** HIDDEN ROW************** -->
                            <div class='row'>

                                <div class='col-md-2'>
                                    <label class='form-label'>Adı:</label>
                                </div>
                                <div class='col-md-4'>
                                    <input type='text' name='incexp_name' class='form-control' value="<?php echo $incexp->name ?? ''; ?>">
                                </div>

                                <div class='col-md-2'>
                                    <label class='form-label'>Tipi</label>
                                </div>
                                <div class='col-md-4'>
                                    <?php echo Helper::incExpTypeSelect("incexp_type", $incexp->type_id ?? 1) ?>
                                </div>

                            </div>
                            <div class="row mt-2">
                                <div class="col-md-2">
                                    <label class="form-label">Açıklama</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" name="description" class="form-control"
                                        value="<?php echo $incexp->description ?? '' ?>">

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