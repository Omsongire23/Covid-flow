<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Myths vs. Facts — COVID-19 Fact Checker';
$pageDesc  = 'Scientifically verified fact checks addressing dangerous COVID-19 misinformation and rumors.';

$contentFile = APP_ROOT . '/data/content.json';
$content = file_exists($contentFile) ? (json_decode(file_get_contents($contentFile), true) ?? []) : [];
$mythsList = $content['myths'] ?? [];
$lastReviewed = $content['lastReviewed'] ?? '2026-10-14';

require_once APP_ROOT . '/includes/layout_header.php';
?>

<div class="page-header bg-surface-2">
  <div class="container">
    <div class="page-header-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="/index.php">Home</a></li>
          <li><span>Learn</span></li>
          <li aria-current="page">Myths vs. Facts</li>
        </ol>
      </nav>
      <div class="header-meta-row">
        <h1 class="page-title">COVID-19 Myths vs. Scientific Facts</h1>
        <div class="review-date-badge">
          <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-calendar"></use></svg>
          <span>Last reviewed: <strong><?= e($lastReviewed) ?></strong></span>
        </div>
      </div>
      <p class="page-subtitle">
        Separate scientific reality from widespread internet rumors, unverified treatments, and conspiracy theories.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <!-- Filter Chips Toolbar -->
  <div class="filter-chips-bar mb-6" role="tablist" aria-label="Myth categories">
    <button type="button" class="chip active" data-myth-filter="all">All Topics (<?= count($mythsList) ?>)</button>
    <button type="button" class="chip" data-myth-filter="disease">Disease Severity</button>
    <button type="button" class="chip" data-myth-filter="vaccine">Vaccine Safety</button>
    <button type="button" class="chip" data-myth-filter="treatment">Treatments &amp; Antibiotics</button>
    <button type="button" class="chip" data-myth-filter="transmission">Transmission &amp; Climate</button>
    <button type="button" class="chip" data-myth-filter="diagnosis">Diagnosis Testing</button>
  </div>

  <!-- Myths Cards Grid -->
  <div class="myth-cards-container" id="myths-grid">
    <?php foreach ($mythsList as $m): ?>
      <?php $cat = e($m['category'] ?? 'general'); ?>
      <article class="myth-card card mb-4" data-category="<?= $cat ?>">
        <!-- Myth block -->
        <div class="myth-box">
          <div class="myth-badge-wrap">
            <span class="badge badge-danger">
              <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-close"></use></svg>
              MYTH
            </span>
          </div>
          <h2 class="myth-statement text-lg font-semibold text-danger">
            &ldquo;<?= e($m['myth']) ?>&rdquo;
          </h2>
        </div>

        <!-- Fact block -->
        <div class="fact-box">
          <div class="fact-badge-wrap">
            <span class="badge badge-ok">
              <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-check"></use></svg>
              VERIFIED FACT
            </span>
          </div>
          <p class="fact-explanation text-base">
            <?= e($m['fact']) ?>
          </p>

          <div class="source-citation-line mt-3">
            <span class="text-xs text-muted">Scientific Source:</span>
            <a href="<?= e($m['sourceUrl']) ?>" target="_blank" rel="noopener noreferrer" class="source-link text-xs">
              <?= e($m['source']) ?>
              <svg class="icon icon-xs inline-icon" aria-hidden="true"><use href="#icon-external-link"></use></svg>
            </a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Fact-Checking Methodology Note -->
  <div class="alert alert-info mt-6">
    <svg class="icon icon-md text-info" aria-hidden="true"><use href="#icon-info"></use></svg>
    <div>
      <strong>Verification Protocol:</strong> Every fact-check entry adheres to WHO infodemic management criteria. 
      Do not rely on unsolicited social media advice for medical treatment. Consult a licensed physician.
    </div>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
