<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_login();

$u = current_user();
$userId = (int)$u['id'];
$pdo = db();

$contentFile = APP_ROOT . '/data/content.json';
$content = file_exists($contentFile) ? (json_decode(file_get_contents($contentFile), true) ?? []) : [];
$indianStates = $content['indianStates'] ?? get_indian_states();

$errors = [];
$successRef = null;
$submittedData = [];

// Prefill defaults from user account
$fullName = '';
$email    = $u['email'] ?? '';
$phone    = '';
$dob      = '';
$gender   = '';
$pinCode  = '';
$state    = '';
$service  = '';
$consent  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email    = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone    = trim((string)($_POST['phone'] ?? ''));
    $dob      = trim((string)($_POST['dob'] ?? ''));
    $gender   = trim((string)($_POST['gender'] ?? ''));
    $pinCode  = trim((string)($_POST['pin_code'] ?? ''));
    $state    = trim((string)($_POST['state'] ?? ''));
    $service  = trim((string)($_POST['service'] ?? ''));
    $consent  = !empty($_POST['consent']);

    $formData = [
        'full_name' => $fullName,
        'email'     => $email,
        'phone'     => $phone,
        'dob'       => $dob,
        'gender'    => $gender,
        'pin_code'  => $pinCode,
        'state'     => $state,
        'service'   => $service,
        'consent'   => $consent ? 1 : 0,
    ];

    // Server-side validation using validate_registration in includes/validate.php
    $errors = validate_registration($formData);

    if (empty($errors)) {
        // Normalise phone before saving
        $cleanPhone = normalise_phone($phone);

        // Check for existing registration for this service
        $checkStmt = $pdo->prepare('SELECT id FROM registrations WHERE user_id = ? AND service = ? LIMIT 1');
        $checkStmt->execute([$userId, $service]);
        if ($checkStmt->fetch()) {
            $errors['service'] = 'You have already enrolled in this specific programme. You can view or manage it on your Profile page.';
        } else {
            try {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO registrations (user_id, full_name, email, phone, dob, gender, pin_code, state, service, consent, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, "active")'
                );
                $insertStmt->execute([
                    $userId,
                    $fullName,
                    $email,
                    $cleanPhone,
                    $dob,
                    $gender,
                    $pinCode,
                    $state,
                    $service,
                ]);

                $newId = (int)$pdo->lastInsertId();
                $successRef = sprintf('REG-%06d', $newId);
                $submittedData = $formData;
                $submittedData['phone'] = $cleanPhone;
            } catch (PDOException $e) {
                if ((int)$e->getCode() === 23000) {
                    $errors['service'] = 'You have already enrolled in this programme.';
                } else {
                    $errors['general'] = 'An unexpected database error occurred. Please try again.';
                }
            }
        }
    }
}

$servicesMeta = [
    'vaccination_info' => [
        'title' => 'Vaccination Guidance & Local Clinic Tele-Support',
        'desc'  => 'Assistance with vaccine scheduling, precautionary booster timelines, and clinical queries.',
    ],
    'awareness_webinar' => [
        'title' => 'COVID-19 Awareness & Science Webinar Series',
        'desc'  => 'Live interactive virtual seminar with public health epidemiologists on transmission dynamics.',
    ],
    'volunteer' => [
        'title' => 'Community Health Volunteer Support Team',
        'desc'  => 'Join neighborhood health awareness groups and assist vulnerable individuals with helpline access.',
    ],
    'helpline_callback' => [
        'title' => 'Clinical Teleconsultation Follow-up Callback',
        'desc'  => 'Request a scheduled informational callback from certified health desk personnel.',
    ],
];

$pageTitle = 'Programme Registration Form';
$pageDesc  = 'Enroll in verified COVID-19 health information webinars, teleconsultation callbacks, and community volunteer support.';
$pageScript = ['forms.js'];

require_once APP_ROOT . '/includes/layout_header.php';
?>

