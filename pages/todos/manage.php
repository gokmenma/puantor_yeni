<?php

require_once "Model/TodoModel.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/date.php";
require_once "App/Helper/projects.php";


use App\Helper\Security;
use App\Helper\Date;
use App\Helper\Helper;


//Yetki kontrolü
$Auths->checkAuthorize('todo_manage');


$Todos = new Todo();
$projectHelper = new ProjectHelper();
$id = isset($_GET["id"]) ? Security::decrypt($_GET['id']) : 0;

$todo = $Todos->find($id);

$pageTitle = $id > 0 ? "Görev Güncelleme" : "Yeni Görev";

?>


<style>
    .row {
        display: flex;
        align-items: center;
    }
</style>


<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">

            <!-- Alert component'i dahil et -->
            <?php
            $title = "Yapılacaklar Listesi!";
            $text = "Firmanız yapılacak iş ve işlemleri buradan takip edebilirsiniz!";
            require_once 'pages/components/alert.php'
                ?>
            <!-- Alert  -->

        </div>


        <div class="container-xl">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $pageTitle; ?></h3>

                        <!-- Page title actions -->
                        <div class="col-auto ms-auto d-print-none">
                            <button type="button" class="btn btn-outline-secondary route-link" data-page="todos/list">
                                <i class="ti ti-list icon me-2"></i>
                                Listeye Dön
                            </button>
                            <button type="button" class="btn btn-primary" id="saveTodo">
                                <i class="ti ti-device-floppy icon me-2"></i>
                                Kaydet
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- **************FORM**************** -->
                        <form action="" id="todoForm">
                            <!--********** HIDDEN ROW************** -->
                            <div class="row d-none">
                                <div class="col-md-4">
                                    <input type="text" name="id" class="form-control" value="<?php echo $id ?? '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="action" value="saveTodo" class="form-control">
                                </div>
                            </div>
                            <!--********** HIDDEN ROW************** -->
                            <div class="row mb-3">
                                <!-- Proje Adı -->
                                <div class="col-md-2">
                                    <label class="form-label">Proje Adı</label>
                                </div>
                                <div class="col-md-4">
                                    <?php echo $projectHelper->getProjectSelect("projects", $todo->project_id ?? 0); ?>
                                </div>

                                <!-- Konu -->
                                <div class="col-md-2">
                                    <label class="form-label">Başlık</label>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="title" class="form-control"
                                        value="<?php echo $todo->title ?? '' ?>">
                                </div>


                            </div>
                            <div class="row">

                                <!-- Yapılacak Adı  -->
                                <div class="col-md-2">
                                    <label class="form-label">Yapılacak Adı</label>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="subject" class="form-control"
                                        value="<?php echo $todo->subject ?? '' ?>">
                                </div>

                                <!-- Son Tarih -->
                                <div class="col-md-2">
                                    <label class="form-label">Son Tarih</label>
                                </div>
                                <div class="col-md-4">
                                    <input type="date" name="due_date" class="form-control flatpickr"
                                        value="<?php echo $todo->due_date ?? '' ?>">
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