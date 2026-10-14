<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$u = current_user();
$pageTitle = 'Community Discussions & Updates';
$pageDesc  = 'Public community questions, observations, and discussion regarding pandemic guidelines and local resources.';
$pageScript = ['comments.js'];

require_once APP_ROOT . '/includes/layout_header.php';
?>

<div class="page-header bg-surface-2">
  <div class="container">
    <div class="page-header-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="/index.php">Home</a></li>
          <li aria-current="page">Community</li>
        </ol>
      </nav>
      <div class="header-meta-row">
        <h1 class="page-title">Community Discussions &amp; Insights</h1>
        <span class="badge badge-info">Public Discussion Board</span>
      </div>
      <p class="page-subtitle">
        Share questions, local health clinic experiences, and community support advice. All posts are sanitized and validated.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <div class="community-layout-grid">
    <!-- Left Column: Composer / Auth Prompt -->
    <div class="community-composer-col">
      <?php if ($u !== null): ?>
        <div class="card mb-6" id="composer-card">
          <div class="card-header border-b">
            <h2 class="card-title text-lg">Post to Discussion</h2>
            <p class="card-desc text-xs">Signed in as <strong><?= e($u['username']) ?></strong></p>
          </div>
          <div class="card-body">
            <form id="comment-form" method="POST" action="/api/comments.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="create">

              <div class="form-group mb-3">
                <label for="comment-body" class="form-label sr-only">Your Comment</label>
                <textarea 
                  name="body" 
                  id="comment-body" 
                  class="form-control" 
                  rows="4" 
                  maxlength="500" 
                  placeholder="Share a community question or update (3–500 characters, no HTML tags)..." 
                  required
                ></textarea>
                <div class="textarea-meta-bar mt-2 flex justify-between items-center text-xs text-muted">
                  <span id="char-counter" class="char-count">0 / 500 characters</span>
                  <span class="comment-guideline">HTML tags disallowed</span>
                </div>
              </div>

              <div class="flex justify-end">
                <button type="submit" class="btn btn-primary" id="btn-submit-comment">
                  <svg class="icon icon-xs" aria-hidden="true"><use href="#icon-send"></use></svg>
                  Post Comment
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php else: ?>
        <div class="card mb-6 bg-surface-2 text-center p-6">
          <div class="community-auth-icon mb-3">
            <svg class="icon icon-xl text-primary" aria-hidden="true"><use href="#icon-chat"></use></svg>
          </div>
          <h2 class="card-title text-xl mb-2">Join the Conversation</h2>
          <p class="text-sm text-muted mb-4">
            Sign in to your account or register to contribute questions and community support updates.
          </p>
          <div class="flex justify-center gap-3">
            <a href="/login.php?return=/community.php" class="btn btn-outline btn-sm">Log In</a>
            <a href="/signup.php" class="btn btn-primary btn-sm">Create Account</a>
          </div>
        </div>
      <?php endif; ?>

      <!-- Community Rules Card -->
      <div class="card mb-6">
        <div class="card-header border-b">
          <h3 class="card-title text-base">Community Guidelines</h3>
        </div>
        <div class="card-body text-xs text-muted leading-relaxed">
          <ul class="guidelines-list">
            <li>&bull; Be courteous and supportive to fellow community members.</li>
            <li>&bull; Do not post medical prescriptions or unsolicited clinical advice.</li>
            <li>&bull; Misinformation regarding treatments or vaccines is moderated.</li>
            <li>&bull; Plain text only (HTML tags are rejected by regex).</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Right Column: Comments Feed -->
    <div class="community-feed-col">
      <div class="card">
        <div class="card-header border-b flex justify-between items-center">
          <h2 class="card-title text-lg">Recent Community Posts</h2>
          <span class="text-xs text-muted">Real-time Feed</span>
        </div>
        <div class="card-body p-0">
          <div class="comments-list-wrap" id="comments-list">
            <div class="loading-state py-8 text-center text-muted">
              <div class="loading-spinner"></div>
              <p class="mt-2 text-sm">Loading discussions...</p>
            </div>
          </div>
        </div>
        <div class="card-footer border-t text-center py-3" id="load-more-wrap">
          <button type="button" class="btn btn-outline btn-sm" id="load-more-btn">Load More Comments</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
