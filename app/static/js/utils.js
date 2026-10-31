/**
 * utils.js — Shared utility functions (ES module).
 */

/** Debounce a function call. */
export function debounce(fn, delay) {
  let timer;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}

/** Format a large number with locale commas. */
export function fmtNum(n) {
  if (n == null || n === '' || isNaN(n)) return '—';
  return Number(n).toLocaleString();
}

/** Format a date string to "Oct 14, 2026 HH:MM UTC". */
export function fmtDate(iso) {
  if (!iso) return '—';
  try {
    return new Date(iso).toLocaleString('en-IN', {
      year: 'numeric', month: 'short', day: 'numeric',
      hour: '2-digit', minute: '2-digit', timeZone: 'UTC', timeZoneName: 'short'
    });
  } catch { return iso; }
}

/** Show a toast notification. */
let _toastContainer = null;
export function showToast(message, type = 'info', duration = 4000) {
  if (!_toastContainer) {
    _toastContainer = document.querySelector('.toast-container');
    if (!_toastContainer) {
      _toastContainer = document.createElement('div');
      _toastContainer.className = 'toast-container';
      document.body.appendChild(_toastContainer);
    }
  }
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.setAttribute('role', 'status');
  toast.setAttribute('aria-live', 'polite');
  toast.innerHTML = `
    <span class="toast-icon" aria-hidden="true">${icons[type] || 'ℹ️'}</span>
    <span class="toast-body">${escHtml(message)}</span>
    <button class="toast-close" aria-label="Close notification">✕</button>
  `;
  toast.querySelector('.toast-close').addEventListener('click', () => removeToast(toast));
  _toastContainer.appendChild(toast);
  if (duration > 0) setTimeout(() => removeToast(toast), duration);
}

function removeToast(toast) {
  if (!toast.parentNode) return;
  toast.classList.add('exiting');
  setTimeout(() => toast.remove(), 200);
}

/** Escape HTML for use in innerHTML contexts. */
export function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/** Get the CSRF token from a meta tag or a cookie. */
export function getCsrf() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

/** Fetch JSON with CSRF header. */
export async function apiFetch(url, options = {}) {
  const csrf = getCsrf();
  const opts = {
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrf,
      ...(options.headers || {}),
    },
    ...options,
  };
  const res = await fetch(url, opts);
  const data = await res.json();
  return { ok: res.ok, status: res.status, data };
}

/** Make control characters visible in strings. */
export function visibleCtrl(str) {
  return String(str)
    .replace(/\n/g, '<span class="ctrl-char">↵</span>')
    .replace(/\r/g, '<span class="ctrl-char">↵</span>')
    .replace(/\t/g, '<span class="ctrl-char">→</span>');
}

/** Download a string as a file. */
export function downloadFile(content, filename, mimeType = 'text/plain') {
  const blob = new Blob([content], { type: mimeType });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = filename;
  document.body.appendChild(a);
  a.click();
  setTimeout(() => { URL.revokeObjectURL(url); a.remove(); }, 100);
}

/** Count-up animation. Respects prefers-reduced-motion. */
export function countUp(element, target, duration = 1200) {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    element.textContent = fmtNum(target);
    return;
  }
  const start = 0;
  const step = (timestamp) => {
    if (!countUp._startTs) countUp._startTs = timestamp;
    const progress = Math.min((timestamp - countUp._startTs) / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
    element.textContent = fmtNum(Math.floor(eased * target));
    if (progress < 1) requestAnimationFrame(step);
    else countUp._startTs = null;
  };
  countUp._startTs = null;
  requestAnimationFrame(step);
}

/** Observe element for intersection (lazy init). */
export function onVisible(element, callback) {
  if ('IntersectionObserver' in window) {
    const obs = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting) { callback(); obs.disconnect(); }
    }, { threshold: 0.1 });
    obs.observe(element);
  } else {
    callback();
  }
}
