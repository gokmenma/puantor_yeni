<?php
define('ROOT', __DIR__);
require_once "Database/db.php";
require_once "Model/BaseModel.php";

$db = (new Model('menu'))->getDb();

function getCols($db, $table) {
    echo "\n$table columns:\n";
    try {
        $stmt = $db->query("DESCRIBES $table"); // Oops, DESCRIBE
    } catch (Exception $e) {
        $stmt = $db->query("DESCRIBE $table");
    }
    $res = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($res);
}

getCols($db, 'projects');
getCols($db, 'login_logs');
getCols($db, 'case_transactions');
