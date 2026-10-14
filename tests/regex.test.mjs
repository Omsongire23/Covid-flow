#!/usr/bin/env node
/**
 * regex.test.mjs — Automated regular expression test suite runner for Node.js (ECMAScript RegExp).
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

const patternsPath = path.join(rootDir, 'config', 'patterns.json');
const casesPath = path.join(rootDir, 'tests', 'regex-cases.json');

const patterns = JSON.parse(fs.readFileSync(patternsPath, 'utf8'));
const cases = JSON.parse(fs.readFileSync(casesPath, 'utf8'));

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

function esc(str) {
  return str.replace(/\n/g, '\\n').replace(/\r/g, '\\r').replace(/\t/g, '\\t');
}

console.log('================================================================');
console.log('  COVID-19 Portal Regex Test Suite — Node.js RegExp (/u)');
console.log('================================================================\n');

let total = 0;
let passed = 0;
let failed = 0;
const failures = [];

for (const [field, fieldCases] of Object.entries(cases)) {
  const patObj = patterns[field];
  if (!patObj) {
    console.error(`Pattern not defined for field: ${field}`);
    process.exit(1);
  }
  const regex = new RegExp(patObj.pattern, 'u');

  fieldCases.forEach((c, idx) => {
    total++;
    const id = `TC-REG-${field.toUpperCase()}-${String(idx + 1).padStart(2, '0')}`;
    const inputVal = expandInput(c);
    const actual = regex.test(inputVal);
    const isPass = actual === c.ok;

    if (isPass) {
      passed++;
    } else {
      failed++;
      failures.push({
        id,
        field,
        input: esc(inputVal.slice(0, 40)),
        expected: c.ok,
        actual,
        note: c.note || ''
      });
    }
  });
}

// Hostile ReDoS performance assertions
const hostileTests = [
  { field: 'comment', in: '<'.repeat(100000), desc: '100k hostile < characters' },
  { field: 'email', in: 'a.'.repeat(50000), desc: '100k hostile dots' },
  { field: 'username', in: 'A'.repeat(100000), desc: '100k letters' }
];

console.log('Running ReDoS & Backtracking Protection Benchmarks:');
for (const ht of hostileTests) {
  total++;
  const id = `TC-REG-REDOS-${ht.field.toUpperCase()}`;
  const regex = new RegExp(patterns[ht.field].pattern, 'u');
  const t0 = performance.now();
  const actual = regex.test(ht.in);
  const duration = performance.now() - t0;
  const isPass = !actual && duration < 200;

  if (isPass) {
    passed++;
    console.log(`  ✓ ${id}: ${ht.desc} completed in ${duration.toFixed(2)}ms (threshold < 200ms)`);
  } else {
    failed++;
    failures.push({
      id,
      field: ht.field,
      input: `[Hostile len=${ht.in.length}]`,
      expected: false,
      actual,
      note: `ReDoS benchmark exceeded 200ms threshold: ${duration.toFixed(2)}ms`
    });
  }
}

console.log('\n----------------------------------------------------------------');
console.log(`Summary: Total: ${total} | Passed: ${passed} | Failed: ${failed}`);
console.log('----------------------------------------------------------------\n');

if (failures.length > 0) {
  console.error('FAILURES DETECTED:');
  console.table(failures);
  process.exit(1);
} else {
  console.log('✓ All regex test cases and ReDoS checks PASSED successfully in Node.js.');
  process.exit(0);
}
