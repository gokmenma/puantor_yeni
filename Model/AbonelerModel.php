<?php
require_once 'BaseModel.php';

class AbonelerModel extends Model
{
    protected $table = 'users';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Retrieve all main subscribers (parent_id = 0) with their latest subscription information.
     */
    public function getSubscribers()
    {
        $sql = $this->db->prepare("
            SELECT u.*, 
                   ka.baslangic_tarihi, 
                   ka.bitis_tarihi, 
                   ka.durum AS abonelik_durumu, 
                   ap.ad AS paket_adi
            FROM $this->table u
            LEFT JOIN (
                SELECT ka1.*
                FROM kullanici_abonelikleri ka1
                INNER JOIN (
                    SELECT kullanici_id, MAX(id) as max_id
                    FROM kullanici_abonelikleri
                    GROUP BY kullanici_id
                ) ka2 ON ka1.id = ka2.max_id
            ) ka ON ka.kullanici_id = u.id
            LEFT JOIN abonelik_paketleri ap ON ap.id = ka.paket_id
            WHERE u.parent_id = 0 OR u.parent_id = u.id
            ORDER BY u.created_at DESC
        ");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Clear specific modules of data for a subscriber.
     * All deletions are performed inside a PDO transaction.
     */
    public function clearSubscriberData($subscriberId, $modules)
    {
        $subscriberId = (int)$subscriberId;
        
        // 1. Get subscriber's firms
        $stmtFirms = $this->db->prepare("SELECT id FROM myfirms WHERE user_id = ?");
        $stmtFirms->execute([$subscriberId]);
        $firmIds = $stmtFirms->fetchAll(PDO::FETCH_COLUMN);
        
        // 2. Get subscriber's sub-users + subscriber themselves
        $stmtUsers = $this->db->prepare("SELECT id FROM users WHERE parent_id = ? OR id = ?");
        $stmtUsers->execute([$subscriberId, $subscriberId]);
        $userIds = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);
        
        $firmPlaceholders = !empty($firmIds) ? implode(',', array_fill(0, count($firmIds), '?')) : '';
        $userPlaceholders = !empty($userIds) ? implode(',', array_fill(0, count($userIds), '?')) : '';
        
        // 3. Find first registered firm and its default case, and main user's role to preserve
        $stmtMinFirm = $this->db->prepare("SELECT MIN(id) FROM myfirms WHERE user_id = ?");
        $stmtMinFirm->execute([$subscriberId]);
        $mainFirmId = (int)$stmtMinFirm->fetchColumn();
        
        $mainCaseId = 0;
        if ($mainFirmId > 0) {
            $stmtMinCase = $this->db->prepare("SELECT id FROM cases WHERE firm_id = ? AND isDefault = 1 LIMIT 1");
            $stmtMinCase->execute([$mainFirmId]);
            $mainCaseId = (int)$stmtMinCase->fetchColumn();
            if (!$mainCaseId) {
                $stmtMinCase = $this->db->prepare("SELECT MIN(id) FROM cases WHERE firm_id = ?");
                $stmtMinCase->execute([$mainFirmId]);
                $mainCaseId = (int)$stmtMinCase->fetchColumn();
            }
        }
        
        $stmtUserRole = $this->db->prepare("SELECT user_roles FROM users WHERE id = ?");
        $stmtUserRole->execute([$subscriberId]);
        $mainUserRoleId = (int)$stmtUserRole->fetchColumn();
        
        $this->db->beginTransaction();
        
        try {
            // Module 1: Puantaj
            if (in_array('puantaj', $modules)) {
                if (!empty($firmIds)) {
                    $stmt = $this->db->prepare("DELETE FROM puantaj WHERE person IN (SELECT id FROM persons WHERE firm_id IN ($firmPlaceholders))");
                    $stmt->execute($firmIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM puantaj WHERE project_id IN (SELECT id FROM projects WHERE firm_id IN ($firmPlaceholders))");
                    $stmt->execute($firmIds);
                }
            }
            
            // Module 2: Personnel Records
            if (in_array('personnel', $modules)) {
                if (!empty($firmIds)) {
                    $stmt = $this->db->prepare("DELETE FROM person_daily_wages WHERE person_id IN (SELECT id FROM persons WHERE firm_id IN ($firmPlaceholders))");
                    $stmt->execute($firmIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id IN (SELECT id FROM persons WHERE firm_id IN ($firmPlaceholders)) OR user_id IN ($firmPlaceholders)");
                    $stmt->execute(array_merge($firmIds, $firmIds));
                    
                    $stmt = $this->db->prepare("DELETE FROM personel_avans_talepleri WHERE firm_id IN ($firmPlaceholders)");
                    $stmt->execute($firmIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM project_person WHERE person_id IN (SELECT id FROM persons WHERE firm_id IN ($firmPlaceholders)) OR project_id IN (SELECT id FROM projects WHERE firm_id IN ($firmPlaceholders))");
                    $stmt->execute(array_merge($firmIds, $firmIds));
                    
                    $stmt = $this->db->prepare("DELETE FROM persons WHERE firm_id IN ($firmPlaceholders)");
                    $stmt->execute($firmIds);
                }
            }
            
            // Module 3: Finance / Case Transactions
            if (in_array('finance', $modules)) {
                if (!empty($firmIds)) {
                    $stmt = $this->db->prepare("DELETE FROM case_transactions WHERE case_id IN (SELECT id FROM cases WHERE firm_id IN ($firmPlaceholders))");
                    $stmt->execute($firmIds);
                }
            }
            
            // Module 4: Cari Firmalar (Companies)
            if (in_array('companies', $modules)) {
                if (!empty($userIds)) {
                    $stmt = $this->db->prepare("DELETE FROM report_ysc_content WHERE report_id IN (SELECT id FROM reports WHERE customer_id IN (SELECT id FROM companies WHERE user_id IN ($userPlaceholders)))");
                    $stmt->execute($userIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM reports WHERE customer_id IN (SELECT id FROM companies WHERE user_id IN ($userPlaceholders))");
                    $stmt->execute($userIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM offer_products WHERE oid IN (SELECT id FROM offers WHERE cid IN (SELECT id FROM companies WHERE user_id IN ($userPlaceholders)))");
                    $stmt->execute($userIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM offers WHERE cid IN (SELECT id FROM companies WHERE user_id IN ($userPlaceholders))");
                    $stmt->execute($userIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM companies WHERE user_id IN ($userPlaceholders)");
                    $stmt->execute($userIds);
                }
            }
            
            // Module 5: Projects & Tasks
            if (in_array('projects', $modules)) {
                if (!empty($firmIds)) {
                    $stmt = $this->db->prepare("DELETE FROM project_gelir_gider WHERE firm_id IN ($firmPlaceholders) OR project_id IN (SELECT id FROM projects WHERE firm_id IN ($firmPlaceholders))");
                    $stmt->execute(array_merge($firmIds, $firmIds));
                    
                    $stmt = $this->db->prepare("DELETE FROM project_tasks WHERE firm_id IN ($firmPlaceholders) OR project_id IN (SELECT id FROM projects WHERE firm_id IN ($firmPlaceholders))");
                    $stmt->execute(array_merge($firmIds, $firmIds));
                    
                    $stmt = $this->db->prepare("DELETE FROM projects WHERE firm_id IN ($firmPlaceholders)");
                    $stmt->execute($firmIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM gorevler WHERE firma_id IN ($firmPlaceholders)");
                    $stmt->execute($firmIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM gorev_listeleri WHERE firma_id IN ($firmPlaceholders)");
                    $stmt->execute($firmIds);
                }
            }
            
            // Module 6: Offers (if selected alone and companies were not cleared)
            if (in_array('offers', $modules) && !in_array('companies', $modules)) {
                if (!empty($userIds)) {
                    $stmt = $this->db->prepare("DELETE FROM offer_products WHERE oid IN (SELECT id FROM offers WHERE cid IN (SELECT id FROM companies WHERE user_id IN ($userPlaceholders)))");
                    $stmt->execute($userIds);
                    
                    $stmt = $this->db->prepare("DELETE FROM offers WHERE cid IN (SELECT id FROM companies WHERE user_id IN ($userPlaceholders))");
                    $stmt->execute($userIds);
                }
            }
            
            // Module 7: Roles (userroles)
            if (in_array('roles', $modules)) {
                if (!empty($firmIds)) {
                    // Delete user roles except the main user's role
                    $stmt = $this->db->prepare("DELETE FROM userroles WHERE firm_id IN ($firmPlaceholders) AND id != ?");
                    $stmt->execute(array_merge($firmIds, [$mainUserRoleId]));
                }
            }
            
            // Module 8: My Firms (myfirms)
            if (in_array('myfirms', $modules)) {
                if (!empty($firmIds)) {
                    // Delete cases except the main case
                    $stmt = $this->db->prepare("DELETE FROM cases WHERE firm_id IN ($firmPlaceholders) AND id != ?");
                    $stmt->execute(array_merge($firmIds, [$mainCaseId]));
                    
                    // Delete settings
                    $stmt = $this->db->prepare("DELETE FROM settings WHERE firm_id IN ($firmPlaceholders)");
                    $stmt->execute($firmIds);
                    
                    // Delete todos
                    $stmt = $this->db->prepare("DELETE FROM todos WHERE firm_id IN ($firmPlaceholders)");
                    $stmt->execute($firmIds);
                    
                    // Delete activity logs
                    $stmt = $this->db->prepare("DELETE FROM activity_logs WHERE firm_id IN ($firmPlaceholders)");
                    $stmt->execute($firmIds);
                    
                    // Delete sub-users
                    $stmt = $this->db->prepare("DELETE FROM users WHERE parent_id = ?");
                    $stmt->execute([$subscriberId]);
                    
                    // Delete myfirms except the main/first firm
                    $stmt = $this->db->prepare("DELETE FROM myfirms WHERE user_id = ? AND id != ?");
                    $stmt->execute([$subscriberId, $mainFirmId]);
                }
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

