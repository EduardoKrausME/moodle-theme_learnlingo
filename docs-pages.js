'use strict';
(() => {
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#mobile-nav');
  if (!toggle || !nav) return;
  const close = () => {
    nav.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', toggle.dataset.labelOpen);
  };
  toggle.addEventListener('click', () => {
    const open = nav.hidden;
    nav.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? toggle.dataset.labelClose : toggle.dataset.labelOpen);
  });
  nav.querySelectorAll('a').forEach(link => link.addEventListener('click', close));
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !nav.hidden) { close(); toggle.focus(); }
  });
  document.addEventListener('click', e => { if (!nav.hidden && !e.target.closest('.header')) close(); });
})();
