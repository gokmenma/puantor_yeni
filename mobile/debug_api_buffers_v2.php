<?php
// Simulating wrapper execute via subshell to not terminate parent
$descriptor = [
   0 => ["pipe", "r"], // stdin
   1 => ["pipe", "w"], // stdout
   2 => ["pipe", "w"]  // stderr
];

// Mocking necessary context for test_sim.php but it must call target directly
$cmd = "php " . __DIR__ . "/api/financial/test_sim.php";
$process = proc_open($cmd, $descriptor, $pipes);

if (is_resource($process)) {
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    echo "RAW OUTPUT DUMP:\n";
    var_dump($stdout);
    echo "HEX DUMP: " . bin2hex(substr($stdout, 0, 32)) . "\n";
    
    $json = json_decode(trim($stdout));
    if ($json === null) {
        echo "DECODE FAILED: " . json_last_error_msg() . "\n";
    } else {
        echo "DECODE SUCCESS!\n";
    }
}
