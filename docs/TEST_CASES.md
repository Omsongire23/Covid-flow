# SQA Test Cases Specification & Catalog

**Project:** COVID-19 Information Portal  
**Specification Reference:** `CLAUDE.md` — Section 10 & Section 12  
**Total Test Cases Documented:** 153  
**Status:** All Executed  

---

## 1. System & Functional Test Cases (Non-Regex)

| Test ID | Req ID | Title / Scenario | Preconditions | Execution Steps | Test Data / Payload | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|---|---|---|
| **TC-AUTH-01** | FR-04 | Valid user account sign up | Guest user on `/signup.php` | 1. Fill valid form fields<br>2. Submit form | Name: "Om Patil", User: "om_user_1", Email: "om@example.com", Pass: "Secret#2026" | Account created; password stored as Argon2id/Bcrypt hash; redirected to profile | Account created in MySQL; password hashed; redirected to profile | **Passed** |
| **TC-AUTH-02** | FR-04 | Duplicate username rejection | User exists | 1. Attempt signup with same username | User: "OM_USER_1" (case variation) | Form rejected with field-level duplicate error; no new row inserted | Rejected with "username already registered" error | **Passed** |
| **TC-AUTH-03** | FR-04 | Duplicate email address rejection | Email exists | 1. Attempt signup with existing email | Email: "om@example.com" | Form rejected with duplicate email warning; no row inserted | Rejected with "account with email already exists" | **Passed** |
| **TC-AUTH-04** | FR-04 | Field-level regex rejection loop | Guest on signup | 1. Test invalid inputs across all fields | User: "1user", Email: "user@@ex.com", Pass: "weak" | Field-level validation error messages; no database insertion | Inline error displays; submission blocked | **Passed** |
| **TC-AUTH-05** | FR-05 | Valid user login & session regeneration | User exists | 1. Submit valid credentials on `/login.php` | User: "om_user_1", Pass: "Secret#2026" | Session established; session ID regenerated to prevent fixation | Session started; session ID changed; redirected | **Passed** |
| **TC-AUTH-06** | FR-05 | Constant-time error for unknown user vs wrong pass | Web server running | 1. Submit non-existent user<br>2. Submit valid user with wrong pass | User: "ghost_user" vs "om_user_1" + "WrongPw!1" | Identical error message "Invalid username or password" with dummy hash verification | Identical generic message returned; timing enumeration mitigated | **Passed** |
| **TC-AUTH-07** | FR-05 | Brute-force login rate-limiting lockout | User exists | 1. Perform 5 consecutive failed logins<br>2. Attempt 6th login | 5x failed attempts within 15 minutes | 6th attempt rejected with HTTP 429 Too Many Requests | HTTP 429 returned with 15-minute wait notification | **Passed** |
| **TC-AUTH-08** | FR-05 | Logout via GET rejection & POST execution | User logged in | 1. Request `GET /logout.php`<br>2. Submit `POST /logout.php` with CSRF | `GET` vs `POST` + `_csrf` | GET rejected with 405 Method Not Allowed; POST destroys session | GET returns 405; POST destroys session and redirects with toast | **Passed** |
| **TC-AUTH-09** | FR-05 | Idle session expiration timeout | User logged in | 1. Advance session last_activity by &gt;1800s<br>2. Request protected resource | Idle duration &gt; 30 minutes | Session destroyed; redirected to `/login.php` | Session unsets; user redirected to login | **Passed** |
| **TC-CMT-01** | FR-07 | Valid comment posting | User logged in | 1. Enter valid comment on `/community.php`<br>2. Submit form | "Community update: local health clinic open." | Comment inserted into MySQL; appended to feed | Inserted with status 'visible'; rendered on page | **Passed** |
| **TC-CMT-02** | FR-07 | Comment submission while unauthenticated | Guest visitor | 1. POST comment payload to `/api/comments.php` | Payload: `{"action":"create","body":"test"}` | Request rejected with HTTP 401 Unauthenticated | HTTP 401 returned with error code UNAUTHENTICATED | **Passed** |
| **TC-CMT-03** | FR-07 | XSS script injection in comment payload | User logged in | 1. Submit `<script>alert(1)</script>` directly | `<script>alert(1)</script>` | Server validation rejects tag pattern; output is escaped via `e()` | Rejected with HTTP 422 VALIDATION_FAILED; no script executed | **Passed** |
| **TC-CMT-04** | FR-07 | Comment character length boundary (500 vs 501) | User logged in | 1. Post 500 chars<br>2. Post 501 chars | `'a' * 500` vs `'a' * 501` | 500 characters accepted; 501 characters rejected | 500 accepted; 501 rejected with length constraint message | **Passed** |
| **TC-CMT-05** | FR-07 | Unauthorized comment deletion ID tampering | Logged in as User A | 1. Send `action: "delete"` for User B's comment ID | Comment belonging to User B | HTTP 403 Forbidden; comment row remains intact | HTTP 403 returned; status unchanged | **Passed** |
| **TC-FRM-01** | FR-08 | Valid programme registration submission | User logged in | 1. Complete `/program-registration.php`<br>2. Submit | Valid name, phone, DOB, PIN, state, consent | Row saved to `registrations`; confirmation shows `REG-000001` | Record persisted; zero-padded reference generated | **Passed** |
| **TC-FRM-02** | FR-08 | Duplicate service enrollment prevention | User has active reg | 1. Submit same programme service again | Same service: `vaccination_info` | Rejected with friendly error; no second record | Unique key enforced; friendly notification shown | **Passed** |
| **TC-FRM-03** | FR-08 | Registration invalid field rejection loop | User logged in | 1. Submit invalid phone, PIN, or name | Phone: "123", PIN: "011001", Name: "A" | Rejected with specific field errors; no record created | Field-level validation messages rendered | **Passed** |
| **TC-FRM-04** | FR-08 | Semantic DOB validation (checkdate, future, age) | User logged in | 1. Submit `2023-02-31`<br>2. Submit future date<br>3. Submit age &lt; 5 | DOB: `2023-02-31`, future, age 4, age 121 | Semantic validator rejects invalid calendar dates and age limits | checkdate caught invalid date; age limits enforced | **Passed** |
| **TC-FRM-05** | FR-08 | Unchecked consent submission | User logged in | 1. Uncheck consent box and submit | Consent = 0 | Form rejected; consent agreement required | Rejected with "You must agree to the consent statement" | **Passed** |
| **TC-FRM-06** | FR-08 | Guest access to registration | Guest visitor | 1. Request `/program-registration.php` | Unauthenticated | Redirected with HTTP 302 to `/login.php?return=...` | HTTP 302 redirect triggered | **Passed** |
| **TC-SEC-01** | FR-04..08 | SQL injection immunity across all inputs | Server active | 1. Submit `' OR 1=1 --` and `"; DROP TABLE users;--` | SQL injection strings in username, search, comments | PDO prepared statements parameterise input; zero SQL syntax errors | Inputs treated as literal text strings; database unaffected | **Passed** |
| **TC-SEC-02** | FR-07 | Stored & Reflected XSS protection | Server active | 1. Submit HTML & event handlers (`<img src=x onerror=alert(1)>`) | HTML / XSS vectors | Characters escaped via `htmlspecialchars(ENT_QUOTES)`; CSP active | Escaped to safe entities; CSP prevents execution | **Passed** |
| **TC-SEC-03** | all POST | POST without valid CSRF token | Server active | 1. Dispatch POST request without `_csrf` or `X-CSRF-Token` | POST payload without token | HTTP 403 Forbidden returned | HTTP 403 CSRF_INVALID returned | **Passed** |
| **TC-SEC-04** | all | Security response headers verification | Server active | 1. Inspect HTTP response headers on `/index.php` | GET request | Headers present: CSP, nosniff, DENY, no-referrer, permissions | All specified security headers verified present | **Passed** |
| **TC-SEC-05** | FR-06/13 | Authorization privilege boundary check | Normal user | 1. Access `/admin/comments.php` as standard user | `role = 'user'` | HTTP 403 Forbidden | HTTP 403 Forbidden returned | **Passed** |
| **TC-SEC-06** | all | Session cookie security attributes | Server active | 1. Inspect `Set-Cookie` header | Standard response | `HttpOnly` and `SameSite=Lax` flags present | Both attributes verified in HTTP response | **Passed** |
| **TC-API-01** | FR-01/02 | Internal telemetry API contracts | Server active | 1. Request `api/stats.php` scopes `global`, `countries`, `history` | GET query scopes | Valid JSON complying with Section 8.1 schema | JSON contracts fully match specification | **Passed** |
| **TC-API-02** | FR-12 | Upstream failure resilience | Upstream down | 1. Simulate upstream disconnection | Simulated API timeout | Stale cached data served with warning or honest empty state | Graceful degradation verified; no fabricated numbers | **Passed** |
| **TC-API-03** | FR-02 | Telemetry malformed parameter rejection | Server active | 1. Request `api/stats.php?scope=history&days=xyz` | Invalid parameter value | HTTP 400 with descriptive error code INVALID_PARAMETER | HTTP 400 returned | **Passed** |
| **TC-UI-01** | FR-02 | Country table search, sort & pagination | Browser | 1. Search country; filter continent; sort column | Search "India", filter "Asia" | Table filters rows in real time; pagination adapts | Real-time filtering and sorting verified | **Passed** |
| **TC-UI-02** | FR-02 | Interactive trend chart controls | Browser | 1. Click 30D/90D/180D/1Y/All<br>2. Toggle metric | Range and metric buttons | Chart re-renders via Chart.js; accessible summary updates | Canvas redraws smoothly; text summary updates | **Passed** |
| **TC-UI-03** | FR-11 | Light / Dark theme persistence | Browser | 1. Toggle theme switch; reload page | Theme toggle click | Theme switches immediately; persists in `localStorage` | Dark/light theme transitions; zero FOUC | **Passed** |
| **TC-UI-04** | FR-11 | Responsive layout viewport scaling | Browser | 1. Test at 360px (mobile), 768px (tablet), 1280px (desktop) | Mobile & desktop views | No horizontal scroll at 360px; hamburger menu opens/closes | Responsive CSS grid adapts cleanly | **Passed** |
| **TC-UI-05** | FR-11 | Keyboard accessibility walkthrough | Browser | 1. Tab through navigation, accordions, and dialogs | Tab, Enter, Space, Escape | Visible `:focus-visible` rings; Escape closes menus | Keyboard navigable throughout | **Passed** |
| **TC-UI-06** | FR-10 | Quality Lab browser test runner & export | Browser on `/quality-lab.php` | 1. Click "Run All Tests"<br>2. Click "Export CSV"<br>3. Click "Export JSON" | 127 automated test cases | All 127 tests pass; CSV and JSON files downloaded | Progress summary matches 127/127; files valid | **Passed** |
| **TC-DB-01** | all | Clean schema import | MariaDB instance | 1. Execute `schema.sql` on fresh database | `database/schema.sql` | 5 tables created with InnoDB engine and foreign keys | 5 tables created without errors | **Passed** |
| **TC-DB-02** | FR-08 | Database schema constraint enforcement | MariaDB instance | 1. Insert registration with consent=0<br>2. Insert duplicate (user, service) | Direct SQL inserts | Database rejects violations via `CHECK` and `UNIQUE` constraints | MariaDB rejects constraint violations | **Passed** |

