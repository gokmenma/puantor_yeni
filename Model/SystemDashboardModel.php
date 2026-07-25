<?php

require_once __DIR__ . '/BaseModel.php';

class SystemDashboardModel extends Model
{
    public function __construct()
    {
        parent::__construct('users');
    }

    public function getSummary(): object
    {
        $sql = $this->db->query("
            SELECT
                (
                    SELECT COUNT(*)
                    FROM users
                    WHERE (parent_id = 0 OR parent_id = id)
                      AND COALESCE(superadmin, 0) = 0
                      AND deleted_at IS NULL
                ) AS total_subscribers,
                (
                    SELECT COUNT(*)
                    FROM myfirms
                    WHERE deleted_at IS NULL OR deleted_at IN ('', '0')
                ) AS total_firms,
                (
                    SELECT COUNT(*)
                    FROM users
                    WHERE COALESCE(superadmin, 0) = 0
                      AND deleted_at IS NULL
                      AND status = 1
                ) AS active_users,
                (
                    SELECT COUNT(DISTINCT kullanici_id)
                    FROM kullanici_abonelikleri
                    WHERE durum = 'aktif'
                      AND bitis_tarihi >= CURDATE()
                ) AS active_subscriptions,
                (
                    SELECT COUNT(DISTINCT kullanici_id)
                    FROM kullanici_abonelikleri
                    WHERE durum = 'aktif'
                      AND bitis_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ) AS expiring_subscriptions,
                (
                    SELECT COUNT(*)
                    FROM users u
                    WHERE (u.parent_id = 0 OR u.parent_id = u.id)
                      AND COALESCE(u.superadmin, 0) = 0
                      AND u.deleted_at IS NULL
                      AND u.user_type = 1
                      AND u.created_at >= DATE_SUB(NOW(), INTERVAL 15 DAY)
                      AND NOT EXISTS (
                          SELECT 1
                          FROM kullanici_abonelikleri ka
                          WHERE ka.kullanici_id = u.id
                            AND ka.durum = 'aktif'
                            AND ka.bitis_tarihi >= CURDATE()
                      )
                ) AS trial_subscribers,
                (
                    SELECT COUNT(*)
                    FROM activity_logs
                    WHERE created_at >= CURDATE()
                ) AS activities_today,
                (
                    SELECT COUNT(DISTINCT user_id)
                    FROM login_logs
                    WHERE login_time >= CURDATE()
                ) AS users_logged_in_today
        ");

        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function getMonthlyTrend(int $months = 12): array
    {
        $months = max(1, min($months, 24));
        $startDate = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));

        $subscriptionStmt = $this->db->prepare("
            SELECT DATE_FORMAT(olusturma_tarihi, '%Y-%m') AS month_key, COUNT(*) AS total
            FROM kullanici_abonelikleri
            WHERE olusturma_tarihi >= ?
            GROUP BY DATE_FORMAT(olusturma_tarihi, '%Y-%m')
        ");
        $subscriptionStmt->execute([$startDate]);
        $subscriptionRows = $subscriptionStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $subscriberStmt = $this->db->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total
            FROM users
            WHERE created_at >= ?
              AND (parent_id = 0 OR parent_id = id)
              AND COALESCE(superadmin, 0) = 0
              AND deleted_at IS NULL
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ");
        $subscriberStmt->execute([$startDate]);
        $subscriberRows = $subscriberStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $labels = [];
        $subscriptions = [];
        $subscribers = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $timestamp = strtotime('-' . $i . ' months');
            $key = date('Y-m', $timestamp);
            $labels[] = date('m.Y', $timestamp);
            $subscriptions[] = (int) ($subscriptionRows[$key] ?? 0);
            $subscribers[] = (int) ($subscriberRows[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'subscriptions' => $subscriptions,
            'subscribers' => $subscribers,
        ];
    }

    public function getSubscriptionStatuses(): array
    {
        $sql = $this->db->query("
            SELECT latest.durum, COUNT(*) AS total
            FROM kullanici_abonelikleri latest
            INNER JOIN (
                SELECT kullanici_id, MAX(id) AS latest_id
                FROM kullanici_abonelikleri
                GROUP BY kullanici_id
            ) selected ON selected.latest_id = latest.id
            GROUP BY latest.durum
            ORDER BY total DESC
        ");

        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getRecentSubscribers(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $sql = $this->db->prepare("
            SELECT
                u.id,
                u.full_name,
                u.email,
                u.created_at,
                u.status,
                ka.durum AS subscription_status,
                ka.baslangic_tarihi,
                ka.bitis_tarihi,
                ap.ad AS package_name,
                (
                    SELECT COUNT(*)
                    FROM myfirms mf
                    WHERE mf.user_id = u.id
                      AND (mf.deleted_at IS NULL OR mf.deleted_at IN ('', '0'))
                ) AS firm_count
            FROM users u
            LEFT JOIN (
                SELECT ka1.*
                FROM kullanici_abonelikleri ka1
                INNER JOIN (
                    SELECT kullanici_id, MAX(id) AS latest_id
                    FROM kullanici_abonelikleri
                    GROUP BY kullanici_id
                ) ka2 ON ka2.latest_id = ka1.id
            ) ka ON ka.kullanici_id = u.id
            LEFT JOIN abonelik_paketleri ap ON ap.id = ka.paket_id
            WHERE (u.parent_id = 0 OR u.parent_id = u.id)
              AND COALESCE(u.superadmin, 0) = 0
              AND u.deleted_at IS NULL
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        $sql->bindValue(1, $limit, PDO::PARAM_INT);
        $sql->execute();

        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getRecentActivities(int $limit = 12): array
    {
        $limit = max(1, min($limit, 50));
        $sql = $this->db->prepare("
            SELECT
                a.activity_type,
                a.action,
                a.description,
                a.created_at,
                u.full_name AS user_name,
                mf.firm_name
            FROM activity_logs a
            LEFT JOIN users u ON u.id = a.user_id
            LEFT JOIN myfirms mf ON mf.id = a.firm_id
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        $sql->bindValue(1, $limit, PDO::PARAM_INT);
        $sql->execute();

        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getRecentLogins(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $sql = $this->db->prepare("
            SELECT
                l.login_time,
                l.logout_time,
                l.ip_address,
                l.user_agent,
                u.full_name AS user_name,
                u.email,
                mf.firm_name
            FROM login_logs l
            INNER JOIN users u ON u.id = l.user_id
            LEFT JOIN myfirms mf ON mf.id = u.firm_id
            ORDER BY l.login_time DESC
            LIMIT ?
        ");
        $sql->bindValue(1, $limit, PDO::PARAM_INT);
        $sql->execute();

        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getRecentSecurityEvents(int $limit = 8): array
    {
        $limit = max(1, min($limit, 50));
        $exists = $this->db->query("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = 'security_events'
        ")->fetchColumn();

        if (!$exists) {
            return [];
        }

        $sql = $this->db->prepare("
            SELECT
                se.event_type,
                se.ip_address,
                se.description,
                se.created_at,
                u.full_name AS user_name
            FROM security_events se
            LEFT JOIN users u ON u.id = se.user_id
            ORDER BY se.created_at DESC
            LIMIT ?
        ");
        $sql->bindValue(1, $limit, PDO::PARAM_INT);
        $sql->execute();

        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}
