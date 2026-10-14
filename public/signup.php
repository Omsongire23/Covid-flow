<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_guest();

$errors = [];
$username = '';
$email = '';
$fullName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim((string)($_POST['username'] ?? ''));
    $email    = strtolower(trim((string)($_POST['email'] ?? '')));
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password2'] ?? '');

    // Validate fields with shared patterns
    if (!validate_field('username', $username)) {
        $errors['username'] = validate_message('username');
    }
    if (!validate_field('email', $email)) {
        $errors['email'] = validate_message('email');
    }
    if (!validate_field('fullName', $fullName)) {
        $errors['full_name'] = validate_message('fullName');
    }
    if (!validate_field('password', $password)) {
        $errors['password'] = validate_message('password');
    } elseif ($password !== $confirm) {
        $errors['password2'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = db();

        // Check if username already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors['username'] = 'This username is already registered. Please choose another.';
        }

        // Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email address already exists.';
        }

        if (empty($errors)) {
            $pwHash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, "user")');
            $insert->execute([$username, $email, $pwHash]);
            $newUserId = (int)$pdo->lastInsertId();

            // Automatically log in newly created user
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'       => $newUserId,
                'username' => $username,
                'email'    => $email,
                'role'     => 'user',
            ];

            flash('success', 'Account created successfully! Welcome to the COVID-19 Information Portal.');
            redirect('/profile.php');
        }
    }
}

$pageTitle = 'Sign Up for Portal Account';
$pageDesc  = 'Register a secure account on the COVID-19 Portal to access programme registrations and community features.';
$pageScript = ['forms.js'];

require_once APP_ROOT . '/includes/layout_header.php';
?>

<div class="auth-page-wrapper">
  <div class="auth-card card">
    <div class="card-header text-center border-b">
      <div class="auth-brand-logo mb-2">
        <img src="/assets/img/logo.svg" alt="" width="44" height="44" aria-hidden="true">
      </div>
      <h1 class="card-title text-2xl">Create Your Account</h1>
      <p class="card-desc text-sm">Join the portal to register for webinars, support programmes, and community discussions.</p>
    </div>

    <div class="card-body">
      <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger mb-4"><?= e($errors['general']) ?></div>
      <?php endif; ?>

      <form action="/signup.php" method="POST" id="signup-form" novalidate>
        <?= csrf_field() ?>

        <!-- Full Name -->
        <div class="form-group mb-4">
          <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
          <input 
            type="text" 
            name="full_name" 
            id="full_name" 
            class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
            value="<?= e($fullName) ?>" 
            required 
            autocomplete="name" 
            placeholder="e.g. Om Patil"
          >
          <?php if (isset($errors['full_name'])): ?>
            <p class="form-error" id="full_name-error">✕ <?= e($errors['full_name']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Username -->
        <div class="form-group mb-4">
          <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
          <input 
            type="text" 
            name="username" 
            id="username" 
            class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
            value="<?= e($username) ?>" 
            required 
            autocomplete="username" 
            placeholder="e.g. om_patil"
          >
          <p class="form-hint text-xs text-muted mt-1">4–20 characters; start with a letter; letters, numbers, and underscores.</p>
          <?php if (isset($errors['username'])): ?>
            <p class="form-error" id="username-error">✕ <?= e($errors['username']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="form-group mb-4">
          <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
          <input 
            type="email" 
            name="email" 
            id="email" 
            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
            value="<?= e($email) ?>" 
            required 
            autocomplete="email" 
            placeholder="name@example.com"
          >
          <?php if (isset($errors['email'])): ?>
            <p class="form-error" id="email-error">✕ <?= e($errors['email']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Password -->
        <div class="form-group mb-4">
          <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
          <div class="input-password-wrap">
            <input 
              type="password" 
              name="password" 
              id="password" 
              class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
              required 
              autocomplete="new-password"
            >
            <button type="button" class="btn-toggle-pw" aria-label="Show password">
              <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-eye"></use></svg>
            </button>
          </div>
          <!-- Password strength meter -->
          <div class="strength-meter-wrap mt-2">
            <div class="strength-meter">
              <span class="strength-bar"></span>
              <span class="strength-bar"></span>
              <span class="strength-bar"></span>
            </div>
            <span class="strength-label text-xs"></span>
          </div>
          <p class="form-hint text-xs text-muted mt-1">8–64 characters with upper, lower, digit &amp; special character.</p>
          <?php if (isset($errors['password'])): ?>
            <p class="form-error" id="password-error">✕ <?= e($errors['password']) ?></p>
          <?php endif; ?>
        </div>

        <!-- Confirm Password -->
        <div class="form-group mb-5">
          <label for="password2" class="form-label">Confirm Password <span class="text-danger">*</span></label>
          <div class="input-password-wrap">
            <input 
              type="password" 
              name="password2" 
              id="password2" 
              class="form-control <?= isset($errors['password2']) ? 'is-invalid' : '' ?>" 
              required 
              autocomplete="new-password"
            >
            <button type="button" class="btn-toggle-pw" aria-label="Show password">
              <svg class="icon icon-sm" aria-hidden="true"><use href="#icon-eye"></use></svg>
            </button>
          </div>
          <?php if (isset($errors['password2'])): ?>
            <p class="form-error" id="password2-error">✕ <?= e($errors['password2']) ?></p>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
      </form>
    </div>

    <div class="card-footer text-center border-t py-4">
      <p class="text-sm text-muted">
        Already have an account? <a href="/login.php" class="font-medium text-primary">Log in here</a>
      </p>
    </div>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
