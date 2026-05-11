<?php
require_once 'Database/db.php';
$db = new Database\Db();
$stmt = $db->connect()->prepare('SELECT * FROM persons WHERE id = 271');
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
