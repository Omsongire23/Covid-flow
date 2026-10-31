/**
 * validate.js — Client-side validation using config/patterns.json (ES module).
 * Patterns are loaded once from api/patterns.php.
 */

let _patterns = null;

/** Load patterns from the server (cached). */
export async function loadPatterns() {
  if (_patterns) return _patterns;
  try {
    const res = await fetch('/api/patterns.php');
    _patterns = await res.json();
  } catch {
    _patterns = {};
  }
  return _patterns;
}

/** Test a value against a named pattern. Returns true/false. */
export function testPattern(patterns, field, value) {
  const entry = patterns[field];
  if (!entry) return false;
  try {
    const re = new RegExp(entry.pattern, 'u');
    return re.test(value);
  } catch { return false; }
}

/** Get the error message for a pattern. */
export function getMessage(patterns, field) {
  return patterns[field]?.message ?? 'Invalid value.';
}

/**
 * Attach live validation to a form field.
 * opts: { field, trim, onValid, onInvalid }
 */
export function attachValidation(input, patterns, field, opts = {}) {
  const trim = opts.trim !== false;
  let touched = false;

  function validate() {
    const val = trim ? input.value.trim() : input.value;
    const valid = testPattern(patterns, field, val);
    updateFieldUI(input, valid, valid ? null : getMessage(patterns, field), opts);
    return valid;
  }

  input.addEventListener('blur', () => { touched = true; validate(); });
  input.addEventListener('input', () => { if (touched) validate(); });

  return { validate };
}

/** Update field UI: border, icon, error message. */
export function updateFieldUI(input, valid, message, opts = {}) {
  const wrapper = input.closest('.form-group') || input.parentElement;
  if (!wrapper) return;

  input.classList.toggle('is-valid',   valid && input.value.length > 0);
  input.classList.toggle('is-invalid', !valid && input.value.length > 0);

  let msgEl = wrapper.querySelector('.form-error');
  let okEl  = wrapper.querySelector('.form-success');

  if (!valid && message && input.value.length > 0) {
    if (!msgEl) {
      msgEl = document.createElement('p');
      msgEl.className = 'form-error';
      msgEl.setAttribute('aria-live', 'polite');
      const id = input.id + '-error';
      msgEl.id = id;
      input.setAttribute('aria-describedby', id);
      input.parentElement.after(msgEl);
    }
    msgEl.textContent = '✕ ' + message;
    msgEl.hidden = false;
    if (okEl) okEl.hidden = true;
  } else {
    if (msgEl) msgEl.hidden = true;
    if (valid && input.value.length > 0) {
      if (!okEl) {
        okEl = document.createElement('p');
        okEl.className = 'form-success';
        okEl.setAttribute('aria-live', 'polite');
        input.parentElement.after(okEl);
      }
      if (opts.successMsg) okEl.textContent = '✓ ' + opts.successMsg;
      else okEl.textContent = '✓';
      okEl.hidden = false;
    }
  }
}

/** Password strength (0=empty, 1=weak, 2=fair, 3=strong). */
export function passwordStrength(pw) {
  if (!pw) return 0;
  let score = 0;
  if (pw.length >= 8)   score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[a-z]/.test(pw)) score++;
  if (/\d/.test(pw))    score++;
  if (/[^A-Za-z0-9\s]/.test(pw)) score++;
  if (score <= 2) return 1; // weak
  if (score <= 3) return 2; // fair
  return 3;                  // strong
}

/** Render strength meter. el: container with 3 .strength-bar, label: .strength-label */
export function renderStrength(meterEl, labelEl, pw) {
  const bars  = meterEl?.querySelectorAll('.strength-bar') ?? [];
  const level = passwordStrength(pw);
  const labels = ['', 'Weak', 'Fair', 'Strong'];
  const classes = ['', 'weak', 'fair', 'strong'];

  bars.forEach((bar, i) => {
    bar.className = 'strength-bar';
    if (i < level) bar.classList.add(`active-${classes[level]}`);
  });

  if (labelEl) {
    labelEl.textContent = pw ? labels[level] : '';
    labelEl.className = `strength-label ${pw ? classes[level] : ''}`;
  }
}
