<?php 

require_once "BaseModel.php";

class Menus extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getMenus($userId = null)
    {
        if ($userId !== null) {
            $sql = $this->db->prepare("
                SELECT m.*, COALESCE(umo.index_no, m.index_no) as custom_index 
                FROM menu m 
                LEFT JOIN user_menu_order umo ON m.id = umo.menu_id AND umo.user_id = ? 
                WHERE m.parent_id = ? AND m.isActive = ? 
                ORDER BY custom_index ASC, m.index_no ASC, m.id ASC
            ");
            $sql->execute([$userId, 0, 1]);
        } else {
            $sql = $this->db->prepare("SELECT * FROM menu where parent_id = ? and isActive = ? ORDER BY index_no ASC");
            $sql->execute([0, 1]);
        }
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function saveUserMenuOrder($userId, $order)
    {
        try {
            $this->db->beginTransaction();

            $sqlDelete = $this->db->prepare("DELETE FROM user_menu_order WHERE user_id = ?");
            $sqlDelete->execute([$userId]);

            $sqlInsert = $this->db->prepare("INSERT INTO user_menu_order (user_id, menu_id, index_no) VALUES (?, ?, ?)");
            
            foreach ($order as $item) {
                $menuId = (int)$item['id'];
                $indexNo = (int)$item['index'];
                $sqlInsert->execute([$userId, $menuId, $indexNo]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getMenusByLink($page_link)
    {
        $sql = $this->db->prepare("SELECT id,page_name FROM menu where page_link = ?");
        $sql->execute([$page_link]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }   

    public function getSubMenusisMenu($menuId)
    {
        $sql = $this->db->prepare("SELECT * FROM menu WHERE parent_id = ? and isMenu = 1");
        $sql->execute(array($menuId));
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getSubMenus($menuId)
    {
        $sql = $this->db->prepare("SELECT * FROM menu WHERE parent_id = ? and isActive = ? ");
        $sql->execute(array($menuId,1));
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}