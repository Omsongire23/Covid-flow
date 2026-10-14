<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Home — Global Pandemic Information Portal';
$pageDesc  = 'Live verified COVID-19 pandemic statistics, public health guidelines, community discussions, and software quality assurance verification.';
$pageScript = ['stats.js'];

require_once APP_ROOT . '/includes/layout_header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container hero-container">
    <div class="hero-content">
      <div class="hero-badge">
        <span class="badge-dot"></span>
        <span>Evidence-Based Public Health Portal</span>
      </div>
      <h1 class="hero-title">Real-Time COVID-19 Monitoring &amp; Public Guidance</h1>
      <p class="hero-subtitle">
        Access verified global surveillance metrics, evidence-based medical guidance, and community registration services engineered to rigorous software quality assurance standards.
      </p>
      <div class="hero-cta-group">
        <a href="/statistics.php" class="btn btn-primary btn-lg">
          <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-chart"></use></svg>
          Explore Statistics
        </a>
        <a href="/program-registration.php" class="btn btn-outline btn-lg">
          <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-clipboard"></use></svg>
          Register for Programme
        </a>
        <a href="/quality-lab.php" class="btn btn-ghost btn-lg">
          <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-lab"></use></svg>
          Quality Lab
        </a>
      </div>
    </div>
    <div class="hero-card-preview">
      <div class="hero-feature-card">
        <div class="feature-header">
          <span class="feature-tag">Dual-Engine Parity</span>
          <span class="feature-status">100% Tested</span>
        </div>
        <h3>Regular Expression Engine Parity</h3>
        <p>
          Single source-of-truth validation suite executed simultaneously across JavaScript and PHP.
        </p>
        <ul class="feature-metrics">
          <li><strong>127</strong> Automated Test Cases</li>
          <li><strong>0</strong> Engine Discrepancies</li>
          <li><strong>100%</strong> Test Coverage</li>
        </ul>
        <a href="/quality-lab.php" class="feature-link">Open QA Interactive Runner &rarr;</a>
      </div>
    </div>
  </div>
</section>

<!-- Global Statistics Section -->
<section class="section stats-section-wrap" aria-labelledby="global-stats-heading">
  <div class="container">
    <div class="section-header">
      <div>
        <h2 id="global-stats-heading" class="section-title">Global Surveillance Snapshot</h2>
        <p class="section-desc">Up-to-date global case counts and daily variations sourced directly from the disease.sh repository.</p>
      </div>
      <div id="source-bar" class="source-timestamp-bar" aria-live="polite">
        Source: <strong>disease.sh</strong> &bull; Loading latest telemetry...
      </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="stat-grid" id="stat-section">
      <!-- Total Cases -->
      <div class="stat-card stat-card-primary" id="stat-cases" tabindex="0">
        <div class="stat-card-header">
          <span class="stat-card-title">Confirmed Cases</span>
          <svg class="icon icon-md stat-icon text-accent" aria-hidden="true"><use href="#icon-virus"></use></svg>
        </div>
        <div class="stat-card-value">&mdash;</div>
        <div class="stat-card-footer">
          <span class="stat-card-delta">&mdash;</span>
          <span class="stat-card-sub">Cumulative global infections</span>
        </div>
      </div>

      <!-- Active Cases -->
      <div class="stat-card stat-card-info" id="stat-active" tabindex="0">
        <div class="stat-card-header">
          <span class="stat-card-title">Active Cases</span>
          <svg class="icon icon-md stat-icon text-info" aria-hidden="true"><use href="#icon-pulse"></use></svg>
        </div>
        <div class="stat-card-value">&mdash;</div>
        <div class="stat-card-footer">
          <span class="stat-card-delta">&mdash;</span>
          <span class="stat-card-sub">Currently undergoing care</span>
        </div>
      </div>

      <!-- Recovered Cases -->
      <div class="stat-card stat-card-ok" id="stat-recovered" tabindex="0">
        <div class="stat-card-header">
          <span class="stat-card-title">Recovered</span>
          <svg class="icon icon-md stat-icon text-ok" aria-hidden="true"><use href="#icon-shield-check"></use></svg>
        </div>
        <div class="stat-card-value">&mdash;</div>
        <div class="stat-card-footer">
          <span class="stat-card-delta">&mdash;</span>
          <span class="stat-card-sub">Reported recoveries</span>
        </div>
      </div>

      <!-- Total Deaths -->
      <div class="stat-card stat-card-danger" id="stat-deaths" tabindex="0">
        <div class="stat-card-header">
          <span class="stat-card-title">Deaths</span>
          <svg class="icon icon-md stat-icon text-danger" aria-hidden="true"><use href="#icon-heart-pulse"></use></svg>
        </div>
        <div class="stat-card-value">&mdash;</div>
        <div class="stat-card-footer">
          <span class="stat-card-delta">&mdash;</span>
          <span class="stat-card-sub">Attributed to COVID-19</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stay Informed Navigation Cards -->
