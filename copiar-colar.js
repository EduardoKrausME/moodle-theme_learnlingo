'use strict';

(() => {
  const {translate, localize} = window.LearnLingoI18n;
  const mobileNav = document.querySelector('#mobile-nav');
  const menuButton = document.querySelector('.menu-toggle');
  const toast = document.querySelector('#guide-toast');
  let toastTimeout;

  function closeMenu() {
    mobileNav.hidden = true;
    menuButton.setAttribute('aria-expanded', 'false');
    menuButton.setAttribute('aria-label', 'Abrir menu');
    localize(menuButton);
  }

  menuButton.addEventListener('click', () => {
    const shouldOpen = mobileNav.hidden;
    mobileNav.hidden = !shouldOpen;
    menuButton.setAttribute('aria-expanded', String(shouldOpen));
    menuButton.setAttribute('aria-label', shouldOpen ? 'Fechar menu' : 'Abrir menu');
    localize(menuButton);
  });
  mobileNav.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !mobileNav.hidden) {
      closeMenu();
      menuButton.focus();
    }
  });
  document.addEventListener('click', event => {
    if (!mobileNav.hidden && !event.target.closest('.header')) closeMenu();
  });

  async function copy(text) {
    if (navigator.clipboard && window.isSecureContext) {
      try {
        await navigator.clipboard.writeText(text);
        return;
      } catch (_) { /* Fall back to selection-based copy. */ }
    }
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.cssText = 'position:fixed;opacity:0;left:-9999px';
    document.body.append(textarea);
    textarea.select();
    const successful = document.execCommand('copy');
    textarea.remove();
    if (!successful) throw new Error('Clipboard is unavailable');
  }

  function showToast(message) {
    toast.textContent = translate(message);
    toast.classList.add('show');
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => toast.classList.remove('show'), 3500);
  }

  document.querySelector('#copy-path').addEventListener('click', async () => {
    try {
      await copy('/theme/learnlingo/admin/branding_json.php');
      showToast('Caminho copiado!');
    } catch (_) {
      showToast('Não foi possível copiar. Selecione o texto manualmente.');
    }
  });
  document.querySelector('#copy-example').addEventListener('click', async () => {
    try {
      await copy(document.querySelector('#guide-example-text').textContent.trim());
      showToast('Exemplo copiado! Cole na sua conversa.');
    } catch (_) {
      showToast('Não foi possível copiar. Selecione o texto manualmente.');
    }
  });

  localize();
})();
