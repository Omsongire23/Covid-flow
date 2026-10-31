/**
 * stats.js — Home page: stat card count-up, trend chart preview.
 */
import { onVisible, fmtNum, fmtDate, showToast } from './utils.js';

async function initStats() {
  const section = document.getElementById('stat-section');
  if (!section) return;

  // Render skeletons while loading
  showSkeletons(section);

  try {
    const res  = await fetch('/api/stats.php?scope=global');
    const json = await res.json();

    if (!json.ok) throw new Error(json.error?.message ?? 'API error');

    const d = json.data;
    renderStatCards(section, d);
    renderSourceBar(json);

    if (json.stale) {
      showToast(`Showing cached data last updated ${fmtDate(json.updatedAt)}`, 'warning');
    }
  } catch (err) {
    renderError(section, err.message);
  }
}

function showSkeletons(section) {
  section.querySelectorAll('.stat-card').forEach(c => {
    c.querySelector('.stat-card-value').innerHTML = '<div class="skeleton skeleton-val"></div>';
    c.querySelector('.stat-card-delta').innerHTML = '<div class="skeleton skeleton-delta"></div>';
  });
}

function renderStatCards(section, d) {
  const cards = [
    { key: 'cases',     todayKey: 'todayCases',  el: '#stat-cases'    },
    { key: 'deaths',    todayKey: 'todayDeaths', el: '#stat-deaths'   },
    { key: 'active',    todayKey: null,           el: '#stat-active'   },
    { key: 'recovered', todayKey: null,           el: '#stat-recovered'},
  ];

  cards.forEach(({ key, todayKey, el }) => {
    const card   = section.querySelector(el);
    if (!card) return;
    const valEl  = card.querySelector('.stat-card-value');
    const deltaEl= card.querySelector('.stat-card-delta');
    const val    = d[key] ?? 0;
    const today  = todayKey ? (d[todayKey] ?? 0) : null;

    // Count up
    onVisible(valEl, () => countUp(valEl, val));

    if (today !== null && deltaEl) {
      deltaEl.textContent = `+${fmtNum(today)} today`;
      deltaEl.className   = 'stat-card-delta' + (today > 0 ? ' up' : '');
    } else if (val === 0 && deltaEl) {
      deltaEl.textContent = 'not reported';
      deltaEl.className   = 'stat-card-delta';
    }

    // Tooltip
    card.setAttribute('title', `Exact figure: ${fmtNum(val)}`);
  });
}

function renderSourceBar(json) {
  const bar = document.getElementById('source-bar');
  if (!bar) return;
  const stale = json.stale ? ' — <strong class="text-warn">showing cached data</strong>' : '';
  bar.innerHTML = `Source: <strong>${json.source ?? 'disease.sh'}</strong> · Last updated: <strong>${fmtDate(json.updatedAt)}</strong>${stale}`;
}

function renderError(section, msg) {
  section.innerHTML = `<div class="alert alert-warning" role="alert">
    <span>⚠️</span>
    <div>Live statistics unavailable: ${msg}. Showing fallback data if available.</div>
  </div>`;
}

/** Simple count-up (standalone version for stats page). */
function countUp(el, target, duration = 1500) {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    el.textContent = fmtNum(target);
    return;
  }
  let start = null;
  const step = (ts) => {
    if (!start) start = ts;
    const p = Math.min((ts - start) / duration, 1);
    const e = 1 - Math.pow(1 - p, 3);
    el.textContent = fmtNum(Math.round(e * target));
    if (p < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

document.addEventListener('DOMContentLoaded', initStats);
