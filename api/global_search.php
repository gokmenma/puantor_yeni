<?php
// Buffer all outputs to guarantee clean JSON
ob_start();

!defined('ROOT') ? define('ROOT', dirname(__DIR__)) : false;
require_once ROOT . '/App/bootstrap.php';
require_once ROOT . '/App/Helper/session_security.php';
puantorStartSecureSession();

require_once ROOT . '/Model/GlobalSearchModel.php';
require_once ROOT . '/Model/ActivityLogModel.php';

// Session ve Giriş Kontrolü
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Oturum süreniz dolmuş veya giriş yapmanız gerekmektedir.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$query    = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$category = isset($_GET['category']) ? trim((string)$_GET['category']) : 'all';
$limit    = isset($_GET['limit']) ? min(max((int)$_GET['limit'], 1), 20) : 8;

$allowedCategories = ['all', 'persons', 'projects', 'financial', 'icra', 'izin', 'tasks'];
if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}

// Arama audit/log kaydı
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'log_search') {
    $query = trim((string) ($_POST['query'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? 'all'));
    $completionType = trim((string) ($_POST['completion_type'] ?? 'closed'));
    $totalFound = max(0, (int) ($_POST['total_found'] ?? 0));
    $selectedType = trim((string) ($_POST['selected_type'] ?? ''));
    $selectedId = trim((string) ($_POST['selected_id'] ?? ''));

    if (!in_array($category, $allowedCategories, true)) {
        $category = 'all';
    }
    if (!in_array($completionType, ['completed', 'closed', 'cleared', 'result_selected'], true)) {
        $completionType = 'closed';
    }

    if (mb_strlen($query, 'UTF-8') >= 2) {
        $desc = "Global Arama: '{$query}' (Kategori: {$category}, Bulunan: {$totalFound}, Durum: {$completionType})";
        if ($selectedType !== '') {
            $desc .= " -> Seçilen: {$selectedType} #{$selectedId}";
        }
        ActivityLogModel::log('global_search', 'search', $desc);
    }

    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $firmId = (int)($_SESSION['firm_id'] ?? 0);
    $userId = (int)($_SESSION['user']->id ?? 0);

    $searchModel = new GlobalSearchModel();
    $data = $searchModel->search($query, $category, $limit, $firmId, $userId);

    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'success',
        'query'   => $query,
        'counts'  => $data['counts'],
        'results' => $data['results']
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (\Throwable $e) {
    error_log('Global Search API Error: ' . $e->getMessage());

    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Arama işlemi sırasında bir hata oluştu.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
