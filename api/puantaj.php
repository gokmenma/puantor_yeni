<?php
!defined('ROOT') ? define('ROOT', $_SERVER["DOCUMENT_ROOT"]) : '';
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Puantaj.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Wages.php';
require_once ROOT . '/Database/db.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/Model/SettingsModel.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/Model/ActivityLogModel.php';


use App\Helper\Date;
use App\Helper\Security;
use App\Helper\Helper;

$Settings = new SettingsModel();
$puantajObj = new Puantaj();
$person = new Persons();
$wages = new Wages();

if ($_POST['action'] == 'savePuantaj') {
    $status = '';
    $message = '';

    //Günlük calisma saatini getir
    $work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
    $work_hour = str_replace(',', '.', $work_hour);

    //Gelen data json formatında olduğu için decode edilir
    $json_data = json_decode($_POST['data'], true);
    $error_wages = [];
    foreach ($json_data as $person_key => $person_item) {
        // puantajId'nin boş olmadığını kontrol et
        //personelin tanımlı ücreti var ise o ücretten hesaplama yapılacak
        $person_id = Security::decrypt($person_key);
        $ucret = $person->getDailyWages($person_id)->daily_wages / $work_hour;
        if ($ucret == 0 || $ucret == '') {
            $error_wages[] = $person->getPersonByField($person_id, 'full_name');
        }
        foreach ($person_item as $puantaj_key => $puantaj_item) {
            //Personelin gün bazında, kaydedilen puantaj id alınır
            $id = $puantajObj->getPuantajId($person_id, $puantaj_key);
            if ($puantaj_item['puantajId'] == 0) {
                // Eğer puantajId 0 ise personelin o gün için puantajı olmadığı için
                // hesaplama yapılmaması için o kayıt silinir
                $puantajObj->deletePuantajGun($id);
            } else if (!empty($puantaj_item['puantajId'])) {


                // Eğer personelin günlük ücreti tanımlı ise o ücretten hesaplama yapılır
                $defined_wage = $wages->getWageByPersonIdAndDate($person_id, $puantaj_key)->amount ?? 0;
                //tanımlı ücret yoksa günlük ücretten hesaplama yapılır
                $daily_wages = (($defined_wage > 0) ? ($defined_wage / $work_hour) : $ucret);


                //eğer saatlik çalışma değilse

                $puantaj_turu = $puantajObj->getPuantajTuruById($puantaj_item['puantajId']);
                if ($puantaj_turu->Turu != 'Saatlik') {

                    //puantajın saati, puantaj id'sine göre getirilir
                    $saat = $puantajObj->getPuantajSaatiByfirm($puantaj_item['puantajId']);
                    //Günlük hakediş tutarı hesaplanır
                    $tutar = floatval($saat) *  $daily_wages;
                } else {
                    $saat = $puantaj_turu->PuantajSaati;
                    $tutar =floatval($saat) *  $daily_wages;
                }
                $data = [
                    'id' => $id, // Puantaj tablosundaki id,
                    'person' => Security::decrypt($person_key), // Personel id
                    'project_id' => $puantaj_item['project_id'],  // Proje id
                    'puantaj_id' => $puantaj_item['puantajId'], // Puantaj id
                    'gun' => $puantaj_key,  // Tarih
                    'saat' => $saat, // Puantaj saati
                    'tutar' => $tutar, // Tutar,
                    "description" => "Puantaj Çalışma", // Tutar,
                ];

                try {
                    //if ($tutar > 0) {
                        // Veriyi modele gönder
                        $lastInsertId = $puantajObj->saveWithAttr($data);
                        
                        $status = 'success';
                        if (count($error_wages) > 0) {
                            $message = 'Puantaj başarıyla güncellendi.<br>Fakat şu personellerin günlük ücreti 0 olduğu için hesaplama yapılamadı: <strong> <br>' . implode(', ', $error_wages) . '</strong>';
                        } else {
                            $message = 'Puantaj başarıyla güncellendi';
                        }
                    //}
                } catch (Exception $e) {
                    // Hata yönetimi
                    $status = 'error';
                    $message = 'Bir hata oluştu: ' . $e->getMessage();
                }
            }
        }
    }

    $res = [
        'status' => $status,
        'message' => $message,
        'error_wages' => $error_wages
    ];

    echo json_encode($res);
}
