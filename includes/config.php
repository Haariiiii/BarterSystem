<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'barter_system');
define('DB_USER', 'root');  // Change this to your database username
define('DB_PASS', '');      // Change this to your database password

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_URL', 'http://localhost/barter-system');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/barter-system/uploads/');
?> 