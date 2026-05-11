<?php
$path = "c:/xampp/htdocs/puantoryeni/api/financial/transaction.php";
$content = file_get_contents($path);
echo "Original Length: " . strlen($content) . "\n";
$new_content = str_replace("\x00", "", $content);
echo "New Length: " . strlen($new_content) . "\n";
file_put_contents($path, $new_content);
echo "DONE!\n";
