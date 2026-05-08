<?php

use App\Helper\Security;

require_once 'Model/SupportsModel.php';
require_once 'Model/SupportsMessagesModel.php';

$Auths->checkFirmReturn();
$perm->checkAuthorize('supports_tickets_view');

$id = isset($_GET['id']) ? $_GET['id'] : 0;

$support_id = Security::decrypt($id);

$Supports = new SupportsModel();
$SupportsMessages = new SupportsMessagesModel();

$support = $Supports->find($support_id);
$messages = $SupportsMessages->getMessagesByTicketId($support_id);

// Son mesajın author bilgisi boş ise bu mesajı kullanıcı göndermiştir ve destek ekibinin bu mesajı cevaplaması gerekmektedir.
$lastMessage = $SupportsMessages->getLastMessageByTicketId($support_id);
// Eğer son mesaj destek ekibi tarafından gönderilmiş ise yeni mesaj gönderme butonunu gösterme
$author = $lastMessage->author ?? 0;
if ($author == 0) {
    $showNewMessage = false;
    $icon = 'headset';
    $bg_color = 'info';
} else {
    $showNewMessage = true;
    $icon = 'user';
    $bg_color = 'success';
}

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
                <h4 class="alert-title">Destek Talep Mesajları</h4>
                <div class="text-secondary">Bu sayfada destek taleplerinizin önceki mesajlarını görebilir ve yeni mesaj
                    yazabilirsiniz.
                    <p class="m-0">Yeni mesaj yazmadan önce destek talebinin cevaplanmış olması gerekmektedir.</p>
                    <p>Açık talepler 1 saat sonra otomatik olarak kapatılacaktır!</p>
                </div>
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
    <div class="row mb-3">

        <div class="col-auto ms-auto d-print-none me-2">
            <button type="button" class="btn btn-outline-secondary route-link" data-page="supports/tickets">
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
                        <time class="cbp_tmtime" datetime="2017-11-04T03:45"><span><?php echo '13:02'; ?></span>
                            <span><?php echo date("d.m.Y"); ?></span></time>
                        <div class="cbp_tmicon bg-<?php echo $bg_color ?>">
                            <i class="ti ti-<?php echo $icon ?>"></i>
                        </div>
                        <div class="cbp_tmlabel">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Yeni Mesaj</h3>
                                    <div class="col-auto ms-auto d-print-none me-2">
                                        <?php
                                        if ($showNewMessage) {
                                            ?>
                                            <button type="button" id="send_new_ticket_message" class="btn btn-primary">
                                                <i class="ti ti-send icon me-2"></i>
                                                Gönder
                                            </button>
                                        <?php } else {
                                            echo '<span class="text-danger blinking-text">Destek ekibinin cevabı bekleniyor.</span>';
                                        } ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form action="" id="newTicketMessageForm">
                                        <input type="hidden" name="support_id" id="support_id" value="<?php echo $id; ?>">
                                        <div class="row mb-3">

                                            <label for="">Mesaj</label>
                                            <textarea name="message" class="form-control summernote"
                                                style="max-height: 120px;" required></textarea>
                                        </div>
                                        <div class="row">

                                            <!-- file input -->
                                            <div class="col-md-12">
                                                <label for="">Dosya Ekle</label>
                                                <input type="file" class="form-control d-block" name="file">
                                            </div>
                                            <!-- Eklenen dosya isimlerini göster -->
                                            <div class="col-md-12 mt-3">
                                                <span id="result"></span>

                                            </div>
                                        </div>
                                    </form>

                                </div>



                            </div>
                    </li>
                <?php } else { ?>
                    <li>

                        <div class="cbp_tmlabel">
                            <div class="alert alert-warning bg-white" role="alert">
                                <div class="d-flex">
                                    <div>
                                        <!-- Download SVG icon from http://tabler-icons.io/i/alert-triangle -->
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
                                        <div class="text-secondary">Bu destek bildirimi kapatılmıştır.Yeni bir destek bildirimi açabilirsiniz!</div>
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
                        $author = $message->author;

                    } else {
                        $icon = 'user';
                        $bg_color = 'success';
                        $author = "Kullanıcı";
                    }
                    ;

                    // DateTime nesnesi oluştur
                    $date = new DateTime($message->created_at);

                    // Sadece saati al
                    $time = $date->format('H:i:s');
                    $day = $date->format('d.m.Y');
                    ?>
                    <li>
                        <time class="cbp_tmtime" datetime="2017-11-04T03:45"><span><?php echo $time; ?></span>
                            <span><?php echo $day; ?></span></time>
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