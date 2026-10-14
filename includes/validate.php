<?php
declare(strict_types=1);

/**
 * validate.php — Shared validation using config/patterns.json.
 * PHP uses ~pattern~Du flags (D = no $ before trailing newline; u = Unicode).
 */

/** Load and cache the patterns array. */
function get_patterns(): array
{
    static $patterns = null;
    if ($patterns !== null) {
        return $patterns;
    }
    $file     = APP_ROOT . '/config/patterns.json';
    $json     = file_get_contents($file);
    $patterns = json_decode($json, true) ?? [];
    return $patterns;
}

/**
 * Validate a single field value against its named pattern.
 * Returns true if valid, false if invalid or pattern unknown.
 * A preg_match return of false (invalid UTF-8 etc.) counts as invalid.
 */
function validate_field(string $field, string $value): bool
{
    $patterns = get_patterns();
    if (!isset($patterns[$field])) {
        return false;
    }
    $pat    = $patterns[$field]['pattern'];
    // Cap input length before any regex to guard against ReDoS
    if (mb_strlen($value) > 100000) {
        return false;
    }
    $result = preg_match('~' . $pat . '~Du', $value);
    return $result === 1;
}

/**
 * Get the validation message for a field.
 */
function validate_message(string $field): string
{
    $patterns = get_patterns();
    return $patterns[$field]['message'] ?? 'Invalid value.';
}

// ── Semantic validators ────────────────────────────────────────────────────────

/**
 * Validate date of birth:
 *  - Passes regex shape check (validate_field 'dob')
 *  - Real calendar date (checkdate)
 *  - Not in the future
 *  - Age between 5 and 120
 */
function validate_dob(string $value): bool|string
{
    if (!validate_field('dob', $value)) {
        return 'Use the format YYYY-MM-DD.';
    }
    [$year, $month, $day] = explode('-', $value);
    if (!checkdate((int)$month, (int)$day, (int)$year)) {
        return 'That date does not exist on the calendar.';
    }
    $dob = new DateTimeImmutable($value);
    $now = new DateTimeImmutable('today');
    if ($dob > $now) {
        return 'Date of birth cannot be in the future.';
    }
    $age = (int)$now->diff($dob)->y;
    if ($age < 5) {
        return 'You must be at least 5 years old to register.';
    }
    if ($age > 120) {
        return 'Age must be 120 or younger.';
    }
    return true;
}

/**
 * Normalise an Indian phone number:
 *  - Strip spaces and hyphens (only between +91 and the 10-digit part)
 *  - Keep optional leading +91
 * Returns the normalised string, or false if shape-invalid.
 */
function normalise_phone(string $value): string|false
{
    if (!validate_field('phoneIN', $value)) {
        return false;
    }
    // Remove spaces/hyphens between prefix and number
    $v = preg_replace('/(?<=\+91)[\\- ]/', '', $value);
    return $v;
}

/** Validate that a state value is in the allowed list. */
function validate_state(string $value): bool
{
    return in_array($value, get_indian_states(), true);
}

/** Validate a service enum value. */
function validate_service(string $value): bool
{
    return in_array($value, ['vaccination_info', 'awareness_webinar', 'volunteer', 'helpline_callback'], true);
}

/** Validate a gender enum value. */
function validate_gender(string $value): bool
{
    return in_array($value, ['female', 'male', 'other', 'prefer_not_to_say'], true);
}

/** Return the fixed list of Indian states/UTs. */
function get_indian_states(): array
{
    return [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
        'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
        'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
        'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
        'Andaman and Nicobar Islands', 'Chandigarh',
        'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
    ];
}

/**
 * Normalise and validate a comment body:
 *  - CRLF → LF
 *  - trim
 *  - mb_strlen ≤ 500
 *  - passes regex pattern
 * Returns ['ok'=>true,'value'=>'...'] or ['ok'=>false,'error'=>'...'].
 */
function validate_comment(string $raw): array
{
    $v = str_replace("\r\n", "\n", $raw);
    $v = str_replace("\r", "\n", $v);
    if (mb_strlen($v) > 500) {
        return ['ok' => false, 'error' => 'Comment must be 500 characters or fewer.'];
    }
    if (!validate_field('comment', $v)) {
        return ['ok' => false, 'error' => validate_message('comment')];
    }
    return ['ok' => true, 'value' => $v];
}

/**
 * Validate all registration form fields.
 * Returns array of field=>errorMessage, empty if all valid.
 */
function validate_registration(array $data): array
{
    $errors = [];

    // Full name
    if (!validate_field('fullName', trim($data['full_name'] ?? ''))) {
        $errors['full_name'] = validate_message('fullName');
    }
    // Email
    if (!validate_field('email', strtolower(trim($data['email'] ?? '')))) {
        $errors['email'] = validate_message('email');
    }
    // Phone
    if (normalise_phone(trim($data['phone'] ?? '')) === false) {
        $errors['phone'] = validate_message('phoneIN');
    }
    // Date of birth (semantic)
    $dobResult = validate_dob(trim($data['dob'] ?? ''));
    if ($dobResult !== true) {
        $errors['dob'] = $dobResult;
    }
    // Gender
    if (!validate_gender($data['gender'] ?? '')) {
        $errors['gender'] = 'Please select a gender.';
    }
    // PIN code
    if (!validate_field('pinCode', trim($data['pin_code'] ?? ''))) {
        $errors['pin_code'] = validate_message('pinCode');
    }
    // State
    if (!validate_state(trim($data['state'] ?? ''))) {
        $errors['state'] = 'Please select a valid state or UT.';
    }
    // Service
    if (!validate_service($data['service'] ?? '')) {
        $errors['service'] = 'Please select a programme.';
    }
    // Consent
    if (empty($data['consent'])) {
        $errors['consent'] = 'You must agree to the consent statement.';
    }

    return $errors;
}
