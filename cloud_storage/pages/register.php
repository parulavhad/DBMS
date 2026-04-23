<?php
// pages/register.php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db       = getDB();
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = trim($_POST['password']  ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone']     ?? '');

    // Check duplicate
    $chk = $db->prepare("SELECT user_id FROM User WHERE email=? OR username=? LIMIT 1");
    $chk->bind_param('ss', $email, $username);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $error = 'Email or username already exists.';
    } else {
        // Assign Free plan (plan_id = 1)
        $stmt = $db->prepare(
            "INSERT INTO User (username, email, password, full_name, phone, plan_id)
             VALUES (?, ?, MD5(?), ?, ?, 1)"
        );
        $stmt->bind_param('sssss', $username, $email, $password, $full_name, $phone);
        if ($stmt->execute()) {
            $newId = $db->insert_id;
            logActivity($newId, 'Login');
            $success = 'Account created! <a href="../index.php">Sign in now</a>';
        } else {
            $error = 'Registration failed. Try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CloudVault — Register</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="logo-icon">⬡</span>
      <span class="logo-text">CloudVault</span>
    </div>
    <div class="auth-title">Create your vault</div>

    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" placeholder="John Doe" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="johndoe" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" placeholder="+91 XXXXX XXXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
        Create Vault →
      </button>
    </form>
    <div class="auth-footer">Already have an account? <a href="../index.php">Sign in</a></div>
  </div>
</div>
</body>
</html>
