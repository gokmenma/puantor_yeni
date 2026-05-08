<?php

require_once 'Model/SupportsModel.php';
require_once 'App/Helper/date.php';


use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$Supports = new SupportsModel();

$supports = $Supports->getSupportsByUser();

$Auths->checkFirmReturn();
$perm->checkAuthorize('supports_tickets_view');

?>
<style>
    .card {
        height: 42rem;
        max-height: 42rem;
    }

    .list-group-item {
        padding: 1rem;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
    }
</style>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Geçmiş Destek Talepleri</h3>
                        </div>

                        <div class="list-group list-group-flush list-group-hoverable overflow-auto">
                            <?php
                            foreach ($supports as $support) { ?>

                                <div class="list-group-item route-link"
                                    data-page="supports/ticket-view&id=<?php echo Security::encrypt($support->id) ?>">
                                    <div class="row align-items-center">
                                        <?php
                                        $badge_color = $support->status == 0 ? 'red' : 'green';
                                        ?>
                                        <div class="col-auto"><span class="badge bg-<?php echo $badge_color; ?>"></span>
                                        </div>
                                        <div class="col-auto">
                                            <a href="#">
                                                <span class="avatar">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                            </a>
                                        </div>
                                        <div class="col text-truncate">
                                            <a href="#" class="text-reset d-block"><?php echo $support->subject; ?></a>
                                            <div class="d-block text-secondary text-truncate mt-n1">
                                                <?php echo preg_replace('/\?/', '', strip_tags($support->message)); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <span class="list-group-item-actions">
                                                <?php echo Date::dmY($support->created_at); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Yeni Destek Talebi Oluştur</h4>
                            <!-- Kaydet Butonu -->
                            <div class="col-auto ms-auto">

                                <button class="btn btn-primary float-end" id="send-ticket">
                                    <i class="ti ti-device-floppy icon"></i>
                                    Kaydet
                                </button>
                            </div>

                        </div>
                        <div class="card-body">
                            <!-- **************FORM**************** -->
                            <form action="" id="supportTicketForm">
                                <!--********** HIDDEN ROW************** -->
                                <div class="row d-none">
                                    <div class="col-md-4">
                                        <input type="text" name="id" class="form-control" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="action" value="saveSupportTicket" class="form-control">
                                    </div>
                                </div>
                                <!--********** HIDDEN ROW************** -->
                                <div class="container p-5">


                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="">Adı</label>
                                            <input type="text" name="user_name" disabled readonly class="form-control"
                                                value="<?php echo $user->full_name ?? '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="">Email</label>
                                            <input type="text" name="email" disabled readonly class="form-control"
                                                value="<?php echo $user->email ?? '' ?>">
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <label for="">Konu</label>
                                            <input type="text" name="subject" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="row mt-3">



                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="summernote">Mesajınız</label>
                                                <textarea id="summernote" name="message" class="summernote"></textarea>
                                                <input type="hidden" id="summernoteContent" name="summernoteContent">
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
</div>