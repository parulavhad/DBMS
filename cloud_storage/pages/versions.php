<?php
// pages/versions.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$db    = getDB();
$uid   = (int)currentUser()['user_id'];
$fid   = (int)($_GET['file_id'] ?? 0);
$msg   = '';

if (!$fid) { header('Location: files.php'); exit; }

// Verify ownership
$file = $db->query("SELECT * FROM File WHERE file_id=$fid AND user_id=$uid LIMIT 1")->fetch_assoc();
if (!$file) { header('Location: files.php'); exit; }

// Add new version
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latestV = $db->query("SELECT MAX(version_no) v FROM FileVersion WHERE file_id=$fid")->fetch_assoc()['v'] ?? 0;
    $newV    = $latestV + 1;
    $sizeKB  = (float)($_POST['version_size_kb'] ?? 0);
    $db->prepare("INSERT INTO FileVersion (file_id, version_no, version_size_kb) VALUES (?,?,?)")
       ->bind_param('iid', $fid, $newV, $sizeKB) ;
    $stmt = $db->prepare("INSERT INTO FileVersion (file_id, version_no, version_size_kb) VALUES (?,?,?)");
    $stmt->bind_param('iid', $fid, $newV, $sizeKB);
    $stmt->execute();
    logActivity($uid, 'Upload', $fid);
    $msg = "Version $newV saved.";
}

// Delete version
if (isset($_GET['del_version']) && (int)$_GET['del_version']) {
    $vid = (int)$_GET['del_version'];
    $db->query("DELETE FROM FileVersion WHERE version_id=$vid");
    $msg = 'Version deleted.';
}

$versions = $db->query(
    "SELECT * FROM FileVersion WHERE file_id=$fid ORDER BY version_no DESC"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Versions: ' . $file['file_name'];
require_once '../includes/header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:300px 1fr;gap:20px;">

  <div>
    <div class="card">
      <div class="card-title">File Info</div>
      <table style="font-size:12px;width:100%">
        <tr><td style="color:var(--text-muted);padding:4px 0;">Name</td><td><?= htmlspecialchars($file['file_name']) ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:4px 0;">Type</td><td>.<?= htmlspecialchars($file['file_type'] ?? '?') ?></td></tr>
        <tr><td style="color:var(--text-muted);padding:4px 0;">Size</td><td><?= $file['file_size_mb'] ?> MB</td></tr>
        <tr><td style="color:var(--text-muted);padding:4px 0;">Versions</td><td><?= count($versions) ?></td></tr>
      </table>
    </div>

    <div class="card">
      <div class="card-title">Save New Version</div>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Version Size (KB)</label>
          <input type="number" name="version_size_kb" class="form-control" step="0.01" placeholder="e.g. 1024" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">⎇ Save Version</button>
      </form>
    </div>

    <a href="files.php" class="btn btn-ghost" style="width:100%;justify-content:center;">← Back to Files</a>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Version</th><th>Size</th><th>Saved At</th><th>Days Ago</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (empty($versions)): ?>
          <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);">No versions recorded.</td></tr>
        <?php else: ?>
          <?php foreach ($versions as $v): ?>
          <?php $daysAgo = (int)floor((time() - strtotime($v['saved_at'])) / 86400); ?>
          <tr>
            <td style="font-family:var(--font-mono);color:var(--text-muted);">#<?= $v['version_id'] ?></td>
            <td><span class="badge badge-purple">v<?= $v['version_no'] ?></span></td>
            <td style="font-family:var(--font-mono);font-size:12px;"><?= $v['version_size_kb'] ?> KB</td>
            <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= date('d M Y, H:i', strtotime($v['saved_at'])) ?></td>
            <td style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);"><?= $daysAgo ?> days</td>
            <td>
              <a href="versions.php?file_id=<?= $fid ?>&del_version=<?= $v['version_id'] ?>"
                 class="btn btn-danger btn-sm" data-confirm="Delete version <?= $v['version_no'] ?>?">✕</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once '../includes/footer.php'; ?>
