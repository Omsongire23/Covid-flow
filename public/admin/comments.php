<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

require_admin();

$pdo = db();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');
    $commentId = (int)($_POST['comment_id'] ?? 0);

    if ($commentId > 0 && in_array($action, ['hide', 'show', 'delete', 'restore'], true)) {
        $targetStatus = match ($action) {
            'hide'    => 'hidden',
            'show'    => 'visible',
            'delete'  => 'deleted',
            'restore' => 'visible',
        };

        $stmt = $pdo->prepare('UPDATE comments SET status = ? WHERE id = ?');
        $stmt->execute([$targetStatus, $commentId]);
        flash('success', 'Comment #' . $commentId . ' updated to status: ' . $targetStatus);
        redirect('/admin/comments.php');
    }
}

$statusFilter = (string)($_GET['status'] ?? 'all');
$validFilters = ['all', 'visible', 'hidden', 'deleted'];
if (!in_array($statusFilter, $validFilters, true)) {
    $statusFilter = 'all';
}

$query = 'SELECT c.id, c.body, c.status, c.created_at, u.username, u.email
          FROM comments c
          JOIN users u ON c.user_id = u.id';

$params = [];
if ($statusFilter !== 'all') {
    $query .= ' WHERE c.status = ?';
    $params[] = $statusFilter;
}
$query .= ' ORDER BY c.created_at DESC LIMIT 100';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$comments = $stmt->fetchAll();

$pageTitle = 'Admin Comment Moderation';
$pageDesc  = 'Administrative moderation console for COVID-19 community comments.';

require_once APP_ROOT . '/includes/layout_header.php';
?>

<div class="page-header bg-surface-2">
  <div class="container">
    <div class="page-header-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="/index.php">Home</a></li>
          <li><a href="/profile.php">Profile</a></li>
          <li aria-current="page">Admin Moderation</li>
        </ol>
      </nav>
      <div class="header-meta-row">
        <h1 class="page-title">Community Comment Moderation</h1>
        <span class="badge badge-warn">Administrator Privileges Active</span>
      </div>
      <p class="page-subtitle">
        Review, hide, restore, or moderate public user comments submitted to the community discussions feed.
      </p>
    </div>
  </div>
</div>

<div class="container page-body py-6">
  <div class="card mb-6">
    <div class="card-header border-b flex justify-between items-center flex-wrap gap-3">
      <div>
        <h2 class="card-title text-lg">All Community Comments (Last 100)</h2>
        <p class="card-desc text-xs">Total displayed: <?= count($comments) ?></p>
      </div>

      <!-- Status Filter Chips -->
      <div class="flex gap-2">
        <a href="/admin/comments.php?status=all" class="btn btn-xs <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
        <a href="/admin/comments.php?status=visible" class="btn btn-xs <?= $statusFilter === 'visible' ? 'btn-primary' : 'btn-outline' ?>">Visible</a>
        <a href="/admin/comments.php?status=hidden" class="btn btn-xs <?= $statusFilter === 'hidden' ? 'btn-primary' : 'btn-outline' ?>">Hidden</a>
        <a href="/admin/comments.php?status=deleted" class="btn btn-xs <?= $statusFilter === 'deleted' ? 'btn-primary' : 'btn-outline' ?>">Deleted</a>
      </div>
    </div>

    <div class="card-body p-0">
      <?php if (empty($comments)): ?>
        <div class="empty-state py-8 text-center text-muted">
          <p>No comments found for status filter: <strong><?= e($statusFilter) ?></strong></p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th scope="col" class="th-id-sm">ID</th>
                <th scope="col" class="th-author">Author</th>
                <th scope="col">Comment Body</th>
                <th scope="col" class="th-status-sm">Status</th>
                <th scope="col" class="th-date">Posted</th>
                <th scope="col" class="text-right th-actions">Moderation</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($comments as $c): ?>
                <tr>
                  <td><code>#<?= (int)$c['id'] ?></code></td>
                  <td>
                    <strong><?= e($c['username']) ?></strong>
                    <div class="text-xs text-muted"><?= e($c['email']) ?></div>
                  </td>
                  <td class="text-sm"><?= nl2br(e($c['body'])) ?></td>
                  <td>
                    <span class="badge badge-<?= $c['status'] === 'visible' ? 'ok' : ($c['status'] === 'hidden' ? 'warn' : 'danger') ?>">
                      <?= ucfirst(e($c['status'])) ?>
                    </span>
                  </td>
                  <td class="text-xs text-muted"><?= date('M j, Y H:i', strtotime($c['created_at'])) ?></td>
                  <td class="text-right">
                    <div class="flex gap-1 justify-end">
                      <?php if ($c['status'] === 'visible'): ?>
                        <form action="/admin/comments.php" method="POST" class="inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                          <input type="hidden" name="action" value="hide">
                          <button type="submit" class="btn btn-ghost btn-xs text-warn">Hide</button>
                        </form>
                      <?php else: ?>
                        <form action="/admin/comments.php" method="POST" class="inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                          <input type="hidden" name="action" value="show">
                          <button type="submit" class="btn btn-ghost btn-xs text-ok">Show</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($c['status'] !== 'deleted'): ?>
                        <form action="/admin/comments.php" method="POST" class="inline" onsubmit="return confirm('Soft-delete this comment?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                          <input type="hidden" name="action" value="delete">
                          <button type="submit" class="btn btn-ghost btn-xs text-danger">Delete</button>
                        </form>
                      <?php else: ?>
                        <form action="/admin/comments.php" method="POST" class="inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                          <input type="hidden" name="action" value="restore">
                          <button type="submit" class="btn btn-ghost btn-xs text-info">Restore</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once APP_ROOT . '/includes/layout_footer.php'; ?>
