<?php
require_once 'BaseModel.php';

class ProjectTask extends Model
{
    protected $table = 'project_tasks';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function saveWithAttr($data)
    {
        $id = parent::saveWithAttr($data);
        require_once __DIR__ . '/ActivityLogModel.php';
        $action = (isset($data['id']) && $data['id'] > 0) ? 'güncellendi' : 'eklendi';
        $name = $data['task_name'] ?? 'Görev';
        ActivityLogModel::log('project_task', (isset($data['id']) && $data['id'] > 0) ? 'update' : 'add', "Proje görevi {$action}: {$name}");
        return $id;
    }

    public function delete($id)
    {
        $task = $this->find($id);
        if ($task) {
            require_once __DIR__ . '/ActivityLogModel.php';
            ActivityLogModel::log('project_task', 'delete', "Proje görevi silindi: " . ($task->task_name ?? 'Görev'));
        }
        return parent::delete($id);
    }

    public function getTasksByProject($project_id, $firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM project_tasks WHERE project_id = ? AND firm_id = ? ORDER BY sort_order ASC, id ASC");
        $sql->execute([$project_id, $firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function belongsToFirm($task_id, $firm_id)
    {
        $sql = $this->db->prepare("SELECT COUNT(*) as total FROM project_tasks WHERE id = ? AND firm_id = ?");
        $sql->execute([$task_id, $firm_id]);
        return (int)($sql->fetch(PDO::FETCH_OBJ)->total ?? 0) > 0;
    }

    public static function statusLabel($status)
    {
        $labels = [
            0 => ['label' => 'Bekliyor',      'class' => 'badge-secondary'],
            1 => ['label' => 'Devam Ediyor',  'class' => 'badge-primary'],
            2 => ['label' => 'Tamamlandı',    'class' => 'badge-success'],
            3 => ['label' => 'İptal',         'class' => 'badge-danger'],
        ];
        return $labels[(int)$status] ?? $labels[0];
    }
}
