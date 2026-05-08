<?php
//define('ROOT', $_SERVER['DOCUMENT_ROOT'] );
require_once 'BaseModel.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . "/Model/Projects.php";

use App\Helper\Helper;

class ProjectIncomeExpense extends Model
{
    protected $table = 'project_gelir_gider';
    protected $sql_table = "sql_project_gelir_gider";

    protected $Projects;

    public function __construct()
    {
        parent::__construct($this->table);
        $this->Projects = new Projects();

    }

    public function getAllIncomeExpenseByFirm($firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = :firm_id  ORDER BY id DESC");
        $sql->execute(['firm_id' => $firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    // public function getAllIncomeExpenseByProject($project_id)
    // {
    //     $sql = $this->db->prepare("SELECT * FROM $this->table WHERE project_id = :project_id and project_id != 0 ORDER BY id DESC");
    //     $sql->execute(['project_id' => $project_id]);
    //     return $sql->fetchAll(PDO::FETCH_OBJ);
    // }

    public function getAllIncomeExpenseByProject($project_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->sql_table WHERE project_id = :project_id and project_id != 0 ORDER BY id DESC");
        $sql->execute(['project_id' => $project_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    // function sumAllIncomeExpense($project_id)
    // {
    //     $sql = $this->db->prepare('SELECT 
    //                                             COALESCE(SUM(CASE WHEN kategori = 6 THEN tutar END), 0) AS hakedis,
    //                                             COALESCE(SUM(CASE WHEN kategori = 2 THEN tutar END), 0) AS kesinti,
    //                                             COALESCE(SUM(CASE WHEN kategori = 3 THEN tutar END), 0) AS odeme
    //                                         FROM project_gelir_gider 
    //                                         WHERE project_id = :project_id and project_id != 0;');
    //     $sql->execute(['project_id' => $project_id]);
    //     return $sql->fetch(PDO::FETCH_OBJ);
    // }


    function sumAllIncomeExpense($project_id)
    {
        $sql = $this->db->prepare("SELECT 

                                                COALESCE(SUM(CASE WHEN turu IN (10) THEN tutar END), 0) AS hakedis,   /* 10: hakediş */
                                                COALESCE(SUM(CASE WHEN turu IN (5) THEN tutar END), 0) AS gelir,   /* 5: Projeden Alınan Ödeme */
                                                COALESCE(SUM(CASE WHEN turu IN (11,12,14) THEN tutar END), 0) AS kesinti,  /* 11: Proje Masraf , 12:Proje Kesinti */
                                                COALESCE(SUM(CASE WHEN turu IN(6) THEN tutar END), 0) AS odeme          /* 6: Projeye Yapılan Ödeme */
                                            FROM $this->sql_table 
                                            WHERE project_id = :project_id and project_id != 0;");
        $sql->execute(['project_id' => $project_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    //Formatlanmış gelir gider bilgileri
    function sumAllIncomeExpenseFormatted($project_id)
    {
        $result = $this->sumAllIncomeExpense($project_id);
        $result->hakedis = Helper::formattedMoney($result->hakedis);
        $result->gelir = Helper::formattedMoney($result->gelir);
        $result->kesinti = Helper::formattedMoney($result->kesinti);
        $result->odeme = Helper::formattedMoney($result->odeme);
        //Bakiyeyi de ekle
        $result->balance = $this->getBalanceFormatted($project_id);
        return $result;
    }

    function getBalance($project_id)
    {
        $result = $this->sumAllIncomeExpense($project_id);
        return $result->hakedis - $result->gelir - $result->kesinti - $result->odeme;
    }

    //Formatlanmış bakiye
    function getBalanceFormatted($project_id)
    {
        return Helper::formattedMoney($this->getBalance($project_id));
    }

    function getProgressPaymentRange($project_id)
    {
        //Projenin Proje bedelini getir
        $project = $this->Projects->find($project_id);
        $budget = $project->budget;

        //Projenin hakediş toplamını getir
        $hakedis = $this->sumAllIncomeExpense($project_id)->hakedis;

        //Progress statunun değerini oluştur
        $progress = number_format($hakedis / $budget * 100, 0);
        //100'den büyük olamaz
        if ($progress > 100) {
            $progress = 100;
        }

        return $progress;

    }
}
