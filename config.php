<?php
// config.php
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('LISTING_DURATION', 4 * 7 * 24 * 60 * 60); // 4 weeks in seconds
define('USE_DATABASE', false);
define('UPLOAD_LIMIT', 5); // Maximum uploads per hour
define('UPLOAD_LIMIT_WINDOW', 3600); // 1 hour in seconds
define('ADMIN_PASSWORD', 'change_this_password'); // Change this to a secure password
define('LOG_FILE', __DIR__ . '/app.log');

// Enable necessary PHP extensions
if (!extension_loaded('fileinfo')) {
    if (!function_exists('dl') || !dl('fileinfo.so')) {
        die("The fileinfo extension is required but not available.");
    }
}