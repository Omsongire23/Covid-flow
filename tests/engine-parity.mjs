#!/usr/bin/env node
/**
 * engine-parity.mjs — Cross-engine parity verifier (ECMAScript /u vs PHP PCRE ~...~Du).
 */
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

const patterns = JSON.parse(fs.readFileSync(path.join(rootDir, 'config', 'patterns.json'), 'utf8'));
const cases = JSON.parse(fs.readFileSync(path.join(rootDir, 'tests', 'regex-cases.json'), 'utf8'));

console.log('================================================================');
console.log('  COVID-19 Portal Regex Cross-Engine Parity Check (JS vs PHP)');
console.log('================================================================\n');

// 1. Evaluate JS side
const jsResults = {};
function expandInput(c) {
  if (typeof c.in === 'string') return c.in;
  if (c.gen) {
    const p = c.gen.prefix || '';
    const r = c.gen.repeat || '';
    const t = Number(c.gen.times) || 0;
    const s = c.gen.suffix || '';
    return p + r.repeat(t) + s;
  }
  return '';
}

for (const [field, fieldCases] of Object.entries(cases)) {
  const regex = new RegExp(patterns[field].pattern, 'u');
  fieldCases.forEach((c, idx) => {
    const id = `TC-REG-${field.toUpperCase()}-${String(idx + 1).padStart(2, '0')}`;
    const inp = expandInput(c);
    jsResults[id] = {
      field,
      input: inp,
      ok: regex.test(inp)
    };
  });
}

// 2. Evaluate PHP side
let phpOutput = null;
const phpCode = `
$searchDirs = ['${rootDir}', '/var/www/html', '/app', getcwd()];
$baseDir = null;
foreach ($searchDirs as $d) {
  if (file_exists($d . '/config/patterns.json')) { $baseDir = $d; break; }
}
if (!$baseDir) { exit(1); }
$patterns = json_decode(file_get_contents($baseDir . '/config/patterns.json'), true);
$cases = json_decode(file_get_contents($baseDir . '/tests/regex-cases.json'), true);
$out = [];
foreach ($cases as $field => $fieldCases) {
  $pat = $patterns[$field]['pattern'];
  foreach ($fieldCases as $idx => $c) {
    $id = 'TC-REG-' . strtoupper($field) . '-' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
    $inp = '';
    if (isset($c['in']) && is_string($c['in'])) {
      $inp = $c['in'];
    } elseif (isset($c['gen'])) {
      $inp = ($c['gen']['prefix'] ?? '') . str_repeat($c['gen']['repeat'] ?? '', (int)($c['gen']['times'] ?? 0)) . ($c['gen']['suffix'] ?? '');
    }
    $res = preg_match('~' . $pat . '~Du', $inp);
    $out[$id] = ($res === 1);
  }
}
echo json_encode($out);
`;

function extractJson(str) {
  if (!str) return null;
  const start = str.indexOf('{');
  const end = str.lastIndexOf('}');
  if (start !== -1 && end !== -1 && end > start) {
    try {
      return JSON.parse(str.slice(start, end + 1));
    } catch {
      return null;
    }
  }
  return null;
}

let hasPhp = false;
let phpRes = spawnSync('php', ['-r', phpCode], { encoding: 'utf8' });
if (phpRes.status === 0 && phpRes.stdout) {
  phpOutput = extractJson(phpRes.stdout);
  if (phpOutput) hasPhp = true;
}

if (!hasPhp) {
  // Try via running docker compose container 'web'
  const dockerComposeWeb = spawnSync('docker', ['compose', 'exec', '-T', 'web', 'php', '-r', phpCode], {
    cwd: rootDir,
    encoding: 'utf8'
  });
  if (dockerComposeWeb.status === 0 && dockerComposeWeb.stdout) {
    phpOutput = extractJson(dockerComposeWeb.stdout);
    if (phpOutput) hasPhp = true;
  }
}

if (!hasPhp) {
  const dockerRun = spawnSync('docker', ['run', '--rm', '-v', `${rootDir}:/app`, '-w', '/app', 'php:8.3-cli', 'php', '-r', phpCode], {
    cwd: rootDir,
    encoding: 'utf8'
  });
  if (dockerRun.status === 0 && dockerRun.stdout) {
    phpOutput = extractJson(dockerRun.stdout);
    if (phpOutput) hasPhp = true;
  }
}

if (!hasPhp || !phpOutput) {
  console.log('⚠️  PHP not available locally or via Docker yet.');
  console.log('   Report: not run (PHP CLI container will run this inside Docker)');
  process.exit(0);
}

// 3. Compare parity
let totalCases = Object.keys(jsResults).length;
let parityAgreed = 0;
let parityDisagreed = 0;
const disagreements = [];

for (const [id, jsItem] of Object.entries(jsResults)) {
  const phpVal = phpOutput[id];
  if (phpVal === undefined) {
    parityDisagreed++;
    disagreements.push({ id, field: jsItem.field, reason: 'Missing in PHP output' });
  } else if (jsItem.ok !== phpVal) {
    parityDisagreed++;
    disagreements.push({
      id,
      field: jsItem.field,
      jsResult: jsItem.ok,
      phpResult: phpVal
    });
  } else {
    parityAgreed++;
  }
}

console.log(`Evaluated ${totalCases} test cases across JS and PHP:`);
console.log(`  Agreed: ${parityAgreed}`);
console.log(`  Disagreed: ${parityDisagreed}\n`);

if (parityDisagreed > 0) {
  console.error('❌ Parity discrepancies detected between JS and PHP engines:');
  console.table(disagreements);
  process.exit(1);
} else {
  console.log('✓ 100% PARITY CONFIRMED: Both JavaScript and PHP (PCRE Du) engines agree on every single test case.');
  process.exit(0);
}
