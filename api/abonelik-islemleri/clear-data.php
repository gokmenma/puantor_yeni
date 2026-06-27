<?php
define("ROOT", $_SERVER["DOCUMENT_ROOT"]);
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/UserModel.php';
require_once ROOT . '/Model/AbonelerModel.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/App/Helper/security.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Session check
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekmektedir.']);
    exit;
}

// 2. Permission check
$auths = new Auths();
$auths->hasPermissionReturn("aboneler_sayfasi");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_ids = $input['user_ids'] ?? [];
$modules  = $input['modules'] ?? [];
$password = $input['password'] ?? '';

if (empty($user_ids) || empty($modules) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler. Lütfen tüm alanları doldurun.']);
    exit;
}

$user_ids = array_values(array_filter(array_map('intval', $user_ids), fn($id) => $id > 0));
if (empty($user_ids)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı listesi.']);
    exit;
}

try {
    // 3. Verify Admin Password
    $admin_id = $_SESSION['user']->id;
    $userModel = new UserModel();
    $adminUser = $userModel->getUser($admin_id);

    if (!$adminUser || !password_verify($password, $adminUser->password)) {
        echo json_encode(['success' => false, 'message' => 'Girdiğiniz şifre hatalı.']);
        exit;
    }

    // 4. Execute Clearing for each subscriber
    $abonelerModel = new AbonelerModel();
    $clearedCount = 0;
    
    foreach ($user_ids as $sub_id) {
        // Fetch subscriber info for logging
        $subscriber = $userModel->getUser($sub_id);
        $subName = $subscriber ? $subscriber->full_name : 'Bilinmeyen Abone';
        
        $result = $abonelerModel->clearSubscriberData($sub_id, $modules);
        if ($result) {
            $clearedCount++;
            // Log the action
            $modulesStr = implode(', ', $modules);
            ActivityLogModel::log(
                'subscriber_data_clear',
                'delete',
                "Abone ({$subName}, ID: {$sub_id}) verileri temizlendi. Silinen modüller: [{$modulesStr}]"
            );
        }
    }

    if ($clearedCount > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Seçili {$clearedCount} abonenin verileri başarıyla temizlendi."
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Veriler temizlenemedi.'
        ]);
    }

} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Sistem Hatası: ' . $e->getMessage()
    ]);
}
exit;
