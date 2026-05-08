<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../Database/db.php";
require_once "../Model/Offer.php";
require_once "../Model/OfferProducts.php";
require_once "../App/Helper/date.php";


use Database\Db;

use App\Helper\Date;




$dbInstance = new Db(); // Db sınıfının bir örneğini oluşturuyoruz.
$db = $dbInstance->connect(); // Veritabanı bağlantısını alıyoruz.

$offer = new Offer();

if ($_POST["action"] == "offerSave") {
    $id = $_POST["id"];



    try {
        $data = [
            "offerNumber" => $_POST["offerNumber"],
            "regdate" => Date::Ymd($_POST["offerDate"]),
            "cid" => $_POST["customers"],
            "company_authors" => "Mehmet Ali",
            "buyTotal" => $_POST["genel_toplam_input"]


        ];

        $lastInsertId = $offer->saveandUpdate($id, $data);


        //Eğer bir güncelleme işlemi ise 
        //Offer'a ait olan tüm offerProducts kayıtlarını sil
        $offerProduct = new OfferProducts();

        if ($id > 0) {
            $offerProduct->deleteByOfferId($id);
        }

        // $_POST["products"] içindeki ürünleri döngüye al ve kaydet
        if (isset($_POST['urun_adi'])) {
            $urun_sayisi = count($_POST['urun_adi']);
            for ($i = 0; $i < $urun_sayisi; $i++) {

                $data = [
                    "oid" => $lastInsertId,
                    "xid" => $_POST["customers"],
                    "stokKodu" => $_POST['stok_kodu'][$i],
                    "title" => $_POST['urun_adi'][$i],
                    "unit" => $_POST['urun_birim'][$i],
                    "amount" => $_POST['urun_miktari'][$i],
                    "buyprice" => $_POST['alis_fiyati'][$i],
                    "buycur" => $_POST['alis_para_birimi'][$i],
                    "saleprice" => $_POST['satis_fiyati'][$i],
                    "salecur" => $_POST['satis_para_birimi'][$i],
                    "satirno" => $i
                ];

                $offerProduct->saveOfferProducts($data);
            }
        }
        if ($id > 0) {
            $status = "success";
            $message = "Teklif başarıyla güncellendi.";
        } else {
            $status = "success";
            $message = "Teklif başarıyla kaydedildi.";
        }
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($res);
}
