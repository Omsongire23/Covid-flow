/**
 * charts.js — Statistics page: trend chart + country table.
 */
import { fmtNum, debounce, showToast } from './utils.js';

let chart       = null;
let allCountries= [];
let currentRange= '30';
let currentMetric='cases';

// ── Trend chart ───────────────────────────────────────────────────────────────
async function initChart() {
  const canvas = document.getElementById('trend-chart');
  if (!canvas) return;

  await loadChartData(currentRange, currentMetric);

  // Range buttons
  document.querySelectorAll('[data-range]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-range]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentRange = btn.dataset.range;
      loadChartData(currentRange, currentMetric);
    });
  });

  // Metric buttons
  document.querySelectorAll('[data-metric]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-metric]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentMetric = btn.dataset.metric;
      loadChartData(currentRange, currentMetric);
    });
  });
}

async function loadChartData(days, metric) {
  const canvas = document.getElementById('trend-chart');
  if (!canvas) return;

  try {
    const res  = await fetch(`/api/stats.php?scope=history&days=${days}`);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error?.message);

    const seriesRaw = json.data[metric] ?? {};
    const entries   = Object.entries(seriesRaw).sort((a, b) => new Date(a[0]) - new Date(b[0]));
    const labels    = entries.map(([k]) => k);
    const values    = entries.map(([, v]) => v);

    renderChart(canvas, labels, values, metric);
    renderChartSummary(labels, values, metric);

  } catch (err) {
    canvas.parentElement.innerHTML = `<div class="alert alert-warning">⚠️ ${err.message}</div>`;
  }
}

const metricColors = {
  cases:     { border: '#2563eb', bg: 'rgba(37,99,235,.08)' },
  deaths:    { border: '#b91c1c', bg: 'rgba(185,28,28,.08)' },
  recovered: { border: '#15803d', bg: 'rgba(21,128,61,.08)' },
};

function renderChart(canvas, labels, values, metric) {
  const color = metricColors[metric] ?? metricColors.cases;
  const Chart = window.Chart;
  if (!Chart) { console.warn('Chart.js not loaded'); return; }

  if (chart) chart.destroy();

  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
  const textColor = isDark ? '#9aa8c4' : '#52637a';

  chart = new Chart(canvas.getContext('2d'), {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: metric.charAt(0).toUpperCase() + metric.slice(1),
        data: values,
        borderColor: color.border,
        backgroundColor: color.bg,
        fill: true,
        tension: 0.3,
        pointRadius: 0,
        pointHitRadius: 12,
        borderWidth: 2,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => `  ${ctx.dataset.label}: ${fmtNum(ctx.raw)}`,
          }
        }
      },
      scales: {
        x: {
          grid: { color: gridColor },
          ticks: {
            color: textColor,
            maxTicksLimit: 8,
            maxRotation: 0,
          },
        },
        y: {
          grid: { color: gridColor },
          ticks: {
            color: textColor,
            callback: (v) => {
              if (v >= 1e9) return (v / 1e9).toFixed(1) + 'B';
              if (v >= 1e6) return (v / 1e6).toFixed(1) + 'M';
              if (v >= 1e3) return (v / 1e3).toFixed(0) + 'K';
              return v;
            }
          }
        }
      }
    }
  });
}

function renderChartSummary(labels, values, metric) {
  const el = document.getElementById('chart-summary');
  if (!el || values.length < 2) return;
  const latest = values[values.length - 1];
  const first  = values[0];
  const change = latest - first;
  const pct    = first ? ((change / first) * 100).toFixed(1) : 'N/A';
  const dir    = change >= 0 ? '▲' : '▼';
  el.textContent = `Latest (${labels[labels.length - 1]}): ${fmtNum(latest)} ${metric}. ` +
    `Change over period: ${dir} ${fmtNum(Math.abs(change))} (${pct}%).`;
}

// ── Country table ─────────────────────────────────────────────────────────────
async function initCountryTable() {
  const tbody = document.getElementById('countries-tbody');
  if (!tbody) return;

  try {
    const res  = await fetch('/api/stats.php?scope=countries');
    const json = await res.json();
    if (!json.ok) throw new Error(json.error?.message);
    allCountries = json.data ?? [];
    renderTable();
  } catch (err) {
    document.getElementById('countries-wrapper')?.insertAdjacentHTML('afterbegin',
      `<div class="alert alert-warning">⚠️ ${err.message}</div>`);
  }

  // Search
  const searchInput = document.getElementById('country-search');
  if (searchInput) {
    searchInput.addEventListener('input', debounce(() => {
      currentPage = 1; renderTable();
    }, 250));
  }

  // Continent filter
  const contFilter = document.getElementById('continent-filter');
  if (contFilter) {
    contFilter.addEventListener('change', () => { currentPage = 1; renderTable(); });
  }

  // Per-page
  const perPageSel = document.getElementById('per-page');
  if (perPageSel) {
    perPageSel.addEventListener('change', () => { currentPage = 1; renderTable(); });
  }

  // Sort by clicking headers
  document.querySelectorAll('[data-sort]').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.sort;
      if (sortCol === col) sortDir = sortDir === 'desc' ? 'asc' : 'desc';
      else { sortCol = col; sortDir = 'desc'; }
      currentPage = 1;
      renderTable();
    });
  });
}

let currentPage = 1;
let sortCol     = 'cases';
let sortDir     = 'desc';

