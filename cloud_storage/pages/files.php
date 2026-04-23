<?php
// pages/files.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$db  = getDB();
$uid = (int)currentUser()['user_id'];
$msg = $err = '';

// ── Handle Actions ──────────────────────────────────────────
$action = $_GET['action'] ?? '';
$fid    = (int)($_GET['id'] ?? 0);

if ($action === 'trash' && $fid) {
    $db->query("UPDATE File SET is_deleted=1 WHERE file_id=$fid AND user_id=$uid");
    $pDel = date('Y-m-d H:i:s', strtotime('+30 days'));
    $db->query("INSERT INTO Trash (file_id, user_id, deleted_at, permanent_delete_at)
                VALUES ($fid, $uid, NOW(), '$pDel')");
    logActivity($uid, 'Delete', $fid);
    $msg = 'File moved to trash.';
}

if ($action === 'delete_version' && $fid) {
    $db->query("DELETE FROM FileVersion WHERE version_id=$fid");
    $msg = 'Version deleted.';
}

// ── Upload ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
    $file     = $_FILES['upload_file'];
    $fname    = basename($file['name']);
    $sizeMB   = round($file['size'] / 1048576, 4);
    $ftype    = pathinfo($fname, PATHINFO_EXTENSION);
    $folder   = (int)($_POST['folder_id'] ?? 0) ?: null;
    $tags     = trim($_POST['tags'] ?? '');

    // Storage check
    $stor = getUserStorage($uid);
    if (($stor['used_mb'] + ($sizeMB * 1)) > $stor['limit_mb']) {
        $err = 'Storage limit exceeded. Please upgrade your plan.';
    } elseif ($file['error'] === UPLOAD_ERR_OK) {
        $dest = '../uploads/' . time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $fname);
        move_uploaded_file($file['tmp_name'], $dest);

        $stmt = $db->prepare(
            "INSERT INTO File (file_name, file_type, file_size_mb, folder_id, user_id, tags)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssdiis', $fname, $ftype, $sizeMB, $folder, $uid, $tags);
        $stmt->execute();
        $newFid = $db->insert_id;

        // Create version 1
        $sizeKB = round($sizeMB * 1024, 2);
        $db->query("INSERT INTO FileVersion (file_id, version_no, version_size_kb) VALUES ($newFid, 1, $sizeKB)");
        logActivity($uid, 'Upload', $newFid);
        $msg = "File '$fname' uploaded successfully.";
    } else {
        $err = 'Upload failed.';
    }
}

// ── Fetch Folders for dropdown ──────────────────────────────
$folders = $db->query("SELECT folder_id, folder_name FROM Folder WHERE user_id=$uid ORDER BY folder_name")->fetch_all(MYSQLI_ASSOC);

// ── Filter & Search ─────────────────────────────────────────
$search   = $db->real_escape_string(trim($_GET['q'] ?? ''));
$ftFilter = $db->real_escape_string(trim($_GET['type'] ?? ''));
$where    = "f.user_id=$uid AND f.is_deleted=0";
if ($search)   $where .= " AND f.file_name LIKE '%$search%'";
if ($ftFilter) $where .= " AND f.file_type='$ftFilter'";

$files = $db->query(
    "SELECT f.*, fld.folder_name,
            (SELECT COUNT(*) FROM FileVersion WHERE file_id=f.file_id) AS version_count
     FROM File f
     LEFT JOIN Folder fld ON fld.folder_id = f.folder_id
     WHERE $where
     ORDER BY f.uploaded_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$fileTypes = $db->query("SELECT DISTINCT file_type FROM File WHERE user_id=$uid AND is_deleted=0 AND file_type IS NOT NULL")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Files';
require_once '../includes/header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Upload Card -->
<div class="card">
  <div class="card-title">Upload File</div>
  <form method="POST" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
      <div class="form-group" style="margin:0">
        <label class="form-label">File</label>
        <input type="file" name="upload_file" class="form-control" required>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Folder (optional)</label>
        <select name="folder_id" class="form-control">
          <option value="">— Root —</option>
          <?php foreach ($folders as $fld): ?>
          <option value="<?= $fld['folder_id'] ?>"><?= htmlspecialchars($fld['folder_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Tags</label>
        <input type="text" name="tags" class="form-control" placeholder="work, project, 2025">
      </div>
      <button type="submit" class="btn btn-primary">⬆ Upload</button>
    </div>
  </form>
</div>

<!-- Filter toolbar -->
<div class="toolbar">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
    <input type="text" name="q" class="form-control" placeholder="Search files…" style="width:200px" value="<?= htmlspecialchars($search) ?>">
    <select name="type" class="form-control" style="width:120px">
      <option value="">All types</option>
      <?php foreach ($fileTypes as $ft): ?>
      <option value="<?= $ft['file_type'] ?>" <?= $ftFilter===$ft['file_type']?'selected':'' ?>>
        .<?= htmlspecialchars($ft['file_type']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ghost">Filter</button>
    <a href="files.php" class="btn btn-ghost">Reset</a>
  </form>
  <div class="toolbar-right">
    <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= count($files) ?> files</span>
  </div>
</div>

<!-- Files Table -->
<div class="card" style="padding:0;overflow:hidden;">
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>Name</th><th>Type</th><th>Size</th><th>Folder</th><th>Tags</th><th>Versions</th><th>Uploaded</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($files)): ?>
    <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted);font-family:var(--font-mono);">No files found.</td></tr>
  <?php else: ?>
    <?php foreach ($files as $f): ?>
    <tr>
      <td>
        <span class="file-icon" data-name="<?= htmlspecialchars($f['file_name']) ?>" style="margin-right:8px;font-size:18px;">📁</span>
        <?= htmlspecialchars($f['file_name']) ?>
      </td>
      <td><span class="badge badge-info">.<?= htmlspecialchars($f['file_type'] ?? '?') ?></span></td>
      <td style="font-family:var(--font-mono);font-size:12px;"><?= $f['file_size_mb'] ?> MB</td>
      <td style="color:var(--text-muted);font-size:12px;"><?= htmlspecialchars($f['folder_name'] ?? '—') ?></td>
      <td style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($f['tags'] ?? '') ?></td>
      <td style="font-family:var(--font-mono);text-align:center;">
        <span class="badge badge-purple"><?= $f['version_count'] ?></span>
      </td>
      <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);">
        <?= date('d M Y', strtotime($f['uploaded_at'])) ?>
      </td>
      <td>
        <div style="display:flex;gap:6px;">
          <a href="versions.php?file_id=<?= $f['file_id'] ?>" class="btn btn-ghost btn-sm" title="Versions">⎇</a>
          <a href="shared.php?share_file=<?= $f['file_id'] ?>" class="btn btn-ghost btn-sm" title="Share">⊹</a>
          <a href="files.php?action=trash&id=<?= $f['file_id'] ?>"
             class="btn btn-danger btn-sm" data-confirm="Move '<?= htmlspecialchars($f['file_name']) ?>' to trash?">⊘</a>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
