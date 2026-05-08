<?php
header('Content-Type: text/plain');
echo "Current directory: " . __DIR__ . "\n";
$dir = __DIR__ . "/pages";
echo "Checking directory: " . $dir . "\n";
if (is_dir($dir)) {
    echo "Directory exists.\n";
    $files = scandir($dir);
    echo "Files found:\n";
    print_r($files);
} else {
    echo "Directory NOT found.\n";
}

$test_file = $dir . "/home.php";
echo "Testing file_exists for: " . $test_file . "\n";
if (file_exists($test_file)) {
    echo "File exists!\n";
} else {
    echo "File DOES NOT exist.\n";
}
?>
