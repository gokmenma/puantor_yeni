<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
// Forward the request to the main advances admin API
// Forward to the unified API controller
chdir('../../api/advances');
require_once 'advances.php';
