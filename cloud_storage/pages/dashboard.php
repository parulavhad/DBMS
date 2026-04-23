<?php
// pages/dashboard.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$db   = getDB();
$uid  = (int)currentUser()['user_id'];

// Stats
$stats = [];

$r = $db->query("SELECT COUNT(*) c FROM File WHERE user_id=$uid AND is_deleted=0");
$stats['files'] = $r->fetch_assoc()['c'];

$r = $db->query("SELECT COUNT(*) c FROM Folder WHERE user_id=$uid");
$stats['folders'] = $r->fetch_assoc()['c'];

$r = $db->query("SELECT COUNT(*) c FROM SharedAccess WHERE shared_by=$uid");
$stats['shared'] = $r->fetch_assoc()['c'];

$r = $db->query("SELECT COUNT(*) c FROM Trash WHERE user_id=$uid");
$stats['trash'] = $r->fetch_assoc()['c'];

// Recent files
$recent = $db->query(
    "SELECT f.*, fld.folder_name
     FROM File f
     LEFT JOIN Folder fld ON fld.folder_id = f.folder_id
     WHERE f.user_id=$uid AND f.is_deleted=0
     ORDER BY f.uploaded_at DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

// Recent activity
$activity = $db->query(
    "SELECT al.*, fi.file_name
     FROM ActivityLog al
     LEFT JOIN File fi ON fi.file_id = al.file_id
     WHERE al.user_id=$uid
     ORDER BY al.action_date DESC LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Dashboard';
require_once '../includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Files</div>
    <div class="stat-value"><?= $stats['files'] ?></div>
    <div class="stat-sub">In your vault</div>
  </div>
  <div class="stat-card purple">
    <div class="stat-label">Folders</div>
    <div class="stat-value"><?= $stats['folders'] ?></div>
    <div class="stat-sub">Organized</div>
  </div>
  <div class="stat-card warn">
    <div class="stat-label">Shared</div>
    <div class="stat-value"><?= $stats['shared'] ?></div>
    <div class="stat-sub">Access grants</div>
  </div>
  <div class="stat-card danger">
    <div class="stat-label">Trash</div>
    <div class="stat-value"><?= $stats['trash'] ?></div>
    <div class="stat-sub">Pending delete</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;">

  <!-- Recent Files -->
  <div class="card">
    <div class="card-title">Recent Files</div>
    <?php if (empty($recent)): ?>
      <p style="color:var(--text-muted);font-family:var(--font-mono);font-size:12px;">No files yet. <a href="files.php" style="color:var(--accent);">Upload one →</a></p>
    <?php else: ?>
    <div class="file-grid">
      <?php foreach ($recent as $f): ?>
      <div class="file-card">
        <span class="file-icon" data-name="<?= htmlspecialchars($f['file_name']) ?>">📁</span>
        <div class="file-name"><?= htmlspecialchars($f['file_name']) ?></div>
        <div class="file-meta"><?= round($f['file_size_mb'],2) ?> MB</div>
        <div class="file-actions">
          <a href="files.php?action=download&id=<?= $f['file_id'] ?>" class="btn btn-ghost btn-sm">↓</a>
          <a href="files.php?action=trash&id=<?= $f['file_id'] ?>" class="btn btn-danger btn-sm"
             data-confirm="Move to trash?">⊘</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent Activity -->
  <div class="card">
    <div class="card-title">Recent Activity</div>
    <?php foreach ($activity as $a): ?>
    <div style="display:flex;gap:12px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--border);">
      <span style="font-size:18px;">
        <?php
          $icons = ['Upload'=>'⬆','Download'=>'⬇','Delete'=>'✕','Restore'=>'↩','Share'=>'⊹','Login'=>'→','Logout'=>'←','CreateFolder'=>'◫','RenameFile'=>'✎'];
          echo $icons[$a['action_type']] ?? '○';
        ?>
      </span>
      <div>
        <div style="font-size:12px;font-weight:600;"><?= $a['action_type'] ?></div>
        <div style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);">
          <?= $a['file_name'] ? htmlspecialchars($a['file_name']) : '—' ?>
        </div>
        <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
          <?= date('d M, H:i', strtotime($a['action_date'])) ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <a href="activity.php" style="display:block;text-align:center;padding:12px;font-family:var(--font-mono);font-size:11px;color:var(--accent);text-decoration:none;margin-top:8px;">
      View All →
    </a>
  </div>

</div>

<?php require_once '../includes/footer.php'; ?>
