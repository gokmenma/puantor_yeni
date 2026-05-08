<?php 

require_once "BaseModel.php";

class Menus extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getMenus()
    {
        $sql = $this->db->prepare("SELECT * FROM menu where parent_id = ? and isActive = ? ORDER BY index_no ASC");
        $sql->execute([0, 1]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
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