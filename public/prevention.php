<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'COVID-19 Prevention Guidelines';
$pageDesc  = 'Evidence-based infection prevention guidelines, ventilation standards, and protective health measures from WHO and MoHFW.';

$contentFile = APP_ROOT . '/data/content.json';
$content = file_exists($contentFile) ? (json_decode(file_get_contents($contentFile), true) ?? []) : [];
$preventionList = $content['prevention'] ?? [];
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
          <li aria-current="page">Prevention</li>
        </ol>
      </nav>
      <div class="header-meta-row">
        <h1 class="page-title">Infection Prevention Guidelines</h1>
        <div class="review-date-badge">
          <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-calendar"></use></svg>
          <span>Last reviewed: <strong><?= e($lastReviewed) ?></strong></span>
        </div>
      </div>
      <p class="page-subtitle">
        Scientifically verified measures to protect yourself, your family, and vulnerable members of your community from respiratory viral transmission.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <!-- Print Checklist Toolbar -->
  <div class="action-bar-top mb-4">
    <div class="action-bar-left">
      <span class="text-sm text-muted">Clinical guidelines verified with WHO Infection Prevention &amp; Control guidance.</span>
    </div>
    <div class="action-bar-right">
      <button type="button" class="btn btn-outline btn-sm no-print" data-action="print">
        <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-print"></use></svg>
        Print Checklist
      </button>
      <button type="button" class="btn btn-ghost btn-sm" data-accordion-expand-all="#prevention-accordion">
        Expand all
      </button>
    </div>
  </div>

  <!-- Prevention Accordion / Cards List -->
  <div class="accordion" id="prevention-accordion">
    <?php foreach ($preventionList as $index => $item): ?>
      <?php 
        $itemId = 'prev-' . e($item['id'] ?? (string)$index);
        $cat = e($item['category'] ?? 'General');
      ?>
      <div class="accordion-item card mb-4">
        <div class="accordion-header card-header" id="heading-<?= $itemId ?>">
          <button type="button" class="accordion-btn" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="panel-<?= $itemId ?>">
            <div class="accordion-title-wrap">
              <span class="badge badge-<?= $cat === 'personal' ? 'primary' : ($cat === 'environment' ? 'info' : 'warn') ?>">
                <?= ucfirst($cat) ?>
              </span>
              <span class="accordion-title-text"><?= e($item['title']) ?></span>
            </div>
            <svg class="icon icon-sm accordion-chevron" aria-hidden="true"><use href="#icon-chevron-down"></use></svg>
          </button>
        </div>
        <div id="panel-<?= $itemId ?>" class="accordion-panel <?= $index === 0 ? 'open' : '' ?>" aria-labelledby="heading-<?= $itemId ?>">
          <div class="card-body">
            <p class="guideline-desc mb-3"><?= e($item['desc']) ?></p>
            <div class="source-citation-line">
              <span class="text-xs text-muted">Authoritative Source:</span>
              <a href="<?= e($item['sourceUrl']) ?>" target="_blank" rel="noopener noreferrer" class="source-link text-xs">
                <?= e($item['source']) ?>
                <svg class="icon icon-xs inline-icon" aria-hidden="true"><use href="#icon-external-link"></use></svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Medical Advice Callout -->
  <div class="alert alert-info mt-6" role="note">
    <svg class="icon icon-md text-info" aria-hidden="true"><use href="#icon-info"></use></svg>
    <div>
      <strong>Health Advisory:</strong> Individuals at higher risk of severe illness (older adults and individuals with pre-existing cardiovascular or immunocompromising conditions) should consider wearing high-efficiency respiratory protection (N95/FFP2) in crowded public venues.
    </div>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