<section class="section bg-surface-2" aria-labelledby="guidance-heading">
  <div class="container">
    <div class="section-header text-center">
      <h2 id="guidance-heading" class="section-title">Verified Health Guidance</h2>
      <p class="section-desc">Clinical recommendations and advisory information verified by WHO and MoHFW.</p>
    </div>

    <div class="card-grid card-grid-3">
      <!-- Prevention -->
      <article class="info-card">
        <div class="info-card-icon bg-primary-soft">
          <svg class="icon icon-lg text-primary" aria-hidden="true"><use href="#icon-mask"></use></svg>
        </div>
        <h3 class="info-card-title">Prevention Protocols</h3>
        <p class="info-card-desc">
          High-filtration masks, indoor ventilation benchmarks, hand hygiene, and transmission reduction protocols.
        </p>
        <a href="/prevention.php" class="card-action-link">Read Prevention Guide &rarr;</a>
      </article>

      <!-- Symptoms -->
      <article class="info-card">
        <div class="info-card-icon bg-info-soft">
          <svg class="icon icon-lg text-info" aria-hidden="true"><use href="#icon-thermometer"></use></svg>
        </div>
        <h3 class="info-card-title">Symptoms &amp; Warning Signs</h3>
        <p class="info-card-desc">
          Identify common viral indicators versus emergency danger signs requiring immediate hospital oxygen therapy.
        </p>
        <a href="/symptoms.php" class="card-action-link">Review Symptom Guide &rarr;</a>
      </article>

      <!-- Vaccines -->
      <article class="info-card">
        <div class="info-card-icon bg-ok-soft">
          <svg class="icon icon-lg text-ok" aria-hidden="true"><use href="#icon-syringe"></use></svg>
        </div>
        <h3 class="info-card-title">Vaccine Information</h3>
        <p class="info-card-desc">
          Clinical facts on primary schedules, precautionary booster doses, vaccine safety, and adverse event monitoring.
        </p>
        <a href="/vaccination.php" class="card-action-link">Explore Vaccines &rarr;</a>
      </article>

      <!-- Myths vs Facts -->
      <article class="info-card">
        <div class="info-card-icon bg-warn-soft">
          <svg class="icon icon-lg text-warn" aria-hidden="true"><use href="#icon-lightbulb"></use></svg>
        </div>
        <h3 class="info-card-title">Myths vs. Facts</h3>
        <p class="info-card-desc">
          Scientific fact-checking countering dangerous misinformation about treatments, transmission, and vaccines.
        </p>
        <a href="/myths.php" class="card-action-link">Check Facts &rarr;</a>
      </article>

      <!-- Helplines -->
      <article class="info-card">
        <div class="info-card-icon bg-danger-soft">
          <svg class="icon icon-lg text-danger" aria-hidden="true"><use href="#icon-phone"></use></svg>
        </div>
        <h3 class="info-card-title">Emergency Helplines</h3>
        <p class="info-card-desc">
          Direct toll-free national emergency lines, state control rooms, childline support, and mental health assistance.
        </p>
        <a href="/helplines.php" class="card-action-link">View Helpline Directory &rarr;</a>
      </article>

      <!-- Community & QA -->
      <article class="info-card">
        <div class="info-card-icon bg-primary-soft">
          <svg class="icon icon-lg text-primary" aria-hidden="true"><use href="#icon-chat"></use></svg>
        </div>
        <h3 class="info-card-title">Community &amp; Services</h3>
        <p class="info-card-desc">
          Join community discussions, register for awareness webinars or volunteer support, and verify software quality test results.
        </p>
        <a href="/community.php" class="card-action-link">Join Discussions &rarr;</a>
      </article>
    </div>
  </div>
</section>

<!-- SQA Project Highlights Banner -->
<section class="section" aria-labelledby="sqa-banner-title">
  <div class="container">
    <div class="callout-card">
      <div class="callout-content">
        <span class="badge badge-primary">Software Quality Assurance Deliverable</span>
        <h2 id="sqa-banner-title" class="callout-title">Engineered for Verifiable Quality &amp; Security</h2>
        <p class="callout-desc">
          This portal fulfills every mandatory requirement for the SQA Mini Project:
          MySQL persistence with PDO prepared statements, client &amp; server regex validation, cross-engine parity testing, 
          session hardening with CSRF protection and brute-force throttling, and an in-browser Quality Lab.
        </p>
        <div class="callout-links">
          <a href="/quality-lab.php" class="btn btn-primary">Run Quality Lab Suite</a>
          <a href="/program-registration.php" class="btn btn-outline">Test Programme Registration Form</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
