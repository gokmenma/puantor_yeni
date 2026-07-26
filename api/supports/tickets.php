<?php
//!defined("ROOT") ? define("ROOT", dirname(dirname(__DIR__))) : null;
use App\Helper\Security;

require_once __DIR__ . '/../../Database/require.php';
require_once __DIR__ . '/../../Model/SupportsModel.php';
require_once __DIR__ . '/../../Model/SupportsMessagesModel.php';
require_once __DIR__ . "/../../mail-settings.php";

$Supports = new SupportsModel();
$SupportsMessages = new SupportsMessagesModel();

if (isset($_POST['action']) && $_POST['action'] == 'saveSupportTicket') {
    $data = [
        'user_id' => $_SESSION['user']->id,
        'subject' => $_POST['subject'],
        'message' => $_POST['message'],
        'status' => 0,
        'program_name' => 'puantor'
    ];

    try {
        $lastInsertId = $Supports->saveWithAttr($data);

        // Destek talebi oluşturulduktan sonra destek mesajı oluşturuluyor
        $data = [
            'support_id' => Security::decrypt($lastInsertId),
            'message' => $_POST['message']
        ];
        $SupportsMessages->saveWithAttr($data);

        $status = "success";
        $message = "Destek talebiniz başarıyla oluşturuldu.";


        $ticket_number = Security::decrypt($lastInsertId);
        $ticket_subject = $_POST["subject"];
        $user_name = $_SESSION["user"]->full_name;
        $user_email = $_SESSION["user"]->email;
        $message_body = strip_tags($_POST["message"]);

        
        // ticket-mail.php dosyasını dahil et ve değişkenleri geçir
        ob_start();
        include(ROOT . "/pages/supports/ticket-mail.php");
        $body = ob_get_clean();


        // Alıcılar
        $mail->setFrom('sifre@puantor.com.tr', 'Yeni Destek Talebi');
        $mail->addReplyTo($_SESSION["user"]->email, $_SESSION["user"]->full_name);
        $mail->addAddress('destek@puantor.com.tr');
        $mail->addAddress('mbeyazilim@gmail.com');
        $mail->isHTML(true);

        $mail->Subject = 'Yeni Destek Talebi Bildirimi';
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        //Karakter seti
        $mail->CharSet = 'UTF-8';

        $mail->send();



    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        'status' => $status,
        'message' => $message
    ];
    echo json_encode($res);

}

if (isset($_POST['action']) && $_POST['action'] == 'newTicketMessage') {
    $support_id = Security::decrypt($_POST['support_id']);
    
    $is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
    $author = $is_superadmin ? ($_SESSION['user']->full_name ?? 'Destek Ekibi') : 0;

    $data = [
        'support_id' => $support_id,
        'message' => $_POST['message'],
        'author' => $author
    ];

    try {
        $lastInsertId = $SupportsMessages->saveWithAttr($data);
        $status = "success";
        $message = "Mesajınız başarıyla gönderildi.";

        try {

            $ticket_number = Security::decrypt($lastInsertId);
            $support_ticket = $Supports->find($support_id);
            $ticket_subject = $support_ticket->subject;
            $message_body = strip_tags($_POST["message"]);

            if ($is_superadmin) {
                require_once ROOT . '/Model/UserModel.php';
                $UserModel = new UserModel();
                $ticket_user = $UserModel->find($support_ticket->user_id);
                $user_name = $ticket_user->full_name;
                $user_email = $ticket_user->email;

                $mail_header_title = "Destek Talebiniz Yanıtlandı";
                $mail_intro = "Destek ekibimiz talebinize yeni bir yanıt gönderdi. Detayları aşağıda bulabilirsiniz:";
                $mail_info_section_title = "Destek Talebi Sahibi";
                $mail_sender_title = "Ad Soyad:";
                $mail_sender_email_title = "E-posta:";
                $mail_ticket_number_title = "Talep Numarası:";
                $mail_footer_text = "Destek panelinizden talebinizi görüntüleyebilir ve gerektiğinde tekrar yanıt yazabilirsiniz.";

                ob_start();
                include(ROOT . "/pages/supports/ticket-mail.php");
                $body = ob_get_clean();

                $mail->setFrom('sifre@puantor.com.tr', 'Puantor Destek Ekibi');
                $mail->addReplyTo('destek@puantor.com.tr', 'Puantor Destek');
                $mail->addAddress($user_email, $user_name);
                $mail->Subject = 'Destek Talebiniz Yanıtlandı - ' . $ticket_subject;
            } else {
                $user_name = $_SESSION["user"]->full_name;
                $user_email = $_SESSION["user"]->email;

                ob_start();
                include(ROOT . "/pages/supports/ticket-mail.php");
                $body = ob_get_clean();

                $mail->setFrom('sifre@puantor.com.tr', 'Yeni Destek mesajı');
                $mail->addReplyTo($_SESSION["user"]->email, $_SESSION["user"]->full_name);
                $mail->addAddress('destek@puantor.com.tr');
                $mail->addAddress('mbeyazilim@gmail.com');
                $mail->Subject = 'Destek Mesajı Bildirimi';
            }

            $mail->isHTML(true);
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $mail->CharSet = 'UTF-8';

            $mail->send();
        } catch (Exception $e) {
            // E-posta gönderimindeki hatayı yut ama hata logu yaz
            system_log_exception($e, ['operation' => 'support_ticket_mail']);
        }

    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }


    $res = [
        'status' => $status,
        'message' => $message
    ];
    echo json_encode($res);
}

if (isset($_POST['action']) && $_POST['action'] == 'closeTicket') {
    $id = Security::decrypt($_POST['id']);
    $data = [
        'id' => $id,
        'status' => 1
    ];

    try {
        $Supports->saveWithAttr($data);
        $status = "success";
        $message = "Destek talebi başarıyla kapatıldı.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        'status' => $status,
        'message' => $message
    ];
    echo json_encode($res);
}
