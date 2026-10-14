<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_guest();

$error = '';
$username = '';
$returnTo = (string)($_GET['return'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $returnTo = (string)($_POST['return'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $res = attempt_login($username, $password);
        if ($res['ok']) {
            flash('success', 'Welcome back, ' . $res['user']['username'] . '!');
            
            // Validate safe return URL (prevent open redirect)
            if ($returnTo !== '' && str_starts_with($returnTo, '/') && !str_starts_with($returnTo, '//')) {
                redirect($returnTo);
            }
            redirect('/profile.php');
        } else {
            if (!empty($res['throttled'])) {
                http_response_code(429);
            }
            $error = $res['error'] ?? 'Invalid username or password.';
        }
    }
}

$pageTitle = 'Log In to Account';
$pageDesc  = 'Securely sign in to your COVID-19 Portal account.';
$pageScript = ['forms.js'];

require_once APP_ROOT . '/includes/layout_header.php';
?>

<div class="auth-page-wrapper">
  <div class="auth-card card">
    <div class="card-header text-center border-b">
      <div class="auth-brand-logo mb-2">
        <img src="/assets/img/logo.svg" alt="" width="44" height="44" aria-hidden="true">
      </div>
      <h1 class="card-title text-2xl">Sign In to COVID-19 Portal</h1>
      <p class="card-desc text-sm">Enter your credentials to access your registrations and community history.</p>
    </div>

    <div class="card-body">
      <?php if ($error !== ''): ?>
        <div class="alert alert-danger mb-4" role="alert">
          <svg class="icon icon-sm text-danger" aria-hidden="true"><use href="#icon-alert-triangle"></use></svg>
          <div><?= e($error) ?></div>
        </div>
      <?php endif; ?>

      <form action="/login.php<?= $returnTo !== '' ? '?return=' . urlencode($returnTo) : '' ?>" method="POST" id="login-form">
        <?= csrf_field() ?>
        <input type="hidden" name="return" value="<?= e($returnTo) ?>">

        <!-- Username -->
        <div class="form-group mb-4">
          <label for="username" class="form-label">Username</label>
          <input 
            type="text" 
            name="username" 
            id="username" 
            class="form-control" 
            value="<?= e($username) ?>" 
            required 
            autocomplete="username" 
            autofocus
            placeholder="Your username"
          >
        </div>

        <!-- Password -->
        <div class="form-group mb-5">
          <div class="flex items-center justify-between mb-1">
            <label for="password" class="form-label mb-0">Password</label>
          </div>
          <div class="input-password-wrap">
            <input 
              type="password" 
              name="password" 
              id="password" 
              class="form-control" 
              required 
              autocomplete="current-password"
              placeholder="Your password"
            >
            <button type="button" class="btn-toggle-pw" aria-label="Show password">
              <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-eye"></use></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">Log In</button>
      </form>
    </div>

    <div class="card-footer text-center border-t py-4">
      <p class="text-sm text-muted">
        Don&apos;t have an account? <a href="/signup.php" class="font-medium text-primary">Sign up now</a>
      </p>
    </div>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
