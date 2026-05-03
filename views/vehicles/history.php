<?php
// views/vehicles/history.php - AJAX partial for parking history
require_once __DIR__ . '/../../auth/auth.php';
requireLogin();
require_once __DIR__ . '/../../models/Vehicle.php';

$vm = new Vehicle();
$id = (int)($_GET['id'] ?? 0);
$records = $vm->getParkingHistory($id);

if (!$records):
?>
<div class="empty-state" style="padding:1.5rem">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <h3>No parking history</h3>
  <p>This vehicle has never been parked.</p>
</div>
<?php else: ?>
<div class="table-wrapper">
  <table>
    <thead><tr><th>Slot</th><th>Lot</th><th>Entry</th><th>Exit</th><th>Duration</th><th>Amount</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($records as $r): ?>
      <?php
        $mins = 0;
        if ($r['exitTime']) {
            $mins = (int)ceil((strtotime($r['exitTime']) - strtotime($r['entryTime'])) / 60);
        }
      ?>
      <tr>
        <td class="td-mono"><?= esc($r['slotNumber']) ?></td>
        <td><?= esc($r['lotName']) ?></td>
        <td style="font-size:.78rem;color:var(--text-muted)"><?= fmtDT($r['entryTime']) ?></td>
        <td style="font-size:.78rem;color:var(--text-muted)"><?= fmtDT($r['exitTime']) ?></td>
        <td class="td-mono"><?= $mins ? fmtDuration($mins) : '—' ?></td>
        <td class="td-mono" style="color:var(--accent)"><?= $r['amount'] ? '₨'.number_format($r['amount'],0) : '—' ?></td>
        <td><span class="badge badge-<?= esc($r['status']) ?>"><?= esc($r['status']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
