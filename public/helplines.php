<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Emergency Helplines Directory';
$pageDesc  = 'Verified emergency telephone helplines, tele-mental health assistance, and medical escalation contacts for COVID-19.';

$contentFile = APP_ROOT . '/data/content.json';
$content = file_exists($contentFile) ? (json_decode(file_get_contents($contentFile), true) ?? []) : [];
$helplinesList = $content['helplines'] ?? [];
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
          <li aria-current="page">Helplines</li>
        </ol>
      </nav>
      <div class="header-meta-row">
        <h1 class="page-title">National COVID-19 Helplines Directory</h1>
        <div class="review-date-badge">
          <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-calendar"></use></svg>
          <span>Last verified: <strong><?= e($lastReviewed) ?></strong></span>
        </div>
      </div>
      <p class="page-subtitle">
        Direct telephone helplines verified with official government and public health ministries for urgent clinical consultation, mental health, and emergency dispatch.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <!-- Urgent Calling Notice -->
  <div class="emergency-alert-card mb-6" role="alert">
    <div class="emergency-alert-header">
      <svg class="icon icon-lg text-danger" aria-hidden="true"><use href="#icon-phone"></use></svg>
      <div>
        <h2 class="emergency-title">National Emergency Primary Helpline: 1075</h2>
        <p class="emergency-desc">
          Toll-free 24/7 national central helpline operated by the Ministry of Health and Family Welfare (MoHFW) for immediate public health escalations, triage, and guidance.
        </p>
      </div>
    </div>
    <div class="mt-3">
      <a href="tel:1075" class="btn btn-danger btn-lg">
        <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-phone"></use></svg>
        Call 1075 Now (Toll Free)
      </a>
    </div>
  </div>

  <!-- Helplines Grid -->
  <div class="card-grid card-grid-2 mb-6">
    <?php foreach ($helplinesList as $h): ?>
      <?php 
        $numClean = preg_replace('/[^0-9+]/', '', $h['number']);
      ?>
      <article class="helpline-card card">
        <div class="card-header border-b">
          <div class="flex items-center justify-between">
            <h2 class="card-title text-lg"><?= e($h['name']) ?></h2>
            <div class="flex gap-2">
              <?php if (!empty($h['tollFree'])): ?>
                <span class="badge badge-ok">Toll Free</span>
              <?php endif; ?>
              <span class="badge badge-info"><?= e($h['hours']) ?></span>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="helpline-number-wrap mb-3">
            <a href="tel:<?= e($numClean) ?>" class="helpline-tel-link">
              <svg class="icon icon-md" aria-hidden="true"><use href="#icon-phone"></use></svg>
              <span><?= e($h['number']) ?></span>
            </a>
          </div>
          <p class="helpline-desc text-sm text-muted mb-4"><?= e($h['desc']) ?></p>

          <div class="source-citation-line border-t pt-3">
            <div class="flex items-center justify-between text-xs">
              <span class="text-muted">Verified: <?= e($h['lastVerified'] ?? $lastReviewed) ?></span>
              <a href="<?= e($h['sourceUrl']) ?>" target="_blank" rel="noopener noreferrer" class="source-link">
                <?= e($h['source']) ?>
                <svg class="icon icon-xs inline-icon" aria-hidden="true"><use href="#icon-external-link"></use></svg>
              </a>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Disclaimer callout -->
  <div class="alert alert-info">
    <svg class="icon icon-md text-info" aria-hidden="true"><use href="#icon-info"></use></svg>
    <div>
      <strong>Teleconsultation Information:</strong> Helplines provide tele-triage and administrative healthcare referral services. 
      For active respiratory distress or life-threatening symptoms, dial your regional ambulance dispatch (108 / 112 in India) or visit an acute hospital casualty center.
    </div>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
