<?php
if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/../../'));
}
set_include_path(get_include_path() . PATH_SEPARATOR . ROOT . '/Model');

require_once ROOT . "/Database/require.php";
require_once ROOT . '/Model/Projects.php';
require_once ROOT . '/Model/JobGroupsModel.php';
require_once ROOT . '/Model/Auths.php';

use App\Helper\Security;

header('Content-Type: application/json');

$auth = new Auths();

if (!isset($_SESSION['user']) || !isset($_SESSION['firm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim. Lütfen tekrar giriş yapın.']);
    exit;
}

// Proje listesi görme yetkisi olanlar personel listesini de görebilir
if (!$auth->hasPermission("project_add_update")) {
    echo json_encode(['status' => 'error', 'message' => 'Bu bilgiyi görme yetkiniz bulunmamaktadır.']);
    exit;
}

$project_id = $_GET['project_id'] ?? 0;
$firm_id = $_SESSION['firm_id'];

if (!$project_id) {
    echo json_encode(['status' => 'error', 'message' => 'Proje ID belirtilmedi.']);
    exit;
}

try {
    $sql = "SELECT p.id, p.full_name, p.kimlik_no, p.ekip, jg.name as job_group_name
            FROM project_person pp
            JOIN persons p ON p.id = pp.person_id
            LEFT JOIN job_groups jg ON jg.id = p.job_group
            WHERE pp.project_id = ? AND p.firm_id = ? AND p.deleted_at IS NULL";
    
    $query = $db->prepare($sql);
    $query->execute([$project_id, $firm_id]);
    $personnel = $query->fetchAll(PDO::FETCH_OBJ);
    
    $data = [];
    foreach ($personnel as $person) {
        $data[] = [
            'id' => $person->id,
            'encrypted_id' => Security::encrypt($person->id),
            'name' => $person->full_name,
            'tckn' => Security::safeDecrypt($person->kimlik_no),
            'team_name' => $person->ekip,
            'job_group_name' => $person->job_group_name
        ];
    }
    
    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Bir hata oluştu: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
