<?php
require_once "../../Database/require.php";
require_once "../../Model/Company.php";
require_once "../../Model/CaseTransactions.php";
require_once "../../App/Helper/helper.php";
require_once "../../App/Helper/date.php";

use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;


$company = new Company();

if ($_POST["action"] == "saveCompany") {
    $id = $_POST["id"] != 0 ? Security::decrypt($_POST["id"]) : 0;

    if (empty($_POST["company_name"])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "Lütfen Firma Adı alanını doldurunuz."
        ]);
        exit;
    }

    $data = [
        "id" => $id,
        "user_id" => $_SESSION["user"]->id,
        "company_name" => $_POST["company_name"],
        "yetkili" => $_POST["yetkili"],
        "tax_office" => $_POST["tax_office"],
        "tax_number" => $_POST["tax_number"],
        "account_number" => $_POST["account_number"],
        "phone" => $_POST["phone"],
        "email" => $_POST["email"],
        "city" => !empty($_POST["firm_cities"]) ? $_POST["firm_cities"] : null,
        "town" => !empty($_POST["firm_towns"]) ? $_POST["firm_towns"] : null,
        "address" => $_POST["address"],
        "description" => $_POST["description"],
        "updater" => $_SESSION["user"]->id,
        "updated_at" => date("Y-m-d H:i:s"),
    ];

    if ($id == 0) {
        $data["creativer"] = $_SESSION["user"]->id;
    }


    try {
        $lastInsertId = $company->saveWithAttr($data);
        $status = "success";
        if ($id == 0) {
            $message = "Firma başarıyla kaydedildi.";
        } else {
            $message = "Firma başarıyla güncellendi.";
        }
    } catch (PDOException $e) {
        $status = "error";
        $message =  $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($res);
}

if (isset($_POST["action"]) && $_POST["action"] == "getCompanyDetails") {
    $id = isset($_POST["id"]) ? Security::decrypt($_POST["id"]) : 0;
    $companyData = $company->find($id);
    if ($companyData) {
        $ctObj = new CaseTransactions();
        $db = $ctObj->getDb();

        $stmt = $db->prepare("SELECT ct.*, c.case_name FROM case_transactions ct LEFT JOIN cases c ON ct.case_id = c.id WHERE ct.company_id = ? ORDER BY ct.date DESC, ct.id DESC");
        $stmt->execute([$id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_OBJ);

        $formattedTransactions = [];
        $totalIncome = 0;
        $totalExpense = 0;
        
        foreach ($transactions as $t) {
            if ($t->type_id == 1) {
                $totalIncome += $t->amount;
            } else if ($t->type_id == 2) {
                $totalExpense += $t->amount;
            }
            $formattedTransactions[] = [
                'id' => Security::encrypt($t->id),
                'date' => \App\Helper\Date::dmY($t->date),
                'case_name' => $t->case_name ?? 'Bilinmiyor',
                'type' => $t->type_id == 1 ? 'Gelir' : 'Gider',
                'type_id' => $t->type_id,
                'amount' => \App\Helper\Helper::formattedMoney($t->amount),
                'description' => $t->description
            ];
        }
        
        $townOptionsHtml = '';
        if (!empty($companyData->city)) {
            require_once "../../App/Helper/cities.php";
            $citiesHelper = new Cities();
            $townOptionsHtml = $citiesHelper->getCityTowns($companyData->city);
        }
        
        $res = [
            "status" => "success",
            "company" => $companyData,
            "townOptions" => $townOptionsHtml,
            "summary" => [
                "totalIncome" => \App\Helper\Helper::formattedMoney($totalIncome),
                "totalExpense" => \App\Helper\Helper::formattedMoney($totalExpense),
                "balance" => \App\Helper\Helper::formattedMoney($totalIncome - $totalExpense),
                "balance_raw" => $totalIncome - $totalExpense
            ],
            "transactions" => $formattedTransactions
        ];
    } else {
        $res = ["status" => "error", "message" => "Firma bulunamadı."];
    }
    echo json_encode($res);
    exit;
}


if ($_POST["action"] == "deleteCompany") {
    $id = $_POST["id"];
    try {
        $company->delete($id);
        $status = "success";
        $message = "Firma başarıyla silindi.";
    } catch (PDOException $e) {
        $status = "error";
        $message =  $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($res);
}

if (isset($_POST["action"]) && $_POST["action"] == "getCompanyPayment") {
    $id = Security::decrypt($_POST["id"] ?? '');
    if (empty($id)) {
        echo json_encode(["status" => "error", "message" => "Geçersiz kayıt."]);
        exit;
    }
    $ctObj = new CaseTransactions();
    $t = $ctObj->find($id);
    if (!$t) {
        echo json_encode(["status" => "error", "message" => "Kayıt bulunamadı."]);
        exit;
    }
    echo json_encode([
        "status"      => "success",
        "type_id"     => $t->type_id,
        "amount"      => number_format((float)$t->amount, 2, ',', '.'),
        "date"        => \App\Helper\Date::dmY($t->date),
        "case_id"     => Security::encrypt($t->case_id),
        "description" => $t->description ?? '',
    ]);
    exit;
}

if (isset($_POST["action"]) && $_POST["action"] == "saveCompanyPayment") {
    $cp_id      = Security::decrypt($_POST["cp_id"] ?? '');
    $company_id = Security::decrypt($_POST["cp_company_id"] ?? '');
    $case_id    = Security::decrypt($_POST["cp_case_id"] ?? '');
    $type_id    = (int)($_POST["cp_type_id"] ?? 0);
    $amount_raw = $_POST["cp_amount"] ?? '';
    $date_raw   = $_POST["cp_date"] ?? '';
    $description = trim($_POST["cp_description"] ?? '');

    if (empty($company_id) || empty($case_id) || empty($type_id) || empty($amount_raw) || empty($date_raw)) {
        echo json_encode(["status" => "error", "message" => "Tüm zorunlu alanları doldurunuz."]);
        exit;
    }

    $amount  = Helper::formattedMoneyToNumber($amount_raw);
    $dateObj = DateTime::createFromFormat('d.m.Y', $date_raw);
    $date    = $dateObj ? $dateObj->format('Y-m-d') : date('Y-m-d');

    if ($amount <= 0) {
        echo json_encode(["status" => "error", "message" => "Geçersiz tutar."]);
        exit;
    }

    $ctObj = new CaseTransactions();
    $data = [
        "type_id"       => $type_id,
        "date"          => $date,
        "case_id"       => $case_id,
        "project_id"    => 0,
        "person_id"     => 0,
        "company_id"    => $company_id,
        "sub_type"      => 8,
        "users_type_id" => 0,
        "amount"        => $amount,
        "description"   => $description,
    ];

    if (!empty($cp_id)) {
        $data["id"] = $cp_id;
        $msg = "Ödeme başarıyla güncellendi.";
    } else {
        $data["created_at"] = date("Y-m-d H:i:s");
        $msg = "Ödeme başarıyla kaydedildi.";
    }

    try {
        $ctObj->saveWithAttr($data);
        echo json_encode(["status" => "success", "message" => $msg]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

if (isset($_POST["action"]) && $_POST["action"] == "deleteCompanyPayment") {
    $id = Security::decrypt($_POST["id"] ?? '');
    if (empty($id)) {
        echo json_encode(["status" => "error", "message" => "Geçersiz kayıt."]);
        exit;
    }
    $ctObj = new CaseTransactions();
    try {
        $ctObj->delete($id);
        echo json_encode(["status" => "success", "message" => "Ödeme kaydı silindi."]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
