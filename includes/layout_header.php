<?php
declare(strict_types=1);

/**
 * layout_header.php — Global HTML head and header navigation.
 * Expected variables:
 *  - $pageTitle (string)
 *  - $pageDesc  (string)
 */

$u = current_user();
$flashes = flash_get();
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="description" content="<?= e($pageDesc ?? 'Official COVID-19 information, live statistics, community updates, and quality engineering lab.') ?>">
  <title><?= e(($pageTitle ?? 'Home') . ' | ' . (APP_CFG['app_name'] ?? 'COVID-19 Portal')) ?></title>

  <!-- Early theme initialisation to prevent FOUC -->
  <script src="/assets/js/theme-init.js"></script>

  <!-- Core Stylesheet Pipeline -->
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/layout.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/pages.css">

  <link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
</head>
<body>
  <!-- Hidden SVG Sprite Sheet -->
  <?php @include APP_ROOT . '/public/assets/img/sprite.svg'; ?>

  <!-- Primary Site Header -->
  <header class="site-header" id="header">
    <div class="container header-container">
      <a href="/index.php" class="brand-link" aria-label="COVID-19 Portal Home">
        <img src="/assets/img/logo.svg" alt="" width="34" height="34" class="brand-logo" aria-hidden="true">
        <span class="brand-text">COVID-19 <strong>Portal</strong></span>
      </a>

      <!-- Desktop Navigation -->
      <nav class="main-nav" aria-label="Main Navigation">
        <ul class="nav-list">
          <li class="nav-item">
            <a href="/index.php" class="nav-link <?= ($currentPath === '/' || $currentPath === '/index.php') ? 'active' : '' ?>" <?= ($currentPath === '/' || $currentPath === '/index.php') ? 'aria-current="page"' : '' ?>>Home</a>
          </li>
          <li class="nav-item">
            <a href="/statistics.php" class="nav-link <?= ($currentPath === '/statistics.php') ? 'active' : '' ?>" <?= ($currentPath === '/statistics.php') ? 'aria-current="page"' : '' ?>>Statistics</a>
          </li>

          <!-- Learn Dropdown -->
          <li class="nav-item dropdown">
            <button type="button" class="nav-link dropdown-toggle" aria-expanded="false" aria-haspopup="true" id="learnDropdownBtn">
              Learn
              <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-chevron-down"></use></svg>
            </button>
            <ul class="dropdown-menu" aria-labelledby="learnDropdownBtn">
              <li><a href="/prevention.php" class="dropdown-item">Prevention Guidelines</a></li>
              <li><a href="/symptoms.php" class="dropdown-item">Symptoms Guide</a></li>
              <li><a href="/vaccination.php" class="dropdown-item">Vaccine Information</a></li>
              <li><a href="/myths.php" class="dropdown-item">Myths vs. Facts</a></li>
              <li><a href="/helplines.php" class="dropdown-item">National Helplines</a></li>
            </ul>
          </li>

          <li class="nav-item">
            <a href="/community.php" class="nav-link <?= ($currentPath === '/community.php') ? 'active' : '' ?>" <?= ($currentPath === '/community.php') ? 'aria-current="page"' : '' ?>>Community</a>
          </li>
          <li class="nav-item">
            <a href="/quality-lab.php" class="nav-link lab-link <?= ($currentPath === '/quality-lab.php') ? 'active' : '' ?>" <?= ($currentPath === '/quality-lab.php') ? 'aria-current="page"' : '' ?>>
              <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-lab"></use></svg>
              Quality Lab
            </a>
          </li>
        </ul>
      </nav>

      <!-- Header Actions (Theme Toggle & Auth) -->
      <div class="header-actions">
        <!-- Theme Toggle Button -->
        <button type="button" class="btn-theme-toggle" id="themeToggleBtn" aria-label="Toggle theme">
          <svg class="icon theme-icon-sun" aria-hidden="true"><use href="#icon-sun"></use></svg>
          <svg class="icon theme-icon-moon" aria-hidden="true"><use href="#icon-moon"></use></svg>
        </button>

        <?php if ($u !== null): ?>
          <div class="user-menu dropdown">
            <button type="button" class="btn btn-outline btn-sm dropdown-toggle" id="userMenuBtn" aria-expanded="false" aria-haspopup="true">
              <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-user"></use></svg>
              <span><?= e($u['username']) ?></span>
              <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-chevron-down"></use></svg>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuBtn">
              <li><a href="/profile.php" class="dropdown-item">My Profile &amp; History</a></li>
              <?php if (($u['role'] ?? '') === 'admin'): ?>
                <li><a href="/admin/comments.php" class="dropdown-item">Admin Moderation</a></li>
              <?php endif; ?>
              <li class="dropdown-divider"></li>
              <li>
                <form action="/logout.php" method="POST" class="logout-form">
                  <?= csrf_field() ?>
                  <button type="submit" class="dropdown-item text-danger">
                    <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-logout"></use></svg>
                    Log Out
                  </button>
                </form>
              </li>
            </ul>
          </div>
        <?php else: ?>
          <div class="auth-buttons">
            <a href="/login.php" class="btn btn-ghost btn-sm">Log In</a>
            <a href="/signup.php" class="btn btn-primary btn-sm">Sign Up</a>
          </div>
        <?php endif; ?>

        <!-- Mobile Hamburger Button -->
        <button type="button" class="btn-hamburger" id="hamburgerBtn" aria-label="Open Navigation Menu" aria-expanded="false" aria-controls="mobileDrawer">
          <svg class="icon icon-menu" aria-hidden="true"><use href="#icon-menu"></use></svg>
        </button>
      </div>
    </div>

    <!-- Mobile Drawer Navigation -->
    <div class="mobile-drawer" id="mobileDrawer" hidden>
      <div class="mobile-drawer-inner">
        <div class="mobile-drawer-header">
          <div class="brand-link">
            <img src="/assets/img/logo.svg" alt="" width="28" height="28" aria-hidden="true">
            <span class="brand-text">COVID-19 <strong>Portal</strong></span>
          </div>
          <button type="button" class="btn-drawer-close" id="drawerCloseBtn" aria-label="Close navigation">
            <svg class="icon" aria-hidden="true"><use href="#icon-close"></use></svg>
          </button>
        </div>
        <nav class="mobile-nav" aria-label="Mobile Navigation">
          <ul class="mobile-nav-list">
            <li><a href="/index.php" class="mobile-nav-link">Home</a></li>
            <li><a href="/statistics.php" class="mobile-nav-link">Statistics &amp; Charts</a></li>
            <li class="mobile-group-title">Information &amp; Guidance</li>
            <li><a href="/prevention.php" class="mobile-nav-sublink">Prevention Guidelines</a></li>
            <li><a href="/symptoms.php" class="mobile-nav-sublink">Symptoms Guide</a></li>
            <li><a href="/vaccination.php" class="mobile-nav-sublink">Vaccine Information</a></li>
            <li><a href="/myths.php" class="mobile-nav-sublink">Myths vs Facts</a></li>
            <li><a href="/helplines.php" class="mobile-nav-sublink">Helpline Directory</a></li>
            <li class="mobile-group-title">Interaction &amp; Lab</li>
            <li><a href="/community.php" class="mobile-nav-link">Community Discussions</a></li>
            <li><a href="/program-registration.php" class="mobile-nav-link">Programme Registration</a></li>
            <li><a href="/quality-lab.php" class="mobile-nav-link">Quality Assurance Lab</a></li>
          </ul>
        </nav>
        <div class="mobile-drawer-footer">
          <?php if ($u !== null): ?>
            <div class="user-badge mb-2">Signed in as <strong><?= e($u['username']) ?></strong></div>
            <a href="/profile.php" class="btn btn-outline btn-block mb-2">View Profile</a>
            <form action="/logout.php" method="POST">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger btn-block">Log Out</button>
            </form>
          <?php else: ?>
            <a href="/login.php" class="btn btn-outline btn-block mb-2">Log In</a>
            <a href="/signup.php" class="btn btn-primary btn-block">Sign Up</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <!-- Flash Messages / Toast Container -->
  <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true">
    <?php foreach ($flashes as $f): ?>
      <div class="toast toast-<?= e($f['type']) ?>" role="status">
        <span class="toast-message"><?= e($f['message']) ?></span>
        <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Main Content Landmark -->
  <main id="main-content" class="site-main">
