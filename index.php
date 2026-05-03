<?php
// index.php - Root entry point
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth.php';

if (isLoggedIn()) {
    redirect(APP_URL . (isAdmin() ? '/views/dashboard/admin.php' : '/views/dashboard/user.php'));
} else {
    redirect(APP_URL . '/views/auth/login.php');
}