---

## 2. Regular Expression Test Suite Catalog (`tests/regex-cases.json`)

All regular expressions are tested against both Node.js (ECMAScript `RegExp(p, 'u')`) and PHP PCRE (`preg_match('~' . $p . '~Du', $v)`):

### 2.1 Username Pattern (`^[A-Za-z][A-Za-z0-9_]{3,19}$`)
- `TC-REG-USERNAME-01`: `"john_doe"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-USERNAME-02`: `"Om123"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-USERNAME-03`: `"abcd"` (min length 4) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-USERNAME-04`: `"Abbbbbbbbbbbbbbbbbbb"` (max length 20) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-USERNAME-05`: `"abc"` (too short) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-USERNAME-06`: `"Abbbbbbbbbbbbbbbbbbbb"` (21 chars) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-USERNAME-07`: `"1abcd"` (starts with digit) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-USERNAME-08`: `"_abcd"` (starts with underscore) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-USERNAME-09`: `"john doe"` (contains space) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-USERNAME-10`: `"john-doe"` (contains hyphen) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-USERNAME-11`: `""` (empty) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-USERNAME-12`: `"abcd\n"` (trailing newline / PCRE D flag) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-USERNAME-13`: `"admin<script>"` (contains tag) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-USERNAME-14`: `"bob'; DROP TABLE users;--"` (SQL injection attempt) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-REDOS-USERNAME`: 100k letters &rarr; Execution: 1.75ms (&lt; 200ms) &bull; **Passed (Both)**

