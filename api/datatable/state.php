<?php

define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/App/bootstrap.php';
require_once ROOT . '/App/Helper/session_security.php';
require_once ROOT . '/Model/UserDatatableStateModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    puantorStartSecureSession();

    if (empty($_SESSION['user']) || empty($_SESSION['user']->id)) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Oturum açmanız gerekiyor.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userId = (int) $_SESSION['user']->id;
    $firmId = isset($_SESSION['firm_id']) ? (int) $_SESSION['firm_id'] : null;

    $model = new UserDatatableStateModel();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_REQUEST['action'] ?? '';

    // 1. GET ALL: Kullanıcının tüm tablo durumlarını getirir
    if ($method === 'GET' && $action === 'get_all') {
        $states = $model->getAllStatesForUser($userId);
        echo json_encode([
            'status' => 'success',
            'data' => $states
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. GET SINGLE: Belirli bir tablonun durumunu getirir
    if ($method === 'GET') {
        $tableKey = trim((string) ($_GET['table_key'] ?? ''));
        if (empty($tableKey)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Tablo anahtarı (table_key) belirtilmedi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $model->getState($userId, $tableKey);
        echo json_encode([
            'status' => 'success',
            'data' => $state
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. POST / SAVE: Tablo durumunu kaydeder (Upsert)
    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            $tableKey = trim((string) ($jsonData['table_key'] ?? ''));
            $stateData = $jsonData['state_data'] ?? null;
            $actionType = $jsonData['action'] ?? $action;
        } else {
            $tableKey = trim((string) ($_POST['table_key'] ?? ''));
            $stateData = $_POST['state_data'] ?? null;
            $actionType = $_POST['action'] ?? $action;
        }

        if ($actionType === 'reset') {
            if (empty($tableKey)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Tablo anahtarı belirtilmedi.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $success = $model->resetState($userId, $tableKey);
            echo json_encode([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Tablo ayarları varsayılana sıfırlandı.' : 'Sıfırlama başarısız oldu.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($tableKey) || $stateData === null) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Geçersiz parametreler.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $saved = $model->saveState($userId, $firmId, $tableKey, $stateData);

        echo json_encode([
            'status' => $saved ? 'success' : 'error',
            'message' => $saved ? 'Ayarlar başarıyla kaydedildi.' : 'Kaydetme başarısız oldu.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4. DELETE / RESET: Tablo durumunu sıfırlar
    if ($method === 'DELETE') {
        $tableKey = trim((string) ($_GET['table_key'] ?? ''));
        if (empty($tableKey)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Tablo anahtarı belirtilmedi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $success = $model->resetState($userId, $tableKey);
        echo json_encode([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Tablo ayarları varsayılana sıfırlandı.' : 'Sıfırlama başarısız oldu.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    system_log_exception($e, ['operation' => 'api_datatable_state']);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Sunucu hatası oluştu.'], JSON_UNESCAPED_UNICODE);
}
