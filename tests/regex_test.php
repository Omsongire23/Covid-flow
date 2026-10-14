<?php
declare(strict_types=1);

/**
 * regex_test.php — Automated regular expression test suite runner for PHP (PCRE ~...~Du).
 */

$rootDir = dirname(__DIR__);
$patternsPath = $rootDir . '/config/patterns.json';
$casesPath = $rootDir . '/tests/regex-cases.json';

$patterns = json_decode(file_get_contents($patternsPath), true);
$cases = json_decode(file_get_contents($casesPath), true);

function expand_input(array $c): string {
    if (isset($c['in']) && is_string($c['in'])) {
        return $c['in'];
    }
    if (isset($c['gen'])) {
        $p = $c['gen']['prefix'] ?? '';
        $r = $c['gen']['repeat'] ?? '';
        $t = (int)($c['gen']['times'] ?? 0);
        $s = $c['gen']['suffix'] ?? '';
        return $p . str_repeat($r, $t) . $s;
    }
    return '';
}

function esc_inv(string $str): string {
    return str_replace(["\n", "\r", "\t"], ['\n', '\r', '\t'], $str);
}

echo "================================================================\n";
echo "  COVID-19 Portal Regex Test Suite — PHP PCRE (~...~Du)\n";
echo "================================================================\n\n";

$total = 0;
$passed = 0;
$failed = 0;
$failures = [];

foreach ($cases as $field => $fieldCases) {
    if (!isset($patterns[$field])) {
        fwrite(STDERR, "Missing pattern configuration for field: {$field}\n");
        exit(1);
    }
    $pattern = $patterns[$field]['pattern'];

    foreach ($fieldCases as $idx => $c) {
        $total++;
        $numStr = str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
        $id = "TC-REG-" . strtoupper($field) . "-{$numStr}";
        $inputVal = expand_input($c);

        $res = preg_match('~' . $pattern . '~Du', $inputVal);
        $actual = ($res === 1);
        $expected = (bool)$c['ok'];
        $isPass = ($actual === $expected);

        if ($isPass) {
            $passed++;
        } else {
            $failed++;
            $failures[] = [
                'id' => $id,
                'field' => $field,
                'input' => esc_inv(substr($inputVal, 0, 40)),
                'expected' => $expected ? 'VALID' : 'INVALID',
                'actual' => $actual ? 'VALID' : 'INVALID',
                'note' => $c['note'] ?? ''
            ];
        }
    }
}

// ReDoS performance checks
$hostile = [
    ['field' => 'comment', 'in' => str_repeat('<', 100000), 'desc' => '100k hostile < characters'],
    ['field' => 'email', 'in' => str_repeat('a.', 50000), 'desc' => '100k hostile dots'],
    ['field' => 'username', 'in' => str_repeat('A', 100000), 'desc' => '100k letters']
];

echo "Running ReDoS & Backtracking Protection Benchmarks:\n";
foreach ($hostile as $ht) {
    $total++;
    $id = "TC-REG-REDOS-" . strtoupper($ht['field']);
    $pattern = $patterns[$ht['field']]['pattern'];

    $t0 = microtime(true);
    $res = preg_match('~' . $pattern . '~Du', $ht['in']);
    $duration = (microtime(true) - $t0) * 1000;
    $actual = ($res === 1);
    $isPass = (!$actual && $duration < 200);

    if ($isPass) {
        $passed++;
        printf("  ✓ %s: %s completed in %.2fms (threshold < 200ms)\n", $id, $ht['desc'], $duration);
    } else {
        $failed++;
        $failures[] = [
            'id' => $id,
            'field' => $ht['field'],
            'input' => '[Hostile string]',
            'expected' => 'INVALID',
            'actual' => $actual ? 'VALID' : 'INVALID',
            'note' => sprintf("ReDoS exceeded threshold: %.2fms", $duration)
        ];
    }
}

echo "\n----------------------------------------------------------------\n";
printf("Summary: Total: %d | Passed: %d | Failed: %d\n", $total, $passed, $failed);
echo "----------------------------------------------------------------\n\n";

if ($failed > 0) {
    echo "FAILURES DETECTED:\n";
    foreach ($failures as $f) {
        printf("  [%s] %s: Expected %s, got %s (Input: %s) Note: %s\n",
            $f['id'], $f['field'], $f['expected'], $f['actual'], $f['input'], $f['note']);
    }
    exit(1);
} else {
    echo "✓ All regex test cases and ReDoS checks PASSED successfully in PHP.\n";
    exit(0);
}
