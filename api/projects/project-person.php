<?php
header('Content-Type: application/json');

use App\Helper\Security;

require_once __DIR__ . "/../../Database/require.php";
require_once __DIR__ . "/../../Model/Projects.php";

$Projects = new Projects();

// Debug log
file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - POST: " . json_encode($_POST) . " - SESSION: " . (isset($_SESSION['user']) ? 'YES' : 'NO') . "\n", FILE_APPEND);

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Oturum sonlanmış. Lütfen tekrar giriş yapın."]);
    exit();
}

$status = "error";
$message = "Geçersiz işlem.";

if (isset($_POST['action']) && $_POST['action'] == "addPersonToProject") {
    //$record_id = $Projects->findById($_POST['project_id']);

    try {
        $persons = explode(",", $_POST['person_id']);
        $project_id = $_POST['project_id'];

        // Projeye kayıtlı olan personelleri al
        $existing_persons = $Projects->getPersonFromProject($project_id);
        $existing_person_ids = array_map(function ($person) {
            return $person->person_id;
        }, $existing_persons);

        // Silinecek personelleri belirle
        $persons_to_delete = array_diff($existing_person_ids, $persons);

        // Eklenecek personelleri belirle
        $persons_to_add = array_diff($persons, $existing_person_ids);

        // Silinecek personelleri sil
        foreach ($persons_to_delete as $person_id) {
            $Projects->deletePersonFromProjects($person_id, $project_id);

        }

        // Eklenecek personelleri ekle
        foreach ($persons_to_add as $person_id) {
            if ($person_id == 0 || $person_id == "") {
                continue;
            }
            
            $data = [
                    'project_id' => $project_id,
                    'person_id' => $person_id,
                    "state" => 1,
                    "user_id" => $_SESSION['user']->id,
                ];
                $Projects->addPersontoProject($data);
        }

        $status = "success";
        $message = "Personeller başarı ile güncellendi";
     
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
}

echo json_encode([
    "status" => $status,
    "message" => $message
]);