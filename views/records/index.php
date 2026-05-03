<?php
// views/records/index.php
require_once __DIR__ . '/../../auth/auth.php';
requireLogin();
require_once __DIR__ . '/../../models/ParkingRecord.php';
require_once __DIR__ . '/../../includes/layout.php';

$pr     = new ParkingRecord();
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;
$records = $pr->getAll($status, $limit, $offset);
$total   = $pr->count($status);
$pages   = ceil($total / $limit);

renderHead('Parking Records');
renderSidebar('records');
renderTopbar('Parking Records');
?>

<?php renderFlash(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Parking Records</h1>
    <p>Track all vehicle entries, exits, and sessions.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/views/records/checkin.php" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Check-In
    </a>
  </div>
</div>

<!-- Status Filter Tabs -->
<div style="display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap">
  <?php foreach ([''=>'All Records','active'=>'Active','completed'=>'Completed','cancelled'=>'Cancelled'] as $val => $label): ?>
  <a href="?status=<?= $val ?>" class="btn <?= $status === $val ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $label ?></a>
  <?php endforeach; ?>
  <span style="margin-left:auto;font-size:.8rem;color:var(--text-muted);line-height:2rem"><?= $total ?> records</span>
</div>

<!-- Records Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Records
    </div>
    <div class="search-bar" style="width:220px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="rec-search" placeholder="Search records...">
    </div>
  </div>
  <div class="table-wrapper">
    <table id="rec-table">
      <thead>
        <tr>
          <th>#ID</th>
          <th>License Plate</th>
          <th>Owner</th>
          <th>Slot</th>
          <th>Entry Time</th>
          <th>Exit Time</th>
          <th>Duration</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($records): ?>
        <?php foreach ($records as $r): ?>
        <?php
          $mins = 0;
          if ($r['exitTime']) {
              $mins = (int)ceil((strtotime($r['exitTime']) - strtotime($r['entryTime'])) / 60);
          } elseif ($r['status'] === 'active') {
              $mins = (int)ceil((time() - strtotime($r['entryTime'])) / 60);
          }
        ?>
          <tr>
            <td class="td-mono" style="color:var(--text-dim)">#<?= $r['recordID'] ?></td>
            <td class="td-mono" style="color:var(--accent);font-weight:700"><?= esc($r['licensePlate']) ?></td>
            <td><?= esc($r['ownerName']) ?></td>
            <td><span class="badge badge-car"><?= esc($r['slotNumber']) ?></span> <small style="color:var(--text-muted)"><?= esc($r['lotName']) ?></small></td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= fmtDT($r['entryTime']) ?></td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= fmtDT($r['exitTime']) ?></td>
            <td class="td-mono" style="font-size:.8rem">
              <?php if ($r['status'] === 'active'): ?>
              <span data-entry-time="<?= esc($r['entryTime']) ?>" style="color:var(--info)"><?= fmtDuration($mins) ?></span>
              <?php else: ?>
              <?= $mins ? fmtDuration($mins) : '—' ?>
              <?php endif; ?>
            </td>
            <td class="td-mono">
              <?php if ($r['amount']): ?>
              <span style="color:var(--accent)">₨<?= number_format($r['amount'], 0) ?></span>
              <?php else: ?>—
              <?php endif; ?>
            </td>
            <td><span class="badge badge-<?= esc($r['status']) ?>"><span class="badge-dot"></span><?= esc($r['status']) ?></span></td>
            <td>
              <?php if ($r['status'] === 'active'): ?>
              <button class="btn btn-danger btn-sm" onclick="openCheckout(<?= $r['recordID'] ?>, '<?= esc($r['licensePlate']) ?>', '<?= esc($r['entryTime']) ?>')">
                Check Out
              </button>
              <?php else: ?>
              <button class="btn btn-ghost btn-sm" onclick="viewDetail(<?= $r['recordID'] ?>)">View</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="10">
          <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg>
            <h3>No records found</h3>
            <p>Start by checking in a vehicle.</p>
          </div>
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="pagination" style="padding:1rem">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?status=<?= urlencode($status) ?>&page=<?= $p ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Check-Out Modal -->
<div class="modal-overlay" id="modal-checkout">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Vehicle Check-Out</div>
      <button class="modal-close" onclick="Modal.close('modal-checkout')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="co-recordID">
      <div style="background:var(--bg-surface);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div><div class="stat-label">Vehicle</div><div class="td-mono" id="co-plate" style="color:var(--accent);font-size:1rem"></div></div>
          <div><div class="stat-label">Duration</div><div class="td-mono" id="co-duration" style="color:var(--info);font-size:1rem"></div></div>
          <div><div class="stat-label">Entry Time</div><div id="co-entry" style="font-size:.85rem"></div></div>
          <div><div class="stat-label">Estimated Fee</div><div class="td-mono" id="co-fee" style="color:var(--accent);font-size:1.2rem"></div></div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Payment Method</label>
        <select id="co-method" class="form-control">
          <option value="cash">Cash</option>
          <option value="card">Card</option>
          <option value="online">Online</option>
          <option value="wallet">Digital Wallet</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="Modal.close('modal-checkout')">Cancel</button>
      <button class="btn btn-danger" onclick="doCheckout()" id="checkout-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Confirm Check-Out
      </button>
    </div>
  </div>
</div>

<!-- Detail Modal -->
<div class="modal-overlay" id="modal-detail">
  <div class="modal" style="max-width:540px">
    <div class="modal-header">
      <div class="modal-title">Record Details</div>
      <button class="modal-close" onclick="Modal.close('modal-detail')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body" id="detail-body">
      <div class="empty-state"><span class="spinner"></span></div>
    </div>
  </div>
</div>

<script>
initTableSearch('rec-search', 'rec-table');

function openCheckout(id, plate, entryTime) {
  const mins = Math.floor((Date.now() - new Date(entryTime).getTime()) / 60000);
  const h = Math.floor(mins/60), m = mins%60;
  const fee = Math.max(<?= MIN_CHARGE ?>, Math.round(mins/60 * <?= RATE_PER_HOUR ?> * 100)/100);

  document.getElementById('co-recordID').value = id;
  document.getElementById('co-plate').textContent    = plate;
  document.getElementById('co-duration').textContent = (h > 0 ? h+'h ' : '') + m+'m';
  document.getElementById('co-entry').textContent    = new Date(entryTime).toLocaleString('en-PK');
  document.getElementById('co-fee').textContent      = '₨' + fee.toFixed(2);
  Modal.open('modal-checkout');
}

async function doCheckout() {
  const btn = document.getElementById('checkout-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Processing...';

  const res = await api('checkout', {
    recordID:      document.getElementById('co-recordID').value,
    paymentMethod: document.getElementById('co-method').value,
  });

  if (res.success) {
    Modal.close('modal-checkout');
    Toast.success(`Checked out! Fee: ₨${res.fee} | Ref: ${res.txnRef}`);
    setTimeout(() => location.reload(), 1500);
  } else {
    Toast.error(res.message);
    btn.disabled = false;
    btn.innerHTML = 'Confirm Check-Out';
  }
}

async function viewDetail(id) {
  document.getElementById('detail-body').innerHTML = '<div class="empty-state"><span class="spinner"></span></div>';
  Modal.open('modal-detail');
  const res = await fetch(`<?= APP_URL ?>/views/records/detail.php?id=${id}`);
  document.getElementById('detail-body').innerHTML = await res.text();
}
</script>

<?php renderFooter(); ?>
