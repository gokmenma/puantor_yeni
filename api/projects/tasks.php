<?php

require_once '../../Database/require.php';
require_once '../../Model/ProjectTask.php';
require_once '../../App/Helper/date.php';

use App\Helper\Date;

$taskModel = new ProjectTask();
$firm_id   = (int)($_SESSION['firm_id'] ?? 0);
$action    = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_tasks') {
    $project_id = (int)($_POST['project_id'] ?? 0);

    if ($project_id <= 0 || $firm_id <= 0) {
        echo json_encode(['data' => [], 'summary' => null]);
        exit;
    }

    $tasks = $taskModel->getTasksByProject($project_id, $firm_id);
    $rows  = [];
    $i     = 0;

    foreach ($tasks as $task) {
        $i++;
        $statusInfo = ProjectTask::statusLabel($task->status);

        $progressHtml = '<div class="progress" style="height:6px;">'
            . '<div class="progress-bar" style="width:' . $task->completion_percent . '%" role="progressbar"></div>'
            . '</div><small class="text-muted">%' . $task->completion_percent . '</small>';

        $actionsHtml = '<div class="d-flex gap-1 justify-content-end">'
            . '<a href="#" class="btn btn-sm edit-task-btn" data-tooltip="Düzenle" data-id="' . $task->id . '">'
            . '<i class="ti ti-edit"></i></a>'
            . '<a href="#" class="btn btn-sm text-danger delete-task-btn" data-tooltip="Sil" data-id="' . $task->id . '">'
            . '<i class="ti ti-trash"></i></a>'
            . '</div>';

        $nameHtml = '<div class="fw-medium">' . htmlspecialchars($task->task_name) . '</div>';
        if ($task->description) {
            $short     = mb_substr($task->description, 0, 60);
            $ellipsis  = mb_strlen($task->description) > 60 ? '…' : '';
            $nameHtml .= '<small class="text-muted">' . htmlspecialchars($short) . $ellipsis . '</small>';
        }

        $rows[] = [
            'rownum'             => $i,
            'id'                 => $task->id,
            'task_name'          => $nameHtml,
            'task_name_raw'      => $task->task_name,
            'description'        => $task->description ?? '',
            'responsible'        => htmlspecialchars($task->responsible ?? '-'),
            'responsible_raw'    => $task->responsible ?? '',
            'start_date'         => $task->start_date ? Date::dmY($task->start_date) : '-',
            'end_date'           => $task->end_date ? Date::dmY($task->end_date) : '-',
            'start_date_raw'     => $task->start_date ?? '',
            'end_date_raw'       => $task->end_date ?? '',
            'status'             => $task->status,
            'status_label'       => $statusInfo['label'],
            'status_class'       => $statusInfo['class'],
            'status_html'        => '<span class="badge ' . $statusInfo['class'] . '">' . $statusInfo['label'] . '</span>',
            'completion_percent' => (int)$task->completion_percent,
            'progress_html'      => $progressHtml,
            'actions_html'       => $actionsHtml,
        ];
    }

    $total     = count($rows);
    $completed = count(array_filter($rows, fn($r) => $r['status'] == 2));
    $overall   = $total > 0 ? round(array_sum(array_column($rows, 'completion_percent')) / $total) : 0;

    echo json_encode([
        'data'    => $rows,
        'summary' => ['total' => $total, 'completed' => $completed, 'overall' => $overall],
    ]);
    exit;
}

if ($action === 'save_task') {
    $task_id    = (int)($_POST['task_id'] ?? 0);
    $project_id = (int)($_POST['project_id'] ?? 0);

    if ($project_id <= 0 || $firm_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz proje']);
        exit;
    }

    if ($task_id > 0 && !$taskModel->belongsToFirm($task_id, $firm_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz işlem']);
        exit;
    }

    $data = [
        'firm_id'            => $firm_id,
        'project_id'         => $project_id,
        'task_name'          => trim($_POST['task_name'] ?? ''),
        'description'        => trim($_POST['description'] ?? ''),
        'responsible'        => trim($_POST['responsible'] ?? ''),
        'start_date'         => !empty($_POST['start_date']) ? Date::Ymd($_POST['start_date']) : null,
        'end_date'           => !empty($_POST['end_date'])   ? Date::Ymd($_POST['end_date'])   : null,
        'status'             => (int)($_POST['status'] ?? 0),
        'completion_percent' => min(100, max(0, (int)($_POST['completion_percent'] ?? 0))),
        'created_by'         => $_SESSION['user']->id ?? null,
    ];

    if (empty($data['task_name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Görev adı boş olamaz']);
        exit;
    }

    if ($task_id > 0) {
        $data['id'] = $task_id;
    }

    try {
        $taskModel->saveWithAttr($data);
        echo json_encode([
            'status'  => 'success',
            'message' => $task_id > 0 ? 'Görev güncellendi' : 'Görev eklendi',
        ]);
    } catch (PDOException $ex) {
        echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
    }
    exit;
}

if ($action === 'delete_task') {
    $task_id = (int)($_POST['task_id'] ?? 0);

    if ($task_id <= 0 || !$taskModel->belongsToFirm($task_id, $firm_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz işlem']);
        exit;
    }

    try {
        $taskModel->delete($task_id);
        echo json_encode(['status' => 'success', 'message' => 'Görev silindi']);
    } catch (PDOException $ex) {
        echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
    }
    exit;
}
