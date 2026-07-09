<?php
session_start();
$_SESSION['user'] = (object)['id' => 242];
$_SESSION['firm_id'] = 186;
$_GET['action'] = 'list';
$_REQUEST['action'] = 'list';

ob_start();
require_once __DIR__ . '/api/izin/talep.php';
$output = ob_get_clean();
echo "API OUTPUT:\n" . $output . "\n";
unlink(__FILE__);
// The require_once above will execute the list action if it matches, but wait, $_REQUEST['action'] needs to be set.
