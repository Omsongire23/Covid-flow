<?php
declare(strict_types=1);

/**
 * auth.php — Session auth: current user, login, logout, throttling.
 */

/** Return the current logged-in user array or null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Redirect to login if not authenticated. */
function require_login(string $returnTo = ''): void
{
    if (current_user() === null) {
        $rt = $returnTo ?: ($_SERVER['REQUEST_URI'] ?? '/');
        flash('info', 'Please log in to continue.');
        redirect('/login.php?return=' . urlencode($rt));
    }
}

/** Redirect away if already logged in (for login/signup pages). */
function require_guest(): void
{
    if (current_user() !== null) {
        redirect('/');
    }
}

/** Require admin role. */
function require_admin(): void
{
    $u = current_user();
    if ($u === null) {
        require_login();
    }
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        exit('403 Forbidden');
    }
}

/**
 * Check whether the (username, IP) pair is rate-limited.
 * Returns true if the user may attempt, false if throttled.
 * Also opportunistically purges old attempts.
 */
function login_throttle_check(string $username, string $ip): bool
{
    $pdo    = db();
    $max    = (int)(APP_CFG['login_max_attempts'] ?? 5);
    $window = (int)(APP_CFG['login_window_sec']   ?? 900);

    // Purge old attempts (older than 24h) — opportunistic
    $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 86400 SECOND')->execute();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts
          WHERE username = ? AND ip = ?
            AND attempted_at >= NOW() - INTERVAL ? SECOND'
    );
    $stmt->execute([$username, $ip, $window]);
    return (int)$stmt->fetchColumn() < $max;
}

/** Record a failed login attempt. */
function login_attempt_record(string $username, string $ip): void
{
    $pdo = db();
    $pdo->prepare('INSERT INTO login_attempts (username, ip) VALUES (?, ?)')->execute([$username, $ip]);
}

/**
 * Attempt to log in with username + password.
 * Returns ['ok' => true, 'user' => [...]] or ['ok' => false, 'error' => '...'].
 * Always returns a generic message to prevent user enumeration.
 */
function attempt_login(string $username, string $password): array
{
    $genericError = 'Invalid username or password.';
    $ip           = client_ip();

    // Throttle check
    if (!login_throttle_check($username, $ip)) {
        return ['ok' => false, 'error' => 'Too many failed attempts. Please wait 15 minutes before trying again.', 'throttled' => true];
    }

    $pdo  = db();
    $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Run verify even when user not found to prevent timing-based enumeration
    $hashToVerify = $user ? $user['password_hash'] : '$2y$10$dummyhashforenumerationprotection000000000000000000000';
    $valid        = password_verify($password, $hashToVerify);

    if (!$user || !$valid) {
        login_attempt_record($username, $ip);
        return ['ok' => false, 'error' => $genericError];
    }

    // Rehash if needed
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $new = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$new, $user['id']]);
    }

    // Successful login — regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'       => (int)$user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $user['role'],
    ];

    // Update last_login_at
    $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

    return ['ok' => true, 'user' => $_SESSION['user']];
}

/** Log out: destroy session. */
function logout(): void
{
    session_unset();
    session_destroy();
    // Restart session for flash message
    session_start();
    session_regenerate_id(true);
    flash('success', 'You have been logged out.');
}
