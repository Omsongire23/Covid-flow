/**
 * lab.js — Quality Lab client runner and interactive pattern explorer.
 */
import { showToast, escHtml } from './utils.js';

let patterns = null;
let testCases = null;
let testResults = [];

function expandInput(item) {
  if (typeof item.in === 'string') {
    return item.in;
  }
  if (item.gen) {
    const p = item.gen.prefix || '';
    const r = item.gen.repeat || '';
    const t = Number(item.gen.times) || 0;
    const s = item.gen.suffix || '';
    return p + r.repeat(t) + s;
  }
  return '';
}

function escapeInvisible(str) {
  if (typeof str !== 'string') return '';
  return str
    .replace(/\\/g, '\\\\')
    .replace(/\n/g, '\\n')
    .replace(/\r/g, '\\r')
    .replace(/\t/g, '\\t');
}

export async function initLab() {
  const testerSection = document.getElementById('lab-tester');
  if (!testerSection) return;

  try {
    const [patRes, casesRes] = await Promise.all([
      fetch('/api/patterns.php'),
      fetch('/api/regex-cases.php')
    ]);
    patterns = await patRes.json();
    testCases = await casesRes.json();
  } catch (err) {
    showToast('Failed to load validation patterns or test cases', 'danger');
    return;
  }

  setupLiveTester();
  setupSuiteRunner();
}

function setupLiveTester() {
  const select = document.getElementById('tester-field');
  const input = document.getElementById('tester-input');
  const patternDisplay = document.getElementById('tester-pattern');
  const patternJsDisplay = document.getElementById('tester-pattern-js');
  const patternPhpDisplay = document.getElementById('tester-pattern-php');
  const messageDisplay = document.getElementById('tester-message');
  const resultBadge = document.getElementById('tester-result');
  const btnValid = document.getElementById('btn-sample-valid');
  const btnInvalid = document.getElementById('btn-sample-invalid');
  const btnCopy = document.getElementById('btn-copy-pattern');
  const copyText = document.getElementById('copy-btn-text');

  if (!select || !input) return;

  function updateFieldInfo() {
    const fieldKey = select.value;
    const info = patterns[fieldKey];
    if (!info) return;

    if (patternDisplay) patternDisplay.textContent = info.pattern;
    if (patternJsDisplay) patternJsDisplay.textContent = `/${info.pattern}/u`;
    if (patternPhpDisplay) patternPhpDisplay.textContent = `~${info.pattern}~Du`;
    if (messageDisplay) messageDisplay.textContent = info.message;
    runLiveCheck();
  }

  function runLiveCheck() {
    const fieldKey = select.value;
    const info = patterns[fieldKey];
    if (!info) return;

    const val = input.value;
    let regex;
    try {
      regex = new RegExp(info.pattern, 'u');
    } catch (e) {
      resultBadge.className = 'badge badge-danger';
      resultBadge.textContent = 'Regex Compile Error';
      return;
    }

    const passed = regex.test(val);
    resultBadge.className = passed ? 'badge badge-ok' : 'badge badge-danger';
    resultBadge.innerHTML = passed
      ? '<span class="status-icon">✓</span> Valid'
      : '<span class="status-icon">✕</span> Invalid';
  }

  select.addEventListener('change', updateFieldInfo);
  input.addEventListener('input', runLiveCheck);

  if (btnCopy) {
    btnCopy.addEventListener('click', async () => {
      const fieldKey = select.value;
      const info = patterns[fieldKey];
      if (!info) return;

      try {
        await navigator.clipboard.writeText(info.pattern);
        if (copyText) copyText.textContent = 'Copied!';
        btnCopy.classList.add('copied');
        showToast(`Pattern for ${fieldKey} copied to clipboard!`, 'ok');
        setTimeout(() => {
          if (copyText) copyText.textContent = 'Copy';
          btnCopy.classList.remove('copied');
        }, 1800);
      } catch (err) {
        showToast(`Pattern: ${info.pattern}`, 'info');
      }
    });
  }

  if (btnValid) {
    btnValid.addEventListener('click', () => {
      const fieldKey = select.value;
      const cases = testCases[fieldKey] || [];
      const validCase = cases.find(c => c.ok === true);
      if (validCase) {
        input.value = expandInput(validCase);
        runLiveCheck();
      }
    });
  }

  if (btnInvalid) {
    btnInvalid.addEventListener('click', () => {
      const fieldKey = select.value;
      const cases = testCases[fieldKey] || [];
      const invalidCase = cases.find(c => c.ok === false);
      if (invalidCase) {
        input.value = expandInput(invalidCase);
        runLiveCheck();
      }
    });
  }

  updateFieldInfo();
}

