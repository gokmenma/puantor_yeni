<?php
// cron/calculate_hakedis.php
if (php_sapi_name() !== 'cli') {
    die("Bu betik yalnızca komut satırından (CLI) çalıştırılabilir.\n");
}

if (!defined('ROOT')) define('ROOT', dirname(__DIR__));

try {
    require_once ROOT . '/Database/require.php';
    require_once ROOT . '/Model/IzinHakedis.php';
    require_once ROOT . '/Model/Persons.php';
    require_once ROOT . '/Model/ActivityLogModel.php';

    $hakedisModel = new IzinHakedis();
    $personsModel = new Persons();

    // Tüm aktif/silinmemiş personelleri getir
    $db = $personsModel->getDb();
    $stmt = $db->query("SELECT id, firm_id, job_start_date, birth_date FROM persons WHERE deleted_at IS NULL AND job_start_date IS NOT NULL AND job_start_date != ''");
    $personeller = $stmt->fetchAll(PDO::FETCH_OBJ);

    $toplam_hesaplanan = 0;
    foreach ($personeller as $p) {
        $toplam_hesaplanan += $hakedisModel->hesaplaVeKaydet(
            (int)$p->id,
            (int)$p->firm_id,
            $p->job_start_date,
            $p->birth_date ?? null
        );
    }

    // Sistem günlüğüne kaydet
    ActivityLogModel::log('izin_hakedis', 'cron_calculate', "Zamanlanmış görev (Cron) çalıştırıldı. Toplam {$toplam_hesaplanan} yeni hakediş oluşturuldu.");

    echo "[" . date('Y-m-d H:i:s') . "] Cron başarıyla tamamlandı. Toplam {$toplam_hesaplanan} yeni hakediş kaydı eklendi.\n";

} catch (\Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] HATA: " . $e->getMessage() . "\n";
    exit(1);
}
