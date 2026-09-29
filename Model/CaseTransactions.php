<?php
!defined("ROOT") ? define("ROOT", $_SERVER['DOCUMENT_ROOT']) : null;
require_once "BaseModel.php";
require_once "Cases.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/security.php";
require_once "SettingsModel.php";

use App\Helper\Helper;
use App\Helper\Security;
class CaseTransactions extends Model
{
    protected $table = "case_transactions";
    protected $sql_table = "sql_case_transactions";
    protected $caseObj;

    protected $Settings;

    public function saveWithAttr($data)
    {
        if (!isset($data['sub_type']) || $data['sub_type'] === null || $data['sub_type'] === '') {
            $type_id = isset($data['type_id']) ? (int)$data['type_id'] : 1;
            if (!empty($data['person_id'])) {
                $data['sub_type'] = ($type_id == 1) ? 1 : 7;
            } elseif (!empty($data['company_id'])) {
                $data['sub_type'] = ($type_id == 1) ? 1 : 8;
            } elseif (!empty($data['project_id'])) {
                $data['sub_type'] = ($type_id == 1) ? 5 : 6;
            } else {
                $data['sub_type'] = ($type_id == 1) ? 1 : 2;
            }
        }
        $id = parent::saveWithAttr($data);
        require_once __DIR__ . '/ActivityLogModel.php';
        $action = (isset($data['id']) && $data['id'] > 0) ? 'update' : 'add';
        $type = (isset($data['type_id']) && $data['type_id'] == 1) ? 'Gelir' : 'Gider';
        $amount = Helper::formattedMoney($data['amount'] ?? 0);
        $desc = $data['description'] ?? '';
        ActivityLogModel::log('finance', $action, "Kasa hareketi ({$type}): {$amount} - {$desc}");
        return $id;
    }

    public function delete($id)
    {
        $transaction = $this->find($id);
        if ($transaction) {
            $type = ($transaction->type_id == 1) ? 'Gelir' : 'Gider';
            $amount = Helper::formattedMoney($transaction->amount);
            require_once __DIR__ . '/ActivityLogModel.php';
            ActivityLogModel::log('finance', 'delete', "Kasa hareketi silindi ({$type}): {$amount} - {$transaction->description}");
        }
        return parent::delete($id);
    }

    public function __construct()
    {
        parent::__construct($this->table);
        $this->caseObj = new Cases();
        $this->Settings = new SettingsModel();
    }

