// Set theme appearance before the first paint.
(() => {
  let theme = null;
  try { theme = localStorage.getItem('learnlingo-theme'); } catch (_) { /* Storage unavailable. */ }
  if (theme !== 'light' && theme !== 'dark') theme = null;
  document.documentElement.dataset.appearance = theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
})();
