<?php

use App\Helper\Security;

require_once 'Model/SupportsModel.php';
require_once 'Model/SupportsMessagesModel.php';
require_once 'Model/UserModel.php';

// Yetki kontrolü (Sadece superadmin girebilir)
if (($_SESSION['user']->superadmin ?? 0) != 1) {
    header("Location: index.php?p=authorize");
    exit();
}

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$support_id = Security::decrypt($id);

$Supports = new SupportsModel();
$SupportsMessages = new SupportsMessagesModel();
$UserModel = new UserModel();

$support = $Supports->find($support_id);
if (!$support) {
    echo '<div class="container-xl p-5 text-center">Destek talebi bulunamadı.</div>';
    return;
}

$messages = $SupportsMessages->getMessagesByTicketId($support_id);
$ticket_user = $UserModel->find($support->user_id);

$showNewMessage = ($support->status == 0);
$icon = 'user';
$bg_color = 'success';

?>

<div class="container-xl">
    <div class="alert alert-info bg-white alert-dismissible mt-3" role="alert">
        <div class="d-flex">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon alert-icon">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                    <path d="M12 9h.01"></path>
                    <path d="M11 12h1v4h1"></path>
                </svg>
            </div>
            <div>
                <h4 class="alert-title">Destek Talebi Cevaplama Paneli (Admin)</h4>
                <div class="text-secondary">
                    Bu talep <strong><?php echo htmlspecialchars($ticket_user->full_name ?? 'Bilinmeyen Kullanıcı'); ?></strong> (<?php echo htmlspecialchars($ticket_user->email ?? '-'); ?>) tarafından oluşturulmuştur.
                    <p class="m-0">Yeni bir yanıt yazabilir veya çözümlenmiş talepleri kapatabilirsiniz.</p>
                </div>
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
    
    <div class="row mb-3">
        <div class="col-auto ms-auto d-print-none me-2">
            <button type="button" class="btn btn-outline-secondary route-link" data-page="supports/admin-tickets">
                <i class="ti ti-list icon me-2"></i>
                Listeye Dön
            </button>
            <?php if ($support->status == 0) { ?>
                <button type="button" class="btn btn-success" id="close_ticket">
                    <i class="ti ti-check icon me-2"></i>
                    Bildirimi Kapat
                </button>
            <?php } ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-10">
            <ul class="cbp_tmtimeline">
                <?php if ($support->status == 0) { ?>
                    <li>
                        <time class="cbp_tmtime" datetime="<?php echo date("Y-m-d H:i"); ?>">
                            <span><?php echo date("H:i"); ?></span>
                            <span><?php echo date("d.m.Y"); ?></span>
                        </time>
                        <div class="cbp_tmicon bg-info">
                            <i class="ti ti-headset"></i>
                        </div>
                        <div class="cbp_tmlabel">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Yanıt Gönder</h3>
                                    <div class="col-auto ms-auto d-print-none me-2">
                                        <button type="button" id="send_new_ticket_message" class="btn btn-primary">
                                            <i class="ti ti-send icon me-2"></i>
                                            Gönder
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form action="" id="newTicketMessageForm">
                                        <input type="hidden" name="support_id" id="support_id" value="<?php echo $id; ?>">
                                        <div class="row mb-3">
                                            <label for="">Mesajınız</label>
                                            <textarea name="message" class="form-control summernote"
                                                style="max-height: 120px;" required></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label for="">Dosya Ekle</label>
                                                <input type="file" class="form-control d-block" name="file">
                                            </div>
                                            <div class="col-md-12 mt-3">
                                                <span id="result"></span>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php } else { ?>
                    <li>
                        <div class="cbp_tmlabel">
                            <div class="alert alert-warning bg-white" role="alert">
                                <div class="d-flex">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" class="icon alert-icon">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M12 9v4"></path>
                                            <path
                                                d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z">
                                            </path>
                                            <path d="M12 16h.01"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="alert-title">Bilgi!</h4>
                                        <div class="text-secondary">Bu destek bildirimi kapatılmıştır.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php } ?>
                
                <?php
                foreach ($messages as $message) {
                    if ($message->author != 0) {
                        $icon = 'headset';
                        $bg_color = 'info';
                        $author = htmlspecialchars($message->author);
                    } else {
                        $icon = 'user';
                        $bg_color = 'success';
                        $author = htmlspecialchars($ticket_user->full_name ?? 'Kullanıcı');
                    }

                    $date = new DateTime($message->created_at);
                    $time = $date->format('H:i:s');
                    $day = $date->format('d.m.Y');
                    ?>
                    <li>
                        <time class="cbp_tmtime" datetime="<?php echo htmlspecialchars($message->created_at); ?>">
                            <span><?php echo $time; ?></span>
                            <span><?php echo $day; ?></span>
                        </time>
                        <div class="cbp_tmicon bg-<?php echo $bg_color ?>">
                            <i class="ti ti-<?php echo $icon ?>"></i>
                        </div>
                        <div class="cbp_tmlabel">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><?php echo $author; ?></h3>
                                </div>
                                <div class="card-body">
                                    <?php echo preg_replace('/\?/', '', strip_tags($message->message)); ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>
