<?php
// pages/logout.php
require_once '../includes/auth.php';
if (isLoggedIn()) {
    require_once '../config/db.php';
    logActivity((int)currentUser()['user_id'], 'Logout');
    session_destroy();
}
header('Location: ../index.php');
exit;
