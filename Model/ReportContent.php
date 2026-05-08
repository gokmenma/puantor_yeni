<?php

require_once "BaseModel.php";


class ReportContent extends Model
{
    protected $table = 'report_ysc_content';
    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getYscReportContent($id)
    {
        $sql = $this->db->prepare("SELECT * FROM report_ysc_content WHERE report_id = ?");
        $sql->execute(array($id));
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function deleteByReportId($id)
    {
        $sql = $this->db->prepare("DELETE FROM report_ysc_content WHERE report_id = ?");
        $sql->execute(array($id));
    }

    public function saveReportsContent($data)
    {
        $this->attributes = $data;
        // $this->primaryKey = $data["id"];
        // $this->isNew = true;
        $this->isNew = true;
        if (isset($data["id"]) && $data["id"] > 0) {
            $this->isNew = false;
        }
        parent::save();
    }
}
