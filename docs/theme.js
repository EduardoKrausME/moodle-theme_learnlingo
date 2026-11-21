'use strict';
// Theme labels are supplied by the localized HTML, not by a translation script.
(() => {
  const button = document.getElementById('theme-toggle');
  if (!button) return;
  const media = window.matchMedia('(prefers-color-scheme: dark)');
  let override = null;
  try { override = localStorage.getItem('learnlingo-theme'); } catch (_) { /* Optional preference. */ }
  if (override !== 'light' && override !== 'dark') override = null;
  function apply(mode, persist = false) {
    const dark = mode === 'dark';
    document.documentElement.dataset.appearance = mode;
    button.setAttribute('aria-pressed', String(dark));
    const label = dark ? button.dataset.activateLight : button.dataset.activateDark;
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
    document.querySelector('meta[name="theme-color"]').content = dark ? '#152119' : '#78ce29';
    if (persist) {
      override = mode;
      try { localStorage.setItem('learnlingo-theme', mode); } catch (_) { /* Still works locally. */ }
    }
  }
  apply(override || (media.matches ? 'dark' : 'light'));
  button.addEventListener('click', () => apply(document.documentElement.dataset.appearance === 'dark' ? 'light' : 'dark', true));
  media.addEventListener('change', event => { if (!override) apply(event.matches ? 'dark' : 'light'); });
})();
