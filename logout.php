<?php
/**
 * Logout Script
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();

// Start fresh session just to flash message
session_start();
set_flash_message('info', 'You have been securely logged out. Thank you for participating!');

header("Location: " . url('login.php'));
exit;
