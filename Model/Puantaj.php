<?php
!defined('ROOT') ? define('ROOT', $_SERVER['DOCUMENT_ROOT']) : null;
require_once "BaseModel.php";
require_once "Persons.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/puantaj.php";
require_once ROOT ."/Model/SettingsModel.php";

$PuantajHelper = new puantajHelper();
$Settings = new SettingsModel();

use App\Helper\Date;
use Mpdf\Tag\S;

class Puantaj extends Model
{
    protected $table = "puantaj";
    protected $PuantajHelper;

    protected $Settings;
    //private $persons;

    public function __construct()
    {
        parent::__construct($this->table);
        $this->PuantajHelper = new puantajHelper();
        $this->Settings = new SettingsModel();
        //Person sınıfını buraya dahil et
        //$this->persons = new Persons();
    }

    public function saveWithAttr($data)
    {
        $id = parent::saveWithAttr($data);
        
        // Sadece person ve gun verisi varsa logla (Bordro hesaplamada bu veriler gelmez ve loglanması istenmez)
        if (isset($data['person']) && isset($data['gun'])) {
            require_once __DIR__ . '/ActivityLogModel.php';
            require_once __DIR__ . '/Persons.php';
            $personObj = new Persons();
            $person_name = $personObj->getPersonByField($data['person'], 'full_name');
            $log_date = date('d.m.Y', strtotime($data['gun']));
            $action = isset($data['id']) ? 'update' : 'add';
            $msg = $action == 'add' ? "eklendi" : "güncellendi";
            ActivityLogModel::log('puantaj', $action, "{$person_name} için {$log_date} tarihine puantaj {$msg}.");
        }
        
        return $id;
    }


    public function getPuantajSaati($id)
    {
        $sql = $this->db->prepare("SELECT PuantajSaati FROM puantajturu WHERE id = ?");
        $sql->execute([$id]);
        return $sql->fetch(PDO::FETCH_OBJ)->PuantajSaati ?? 0;
    }
    public function getPuantajSaatiByfirm($id)
    {
        //Firmanın çalışma saatini getir
        $work_hour = $this->Settings->getSettings("work_hour")->set_value ?? 8;
        $sql = $this->db->prepare("SELECT EklenecekSaat,operant FROM puantajturu WHERE id = ?");
        $sql->execute([$id]);
        $result= $sql->fetch(PDO::FETCH_OBJ);
        //Puantaj saatini hesapla
        $saat =$this->PuantajHelper->calculatePuantajSaati($result->EklenecekSaat,$work_hour, $result->operant);
        return $saat;
    }

