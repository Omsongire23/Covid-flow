<?php
declare(strict_types=1);

/**
 * validator_test.php — Automated semantic validator tests.
 */

define('APP_ROOT', dirname(__DIR__));
define('APP_CFG', ['base_url' => 'http://127.0.0.1:8000']);

require_once APP_ROOT . '/includes/validate.php';

echo "================================================================\n";
echo "  COVID-19 Portal Semantic Validator Test Suite\n";
echo "================================================================\n\n";

$tests = [];
function test(string $id, string $desc, bool $condition, string $details = ''): void {
    global $tests;
    $tests[] = ['id' => $id, 'desc' => $desc, 'passed' => $condition, 'details' => $details];
}

// 1. DOB semantic checks
test('TC-SEM-DOB-01', 'Valid calendar date and age (e.g. 2000-01-31)', validate_dob('2000-01-31') === true);
test('TC-SEM-DOB-02', 'Non-existent calendar date (2023-02-31) rejected by checkdate', validate_dob('2023-02-31') === 'That date does not exist on the calendar.');
test('TC-SEM-DOB-03', 'Valid leap day (2020-02-29) accepted', validate_dob('2020-02-29') === true);
test('TC-SEM-DOB-04', 'Invalid leap day in non-leap year (2023-02-29) rejected', validate_dob('2023-02-29') === 'That date does not exist on the calendar.');

$tomorrow = (new DateTimeImmutable('+1 day'))->format('Y-m-d');
test('TC-SEM-DOB-05', 'Future date of birth rejected', validate_dob($tomorrow) === 'Date of birth cannot be in the future.');

$underAge = (new DateTimeImmutable('-4 years'))->format('Y-m-d');
test('TC-SEM-DOB-06', 'Age < 5 rejected', validate_dob($underAge) === 'You must be at least 5 years old to register.');

$overAge = (new DateTimeImmutable('-121 years'))->format('Y-m-d');
test('TC-SEM-DOB-07', 'Age > 120 rejected', validate_dob($overAge) === 'Age must be 120 or younger.');

// 2. Phone normalisation
test('TC-SEM-PHN-01', 'Normalises "+91 9876543210" to "+919876543210"', normalise_phone('+91 9876543210') === '+919876543210');
test('TC-SEM-PHN-02', 'Normalises "+91-9876543210" to "+919876543210"', normalise_phone('+91-9876543210') === '+919876543210');
test('TC-SEM-PHN-03', 'Preserves standard 10-digit number "9876543210"', normalise_phone('9876543210') === '9876543210');
test('TC-SEM-PHN-04', 'Rejects invalid phone number', normalise_phone('12345') === false);

// 3. State and Service allow-lists
test('TC-SEM-ST-01', 'Valid state "Maharashtra" accepted', validate_state('Maharashtra') === true);
test('TC-SEM-ST-02', 'Invalid state "California" rejected', validate_state('California') === false);
test('TC-SEM-SVC-01', 'Valid service "vaccination_info" accepted', validate_service('vaccination_info') === true);
test('TC-SEM-SVC-02', 'Invalid service "surgery" rejected', validate_service('surgery') === false);

// 4. Comment normalisation & validation
$c1 = validate_comment("Line 1\r\nLine 2");
test('TC-SEM-CMT-01', 'Normalises CRLF to LF in comments', $c1['ok'] === true && $c1['value'] === "Line 1\nLine 2");

$c2 = validate_comment("<script>alert(1)</script>");
test('TC-SEM-CMT-02', 'Rejects HTML tags in comments', $c2['ok'] === false);

$c3 = validate_comment(str_repeat('x', 501));
test('TC-SEM-CMT-03', 'Rejects comments over 500 characters', $c3['ok'] === false);

// Print results
$total = count($tests);
$passed = 0;
$failed = 0;

foreach ($tests as $t) {
    if ($t['passed']) {
        $passed++;
        printf("  ✓ %s: %s\n", $t['id'], $t['desc']);
    } else {
        $failed++;
        printf("  ✕ %s: %s (FAILED)\n", $t['id'], $t['desc']);
    }
}

echo "\n----------------------------------------------------------------\n";
printf("Summary: Total: %d | Passed: %d | Failed: %d\n", $total, $passed, $failed);
echo "----------------------------------------------------------------\n\n";

if ($failed > 0) {
    exit(1);
} else {
    echo "✓ All semantic validator tests PASSED.\n";
    exit(0);
}
