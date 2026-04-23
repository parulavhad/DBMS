<?php
// pages/shared.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$db  = getDB();
$uid = (int)currentUser()['user_id'];
$msg = $err = '';

// Share a file
$shareFileId = (int)($_GET['share_file'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_file_id'])) {
    $fid        = (int)$_POST['share_file_id'];
    $shareWith  = trim($_POST['share_with_email'] ?? '');
    $permission = $_POST['permission'] ?? 'View';

    // Get user_id of share_with
    $swUser = null;
    if ($shareWith) {
        $su = $db->prepare("SELECT user_id FROM User WHERE email=? LIMIT 1");
        $su->bind_param('s', $shareWith);
        $su->execute();
        $swUser = $su->get_result()->fetch_assoc()['user_id'] ?? null;
        if (!$swUser) { $err = "User with email '$shareWith' not found."; }
    }

    if (!$err) {
        $stmt = $db->prepare("INSERT INTO SharedAccess (file_id, shared_by, shared_with, permission) VALUES (?,?,?,?)");
        $stmt->bind_param('iiis', $fid, $uid, $swUser, $permission);
        $stmt->execute();
        logActivity($uid, 'Share', $fid);
        $msg = 'File shared successfully.';
        $shareFileId = 0;
    }
}

// Revoke
if (isset($_GET['revoke']) && (int)$_GET['revoke']) {
    $rid = (int)$_GET['revoke'];
    $db->query("DELETE FROM SharedAccess WHERE share_id=$rid AND shared_by=$uid");
    $msg = 'Access revoked.';
}

// Expire
if (isset($_GET['expire']) && (int)$_GET['expire']) {
    $eid = (int)$_GET['expire'];
    $db->query("UPDATE SharedAccess SET is_expired=1 WHERE share_id=$eid AND shared_by=$uid");
    $msg = 'Share marked as expired.';
}

// My files (for dropdown)
$myFiles = $db->query("SELECT file_id, file_name FROM File WHERE user_id=$uid AND is_deleted=0 ORDER BY file_name")->fetch_all(MYSQLI_ASSOC);

// Shared by me
$sharedByMe = $db->query(
    "SELECT sa.*, f.file_name, u.email AS shared_with_email
     FROM SharedAccess sa
     JOIN File f ON f.file_id = sa.file_id
     LEFT JOIN User u ON u.user_id = sa.shared_with
     WHERE sa.shared_by=$uid
     ORDER BY sa.shared_date DESC"
)->fetch_all(MYSQLI_ASSOC);

// Shared with me
$sharedWithMe = $db->query(
    "SELECT sa.*, f.file_name, u.email AS owner_email
     FROM SharedAccess sa
     JOIN File f ON f.file_id = sa.file_id
     JOIN User u ON u.user_id = sa.shared_by
     WHERE sa.shared_with=$uid AND sa.is_expired=0
     ORDER BY sa.shared_date DESC"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Shared Access';
require_once '../includes/header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Share Form -->
<div class="card">
  <div class="card-title">Share a File</div>
  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
      <div class="form-group" style="margin:0">
        <label class="form-label">File</label>
        <select name="share_file_id" class="form-control" required>
          <option value="">— Select file —</option>
          <?php foreach ($myFiles as $f): ?>
          <option value="<?= $f['file_id'] ?>" <?= $shareFileId==$f['file_id']?'selected':'' ?>>
            <?= htmlspecialchars($f['file_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Share With (email, blank = public)</label>
        <input type="email" name="share_with_email" class="form-control" placeholder="user@example.com">
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Permission</label>
        <select name="permission" class="form-control">
          <option>View</option><option>Download</option><option>Edit</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">⊹ Share</button>
    </div>
  </form>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

<!-- Shared by me -->
<div class="card" style="padding:0;overflow:hidden;">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
    <div class="card-title" style="margin:0;">Shared by Me</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File</th><th>With</th><th>Permission</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($sharedByMe)): ?>
        <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">Nothing shared yet.</td></tr>
      <?php else: ?>
        <?php foreach ($sharedByMe as $s): ?>
        <tr>
          <td style="font-size:12px;"><?= htmlspecialchars($s['file_name']) ?></td>
          <td style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($s['shared_with_email'] ?? 'Public') ?></td>
          <td><span class="badge badge-info"><?= $s['permission'] ?></span></td>
          <td>
            <?php if ($s['is_expired']): ?>
              <span class="badge badge-danger">Expired</span>
            <?php else: ?>
              <span class="badge badge-success">Active</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!$s['is_expired']): ?>
            <a href="shared.php?expire=<?= $s['share_id'] ?>" class="btn btn-ghost btn-sm">Expire</a>
            <?php endif; ?>
            <a href="shared.php?revoke=<?= $s['share_id'] ?>" class="btn btn-danger btn-sm"
               data-confirm="Revoke this share?">Revoke</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Shared with me -->
<div class="card" style="padding:0;overflow:hidden;">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
    <div class="card-title" style="margin:0;">Shared With Me</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File</th><th>From</th><th>Permission</th><th>Shared On</th></tr></thead>
      <tbody>
      <?php if (empty($sharedWithMe)): ?>
        <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">No shared files.</td></tr>
      <?php else: ?>
        <?php foreach ($sharedWithMe as $s): ?>
        <tr>
          <td style="font-size:12px;"><?= htmlspecialchars($s['file_name']) ?></td>
          <td style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($s['owner_email']) ?></td>
          <td><span class="badge badge-info"><?= $s['permission'] ?></span></td>
          <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= date('d M Y', strtotime($s['shared_date'])) ?></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<?php require_once '../includes/footer.php'; ?>
