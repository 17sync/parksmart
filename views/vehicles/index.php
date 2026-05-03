<?php
// views/vehicles/index.php
require_once __DIR__ . '/../../auth/auth.php';
requireLogin();
require_once __DIR__ . '/../../models/Vehicle.php';
require_once __DIR__ . '/../../includes/layout.php';

$vm     = new Vehicle();
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

// If regular user, only show their vehicles via userID filter
$vehicles = isAdmin()
    ? $vm->getAll($search, $limit, $offset)
    : $vm->getAll($search, $limit, $offset); // can be filtered by userID for strict multi-tenant

$total  = $vm->count($search);
$pages  = ceil($total / $limit);

renderHead('Vehicles');
renderSidebar('vehicles');
renderTopbar('Vehicles');
?>

<?php renderFlash(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Vehicles</h1>
    <p>Register and manage vehicles in the system.</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary" onclick="Modal.open('modal-add-vehicle')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Register Vehicle
    </button>
  </div>
</div>

<!-- Search -->
<div class="card" style="margin-bottom:1.25rem;padding:1rem 1.5rem">
  <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
    <div class="search-bar" style="flex:1;min-width:200px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="search" placeholder="Search by plate or owner name..." value="<?= esc($search) ?>">
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search): ?><a href="?" class="btn btn-outline">Clear</a><?php endif; ?>
  </form>
</div>

<!-- Quick Plate Search (AJAX) -->
<div class="card" style="margin-bottom:1.25rem;padding:1rem 1.5rem">
  <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
    <div style="flex:1;min-width:200px">
      <label class="form-label">Quick License Plate Lookup</label>
      <div class="search-bar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        <input type="text" id="plate-input" placeholder="Enter plate e.g. KHI-001" style="text-transform:uppercase">
      </div>
    </div>
    <button class="btn btn-outline" onclick="searchByPlate()">Lookup</button>
  </div>
  <div id="plate-result" style="margin-top:.75rem;display:none"></div>
</div>

<!-- Vehicles Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      All Vehicles
      <span style="font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted);margin-left:.5rem"><?= $total ?> total</span>
    </div>
  </div>
  <div class="table-wrapper">
    <table id="vehicle-table">
      <thead>
        <tr>
          <th>#</th>
          <th>License Plate</th>
          <th>Type</th>
          <th>Owner</th>
          <th>Phone</th>
          <th>Registered</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($vehicles): ?>
        <?php foreach ($vehicles as $i => $v): ?>
        <tr>
          <td class="td-mono" style="color:var(--text-dim)"><?= $offset + $i + 1 ?></td>
          <td>
            <span class="td-mono" style="color:var(--accent);font-weight:700"><?= esc($v['licensePlate']) ?></span>
          </td>
          <td><span class="badge badge-car" style="text-transform:capitalize"><?= esc($v['vehicleType']) ?></span></td>
          <td><?= esc($v['ownerName']) ?></td>
          <td style="font-size:.8rem;color:var(--text-muted)"><?= esc($v['ownerPhone'] ?? '—') ?></td>
          <td style="font-size:.8rem;color:var(--text-muted)"><?= fmtDT($v['createdAt']) ?></td>
          <td>
            <div style="display:flex;gap:.4rem">
              <a href="<?= APP_URL ?>/views/records/checkin.php?vehicle=<?= $v['vehicleID'] ?>" class="btn btn-success btn-sm">Park</a>
              <button class="btn btn-ghost btn-sm" onclick="openEditVehicle(<?= htmlspecialchars(json_encode($v)) ?>)">Edit</button>
              <button class="btn btn-ghost btn-sm" onclick="viewHistory(<?= $v['vehicleID'] ?>, '<?= esc($v['licensePlate']) ?>')">History</button>
              <?php if (isAdmin()): ?>
              <button class="btn btn-danger btn-sm" onclick="deleteVehicle(<?= $v['vehicleID'] ?>)">Del</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <h3>No vehicles found</h3>
            <p>Register a new vehicle to get started.</p>
          </div>
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="pagination" style="padding:1rem">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Add Vehicle Modal -->
<div class="modal-overlay" id="modal-add-vehicle">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Register New Vehicle</div>
      <button class="modal-close" onclick="Modal.close('modal-add-vehicle')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">License Plate *</label>
          <input type="text" id="add-plate" class="form-control" placeholder="e.g. KHI-123" required style="text-transform:uppercase">
        </div>
        <div class="form-group">
          <label class="form-label">Vehicle Type *</label>
          <select id="add-vtype" class="form-control">
            <option value="car">Car</option><option value="motorcycle">Motorcycle</option>
            <option value="truck">Truck</option><option value="bus">Bus</option><option value="ev">EV</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Owner Name *</label>
        <input type="text" id="add-owner" class="form-control" placeholder="Full name" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" id="add-phone" class="form-control" placeholder="03XXXXXXXXX">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" id="add-email" class="form-control" placeholder="owner@email.com">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="Modal.close('modal-add-vehicle')">Cancel</button>
      <button class="btn btn-primary" onclick="addVehicle()">Register Vehicle</button>
    </div>
  </div>
