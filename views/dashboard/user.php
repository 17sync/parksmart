<?php
// views/dashboard/user.php
require_once __DIR__ . '/../../auth/auth.php';
requireLogin();
require_once __DIR__ . '/../../models/ParkingRecord.php';
require_once __DIR__ . '/../../models/ParkingSlot.php';
require_once __DIR__ . '/../../models/Vehicle.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../includes/layout.php';

$user     = currentUser();
$pr       = new ParkingRecord();
$ps       = new ParkingSlot();
$um       = new User();
$vm       = new Vehicle();

$stats    = $pr->getDashboardStats();
$myVehicles = $um->getVehiclesForUser($user['id']);
$recent   = $pr->getRecentActivity(5);
$slotOv   = $ps->getSlotOverview();
$available = $ps->getAvailable();

renderHead('Dashboard');
renderSidebar('dashboard');
renderTopbar('My Dashboard');
?>
<?php renderFlash(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Welcome back, <?= esc($user['name']) ?>! 👋</h1>
    <p>Here's what's happening at the parking system today.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/views/records/checkin.php" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Check-In
    </a>
  </div>
</div>

<!-- Stats -->
<div class="stat-grid">
  <div class="stat-card green">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 8 12 12 14 14"/></svg></div>
    <div class="stat-label">Available Slots</div>
    <div class="stat-value"><?= $stats['available'] ?></div>
    <div class="stat-sub">Of <?= $stats['totalSlots'] ?> total</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
    <div class="stat-label">Occupied</div>
    <div class="stat-value"><?= $stats['occupied'] ?></div>
    <div class="stat-sub">Active sessions</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
    <div class="stat-label">My Vehicles</div>
    <div class="stat-value"><?= count($myVehicles) ?></div>
    <div class="stat-sub">Registered</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
    <div class="stat-label">Active Sessions</div>
    <div class="stat-value"><?= $stats['activeSessions'] ?></div>
    <div class="stat-sub">Currently parked</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
  <!-- Lot availability -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        Parking Lots
      </div>
      <a href="<?= APP_URL ?>/views/slots/index.php" class="btn btn-ghost btn-sm">View Slots</a>
    </div>
    <?php foreach ($slotOv as $lot): ?>
    <div style="margin-bottom:.85rem">
      <div style="display:flex;justify-content:space-between;margin-bottom:.35rem">
        <div>
          <span style="font-size:.875rem;color:var(--text);font-weight:500"><?= esc($lot['lotName']) ?></span>
          <span style="font-size:.72rem;color:var(--text-muted);margin-left:.5rem"><?= esc($lot['lotLocation']) ?></span>
        </div>
        <span class="badge badge-<?= $lot['availableSlots'] > 0 ? 'available' : 'occupied' ?>">
          <?= $lot['availableSlots'] ?> free
        </span>
      </div>
      <div style="background:var(--bg-surface);border-radius:4px;height:6px">
        <div style="height:100%;width:<?= $lot['occupancyRate'] ?>%;background:<?= $lot['occupancyRate'] > 80 ? 'var(--danger)' : 'var(--success)' ?>;border-radius:4px"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- My vehicles -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        My Vehicles
      </div>
      <a href="<?= APP_URL ?>/views/vehicles/index.php" class="btn btn-ghost btn-sm">Manage</a>
    </div>
    <?php if ($myVehicles): ?>
    <?php foreach ($myVehicles as $v): ?>
    <div style="display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid var(--border)">
      <div style="font-family:var(--font-mono);font-size:.85rem;font-weight:700;color:var(--accent);min-width:80px"><?= esc($v['licensePlate']) ?></div>
      <div style="flex:1">
        <div style="font-size:.875rem;color:var(--text);text-transform:capitalize"><?= esc($v['vehicleType']) ?></div>
      </div>
      <a href="<?= APP_URL ?>/views/records/checkin.php?vehicle=<?= $v['vehicleID'] ?>" class="btn btn-outline btn-sm">Park</a>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="empty-state" style="padding:1rem">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      <p>No vehicles registered yet</p>
      <a href="<?= APP_URL ?>/views/vehicles/index.php" class="btn btn-primary btn-sm" style="margin-top:.75rem">Register Vehicle</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Activity -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Recent Parking Activity
    </div>
    <a href="<?= APP_URL ?>/views/records/index.php" class="btn btn-ghost btn-sm">View All</a>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Plate</th><th>Owner</th><th>Slot</th><th>Entry Time</th><th>Exit Time</th><th>Status</th><th>Amount</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td class="td-mono"><?= esc($r['licensePlate']) ?></td>
          <td><?= esc($r['ownerName']) ?></td>
          <td><?= esc($r['slotNumber']) ?> <small style="color:var(--text-muted)"><?= esc($r['lotName']) ?></small></td>
          <td style="font-size:.8rem;color:var(--text-muted)"><?= fmtDT($r['entryTime']) ?></td>
          <td style="font-size:.8rem;color:var(--text-muted)"><?= fmtDT($r['exitTime']) ?></td>
          <td><span class="badge badge-<?= esc($r['status']) ?>"><?= esc($r['status']) ?></span></td>
          <td class="td-mono"><?= $r['amount'] ? '₨'.number_format($r['amount'],0) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php renderFooter(); ?>