    //Personelin puantaj tablosundaki çalışmaları getirilir
    public function getPuantajInfoByPerson($person_id)
    {
        $sql = $this->db->prepare("SELECT pt.* FROM puantaj pt 
                                   INNER JOIN persons p ON p.id = pt.person 
                                   WHERE pt.person = ? 
                                   AND (pt.company_id = p.firm_id OR pt.company_id = 0 OR pt.company_id IS NULL)
                                   ORDER BY pt.gun DESC");
        $sql->execute([$person_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //Projenin puantaj tablosundaki çalışmaları getirilir
    public function getPuantajInfoByProject($project_id)
    {
        $sql = $this->db->prepare("SELECT * FROM puantaj WHERE project_id = ? ");
        $sql->execute([$project_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPuantajByPersonAndDate($person_id, $start_date, $end_date)
    {
        // Tarihleri her iki formatta da hazırla (tireli ve tiresiz)
        $start_dash = (strpos($start_date, '-') !== false) ? $start_date : substr($start_date, 0, 4) . '-' . substr($start_date, 4, 2) . '-' . substr($start_date, 6, 2);
        $start_nodash = str_replace('-', '', $start_date);
        $end_dash = (strpos($end_date, '-') !== false) ? $end_date : substr($end_date, 0, 4) . '-' . substr($end_date, 4, 2) . '-' . substr($end_date, 6, 2);
        $end_nodash = str_replace('-', '', $end_date);

        // Hem tireli aralığı hem de tiresiz aralığı kapsayacak şekilde OR şartı ekle
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE person = ? AND ((gun >= ? AND gun <= ?) OR (gun >= ? AND gun <= ?))");
        $sql->execute([$person_id, $start_dash, $end_dash, $start_nodash, $end_nodash]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    //Puantaj id'sine göre puantaj bilgilerini getiri
    public function getPuantajTuruById($id)
    {
        $sql = $this->db->prepare("SELECT * FROM puantajturu WHERE id = ?");
        $sql->execute([$id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }
    //puantaj gununden puantaj tablosundaki id'yi çekiyoruz
    public function getPuantajId($person_id, $date, $project_id = null)
    {
        $date_dash = (strpos($date, '-') !== false) ? $date : substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        $date_nodash = str_replace('-', '', $date);

        if ($project_id === -1) {
            $sql = $this->db->prepare("SELECT id FROM puantaj WHERE person = ? and (gun = ? OR gun = ?) ORDER BY id DESC LIMIT 1");
            $sql->execute([$person_id, $date_dash, $date_nodash]);
        } elseif ($project_id !== null && $project_id !== "" && $project_id > 0) {
            $sql = $this->db->prepare("SELECT id FROM puantaj WHERE person = ? and (gun = ? OR gun = ?) and project_id = ?");
            $sql->execute([$person_id, $date_dash, $date_nodash, $project_id]);
        } else {
            $sql = $this->db->prepare("SELECT id FROM puantaj WHERE person = ? and (gun = ? OR gun = ?) and (project_id = 0 OR project_id IS NULL)");
            $sql->execute([$person_id, $date_dash, $date_nodash]);
        }
        return $sql->fetch(PDO::FETCH_OBJ)->id ?? 0;
    }

    //Puantaj tablosundan kayıtlı puantaj turu id'sini bul
    public function getPuantajTuruId($person_id, $date, $project_id = null)
    {
        $date_dash = (strpos($date, '-') !== false) ? $date : substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        $date_nodash = str_replace('-', '', $date);

        if ($project_id === -1) {
            $sql = $this->db->prepare("SELECT puantaj_id FROM puantaj WHERE person = ? and (gun = ? OR gun = ?) ORDER BY id DESC LIMIT 1");
            $sql->execute([$person_id, $date_dash, $date_nodash]);
        } elseif ($project_id !== null && $project_id > 0) {
            $sql = $this->db->prepare("SELECT puantaj_id FROM puantaj WHERE person = ? and (gun = ? OR gun = ?) and project_id = ?");
            $sql->execute([$person_id, $date_dash, $date_nodash, $project_id]);
        } else {
            $sql = $this->db->prepare("SELECT puantaj_id FROM puantaj WHERE person = ? and (gun = ? OR gun = ?) and (project_id = 0 OR project_id IS NULL)");
            $sql->execute([$person_id, $date_dash, $date_nodash]);
        }
        return $sql->fetch(PDO::FETCH_OBJ)->puantaj_id ?? '';
    }

    //Puantaj tablosundan kayıtlı proje id'sini bul
    public function getPuantajProjectId($person_id, $date, $project_id = null)
    {
        $date_dash = (strpos($date, '-') !== false) ? $date : substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        $date_nodash = str_replace('-', '', $date);

        if ($project_id === -1) {
            $sql = $this->db->prepare("SELECT project_id FROM puantaj WHERE person = ? and (gun = ? OR gun = ?) ORDER BY id DESC LIMIT 1");
            $sql->execute([$person_id, $date_dash, $date_nodash]);
        } elseif ($project_id !== null && $project_id > 0) {
            $sql = $this->db->prepare("SELECT project_id FROM puantaj WHERE person = ? and (gun = ? OR gun = ?) and project_id = ?");
            $sql->execute([$person_id, $date_dash, $date_nodash, $project_id]);
        } else {
            $sql = $this->db->prepare("SELECT project_id FROM puantaj WHERE person = ? and (gun = ? OR gun = ?) and (project_id = 0 OR project_id IS NULL)");
            $sql->execute([$person_id, $date_dash, $date_nodash]);
        }
        return $sql->fetch(PDO::FETCH_OBJ)->project_id ?? 0;
    }

    //Puantaj tablosundan personel id'sine göre toplam tutarları çek
    public function getPuantajIncomeByPerson($person_id)
    {
        $sql = $this->db->prepare("SELECT SUM(tutar) as total_income FROM puantaj WHERE person = ?");
        $sql->execute([$person_id]);
        return $sql->fetch(PDO::FETCH_OBJ) ?? 0;
    }

    //Personelin göreve başlama tarihinden önceki ve işten ayrılma tarihinden sonraki tüm puantajları sil
    public function deletePastAttendanceRecords($person_id, $job_start_date, $job_end_date)
    {

        $job_start_date = Date::Ymd($job_start_date);
        $job_end_date = Date::Ymd($job_end_date);
        $sql = $this->db->prepare("DELETE FROM $this->table WHERE person = ? and (gun < ? or gun > ?)");
        $sql->execute([$person_id, $job_start_date, $job_end_date]);
    }

    public function deletePuantajGun($id)
    {
        $puantaj = $this->find($id);
        if ($puantaj) {
            require_once __DIR__ . '/ActivityLogModel.php';
            require_once __DIR__ . '/Persons.php';
            $personObj = new Persons();
            $person_name = $personObj->getPersonByField($puantaj->person, 'full_name');
            $log_date = date('d.m.Y', strtotime($puantaj->gun));
            ActivityLogModel::log('puantaj', 'delete', "{$person_name} için {$log_date} tarihli puantaj silindi.");
        }
        $sql = $this->db->prepare("DELETE FROM $this->table WHERE id = ?");
        $sql->execute([$id]);
    }

    //Projenin toplam çalışan personel sayısını getir
    public function getTotalWorksPersonByProject($project_id)
    {
        $sql = $this->db->prepare("SELECT COUNT(DISTINCT person) as total_person FROM puantaj WHERE project_id = ?");
        $sql->execute([$project_id]);
        return $sql->fetch(PDO::FETCH_OBJ)->total_person ?? 0;
    }

    //Projenin toplam çalışma saatini getir
    public function getTotalWorksHourByProject($project_id)
    {
        $sql = $this->db->prepare("SELECT SUM(saat) as total_hour FROM puantaj WHERE project_id = ?");
        $sql->execute([$project_id]);
        return $sql->fetch(PDO::FETCH_OBJ)->total_hour ?? 0;
    }

    //Projenin toplam çalışma tutarını getir
    public function getTotalWorksBalanceByProject($project_id)
    {
        $sql = $this->db->prepare("SELECT SUM(tutar) as total_balance FROM puantaj WHERE project_id = ?");
        $sql->execute([$project_id]);
        return $sql->fetch(PDO::FETCH_OBJ)->total_balance ?? 0;
    }

    /**
     * Birden fazla personelin belirli tarih aralığındaki tüm puantaj kayıtlarını tek sorguda getirir.
     * N+1 query problemini çözmek için toplu veri çekme.
     * Dönen veri: [person_id][gun_tiresiz] = {puantaj_id, project_id, ...}
     */
    public function getAllPuantajForPersons($person_ids, $start_date, $end_date)
    {
        if (empty($person_ids)) return [];

        // gun sütunu hem tireli (2026-05-09) hem tiresiz (20260509) formatta olabilir
        $start_dash = (strpos($start_date, '-') !== false) ? $start_date : substr($start_date, 0, 4) . '-' . substr($start_date, 4, 2) . '-' . substr($start_date, 6, 2);
        $start_nodash = str_replace('-', '', $start_date);
        $end_dash = (strpos($end_date, '-') !== false) ? $end_date : substr($end_date, 0, 4) . '-' . substr($end_date, 4, 2) . '-' . substr($end_date, 6, 2);
        $end_nodash = str_replace('-', '', $end_date);

        $placeholders = implode(',', array_fill(0, count($person_ids), '?'));
        $sql = $this->db->prepare("SELECT * FROM puantaj WHERE person IN ($placeholders) AND ((gun >= ? AND gun <= ?) OR (gun >= ? AND gun <= ?))");
        
        $params = array_merge($person_ids, [$start_dash, $end_dash, $start_nodash, $end_nodash]);
        $sql->execute($params);
        $rows = $sql->fetchAll(PDO::FETCH_OBJ);

        // person_id ve gun bazında indexle (tiresiz formatta normalize et)
        $indexed = [];
        foreach ($rows as $row) {
            $gun = str_replace('-', '', $row->gun); // Normalize: tiresiz format
            $indexed[$row->person][$gun] = $row;
        }
        return $indexed;
    }

    /**
     * Tüm puantaj türlerini tek sorguda getirir (cache için).
     * Dönen veri: [id] = {PuantajKod, ArkaPlanRengi, FontRengi, ...}
     */
    public function getAllPuantajTurleri()
    {
        $sql = $this->db->prepare("SELECT * FROM puantajturu");
        $sql->execute();
        $rows = $sql->fetchAll(PDO::FETCH_OBJ);

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row->id] = $row;
        }
        return $indexed;
    }

}