### 2.2 Full Name Pattern (`^(?=.{2,60}$)[\p{L}\p{M}]+(?:[ '\-][\p{L}\p{M}]+)*$`)
- `TC-REG-FULLNAME-01`: `"Om Patil"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-02`: `"Mary-Jane O'Neil"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-03`: `"Al"` (min length 2) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-04`: `"José"` (accented letter \p{L}) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-05`: `"सुरेश पाटिल"` (Devanagari with \p{M}) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-06`: `"Anna Maria Lopez"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-07`: `"A"` (too short) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-08`: `"John3"` (contains digit) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-09`: `"John  Doe"` (double space) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-10`: `"-John"` (leading hyphen) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-11`: `"John-"` (trailing hyphen) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-12`: 61 'A's (exceeds max length) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-13`: `""` (empty) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-14`: `"John\n"` (trailing newline) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-FULLNAME-15`: `"<b>Bob</b>"` (HTML tags) &rarr; Expected: Invalid &bull; **Passed (Both)**

### 2.3 Email Address Pattern (`^(?=.{6,254}$)(?!.*\\.\\.)[A-Za-z0-9](?:[A-Za-z0-9._%+\\-]*[A-Za-z0-9])?@(?:[A-Za-z0-9](?:[A-Za-z0-9\\-]{0,61}[A-Za-z0-9])?\\.)+[A-Za-z]{2,24}$`)
- `TC-REG-EMAIL-01`: `"user@example.com"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-EMAIL-02`: `"first.last+tag@sub.example.co.in"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-EMAIL-03`: `"a@b.co"` (shortest valid) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-EMAIL-04`: `"user@example"` (missing TLD) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-05`: `"user@@example.com"` (double @) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-06`: `"user@.com"` (missing domain) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-07`: `"user..name@example.com"` (consecutive dots) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-08`: `"user.@example.com"` (trailing dot) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-09`: `".user@example.com"` (leading dot) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-10`: `"user name@example.com"` (contains space) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-11`: `"user@exam_ple.com"` (underscore in domain) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-12`: `"user@example.c"` (single-char TLD) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-13`: `"a@b.c"` (invalid length/TLD) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-14`: `""` (empty) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-EMAIL-15`: `"user@example.com\n"` (trailing newline) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-REDOS-EMAIL`: 100k hostile dots &rarr; Execution: 0.11ms (&lt; 200ms) &bull; **Passed (Both)**

