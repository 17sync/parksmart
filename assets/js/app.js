// ============================================================
// assets/js/app.js - Main Application JavaScript
// ============================================================

const APP_URL = window.APP_URL || '';

// ---- TOAST NOTIFICATIONS ----
const Toast = {
  container: null,

  init() {
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.className = 'toast-container';
      document.body.appendChild(this.container);
    }
  },

  show(message, type = 'info', duration = 4000) {
    this.init();
    const icons = {
      success: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
      error:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
      warning: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
      info:    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      ${icons[type] || icons.info}
      <span class="toast-msg">${message}</span>
      <button class="toast-close" onclick="this.parentElement.remove()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    `;

    this.container.appendChild(toast);
    setTimeout(() => toast.remove(), duration);
  },

  success: (msg, d) => Toast.show(msg, 'success', d),
  error:   (msg, d) => Toast.show(msg, 'error', d),
  warning: (msg, d) => Toast.show(msg, 'warning', d),
  info:    (msg, d) => Toast.show(msg, 'info', d),
};

// ---- MODAL ----
const Modal = {
  open(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
  },
  close(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
  },
  closeAll() {
    document.querySelectorAll('.modal-overlay.open').forEach(el => {
      el.classList.remove('open');
    });
    document.body.style.overflow = '';
  }
};

// Close modal on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) Modal.closeAll();
});

// ---- API HELPER ----
async function api(action, data = {}, method = 'POST') {
  try {
    const form = new FormData();
    form.append('action', action);
    for (const [k, v] of Object.entries(data)) {
      if (v !== undefined && v !== null) form.append(k, v);
    }
    const res = await fetch(`${APP_URL}/controllers/action.php`, {
      method: method === 'GET' ? 'GET' : 'POST',
      body: method === 'GET' ? null : form,
    });
    return await res.json();
  } catch (e) {
    return { success: false, message: 'Network error. Please try again.' };
  }
}

async function apiGet(action, params = {}) {
  const qs = new URLSearchParams({ action, ...params }).toString();
  try {
    const res = await fetch(`${APP_URL}/controllers/action.php?${qs}`);
    return await res.json();
  } catch (e) {
    return { success: false, message: 'Network error.' };
  }
}

// ---- SIDEBAR TOGGLE ----
function initSidebar() {
  const hamburger = document.querySelector('.hamburger');
  const sidebar   = document.querySelector('.sidebar');
  const overlay   = document.querySelector('.overlay');

  if (!hamburger || !sidebar) return;

  hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });

  overlay?.addEventListener('click', () => {
    sidebar.classList.remove('open');
  });
}

// ---- FORM VALIDATION ----
function validateForm(formEl) {
  let valid = true;
  formEl.querySelectorAll('[required]').forEach(field => {
    const err = field.parentElement.querySelector('.form-error');
    if (!field.value.trim()) {
      valid = false;
      field.style.borderColor = 'var(--danger)';
      if (err) err.textContent = 'This field is required.';
    } else {
      field.style.borderColor = '';
      if (err) err.textContent = '';
    }
  });
  return valid;
}

// ---- TABLE SEARCH ----
function initTableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;

  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ---- CONFIRM DIALOG ----
function confirmAction(message) {
  return confirm(message);
}

// ---- DURATION CALCULATOR ----
function calcDuration(entryTimeStr) {
  const entry = new Date(entryTimeStr);
  const now   = new Date();
  const mins  = Math.floor((now - entry) / 60000);
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function calcFee(entryTimeStr, ratePerHour = 50) {
  const entry = new Date(entryTimeStr);
  const now   = new Date();
  const hours = (now - entry) / 3600000;
  return Math.max(20, Math.round(hours * ratePerHour * 100) / 100);
}

// ---- LIVE CLOCK ----
function initClock(el) {
  if (!el) return;
  const update = () => {
    const d = new Date();
    el.textContent = d.toLocaleTimeString('en-PK', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  };
  update();
  setInterval(update, 1000);
}

// ---- LIVE DURATION CELLS ----
function initLiveDuration() {
  const cells = document.querySelectorAll('[data-entry-time]');
  const update = () => {
    cells.forEach(cell => {
      cell.textContent = calcDuration(cell.dataset.entryTime);
    });
  };
  update();
  setInterval(update, 60000); // update every minute
}

// ---- BAR CHART (pure JS/CSS) ----
function renderBarChart(containerId, labels, values, color = 'var(--accent)') {
  const container = document.getElementById(containerId);
  if (!container) return;

  const maxVal = Math.max(...values, 1);
  container.innerHTML = '';
  container.className = 'chart-area';

  labels.forEach((label, i) => {
    const val  = values[i] || 0;
    const pct  = (val / maxVal) * 100;
    const wrap = document.createElement('div');
    wrap.className = 'chart-bar-wrap';
    wrap.innerHTML = `
      <div class="chart-val">${val > 0 ? val : ''}</div>
      <div class="chart-bar" style="height:${Math.max(pct, 2)}%; background:${color};" title="${label}: ${val}"></div>
      <div class="chart-label">${label}</div>
    `;
    container.appendChild(wrap);
  });
}

// ---- DONUT CHART (SVG) ----
function renderDonutChart(svgId, segments) {
  const svg = document.getElementById(svgId);
  if (!svg) return;
  const total = segments.reduce((s, seg) => s + seg.value, 0);
  if (total === 0) return;

  const cx = 60, cy = 60, r = 45, strokeW = 18;
  const circum = 2 * Math.PI * r;
  svg.setAttribute('viewBox', '0 0 120 120');

  let offset = 0;
  svg.innerHTML = segments.map(seg => {
    const frac  = seg.value / total;
    const dash  = frac * circum;
    const gap   = circum - dash;
    const el = `<circle cx="${cx}" cy="${cy}" r="${r}"
      fill="none" stroke="${seg.color}" stroke-width="${strokeW}"
      stroke-dasharray="${dash} ${gap}"
      stroke-dashoffset="${-offset}"
      transform="rotate(-90 ${cx} ${cy})"/>`;
    offset += dash;
    return el;
  }).join('') +
  `<text x="${cx}" y="${cy}" text-anchor="middle" dominant-baseline="middle"
    font-family="'Space Mono',monospace" font-size="13" fill="var(--text)" font-weight="700">${total}</text>
   <text x="${cx}" y="${cy + 13}" text-anchor="middle" dominant-baseline="middle"
    font-family="'DM Sans',sans-serif" font-size="6" fill="var(--text-muted)">TOTAL</text>`;
}

// ---- INIT ON DOM READY ----
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initLiveDuration();

  const clock = document.getElementById('live-clock');
  if (clock) initClock(clock);
});