function renderTable() {
  const tbody     = document.getElementById('countries-tbody');
  const searchVal = document.getElementById('country-search')?.value.trim().toLowerCase() ?? '';
  const continent = document.getElementById('continent-filter')?.value ?? '';
  const perPage   = parseInt(document.getElementById('per-page')?.value ?? '20');

  // Filter
  let rows = allCountries.filter(c => {
    const matchQ = !searchVal || c.country.toLowerCase().includes(searchVal);
    const matchC = !continent || c.continent === continent;
    return matchQ && matchC;
  });

  // Sort
  rows.sort((a, b) => {
    const va = a[sortCol] ?? 0;
    const vb = b[sortCol] ?? 0;
    return sortDir === 'desc' ? vb - va : va - vb;
  });

  // Pagination
  const total  = rows.length;
  const pages  = Math.max(1, Math.ceil(total / perPage));
  currentPage  = Math.min(currentPage, pages);
  const start  = (currentPage - 1) * perPage;
  const paged  = rows.slice(start, start + perPage);

  // Update aria-sort
  document.querySelectorAll('[data-sort]').forEach(th => {
    th.removeAttribute('aria-sort');
    if (th.dataset.sort === sortCol) {
      th.setAttribute('aria-sort', sortDir === 'desc' ? 'descending' : 'ascending');
    }
  });

  // Render rows
  tbody.innerHTML = paged.map((c, i) => `
    <tr class="country-row" data-idx="${start + i}" aria-expanded="false">
      <td data-label="Country"><div class="country-cell"><span class="country-flag">${flagEmoji(c.iso2)}</span><span>${escHtml(c.country)}</span></div></td>
      <td data-label="Cases"     class="text-right">${fmtNum(c.cases)}</td>
      <td data-label="Deaths"    class="text-right text-danger">${fmtNum(c.deaths)}</td>
      <td data-label="Recovered" class="text-right text-ok">${c.recovered ? fmtNum(c.recovered) : '—'}</td>
      <td data-label="Active"    class="text-right">${fmtNum(c.active)}</td>
      <td data-label="Today Cases" class="text-right">${c.todayCases ? '+' + fmtNum(c.todayCases) : '—'}</td>
    </tr>
    <tr class="detail-row" id="detail-${start + i}" hidden>
      <td colspan="6" class="detail-row">
        <div class="detail-inner">
          <div class="detail-item"><label>Population</label><span>${fmtNum(c.population)}</span></div>
          <div class="detail-item"><label>Tests</label><span>${fmtNum(c.tests)}</span></div>
          <div class="detail-item"><label>Cases / 1M</label><span>${fmtNum(c.casesPerOneMillion)}</span></div>
          <div class="detail-item"><label>Deaths / 1M</label><span>${fmtNum(c.deathsPerOneMillion)}</span></div>
          <div class="detail-item"><label>Continent</label><span>${escHtml(c.continent || '—')}</span></div>
          <div class="detail-item"><label>Today Deaths</label><span>${c.todayDeaths ? '+' + fmtNum(c.todayDeaths) : '—'}</span></div>
        </div>
      </td>
    </tr>
  `).join('');

  // Row click expand
  tbody.querySelectorAll('.country-row').forEach(row => {
    row.addEventListener('click', () => {
      const idx    = row.dataset.idx;
      const detail = document.getElementById(`detail-${idx}`);
      const open   = row.getAttribute('aria-expanded') === 'true';
      row.setAttribute('aria-expanded', String(!open));
      if (detail) detail.hidden = open;
    });
  });

  // Pagination controls
  renderPagination(currentPage, pages);

  // Count
  const countEl = document.getElementById('country-count');
  if (countEl) countEl.textContent = `${total} countries`;
}

function renderPagination(page, pages) {
  const el = document.getElementById('country-pagination');
  if (!el) return;
  if (pages <= 1) { el.innerHTML = ''; return; }

  const buttons = [];
  buttons.push(`<button class="page-btn" ${page === 1 ? 'disabled' : ''} data-page="${page - 1}" aria-label="Previous page">‹</button>`);
  for (let p = Math.max(1, page - 2); p <= Math.min(pages, page + 2); p++) {
    buttons.push(`<button class="page-btn ${p === page ? 'active' : ''}" data-page="${p}" aria-label="Page ${p}" ${p === page ? 'aria-current="page"' : ''}>${p}</button>`);
  }
  buttons.push(`<button class="page-btn" ${page === pages ? 'disabled' : ''} data-page="${page + 1}" aria-label="Next page">›</button>`);

  el.innerHTML = buttons.join('');
  el.querySelectorAll('.page-btn:not(:disabled):not(.active)').forEach(btn => {
    btn.addEventListener('click', () => {
      currentPage = parseInt(btn.dataset.page);
      renderTable();
      document.getElementById('countries-wrapper')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
}

function flagEmoji(iso2) {
  if (!iso2 || iso2.length !== 2) return '🏳';
  const offset = 0x1F1E6 - 65;
  return String.fromCodePoint(iso2.charCodeAt(0) + offset, iso2.charCodeAt(1) + offset);
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.addEventListener('DOMContentLoaded', () => {
  initChart();
  initCountryTable();

  // Sync URL state
  const params = new URLSearchParams(location.search);
  const q = params.get('q');
  if (q) {
    const si = document.getElementById('country-search');
    if (si) { si.value = q; }
  }
});