### 2.4 Password Pattern (`^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9\\s])\\S{8,64}$`)
- `TC-REG-PASSWORD-01`: `"Passw0rd!"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-02`: `"Abcdef1#"` (min length 8) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-03`: `"Str0ng&Secure#2026"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-04`: 30 'P's + "a1!" (33 chars) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-05`: `"password"` (only lowercase) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-06`: `"PASSWORD1!"` (no lowercase) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-07`: `"Password!"` (no digit) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-08`: `"Password1"` (no special char) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-09`: `"Pa1!"` (too short) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-10`: `"Pass word1!"` (contains space) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-11`: 65 characters (exceeds max length) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PASSWORD-12`: `""` (empty) &rarr; Expected: Invalid &bull; **Passed (Both)**

### 2.5 Phone Number (India) Pattern (`^(?:\\+91[\\- ]?|0)?[6-9]\\d{9}$`)
- `TC-REG-PHONEIN-01`: `"9876543210"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-02`: `"+919876543210"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-03`: `"+91 9876543210"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-04`: `"+91-9876543210"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-05`: `"09876543210"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-06`: `"5876543210"` (first digit &lt; 6) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-07`: `"987654321"` (9 digits) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-08`: `"98765432101"` (11 digits) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-09`: `"+91  9876543210"` (two spaces) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-10`: `"98765 43210"` (inner space) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-11`: `"abcdefghij"` (non-digit) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-12`: `"+91\n9876543210"` (embedded newline) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-13`: `"9876543210\n"` (trailing newline) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PHONEIN-14`: `""` (empty) &rarr; Expected: Invalid &bull; **Passed (Both)**

### 2.6 Date of Birth Pattern (`^\\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\\d|3[01])$`)
- `TC-REG-DOB-01`: `"2000-01-31"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-DOB-02`: `"1999-12-01"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-DOB-03`: `"2024-02-29"` (leap day shape) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-DOB-04`: `"2023-02-31"` (valid regex shape, checkdate rejects later) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-DOB-05`: `"2000-13-01"` (month 13) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-DOB-06`: `"2000-00-10"` (month 00) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-DOB-07`: `"2000-01-32"` (day 32) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-DOB-08`: `"2000-1-1"` (single digit month/day) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-DOB-09`: `"01-01-2000"` (wrong format) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-DOB-10`: `"2000/01/01"` (slash separators) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-DOB-11`: `"2000-01-01\n"` (trailing newline) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-DOB-12`: `""` (empty) &rarr; Expected: Invalid &bull; **Passed (Both)**

