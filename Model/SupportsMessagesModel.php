<?php 
require_once "BaseModel.php";

class SupportsMessagesModel extends Model{
    protected $table = 'mbeyazil_panel.supports_message';
    public function __construct(){
        parent::__construct($this->table);
    }

    public function getMessagesByTicketId($support_id){
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE support_id = :support_id ORDER BY id DESC");
        $sql->execute([
            'support_id' => $support_id
        ]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    //Son mesajın author bilgisi boş ise bu mesajı kullanıcı göndermiştir ve destek ekibinin bu mesajı cevaplaması gerekmektedir.
    public function getLastMessageByTicketId($support_id){
        $sql = $this->db->prepare("SELECT author FROM $this->table WHERE support_id = :support_id ORDER BY id DESC LIMIT 1");
        $sql->execute([
            'support_id' => $support_id
        ]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }
}
