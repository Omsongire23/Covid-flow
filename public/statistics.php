<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Surveillance Statistics & Analytics';
$pageDesc  = 'Interactive global pandemic trajectory charts and country-by-country COVID-19 surveillance data table.';
$pageScript = ['charts.js'];

require_once APP_ROOT . '/includes/layout_header.php';
?>

<div class="page-header bg-surface-2">
  <div class="container">
    <div class="page-header-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="/index.php">Home</a></li>
          <li aria-current="page">Statistics</li>
        </ol>
      </nav>
      <h1 class="page-title">Global COVID-19 Statistics &amp; Trends</h1>
      <p class="page-subtitle">
        Explore interactive historical trajectory charts and sortable country metrics fetched from verified surveillance feeds.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <!-- Interactive Trend Chart Card -->
  <section class="card mb-6" aria-labelledby="chart-heading">
    <div class="card-header border-b">
      <div class="chart-header-wrap">
        <div>
          <h2 id="chart-heading" class="card-title text-xl">Epidemiological Trajectory</h2>
          <p class="card-desc text-sm">Visualise historical pandemic progression over custom timeframes.</p>
        </div>

        <!-- Controls toolbar -->
        <div class="chart-controls-toolbar">
          <!-- Metric selector -->
          <div class="btn-group" role="group" aria-label="Metric Selection">
            <button type="button" class="btn btn-sm btn-outline active" data-metric="cases">Cases</button>
            <button type="button" class="btn btn-sm btn-outline" data-metric="deaths">Deaths</button>
            <button type="button" class="btn btn-sm btn-outline" data-metric="recovered">Recovered</button>
          </div>

          <!-- Time range selector -->
          <div class="btn-group" role="group" aria-label="Time Horizon Range">
            <button type="button" class="btn btn-sm btn-outline active" data-range="30">30D</button>
            <button type="button" class="btn btn-sm btn-outline" data-range="90">90D</button>
            <button type="button" class="btn btn-sm btn-outline" data-range="180">180D</button>
            <button type="button" class="btn btn-sm btn-outline" data-range="365">1Y</button>
            <button type="button" class="btn btn-sm btn-outline" data-range="all">All</button>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body">
      <!-- Chart Canvas Wrapper -->
      <div class="chart-container-box">
        <canvas id="trend-chart" aria-label="COVID-19 Historical Trend Chart" role="img"></canvas>
      </div>

      <!-- Accessible Summary Text -->
      <div class="chart-accessibility-summary" id="chart-summary" aria-live="polite">
        Loading historical trend summary...
      </div>

      <div class="chart-note mt-3 text-xs text-muted">
        <svg class="icon icon-xs inline-icon" aria-hidden="true"><use href="#icon-info"></use></svg>
        <em>Note:</em> Reporting conventions vary across member states. Certain agencies retired daily cumulative reporting in later pandemic phases.
      </div>
    </div>
  </section>

  <!-- Country Surveillance Breakdown Table -->
  <section class="card" id="countries-wrapper" aria-labelledby="table-heading">
    <div class="card-header border-b">
      <div class="table-header-wrap">
        <div>
          <h2 id="table-heading" class="card-title text-xl">Country-Level Telemetry</h2>
          <p class="card-desc text-sm">Search, filter, and sort confirmed cases across 200+ nations and territories.</p>
        </div>

        <!-- Filter & Search Controls -->
        <div class="table-filters-toolbar">
          <div class="filter-group">
            <label for="country-search" class="sr-only">Search by country name</label>
            <div class="input-icon-wrap">
              <svg class="icon icon-sm input-icon" aria-hidden="true"><use href="#icon-search"></use></svg>
              <input type="search" id="country-search" class="form-control form-control-sm" placeholder="Search country..." autocomplete="off">
            </div>
          </div>

          <div class="filter-group">
            <label for="continent-filter" class="sr-only">Filter by continent</label>
            <select id="continent-filter" class="form-control form-control-sm">
              <option value="">All Continents</option>
              <option value="Asia">Asia</option>
              <option value="Europe">Europe</option>
              <option value="North America">North America</option>
              <option value="South America">South America</option>
              <option value="Africa">Africa</option>
              <option value="Australia-Oceania">Oceania</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="per-page" class="sr-only">Items per page</label>
            <select id="per-page" class="form-control form-control-sm">
              <option value="10">10 / page</option>
              <option value="20" selected>20 / page</option>
              <option value="50">50 / page</option>
              <option value="100">100 / page</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="data-table" id="countries-table">
          <thead>
            <tr>
              <th scope="col" data-sort="country" class="sortable" tabindex="0" role="button">
                Country <span class="sort-icon" aria-hidden="true">↕</span>
              </th>
              <th scope="col" data-sort="cases" class="sortable text-right" tabindex="0" role="button">
                Total Cases <span class="sort-icon" aria-hidden="true">↕</span>
              </th>
              <th scope="col" data-sort="todayCases" class="sortable text-right" tabindex="0" role="button">
                New Today <span class="sort-icon" aria-hidden="true">↕</span>
              </th>
              <th scope="col" data-sort="deaths" class="sortable text-right" tabindex="0" role="button">
                Deaths <span class="sort-icon" aria-hidden="true">↕</span>
              </th>
              <th scope="col" data-sort="recovered" class="sortable text-right" tabindex="0" role="button">
                Recovered <span class="sort-icon" aria-hidden="true">↕</span>
              </th>
              <th scope="col" data-sort="active" class="sortable text-right" tabindex="0" role="button">
                Active <span class="sort-icon" aria-hidden="true">↕</span>
              </th>
              <th scope="col" data-sort="tests" class="sortable text-right" tabindex="0" role="button">
                Tests Tested <span class="sort-icon" aria-hidden="true">↕</span>
              </th>
            </tr>
          </thead>
          <tbody id="countries-tbody">
            <tr>
              <td colspan="7" class="text-center py-6 text-muted">
                <div class="loading-spinner"></div>
                <p class="mt-2">Loading country surveillance metrics...</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Container -->
      <div class="table-pagination-footer" id="countries-pagination">
        <div class="pagination-info" id="pagination-info">Showing metrics...</div>
        <div class="pagination-buttons" id="pagination-nav"></div>
      </div>
    </div>
  </section>
</div>

<!-- Vendored Chart.js UMD build loaded before charts module -->
<script src="/assets/vendor/chart.umd.min.js"></script>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
