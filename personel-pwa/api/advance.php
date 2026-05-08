<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database/require.php';

$action = $_REQUEST['action'] ?? '';

if ($action == 'list') {
    $person_id = $_GET['person_id'] ?? 0;
    $query = $db->prepare("SELECT *, DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as created_at FROM personel_avans_talepleri WHERE person_id = ? ORDER BY id DESC");
    $query->execute([$person_id]);
    $list = $query->fetchAll(PDO::FETCH_OBJ);
    echo json_encode(['status' => 'success', 'list' => $list]);

} elseif ($action == 'create') {
    $person_id = $_POST['person_id'] ?? 0;
    $firm_id = $_POST['firm_id'] ?? 0;
    $tutar = $_POST['tutar'] ?? 0;
    $aciklama = $_POST['aciklama'] ?? '';
    $hedef_ay = $_POST['hedef_ay'] ?? null;
    $hedef_yil = $_POST['hedef_yil'] ?? null;

    $query = $db->prepare("INSERT INTO personel_avans_talepleri (person_id, firm_id, tutar, aciklama, hedef_ay, hedef_yil) VALUES (?, ?, ?, ?, ?, ?)");
    try {
        $query->execute([$person_id, $firm_id, $tutar, $aciklama, $hedef_ay, $hedef_yil]);
        echo json_encode(['status' => 'success', 'message' => 'Talep başarıyla oluşturuldu.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
    }
} elseif ($action == 'update') {
    $id = $_POST['id'] ?? 0;
    $person_id = $_POST['person_id'] ?? 0;
    $tutar = $_POST['tutar'] ?? 0;
    $aciklama = $_POST['aciklama'] ?? '';

    // Check if the advance exists and is still pending (durum == 0)
    $check = $db->prepare("SELECT * FROM personel_avans_talepleri WHERE id = ? AND person_id = ?");
    $check->execute([$id, $person_id]);
    $advance = $check->fetch(PDO::FETCH_OBJ);

    if (!$advance) {
        echo json_encode(['status' => 'error', 'message' => 'Talep bulunamadı veya yetkiniz yok.']);
        exit;
    }

    if ($advance->durum != 0) {
        echo json_encode(['status' => 'error', 'message' => 'Onaylanmış veya reddedilmiş talepler güncellenemez.']);
        exit;
    }

    $query = $db->prepare("UPDATE personel_avans_talepleri SET tutar = ?, aciklama = ? WHERE id = ? AND person_id = ?");
    try {
        $query->execute([$tutar, $aciklama, $id, $person_id]);
        echo json_encode(['status' => 'success', 'message' => 'Talep başarıyla güncellendi.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
    }
}
