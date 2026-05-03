<?php
// views/auth/login.php
require_once __DIR__ . '/../../auth/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(APP_URL . (isAdmin() ? '/views/dashboard/admin.php' : '/views/dashboard/user.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = loginUser(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
    if ($result['success']) {
        redirect(APP_URL . ($result['role'] === 'admin' ? '/views/dashboard/admin.php' : '/views/dashboard/user.php'));
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — ParkSmart</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
  <script>window.APP_URL = '<?= APP_URL ?>';</script>
</head>
<body>
<div class="auth-page">
  <div class="auth-container">

    <div class="auth-logo">
      <div class="logo-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2"/>
          <path d="M9 17V7h4a3 3 0 0 1 0 6H9"/>
        </svg>
        ParkSmart
      </div>
      <p>Parking Management System</p>
    </div>

    <div class="auth-card">
      <div class="auth-tabs">
        <button class="auth-tab active" id="tab-login" onclick="switchTab('login')">Sign In</button>
        <button class="auth-tab" id="tab-register" onclick="switchTab('register')">Register</button>
      </div>

      <!-- LOGIN FORM -->
      <div id="form-login">
        <?php if ($error): ?>
          <div class="alert alert-error" style="margin-bottom:1rem">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span class="alert-msg"><?= esc($error) ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" onsubmit="return handleLogin(event)">
          <div class="form-group">
            <label class="form-label">Username or Email</label>
            <input type="text" name="username" class="form-control" placeholder="Enter username or email"
                   value="<?= esc($_POST['username'] ?? '') ?>" required autocomplete="username">
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg" id="loginBtn">
            Sign In
          </button>
        </form>

        <div style="margin-top:1.25rem;padding:1rem;background:var(--bg-surface);border-radius:var(--radius);font-size:.78rem;color:var(--text-muted)">
          <div style="font-family:var(--font-mono);color:var(--accent);margin-bottom:.4rem;font-size:.72rem;">DEMO CREDENTIALS</div>
          <div><strong style="color:var(--text)">Admin:</strong> admin / Admin@123</div>
          <div><strong style="color:var(--text)">User:</strong> john_doe / password</div>
        </div>
      </div>

      <!-- REGISTER FORM -->
      <div id="form-register" style="display:none">
        <form id="registerForm" onsubmit="return handleRegister(event)">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="fullName" class="form-control" placeholder="Full name" required>
            </div>
            <div class="form-group">
              <label class="form-label">Username *</label>
              <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" placeholder="Email address" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="e.g. 03001234567">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Password *</label>
              <input type="password" name="password" id="reg-pass" class="form-control" placeholder="Min 6 chars" required>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm Password *</label>
              <input type="password" id="reg-pass2" class="form-control" placeholder="Repeat password" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg" id="registerBtn">
            Create Account
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
function switchTab(tab) {
  document.getElementById('form-login').style.display    = tab === 'login'    ? '' : 'none';
  document.getElementById('form-register').style.display = tab === 'register' ? '' : 'none';
  document.getElementById('tab-login').classList.toggle('active', tab === 'login');
  document.getElementById('tab-register').classList.toggle('active', tab === 'register');
}

async function handleLogin(e) {
  e.preventDefault();
  const btn  = document.getElementById('loginBtn');
  const form = e.target;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Signing in...';

  const data = {
    username: form.username.value,
    password: form.password.value,
  };
  const res = await api('login', data);
  if (res.success) {
    window.location.href = window.APP_URL +
      (res.role === 'admin' ? '/views/dashboard/admin.php' : '/views/dashboard/user.php');
  } else {
    Toast.error(res.message);
    btn.disabled = false;
    btn.innerHTML = 'Sign In';
  }
  return false;
}

async function handleRegister(e) {
  e.preventDefault();
  const btn  = document.getElementById('registerBtn');
  const form = e.target;
  const pass  = document.getElementById('reg-pass').value;
  const pass2 = document.getElementById('reg-pass2').value;

  if (pass !== pass2) { Toast.error('Passwords do not match.'); return false; }
  if (pass.length < 6) { Toast.error('Password must be at least 6 characters.'); return false; }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Creating account...';

  const data = {
    username: form.username.value,
    email:    form.email.value,
    password: pass,
    fullName: form.fullName.value,
    phone:    form.phone.value,
  };
  const res = await api('register', data);
  if (res.success) {
    Toast.success(res.message);
    switchTab('login');
  } else {
    Toast.error(res.message);
  }
  btn.disabled = false;
  btn.innerHTML = 'Create Account';
  return false;
}
</script>
</body>
</html>
