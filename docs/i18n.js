'use strict';

(() => {
  const english = window.LearnLingoEnglish;
  const textSources = new WeakMap();
  const attributeSources = new WeakMap();
  let language = window.LearnLingoPreferences.language;

  function translate(source) {
    if (language !== 'en') return source;
    const key = source.trim().replace(/\s+/g, ' ');
    if (Object.hasOwn(english, key)) return english[key];
    if (/^\d+% concluído$/.test(key)) return key.replace('concluído', 'complete');
    const step = key.match(/^ETAPA (\d+) · PRÉVIA DO ASSISTENTE$/);
    if (step) return `STEP ${step[1]} · WIZARD PREVIEW`;
    const selected = key.match(/^(Verde|Azul|Violeta|Coral), selecionado$/);
    if (selected) return `${english[selected[1]]}, selected`;
    return source;
  }

  function renderValue(current, previous) {
    const source = previous && current === previous.rendered ? previous.source : current;
    const leading = source.match(/^\s*/)[0];
    const trailing = source.match(/\s*$/)[0];
    const rendered = source.trim() ? leading + translate(source.trim()) + trailing : source;
    return {source, rendered};
  }

  function localize(root = document) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: node => node.parentElement?.closest('script, style, [data-no-translate]') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT
    });
    let node;
    while ((node = walker.nextNode())) {
      const next = renderValue(node.nodeValue, textSources.get(node));
      textSources.set(node, next);
      if (node.nodeValue !== next.rendered) node.nodeValue = next.rendered;
    }
    const selector = '[aria-label], [alt], [title], meta[name="description"], meta[property="og:title"], meta[property="og:description"]';
    const elements = [...root.querySelectorAll(selector)];
    if (root instanceof Element && root.matches(selector)) elements.push(root);
    elements.forEach(element => {
      const saved = attributeSources.get(element) || {};
      ['aria-label', 'alt', 'title', ...(element.tagName === 'META' ? ['content'] : [])].forEach(attribute => {
        if (!element.hasAttribute(attribute)) return;
        const next = renderValue(element.getAttribute(attribute), saved[attribute]);
        saved[attribute] = next;
        if (element.getAttribute(attribute) !== next.rendered) element.setAttribute(attribute, next.rendered);
      });
      attributeSources.set(element, saved);
    });
  }

  function save(key, value) {
    try { localStorage.setItem(key, value); } catch (_) { /* Preferences still work for this visit. */ }
  }

  function setLanguage(next, persist = true) {
    if (!['pt', 'en'].includes(next)) return;
    language = next;
    document.documentElement.lang = language === 'en' ? 'en' : 'pt-BR';
    document.querySelector('meta[property="og:locale"]').content = language === 'en' ? 'en_US' : 'pt_BR';
    document.querySelectorAll('[data-language]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.language === language)));
    localize();
    if (persist) save('learnlingo-language', language);
  }

  let explicitTheme = window.LearnLingoPreferences.theme;
  const systemTheme = matchMedia('(prefers-color-scheme: dark)');
  const themeButton = document.querySelector('#theme-toggle');
  function setTheme(theme, persist = true) {
    document.documentElement.dataset.appearance = theme;
    const dark = theme === 'dark';
    themeButton.setAttribute('aria-pressed', String(dark));
    themeButton.setAttribute('aria-label', dark ? 'Ativar modo claro' : 'Ativar modo escuro');
    themeButton.setAttribute('title', dark ? 'Ativar modo claro' : 'Ativar modo escuro');
    document.querySelector('meta[name="theme-color"]').content = dark ? '#152119' : '#78ce29';
    localize(themeButton);
    if (persist) { explicitTheme = theme; save('learnlingo-theme', theme); }
  }

  document.querySelectorAll('[data-language]').forEach(button => button.addEventListener('click', () => setLanguage(button.dataset.language)));
  themeButton.addEventListener('click', () => setTheme(document.documentElement.dataset.appearance === 'dark' ? 'light' : 'dark'));
  systemTheme.addEventListener('change', event => { if (!explicitTheme) setTheme(event.matches ? 'dark' : 'light', false); });
  window.LearnLingoI18n = {translate, localize, setLanguage, get language() { return language; }};
  setTheme(document.documentElement.dataset.appearance, false);
  setLanguage(language, false);
})();
