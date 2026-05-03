<?php
// views/reports/index.php
require_once __DIR__ . '/../../auth/auth.php';
requireAdmin();
require_once __DIR__ . '/../../models/ParkingRecord.php';
require_once __DIR__ . '/../../models/ParkingSlot.php';
require_once __DIR__ . '/../../includes/layout.php';

$pr      = new ParkingRecord();
$ps      = new ParkingSlot();
$pay     = new Payment();
$stats   = $pr->getDashboardStats();
$daily   = $pay->getRevenueSummary();
$monthly = $pay->getMonthlyRevenue();
$byMethod = $pay->getByMethod();
$topSlots = $ps->getMostUsed(10);
$slotOv   = $ps->getSlotOverview();

renderHead('Reports');
renderSidebar('reports');
renderTopbar('Reports & Analytics');
?>

<?php renderFlash(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Reports & Analytics</h1>
    <p>Comprehensive parking system data and revenue insights.</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-outline" onclick="window.print()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print Report
    </button>
  </div>
</div>

<!-- KPI Row -->
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem">
  <div class="stat-card orange">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
    <div class="stat-label">Total Revenue</div>
    <div class="stat-value">₨<?= number_format($stats['totalRevenue'], 0) ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
    <div class="stat-label">Total Vehicles</div>
    <div class="stat-value"><?= $stats['totalVehicles'] ?></div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
    <div class="stat-label">Today Parked</div>
    <div class="stat-value"><?= $stats['todayParked'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
    <div class="stat-label">Today Revenue</div>
    <div class="stat-value">₨<?= number_format($stats['todayRevenue'], 0) ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
  <!-- Monthly Revenue Chart -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
        Monthly Revenue
      </div>
    </div>
    <div id="monthly-chart"></div>
  </div>

  <!-- Payment method donut -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/></svg>
        By Method
      </div>
    </div>
    <div style="display:flex;justify-content:center;margin-bottom:1rem">
      <svg id="donut-chart" width="120" height="120"></svg>
    </div>
    <div style="display:flex;flex-direction:column;gap:.5rem">
      <?php
      $colors = ['cash'=>'#f5c842','card'=>'#4a9eff','online'=>'#3dd68c','wallet'=>'#f5a742'];
      foreach ($byMethod as $m):
        $color = $colors[$m['paymentMethod']] ?? '#7a8299';
      ?>
      <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem">
        <span style="width:10px;height:10px;border-radius:50%;background:<?= $color ?>;flex-shrink:0"></span>
        <span style="text-transform:capitalize;color:var(--text-muted);flex:1"><?= esc($m['paymentMethod']) ?></span>
        <span class="td-mono" style="color:var(--text)">₨<?= number_format($m['revenue'],0) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
  <!-- Daily revenue table -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Daily Revenue (Last 30 Days)
      </div>
    </div>
    <div class="table-wrapper" style="max-height:300px;overflow-y:auto">
      <table>
        <thead><tr><th>Date</th><th>Transactions</th><th>Revenue</th><th>Avg</th></tr></thead>
        <tbody>
        <?php foreach ($daily as $d): ?>
          <tr>
            <td class="td-mono" style="font-size:.8rem"><?= esc($d['paymentDate']) ?></td>
            <td class="td-mono"><?= $d['totalTransactions'] ?></td>
            <td class="td-mono" style="color:var(--accent)">₨<?= number_format($d['totalRevenue'],0) ?></td>
            <td class="td-mono" style="color:var(--text-muted)">₨<?= number_format($d['avgRevenue'],0) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top used slots -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        Most Used Slots
      </div>
    </div>
    <div id="slots-chart" style="margin-bottom:1rem"></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Rank</th><th>Slot</th><th>Lot</th><th>Uses</th></tr></thead>
        <tbody>
        <?php foreach ($topSlots as $i => $slot): ?>
          <tr>
            <td class="td-mono" style="color:var(--text-dim)">#<?= $i+1 ?></td>
            <td class="td-mono" style="color:var(--accent)"><?= esc($slot['slotNumber']) ?></td>
            <td style="font-size:.8rem"><?= esc($slot['lotName']) ?></td>
            <td class="td-mono" style="color:var(--text)"><?= $slot['usageCount'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Lot Occupancy Summary -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      Parking Lot Summary
    </div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Lot Name</th><th>Location</th><th>Capacity</th><th>Total Slots</th><th>Available</th><th>Occupied</th><th>Maintenance</th><th>Occupancy</th></tr></thead>
      <tbody>
      <?php foreach ($slotOv as $lot): ?>
        <tr>
          <td style="font-weight:500"><?= esc($lot['lotName']) ?></td>
          <td style="font-size:.8rem;color:var(--text-muted)"><?= esc($lot['lotLocation']) ?></td>
          <td class="td-mono"><?= $lot['capacity'] ?></td>
          <td class="td-mono"><?= $lot['totalSlots'] ?></td>
          <td class="td-mono" style="color:var(--success)"><?= $lot['availableSlots'] ?></td>
          <td class="td-mono" style="color:var(--danger)"><?= $lot['occupiedSlots'] ?></td>
          <td class="td-mono" style="color:var(--warning)"><?= $lot['maintenanceSlots'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.5rem">
              <div style="flex:1;background:var(--bg-surface);border-radius:4px;height:6px">
                <div style="height:100%;width:<?= $lot['occupancyRate'] ?>%;background:<?= $lot['occupancyRate']>80?'var(--danger)':($lot['occupancyRate']>50?'var(--warning)':'var(--success)') ?>;border-radius:4px"></div>
              </div>
              <span class="td-mono" style="font-size:.75rem;min-width:32px"><?= $lot['occupancyRate'] ?>%</span>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$mLabels = array_reverse(array_column(array_slice($monthly, 0, 6), 'month'));
$mVals   = array_reverse(array_column(array_slice($monthly, 0, 6), 'revenue'));
$sLabels = array_column($topSlots, 'slotNumber');
$sVals   = array_column($topSlots, 'usageCount');
$donutSegs = [];
$colors = ['cash'=>'#f5c842','card'=>'#4a9eff','online'=>'#3dd68c','wallet'=>'#f5a742'];
foreach ($byMethod as $m) {
    $donutSegs[] = ['value'=>(float)$m['revenue'], 'color'=>($colors[$m['paymentMethod']]??'#7a8299')];
}
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  renderBarChart('monthly-chart', <?= json_encode($mLabels) ?>, <?= json_encode(array_map('floatval', $mVals)) ?>);
  renderBarChart('slots-chart',   <?= json_encode($sLabels) ?>, <?= json_encode(array_map('intval', $sVals)) ?>, 'var(--info)');
  renderDonutChart('donut-chart', <?= json_encode($donutSegs) ?>);
});
</script>

<?php renderFooter(); ?>