    public function allByCase($case_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE case_id = ?");
        $sql->execute([$case_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //firmanın kasalarının işlemlerini getirir
    public function allTransactionByFirm($firm_id)
    {
        $is_main_user = ($_SESSION['user']->parent_id == 0 || (isset($_SESSION['user']->is_main_user) && $_SESSION['user']->is_main_user == 1));
        if ($is_main_user) {
            $cases = $this->caseObj->allCaseWithFirmId();
        } else {
            $cases = $this->caseObj->getCasesByUserIds();
        }

        $case_ids = array_map(function ($case) {
            return $case->id;
        }, $cases);

        if (empty($case_ids)) {
            return []; // Veya uygun bir boş sonuç döndürme işlemi
        }

        $case_ids = implode(",", $case_ids);
        $sql = $this->db->prepare("SELECT * FROM $this->sql_table WHERE case_id IN ($case_ids) order BY id desc");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //All transactions by case id
    public function allTransactionByCase($case_id)
    {
        //case_id boş ise firmanın varsayılan kasa id'sini al
        if (empty($case_id)) {
            $case_id = $this->caseObj->getDefaultCaseIdByFirm();
        }

        $sql = $this->db->prepare("SELECT * FROM $this->sql_table WHERE case_id = ? ORDER BY id DESC");
        $sql->execute([$case_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function deleteCaseTransactions($case_id)
    {
        $sql = $this->db->prepare("DELETE FROM $this->table WHERE case_id = ?");
        $sql->execute([$case_id]);
    }

    //Kasanın tüm hareketlerini getir
    public function allTransactionByCaseId($case_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->sql_table WHERE case_id = ?");
        $sql->execute([$case_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //Kasanın gelir-gider bilgilerini getir

    public function sumAllIncomeExpense($case_id)
    {
        $sql = $this->db->prepare("SELECT 
                                                SUM(CASE WHEN type_id = 1 THEN amount ELSE 0 END) AS income,
                                                SUM(CASE WHEN type_id = 2 THEN amount ELSE 0 END) AS expense
                                            FROM $this->sql_table WHERE case_id = ?");
        $sql->execute([$case_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }


    public function getCaseBalance($case_id)
    {
        $query = "
            SELECT 
                COALESCE(SUM(CASE WHEN type_id = 1 THEN amount ELSE 0 END),0) AS total_income,
                COALESCE(SUM(CASE WHEN type_id = 2 THEN amount ELSE 0 END),0) AS total_expense,
                COALESCE(SUM(CASE WHEN type_id = 1 THEN amount ELSE 0 END) - SUM(CASE WHEN type_id = 2 THEN amount ELSE 0 END),0) AS balance
            FROM 
                sql_case_transactions
            WHERE 
                case_id = :case_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute(["case_id" => $case_id]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }


    //Kasalar arası transfer işlemi
    public function transfer($from_case, $to_case, $amount, $description,$date)
    {

        $amount = Helper::formattedMoneyToNumber($amount);
        $description = Security::escape($description);
        $description = empty($description) ? "Virman" : $description;

        //aktarılacak kasa boş gelirse
        if (empty($from_case) || empty($to_case)) {
            return ["status" => "error", "message" => "Transfer yapılacak kasayı seçiniz!"];
        }
        //Transfer işlemi için gerekli kontroller
        if ($from_case == $to_case) {
            return ["status" => "error", "message" => "Aynı kasaya transfer yapılamaz."];
        }

        if ($amount <= 0) {
            return ["status" => "error", "message" => "Geçersiz miktar."];
        }

        //alt limit -2500, bakiye -2000 ise 500 transfer yapabilir
        $sub_limit = $this->Settings->getSettings("cases_sub_limit")->set_value ?? 0;
        $from_case_balance =  ($this->getCaseBalance($from_case)->balance) - $sub_limit  ;

        if ($amount > $from_case_balance) {
            return ["status" => "error", "message" => "Yetersiz bakiye.<br> Yapabileceğiniz maksimum transfer miktarı: <br>" . Helper::formattedMoney($from_case_balance)];
        }


        $data = [
            "date" => $date,
            "case_id" => $from_case,
            "type_id" => 2,
            "sub_type" => 3,
            "amount" => ($amount),
            "description" => $description ?? "Virman",
            "created_at" => date("Y-m-d H:i:s")
        ];
        $this->saveWithAttr($data);

        $data = [
            "date" => $date,
            "case_id" => $to_case,
            "type_id" => 1,
            "sub_type" => 3,
            "amount" => ($amount),
            "description" => $description ?? "Virman",
            "created_at" => date("Y-m-d H:i:s")
        ];
        $this->saveWithAttr($data);

        return ["status" => "success", "message" => "Transfer işlemi başarılı."];
    }

    public function getFirmBalance($firm_id)
    {
        // Firmanın kasalarını yetkiye göre al
        $is_main_user = ($_SESSION['user']->parent_id == 0 || (isset($_SESSION['user']->is_main_user) && $_SESSION['user']->is_main_user == 1));
        if ($is_main_user) {
            $cases = $this->caseObj->allCaseWithFirmId();
        } else {
            $cases = $this->caseObj->getCasesByUserIds();
        }

        $case_ids = array_map(function ($case) {
            return $case->id;
        }, $cases);

        if (empty($case_ids)) {
            return (object)[
                'total_income' => 0,
                'total_expense' => 0
            ];
        }

        $ids_str = implode(",", $case_ids);
        $query = "
            SELECT 
                COALESCE(SUM(CASE WHEN type_id = 1 THEN amount ELSE 0 END), 0) AS total_income,
                COALESCE(SUM(CASE WHEN type_id = 2 THEN amount ELSE 0 END), 0) AS total_expense
            FROM 
                $this->sql_table
            WHERE 
                case_id IN ($ids_str)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getMonthlyIncomeExpenseTrend($firm_id, $months = 6)
    {
        $months = max(1, min((int)$months, 24));
        
        $is_main_user = ($_SESSION['user']->parent_id == 0 || (isset($_SESSION['user']->is_main_user) && $_SESSION['user']->is_main_user == 1));
        if ($is_main_user) {
            $cases = $this->caseObj->allCaseWithFirmId();
        } else {
            $cases = $this->caseObj->getCasesByUserIds();
        }

        $case_ids = array_map(function ($case) {
            return (int)$case->id;
        }, $cases);

        if (empty($case_ids)) {
            return [
                'labels' => [],
                'income' => [],
                'expense' => [],
                'balance' => []
            ];
        }

        $placeholders = implode(',', array_fill(0, count($case_ids), '?'));
        
        $sql = "
            SELECT 
                CASE 
                    WHEN date LIKE '____-__-__%' THEN LEFT(date, 7)
                    WHEN date LIKE '________%' THEN CONCAT(LEFT(date, 4), '-', SUBSTRING(date, 5, 2))
                    ELSE LEFT(created_at, 7)
                END AS month_key,
                SUM(CASE WHEN type_id = 1 THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN type_id = 2 THEN amount ELSE 0 END) AS total_expense
            FROM $this->sql_table
            WHERE case_id IN ($placeholders)
            GROUP BY month_key
            ORDER BY month_key ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($case_ids);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $rowMap = [];
        foreach ($rows as $r) {
            if (!empty($r->month_key)) {
                $rowMap[$r->month_key] = $r;
            }
        }

        $labels = [];
        $income = [];
        $expense = [];
        $balance = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $timestamp = strtotime("-$i months");
            $key = date('Y-m', $timestamp);
            $monthNamesTr = [
                '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
                '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
                '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
            ];
            $m = date('m', $timestamp);
            $y = date('Y', $timestamp);
            $labels[] = ($monthNamesTr[$m] ?? $m) . ' ' . $y;

            $inc = isset($rowMap[$key]) ? (float)$rowMap[$key]->total_income : 0.0;
            $exp = isset($rowMap[$key]) ? (float)$rowMap[$key]->total_expense : 0.0;
            $bal = $inc - $exp;

            $income[] = $inc;
            $expense[] = $exp;
            $balance[] = $bal;
        }

        return [
            'labels' => $labels,
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance
        ];
    }

    public function getFirmCasesWithBalance($firm_id)
    {
        $is_main_user = ($_SESSION['user']->parent_id == 0 || (isset($_SESSION['user']->is_main_user) && $_SESSION['user']->is_main_user == 1));
        if ($is_main_user) {
            $cases = $this->caseObj->allCaseWithFirmId();
        } else {
            $cases = $this->caseObj->getCasesByUserIds();
        }

        if (empty($cases)) {
            return [];
        }

        foreach ($cases as &$case) {
            $bal = $this->getCaseBalance($case->id);
            $case->total_income = $bal->total_income ?? 0;
            $case->total_expense = $bal->total_expense ?? 0;
            $case->balance = $bal->balance ?? 0;
        }

        return $cases;
    }

    public function getTransactionsSummaryStats(array $case_ids, $type_id = null)
    {
        if (empty($case_ids)) {
            return [
                'total_count' => 0,
                'income_count' => 0,
                'expense_count' => 0,
                'total_income' => 0.0,
                'total_expense' => 0.0,
                'net_balance' => 0.0
            ];
        }
        $case_ids = array_values(array_unique(array_filter(array_map('intval', $case_ids))));
        if (empty($case_ids)) {
            return [
                'total_count' => 0,
                'income_count' => 0,
                'expense_count' => 0,
                'total_income' => 0.0,
                'total_expense' => 0.0,
                'net_balance' => 0.0
            ];
        }
        $placeholders = implode(',', array_fill(0, count($case_ids), '?'));
        $params = $case_ids;

        $typeSql = '';
        if ($type_id !== null && $type_id !== '') {
            $typeSql = ' AND type_id = ?';
            $params[] = (int)$type_id;
        }

        $sql = "SELECT 
                    COUNT(*) AS total_count,
                    COALESCE(SUM(CASE WHEN type_id = 1 THEN 1 ELSE 0 END), 0) AS income_count,
                    COALESCE(SUM(CASE WHEN type_id = 2 THEN 1 ELSE 0 END), 0) AS expense_count,
                    COALESCE(SUM(CASE WHEN type_id = 1 THEN amount ELSE 0 END), 0) AS total_income,
                    COALESCE(SUM(CASE WHEN type_id = 2 THEN amount ELSE 0 END), 0) AS total_expense,
                    COALESCE(SUM(CASE WHEN type_id = 1 THEN amount ELSE 0 END) - SUM(CASE WHEN type_id = 2 THEN amount ELSE 0 END), 0) AS net_balance
                FROM {$this->sql_table}
                WHERE case_id IN ({$placeholders}){$typeSql}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_count' => (int)($res['total_count'] ?? 0),
            'income_count' => (int)($res['income_count'] ?? 0),
            'expense_count' => (int)($res['expense_count'] ?? 0),
            'total_income' => (float)($res['total_income'] ?? 0),
            'total_expense' => (float)($res['total_expense'] ?? 0),
            'net_balance' => (float)($res['net_balance'] ?? 0)
        ];
    }

    public function getTransactionsServerSideCounts(array $case_ids, $type_id = null): array
    {
        if (empty($case_ids)) {
            return ['total' => 0, 'filtered' => 0];
        }
        $case_ids = array_values(array_unique(array_filter(array_map('intval', $case_ids))));
        if (empty($case_ids)) {
            return ['total' => 0, 'filtered' => 0];
        }
        $placeholders = implode(',', array_fill(0, count($case_ids), '?'));
        $params = $case_ids;

        $typeSql = '';
        if ($type_id !== null && $type_id !== '') {
            $typeSql = ' AND type_id = ?';
            $params[] = (int)$type_id;
        }

        $sql = "SELECT COUNT(*) as count FROM {$this->sql_table} WHERE case_id IN ({$placeholders}){$typeSql}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $count = (int)($stmt->fetch(PDO::FETCH_OBJ)->count ?? 0);

        return ['total' => $count, 'filtered' => $count];
    }

    public function getTransactionsServerSidePage(
        array $case_ids,
        int $start,
        int $length,
        $type_id = null,
        string $orderField = 'id',
        string $orderDirection = 'desc'
    ): array {
        if (empty($case_ids)) {
            return [];
        }
        $case_ids = array_values(array_unique(array_filter(array_map('intval', $case_ids))));
        if (empty($case_ids)) {
            return [];
        }

        $allowedOrderFields = [
            'id' => 'id',
            'case_id' => 'case_id',
            'date' => 'date',
            'type_id' => 'type_id',
            'account_name' => 'account_name',
            'amount' => 'amount',
            'description' => 'description',
            'created_at' => 'created_at'
        ];

        $orderField = $allowedOrderFields[$orderField] ?? 'id';
        $orderDirection = strtolower($orderDirection) === 'asc' ? 'ASC' : 'DESC';

        $placeholders = implode(',', array_fill(0, count($case_ids), '?'));
        $params = $case_ids;

        $typeSql = '';
        if ($type_id !== null && $type_id !== '') {
            $typeSql = ' AND type_id = ?';
            $params[] = (int)$type_id;
        }

        $start = max(0, $start);
        $length = max(1, min(200, $length));

        $sql = "SELECT * FROM {$this->sql_table}
                WHERE case_id IN ({$placeholders}){$typeSql}
                ORDER BY {$orderField} {$orderDirection}
                LIMIT {$start}, {$length}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getAllTransactionsForCases(array $case_ids, $type_id = null): array
    {
        if (empty($case_ids)) {
            return [];
        }
        $case_ids = array_values(array_unique(array_filter(array_map('intval', $case_ids))));
        if (empty($case_ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($case_ids), '?'));
        $params = $case_ids;

        $typeSql = '';
        if ($type_id !== null && $type_id !== '') {
            $typeSql = ' AND type_id = ?';
            $params[] = (int)$type_id;
        }

        $sql = "SELECT * FROM {$this->sql_table} WHERE case_id IN ({$placeholders}){$typeSql} ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

