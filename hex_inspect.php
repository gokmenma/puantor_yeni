<?php
$f = file_get_contents("c:/xampp/htdocs/puantoryeni/api/financial/transaction.php");
$len = strlen($f);
$chunk = substr($f, $len - 300);
echo "LEN: $len\n";
echo "CHUNK DUMP:\n";
echo $chunk . "\n";
echo "HEX DUMP:\n";
echo bin2hex($chunk) . "\n";
