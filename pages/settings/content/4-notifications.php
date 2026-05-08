<?php

$get_value = $Settings->getSettingIdByUserAndAction($_SESSION["user"]->id, "loginde_mail_gonder")->set_value ?? null;
$send_email_on_login = $get_value ? "checked" : "";

?>

<div class="card-body">

    <h3 class="card-title mt-1">Mail Bildirimi</h3>
    <p class="card-subtitle">Hesabınıza, siz veya alt kullanıclar giriş yaptığında mail ile bildirim gelir</p>
    <div>
        <form action="#" id="notificationsForm">
            <label class="form-check form-switch form-switch-lg">
                <input class="form-check-input" name="send_email_on_login" id="send_email_on_login" type="checkbox"
                    <?php echo $send_email_on_login; ?>>
                <span class="form-check-label form-check-label-on">Mail gönder</span>
                <span class="form-check-label form-check-label-off">Mail Gönderme</span>
            </label>
        </form>
    </div>
</div>