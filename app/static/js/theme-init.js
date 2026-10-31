/**
 * theme-init.js — Sets data-theme before first paint to prevent flash.
 * Loaded in <head> without defer.
 */
(function () {
  try {
    var saved = localStorage.getItem('theme');
    var preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', saved || preferred);
  } catch (e) {
    // localStorage blocked — use system preference via CSS media query (default)
  }
}());
