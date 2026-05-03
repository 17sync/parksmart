<?php
// views/records/detail.php - AJAX partial
require_once __DIR__ . '/../../auth/auth.php';
requireLogin();
require_once __DIR__ . '/../../models/ParkingRecord.php';

$pr = new ParkingRecord();
$id = (int)($_GET['id'] ?? 0);
$r  = $pr->getByID($id);
if (!$r) { echo '<div class="empty-state"><p>Record not found.</p></div>'; exit; }

$mins = 0;
if ($r['exitTime']) {
    $mins = (int)ceil((strtotime($r['exitTime']) - strtotime($r['entryTime'])) / 60);
}
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
  <div><div class="stat-label">Record ID</div><div class="td-mono" style="color:var(--accent)">#<?= $r['recordID'] ?></div></div>
  <div><div class="stat-label">Status</div><span class="badge badge-<?= esc($r['status']) ?>"><?= esc($r['status']) ?></span></div>
  <div><div class="stat-label">License Plate</div><div class="td-mono" style="color:var(--accent);font-size:1.1rem"><?= esc($r['licensePlate']) ?></div></div>
  <div><div class="stat-label">Vehicle Type</div><div style="text-transform:capitalize"><?= esc($r['vehicleType']) ?></div></div>
  <div><div class="stat-label">Owner</div><div><?= esc($r['ownerName']) ?></div></div>
  <div><div class="stat-label">Phone</div><div><?= esc($r['ownerPhone'] ?? '—') ?></div></div>
  <div><div class="stat-label">Parking Slot</div><div class="td-mono"><?= esc($r['slotNumber']) ?></div></div>
  <div><div class="stat-label">Lot</div><div><?= esc($r['lotName']) ?></div></div>
  <div><div class="stat-label">Entry Time</div><div style="font-size:.85rem"><?= fmtDT($r['entryTime']) ?></div></div>
  <div><div class="stat-label">Exit Time</div><div style="font-size:.85rem"><?= fmtDT($r['exitTime']) ?></div></div>
  <?php if ($mins): ?>
  <div><div class="stat-label">Duration</div><div class="td-mono" style="color:var(--info)"><?= fmtDuration($mins) ?></div></div>
  <?php endif; ?>
  <?php if ($r['amount']): ?>
  <div>
    <div class="stat-label">Amount Paid</div>
    <div class="td-mono" style="color:var(--accent);font-size:1.2rem">₨<?= number_format($r['amount'], 2) ?></div>
  </div>
  <div><div class="stat-label">Payment Method</div><div style="text-transform:capitalize"><?= esc($r['paymentMethod'] ?? '—') ?></div></div>
  <div><div class="stat-label">Payment Status</div><span class="badge badge-<?= esc($r['payStatus'] ?? 'pending') ?>"><?= esc($r['payStatus'] ?? 'pending') ?></span></div>
  <?php if ($r['transactionRef']): ?>
  <div><div class="stat-label">Transaction Ref</div><div class="td-mono" style="font-size:.8rem"><?= esc($r['transactionRef']) ?></div></div>
  <?php endif; ?>
  <?php endif; ?>
</div>
