// Apply appearance before paint; storage may be unavailable in private contexts.
(() => {
  let theme = null;
  let language = null;
  try {
    theme = localStorage.getItem('learnlingo-theme');
    language = localStorage.getItem('learnlingo-language');
  } catch (_) { /* Keep working with browser defaults. */ }
  if (!['light', 'dark'].includes(theme)) theme = null;
  // Language-specific pages always use their own locale. The guide can also
  // receive an explicit language when opened from one of those pages.
  const route = window.location.pathname.match(/\/(pt_br|en)\/(?:index\.html)?$/i);
  const requested = new URLSearchParams(window.location.search).get('lang');
  if (route) language = route[1].toLowerCase() === 'en' ? 'en' : 'pt';
  else if (['pt', 'en'].includes(requested)) language = requested;
  else if (!['pt', 'en'].includes(language)) {
    language = navigator.language?.toLowerCase().startsWith('en') ? 'en' : 'pt';
  }
  document.documentElement.dataset.appearance = theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  document.documentElement.lang = language === 'en' ? 'en' : 'pt-BR';
  window.LearnLingoPreferences = {theme, language};
})();
