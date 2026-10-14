/**
 * forms.js — Auth and registration form logic.
 */
import { loadPatterns, attachValidation, renderStrength, updateFieldUI, testPattern } from './validate.js';
import { debounce, showToast } from './utils.js';

// ── Sign-up form ──────────────────────────────────────────────────────────────
async function initSignupForm() {
  const form = document.getElementById('signup-form');
  if (!form) return;

  const patterns = await loadPatterns();

  const uInput  = form.querySelector('#username');
  const pwInput = form.querySelector('#password');
  const pw2Input= form.querySelector('#password2');
  const nameIn  = form.querySelector('#full_name');
  const emailIn = form.querySelector('#email');

  const validators = {};
  if (nameIn)  validators.fullName  = attachValidation(nameIn,  patterns, 'fullName');
  if (uInput)  validators.username  = attachValidation(uInput,  patterns, 'username');
  if (emailIn) validators.email     = attachValidation(emailIn, patterns, 'email');
  if (pwInput) validators.password  = attachValidation(pwInput, patterns, 'password', { trim: false });

  // Password show/hide
  initPwToggle(pwInput);
  initPwToggle(pw2Input);

  // Strength meter
  if (pwInput) {
    const meter = form.querySelector('.strength-meter');
    const label = form.querySelector('.strength-label');
    pwInput.addEventListener('input', () => renderStrength(meter, label, pwInput.value));
  }

  // Password confirm
  function validateConfirm() {
    if (!pw2Input) return true;
    const match = pw2Input.value === pwInput.value && pw2Input.value.length > 0;
    updateFieldUI(pw2Input, match, match ? null : 'Passwords do not match.');
    return match;
  }
  if (pw2Input) {
    pw2Input.addEventListener('blur', validateConfirm);
    pw2Input.addEventListener('input', validateConfirm);
    pwInput?.addEventListener('input', () => { if (pw2Input.value) validateConfirm(); });
  }

  // Username availability
  if (uInput) {
    const checkAvail = debounce(async (val) => {
      if (!testPattern(patterns, 'username', val)) return;
      try {
        const res  = await fetch(`/api/check-username.php?u=${encodeURIComponent(val)}`);
        const data = await res.json();
        if (data.ok) {
          const avail = data.available;
          updateFieldUI(uInput, avail, avail ? null : 'Username already taken.', {
            successMsg: 'Available'
          });
        }
      } catch {}
    }, 500);
    uInput.addEventListener('input', () => checkAvail(uInput.value.trim()));
  }

  // Submit validation
  form.addEventListener('submit', (e) => {
    let valid = true;
    Object.values(validators).forEach(v => { if (!v.validate()) valid = false; });
    if (!validateConfirm()) valid = false;
    if (!valid) {
      e.preventDefault();
      const first = form.querySelector('.is-invalid');
      first?.focus();
    }
  });
}

// ── Login form ────────────────────────────────────────────────────────────────
async function initLoginForm() {
  const form = document.getElementById('login-form');
  if (!form) return;

  const patterns = await loadPatterns();
  const uInput   = form.querySelector('#username');
  const pwInput  = form.querySelector('#password');

  if (uInput) attachValidation(uInput, patterns, 'username');
  initPwToggle(pwInput);
}

// ── Registration form ─────────────────────────────────────────────────────────
async function initRegForm() {
  const form = document.getElementById('reg-form');
  if (!form) return;

  const patterns = await loadPatterns();
  const fields = {
    full_name: 'fullName',
    email:     'email',
    phone:     'phoneIN',
    pin_code:  'pinCode',
  };

  const validators = {};
  for (const [id, field] of Object.entries(fields)) {
    const el = form.querySelector(`#${id}`);
    if (el) validators[id] = attachValidation(el, patterns, field);
  }

  // DOB validation
  const dobEl = form.querySelector('#dob');
  if (dobEl) {
    function validateDob() {
      const val   = dobEl.value;
      const shape = testPattern(patterns, 'dob', val);
      if (!shape) { updateFieldUI(dobEl, false, 'Use YYYY-MM-DD format.'); return false; }
      // Semantic: not in future, age 5-120
      const d   = new Date(val);
      const now = new Date();
      if (d > now) { updateFieldUI(dobEl, false, 'Date of birth cannot be in the future.'); return false; }
      const age = (now - d) / (365.25 * 24 * 3600 * 1000);
      if (age < 5)   { updateFieldUI(dobEl, false, 'You must be at least 5 years old.'); return false; }
      if (age > 120) { updateFieldUI(dobEl, false, 'Age must be 120 or younger.'); return false; }
      updateFieldUI(dobEl, true, null);
      return true;
    }
    dobEl.addEventListener('blur', validateDob);
    dobEl.addEventListener('input', validateDob);
    validators.dob = { validate: validateDob };
  }

  // Comment field character counter (used also for the comment composer)
  initCharCounter(form.querySelector('[data-char-counter]'));

  form.addEventListener('submit', (e) => {
    let valid = true;
    Object.values(validators).forEach(v => { if (!v.validate()) valid = false; });
    if (!valid) {
      e.preventDefault();
      const first = form.querySelector('.is-invalid');
      first?.focus();
    }
  });
}

// ── Password show/hide toggle ──────────────────────────────────────────────────
export function initPwToggle(input) {
  if (!input) return;
  const btn = input.parentElement?.querySelector('.pw-toggle');
  if (!btn) return;
  btn.addEventListener('click', () => {
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
    btn.innerHTML = isText
      ? `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`
      : `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
  });
}

// ── Character counter ─────────────────────────────────────────────────────────
export function initCharCounter(el) {
  if (!el) return;
  const max     = parseInt(el.dataset.charCounter) || 500;
  const countEl = document.getElementById(el.dataset.charCounterDisplay);
  function update() {
    const len = el.value.length;
    if (!countEl) return;
    countEl.textContent = `${len} / ${max}`;
    countEl.className = 'char-counter' +
      (len > max ? ' error' : len > max * 0.85 ? ' warning' : '');
  }
  el.addEventListener('input', update);
  update();
}

document.addEventListener('DOMContentLoaded', () => {
  initSignupForm();
  initLoginForm();
  initRegForm();
});
