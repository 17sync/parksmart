<?php
// views/records/checkin.php
require_once __DIR__ . '/../../auth/auth.php';
requireLogin();
require_once __DIR__ . '/../../models/Vehicle.php';
require_once __DIR__ . '/../../models/ParkingSlot.php';
require_once __DIR__ . '/../../includes/layout.php';

$vm       = new Vehicle();
$ps       = new ParkingSlot();
$lots     = $ps->getLots();
$available = $ps->getAvailable();
$preVehicle = (int)($_GET['vehicle'] ?? 0);
$preVehicleData = $preVehicle ? $vm->getByID($preVehicle) : null;

renderHead('Check In');
renderSidebar('records');
renderTopbar('Vehicle Check-In');
?>

<?php renderFlash(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Vehicle Check-In</h1>
    <p>Register a vehicle entry and assign a parking slot.</p>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">

  <!-- Check-in Form -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Check-In Details
      </div>
    </div>

    <!-- Step 1: Find Vehicle -->
    <div class="form-group">
      <label class="form-label">Step 1 — Find Vehicle by License Plate</label>
      <div style="display:flex;gap:.5rem">
        <input type="text" id="search-plate" class="form-control" placeholder="Enter license plate e.g. KHI-001"
               style="text-transform:uppercase" value="<?= $preVehicleData ? esc($preVehicleData['licensePlate']) : '' ?>">
        <button class="btn btn-outline" onclick="lookupVehicle()" style="white-space:nowrap">Lookup</button>
      </div>
    </div>

    <!-- Vehicle Info Card -->
    <div id="vehicle-info" style="<?= $preVehicleData ? '' : 'display:none' ?>;background:var(--bg-surface);border-radius:var(--radius);padding:1rem;margin-bottom:1rem;border:1px solid var(--border-lt)">
      <div style="font-size:.72rem;font-family:var(--font-mono);color:var(--accent);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.06em">Vehicle Found</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
        <div><div class="form-label" style="margin-bottom:.1rem">Plate</div><div class="td-mono" id="vinfo-plate" style="color:var(--accent);font-size:1rem"><?= $preVehicleData ? esc($preVehicleData['licensePlate']) : '' ?></div></div>
        <div><div class="form-label" style="margin-bottom:.1rem">Type</div><div id="vinfo-type"><?= $preVehicleData ? esc($preVehicleData['vehicleType']) : '' ?></div></div>
        <div><div class="form-label" style="margin-bottom:.1rem">Owner</div><div id="vinfo-owner"><?= $preVehicleData ? esc($preVehicleData['ownerName']) : '' ?></div></div>
        <div><div class="form-label" style="margin-bottom:.1rem">Phone</div><div id="vinfo-phone"><?= $preVehicleData ? esc($preVehicleData['ownerPhone'] ?? '—') : '' ?></div></div>
      </div>
      <input type="hidden" id="selected-vehicle-id" value="<?= $preVehicleData ? $preVehicleData['vehicleID'] : '' ?>">
    </div>

    <!-- Register new vehicle quick-link -->
    <div id="no-vehicle" style="display:none;margin-bottom:1rem">
      <div class="alert alert-warning">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
        <span class="alert-msg">Vehicle not found. <a href="<?= APP_URL ?>/views/vehicles/index.php">Register it first</a>.</span>
      </div>
    </div>

    <hr class="divider">

    <!-- Step 2: Select Slot -->
    <div class="form-group">
      <label class="form-label">Step 2 — Select Parking Slot</label>
      <div style="display:flex;gap:.5rem;margin-bottom:.75rem">
        <select id="filter-lot" class="form-control" onchange="filterSlots()">
          <option value="">All Lots</option>
          <?php foreach ($lots as $l): ?>
          <option value="<?= $l['lotID'] ?>"><?= esc($l['lotName']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="slot-picker" class="slot-grid" style="max-height:260px;overflow-y:auto">
        <?php foreach ($available as $slot): ?>
        <div class="slot-tile available" data-lot="<?= $slot['lotID'] ?>" data-id="<?= $slot['slotID'] ?>"
             onclick="selectSlot(this, <?= $slot['slotID'] ?>, '<?= esc($slot['slotNumber']) ?>')">
          <div class="slot-number"><?= esc($slot['slotNumber']) ?></div>
          <div class="slot-type"><?= esc($slot['slotType']) ?></div>
          <div class="slot-status-dot"></div>
        </div>
        <?php endforeach; ?>
        <?php if (!$available): ?>
        <div class="empty-state" style="grid-column:1/-1;padding:1rem">
          <p>No available slots at this time.</p>
        </div>
        <?php endif; ?>
      </div>
      <input type="hidden" id="selected-slot-id">
      <div id="slot-selected-info" style="display:none;margin-top:.75rem;background:var(--bg-surface);padding:.75rem;border-radius:var(--radius);border:1px solid var(--success)">
        <span style="font-size:.78rem;color:var(--success);font-family:var(--font-mono)">✓ SLOT SELECTED: </span>
        <span id="selected-slot-label" style="font-family:var(--font-mono);color:var(--text);font-weight:700"></span>
      </div>
    </div>

    <hr class="divider">

    <!-- Step 3: Payment Method -->
    <div class="form-group">
      <label class="form-label">Step 3 — Payment Method</label>
      <select id="payment-method" class="form-control">
        <option value="cash">Cash</option>
        <option value="card">Card</option>
        <option value="online">Online</option>
        <option value="wallet">Digital Wallet</option>
      </select>
      <div class="form-hint">Rate: ₨<?= RATE_PER_HOUR ?>/hour · Minimum charge: ₨<?= MIN_CHARGE ?></div>
    </div>

    <button class="btn btn-primary btn-full btn-lg" onclick="doCheckIn()" id="checkin-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
      Complete Check-In
    </button>
  </div>

  <!-- Right panel: active sessions + rate info -->
  <div>
    <div class="card" style="margin-bottom:1.25rem">
      <div class="card-header">
        <div class="card-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Parking Rate
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div style="background:var(--bg-surface);padding:1rem;border-radius:var(--radius);text-align:center">
          <div class="stat-label">Rate</div>
          <div style="font-family:var(--font-mono);font-size:1.4rem;color:var(--accent)">₨<?= RATE_PER_HOUR ?></div>
          <div style="font-size:.75rem;color:var(--text-muted)">per hour</div>
        </div>
        <div style="background:var(--bg-surface);padding:1rem;border-radius:var(--radius);text-align:center">
          <div class="stat-label">Minimum</div>
          <div style="font-family:var(--font-mono);font-size:1.4rem;color:var(--accent)">₨<?= MIN_CHARGE ?></div>
          <div style="font-size:.75rem;color:var(--text-muted)">minimum charge</div>
        </div>
      </div>
      <div style="margin-top:1rem;font-size:.8rem;color:var(--text-muted);line-height:1.6">
        <div>• Charged per hour, minimum <?= MIN_CHARGE ?> PKR</div>
        <div>• Payment collected on exit</div>
        <div>• Active session tracked in real-time</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Currently Parked
        </div>
      </div>
      <div id="active-sessions-list">
        <div class="empty-state"><span class="spinner"></span></div>
      </div>
    </div>
  </div>

</div>

<!-- Check-in Success Modal -->
<div class="modal-overlay" id="modal-checkin-success">
  <div class="modal">
    <div class="modal-header" style="background:rgba(61,214,140,.08);border-color:rgba(61,214,140,.2)">
      <div class="modal-title" style="color:var(--success)">✓ Check-In Successful</div>
    </div>
    <div class="modal-body" id="checkin-success-body"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="Modal.close('modal-checkin-success');location.reload()">New Check-In</button>
      <a href="<?= APP_URL ?>/views/records/index.php" class="btn btn-primary">View Records</a>
    </div>
  </div>
</div>

<script>
let selectedSlotID = null;

async function lookupVehicle() {
  const plate = document.getElementById('search-plate').value.trim().toUpperCase();
  if (!plate) { Toast.warning('Enter a license plate.'); return; }
  const res = await apiGet('vehicle_search', { q: plate });
  if (res.success) {
    const v = res.vehicle;
    document.getElementById('vinfo-plate').textContent  = v.licensePlate;
    document.getElementById('vinfo-type').textContent   = v.vehicleType;
    document.getElementById('vinfo-owner').textContent  = v.ownerName;
    document.getElementById('vinfo-phone').textContent  = v.ownerPhone || '—';
    document.getElementById('selected-vehicle-id').value = v.vehicleID;
    document.getElementById('vehicle-info').style.display = '';
    document.getElementById('no-vehicle').style.display   = 'none';
    Toast.success('Vehicle found: ' + v.ownerName);
  } else {
    document.getElementById('vehicle-info').style.display = 'none';
    document.getElementById('no-vehicle').style.display   = '';
    document.getElementById('selected-vehicle-id').value  = '';
  }
}

function filterSlots() {
  const lotID = document.getElementById('filter-lot').value;
  document.querySelectorAll('#slot-picker .slot-tile').forEach(tile => {
    tile.style.display = (!lotID || tile.dataset.lot === lotID) ? '' : 'none';
  });
}

function selectSlot(el, id, label) {
  document.querySelectorAll('#slot-picker .slot-tile').forEach(t => t.style.outline = '');
  el.style.outline = '2px solid var(--accent)';
  selectedSlotID = id;
  document.getElementById('selected-slot-id').value = id;
  document.getElementById('selected-slot-label').textContent = label;
  document.getElementById('slot-selected-info').style.display = '';
}

async function doCheckIn() {
  const vehicleID = document.getElementById('selected-vehicle-id').value;
  const slotID    = selectedSlotID;
  const method    = document.getElementById('payment-method').value;

  if (!vehicleID) { Toast.error('Look up a vehicle first.'); return; }
  if (!slotID)    { Toast.error('Select an available slot.'); return; }

  const btn = document.getElementById('checkin-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Processing...';

  const res = await api('checkin', { vehicleID, slotID, paymentMethod: method });

  if (res.success) {
    const plate  = document.getElementById('vinfo-plate').textContent;
    const slot   = document.getElementById('selected-slot-label').textContent;
    const now    = new Date().toLocaleString('en-PK');
    document.getElementById('checkin-success-body').innerHTML = `
      <div style="text-align:center;padding:.5rem 0">
        <div style="font-size:3rem;margin-bottom:.75rem">🚗</div>
        <div style="font-family:var(--font-mono);font-size:1.2rem;color:var(--accent);margin-bottom:.5rem">${plate}</div>
        <div style="color:var(--text-muted);margin-bottom:1rem">Successfully checked into slot <strong style="color:var(--text)">${slot}</strong></div>
        <div style="background:var(--bg-surface);padding:.75rem;border-radius:var(--radius);font-size:.8rem;color:var(--text-muted)">
          Record ID: <strong style="color:var(--text);font-family:var(--font-mono)">#${res.recordID}</strong><br>
          Entry Time: ${now}
        </div>
      </div>`;
    Modal.open('modal-checkin-success');
  } else {
    Toast.error(res.message);
  }

  btn.disabled = false;
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> Complete Check-In';
}

// Load active sessions
async function loadActive() {
  const res = await apiGet('get_active');
  const box = document.getElementById('active-sessions-list');
  if (!res.success || !res.data.length) {
    box.innerHTML = '<div class="empty-state" style="padding:1rem"><p>No active sessions</p></div>';
    return;
  }
  let html = '<div class="table-wrapper"><table><thead><tr><th>Plate</th><th>Slot</th><th>Time</th></tr></thead><tbody>';
  res.data.forEach(r => {
    html += `<tr><td class="td-mono" style="color:var(--accent)">${r.licensePlate}</td><td>${r.slotNumber}</td><td data-entry-time="${r.entryTime}">${r.minutesParked}m</td></tr>`;
  });
  html += '</tbody></table></div>';
  box.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', loadActive);
document.getElementById('search-plate').addEventListener('keydown', e => {
  if (e.key === 'Enter') lookupVehicle();
});
</script>

<?php renderFooter(); ?>