<div class="page-header bg-surface-2">
  <div class="container">
    <div class="page-header-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="/index.php">Home</a></li>
          <li aria-current="page">Programme Registration</li>
        </ol>
      </nav>
      <h1 class="page-title">Community Programme Registration</h1>
      <p class="page-subtitle">
        Enroll in official COVID-19 educational webinars, guidance support sessions, and volunteer teams.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <?php if ($successRef !== null): ?>
    <!-- Success Confirmation Card -->
    <div class="registration-success-card card max-w-2xl mx-auto">
      <div class="card-header bg-ok-soft text-center py-6 border-b">
        <div class="success-icon-wrap mb-2">
          <svg class="icon icon-xl text-ok" aria-hidden="true"><use href="#icon-check-circle"></use></svg>
        </div>
        <span class="badge badge-ok mb-2">Registration Confirmed</span>
        <h2 class="card-title text-2xl">Registration Reference: <code><?= e($successRef) ?></code></h2>
        <p class="text-sm text-muted">A confirmation record has been saved securely to your account profile.</p>
      </div>
      <div class="card-body">
        <div class="summary-details-list">
          <div class="summary-row">
            <span class="summary-label">Selected Programme:</span>
            <strong><?= e($servicesMeta[$submittedData['service']]['title'] ?? $submittedData['service']) ?></strong>
          </div>
          <div class="summary-row">
            <span class="summary-label">Registrant Name:</span>
            <span><?= e($submittedData['full_name']) ?></span>
          </div>
          <div class="summary-row">
            <span class="summary-label">Registered Email:</span>
            <span><?= e($submittedData['email']) ?></span>
          </div>
          <div class="summary-row">
            <span class="summary-label">Normalised Phone:</span>
            <span><?= e($submittedData['phone']) ?></span>
          </div>
          <div class="summary-row">
            <span class="summary-label">Date of Birth:</span>
            <span><?= e($submittedData['dob']) ?></span>
          </div>
          <div class="summary-row">
            <span class="summary-label">State / Region:</span>
            <span><?= e($submittedData['state']) ?> &bull; PIN <?= e($submittedData['pin_code']) ?></span>
          </div>
        </div>

        <div class="alert alert-info mt-4">
          <svg class="icon icon-sm text-info" aria-hidden="true"><use href="#icon-info"></use></svg>
          <div class="text-xs">
            <strong>Notice:</strong> This registration reserves participation in health educational sessions and volunteer programmes. It does not constitute a formal clinical appointment booking.
          </div>
        </div>

        <div class="flex gap-3 justify-center mt-6">
          <a href="/profile.php" class="btn btn-primary">View in My Profile</a>
          <a href="/index.php" class="btn btn-outline">Return to Home</a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <!-- Form Card -->
    <div class="registration-form-wrap max-w-3xl mx-auto">
      <div class="card">
        <div class="card-header border-b">
          <h2 class="card-title text-xl">Enrollment Details</h2>
          <p class="card-desc text-sm">
            All fields marked with an asterisk (<span class="text-danger">*</span>) are mandatory and validated client &amp; server side.
          </p>
        </div>

        <div class="card-body">
          <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger mb-4" role="alert"><?= e($errors['general']) ?></div>
          <?php endif; ?>

          <form action="/program-registration.php" method="POST" id="reg-form" novalidate>
            <?= csrf_field() ?>

            <!-- Row 1: Full Name & Email -->
            <div class="grid grid-2 gap-4 mb-4">
              <div class="form-group">
                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  name="full_name" 
                  id="full_name" 
                  class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
                  value="<?= e($fullName) ?>" 
                  required 
                  placeholder="e.g. Suresh Patil"
                >
                <?php if (isset($errors['full_name'])): ?>
                  <p class="form-error">✕ <?= e($errors['full_name']) ?></p>
                <?php endif; ?>
              </div>

              <div class="form-group">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input 
                  type="email" 
                  name="email" 
                  id="email" 
                  class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                  value="<?= e($email) ?>" 
                  required 
                  placeholder="name@example.com"
                >
                <?php if (isset($errors['email'])): ?>
                  <p class="form-error">✕ <?= e($errors['email']) ?></p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Row 2: Phone & Date of Birth -->
            <div class="grid grid-2 gap-4 mb-4">
              <div class="form-group">
                <label for="phone" class="form-label">Mobile Phone (India) <span class="text-danger">*</span></label>
                <input 
                  type="tel" 
                  name="phone" 
                  id="phone" 
                  class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                  value="<?= e($phone) ?>" 
                  required 
                  placeholder="e.g. +91 9876543210 or 9876543210"
                >
                <p class="form-hint text-xs text-muted mt-1">10-digit Indian mobile number (optional +91 or 0 prefix).</p>
                <?php if (isset($errors['phone'])): ?>
                  <p class="form-error">✕ <?= e($errors['phone']) ?></p>
                <?php endif; ?>
              </div>

              <div class="form-group">
                <label for="dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                <input 
                  type="date" 
                  name="dob" 
                  id="dob" 
                  class="form-control <?= isset($errors['dob']) ? 'is-invalid' : '' ?>" 
                  value="<?= e($dob) ?>" 
                  required 
                  placeholder="YYYY-MM-DD"
                >
                <p class="form-hint text-xs text-muted mt-1">Format: YYYY-MM-DD (Age must be between 5 and 120).</p>
                <?php if (isset($errors['dob'])): ?>
                  <p class="form-error">✕ <?= e($errors['dob']) ?></p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Row 3: Gender, PIN Code & State -->
            <div class="grid grid-3 gap-4 mb-5">
              <div class="form-group">
                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                <select name="gender" id="gender" class="form-control <?= isset($errors['gender']) ? 'is-invalid' : '' ?>" required>
                  <option value="">Select gender</option>
                  <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Female</option>
                  <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>Male</option>
                  <option value="other" <?= $gender === 'other' ? 'selected' : '' ?>>Other</option>
                  <option value="prefer_not_to_say" <?= $gender === 'prefer_not_to_say' ? 'selected' : '' ?>>Prefer not to say</option>
                </select>
                <?php if (isset($errors['gender'])): ?>
                  <p class="form-error">✕ <?= e($errors['gender']) ?></p>
                <?php endif; ?>
              </div>

              <div class="form-group">
                <label for="pin_code" class="form-label">PIN Code <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  name="pin_code" 
                  id="pin_code" 
                  class="form-control <?= isset($errors['pin_code']) ? 'is-invalid' : '' ?>" 
                  value="<?= e($pinCode) ?>" 
                  required 
                  maxlength="6" 
                  placeholder="411001"
                >
                <p class="form-hint text-xs text-muted mt-1">6 digits, starting 1–9.</p>
                <?php if (isset($errors['pin_code'])): ?>
                  <p class="form-error">✕ <?= e($errors['pin_code']) ?></p>
                <?php endif; ?>
              </div>

              <div class="form-group">
                <label for="state" class="form-label">State / UT <span class="text-danger">*</span></label>
                <select name="state" id="state" class="form-control <?= isset($errors['state']) ? 'is-invalid' : '' ?>" required>
                  <option value="">Select state/UT</option>
                  <?php foreach ($indianStates as $st): ?>
                    <option value="<?= e($st) ?>" <?= $state === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($errors['state'])): ?>
                  <p class="form-error">✕ <?= e($errors['state']) ?></p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Service Radio Cards -->
            <div class="form-group mb-5">
              <label class="form-label block font-semibold mb-2">Select Programme Service <span class="text-danger">*</span></label>
              <?php if (isset($errors['service'])): ?>
                <div class="alert alert-danger mb-3 py-2 text-xs">✕ <?= e($errors['service']) ?></div>
              <?php endif; ?>

              <div class="service-radio-grid">
                <?php foreach ($servicesMeta as $sKey => $sVal): ?>
                  <label class="service-radio-card <?= $service === $sKey ? 'selected' : '' ?>">
                    <input 
                      type="radio" 
                      name="service" 
                      value="<?= e($sKey) ?>" 
                      <?= $service === $sKey ? 'checked' : '' ?> 
                      required
                    >
                    <div class="service-card-inner">
                      <span class="service-card-title"><?= e($sVal['title']) ?></span>
                      <span class="service-card-desc"><?= e($sVal['desc']) ?></span>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Consent Checkbox -->
            <div class="form-group mb-6">
              <label class="checkbox-label flex items-start gap-2">
                <input 
                  type="checkbox" 
                  name="consent" 
                  id="consent" 
                  value="1" 
                  <?= $consent ? 'checked' : '' ?> 
                  required
                >
                <span class="text-sm">
                  <strong>Consent Agreement:</strong> I agree that my details are stored in accordance with portal data minimisation standards solely to contact me about this selected public health programme.
                </span>
              </label>
              <?php if (isset($errors['consent'])): ?>
                <p class="form-error mt-1">✕ <?= e($errors['consent']) ?></p>
              <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Submit Programme Registration</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
