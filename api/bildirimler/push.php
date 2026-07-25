<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/Database/require.php';
if (file_exists(ROOT . '/vendor/autoload.php')) require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/Service/WebPushSender.php';
require_once ROOT . '/Service/PushBildirimService.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/GonderilenBildirimlerModel.php';
require_once ROOT . '/Model/PersonelBildirimModel.php';
require_once ROOT . '/Model/DuyuruModel.php';

use Service\PushBildirimService;

if (!isset($_SESSION['user'])) {
    ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.'], JSON_UNESCAPED_UNICODE); exit;
}

$firma_id      = (int) ($_SESSION['firm_id'] ?? 0);
$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
$is_main_user  = (int) ($_SESSION['user']->is_main_user ?? 0) === 1
    || (int) ($_SESSION['user']->parent_id ?? -1) === 0;

if (!$is_superadmin && !$is_main_user && (int) ($_SESSION['user']->firm_id ?? 0) !== $firma_id) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Bu firma için işlem yetkiniz yok.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $action = $_REQUEST['action'] ?? '';

    if ($action === 'stats') {
        $scopeSql = $is_superadmin ? '' : ' AND firma_id = ?';
        $scopeParams = $is_superadmin ? [] : [$firma_id];

        $stmt = $db->prepare("SELECT COUNT(*) FROM persons WHERE deleted_at IS NULL{$scopeSql}");
        $stmt->execute($scopeParams);
        $total = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT user_id)
             FROM push_subscriptions
             WHERE user_type = 'personel'{$scopeSql}"
        );
        $stmt->execute($scopeParams);
        $abone = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE firm_id = ? AND deleted_at IS NULL");
        $stmt->execute([$firma_id]);
        $toplamKullanici = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(*)
             FROM persons
             WHERE firm_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$firma_id]);
        $firmaToplamPersonel = (int) $stmt->fetchColumn();

        ob_clean();
        echo json_encode([
            'status'                 => 'success',
            'toplam'                 => $total,
            'abone'                  => $abone,
            'abone_degil'            => max(0, $total - $abone),
            'hedef_toplam_personel'  => $firmaToplamPersonel,
            'toplam_kullanici'       => $toplamKullanici,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'list') {
        $gModel = new GonderilenBildirimlerModel();
        $list = $gModel->getList($is_superadmin ? null : $firma_id);

        $person_map = [];
        $persons_by_firm = [];
        $personStmt = $db->query("SELECT id, firm_id, full_name FROM persons WHERE deleted_at IS NULL");
        foreach ($personStmt->fetchAll(PDO::FETCH_OBJ) as $p) {
            $person_map[(int) $p->id] = $p->full_name;
            $persons_by_firm[(int) $p->firm_id][] = $p->full_name;
        }
        $user_map = [];
        $users_by_firm = [];
        $userStmt = $db->query("SELECT id, firm_id, full_name FROM users WHERE deleted_at IS NULL");
        foreach ($userStmt->fetchAll(PDO::FETCH_OBJ) as $user) {
            $user_map[(int) $user->id] = $user->full_name;
            $users_by_firm[(int) $user->firm_id][] = $user->full_name;
        }

        foreach ($list as $row) {
            $row->hedef_turu = $row->hedef_turu ?: 'personel';
            $isUserTarget = $row->hedef_turu === 'kullanici';
            $names = [];
            if ($row->hedef === 'hepsi') {
                $row->hedef_aciklama = $row->hedef_turu === 'kullanici'
                    ? 'Tüm Sistem Kullanıcıları'
                    : 'Tüm Personeller';
                $names = $isUserTarget
                    ? ($users_by_firm[(int) $row->firma_id] ?? [])
                    : ($persons_by_firm[(int) $row->firma_id] ?? []);
            } else {
                $ids = array_filter(explode(',', $isUserTarget ? ($row->kullanici_ids ?? '') : ($row->personel_ids ?? '')));
                $nameMap = $isUserTarget ? $user_map : $person_map;
                foreach ($ids as $id) {
                    if (isset($nameMap[(int) $id])) {
                        $names[] = $nameMap[(int) $id];
                    }
                }
                $row->hedef_aciklama = empty($names)
                    ? ($isUserTarget ? 'Seçili Sistem Kullanıcıları' : 'Seçili Personeller')
                    : implode(', ', $names);
            }
            $row->alici_listesi = array_values($names);
            $row->alici_sayisi = count($row->alici_listesi);
        }

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'list' => $list
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'system-list') {
        $duyuruModel = new DuyuruModel();
        $list = $duyuruModel->getSistemBildirimleri($is_superadmin ? null : $firma_id);
        $hedefler = [
            'aboneler' => 'Tüm Aboneler',
            'herkese' => 'Firma Geneli',
            'firma_kullanicilari' => 'Firma Kullanıcıları',
            'firma_personelleri' => 'Firma Personelleri',
            'bazi_firmalar' => 'Seçili Firmalar',
            'bazi_kullanicilar' => 'Seçili Kullanıcılar',
            'bazi_personeller' => 'Seçili Personeller',
        ];

        $firmNames = [];
        foreach ($db->query("SELECT id, firm_name FROM myfirms")->fetchAll(PDO::FETCH_OBJ) as $firm) {
            $firmNames[(int) $firm->id] = $firm->firm_name;
        }
        $userFirms = [];
        $userNames = [];
        $usersByFirm = [];
        foreach ($db->query("SELECT id, firm_id, full_name FROM users WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_OBJ) as $user) {
            $userFirms[(int) $user->id] = (int) $user->firm_id;
            $userNames[(int) $user->id] = $user->full_name;
            $usersByFirm[(int) $user->firm_id][] = $user->full_name;
        }
        $personFirms = [];
        $personNames = [];
        $personsByFirm = [];
        foreach ($db->query("SELECT id, firm_id, full_name FROM persons WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_OBJ) as $person) {
            $personFirms[(int) $person->id] = (int) $person->firm_id;
            $personNames[(int) $person->id] = $person->full_name;
            $personsByFirm[(int) $person->firm_id][] = $person->full_name;
        }

        foreach ($list as $row) {
            $row->hedef_aciklama = $hedefler[$row->hedef_tip] ?? 'Belirli Alıcılar';
            $row->gonderen_adi = 'Sistem';
            $targets = $duyuruModel->getHedefler((int) $row->id);
            $targetFirmIds = [];
            if ((int) ($row->hedef_firma_id ?? 0) > 0) {
                $targetFirmIds[] = (int) $row->hedef_firma_id;
            } elseif ($row->hedef_tip === 'aboneler') {
                $row->firma_adi = 'Tüm Aboneler';
            } else {
                foreach ($targets as $target) {
                    if ($target->hedef_tip === 'firma') {
                        $targetFirmIds[] = (int) $target->hedef_id;
                    } elseif ($target->hedef_tip === 'kullanici' && isset($userFirms[(int) $target->hedef_id])) {
                        $targetFirmIds[] = $userFirms[(int) $target->hedef_id];
                    } elseif ($target->hedef_tip === 'personel' && isset($personFirms[(int) $target->hedef_id])) {
                        $targetFirmIds[] = $personFirms[(int) $target->hedef_id];
                    }
                }
            }
            $targetFirmIds = array_values(array_unique($targetFirmIds));
            $names = array_values(array_filter(array_map(
                fn($id) => $firmNames[$id] ?? null,
                $targetFirmIds
            )));
            if ($row->hedef_tip !== 'aboneler') {
                $row->firma_adi = $names ? implode(', ', $names) : ($firmNames[$firma_id] ?? '—');
            }

            $recipients = [];
            if ($row->hedef_tip === 'bazi_kullanicilar') {
                foreach ($targets as $target) {
                    if ($target->hedef_tip === 'kullanici' && isset($userNames[(int) $target->hedef_id])) {
                        $recipients[] = $userNames[(int) $target->hedef_id];
                    }
                }
            } elseif ($row->hedef_tip === 'bazi_personeller') {
                foreach ($targets as $target) {
                    if ($target->hedef_tip === 'personel' && isset($personNames[(int) $target->hedef_id])) {
                        $recipients[] = $personNames[(int) $target->hedef_id];
                    }
                }
            } elseif ($row->hedef_tip === 'bazi_firmalar') {
                foreach ($targets as $target) {
                    if ($target->hedef_tip === 'firma' && isset($firmNames[(int) $target->hedef_id])) {
                        $recipients[] = $firmNames[(int) $target->hedef_id];
                    }
                }
            } elseif ($row->hedef_tip === 'firma_kullanicilari') {
                $recipients = $usersByFirm[(int) $row->hedef_firma_id] ?? [];
            } elseif ($row->hedef_tip === 'firma_personelleri') {
                $recipients = $personsByFirm[(int) $row->hedef_firma_id] ?? [];
            } elseif ($row->hedef_tip === 'herkese') {
                $targetFirmId = (int) $row->hedef_firma_id;
                $recipients = array_merge(
                    $usersByFirm[$targetFirmId] ?? [],
                    $personsByFirm[$targetFirmId] ?? []
                );
            } elseif ($row->hedef_tip === 'aboneler') {
                $recipients = array_values($firmNames);
            }
            $row->alici_listesi = array_values($recipients);
            $row->alici_sayisi = count($row->alici_listesi);
        }

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'list' => $list,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'gonder') {
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        $requestToken = (string) ($_POST['csrf_token'] ?? '');
        if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
            throw new Exception('Güvenlik doğrulaması başarısız. Sayfayı yenileyin.');
        }
        if ($firma_id < 1) {
            throw new Exception('Bildirim göndermek için aktif bir firma seçin.');
        }

        $hedefTuru = (string) ($_POST['hedef_turu'] ?? 'personel');
        $hedef = (string) ($_POST['hedef'] ?? 'hepsi');
        $baslik = trim((string) ($_POST['baslik'] ?? ''));
        $icerik = trim((string) ($_POST['icerik'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));

        if (!in_array($hedefTuru, ['personel', 'kullanici'], true) || !in_array($hedef, ['belirli', 'hepsi'], true)) {
            throw new Exception('Geçersiz bildirim hedefi.');
        }
        if ($baslik === '' || $icerik === '') {
            throw new Exception('Başlık ve içerik zorunludur.');
        }
        if (mb_strlen($baslik) > 255 || mb_strlen($icerik) > 5000) {
            throw new Exception('Bildirim başlığı veya içeriği izin verilen uzunluğu aşıyor.');
        }

        $idField = $hedefTuru === 'kullanici' ? 'kullanici_ids' : 'personel_ids';
        $rawIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) ($_POST[$idField] ?? [])
        ), fn($id) => $id > 0)));
        $table = $hedefTuru === 'kullanici' ? 'users' : 'persons';
        $whereDeleted = 'deleted_at IS NULL';

        if ($hedef === 'hepsi') {
            $stmt = $db->prepare("SELECT id FROM {$table} WHERE firm_id = ? AND {$whereDeleted}");
            $stmt->execute([$firma_id]);
        } else {
            if (!$rawIds) {
                throw new Exception('En az bir alıcı seçmelisiniz.');
            }
            $placeholders = implode(',', array_fill(0, count($rawIds), '?'));
            $stmt = $db->prepare(
                "SELECT id FROM {$table}
                 WHERE firm_id = ? AND {$whereDeleted} AND id IN ({$placeholders})"
            );
            $stmt->execute(array_merge([$firma_id], $rawIds));
        }
        $targetIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        sort($targetIds);

        if (!$targetIds || ($hedef === 'belirli' && count($targetIds) !== count($rawIds))) {
            throw new Exception('Seçilen alıcılardan biri bu firmaya ait değil veya artık aktif değil.');
        }

        $service = new PushBildirimService($db);
        $gModel = new GonderilenBildirimlerModel();
        $sender_id = (int) $_SESSION['user']->id;
        $gModel->kaydet(
            $firma_id,
            $sender_id,
            $hedefTuru,
            $hedef,
            $hedef === 'hepsi' ? null : $targetIds,
            $baslik,
            $icerik,
            $url
        );

        if ($hedefTuru === 'personel') {
            $pBildirimModel = new PersonelBildirimModel($db);
            foreach ($targetIds as $targetId) {
                $service->personeleGonder($targetId, $firma_id, $baslik, $icerik, $url ? ['url' => $url] : []);
                $pBildirimModel->kaydet($targetId, $firma_id, $baslik, $icerik, $url);
            }
        } else {
            $duyuruModel = new DuyuruModel();
            $duyuruId = (int) $duyuruModel->ekle([
                'baslik' => $baslik,
                'icerik' => $icerik,
                'kaynak_turu' => 'kullanici',
                'olusturan_id' => $sender_id,
                'hedef_tip' => $hedef === 'hepsi' ? 'firma_kullanicilari' : 'bazi_kullanicilar',
                'hedef_firma_id' => $firma_id,
                'baslangic_tarihi' => '',
                'bitis_tarihi' => '',
                'oncelik' => 'normal',
            ]);
            if ($hedef === 'belirli') {
                $duyuruModel->setHedefler($duyuruId, 'kullanici', $targetIds);
            }
            foreach ($targetIds as $targetId) {
                $service->kullaniciyaGonder($targetId, $firma_id, $baslik, $icerik, $url ? ['url' => $url] : []);
            }
        }

        require_once ROOT . '/Model/ActivityLogModel.php';
        $targetLabel = $hedefTuru === 'kullanici' ? 'Sistem Kullanıcısı' : 'Personel';
        $target_desc = $hedef === 'hepsi' ? "Tüm {$targetLabel}lar" : count($targetIds) . " Seçili {$targetLabel}";
        ActivityLogModel::log('push_notification', 'send', "Push bildirim gönderildi. Başlık: \"{$baslik}\". Hedef: {$target_desc}.");

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => count($targetIds) . " {$targetLabel} için bildirim oluşturuldu.",
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
