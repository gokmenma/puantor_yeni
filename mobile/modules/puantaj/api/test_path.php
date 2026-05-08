<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dir = __DIR__;
$d1 = dirname($dir);
$d2 = dirname($d1);
$d3 = dirname($d2);
$d4 = dirname($d3);

$root = $d4;

$results = [
    '__DIR__' => $dir,
    'd1' => $d1,
    'd2' => $d2,
    'd3' => $d3,
    'd4' => $d4,
    'root_calculated' => $root,
    'exists_db_require' => file_exists($root . "/Database/require.php"),
    'exists_db_db' => file_exists($root . "/Database/db.php")
];

echo json_encode($results, JSON_PRETTY_PRINT);
