<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Vaccination Guidance & Safety';
$pageDesc  = 'Official public health guidance on COVID-19 primary vaccine regimens, precautionary boosters, and pharmacovigilance safety.';

$contentFile = APP_ROOT . '/data/content.json';
$content = file_exists($contentFile) ? (json_decode(file_get_contents($contentFile), true) ?? []) : [];
$vaccinationList = $content['vaccination'] ?? [];
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
          <li aria-current="page">Vaccination</li>
        </ol>
      </nav>
      <div class="header-meta-row">
        <h1 class="page-title">COVID-19 Vaccination &amp; Immunization</h1>
        <div class="review-date-badge">
          <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-calendar"></use></svg>
          <span>Last reviewed: <strong><?= e($lastReviewed) ?></strong></span>
        </div>
      </div>
      <p class="page-subtitle">
        Comprehensive scientific data on primary doses, precautionary booster schedules, and continuous pharmacovigilance monitoring.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <!-- Key Benefits Summary Grid -->
  <div class="grid grid-3 mb-6">
    <div class="stat-card stat-card-ok">
      <div class="stat-card-header">
        <span class="stat-card-title">Hospitalization Defense</span>
        <svg class="icon icon-md text-ok" aria-hidden="true"><use href="#icon-shield-check"></use></svg>
      </div>
      <div class="stat-card-value text-2xl">&gt; 90%</div>
      <p class="text-xs text-muted mt-2">Reduction in risk of severe disease, ICU admission, and death across evaluated platforms.</p>
    </div>

    <div class="stat-card stat-card-primary">
      <div class="stat-card-header">
        <span class="stat-card-title">Global Administrations</span>
        <svg class="icon icon-md text-primary" aria-hidden="true"><use href="#icon-syringe"></use></svg>
      </div>
      <div class="stat-card-value text-2xl">13+ Billion</div>
      <p class="text-xs text-muted mt-2">Doses administered globally under rigorous WHO safety and adverse-event monitoring.</p>
    </div>

    <div class="stat-card stat-card-info">
      <div class="stat-card-header">
        <span class="stat-card-title">Booster Efficacy</span>
        <svg class="icon icon-md text-info" aria-hidden="true"><use href="#icon-pulse"></use></svg>
      </div>
      <div class="stat-card-value text-2xl">Restored Titers</div>
      <p class="text-xs text-muted mt-2">Recharges neutralizing antibody levels against circulating SARS-CoV-2 sub-lineages.</p>
    </div>
  </div>

  <!-- Detailed Sections -->
  <div class="card-stack mb-6">
    <?php foreach ($vaccinationList as $index => $v): ?>
      <article class="card mb-4">
        <div class="card-header border-b">
          <div class="flex items-center gap-3">
            <span class="step-badge"><?= $index + 1 ?></span>
            <h2 class="card-title text-xl"><?= e($v['title']) ?></h2>
          </div>
        </div>
        <div class="card-body">
          <p class="summary-paragraph font-medium mb-3"><?= e($v['summary']) ?></p>
          <div class="details-box mb-4 p-3 bg-surface-2 rounded text-sm">
            <?= e($v['details']) ?>
          </div>
          <div class="source-citation-line">
            <span class="text-xs text-muted">Authoritative Source:</span>
            <a href="<?= e($v['sourceUrl']) ?>" target="_blank" rel="noopener noreferrer" class="source-link text-xs">
              <?= e($v['source']) ?>
              <svg class="icon icon-xs inline-icon" aria-hidden="true"><use href="#icon-external-link"></use></svg>
            </a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Programme Registration Callout -->
  <div class="callout-card">
    <div class="callout-content">
      <span class="badge badge-primary">Portal Service</span>
      <h3 class="callout-title">Need Guidance on Booster Timing or Local Clinics?</h3>
      <p class="callout-desc">
        Registered community members can request a free vaccination guidance callback or enroll in an informational webinar.
      </p>
      <div class="callout-links">
        <a href="/program-registration.php" class="btn btn-primary">Register for Information Session</a>
        <a href="/helplines.php" class="btn btn-outline">Call National Helpline (1075)</a>
      </div>
    </div>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
