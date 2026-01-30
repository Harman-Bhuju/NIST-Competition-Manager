<?php
// Persistent session configuration - keeps users logged in for 30 days
// This file MUST be included BEFORE session_start() on all protected pages

// Set cookie lifetime to 30 days
$lifetime = 60 * 60 * 24 * 30; // 30 days in seconds

// Configure session settings before starting
ini_set('session.cookie_lifetime', $lifetime);
ini_set('session.gc_maxlifetime', $lifetime);

// Set cookie parameters
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'secure' => false,    // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start the session
session_start();

// Regenerate session ID periodically to prevent session fixation
// But only if user is logged in
if (isset($_SESSION['admin_id']) && (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 3600)) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
