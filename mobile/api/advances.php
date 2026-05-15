<?php
// Puantor Mobile - Advance API Proxy
// This proxy file is used to avoid WAF/LiteSpeed security triggers that often occur with direct root-level API calls.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to the unified API controller
chdir('../../api/advances');
require_once 'advances.php';
