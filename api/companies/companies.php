<?php
require_once "../../Database/require.php";
require_once "../../Model/Company.php";


use App\Helper\Security;


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
        // Fetch case transactions for this company
        require_once "../../Model/CaseTransactions.php";
        $ctObj = new CaseTransactions();
        $db = $ctObj->getDb();
        
        $stmt = $db->prepare("SELECT ct.*, c.case_name FROM case_transactions ct LEFT JOIN cases c ON ct.case_id = c.id WHERE ct.company_id = ? ORDER BY ct.date DESC, ct.id DESC");
        $stmt->execute([$id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $formattedTransactions = [];
        $totalIncome = 0;
        $totalExpense = 0;
        
        require_once "../../App/Helper/helper.php";
        require_once "../../App/Helper/date.php";
        
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
