<?php 
require_once 'BaseModel.php';

class SupportsModel extends Model
{
    protected $table = 'supports';

    public function __construct(){
        parent::__construct($this->table);
    }

    //Kullanıcının destek taleplerini getirir
    public function getSupportsByUser(){
        $user_id = $_SESSION['user']->id;
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = :user_id and program_name = :program_name ORDER BY id DESC");
        $sql->execute([
            'user_id' => $user_id,
            'program_name' => 'puantor'
        ]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    // Oturumdaki kullanıcının henüz görmediği destek mesajlarının bulunduğu talep sayısını getirir.
    public function getUnreadSupportsCount(){
        $user_id = $_SESSION['user']->id ?? 0;
        if (!$user_id) return 0;

        $is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
        if ($is_superadmin) {
            $sql = $this->db->prepare("
                SELECT COUNT(*)
                FROM $this->table s
                WHERE s.program_name = :program_name
                  AND EXISTS (
                      SELECT 1
                      FROM supports_message sm
                      WHERE sm.support_id = s.id
                        AND sm.id > COALESCE(s.admin_last_read_message_id, 0)
                        AND (sm.author IS NULL OR sm.author = '' OR sm.author = '0')
                  )
            ");
            $sql->execute([
                'program_name' => 'puantor'
            ]);
        } else {
            $sql = $this->db->prepare("
                SELECT COUNT(*)
                FROM $this->table s
                WHERE s.user_id = :user_id
                  AND s.program_name = :program_name
                  AND EXISTS (
                      SELECT 1
                      FROM supports_message sm
                      WHERE sm.support_id = s.id
                        AND sm.id > COALESCE(s.user_last_read_message_id, 0)
                        AND sm.author IS NOT NULL
                        AND sm.author <> ''
                        AND sm.author <> '0'
                  )
            ");
            $sql->execute([
                'user_id' => $user_id,
                'program_name' => 'puantor'
            ]);
        }
        return (int)$sql->fetchColumn();
    }

    // Geriye dönük çağrılar için korunur.
    public function getOpenSupportsCount(){
        return $this->getUnreadSupportsCount();
    }

    public function markAsRead($support_id){
        $user_id = $_SESSION['user']->id ?? 0;
        if (!$user_id || !$support_id) {
            return false;
        }

        $is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
        $column = $is_superadmin ? 'admin_last_read_message_id' : 'user_last_read_message_id';
        $ownershipSql = $is_superadmin ? '' : ' AND user_id = :user_id';

        $lastMessageSql = $this->db->prepare("
            SELECT COALESCE(MAX(id), 0)
            FROM supports_message
            WHERE support_id = :support_id
        ");
        $lastMessageSql->execute(['support_id' => $support_id]);
        $lastMessageId = (int)$lastMessageSql->fetchColumn();

        $sql = $this->db->prepare("
            UPDATE $this->table
            SET $column = :last_message_id
            WHERE id = :support_id
              AND program_name = :program_name
              $ownershipSql
        ");

        $params = [
            'last_message_id' => $lastMessageId,
            'support_id' => $support_id,
            'program_name' => 'puantor'
        ];
        if (!$is_superadmin) {
            $params['user_id'] = $user_id;
        }
        $sql->execute($params);

        return $sql->rowCount() > 0;
    }

    // Süperadmin için tüm destek taleplerini getirir (kullanıcı bilgileriyle)
    public function getAllSupportsForAdmin(){
        $sql = $this->db->prepare("
            SELECT s.*, 
                   u.full_name as user_name, 
                   u.email as user_email, 
                   u.phone as user_phone,
                   u.avatar as user_avatar,
                   (SELECT COUNT(*) FROM supports_message sm WHERE sm.support_id = s.id) as message_count,
                   (SELECT sm2.created_at FROM supports_message sm2 WHERE sm2.support_id = s.id ORDER BY sm2.id DESC LIMIT 1) as last_reply_at,
                   (SELECT sm3.author FROM supports_message sm3 WHERE sm3.support_id = s.id ORDER BY sm3.id DESC LIMIT 1) as last_author,
                   (CASE WHEN EXISTS (
                       SELECT 1 FROM supports_message sm4 
                       WHERE sm4.support_id = s.id 
                         AND sm4.id > COALESCE(s.admin_last_read_message_id, 0)
                         AND (sm4.author IS NULL OR sm4.author = '' OR sm4.author = '0')
                   ) THEN 1 ELSE 0 END) as has_unread
            FROM $this->table s 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE s.program_name = :program_name 
            ORDER BY s.status ASC, s.id DESC
        ");
        $sql->execute([
            'program_name' => 'puantor'
        ]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function toggleTicketStatus($support_id, $status = null){
        if ($status === null) {
            $ticket = $this->find($support_id);
            if (!$ticket) return false;
            $newStatus = ($ticket->status == 0) ? 1 : 0;
        } else {
            $newStatus = (int)$status;
        }
        $sql = $this->db->prepare("UPDATE $this->table SET status = :status WHERE id = :support_id");
        return $sql->execute([
            'status' => $newStatus,
            'support_id' => $support_id
        ]);
    }

    public function deleteTicketWithMessages($support_id){
        $this->db->beginTransaction();
        try {
            $msgSql = $this->db->prepare("DELETE FROM supports_message WHERE support_id = :support_id");
            $msgSql->execute(['support_id' => $support_id]);

            $ticketSql = $this->db->prepare("DELETE FROM $this->table WHERE id = :support_id");
            $ticketSql->execute(['support_id' => $support_id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("deleteTicketWithMessages Error: " . $e->getMessage());
            return false;
        }
    }

    public function bulkCloseTickets(array $ids){
        if (empty($ids)) return 0;
        $cleanIds = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = $this->db->prepare("UPDATE $this->table SET status = 1 WHERE id IN ($placeholders)");
        $sql->execute($cleanIds);
        return $sql->rowCount();
    }

    public function bulkDeleteTickets(array $ids){
        if (empty($ids)) return 0;
        $cleanIds = array_map('intval', $ids);
        $this->db->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
            $msgSql = $this->db->prepare("DELETE FROM supports_message WHERE support_id IN ($placeholders)");
            $msgSql->execute($cleanIds);

            $ticketSql = $this->db->prepare("DELETE FROM $this->table WHERE id IN ($placeholders)");
            $ticketSql->execute($cleanIds);

            $count = $ticketSql->rowCount();
            $this->db->commit();
            return $count;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("bulkDeleteTickets Error: " . $e->getMessage());
            return 0;
        }
    }

    public function markAllAsReadForAdmin(){
        $sql = $this->db->prepare("
            UPDATE $this->table s
            SET admin_last_read_message_id = COALESCE((
                SELECT MAX(sm.id) FROM supports_message sm WHERE sm.support_id = s.id
            ), 0)
            WHERE s.program_name = :program_name
        ");
        return $sql->execute(['program_name' => 'puantor']);
    }
}
