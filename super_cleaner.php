<?php
$path = "c:/xampp/htdocs/puantoryeni/api/financial/transaction.php";
$content = file_get_contents($path);

// Retain ONLY standard ASCII characters 32-126 and essential formatting \r \n \t
$safe_content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);

// Specifically replace the corrupted tail string manually to ensure total safety
$corrupt_tail_pattern = "Geersiz i_ lem iste  i.";
// Wait, I should just rewrite the final fallback block explicitly to be 100% safe.
$find = "header('Content-Type: application/json');";
$pos = strrpos($safe_content, $find); // Find last occurrence

if ($pos !== false) {
    // The substring from this point includes the header call and the echo json_encode block
    $before = substr($safe_content, 0, $pos);
    $after = "header('Content-Type: application/json');\n";
    $after .= "echo json_encode(['status' => 'error', 'message' => 'Gecersiz islem istegi.']);\n";
    $after .= "exit;\n";
    
    $final_content = $before . $after;
    file_put_contents($path, $final_content);
    echo "Success: Tail rebuilt and control characters purged!\n";
} else {
    // Fallback: just rewrite it using basic replace
    $final_content = preg_replace('/G.*i_.*lem.*e\./', 'Gecersiz islem istegi.', $safe_content);
    file_put_contents($path, $final_content);
    echo "Fallback cleanup invoked.\n";
}
