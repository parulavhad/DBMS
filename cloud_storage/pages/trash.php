<?php
// pages/trash.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$db  = getDB();
$uid = (int)currentUser()['user_id'];
$msg = '';

// Restore
if (isset($_GET['restore']) && (int)$_GET['restore']) {
    $rid = (int)$_GET['restore'];
    $db->query("UPDATE File f JOIN Trash t ON t.file_id=f.file_id SET f.is_deleted=0, t.file_id=NULL WHERE t.trash_id=$rid AND t.user_id=$uid");
    $db->query("DELETE FROM Trash WHERE trash_id=$rid AND user_id=$uid");
    // Get file id to restore
    $msg = 'File restored from trash.';
    logActivity($uid, 'Restore');
}

// Permanent delete
if (isset($_GET['perm_delete']) && (int)$_GET['perm_delete']) {
    $pid = (int)$_GET['perm_delete'];
    $row = $db->query("SELECT file_id FROM Trash WHERE trash_id=$pid AND user_id=$uid")->fetch_assoc();
    if ($row) {
        $db->query("DELETE FROM File WHERE file_id={$row['file_id']} AND user_id=$uid");
        $db->query("DELETE FROM Trash WHERE trash_id=$pid");
        logActivity($uid, 'Delete');
        $msg = 'File permanently deleted.';
    }
}

// Empty trash
if (isset($_GET['empty_trash'])) {
    $allTrash = $db->query("SELECT file_id FROM Trash WHERE user_id=$uid")->fetch_all(MYSQLI_ASSOC);
    foreach ($allTrash as $t) {
        $db->query("DELETE FROM File WHERE file_id={$t['file_id']} AND user_id=$uid");
    }
    $db->query("DELETE FROM Trash WHERE user_id=$uid");
    logActivity($uid, 'Delete');
    $msg = 'Trash emptied permanently.';
}

$trashItems = $db->query(
    "SELECT t.*, f.file_name, f.file_type, f.file_size_mb
     FROM Trash t
     JOIN File f ON f.file_id = t.file_id
     WHERE t.user_id=$uid
     ORDER BY t.deleted_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Trash';
require_once '../includes/header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="toolbar">
  <span style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);">
    <?= count($trashItems) ?> item(s) in trash · Files auto-delete after 30 days
  </span>
  <div class="toolbar-right">
    <?php if (!empty($trashItems)): ?>
    <a href="trash.php?empty_trash=1" class="btn btn-danger"
       data-confirm="Permanently delete ALL trash items? This cannot be undone.">
      ⊘ Empty Trash
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
<div class="table-wrap">
<table>
  <thead>
    <tr><th>File</th><th>Type</th><th>Size</th><th>Deleted At</th><th>Days Left</th><th>Actions</th></tr>
  </thead>
  <tbody>
  <?php if (empty($trashItems)): ?>
    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);font-family:var(--font-mono);">
      Trash is empty ✓
    </td></tr>
  <?php else: ?>
    <?php foreach ($trashItems as $t): ?>
    <?php
      $daysLeft = $t['permanent_delete_at']
                  ? max(0, (int)floor((strtotime($t['permanent_delete_at']) - time()) / 86400))
                  : 30;
      $badgeClass = $daysLeft <= 5 ? 'badge-danger' : ($daysLeft <= 10 ? 'badge-warn' : 'badge-success');
    ?>
    <tr>
      <td>
        <span class="file-icon" data-name="<?= htmlspecialchars($t['file_name']) ?>" style="margin-right:8px;">📁</span>
        <?= htmlspecialchars($t['file_name']) ?>
      </td>
      <td><span class="badge badge-info">.<?= htmlspecialchars($t['file_type'] ?? '?') ?></span></td>
      <td style="font-family:var(--font-mono);font-size:12px;"><?= $t['file_size_mb'] ?> MB</td>
      <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= date('d M Y, H:i', strtotime($t['deleted_at'])) ?></td>
      <td><span class="badge <?= $badgeClass ?>"><?= $daysLeft ?> days</span></td>
      <td>
        <a href="trash.php?restore=<?= $t['trash_id'] ?>" class="btn btn-ghost btn-sm">↩ Restore</a>
        <a href="trash.php?perm_delete=<?= $t['trash_id'] ?>" class="btn btn-danger btn-sm"
           data-confirm="Permanently delete this file?">✕ Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
