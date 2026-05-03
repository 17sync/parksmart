<?php
// ============================================================
// includes/layout.php - Reusable layout components
// ============================================================

function renderHead(string $title = 'ParkSmart', string $extraCss = ''): void {
    $base = APP_URL;
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title} — ParkSmart</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="{$base}/assets/css/style.css">
  {$extraCss}
  <script>window.APP_URL = '{$base}';</script>
</head>
<body>
HTML;
}

function renderSidebar(string $active = ''): void {
    $user    = currentUser();
    $base    = APP_URL;
    $isAdmin = isAdmin();
    $initial = strtoupper(substr($user['name'], 0, 1) ?: 'U');

    $userNav = [
        ['href' => "$base/views/dashboard/user.php",    'icon' => 'grid',    'label' => 'Dashboard',      'key' => 'dashboard'],
        ['href' => "$base/views/slots/index.php",       'icon' => 'map-pin', 'label' => 'Parking Slots',  'key' => 'slots'],
        ['href' => "$base/views/vehicles/index.php",    'icon' => 'truck',   'label' => 'My Vehicles',    'key' => 'vehicles'],
        ['href' => "$base/views/records/index.php",     'icon' => 'list',    'label' => 'Parking Records','key' => 'records'],
        ['href' => "$base/views/payments/index.php",    'icon' => 'credit-card', 'label' => 'Payments',   'key' => 'payments'],
    ];

    $adminNav = [
        ['href' => "$base/views/dashboard/admin.php",   'icon' => 'bar-chart', 'label' => 'Admin Dashboard', 'key' => 'admin-dash'],
        ['href' => "$base/views/admin/users.php",       'icon' => 'users',     'label' => 'Users',           'key' => 'users'],
        ['href' => "$base/views/admin/lots.php",        'icon' => 'layers',    'label' => 'Parking Lots',    'key' => 'lots'],
        ['href' => "$base/views/reports/index.php",     'icon' => 'trending-up','label' => 'Reports',        'key' => 'reports'],
    ];

    $icons = [
        'grid'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'map-pin'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'truck'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        'list'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
        'credit-card' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'bar-chart'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>',
        'users'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'layers'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
        'trending-up' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
        'log-out'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'parking'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/></svg>',
    ];

    $navHtml = '';
    foreach ($userNav as $item) {
        $cls = $active === $item['key'] ? 'nav-item active' : 'nav-item';
        $icon = $icons[$item['icon']] ?? '';
        $navHtml .= "<a href=\"{$item['href']}\" class=\"{$cls}\">{$icon} {$item['label']}</a>\n";
    }

    $adminHtml = '';
    if ($isAdmin) {
        $adminHtml .= '<div class="nav-section">Admin</div>';
        foreach ($adminNav as $item) {
            $cls = $active === $item['key'] ? 'nav-item active' : 'nav-item';
            $icon = $icons[$item['icon']] ?? '';
            $adminHtml .= "<a href=\"{$item['href']}\" class=\"{$cls}\">{$icon} {$item['label']}</a>\n";
        }
    }

    $logoutIcon = $icons['log-out'];
    $parkingIcon = $icons['parking'];
    $roleBadge = $isAdmin ? '<span class="badge badge-admin">admin</span>' : 'user';

    echo <<<HTML
<div class="app-wrapper">
<div class="overlay"></div>
<aside class="sidebar">
  <a href="{$base}/views/dashboard/user.php" class="sidebar-logo">
    {$parkingIcon}
    ParkSmart
  </a>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    {$navHtml}
    {$adminHtml}
  </nav>
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-avatar">{$initial}</div>
      <div class="user-info">
        <div class="name">{$user['name']}</div>
        <div class="role">{$user['role']}</div>
      </div>
      <form method="POST" action="{$base}/auth/logout.php">
        <button type="submit" class="logout-btn" title="Logout">{$logoutIcon}</button>
      </form>
    </div>
  </div>
</aside>
HTML;
}

function renderTopbar(string $title, array $actions = []): void {
    $base = APP_URL;
    $actHtml = '';
    foreach ($actions as $a) {
        $actHtml .= "<button class=\"btn btn-{$a['type']} btn-sm\" onclick=\"{$a['onclick']}\">{$a['label']}</button>";
    }

    echo <<<HTML
<div class="main-content">
<header class="topbar">
  <button class="hamburger" aria-label="Menu">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <div class="topbar-title">{$title}</div>
  <div class="topbar-actions">
    <span id="live-clock" style="font-family:var(--font-mono);font-size:.8rem;color:var(--text-muted)"></span>
    {$actHtml}
  </div>
</header>
<main class="page-body">
HTML;
}

function renderFooter(): void {
    $base = APP_URL;
    echo <<<HTML
</main>
</div><!-- /.main-content -->
</div><!-- /.app-wrapper -->
<script src="{$base}/assets/js/app.js"></script>
</body>
</html>
HTML;
}

function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $type = $flash['type'] ?? 'info';
    $msg  = esc($flash['message'] ?? '');
    $icons = [
        'success' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'error'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        'warning' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>',
        'info'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    ];
    $icon = $icons[$type] ?? $icons['info'];
    echo "<div class=\"alert alert-{$type}\">{$icon}<span class=\"alert-msg\">{$msg}</span></div>";
}
