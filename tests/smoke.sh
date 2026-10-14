#!/usr/bin/env bash
# tests/smoke.sh — HTTP smoke & security tests against local server (127.0.0.1:8000)
BASE_URL="${TEST_BASE_URL:-http://127.0.0.1:8000}"
COOKIE_JAR="$(mktemp /tmp/covid_cookie_XXXXXX.txt)"
trap 'rm -f "$COOKIE_JAR"' EXIT

PASSED=0
FAILED=0

echo "================================================================"
echo "  COVID-19 Portal Automated HTTP Smoke & Security Test Suite"
echo "  Target: ${BASE_URL}"
echo "================================================================"
echo ""

check() {
  local id="$1"
  local desc="$2"
  local cmd="$3"
  
  if eval "$cmd" >/dev/null 2>&1; then
    echo "  ✓ ${id}: ${desc}"
    PASSED=$((PASSED + 1))
  else
    echo "  ✕ ${id}: ${desc} (FAILED)"
    FAILED=$((FAILED + 1))
  fi
}

# 1. TC-SEC-04: Security Headers
check "TC-SEC-04" "Security headers present (CSP, nosniff, DENY, no-referrer)" \
  'curl -sI "'${BASE_URL}'/index.php" | grep -iq "X-Content-Type-Options: nosniff" && \
   curl -sI "'${BASE_URL}'/index.php" | grep -iq "X-Frame-Options: DENY" && \
   curl -sI "'${BASE_URL}'/index.php" | grep -iq "Referrer-Policy: no-referrer" && \
   curl -sI "'${BASE_URL}'/index.php" | grep -iq "Content-Security-Policy:"'

# 2. TC-SEC-06: Cookie flags (HttpOnly, SameSite=Lax)
check "TC-SEC-06" "Session cookie flags set (HttpOnly, SameSite=Lax)" \
  'curl -sI "'${BASE_URL}'/index.php" | grep -iq "Set-Cookie:.*HttpOnly" && \
   curl -sI "'${BASE_URL}'/index.php" | grep -iq "Set-Cookie:.*SameSite=Lax"'

# 3. TC-API-01a: Global Stats Endpoint
check "TC-API-01a" "GET /api/stats.php?scope=global returns HTTP 200" \
  '[ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/api/stats.php?scope=global")" = "200" ]'

# 4. TC-API-01b: Countries Stats Endpoint
check "TC-API-01b" "GET /api/stats.php?scope=countries returns HTTP 200" \
  '[ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/api/stats.php?scope=countries")" = "200" ]'

# 5. TC-API-01c: Historical Stats Endpoint
check "TC-API-01c" "GET /api/stats.php?scope=history&days=30 returns HTTP 200" \
  '[ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/api/stats.php?scope=history&days=30")" = "200" ]'

# 6. TC-API-03: Invalid history parameter rejection
check "TC-API-03" "GET /api/stats.php?scope=history with invalid days rejects with 400" \
  '[ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/api/stats.php?scope=history&days=malicious")" = "400" ]'

# 7. TC-SEC-03: POST without CSRF token is rejected (403)
check "TC-SEC-03" "POST state-changing request without CSRF token returns 403 Forbidden" \
  '[ "$(curl -s -o /dev/null -w "%{http_code}" -X POST "'${BASE_URL}'/api/comments.php" -H "Content-Type: application/json" -d "{\"action\":\"create\",\"body\":\"test\"}")" = "403" ]'

# 8. TC-AUTH-08: Logout via GET is rejected (405)
check "TC-AUTH-08" "GET /logout.php is rejected with 405 Method Not Allowed" \
  '[ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/logout.php")" = "405" ]'

# 9. TC-SEC-05a: Protected programme registration redirects when logged out
check "TC-SEC-05a" "Guest accessing /program-registration.php redirected (302) to login" \
  '[ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/program-registration.php")" = "302" ]'

# 10. TC-SEC-05b: Protected profile redirects when logged out
check "TC-SEC-05b" "Guest accessing /profile.php redirected (302) to login" \
  '[ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/profile.php")" = "302" ]'

# 11. TC-CMT-02: Comment posting while logged out
check "TC-CMT-02" "Posting comment with CSRF while logged out returns 401 Unauthenticated" \
  'HTML="$(curl -s -c "'${COOKIE_JAR}'" -b "'${COOKIE_JAR}'" "'${BASE_URL}'/community.php")" && \
   TOKEN="$(echo "$HTML" | grep -o "name=\"csrf-token\" content=\"[^\"]*" | head -n1 | cut -d"\"" -f4)" && \
   STATUS="$(curl -s -o /dev/null -w "%{http_code}" -X POST "'${BASE_URL}'/api/comments.php" -c "'${COOKIE_JAR}'" -b "'${COOKIE_JAR}'" -H "Content-Type: application/json" -H "X-CSRF-Token: $TOKEN" -d "{\"action\":\"create\",\"body\":\"Hello world\"}")" && \
   [ "$STATUS" = "401" ]'

# 12. TC-API-04: Username check endpoint
check "TC-API-04" "GET /api/check-username.php returns valid JSON syntax check" \
  'curl -s "'${BASE_URL}'/api/check-username.php?u=valid_user_99" | grep -q "\"valid\":true"'

# 13. TC-SEC-01: SQL injection test in check-username
check "TC-SEC-01" "SQL injection string in username query returns valid:false without SQL error" \
  'curl -s "'${BASE_URL}'/api/check-username.php?u=admin%27+OR+1=1--" | grep -q "\"valid\":false"'

# 14. TC-UI-01: Public pages return HTTP 200
check "TC-UI-01" "All public informational pages return HTTP 200 OK" \
  '[ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/index.php")" = "200" ] && \
   [ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/statistics.php")" = "200" ] && \
   [ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/prevention.php")" = "200" ] && \
   [ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/symptoms.php")" = "200" ] && \
   [ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/vaccination.php")" = "200" ] && \
   [ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/myths.php")" = "200" ] && \
   [ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/helplines.php")" = "200" ] && \
   [ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/quality-lab.php")" = "200" ] && \
   [ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/login.php")" = "200" ] && \
   [ "$(curl -s -o /dev/null -w "%{http_code}" "'${BASE_URL}'/signup.php")" = "200" ]'

echo ""
echo "----------------------------------------------------------------"
echo "HTTP Smoke Test Summary: Total: $((PASSED + FAILED)) | Passed: ${PASSED} | Failed: ${FAILED}"
echo "----------------------------------------------------------------"
echo ""

if [ "$FAILED" -gt 0 ]; then
  exit 1
fi
exit 0
