/**
 * app.js — Main app entry point: nav, theme, back-to-top, accordions, toasts.
 */
import { showToast } from './utils.js';

// ── Theme toggle ─────────────────────────────────────────────────────────────
function initTheme() {
  const toggle = document.getElementById('themeToggleBtn') || document.getElementById('theme-toggle');
  if (!toggle) return;

  function updateIcon() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const isDarkNow = isDark;
    toggle.setAttribute('aria-label', isDarkNow ? 'Switch to light theme' : 'Switch to dark theme');
  }

  toggle.addEventListener('click', () => {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const next   = isDark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('theme', next); } catch {}
    updateIcon();
  });

  updateIcon();
}

// ── Sticky nav shadow ─────────────────────────────────────────────────────────
function initNav() {
  const header = document.querySelector('.site-header') || document.querySelector('.site-nav');
  if (!header) return;
  function onScroll() {
    header.classList.toggle('scrolled', window.scrollY > 8);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

// ── Hamburger / mobile nav ────────────────────────────────────────────────────
function initHamburger() {
  const btn     = document.getElementById('hamburgerBtn') || document.getElementById('nav-hamburger');
  const drawer  = document.getElementById('mobileDrawer') || document.getElementById('nav-mobile-overlay');
  const closeBtn= document.getElementById('drawerCloseBtn');
  if (!btn || !drawer) return;

  function close() {
    btn.setAttribute('aria-expanded', 'false');
    drawer.hidden = true;
    drawer.classList.remove('open');
    document.body.style.overflow = '';
  }
  function open() {
    btn.setAttribute('aria-expanded', 'true');
    drawer.hidden = false;
    drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  btn.addEventListener('click', () => {
    if (btn.getAttribute('aria-expanded') === 'true') close(); else open();
  });
  if (closeBtn) closeBtn.addEventListener('click', close);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', close));
}

// ── Desktop dropdowns ─────────────────────────────────────────────────────────
function initDropdowns() {
  document.querySelectorAll('.dropdown, .nav-dropdown, .user-menu').forEach(dd => {
    const toggle = dd.querySelector('.dropdown-toggle, .nav-dropdown-toggle, .user-menu-toggle');
    if (!toggle) return;
    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = dd.classList.contains('open');
      document.querySelectorAll('.dropdown.open, .nav-dropdown.open, .user-menu.open').forEach(d => {
        d.classList.remove('open');
        d.querySelector('.dropdown-toggle, .nav-dropdown-toggle, .user-menu-toggle')?.setAttribute('aria-expanded', 'false');
      });
      if (!open) {
        dd.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
      }
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown.open, .nav-dropdown.open, .user-menu.open').forEach(el => {
      el.classList.remove('open');
      el.querySelector('.dropdown-toggle, .nav-dropdown-toggle, .user-menu-toggle')?.setAttribute('aria-expanded', 'false');
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.dropdown.open, .nav-dropdown.open, .user-menu.open').forEach(el => {
        el.classList.remove('open');
        el.querySelector('.dropdown-toggle, .nav-dropdown-toggle, .user-menu-toggle')?.setAttribute('aria-expanded', 'false');
      });
    }
  });
}

// ── Back to top ──────────────────────────────────────────────────────────────
function initBackToTop() {
  const btn = document.getElementById('backToTopBtn') || document.getElementById('back-to-top');
  if (!btn) return;
  window.addEventListener('scroll', () => {
    const shouldShow = window.scrollY > 400;
    btn.hidden = !shouldShow;
    btn.classList.toggle('visible', shouldShow);
  }, { passive: true });
  btn.addEventListener('click', () => { window.scrollTo({ top: 0, behavior: 'smooth' }); });
}

// ── Accordions ──────────────────────────────────────────────────────────────
export function initAccordions(root = document) {
  root.querySelectorAll('.accordion-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const panel   = document.getElementById(btn.getAttribute('aria-controls'));
      const isOpen  = btn.getAttribute('aria-expanded') === 'true';
      // Optionally close siblings
      const accordion = btn.closest('.accordion');
      if (accordion) {
        accordion.querySelectorAll('.accordion-btn').forEach(b => {
          if (b !== btn) {
            b.setAttribute('aria-expanded', 'false');
            const p = document.getElementById(b.getAttribute('aria-controls'));
            if (p) p.classList.remove('open');
          }
        });
      }
      btn.setAttribute('aria-expanded', String(!isOpen));
      if (panel) panel.classList.toggle('open', !isOpen);
    });
  });
  // Expand all button
  document.querySelectorAll('[data-accordion-expand-all]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.querySelector(btn.dataset.accordionExpandAll);
      if (!target) return;
      const allOpen = [...target.querySelectorAll('.accordion-btn')].every(b => b.getAttribute('aria-expanded') === 'true');
      target.querySelectorAll('.accordion-btn').forEach(b => {
        b.setAttribute('aria-expanded', String(!allOpen));
        const p = document.getElementById(b.getAttribute('aria-controls'));
        if (p) p.classList.toggle('open', !allOpen);
      });
      btn.textContent = allOpen ? 'Expand all' : 'Collapse all';
    });
  });
}

// ── Flash messages (PHP-set, shown via session) ──────────────────────────────
function initFlash() {
  document.querySelectorAll('[data-flash]').forEach(el => {
    const type = el.dataset.flashType || 'info';
    showToast(el.textContent.trim(), type);
    el.remove();
  });
}

// ── Tabs ─────────────────────────────────────────────────────────────────────
export function initTabs(root = document) {
  root.querySelectorAll('[role="tablist"]').forEach(tablist => {
    const tabs   = [...tablist.querySelectorAll('[role="tab"]')];
    const panels = tabs.map(t => document.getElementById(t.getAttribute('aria-controls')));

    tabs.forEach((tab, i) => {
      tab.addEventListener('click', () => activate(i));
      tab.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight') { e.preventDefault(); activate((i + 1) % tabs.length); tabs[(i + 1) % tabs.length].focus(); }
        if (e.key === 'ArrowLeft')  { e.preventDefault(); activate((i - 1 + tabs.length) % tabs.length); tabs[(i - 1 + tabs.length) % tabs.length].focus(); }
      });
    });

    function activate(idx) {
      tabs.forEach((t, i) => {
        t.setAttribute('aria-selected', String(i === idx));
        t.classList.toggle('active', i === idx);
        if (panels[i]) panels[i].classList.toggle('active', i === idx);
      });
    }
    activate(0);
  });
}

// ── Myth filters ────────────────────────────────────────────────────────────
function initMythFilters() {
  const chips = document.querySelectorAll('[data-myth-filter]');
  const cards = document.querySelectorAll('.myth-card');
  if (chips.length === 0 || cards.length === 0) return;

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      const filter = chip.dataset.mythFilter;

      cards.forEach(card => {
        if (filter === 'all' || card.dataset.category === filter) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
}

// ── Global declarative actions (CSP-safe, no inline onclick) ────────────────
function initActions() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const action = btn.dataset.action;
    if (action === 'print') {
      window.print();
    } else if (action === 'reload') {
      window.location.reload();
    }
  });
}

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initNav();
  initHamburger();
  initDropdowns();
  initBackToTop();
  initAccordions();
  initFlash();
  initTabs();
  initMythFilters();
  initActions();
});