</div>

<!-- Edit Vehicle Modal -->
<div class="modal-overlay" id="modal-edit-vehicle">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Vehicle</div>
      <button class="modal-close" onclick="Modal.close('modal-edit-vehicle')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-vid">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">License Plate *</label>
          <input type="text" id="edit-plate" class="form-control" required style="text-transform:uppercase">
        </div>
        <div class="form-group">
          <label class="form-label">Type *</label>
          <select id="edit-vtype" class="form-control">
            <option value="car">Car</option><option value="motorcycle">Motorcycle</option>
            <option value="truck">Truck</option><option value="bus">Bus</option><option value="ev">EV</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Owner Name *</label><input type="text" id="edit-owner" class="form-control" required></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Phone</label><input type="text" id="edit-phone" class="form-control"></div>
        <div class="form-group"><label class="form-label">Email</label><input type="email" id="edit-email" class="form-control"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="Modal.close('modal-edit-vehicle')">Cancel</button>
      <button class="btn btn-primary" onclick="saveVehicle()">Save Changes</button>
    </div>
  </div>
</div>

<!-- History Modal -->
<div class="modal-overlay" id="modal-history">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <div class="modal-title" id="history-title">Parking History</div>
      <button class="modal-close" onclick="Modal.close('modal-history')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body" id="history-body">
      <div class="empty-state"><span class="spinner"></span></div>
    </div>
  </div>
</div>

<script>
async function searchByPlate() {
  const q = document.getElementById('plate-input').value.trim().toUpperCase();
  if (!q) { Toast.warning('Enter a license plate.'); return; }
  const res = await apiGet('vehicle_search', { q });
  const box = document.getElementById('plate-result');
  box.style.display = '';
  if (res.success) {
    const v = res.vehicle;
    box.innerHTML = `<div class="card" style="background:var(--bg-surface);padding:1rem">
      <div style="display:flex;gap:1rem;flex-wrap:wrap">
        <div><div class="stat-label">Plate</div><div class="td-mono" style="color:var(--accent);font-size:1.1rem">${v.licensePlate}</div></div>
        <div><div class="stat-label">Owner</div><div>${v.ownerName}</div></div>
        <div><div class="stat-label">Type</div><div class="badge badge-car">${v.vehicleType}</div></div>
        <div><div class="stat-label">Phone</div><div>${v.ownerPhone||'—'}</div></div>
        <div style="margin-left:auto">
          <a href="<?= APP_URL ?>/views/records/checkin.php?vehicle=${v.vehicleID}" class="btn btn-success btn-sm">Park This Vehicle</a>
        </div>
      </div>
    </div>`;
  } else {
    box.innerHTML = `<div class="alert alert-warning"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg><span class="alert-msg">${res.message}</span></div>`;
  }
}

function openEditVehicle(v) {
  document.getElementById('edit-vid').value   = v.vehicleID;
  document.getElementById('edit-plate').value = v.licensePlate;
  document.getElementById('edit-vtype').value = v.vehicleType;
  document.getElementById('edit-owner').value = v.ownerName;
  document.getElementById('edit-phone').value = v.ownerPhone || '';
  document.getElementById('edit-email').value = v.ownerEmail || '';
  Modal.open('modal-edit-vehicle');
}

async function addVehicle() {
  const data = {
    licensePlate: document.getElementById('add-plate').value.trim().toUpperCase(),
    vehicleType:  document.getElementById('add-vtype').value,
    ownerName:    document.getElementById('add-owner').value.trim(),
    ownerPhone:   document.getElementById('add-phone').value.trim(),
    ownerEmail:   document.getElementById('add-email').value.trim(),
  };
  if (!data.licensePlate || !data.ownerName) { Toast.error('License plate and owner name are required.'); return; }
  const res = await api('vehicle_create', data);
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}

async function saveVehicle() {
  const data = {
    vehicleID:    document.getElementById('edit-vid').value,
    licensePlate: document.getElementById('edit-plate').value.trim().toUpperCase(),
    vehicleType:  document.getElementById('edit-vtype').value,
    ownerName:    document.getElementById('edit-owner').value.trim(),
    ownerPhone:   document.getElementById('edit-phone').value.trim(),
    ownerEmail:   document.getElementById('edit-email').value.trim(),
  };
  const res = await api('vehicle_update', data);
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}

async function deleteVehicle(id) {
  if (!confirm('Delete this vehicle? This cannot be undone.')) return;
  const res = await api('vehicle_delete', { vehicleID: id });
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}

async function viewHistory(id, plate) {
  document.getElementById('history-title').textContent = `History — ${plate}`;
  document.getElementById('history-body').innerHTML = '<div class="empty-state"><span class="spinner"></span></div>';
  Modal.open('modal-history');

  const res = await fetch(`<?= APP_URL ?>/views/vehicles/history.php?id=${id}`);
  const html = await res.text();
  document.getElementById('history-body').innerHTML = html;
}
</script>

<?php renderFooter(); ?>
