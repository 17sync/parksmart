<?php
// views/payments/index.php
require_once __DIR__ . '/../../auth/auth.php';
requireLogin();
require_once __DIR__ . '/../../models/ParkingRecord.php';
require_once __DIR__ . '/../../includes/layout.php';

// Payment class is defined in ParkingRecord.php
$pay    = new Payment();
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;
$payments = $pay->getAll($status, $limit, $offset);
$byMethod = $pay->getByMethod();
$totalRev = $pay->getTotalRevenue();

renderHead('Payments');
renderSidebar('payments');
renderTopbar('Payments');
?>

<?php renderFlash(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Payments</h1>
    <p>View all parking payment transactions.</p>
  </div>
</div>

<!-- Summary Cards -->
<div class="stat-grid" style="margin-bottom:1.5rem">
  <div class="stat-card orange">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
    <div class="stat-label">Total Revenue</div>
    <div class="stat-value">₨<?= number_format($totalRev, 0) ?></div>
    <div class="stat-sub">All time, paid</div>
  </div>
  <?php foreach ($byMethod as $m): ?>
  <div class="stat-card">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
    <div class="stat-label" style="text-transform:capitalize"><?= esc($m['paymentMethod']) ?></div>
    <div class="stat-value">₨<?= number_format($m['revenue'], 0) ?></div>
    <div class="stat-sub"><?= $m['transactions'] ?> transactions</div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter Tabs -->
<div style="display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap">
  <?php foreach ([''=>'All','paid'=>'Paid','pending'=>'Pending','refunded'=>'Refunded'] as $val => $label): ?>
  <a href="?status=<?= $val ?>" class="btn <?= $status === $val ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
      Payment Transactions
    </div>
    <div class="search-bar" style="width:220px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="pay-search" placeholder="Search payments...">
    </div>
  </div>
  <div class="table-wrapper">
    <table id="pay-table">
      <thead>
        <tr>
          <th>#ID</th>
          <th>Record</th>
          <th>Plate</th>
          <th>Owner</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Payment Time</th>
          <th>Txn Ref</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($payments): ?>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td class="td-mono" style="color:var(--text-dim)">#<?= $p['paymentID'] ?></td>
          <td class="td-mono" style="color:var(--text-muted)">#<?= $p['recordID'] ?></td>
          <td class="td-mono" style="color:var(--accent);font-weight:700"><?= esc($p['licensePlate']) ?></td>
          <td><?= esc($p['ownerName']) ?></td>
          <td class="td-mono" style="color:var(--accent);font-weight:700">₨<?= number_format($p['amount'], 2) ?></td>
          <td style="text-transform:capitalize"><?= esc($p['paymentMethod']) ?></td>
          <td style="font-size:.78rem;color:var(--text-muted)"><?= fmtDT($p['paymentTime']) ?></td>
          <td class="td-mono" style="font-size:.75rem;color:var(--text-dim)"><?= esc($p['transactionRef'] ?? '—') ?></td>
          <td><span class="badge badge-<?= esc($p['status']) ?>"><span class="badge-dot"></span><?= esc($p['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="9">
          <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            <h3>No payments found</h3>
          </div>
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>initTableSearch('pay-search', 'pay-table');</script>
<?php renderFooter(); ?>
