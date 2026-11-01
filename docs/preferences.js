// Apply appearance before paint; storage may be unavailable in private contexts.
(() => {
  let theme = null;
  let language = null;
  try {
    theme = localStorage.getItem('learnlingo-theme');
    language = localStorage.getItem('learnlingo-language');
  } catch (_) { /* Keep working with browser defaults. */ }
  if (!['light', 'dark'].includes(theme)) theme = null;
  if (!['pt', 'en'].includes(language)) {
    language = navigator.language?.toLowerCase().startsWith('en') ? 'en' : 'pt';
  }
  document.documentElement.dataset.appearance = theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  document.documentElement.lang = language === 'en' ? 'en' : 'pt-BR';
  window.LearnLingoPreferences = {theme, language};
})();
