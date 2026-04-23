<?php
// pages/folders.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$db  = getDB();
$uid = (int)currentUser()['user_id'];
$msg = $err = '';

// Create Folder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['folder_name'] ?? '');
    $parent = (int)($_POST['parent_folder_id'] ?? 0) ?: null;
    if ($name) {
        $path = $parent ? '' : '/' . $name;
        $stmt = $db->prepare("INSERT INTO Folder (folder_name, folder_path, parent_folder_id, user_id) VALUES (?,?,?,?)");
        $stmt->bind_param('ssii', $name, $path, $parent, $uid);
        $stmt->execute();
        logActivity($uid, 'CreateFolder');
        $msg = "Folder '$name' created.";
    } else {
        $err = 'Folder name is required.';
    }
}

// Delete folder
if (isset($_GET['delete']) && (int)$_GET['delete']) {
    $did = (int)$_GET['delete'];
    $db->query("DELETE FROM Folder WHERE folder_id=$did AND user_id=$uid");
    $msg = 'Folder deleted.';
}

// Fetch all folders with file count
$folders = $db->query(
    "SELECT fld.*,
            pf.folder_name AS parent_name,
            (SELECT COUNT(*) FROM File WHERE folder_id=fld.folder_id AND is_deleted=0) AS file_count
     FROM Folder fld
     LEFT JOIN Folder pf ON pf.folder_id = fld.parent_folder_id
     WHERE fld.user_id=$uid
     ORDER BY fld.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Folders';
require_once '../includes/header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:320px 1fr;gap:20px;">

  <!-- Create Folder -->
  <div class="card">
    <div class="card-title">New Folder</div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Folder Name</label>
        <input type="text" name="folder_name" class="form-control" placeholder="e.g. Projects" required>
      </div>
      <div class="form-group">
        <label class="form-label">Parent Folder (optional)</label>
        <select name="parent_folder_id" class="form-control">
          <option value="">— Root —</option>
          <?php foreach ($folders as $f): ?>
          <option value="<?= $f['folder_id'] ?>"><?= htmlspecialchars($f['folder_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
        ◫ Create Folder
      </button>
    </form>
  </div>

  <!-- Folders List -->
  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Folder</th><th>Parent</th><th>Files</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if (empty($folders)): ?>
          <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted);font-family:var(--font-mono);">No folders yet.</td></tr>
        <?php else: ?>
          <?php foreach ($folders as $f): ?>
          <tr>
            <td>◫ <?= htmlspecialchars($f['folder_name']) ?></td>
            <td style="color:var(--text-muted);font-size:12px;"><?= htmlspecialchars($f['parent_name'] ?? '— Root —') ?></td>
            <td><span class="badge badge-info"><?= $f['file_count'] ?></span></td>
            <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= date('d M Y', strtotime($f['created_at'])) ?></td>
            <td>
              <a href="files.php?folder=<?= $f['folder_id'] ?>" class="btn btn-ghost btn-sm">Browse</a>
              <a href="folders.php?delete=<?= $f['folder_id'] ?>" class="btn btn-danger btn-sm"
                 data-confirm="Delete folder '<?= htmlspecialchars($f['folder_name']) ?>'?">Delete</a>
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
