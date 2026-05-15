<?php
// Legacy Proxy for Advances Admin API
// Redirecting to the new unified API structure to avoid WAF/LiteSpeed security triggers.

if (!defined('ROOT')) {
    define("ROOT", dirname(__DIR__));
}

require_once ROOT . "/api/advances/advances.php";
