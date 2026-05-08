<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parola Sıfırlama Talimatı</title>
    <style>
        body {
            font-family: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            text-align: center;
        }

        .container-item {
            margin: 20px;
        }

        .button {
            background-color: #066fd1;
            color: #fff !important;
            padding: 10px 20px;
            text-decoration: none;
            display: inline-block;
            border-radius: 5px;
            margin: 30px;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .row {
            /* display: flex !important; */
            justify-content: center !important;
        }

        .link {
            color: #066fd1;
        }

        .icon {
            color: #066fd1;
        }
    </style>
    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon">
            <img src="cid:lock-icon" width="96" height="96" alt="Lock Icon">
        </div>
        <h1>Parola Sıfırlama Talebi</h1>
        <p>Puantor hesabınızın parolasını sıfırlamak istediğinizi kısa bir süre önce bildirdiniz. Aşağıdaki düğmeyi
            kullanarak parolanızı sıfırlayabilirsiniz. Bu mesaj 24 saat içinde geçerliliğini yitirecektir.</p>
        <div class="row container-item">

            <a href="<?php echo $resetLink; ?>" class="button">Parolayı Sıfırla</a>

        </div>
        <p>Üstteki düğme ile ilgili bir sorun mu yaşıyorsunuz? Lütfen şu URL'yi kopyalayın: <a
                href="<?php echo $resetLink; ?>" class="link"><?php echo $resetLink; ?></a> ve tarayıcınızda açın.</p>
        <p>Parola sıfırlama talebinde bulunmadıysanız, bu mesajı dikkate almayın veya herhangi bir sorunuz varsa bizimle
            iletişime geçin.</p>
    </div>
</body>

</html>