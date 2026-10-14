<?php
declare(strict_types=1);

/**
 * database/create_admin.php — CLI script to create an administrator account.
 * Prompts for username, email, and password, validates with shared patterns,
 * hashes the password with PASSWORD_DEFAULT, and inserts into `users` table with role 'admin'.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("This utility can only be executed via the command line (CLI).\n");
}

require_once dirname(__DIR__) . '/includes/bootstrap.php';

echo "================================================================\n";
echo "  COVID-19 Portal — Interactive Admin User Provisioning CLI\n";
echo "================================================================\n\n";

function prompt(string $question): string {
    echo $question . ': ';
    $handle = fopen('php://stdin', 'r');
    $line = fgets($handle);
    fclose($handle);
    return trim((string)$line);
}

function prompt_hidden(string $question): string {
    echo $question . ': ';
    // On UNIX, attempt stty -echo
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        system('stty -echo');
        $handle = fopen('php://stdin', 'r');
        $line = fgets($handle);
        fclose($handle);
        system('stty echo');
        echo "\n";
        return trim((string)$line);
    }
    $handle = fopen('php://stdin', 'r');
    $line = fgets($handle);
    fclose($handle);
    return trim((string)$line);
}

$pdo = db();

// 1. Username
while (true) {
    $username = prompt('Enter admin username');
    if (!validate_field('username', $username)) {
        echo "  [Error] " . validate_message('username') . "\n";
        continue;
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo "  [Error] Username '{$username}' is already in use. Please choose another.\n";
        continue;
    }
    break;
}

// 2. Email
while (true) {
    $email = strtolower(prompt('Enter admin email address'));
    if (!validate_field('email', $email)) {
        echo "  [Error] " . validate_message('email') . "\n";
        continue;
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "  [Error] Email '{$email}' is already associated with an existing account.\n";
        continue;
    }
    break;
}

// 3. Password
while (true) {
    $password = prompt_hidden('Enter secure admin password');
    if (!validate_field('password', $password)) {
        echo "  [Error] " . validate_message('password') . "\n";
        continue;
    }
    $confirm = prompt_hidden('Confirm admin password');
    if ($password !== $confirm) {
        echo "  [Error] Passwords do not match. Please try again.\n";
        continue;
    }
    break;
}

// Insert admin user
$pwHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, "admin")');
$stmt->execute([$username, $email, $pwHash]);

echo "\n✓ Administrator account '{$username}' successfully created with role 'admin'!\n";
echo "  You may now log in at /login.php to access administrator moderation.\n\n";
