<?php
// ============================================================
//  includes/auth.php  —  Session & Auth Helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'user_id'   => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'email'     => $_SESSION['email']     ?? '',
    ];
}

function logActivity(int $userId, string $action, ?int $fileId = null): void {
    require_once __DIR__ . '/../config/db.php';
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $db->prepare(
        "INSERT INTO ActivityLog (user_id, file_id, action_type, ip_address)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param('iiss', $userId, $fileId, $action, $ip);
    $stmt->execute();
}

function getUserStorage(int $userId): array {
    require_once __DIR__ . '/../config/db.php';
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT COALESCE(SUM(f.file_size_mb),0) AS used_mb,
                sp.storage_limit_gb * 1024        AS limit_mb,
                sp.plan_name
         FROM   User u
         LEFT JOIN File f ON f.user_id = u.user_id AND f.is_deleted = 0
         LEFT JOIN StoragePlan sp ON sp.plan_id = u.plan_id
         WHERE  u.user_id = ?
         GROUP  BY u.user_id"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: ['used_mb' => 0, 'limit_mb' => 5120, 'plan_name' => 'Free'];
}
