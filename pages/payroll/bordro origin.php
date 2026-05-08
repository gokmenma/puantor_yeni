<?php
use App\Helper\Helper;

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $person->full_name ?> - Bordro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-size: 13px;
        }

        .payslip-container {
            width: 797px;
            padding: 20px;
            background-color: #fff;

            /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); */
        }

        .header,
        .section,
        .footer {
            margin-bottom: 20px;
        }

        .header h2,
        .section h3 {
            text-align: center;
            margin-bottom: 10px;
        }

        .info-table,
        .earnings-deductions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td,
        .earnings-deductions-table th,
        .earnings-deductions-table td {
            padding: 6px;
            border: 1px solid #ddd;
        }

        .info-table td {
            width: 50%;
        }

        .earnings-deductions-table th {
            background-color: #333;
            color: #fff;
        }

        .total {
            font-weight: bold;
            background-color: #f4f4f4;
        }

        .net-salary {
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 10px;
            padding: 10px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            float: left;
        }

        .table td {
            padding: 4px;
            font-size: 13px;
            border: 1px solid #ddd;
        }

        .table th {
            border: 1px solid #ddd;
        }



        .row {
            width: 100%;
        }

        .pt-20 {
            padding-top: 20px;
        }

        .col-4 {
            width: 33.33%;
            float: left;
        }

        .col-6 {
            width: 50%;
            float: left;
        }

        .col-8 {
            width: 66.66%;
            float: left;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bg-gray {
            background-color: #f4f4f4;
        }

        .align-middle {
            vertical-align: middle;
        }

        .mb-20 {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="payslip-container">
        <div class="header">
            <h2>Personel Maaş Bordrosu</h2>
            <p style="text-align: center;"></p>
        </div>

        <div class="row">
            <div class="col-6">

                <table class="table">
                    <tr>
                        <th colspan="2">Çalışan Bilgileri</th>
                    </tr>
                    <tr>
                        <td class="col-4">Adı Soyadı:</td>
                        <td class="col-8"><?php echo $person->full_name; ?></td>
                    </tr>
                    <tr>
                        <td>TC Kimlik No:</td>
                        <td><?php echo $person->kimlik_no; ?></td>
                    </tr>
                </table>
            </div>

            <!-- FİRMA BİLGİLERİ -->
            <div class="col-6">

                <table class="table">
                    <tr>
                        <th colspan="2">Firma Bilgileri</th>
                    </tr>
                    <tr>
                        <td class="col-4 text-right">Firma Adı:</td>
                        <td class="col-8"><?php echo $firm_name; ?></td>
                    </tr>
                    <tr>
                        <td class="text-right">Email:</td>
                        <td><?php echo $firm_email; ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row">
            <h3 class="text-center pt-20">GELİRLER VE GİDERLER</h3>
            <div class="col-6">


                <!-- GELİRLER -->

                <table class="table">
                    <tr class="align-middle">
                        <th>Türü</th>
                        <th>Saati</th>
                        <th>Tutar (₺)</th>
                    </tr>

                    <?php
                    $total_income = 0;
                    foreach ($incomes as $income) {
                        $total_income += $income->tutar;
                        ?>
                        <tr>
                            <td><?php echo $income->turu; ?></td>
                            <td class="text-center"><?php echo $income->saat; ?></td>
                            <td class="text-right"><?php echo Helper::formattedMoneyWithoutCurrency($income->tutar); ?></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="2" class="total">Toplam</td>
                        <td class="text-right total"><?php echo Helper::formattedMoneyWithoutCurrency($total_income); ?>
                        </td>
                    </tr>


                </table>
            </div>
            <div class="col-6">

                <!-- GİDERLER -->
                <table class="table">
                    <tr class="text-center ">
                        <th>Kesinti Türü</th>
                        <th>Tutar (₺)</th>
                    </tr>
                    <?php
                    $total_expense = 0;
                    foreach ($expenses as $expense) {
                        $total_expense += $expense->tutar;
                        ?>
                        <tr>
                            <td><?php echo $expense->turu; ?></td>
                            <td class="text-right">
                                <?php echo Helper::formattedMoneyWithoutCurrency($expense->tutar); ?>
                            </td>
                        </tr>
                    <?php } ?>

                    <tr>
                        <td class="total">Toplam</td>
                        <td class="text-right total">
                            <?php echo Helper::formattedMoneyWithoutCurrency($total_expense); ?>
                        </td>
                    </tr>

                </table>
            </div>
        </div>
        <div class="net-salary bg-gray mb-20">Ödenecek:
            <?php echo '₺' . Helper::formattedMoneyWithoutCurrency($total_income - $total_expense); ?>
        </div>


        <div class="footer">
            <table class="info-table">
                <tr>
                    <td style="padding:25px">
                        Çalışan İmza: _____________
                    </td>
                    <td>
                        Firma İmza: _____________
                        </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>