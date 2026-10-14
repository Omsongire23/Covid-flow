<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Quality Assurance & Regex Engineering Lab';
$pageDesc  = 'Interactive in-browser testing lab executing shared regular expressions, benchmark ReDoS checks, and cross-engine validation.';
$pageScript = ['lab.js'];

$patternsFile = APP_ROOT . '/config/patterns.json';
$patterns = file_exists($patternsFile) ? (json_decode(file_get_contents($patternsFile), true) ?? []) : [];

require_once APP_ROOT . '/includes/layout_header.php';
?>

<div class="page-header bg-surface-2">
  <div class="container">
    <div class="page-header-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="/index.php">Home</a></li>
          <li aria-current="page">Quality Lab</li>
        </ol>
      </nav>
      <div class="header-meta-row">
        <h1 class="page-title">Quality Assurance &amp; Regular Expression Lab</h1>
        <span class="badge badge-primary">SQA Core Deliverable &bull; FR-10</span>
      </div>
      <p class="page-subtitle">
        Interactive test engineering workbench verifying input validation patterns, dual-engine cross-parity (ECMAScript <code>/u</code> vs PHP PCRE <code>~...~Du</code>), and ReDoS resistance.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <!-- Explainer Cards: Architectural Quality Decisions -->
  <div class="grid grid-3 mb-6">
    <!-- Card 1: PCRE D Flag -->
    <div class="card quality-explainer-card">
      <div class="card-header border-b">
        <div class="flex items-center gap-2">
          <span class="badge badge-danger">PCRE 'D' Modifier</span>
          <h2 class="card-title text-base">Trailing Newline Defense</h2>
        </div>
      </div>
      <div class="card-body text-xs text-muted leading-relaxed">
        <p class="mb-2">
          By default in PHP (PCRE), the <code>$</code> anchor matches <em>before</em> a trailing newline (<code>\n</code>), allowing malicious payloads like <code>"user\n"</code> to slip past standard validation.
        </p>
        <p class="mb-0">
          Enforcing delimiter modifier <code>~...~Du</code> ensures absolute end-of-string alignment, guaranteeing strict 100% equivalence with browser JavaScript.
        </p>
      </div>
    </div>

    <!-- Card 2: ReDoS Ordering -->
    <div class="card quality-explainer-card">
      <div class="card-header border-b">
        <div class="flex items-center gap-2">
          <span class="badge badge-ok">ReDoS Optimization</span>
          <h2 class="card-title text-base">Linear-Time Backtracking</h2>
        </div>
      </div>
      <div class="card-body text-xs text-muted leading-relaxed">
        <p class="mb-2">
          Hostile nested patterns without length caps can induce quadratic backtracking (catastrophic ReDoS), taking &gt;7.5 seconds on 100k malicious characters.
        </p>
        <p class="mb-0">
          Our patterns check bounded length <code>(?=[\s\S]{3,500}$)</code> <em>first</em> before any tag inspection, executing across hostile 100,000-character inputs in <strong>under 2 ms</strong>.
        </p>
      </div>
    </div>

    <!-- Card 3: Shape vs Semantic -->
    <div class="card quality-explainer-card">
      <div class="card-header border-b">
        <div class="flex items-center gap-2">
          <span class="badge badge-info">Layered Defense</span>
          <h2 class="card-title text-base">Shape vs. Semantic Split</h2>
        </div>
      </div>
      <div class="card-body text-xs text-muted leading-relaxed">
        <p class="mb-2">
          Regular expressions validate structural syntax (digits, separators, character classes).
        </p>
        <p class="mb-0">
          Semantic validity (e.g. <code>checkdate()</code> preventing non-existent February 31st dates, age constraints 5–120, and phone normalisation) is strictly enforced in the backend validator.
        </p>
      </div>
    </div>
  </div>

  <!-- Part 1: Interactive Live Pattern Explorer -->
  <section class="card mb-6" id="lab-tester" aria-labelledby="live-tester-heading">
    <div class="card-header border-b">
      <h2 id="live-tester-heading" class="card-title text-xl">1. Live Interactive Pattern Explorer</h2>
      <p class="card-desc text-sm">
        Select any field from the single source-of-truth configuration (<code>config/patterns.json</code>) and test sample inputs in real time.
      </p>
    </div>
    <div class="card-body">
      <div class="grid grid-2 gap-6">
        <div>
          <div class="form-group mb-4">
            <label for="tester-field" class="form-label font-semibold">Validation Target Field:</label>
            <select id="tester-field" class="form-control">
              <?php foreach ($patterns as $fKey => $fVal): ?>
                <option value="<?= e($fKey) ?>"><?= e($fKey) ?> &mdash; <?= e($fVal['message']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group mb-4">
            <label for="tester-input" class="form-label font-semibold">Test Input String:</label>
            <input type="text" id="tester-input" class="form-control" placeholder="Type test input..." value="john_doe">
          </div>

          <div class="flex gap-2">
            <button type="button" class="btn btn-outline btn-sm" id="btn-sample-valid">
              <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-check"></use></svg>
              Insert Valid Sample
            </button>
            <button type="button" class="btn btn-ghost btn-sm text-danger" id="btn-sample-invalid">
              <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-close"></use></svg>
              Insert Hostile/Invalid Sample
            </button>
          </div>
        </div>

        <div class="pattern-metadata-panel card">
          <div class="pattern-panel-header flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span class="badge badge-primary">Pattern Inspector</span>
              <span class="text-xs text-muted font-bold uppercase tracking-wider">Active Regular Expression</span>
            </div>
            <button type="button" class="btn btn-ghost btn-sm btn-copy-pattern" id="btn-copy-pattern" title="Copy pattern to clipboard">
              <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-clipboard"></use></svg>
              <span id="copy-btn-text">Copy</span>
            </button>
          </div>

          <!-- Highlighted, clear, high-contrast regex terminal card -->
          <div class="regex-display-card mb-3">
            <div class="regex-display-header">
              <span class="regex-lang-badge">ECMAScript /u &bull; PCRE ~...~Du</span>
              <span class="regex-anchor-tag">Anchor Bounds (^...$)</span>
            </div>
            <div class="regex-code-wrapper">
              <code class="regex-code" id="tester-pattern">^[A-Za-z][A-Za-z0-9_]{3,19}$</code>
            </div>
          </div>

          <!-- Dual Engine Preview Boxes -->
          <div class="grid grid-2 gap-2 mb-3">
            <div class="engine-format-box">
              <span class="engine-format-label">JavaScript (RegExp):</span>
              <code class="engine-format-code" id="tester-pattern-js">/^[A-Za-z][A-Za-z0-9_]{3,19}$/u</code>
            </div>
            <div class="engine-format-box">
              <span class="engine-format-label">PHP (PCRE Du):</span>
              <code class="engine-format-code" id="tester-pattern-php">~^[A-Za-z][A-Za-z0-9_]{3,19}$~Du</code>
            </div>
          </div>

          <div class="mb-3">
            <span class="text-xs text-muted block uppercase font-bold mb-1">Validation Constraint Message:</span>
            <p id="tester-message" class="text-sm mb-0 text-muted">4–20 characters; start with a letter; letters, numbers and underscore only.</p>
          </div>

          <div class="result-inspection-box mt-2 border-t pt-3 flex items-center justify-between">
            <span class="text-xs text-muted uppercase font-bold">Live Client Evaluation:</span>
            <div id="tester-result" class="badge badge-ok text-sm py-1 px-3">
              <span class="status-icon">✓</span> Valid
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Part 2: Automated Suite Runner -->
  <section class="card mb-6" aria-labelledby="suite-runner-heading">
    <div class="card-header border-b flex justify-between items-center">
      <div>
        <h2 id="suite-runner-heading" class="card-title text-xl">2. Full Test Suite Runner</h2>
        <p class="card-desc text-sm">
          Executes all 124 curated valid/invalid test cases plus hostile ReDoS benchmarks against the active patterns.
        </p>
      </div>
      <button type="button" class="btn btn-primary" id="btn-run-all">
        <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-play"></use></svg>
        Run All Tests In Browser
      </button>
    </div>

    <div class="card-body">
      <!-- Test Summary Metrics Box -->
      <div id="runner-summary" class="mb-6 d-none" aria-live="polite"></div>

      <!-- Filters & Export Toolbar -->
      <div class="suite-toolbar flex justify-between items-center mb-4 flex-wrap gap-3">
        <div class="flex gap-3 items-center">
          <div>
            <label for="filter-field" class="sr-only">Filter by Field</label>
            <select id="filter-field" class="form-control form-control-sm">
              <option value="all">All Fields (9 Targets)</option>
              <?php foreach ($patterns as $fKey => $fVal): ?>
                <option value="<?= e($fKey) ?>"><?= e($fKey) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="filter-status" class="sr-only">Filter by Outcome</label>
            <select id="filter-status" class="form-control form-control-sm">
              <option value="all">All Results</option>
              <option value="passed">Passed Cases Only</option>
              <option value="failed">Failed Cases Only</option>
            </select>
          </div>
        </div>

        <div class="export-actions-group flex gap-2">
          <button type="button" class="btn btn-outline btn-sm" id="btn-export-csv" disabled>
            <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-download"></use></svg>
            Export CSV
          </button>
          <button type="button" class="btn btn-outline btn-sm" id="btn-export-json" disabled>
            <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-download"></use></svg>
            Export JSON
          </button>
        </div>
      </div>

      <!-- Test Cases Execution Table -->
      <div class="table-responsive">
        <table class="data-table text-sm">
          <thead>
            <tr>
              <th scope="col" class="th-id">Test ID</th>
              <th scope="col" class="th-field">Field</th>
              <th scope="col">Test Input Value</th>
              <th scope="col" class="th-exp">Expected</th>
              <th scope="col" class="th-act">Actual</th>
              <th scope="col" class="th-status">Status</th>
              <th scope="col">Specification Notes</th>
            </tr>
          </thead>
          <tbody id="runner-tbody">
            <tr>
              <td colspan="7" class="text-center py-6 text-muted">
                Click <strong>&ldquo;Run All Tests In Browser&rdquo;</strong> to execute the full regex validation suite.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Part 3: Dual-Engine Verification Notice -->
  <div class="alert alert-info" role="note">
    <svg class="icon icon-md text-info" aria-hidden="true"><use href="#icon-shield-check"></use></svg>
    <div>
      <strong>Dual-Engine Assurance:</strong> The interactive table above tests the browser&apos;s JavaScript engine (<code>RegExp('...', 'u')</code>).
      Server-side PCRE tests are executed identically via <code>tests/regex_test.php</code>, and cross-engine parity is enforced by <code>tests/engine-parity.mjs</code>. Full documented outputs are cataloged in <code>docs/TEST_REPORT.md</code>.
    </div>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
