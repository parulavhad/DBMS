<?php
// pages/activity.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$db  = getDB();
$uid = (int)currentUser()['user_id'];

$filterAction = $db->real_escape_string($_GET['action_type'] ?? '');
$where = "al.user_id=$uid";
if ($filterAction) $where .= " AND al.action_type='$filterAction'";

$logs = $db->query(
    "SELECT al.*, f.file_name
     FROM ActivityLog al
     LEFT JOIN File f ON f.file_id = al.file_id
     WHERE $where
     ORDER BY al.action_date DESC
     LIMIT 200"
)->fetch_all(MYSQLI_ASSOC);

$actionTypes = ['Upload','Download','Delete','Restore','Share','Login','Logout','CreateFolder','RenameFile'];

$pageTitle = 'Activity Log';
require_once '../includes/header.php';
?>

<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px;">
    <select name="action_type" class="form-control" style="width:160px;">
      <option value="">All Actions</option>
      <?php foreach ($actionTypes as $at): ?>
      <option value="<?= $at ?>" <?= $filterAction===$at?'selected':'' ?>><?= $at ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ghost">Filter</button>
    <a href="activity.php" class="btn btn-ghost">Reset</a>
  </form>
  <div class="toolbar-right">
    <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= count($logs) ?> entries</span>
  </div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
<div class="table-wrap">
<table>
  <thead>
    <tr><th>#</th><th>Action</th><th>File</th><th>IP Address</th><th>Date & Time</th><th>Days Ago</th></tr>
  </thead>
  <tbody>
  <?php if (empty($logs)): ?>
    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">No activity recorded.</td></tr>
  <?php else: ?>
    <?php
    $badgeMap = [
        'Upload'       => 'badge-success',
        'Download'     => 'badge-info',
        'Delete'       => 'badge-danger',
        'Restore'      => 'badge-warn',
        'Share'        => 'badge-purple',
        'Login'        => 'badge-success',
        'Logout'       => 'badge-info',
        'CreateFolder' => 'badge-purple',
        'RenameFile'   => 'badge-warn',
    ];
    foreach ($logs as $log):
      $daysAgo = (int)floor((time() - strtotime($log['action_date'])) / 86400);
    ?>
    <tr>
      <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= $log['log_id'] ?></td>
      <td><span class="badge <?= $badgeMap[$log['action_type']] ?? 'badge-info' ?>"><?= $log['action_type'] ?></span></td>
      <td style="font-size:12px;"><?= htmlspecialchars($log['file_name'] ?? '—') ?></td>
      <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
      <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= date('d M Y, H:i:s', strtotime($log['action_date'])) ?></td>
      <td style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);"><?= $daysAgo === 0 ? 'Today' : "$daysAgo d ago" ?></td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
