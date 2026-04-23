<?php
// pages/plans.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$db   = getDB();
$uid  = (int)currentUser()['user_id'];
$user = $db->query("SELECT u.*, sp.plan_name AS current_plan FROM User u LEFT JOIN StoragePlan sp ON sp.plan_id=u.plan_id WHERE u.user_id=$uid")->fetch_assoc();
$plans = $db->query("SELECT * FROM StoragePlan ORDER BY price")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Storage Plans';
require_once '../includes/header.php';
?>

<div class="alert alert-info">
  Your current plan: <strong><?= htmlspecialchars($user['current_plan'] ?? 'Free') ?></strong>
  &nbsp;·&nbsp; To upgrade, go to <a href="payments.php" style="color:var(--accent);">Payments →</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;">
<?php foreach ($plans as $p): ?>
<?php $isCurrent = ($user['plan_id'] == $p['plan_id']); ?>
<div class="card" style="position:relative;<?= $isCurrent ? 'border-color:var(--accent);' : '' ?>">
  <?php if ($isCurrent): ?>
  <div style="position:absolute;top:12px;right:12px;">
    <span class="badge badge-success">Current</span>
  </div>
  <?php endif; ?>
  <div style="font-family:var(--font-mono);font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px;">
    <?= htmlspecialchars($p['plan_name']) ?>
  </div>
  <div style="font-family:var(--font-mono);font-size:36px;font-weight:700;color:var(--accent);margin-bottom:4px;">
    <?= $p['price'] == 0 ? 'Free' : '₹'.number_format($p['price'],0) ?>
  </div>
  <?php if ($p['price'] > 0): ?>
  <div style="font-size:11px;color:var(--text-muted);margin-bottom:16px;">per <?= $p['duration_days'] ?> days</div>
  <?php else: ?>
  <div style="font-size:11px;color:var(--text-muted);margin-bottom:16px;">forever</div>
  <?php endif; ?>
  <hr style="border:none;border-top:1px solid var(--border);margin-bottom:16px;">
  <div style="font-size:13px;margin-bottom:6px;">⬡ <strong><?= $p['storage_limit_gb'] ?> GB</strong> Storage</div>
  <div style="font-size:13px;margin-bottom:6px;">⬡ File Versioning</div>
  <div style="font-size:13px;margin-bottom:6px;">⬡ Shared Access</div>
  <div style="font-size:13px;margin-bottom:16px;">⬡ Activity Logs</div>
  <?php if (!$isCurrent): ?>
  <a href="payments.php" class="btn btn-primary" style="width:100%;justify-content:center;">
    Upgrade →
  </a>
  <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
