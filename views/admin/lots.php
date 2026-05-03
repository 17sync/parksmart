<?php
// views/admin/lots.php
require_once __DIR__ . '/../../auth/auth.php';
requireAdmin();
require_once __DIR__ . '/../../models/ParkingSlot.php';
require_once __DIR__ . '/../../includes/layout.php';

$ps   = new ParkingSlot();
$lots = $ps->getLots();
$overview = $ps->getSlotOverview();

renderHead('Parking Lots');
renderSidebar('lots');
renderTopbar('Parking Lots');
?>
<?php renderFlash(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Parking Lots</h1>
    <p>Manage parking lot locations and capacity.</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary" onclick="Modal.open('modal-add-lot')">+ Add Lot</button>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem">
<?php foreach ($overview as $lot): ?>
<div class="card">
  <div class="card-header">
    <div class="card-title" style="font-size:1rem"><?= esc($lot['lotName']) ?></div>
    <span style="font-size:.72rem;color:var(--text-muted);font-family:var(--font-mono)"><?= $lot['occupancyRate'] ?>% full</span>
  </div>
  <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="vertical-align:middle;margin-right:.3rem"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
    <?= esc($lot['lotLocation']) ?>
  </div>
  <div style="background:var(--bg-surface);border-radius:var(--radius);height:8px;margin-bottom:1rem">
    <div style="height:100%;width:<?= $lot['occupancyRate'] ?>%;background:<?= $lot['occupancyRate']>80?'var(--danger)':($lot['occupancyRate']>50?'var(--warning)':'var(--success)') ?>;border-radius:var(--radius)"></div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;text-align:center">
    <div style="background:rgba(61,214,140,.1);padding:.6rem;border-radius:var(--radius)">
      <div style="font-family:var(--font-mono);font-size:1.2rem;color:var(--success)"><?= $lot['availableSlots'] ?></div>
      <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Available</div>
    </div>
    <div style="background:rgba(232,82,82,.1);padding:.6rem;border-radius:var(--radius)">
      <div style="font-family:var(--font-mono);font-size:1.2rem;color:var(--danger)"><?= $lot['occupiedSlots'] ?></div>
      <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Occupied</div>
    </div>
    <div style="background:rgba(245,167,66,.1);padding:.6rem;border-radius:var(--radius)">
      <div style="font-family:var(--font-mono);font-size:1.2rem;color:var(--warning)"><?= $lot['maintenanceSlots'] ?></div>
      <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Maint.</div>
    </div>
  </div>
  <div style="margin-top:1rem;display:flex;gap:.5rem">
    <a href="<?= APP_URL ?>/views/slots/index.php?lot=<?= $lot['lotID'] ?>" class="btn btn-outline btn-sm" style="flex:1">View Slots</a>
  </div>
</div>
<?php endforeach; ?>
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
      <div class="form-group"><label class="form-label">Lot Name *</label><input type="text" id="lot-name" class="form-control" placeholder="e.g. West Wing Garage" required></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Capacity *</label><input type="number" id="lot-capacity" class="form-control" min="1" placeholder="50" required></div>
        <div class="form-group"><label class="form-label">Location *</label><input type="text" id="lot-location" class="form-control" placeholder="Address" required></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="Modal.close('modal-add-lot')">Cancel</button>
      <button class="btn btn-primary" onclick="addLot()">Create Lot</button>
    </div>
  </div>
</div>

<script>
async function addLot() {
  const data = {
    lotName:  document.getElementById('lot-name').value.trim(),
    capacity: document.getElementById('lot-capacity').value,
    location: document.getElementById('lot-location').value.trim(),
  };
  if (!data.lotName || !data.capacity || !data.location) { Toast.error('Fill all required fields.'); return; }
  const res = await api('lot_create', data);
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}
</script>

<?php renderFooter(); ?>