function setupSuiteRunner() {
  const runBtn = document.getElementById('btn-run-all');
  const summaryBox = document.getElementById('runner-summary');
  const tableBody = document.getElementById('runner-tbody');
  const filterSelect = document.getElementById('filter-field');
  const statusFilter = document.getElementById('filter-status');
  const btnCsv = document.getElementById('btn-export-csv');
  const btnJson = document.getElementById('btn-export-json');

  if (!runBtn || !tableBody) return;

  function renderTable() {
    const fieldVal = filterSelect ? filterSelect.value : 'all';
    const statusVal = statusFilter ? statusFilter.value : 'all';

    const filtered = testResults.filter(r => {
      if (fieldVal !== 'all' && r.field !== fieldVal) return false;
      if (statusVal === 'passed' && !r.passed) return false;
      if (statusVal === 'failed' && r.passed) return false;
      return true;
    });

    tableBody.innerHTML = filtered.map(r => `
      <tr class="${r.passed ? 'row-pass' : 'row-fail'}">
        <td><code>${escHtml(r.id)}</code></td>
        <td><strong>${escHtml(r.field)}</strong></td>
        <td><code class="input-preview">${escHtml(escapeInvisible(r.input))}</code></td>
        <td><span class="badge ${r.expected ? 'badge-ok' : 'badge-neutral'}">${r.expected ? 'VALID' : 'INVALID'}</span></td>
        <td><span class="badge ${r.actual ? 'badge-ok' : 'badge-neutral'}">${r.actual ? 'VALID' : 'INVALID'}</span></td>
        <td>
          <span class="badge ${r.passed ? 'badge-ok' : 'badge-danger'}">
            ${r.passed ? 'PASS' : 'FAIL'}
          </span>
        </td>
        <td><small class="text-muted">${escHtml(r.note || '')}</small></td>
      </tr>
    `).join('');
  }

  runBtn.addEventListener('click', () => {
    runBtn.disabled = true;
    runBtn.textContent = 'Running Tests...';
    testResults = [];

    const startTime = performance.now();
    let total = 0;
    let passed = 0;
    let failed = 0;

    for (const [field, cases] of Object.entries(testCases)) {
      const patObj = patterns[field];
      if (!patObj) continue;
      const regex = new RegExp(patObj.pattern, 'u');

      cases.forEach((c, idx) => {
        total++;
        const numStr = String(idx + 1).padStart(2, '0');
        const id = `TC-REG-${field.toUpperCase()}-${numStr}`;
        const inputStr = expandInput(c);
        const actual = regex.test(inputStr);
        const isPass = actual === c.ok;

        if (isPass) passed++; else failed++;

        testResults.push({
          id,
          field,
          input: inputStr,
          expected: c.ok,
          actual,
          passed: isPass,
          note: c.note || ''
        });
      });
    }

    // ReDoS hostile performance assertions
    const hostileChecks = [
      { field: 'comment', in: '<'.repeat(100000), note: 'ReDoS 100k < characters' },
      { field: 'email', in: 'a.'.repeat(50000), note: 'ReDoS 100k hostile dots' },
      { field: 'username', in: 'A'.repeat(100000), note: 'ReDoS 100k letters' }
    ];

    hostileChecks.forEach((h, hIdx) => {
      total++;
      const id = `TC-REG-REDOS-${h.field.toUpperCase()}-${hIdx + 1}`;
      const patObj = patterns[h.field];
      const regex = new RegExp(patObj.pattern, 'u');
      const t0 = performance.now();
      const actual = regex.test(h.in);
      const duration = performance.now() - t0;
      const isPass = !actual && duration < 200;
      if (isPass) passed++; else failed++;
      testResults.push({
        id,
        field: h.field,
        input: `[Hostile string: len=${h.in.length}]`,
        expected: false,
        actual,
        passed: isPass,
        note: `${h.note} (${duration.toFixed(2)}ms, threshold <200ms)`
      });
    });

    const elapsed = (performance.now() - startTime).toFixed(1);

    if (summaryBox) {
      summaryBox.classList.remove('d-none');
      summaryBox.innerHTML = `
        <div class="test-summary-grid">
          <div class="summary-card stat-total">
            <span class="stat-num">${total}</span>
            <span class="stat-label">Total Executed</span>
          </div>
          <div class="summary-card stat-pass">
            <span class="stat-num">${passed}</span>
            <span class="stat-label">Passed</span>
          </div>
          <div class="summary-card stat-fail ${failed > 0 ? 'has-failures' : ''}">
            <span class="stat-num">${failed}</span>
            <span class="stat-label">Failed</span>
          </div>
          <div class="summary-card stat-time">
            <span class="stat-num">${elapsed}ms</span>
            <span class="stat-label">Runtime</span>
          </div>
        </div>
      `;
    }

    renderTable();

    if (btnCsv) btnCsv.disabled = false;
    if (btnJson) btnJson.disabled = false;
    runBtn.disabled = false;
    runBtn.textContent = 'Re-Run All Tests';

    showToast(`Executed ${total} test cases: ${passed} passed, ${failed} failed in ${elapsed}ms`, failed === 0 ? 'success' : 'warn');
  });

  if (filterSelect) filterSelect.addEventListener('change', renderTable);
  if (statusFilter) statusFilter.addEventListener('change', renderTable);

  if (btnCsv) {
    btnCsv.addEventListener('click', () => {
      if (testResults.length === 0) return;
      const rows = [
        ['ID', 'Field', 'Input (Escaped)', 'Expected', 'Actual', 'Status', 'Note']
      ];
      testResults.forEach(r => {
        rows.push([
          r.id,
          r.field,
          escapeInvisible(r.input),
          r.expected ? 'VALID' : 'INVALID',
          r.actual ? 'VALID' : 'INVALID',
          r.passed ? 'PASS' : 'FAIL',
          r.note || ''
        ]);
      });
      const csvContent = 'data:text/csv;charset=utf-8,' + rows.map(e => e.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement('a');
      link.setAttribute('href', encodedUri);
      link.setAttribute('download', `regex-test-results-${new Date().toISOString().slice(0, 10)}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });
  }

  if (btnJson) {
    btnJson.addEventListener('click', () => {
      if (testResults.length === 0) return;
      const jsonContent = 'data:application/json;charset=utf-8,' + encodeURIComponent(JSON.stringify({
        suite: 'COVID-19 Regex Quality Lab Test Suite',
        engine: 'JavaScript (ECMAScript RegExp /u)',
        executedAt: new Date().toISOString(),
        summary: {
          total: testResults.length,
          passed: testResults.filter(r => r.passed).length,
          failed: testResults.filter(r => !r.passed).length
        },
        results: testResults
      }, null, 2));
      const link = document.createElement('a');
      link.setAttribute('href', jsonContent);
      link.setAttribute('download', `regex-test-results-${new Date().toISOString().slice(0, 10)}.json`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });
  }
}

document.addEventListener('DOMContentLoaded', initLab);
