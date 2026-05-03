<?php
// views/dashboard/admin.php
require_once __DIR__ . '/../../auth/auth.php';
requireAdmin();
require_once __DIR__ . '/../../models/ParkingRecord.php';
require_once __DIR__ . '/../../models/ParkingSlot.php';
require_once __DIR__ . '/../../models/Vehicle.php';
require_once __DIR__ . '/../../models/Payment.php';
require_once __DIR__ . '/../../includes/layout.php';

$pr      = new ParkingRecord();
$ps      = new ParkingSlot();
$stats   = $pr->getDashboardStats();
$active  = $pr->getActive();
$recent  = $pr->getRecentActivity(8);
$slotOv  = $ps->getSlotOverview();
$topSlots = $ps->getMostUsed(5);
$pay     = new Payment();
$byMethod = $pay->getByMethod();
$dailyRev = $pay->getRevenueSummary();

renderHead('Admin Dashboard');
renderSidebar('admin-dash');
renderTopbar('Admin Dashboard');
?>

<!-- Stats -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/></svg></div>
    <div class="stat-label">Total Slots</div>
    <div class="stat-value"><?= $stats['totalSlots'] ?></div>
    <div class="stat-sub">Across all lots</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 8 12 12 14 14"/></svg></div>
    <div class="stat-label">Available</div>
    <div class="stat-value"><?= $stats['available'] ?></div>
    <div class="stat-sub">Ready to park</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
    <div class="stat-label">Occupied</div>
    <div class="stat-value"><?= $stats['occupied'] ?></div>
    <div class="stat-sub">Active sessions</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
    <div class="stat-label">Vehicles</div>
    <div class="stat-value"><?= $stats['totalVehicles'] ?></div>
    <div class="stat-sub">Registered</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
    <div class="stat-label">Total Revenue</div>
    <div class="stat-value">₨<?= number_format($stats['totalRevenue'], 0) ?></div>
    <div class="stat-sub">All time</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
    <div class="stat-label">Today Revenue</div>
    <div class="stat-value">₨<?= number_format($stats['todayRevenue'], 0) ?></div>
    <div class="stat-sub"><?= $stats['todayParked'] ?> parked today</div>
  </div>
</div>

<!-- Grid: Active Sessions + Lot Overview -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">

  <!-- Active Sessions -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Live Sessions <span class="badge badge-active" style="margin-left:.5rem"><?= count($active) ?></span>
      </div>
      <a href="<?= APP_URL ?>/views/records/index.php?status=active" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <?php if ($active): ?>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Plate</th><th>Slot</th><th>Duration</th><th>Fee Est.</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($active, 0, 6) as $r): ?>
          <tr>
            <td class="td-mono"><?= esc($r['licensePlate']) ?></td>
            <td><span class="badge badge-occupied"><?= esc($r['slotNumber']) ?></span></td>
            <td data-entry-time="<?= esc($r['entryTime']) ?>"><?= fmtDuration((int)$r['minutesParked']) ?></td>
            <td class="td-mono" style="color:var(--accent)">₨<?= number_format((float)$r['estimatedFee'], 0) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:1.5rem">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <p>No active parking sessions</p>
    </div>
    <?php endif; ?>
  </div>

  <!-- Lot Overview -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        Lot Occupancy
      </div>
    </div>
    <?php foreach ($slotOv as $lot): ?>
    <div style="margin-bottom:1rem">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
        <div>
          <div style="font-size:.875rem;font-weight:500;color:var(--text)"><?= esc($lot['lotName']) ?></div>
          <div style="font-size:.75rem;color:var(--text-muted)"><?= $lot['availableSlots'] ?> available / <?= $lot['totalSlots'] ?> total</div>
        </div>
        <div style="font-family:var(--font-mono);font-size:.85rem;color:var(--accent)"><?= $lot['occupancyRate'] ?>%</div>
      </div>
      <div style="background:var(--bg-surface);border-radius:4px;height:8px;overflow:hidden">
        <div style="height:100%;width:<?= $lot['occupancyRate'] ?>%;background:<?= $lot['occupancyRate'] > 80 ? 'var(--danger)' : ($lot['occupancyRate'] > 50 ? 'var(--warning)' : 'var(--success)') ?>;border-radius:4px;transition:width .5s"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Revenue Chart + Top Slots + Payment Methods -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
        Daily Revenue (Last 7 days)
      </div>
    </div>
    <div id="rev-chart"></div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Payment Methods
      </div>
    </div>
    <?php foreach ($byMethod as $m): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid var(--border)">
      <div>
        <div style="font-size:.875rem;color:var(--text);text-transform:capitalize"><?= esc($m['paymentMethod']) ?></div>
        <div style="font-size:.75rem;color:var(--text-muted)"><?= $m['transactions'] ?> transactions</div>
      </div>
      <div style="font-family:var(--font-mono);font-size:.875rem;color:var(--accent)">₨<?= number_format($m['revenue'], 0) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Recent Activity + Top Slots -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.25rem">
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Recent Activity
      </div>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Plate</th><th>Slot</th><th>Entry</th><th>Status</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td class="td-mono"><?= esc($r['licensePlate']) ?></td>
            <td><?= esc($r['slotNumber']) ?></td>
            <td style="font-size:.8rem;color:var(--text-muted)"><?= fmtDT($r['entryTime']) ?></td>
            <td><span class="badge badge-<?= esc($r['status']) ?>"><?= esc($r['status']) ?></span></td>
            <td class="td-mono"><?= $r['amount'] ? '₨'.number_format($r['amount'],0) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
        Top Slots
      </div>
    </div>
    <?php foreach ($topSlots as $i => $slot): ?>
    <div style="display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid var(--border)">
      <div style="font-family:var(--font-mono);font-size:.75rem;color:var(--text-dim);width:1.2rem">#<?= $i+1 ?></div>
      <div style="flex:1">
        <div style="font-size:.875rem;font-weight:500;color:var(--text)"><?= esc($slot['slotNumber']) ?></div>
        <div style="font-size:.72rem;color:var(--text-muted)"><?= esc($slot['lotName']) ?></div>
      </div>
      <div style="font-family:var(--font-mono);font-size:.8rem;color:var(--accent)"><?= $slot['usageCount'] ?>x</div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php
// Build chart data
$chartLabels = [];
$chartVals   = [];
$revMap = [];
foreach ($dailyRev as $d) {
    $revMap[$d['paymentDate']] = (float)$d['totalRevenue'];
}
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D', strtotime($date));
    $chartVals[]   = $revMap[$date] ?? 0;
}
$labelsJson = json_encode($chartLabels);
$valsJson   = json_encode($chartVals);
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  renderBarChart('rev-chart', <?= $labelsJson ?>, <?= $valsJson ?>);
});
</script>

<?php renderFooter(); ?>