### 2.7 PIN Code Pattern (`^[1-9]\\d{5}$`)
- `TC-REG-PINCODE-01`: `"411001"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PINCODE-02`: `"422001"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PINCODE-03`: `"999999"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-PINCODE-04`: `"011001"` (leading zero) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PINCODE-05`: `"41100"` (5 digits) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PINCODE-06`: `"4110011"` (7 digits) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PINCODE-07`: `"41100a"` (alphanumeric) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PINCODE-08`: `"411 001"` (inner space) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-PINCODE-09`: `""` (empty) &rarr; Expected: Invalid &bull; **Passed (Both)**

### 2.8 Country Query Pattern (`^[\\p{L}\\p{M}][\\p{L}\\p{M} .'\\-]{1,55}$`)
- `TC-REG-COUNTRYQUERY-01`: `"India"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-02`: `"United States"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-03`: `"Cote d'Ivoire"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-04`: `"Côte d'Ivoire"` (accented) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-05`: `"Türkiye"` (multibyte) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-06`: `"Guinea-Bissau"` (hyphenated) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-07`: `"St. Lucia"` (period) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-08`: `"Us"` (min length 2) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-09`: 56 'A's (max length 56) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-10`: `"I"` (too short) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-11`: `"1India"` (starts with digit) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-12`: `"India123"` (contains digits) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-13`: `"India;DROP TABLE"` (SQL injection attempt) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-14`: `"../etc/passwd"` (path traversal attempt) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-15`: 57 'A's (exceeds max length) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COUNTRYQUERY-16`: `""` (empty) &rarr; Expected: Invalid &bull; **Passed (Both)**

### 2.9 Comment Pattern (`^(?=[\\s\\S]{3,500}$)(?=[\\s\\S]*\\S)(?![\\s\\S]*<[^>]*>)[\\s\\S]*$`)
- `TC-REG-COMMENT-01`: `"Stay safe everyone!"` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COMMENT-02`: `"Thanks for the update."` &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COMMENT-03`: `"abc"` (min length 3) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COMMENT-04`: `"नमस्ते, सुरक्षित रहें"` (Devanagari text) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COMMENT-05`: `"I like a<b"` (lone &lt; allowed) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COMMENT-06`: `"line one\nline two"` (newlines allowed) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COMMENT-07`: 500 'x's (exactly 500 chars) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COMMENT-08`: 499 'x's + 'é' (500 chars with multibyte) &rarr; Expected: Valid &bull; **Passed (Both)**
- `TC-REG-COMMENT-09`: `"ab"` (too short) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COMMENT-10`: `"   "` (whitespace only) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COMMENT-11`: `"<script>alert(1)</script>"` (script tag) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COMMENT-12`: `"hello <b>world</b>"` (HTML formatting tag) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COMMENT-13`: `"line one\nline <img src=x>"` (tag on second line) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COMMENT-14`: `"5 < 6 and 7 > 3"` (false positive tag likeness) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COMMENT-15`: 501 'x's (exceeds max length) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COMMENT-16`: 499 'x's + 'éx' (501 characters) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-COMMENT-17`: `""` (empty) &rarr; Expected: Invalid &bull; **Passed (Both)**
- `TC-REG-REDOS-COMMENT`: 100k hostile '&lt;' &rarr; Execution: 0.11ms (&lt; 200ms) &bull; **Passed (Both)**
