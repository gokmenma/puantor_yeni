<?php

namespace App\Helper;

class Helper
{
    const MONEY_UNIT = [
        '1' => 'TRY',
        '2' => 'USD',
        '3' => 'EUR',
    ];

    const UNITS = [
        '1' => 'Ad.',
        '2' => 'Kg',
        '3' => 'Lt.',
        '4' => 'Mt.',
        '5' => 'Pak.',
    ];

    const KDV_ORANI = [
        '0' => '% 0',
        '1' => '% 1',
        '8' => '% 8',
        '18' => '% 18',
        '20' => '% 20',
    ];

    const INCOME_EXPENSE_TYPE = [
        '1' => 'Gelir',
        '2' => 'Kesinti',
        '3' => 'Ödeme',
        '4' => 'Maaş',
        '5' => 'Puantaj Çalışma',
        '6' => 'Hakediş',
        '' => 'Bilinmiyor',
    ];

    const INC_EXP = [
        '1' => 'Gelir',
        '2' => 'Gider',
    ];

    const PRIORITY = [
        '1' => 'Düşük',
        '2' => 'Orta',
        '3' => 'Yüksek',
    ];




    public static function getPriority($priority)
    {
        $priorities = self::PRIORITY;
        return $priorities[$priority];
    }

    //Get transaction type


    public static function getTransactionType($type)
    {
        $types = self::INC_EXP;
        return $types[$type];
    }
    public static function getIncomeExpenseType($type)
    {
        $types = self::INCOME_EXPENSE_TYPE;
        return $types[$type];
    }

    public static function short($value, $lenght = 21)
    {
        return strlen($value) > $lenght ? substr($value, 0, $lenght) . '...' : $value;
    }

    public static function formattedMoney($value, $currency = 1)
    {
        return number_format($value, 2, ',', '.') . ' ' . self::MONEY_UNIT[$currency];
    }

    //109.852,25 şeklinde gelen değeri 109852.25 olarak döndürür
    public static function formattedMoneyToNumber($value)
    {
        return str_replace(['.', ','], ['', '.'], $value);
    }

    // Veritabanından gelen sayıdaki "." yı virgüle çevirir
    public static function moneyToNumber($value)
    {
        return str_replace('.', ',', $value);
    }

    //Para birim formatında TRY olmadan
    public static function formattedMoneyWithoutCurrency($value)
    {
        return number_format($value, 2, ',', '.');
    }

    public static function moneySelect($name = 'moneys', $selected = '1')
    {
        $select = '<select id="' . $name . '" name="' . $name . '" class="select2 form-control" style="width:100%">';
        foreach (self::MONEY_UNIT as $key => $value) {
            $selectedAttr = $selected == $key ? 'selected' : '';
            $select .= "<option value='$key' $selectedAttr>$value</option>";
        }
        $select .= '</select>';
        return $select;
    }

    public static function money($currency = 1)
    {
        if (!isset(self::MONEY_UNIT[$currency])) {
            return '';
        }
        return self::MONEY_UNIT[$currency];
    }

    public static function unitSelect($name = 'units', $selected = '1')
    {
        $select = '<select id="' . $name . '" name="' . $name . '" class="select2 form-control" style="width:100%">';
        foreach (self::UNITS as $key => $value) {
            $selectedAttr = $selected == $key ? 'selected' : '';
            $select .= "<option value='$key' $selectedAttr>$value</option>";
        }
        $select .= '</select>';
        return $select;
    }

    public static function unit($unit = '1')
    {
        if (!isset(self::UNITS[$unit])) {
            return '';
        }
        return self::UNITS[$unit];
    }




    public static function kdvSelect($name = 'kdv', $selected = '20')
    {
        $select = '<select id="' . $name . '" name="' . $name . '" class="select2 form-control" style="width:100%">';
        foreach (self::KDV_ORANI as $key => $value) {
            $selectedAttr = $selected == $key ? 'selected' : '';
            $select .= "<option value='$key' $selectedAttr>$value</option>";
        }
        $select .= '</select>';
        return $select;
    }

    public static function kdv($value, $kdv = '1')
    {
        return $value . ' %' . self::KDV_ORANI[$kdv];
    }

    public static function balanceColor($tutar)
    {
        if ($tutar < 0) {
            return 'text-danger';
        } elseif ($tutar > 0) {
            return 'text-success';
        } else {
            return '';
        }
    }

    public static function getIncExpTypeName($type)
    {
        $types = self::INC_EXP;
        return $types[$type];
    }

    public static function incExpTypeSelect($name = 'incexp_type', $selected = '1')
    {
        $select = '<select id="' . $name . '" name="' . $name . '" class="select2 form-control" style="width:100%">';
        foreach (self::INC_EXP as $key => $value) {
            $selectedAttr = $selected == $key ? 'selected' : '';
            $select .= "<option value='$key' $selectedAttr>$value</option>";
        }
        $select .= '</select>';
        return $select;
    }

    //dd fonksiyonu
    public static function dd($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';

    }

    //gelen kelimelerin sadece ilk harflerini döndürür
    //örnek : Ahmet Yılmaz => AY
    public static function getInitials($name, $count = 2)
    {
        if (empty($name) || $name == null) {
            return '';
        }
        $name = explode(' ', $name);
        $initials = '';
        $counter = 0;
        foreach ($name as $n) {
            if (!empty($n) && $counter < $count) { // Boş olup olmadığını ve counter'ı kontrol et
                $initials .= $n[0];
                $counter++;
            }
        }
        return strtoupper($initials);
    }

    //authorize sayfasını include eder
    public static function authorizePage()
    {
        echo '<div class="empty">
                <div class="empty-img">
                    <img src="static/unauthorize-red.svg" alt="" style="width:200px;height:200px">


                </div>
                <p class="empty-title">Yetkiniz Yok!!!</p>
                <p class="empty-subtitle text-secondary">
                    Bu alanı görüntüleme yetkiniz bulunmamaktadır!
                </p>
            
            </div>';
    }



    /**
     * Get icon with color by type
     * @param $type int app/helper/financial.php içindeki sabitler
     * @return string
     */
    public static function getIconWithColorByType($type)
    {
        $icon = "";
        $color = "";

        switch ($type) {
            case 1://Gelir
            case 6: //Hakediş
                $icon = "ti-arrow-up-dashed";
                $color = "color-green";
                break;
            case 2:
                $icon = "ti-arrow-down-dashed";
                $color = "color-red";
                break;
            case 3:
                $icon = "ti-upload";
                $color = "color-yellow";
                break;
            case 5: //Ödeme
            case 7: //Maaş
                $icon = "ti-cash-register";
                $color = "color-yellow";
                break;
            case 10: //Hakediş
                $icon = "ti-building-estate";
                $color = "color-green";
                break;
            case 11: //Masraf
            case 12:  //Kesinti
            case 15: //Personel Kesinti
                $icon = "ti-fold-down";
                $color = "color-red";
                break;
            case 14: //Puantaj Çalışma
                $icon = "ti-stretching";
                $color = "color-green";
                break;

            default:
                $icon = "ti-question-mark";
                $color = "";
                break;
        }

        return "<i class='ti $icon icon $color me-1'></i>";
    }



}
