<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Symptoms & Emergency Indicators';
$pageDesc  = 'Clinical guide to COVID-19 symptoms, presentation frequencies, and critical danger signs requiring emergency medical care.';

$contentFile = APP_ROOT . '/data/content.json';
$content = file_exists($contentFile) ? (json_decode(file_get_contents($contentFile), true) ?? []) : [];
$symptoms = $content['symptoms'] ?? [];
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
          <li aria-current="page">Symptoms</li>
        </ol>
      </nav>
      <div class="header-meta-row">
        <h1 class="page-title">COVID-19 Symptoms &amp; Warning Signs</h1>
        <div class="review-date-badge">
          <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-calendar"></use></svg>
          <span>Last reviewed: <strong><?= e($lastReviewed) ?></strong></span>
        </div>
      </div>
      <p class="page-subtitle">
        Understand the spectrum of COVID-19 infection indicators, from common mild presentations to life-threatening emergency warning signs.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <!-- Critical Emergency Alert Box -->
  <div class="emergency-alert-card mb-6" role="alert">
    <div class="emergency-alert-header">
      <svg class="icon icon-lg text-danger" aria-hidden="true"><use href="#icon-alert-triangle"></use></svg>
      <div>
        <h2 class="emergency-title">Emergency Warning Signs — Call 1075 or Local EMS Immediately</h2>
        <p class="emergency-desc">
          If you or someone in your care displays any of the following clinical danger signs, seek immediate in-person emergency hospital care:
        </p>
      </div>
    </div>
    <div class="emergency-grid">
      <?php foreach ($symptoms['emergency'] ?? [] as $em): ?>
        <div class="emergency-item">
          <span class="emergency-bullet">&bull;</span>
          <div>
            <strong><?= e($em['name']) ?></strong>
            <p class="emergency-action-note text-sm"><?= e($em['action']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Common & Less Common Symptoms Columns -->
  <div class="grid grid-2 mb-6">
    <!-- Most Common Symptoms -->
    <div class="card">
      <div class="card-header border-b">
        <div class="flex items-center gap-2">
          <svg class="icon icon-md text-primary" aria-hidden="true"><use href="#icon-thermometer"></use></svg>
          <h2 class="card-title text-lg">Most Common Indicators</h2>
        </div>
        <span class="badge badge-primary">High Frequency</span>
      </div>
      <div class="card-body p-0">
        <ul class="symptom-list">
          <?php foreach ($symptoms['common'] ?? [] as $sc): ?>
            <li class="symptom-item">
              <div class="symptom-info">
                <span class="symptom-name"><?= e($sc['name']) ?></span>
                <span class="symptom-severity badge badge-info"><?= e($sc['severity']) ?></span>
              </div>
              <span class="symptom-freq text-xs text-muted"><?= e($sc['frequency']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- Less Common Symptoms -->
    <div class="card">
      <div class="card-header border-b">
        <div class="flex items-center gap-2">
          <svg class="icon icon-md text-info" aria-hidden="true"><use href="#icon-pulse"></use></svg>
          <h2 class="card-title text-lg">Less Common Presentations</h2>
        </div>
        <span class="badge badge-outline">Moderate Frequency</span>
      </div>
      <div class="card-body p-0">
        <ul class="symptom-list">
          <?php foreach ($symptoms['less_common'] ?? [] as $sl): ?>
            <li class="symptom-item">
              <div class="symptom-info">
                <span class="symptom-name"><?= e($sl['name']) ?></span>
                <span class="symptom-severity badge badge-ghost"><?= e($sl['severity']) ?></span>
              </div>
              <span class="symptom-freq text-xs text-muted"><?= e($sl['frequency']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <!-- Source citation -->
  <div class="source-box-card">
    <span class="text-sm">Clinical Reference:</span>
    <a href="<?= e($symptoms['sourceUrl'] ?? 'https://www.who.int') ?>" target="_blank" rel="noopener noreferrer" class="source-link">
      <?= e($symptoms['source'] ?? 'WHO Clinical Management of COVID-19') ?>
      <svg class="icon icon-xs inline-icon" aria-hidden="true"><use href="#icon-external-link"></use></svg>
    </a>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
