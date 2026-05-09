<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing API proxy...\n";

$target = __DIR__ . "/../../../api/persons/person.php";
echo "Target: $target\n";

if (file_exists($target)) {
    echo "Target found. Requiring...\n";
    chdir(dirname($target));
    $_POST['action'] = 'test'; // To avoid doing anything real
    require_once basename($target);
    echo "\nTarget required successfully.";
} else {
    echo "Target NOT found.";
}
