<?php
// views/slots/index.php
require_once __DIR__ . '/../../auth/auth.php';
requireLogin();
require_once __DIR__ . '/../../models/ParkingSlot.php';
require_once __DIR__ . '/../../includes/layout.php';

$ps     = new ParkingSlot();
$lots   = $ps->getLots();
$filter_status = $_GET['status'] ?? '';
$filter_lot    = (int)($_GET['lot'] ?? 0);
$slots  = $ps->getAll($filter_status, $filter_lot);
$stats  = $ps->getStats();

renderHead('Parking Slots');
renderSidebar('slots');
renderTopbar('Parking Slots');
?>

<?php renderFlash(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Parking Slots</h1>
    <p>Monitor and manage all parking slots across lots.</p>
  </div>
  <div class="page-header-actions">
    <?php if (isAdmin()): ?>
    <button class="btn btn-outline" onclick="Modal.open('modal-add-lot')">+ Add Lot</button>
    <button class="btn btn-primary" onclick="Modal.open('modal-add-slot')">+ Add Slot</button>
    <?php endif; ?>
  </div>
</div>

<!-- Quick Stats -->
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem">
  <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= $stats['total'] ?></div></div>
  <div class="stat-card green"><div class="stat-label">Available</div><div class="stat-value"><?= $stats['available'] ?></div></div>
  <div class="stat-card red"><div class="stat-label">Occupied</div><div class="stat-value"><?= $stats['occupied'] ?></div></div>
  <div class="stat-card orange"><div class="stat-label">Maintenance</div><div class="stat-value"><?= $stats['maintenance'] ?></div></div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:1.25rem">
  <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
    <div class="form-group" style="margin:0;flex:1;min-width:150px">
      <label class="form-label">Filter by Lot</label>
      <select name="lot" class="form-control">
        <option value="">All Lots</option>
        <?php foreach ($lots as $l): ?>
        <option value="<?= $l['lotID'] ?>" <?= $filter_lot == $l['lotID'] ? 'selected' : '' ?>><?= esc($l['lotName']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin:0;flex:1;min-width:150px">
      <label class="form-label">Filter by Status</label>
      <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <option value="available"   <?= $filter_status === 'available'   ? 'selected' : '' ?>>Available</option>
        <option value="occupied"    <?= $filter_status === 'occupied'    ? 'selected' : '' ?>>Occupied</option>
        <option value="maintenance" <?= $filter_status === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
      </select>
    </div>
    <div style="display:flex;gap:.5rem">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="?" class="btn btn-outline">Reset</a>
      <button type="button" class="btn btn-ghost" onclick="toggleView()" id="view-toggle">☰ Table</button>
    </div>
  </form>
</div>

<!-- GRID VIEW (default) -->
<div id="grid-view">
<?php
$grouped = [];
foreach ($slots as $s) { $grouped[$s['lotName']][] = $s; }
foreach ($grouped as $lotName => $lotSlots):
?>
<div class="card" style="margin-bottom:1.25rem">
  <div class="card-header">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      <?= esc($lotName) ?>
    </div>
    <div style="font-size:.8rem;color:var(--text-muted)">
      <?= count(array_filter($lotSlots, fn($s) => $s['status'] === 'available')) ?> available /
      <?= count($lotSlots) ?> total
    </div>
  </div>
  <div class="slot-grid">
    <?php foreach ($lotSlots as $slot): ?>
    <div class="slot-tile <?= esc($slot['status']) ?>"
         onclick="<?= isAdmin() ? "openSlotEdit({$slot['slotID']}, '{$slot['slotNumber']}', '{$slot['status']}', '{$slot['slotType']}', {$slot['lotID']})" : '' ?>"
         title="<?= esc($slot['slotNumber']) ?> — <?= esc($slot['status']) ?>">
      <div class="slot-number"><?= esc($slot['slotNumber']) ?></div>
      <div class="slot-type"><?= esc($slot['slotType']) ?></div>
      <div class="slot-status-dot"></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- TABLE VIEW -->
<div id="table-view" style="display:none">
  <div class="card">
    <div class="card-header">
      <div class="card-title">All Slots</div>
      <div class="search-bar" style="width:220px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="slot-search" placeholder="Search slots...">
      </div>
    </div>
    <div class="table-wrapper">
      <table id="slot-table">
        <thead><tr><th>Slot #</th><th>Lot</th><th>Location</th><th>Type</th><th>Status</th><?php if(isAdmin()):?><th>Actions</th><?php endif;?></tr></thead>
        <tbody>
        <?php foreach ($slots as $slot): ?>
          <tr>
            <td class="td-mono"><?= esc($slot['slotNumber']) ?></td>
            <td><?= esc($slot['lotName']) ?></td>
            <td style="font-size:.8rem;color:var(--text-muted)"><?= esc($slot['location']) ?></td>
            <td><span class="badge badge-car"><?= esc($slot['slotType']) ?></span></td>
            <td><span class="badge badge-<?= esc($slot['status']) ?>"><span class="badge-dot"></span><?= esc($slot['status']) ?></span></td>
            <?php if(isAdmin()):?>
            <td>
              <button class="btn btn-ghost btn-sm" onclick="openSlotEdit(<?= $slot['slotID'] ?>, '<?= esc($slot['slotNumber']) ?>', '<?= esc($slot['status']) ?>', '<?= esc($slot['slotType']) ?>', <?= $slot['lotID'] ?>)">Edit</button>
              <button class="btn btn-danger btn-sm" onclick="deleteSlot(<?= $slot['slotID'] ?>)">Del</button>
            </td>
            <?php endif;?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (isAdmin()): ?>
<!-- Add Slot Modal -->
<div class="modal-overlay" id="modal-add-slot">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Parking Slot</div>
      <button class="modal-close" onclick="Modal.close('modal-add-slot')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Parking Lot *</label>
        <select id="add-lotID" class="form-control" required>
          <?php foreach ($lots as $l): ?>
          <option value="<?= $l['lotID'] ?>"><?= esc($l['lotName']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Slot Number *</label>
          <input type="text" id="add-slotNumber" class="form-control" placeholder="e.g. A-10" required>
        </div>
        <div class="form-group">
          <label class="form-label">Type</label>
          <select id="add-slotType" class="form-control">
            <option value="standard">Standard</option>
            <option value="compact">Compact</option>
            <option value="handicapped">Handicapped</option>
            <option value="ev">EV</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Location Description *</label>
        <input type="text" id="add-location" class="form-control" placeholder="e.g. Ground Floor - Section A" required>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="Modal.close('modal-add-slot')">Cancel</button>
      <button class="btn btn-primary" onclick="addSlot()">Create Slot</button>
    </div>
  </div>
</div>

<!-- Edit Slot Modal -->
<div class="modal-overlay" id="modal-edit-slot">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Slot</div>
      <button class="modal-close" onclick="Modal.close('modal-edit-slot')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="edit-slotID">
      <div class="form-group">
        <label class="form-label">Lot *</label>
        <select id="edit-lotID" class="form-control">
          <?php foreach ($lots as $l): ?>
          <option value="<?= $l['lotID'] ?>"><?= esc($l['lotName']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Slot Number *</label>
          <input type="text" id="edit-slotNumber" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Type</label>
          <select id="edit-slotType" class="form-control">
            <option value="standard">Standard</option><option value="compact">Compact</option>
            <option value="handicapped">Handicapped</option><option value="ev">EV</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="edit-status" class="form-control">
          <option value="available">Available</option>
          <option value="occupied">Occupied</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="Modal.close('modal-edit-slot')">Cancel</button>
      <button class="btn btn-primary" onclick="saveSlot()">Save Changes</button>
    </div>
  </div>
</div>

<!-- Add Lot Modal -->
<div class="modal-overlay" id="modal-add-lot">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Parking Lot</div>
      <button class="modal-close" onclick="Modal.close('modal-add-lot')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Lot Name *</label><input type="text" id="lot-name" class="form-control" placeholder="e.g. East Wing Garage" required></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Capacity *</label><input type="number" id="lot-capacity" class="form-control" placeholder="100" min="1" required></div>
        <div class="form-group"><label class="form-label">Location *</label><input type="text" id="lot-location" class="form-control" placeholder="Address / Area" required></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="Modal.close('modal-add-lot')">Cancel</button>
      <button class="btn btn-primary" onclick="addLot()">Create Lot</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
let currentView = 'grid';
function toggleView() {
  currentView = currentView === 'grid' ? 'table' : 'grid';
  document.getElementById('grid-view').style.display  = currentView === 'grid'  ? '' : 'none';
  document.getElementById('table-view').style.display = currentView === 'table' ? '' : 'none';
  document.getElementById('view-toggle').textContent  = currentView === 'grid'  ? '☰ Table' : '⊞ Grid';
  if (currentView === 'table') initTableSearch('slot-search', 'slot-table');
}

function openSlotEdit(id, num, status, type, lotID) {
  document.getElementById('edit-slotID').value = id;
  document.getElementById('edit-slotNumber').value = num;
  document.getElementById('edit-status').value = status;
  document.getElementById('edit-slotType').value = type;
  document.getElementById('edit-lotID').value = lotID;
  Modal.open('modal-edit-slot');
}

async function addSlot() {
  const data = {
    lotID: document.getElementById('add-lotID').value,
    slotNumber: document.getElementById('add-slotNumber').value,
    slotType: document.getElementById('add-slotType').value,
    location: document.getElementById('add-location').value,
  };
  if (!data.slotNumber || !data.location) { Toast.error('Fill all required fields.'); return; }
  const res = await api('slot_create', data);
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}

async function saveSlot() {
  const data = {
    slotID: document.getElementById('edit-slotID').value,
    lotID: document.getElementById('edit-lotID').value,
    slotNumber: document.getElementById('edit-slotNumber').value,
    slotType: document.getElementById('edit-slotType').value,
    status: document.getElementById('edit-status').value,
    location: document.getElementById('edit-slotNumber').value,
  };
  const res = await api('slot_update', data);
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}

async function deleteSlot(id) {
  if (!confirm('Delete this slot?')) return;
  const res = await api('slot_delete', { slotID: id });
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}

async function addLot() {
  const data = {
    lotName: document.getElementById('lot-name').value,
    capacity: document.getElementById('lot-capacity').value,
    location: document.getElementById('lot-location').value,
  };
  if (!data.lotName || !data.capacity || !data.location) { Toast.error('Fill all required fields.'); return; }
  const res = await api('lot_create', data);
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}
</script>

<?php renderFooter(); ?>
