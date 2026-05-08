<?php
require_once "BaseModel.php";
class Todo extends Model
{
    protected $table = 'todos';
    protected $firm_id;

    public function __construct()
    {
        parent::__construct($this->table);
        $this->firm_id = $_SESSION['firm_id'];
    }

    //Firma id sine göre tüm todoları getirir
    public function getTodosByFirm()
    {
        $sql = "SELECT t.*, p.project_name 
                FROM $this->table t 
                LEFT JOIN projects p ON t.project_id = p.id 
                WHERE t.firm_id = :firm_id 
                ORDER BY t.status ASC, t.created_at DESC";
        $query = $this->db->prepare($sql);
        $query->execute(['firm_id' => $this->firm_id]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function saveWithAttr($data)
    {
        $id = parent::saveWithAttr($data);
        require_once __DIR__ . '/ActivityLogModel.php';
        $action = (isset($data['id']) && $data['id'] > 0) ? 'güncellendi' : 'eklendi';
        $title = $data['title'] ?? 'Görev';
        ActivityLogModel::log('todo', (isset($data['id']) && $data['id'] > 0) ? 'update' : 'add', "Görev {$action}: {$title}");
        return $id;
    }

    public function delete($id)
    {
        $todo = $this->find($id);
        if ($todo) {
            require_once __DIR__ . '/ActivityLogModel.php';
            ActivityLogModel::log('todo', 'delete', "Görev silindi: " . ($todo->title ?? 'Görev'));
        }
        return parent::delete($id);
    }

    

}
