<?php
// pages/payments.php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$db  = getDB();
$uid = (int)currentUser()['user_id'];
$msg = $err = '';

// New payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId  = (int)$_POST['plan_id'];
    $mode    = $db->real_escape_string($_POST['payment_mode'] ?? 'UPI');
    $plan    = $db->query("SELECT * FROM StoragePlan WHERE plan_id=$planId")->fetch_assoc();

    if ($plan) {
        $amount = $plan['price'];
        $today  = date('Y-m-d');
        $stmt   = $db->prepare(
            "INSERT INTO Payment (user_id, plan_id, amount, payment_date, payment_mode, status) VALUES (?,?,?,?,?,'Completed')"
        );
        $stmt->bind_param('iidss', $uid, $planId, $amount, $today, $mode);
        $stmt->execute();
        // Upgrade user plan
        $db->query("UPDATE User SET plan_id=$planId WHERE user_id=$uid");
        $msg = "Payment successful! Plan upgraded to '{$plan['plan_name']}'.";
    } else {
        $err = 'Invalid plan selected.';
    }
}

$plans    = $db->query("SELECT * FROM StoragePlan ORDER BY price")->fetch_all(MYSQLI_ASSOC);
$payments = $db->query(
    "SELECT p.*, sp.plan_name
     FROM Payment p JOIN StoragePlan sp ON sp.plan_id=p.plan_id
     WHERE p.user_id=$uid
     ORDER BY p.payment_date DESC"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Payments';
require_once '../includes/header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- New Payment -->
<div class="card">
  <div class="card-title">Make a Payment / Upgrade Plan</div>
  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;">
      <div class="form-group" style="margin:0">
        <label class="form-label">Storage Plan</label>
        <select name="plan_id" class="form-control" required>
          <option value="">— Select —</option>
          <?php foreach ($plans as $p): ?>
          <option value="<?= $p['plan_id'] ?>">
            <?= htmlspecialchars($p['plan_name']) ?> — <?= $p['storage_limit_gb'] ?> GB — ₹<?= $p['price'] ?>/<?= $p['duration_days'] ?>days
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Payment Mode</label>
        <select name="payment_mode" class="form-control">
          <option>UPI</option><option>Credit Card</option><option>Debit Card</option><option>NetBanking</option><option>Wallet</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">◈ Pay Now</button>
    </div>
  </form>
</div>

<!-- Payment History -->
<div class="card" style="padding:0;overflow:hidden;">
<div style="padding:16px 20px;border-bottom:1px solid var(--border);">
  <div class="card-title" style="margin:0;">Payment History</div>
</div>
<div class="table-wrap">
<table>
  <thead>
    <tr><th>ID</th><th>Plan</th><th>Amount</th><th>Mode</th><th>Status</th><th>Date</th></tr>
  </thead>
  <tbody>
  <?php if (empty($payments)): ?>
    <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);">No payments yet.</td></tr>
  <?php else: ?>
    <?php foreach ($payments as $p): ?>
    <tr>
      <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);">#<?= $p['payment_id'] ?></td>
      <td><?= htmlspecialchars($p['plan_name']) ?></td>
      <td style="font-family:var(--font-mono);font-weight:700;color:var(--accent);">₹<?= number_format($p['amount'],2) ?></td>
      <td style="font-size:12px;"><?= htmlspecialchars($p['payment_mode']) ?></td>
      <td>
        <?php
          $bc = ['Completed'=>'badge-success','Pending'=>'badge-warn','Failed'=>'badge-danger','Refunded'=>'badge-info'];
          echo '<span class="badge '.($bc[$p['status']]??'badge-info').'">'.$p['status'].'</span>';
        ?>
      </td>
      <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
