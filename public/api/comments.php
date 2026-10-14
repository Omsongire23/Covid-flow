<?php
declare(strict_types=1);

define('API_ENDPOINT', true);
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$u = current_user();

if ($method === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $pdo = db();
    $total = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'visible'")->fetchColumn();
    $pages = max(1, (int)ceil($total / $limit));

    $stmt = $pdo->prepare(
        "SELECT c.id, c.user_id, c.body, c.created_at, u.username
         FROM comments c
         JOIN users u ON c.user_id = u.id
         WHERE c.status = 'visible'
         ORDER BY c.created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $r) {
        $items[] = [
            'id'        => (int)$r['id'],
            'username'  => (string)$r['username'],
            'body'      => (string)$r['body'],
            'createdAt' => time_ago((string)$r['created_at']),
            'mine'      => ($u !== null && (int)$u['id'] === (int)$r['user_id']),
        ];
    }

    json_response([
        'ok'   => true,
        'data' => [
            'items' => $items,
            'page'  => $page,
            'pages' => $pages,
            'total' => $total,
        ]
    ]);
}

if ($method === 'POST') {
    csrf_verify();

    if ($u === null) {
        json_response([
            'ok' => false,
            'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'You must be logged in to perform this action.']
        ], 401);
    }

    // Rate limiting for comment actions (30 per min per session)
    $maxRequests = (int)(APP_CFG['rate_limit_comment'] ?? 30);
    $now = time();
    $_SESSION['_rl_cmt'] = $_SESSION['_rl_cmt'] ?? ['count' => 0, 'reset' => $now + 60];
    if ($now > $_SESSION['_rl_cmt']['reset']) {
        $_SESSION['_rl_cmt'] = ['count' => 1, 'reset' => $now + 60];
    } else {
        $_SESSION['_rl_cmt']['count']++;
        if ($_SESSION['_rl_cmt']['count'] > $maxRequests) {
            json_response([
                'ok' => false,
                'error' => ['code' => 'RATE_LIMITED', 'message' => 'Rate limit exceeded. Please wait a moment.']
            ], 429);
        }
    }

    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $action = $payload['action'] ?? '';

    if ($action === 'create') {
        $rawBody = (string)($payload['body'] ?? '');
        $val = validate_comment($rawBody);
        if (!$val['ok']) {
            json_response([
                'ok' => false,
                'error' => ['code' => 'VALIDATION_FAILED', 'message' => $val['error']]
            ], 422);
        }

        $cleanBody = $val['value'];
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO comments (user_id, body, status) VALUES (?, ?, "visible")');
        $stmt->execute([(int)$u['id'], $cleanBody]);
        $newId = (int)$pdo->lastInsertId();

        $commentObj = [
            'id'        => $newId,
            'username'  => $u['username'],
            'body'      => $cleanBody,
            'createdAt' => 'just now',
            'mine'      => true,
        ];

        json_response([
            'ok'      => true,
            'comment' => $commentObj,
            'data'    => $commentObj,
        ]);
    }

    if ($action === 'delete') {
        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) {
            json_response(['ok' => false, 'error' => ['code' => 'INVALID_ID', 'message' => 'Invalid comment ID.']], 400);
        }

        $pdo = db();
        $stmt = $pdo->prepare('SELECT id, user_id FROM comments WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            json_response(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Comment not found.']], 404);
        }

        $isOwner = ((int)$row['user_id'] === (int)$u['id']);
        $isAdmin = (($u['role'] ?? '') === 'admin');

        if (!$isOwner && !$isAdmin) {
            json_response([
                'ok' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have permission to delete this comment.']
            ], 403);
        }

        // Soft delete
        $del = $pdo->prepare('UPDATE comments SET status = "deleted" WHERE id = ?');
        $del->execute([$id]);

        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'error' => ['code' => 'INVALID_ACTION', 'message' => 'Action must be create or delete.']], 400);
}

http_response_code(405);
header('Allow: GET, POST');
exit('405 Method Not Allowed');
