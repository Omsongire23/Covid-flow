<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Service Unavailable | COVID-19 Portal</title>
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/layout.css">
  <style>
    .error-container {
      min-height: 80vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: var(--space-6) var(--space-4);
      text-align: center;
    }
    .error-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: var(--space-8);
      max-width: 580px;
      box-shadow: var(--shadow-md);
    }
    .error-icon {
      font-size: 3rem;
      margin-bottom: var(--space-4);
    }
    .error-title {
      font-size: 1.75rem;
      color: var(--text);
      margin-bottom: var(--space-3);
    }
    .error-desc {
      color: var(--text-muted);
      margin-bottom: var(--space-6);
      line-height: 1.6;
    }
    .error-actions {
      display: flex;
      gap: var(--space-3);
      justify-content: center;
      flex-wrap: wrap;
    }
    .emergency-box {
      margin-top: var(--space-6);
      padding: var(--space-4);
      background: var(--surface-2);
      border-radius: var(--radius);
      font-size: var(--font-size-sm);
      color: var(--text-muted);
    }
  </style>
</head>
<body>
  <main class="error-container">
    <div class="error-card">
      <div class="error-icon" aria-hidden="true">⚠️</div>
      <h1 class="error-title">Database Service Temporarily Unavailable</h1>
      <p class="error-desc">
        We are experiencing a temporary connection interruption with the backend database. 
        Our system administrators have been alerted. Please try refreshing in a few moments.
      </p>
      <div class="error-actions">
        <button type="button" class="btn btn-primary" data-action="reload">Retry Now</button>
        <a href="/" class="btn btn-outline">Return to Home</a>
      </div>
      <div class="emergency-box">
        <strong>Need immediate health assistance?</strong><br>
        National COVID-19 Helpline: <strong>1075</strong> (Toll-free, 24x7)
      </div>
    </div>
  </main>
</body>
</html>
