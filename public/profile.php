<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_login();

$u = current_user();
$pdo = db();
$userId = (int)$u['id'];

// Handle registration cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_reg') {
    csrf_verify();
    $regId = (int)($_POST['reg_id'] ?? 0);
    if ($regId > 0) {
        $cancelStmt = $pdo->prepare('UPDATE registrations SET status = "cancelled" WHERE id = ? AND user_id = ?');
        $cancelStmt->execute([$regId, $userId]);
        flash('info', 'Your programme registration has been cancelled.');
        redirect('/profile.php');
    }
}

// Fetch user profile details
$userStmt = $pdo->prepare('SELECT id, username, email, role, created_at, last_login_at FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$userId]);
$profile = $userStmt->fetch();

if (!$profile) {
    logout();
    redirect('/login.php');
}

// Fetch user's registrations
$regStmt = $pdo->prepare('SELECT id, full_name, email, phone, dob, gender, pin_code, state, service, status, created_at FROM registrations WHERE user_id = ? ORDER BY created_at DESC');
$regStmt->execute([$userId]);
$registrations = $regStmt->fetchAll();

// Fetch user's own comments
$cmtStmt = $pdo->prepare('SELECT id, body, status, created_at FROM comments WHERE user_id = ? AND status != "deleted" ORDER BY created_at DESC');
$cmtStmt->execute([$userId]);
$userComments = $cmtStmt->fetchAll();

$serviceLabels = [
    'vaccination_info'   => 'Vaccination Guidance & Clinic Support',
    'awareness_webinar'  => 'COVID-19 Awareness & Science Webinar',
    'volunteer'          => 'Community Health Volunteer Programme',
    'helpline_callback'  => 'Helpline Clinical Follow-up Callback',
];

$pageTitle = 'Account Profile & Registrations';
$pageDesc  = 'View your COVID-19 Portal account details, registered programmes, and community contributions.';

require_once APP_ROOT . '/includes/layout_header.php';
?>

<div class="page-header bg-surface-2">
  <div class="container">
    <div class="page-header-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="/index.php">Home</a></li>
          <li aria-current="page">Profile</li>
        </ol>
      </nav>
      <div class="header-meta-row">
        <h1 class="page-title">Account Profile &amp; Activities</h1>
        <span class="badge badge-primary"><?= ucfirst(e($profile['role'])) ?> Account</span>
      </div>
      <p class="page-subtitle">
        Manage your registered COVID-19 programmes, review community contributions, and inspect your account security telemetry.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <!-- Profile Overview Card -->
  <div class="card mb-6">
    <div class="card-header border-b flex justify-between items-center">
      <div class="flex items-center gap-3">
        <div class="user-avatar-large">
          <svg class="icon icon-lg" aria-hidden="true"><use href="#icon-user"></use></svg>
        </div>
        <div>
          <h2 class="card-title text-xl"><?= e($profile['username']) ?></h2>
          <p class="text-xs text-muted"><?= e($profile['email']) ?></p>
        </div>
      </div>
      <div class="flex gap-2">
        <?php if ($profile['role'] === 'admin'): ?>
          <a href="/admin/comments.php" class="btn btn-outline btn-sm">Admin Moderation</a>
        <?php endif; ?>
        <form action="/logout.php" method="POST" class="inline">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-ghost btn-sm text-danger">Log Out</button>
        </form>
      </div>
    </div>
    <div class="card-body">
      <div class="grid grid-3 text-sm">
        <div class="profile-meta-item">
          <span class="text-muted block text-xs">Account Role</span>
          <strong><?= ucfirst(e($profile['role'])) ?></strong>
        </div>
        <div class="profile-meta-item">
          <span class="text-muted block text-xs">Member Since</span>
          <strong><?= date('M j, Y H:i', strtotime($profile['created_at'])) ?></strong>
        </div>
        <div class="profile-meta-item">
          <span class="text-muted block text-xs">Last Successful Login</span>
          <strong><?= $profile['last_login_at'] ? date('M j, Y H:i', strtotime($profile['last_login_at'])) : 'Initial session' ?></strong>
        </div>
      </div>
    </div>
  </div>

  <!-- Programme Registrations Section -->
  <section class="card mb-6" aria-labelledby="reg-heading">
    <div class="card-header border-b flex justify-between items-center">
      <div>
        <h2 id="reg-heading" class="card-title text-lg">My Programme Registrations</h2>
        <p class="card-desc text-xs">Information sessions, webinars, and volunteer enrollments linked to your account.</p>
      </div>
      <a href="/program-registration.php" class="btn btn-primary btn-sm">
        <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-plus"></use></svg>
        New Registration
      </a>
    </div>
    <div class="card-body p-0">
      <?php if (empty($registrations)): ?>
        <div class="empty-state py-6 text-center">
          <svg class="icon icon-xl text-muted mb-2" aria-hidden="true"><use href="#icon-clipboard"></use></svg>
          <h3 class="text-base font-semibold">No active registrations</h3>
          <p class="text-sm text-muted mb-4">You have not enrolled in any COVID-19 health programmes or awareness sessions yet.</p>
          <a href="/program-registration.php" class="btn btn-outline btn-sm">Enroll in a Programme</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th scope="col">Reference</th>
                <th scope="col">Programme</th>
                <th scope="col">State / Location</th>
                <th scope="col">Registered On</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($registrations as $reg): ?>
                <?php 
                  $refCode = sprintf('REG-%06d', (int)$reg['id']);
                  $isActive = ($reg['status'] === 'active');
                ?>
                <tr>
                  <td><code><?= e($refCode) ?></code></td>
                  <td><strong><?= e($serviceLabels[$reg['service']] ?? $reg['service']) ?></strong></td>
                  <td><?= e($reg['state']) ?> (<?= e($reg['pin_code']) ?>)</td>
                  <td class="text-sm text-muted"><?= date('M j, Y', strtotime($reg['created_at'])) ?></td>
                  <td>
                    <span class="badge badge-<?= $isActive ? 'ok' : 'danger' ?>">
                      <?= ucfirst(e($reg['status'])) ?>
                    </span>
                  </td>
                  <td class="text-right">
                    <?php if ($isActive): ?>
                      <form action="/profile.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this registration?');" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel_reg">
                        <input type="hidden" name="reg_id" value="<?= (int)$reg['id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-xs text-danger">Cancel</button>
                      </form>
                    <?php else: ?>
                      <span class="text-xs text-muted">Cancelled</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- My Comments Section -->
  <section class="card" aria-labelledby="comments-heading">
    <div class="card-header border-b flex justify-between items-center">
      <div>
        <h2 id="comments-heading" class="card-title text-lg">My Community Comments</h2>
        <p class="card-desc text-xs">Public questions and updates posted to the Community discussion page.</p>
      </div>
      <a href="/community.php" class="btn btn-outline btn-sm">Community Page</a>
    </div>
    <div class="card-body p-0">
      <?php if (empty($userComments)): ?>
        <div class="empty-state py-6 text-center">
          <svg class="icon icon-xl text-muted mb-2" aria-hidden="true"><use href="#icon-chat"></use></svg>
          <h3 class="text-base font-semibold">No comments posted</h3>
          <p class="text-sm text-muted mb-4">You have not posted any comments in the public community discussions.</p>
          <a href="/community.php" class="btn btn-outline btn-sm">Go to Community</a>
        </div>
      <?php else: ?>
        <ul class="profile-comments-list">
          <?php foreach ($userComments as $cmt): ?>
            <li class="profile-comment-item border-b p-4">
              <div class="flex justify-between items-start mb-2">
                <span class="badge badge-<?= $cmt['status'] === 'visible' ? 'ok' : 'warn' ?> text-xs">
                  <?= ucfirst(e($cmt['status'])) ?>
                </span>
                <span class="text-xs text-muted"><?= time_ago($cmt['created_at']) ?></span>
              </div>
              <p class="comment-text text-sm mb-0"><?= nl2br(e($cmt['body'])) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
