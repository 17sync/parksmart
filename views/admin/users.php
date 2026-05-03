<?php
// views/admin/users.php
require_once __DIR__ . '/../../auth/auth.php';
requireAdmin();
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../includes/layout.php';

$um    = new User();
$users = $um->getAll(100, 0);
$total = $um->count();

renderHead('Users');
renderSidebar('users');
renderTopbar('User Management');
?>

<?php renderFlash(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Users</h1>
    <p>Manage all system users and their roles.</p>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      All Users <span style="font-size:.75rem;color:var(--text-muted);font-family:var(--font-mono);margin-left:.5rem"><?= $total ?> total</span>
    </div>
    <div class="search-bar" style="width:220px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="user-search" placeholder="Search users...">
    </div>
  </div>
  <div class="table-wrapper">
    <table id="user-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Username</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Status</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $i => $u): ?>
        <tr>
          <td class="td-mono" style="color:var(--text-dim)"><?= $i+1 ?></td>
          <td class="td-mono" style="color:var(--accent)"><?= esc($u['username']) ?></td>
          <td><?= esc($u['fullName']) ?></td>
          <td style="font-size:.8rem;color:var(--text-muted)"><?= esc($u['email']) ?></td>
          <td style="font-size:.8rem;color:var(--text-muted)"><?= esc($u['phone'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $u['role'] ?>"><?= esc($u['role']) ?></span></td>
          <td>
            <span class="badge <?= $u['isActive'] ? 'badge-available' : 'badge-cancelled' ?>">
              <?= $u['isActive'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td style="font-size:.78rem;color:var(--text-muted)"><?= fmtDT($u['createdAt']) ?></td>
          <td>
            <div style="display:flex;gap:.4rem">
              <button class="btn btn-ghost btn-sm" onclick="openEditUser(<?= htmlspecialchars(json_encode($u)) ?>)">Edit</button>
              <?php if ($u['userID'] != currentUser()['id']): ?>
              <button class="btn btn-danger btn-sm" onclick="deactivateUser(<?= $u['userID'] ?>, '<?= esc($u['username']) ?>')">
                <?= $u['isActive'] ? 'Deactivate' : 'Deactivated' ?>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="modal-edit-user">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit User</div>
      <button class="modal-close" onclick="Modal.close('modal-edit-user')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="eu-id">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" id="eu-name" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" id="eu-phone" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" id="eu-email" class="form-control">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role</label>
          <select id="eu-role" class="form-control">
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" id="eu-pass" class="form-control" placeholder="Leave blank to keep">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="Modal.close('modal-edit-user')">Cancel</button>
      <button class="btn btn-primary" onclick="saveUser()">Save Changes</button>
    </div>
  </div>
</div>

<script>
initTableSearch('user-search', 'user-table');

function openEditUser(u) {
  document.getElementById('eu-id').value    = u.userID;
  document.getElementById('eu-name').value  = u.fullName;
  document.getElementById('eu-email').value = u.email;
  document.getElementById('eu-phone').value = u.phone || '';
  document.getElementById('eu-role').value  = u.role;
  document.getElementById('eu-pass').value  = '';
  Modal.open('modal-edit-user');
}

async function saveUser() {
  const data = {
    userID:   document.getElementById('eu-id').value,
    fullName: document.getElementById('eu-name').value,
    email:    document.getElementById('eu-email').value,
    phone:    document.getElementById('eu-phone').value,
    role:     document.getElementById('eu-role').value,
    password: document.getElementById('eu-pass').value,
  };
  const res = await api('user_update', data);
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}

async function deactivateUser(id, username) {
  if (!confirm(`Deactivate user "${username}"?`)) return;
  const res = await api('user_delete', { userID: id });
  if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(res.message);
}
</script>

<?php renderFooter(); ?>
