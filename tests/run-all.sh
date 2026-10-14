#!/usr/bin/env bash
# tests/run-all.sh — Master test suite runner for COVID-19 Portal SQA Mini Project
set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$DIR")"

echo "========================================================================"
echo "  COVID-19 PORTAL — MASTER SQA AUTOMATED TEST SUITE"
echo "  Timestamp: $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
echo "========================================================================"
echo ""

TOTAL_SUITES=5
PASSED_SUITES=0

run_step() {
  local name="$1"
  local cmd="$2"
  echo "------------------------------------------------------------------------"
  echo ">>> [SUITE] ${name}"
  echo "------------------------------------------------------------------------"
  if eval "${cmd}"; then
    echo ">>> RESULT: PASSED"
    PASSED_SUITES=$((PASSED_SUITES + 1))
  else
    echo ">>> RESULT: FAILED"
    exit 1
  fi
  echo ""
}

# 1. Node.js RegExp (/u) Suite
run_step "1/5: JavaScript RegExp Engine (/u) Test Suite" \
  "node ${ROOT}/tests/regex.test.mjs"

# 2. PHP PCRE (~...~Du) Suite
run_step "2/5: PHP PCRE Engine (~...~Du) Test Suite" \
  "if which php >/dev/null 2>&1; then php ${ROOT}/tests/regex_test.php; else docker compose -f ${ROOT}/docker-compose.yml exec -T web php tests/regex_test.php; fi"

# 3. Cross-Engine Parity Check
run_step "3/5: Cross-Engine Parity Verification (JS vs PHP)" \
  "node ${ROOT}/tests/engine-parity.mjs"

# 4. Semantic Validator Suite
run_step "4/5: Semantic Validator Test Suite (checkdate, age, normalisation)" \
  "if which php >/dev/null 2>&1; then php ${ROOT}/tests/validator_test.php; else docker compose -f ${ROOT}/docker-compose.yml exec -T web php tests/validator_test.php; fi"

# 5. HTTP Smoke & Security Suite
run_step "5/5: Automated HTTP Smoke & Security Verification" \
  "bash ${ROOT}/tests/smoke.sh"

echo "========================================================================"
echo "  MASTER TEST SUMMARY: All ${PASSED_SUITES}/${TOTAL_SUITES} Test Suites PASSED Successfully!"
echo "========================================================================"
exit 0
