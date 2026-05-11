<?php
require_once 'c:/xampp/htdocs/puantoryeni/App/Helper/db.php';
$db = DB::getConnection();
$stmt = $db->query("SELECT * FROM sqlmaas_gelir_kesinti LIMIT 1");
$r = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Columns: " . implode(", ", array_keys($r)) . "\n";
