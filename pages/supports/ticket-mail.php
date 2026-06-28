<?php
$mail_header_title = $mail_header_title ?? "Yeni Destek Bildirimi Alındı";
$mail_intro = $mail_intro ?? "Bir müşteriden yeni bir destek bildirimi alınmıştır. Detayları aşağıda bulabilirsiniz:";
$mail_info_section_title = $mail_info_section_title ?? "Müşteri Bilgileri";
$mail_sender_title = $mail_sender_title ?? "Ad Soyad:";
$mail_sender_email_title = $mail_sender_email_title ?? "E-posta:";
$mail_ticket_number_title = $mail_ticket_number_title ?? "Bildirim Numarası:";
$mail_footer_text = $mail_footer_text ?? "Lütfen müşteri ile en kısa sürede iletişime geçilmesini sağlayın. Müşteri memnuniyeti için bu bildirimle ilgilenmeniz önemlidir.";
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Bildirimi</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f7f7f7; padding: 20px;">
    <table width="100%" cellpadding="0" cellspacing="0"
        style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 6px; border: 1px solid #e0e0e0; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">
        <tr>
            <td
                style="padding: 20px; background-color: #0073e6; color: #ffffff; text-align: center; border-top-left-radius: 6px; border-top-right-radius: 6px;">
                <h2><?php echo $mail_header_title; ?></h2>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px;">
                <p>Merhaba</p>
                <p><?php echo $mail_intro; ?></p>

                <hr style="border: 1px solid #e0e0e0; margin: 20px 0;">

                <h3 style="color: #0073e6; margin: 0;"><?php echo $mail_info_section_title; ?></h3>
                <p style="margin-top: 8px;">
                    <strong><?php echo $mail_sender_title; ?></strong> <?php echo $user_name; ?><br>
                    <strong><?php echo $mail_sender_email_title; ?></strong> <?php echo $user_email; ?><br>
                </p>

                <h3 style="color: #0073e6; margin: 0;">Bildirim Detayları</h3>
                <p style="margin-top: 8px;">
                    <strong><?php echo $mail_ticket_number_title; ?></strong> <?php echo $ticket_number; ?><br>
                    <strong>Konu:</strong><?php echo $ticket_subject ?? 'Destek'; ?> <br>
                    <strong>Bildirim Tarihi:</strong> <?php echo date("d.m.Y h:i:A"); ?><br>

                </p>

                <h3 style="color: #0073e6; margin: 0;">Mesaj</h3>
                <p style="margin-top: 8px; border-left: 4px solid #0073e6; padding-left: 10px; color: #555;">
                    <?php echo $message_body; ?>
                </p>

                <hr style="border: 1px solid #e0e0e0; margin: 20px 0;">

                <p><?php echo $mail_footer_text; ?></p>

                <p>Saygılarımızla,<br><strong>MbeYazılım Destek Ekibi</strong></p>
            </td>
        </tr>
        <tr>
            <td
                style="padding: 10px; background-color: #f0f0f0; text-align: center; font-size: 12px; color: #888; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;">
                © Mbe Yazılım. Tüm hakları saklıdır.
            </td>
        </tr>
    </table>
</body>

</html>