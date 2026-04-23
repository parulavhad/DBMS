<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = currentUser();
$storage = getUserStorage((int)$user['user_id']);
$usedPct = $storage['limit_mb'] > 0
    ? min(100, round(($storage['used_mb'] / $storage['limit_mb']) * 100, 1))
    : 0;
$usedGB  = round($storage['used_mb']  / 1024, 2);
$limitGB = round($storage['limit_mb'] / 1024, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CloudVault — <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <span class="logo-icon">⬡</span>
        <span class="logo-text">CloudVault</span>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php"   class="nav-item <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
            <span class="nav-icon">⊞</span> Dashboard
        </a>
        <a href="files.php"       class="nav-item <?= basename($_SERVER['PHP_SELF'])=='files.php'?'active':'' ?>">
            <span class="nav-icon">▦</span> My Files
        </a>
        <a href="folders.php"     class="nav-item <?= basename($_SERVER['PHP_SELF'])=='folders.php'?'active':'' ?>">
            <span class="nav-icon">◫</span> Folders
        </a>
        <a href="shared.php"      class="nav-item <?= basename($_SERVER['PHP_SELF'])=='shared.php'?'active':'' ?>">
            <span class="nav-icon">⊹</span> Shared
        </a>
        <a href="trash.php"       class="nav-item <?= basename($_SERVER['PHP_SELF'])=='trash.php'?'active':'' ?>">
            <span class="nav-icon">⊘</span> Trash
        </a>
        <a href="activity.php"    class="nav-item <?= basename($_SERVER['PHP_SELF'])=='activity.php'?'active':'' ?>">
            <span class="nav-icon">≋</span> Activity
        </a>
        <a href="payments.php"    class="nav-item <?= basename($_SERVER['PHP_SELF'])=='payments.php'?'active':'' ?>">
            <span class="nav-icon">◈</span> Payments
        </a>
        <a href="plans.php"       class="nav-item <?= basename($_SERVER['PHP_SELF'])=='plans.php'?'active':'' ?>">
            <span class="nav-icon">◆</span> Plans
        </a>
    </nav>

    <div class="storage-meter">
        <div class="meter-label">
            <span>Storage</span>
            <span class="meter-plan"><?= htmlspecialchars($storage['plan_name']) ?></span>
        </div>
        <div class="meter-bar">
            <div class="meter-fill" style="width:<?= $usedPct ?>%;
                background: <?= $usedPct > 85 ? '#ff4545' : ($usedPct > 60 ? '#ffa500' : 'var(--accent)') ?>">
            </div>
        </div>
        <div class="meter-stats"><?= $usedGB ?> GB / <?= $limitGB ?> GB</div>
    </div>

    <div class="sidebar-footer">
        <div class="user-badge">
            <div class="user-avatar"><?= strtoupper(substr($user['username'],0,1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></div>
                <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">Sign out</a>
    </div>
</aside>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
    </div>
    <div class="content-area">
