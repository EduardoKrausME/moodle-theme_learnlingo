'use strict';

// Every translated message and all preview markup live in pt_br/index.html or en/index.html.
const $ = selector => document.querySelector(selector);

function selectTab(buttons, selected) {
  buttons.forEach(button => {
    const active = button === selected;
    button.setAttribute('aria-selected', String(active));
    button.tabIndex = active ? 0 : -1;
    button.classList.toggle('active', active);
  });
}

function keyboardTabs(buttons) {
  buttons.forEach((button, index) => button.addEventListener('keydown', event => {
    let target;
    if (event.key === 'ArrowRight') target = (index + 1) % buttons.length;
    if (event.key === 'ArrowLeft') target = (index - 1 + buttons.length) % buttons.length;
    if (event.key === 'Home') target = 0;
    if (event.key === 'End') target = buttons.length - 1;
    if (target !== undefined) {
      event.preventDefault();
      buttons[target].click();
      buttons[target].focus();
    }
  }));
}

const screenButtons = [...document.querySelectorAll('[data-screen]')];
const previewPanel = $('#preview-panel');
function showScreen(button) {
  const template = document.getElementById('preview-' + button.dataset.screen);
  previewPanel.replaceChildren(template.content.cloneNode(true));
  previewPanel.setAttribute('aria-labelledby', button.id);
  $('#preview-caption').textContent = template.dataset.caption;
  selectTab(screenButtons, button);
}
screenButtons.forEach(button => button.addEventListener('click', () => showScreen(button)));
keyboardTabs(screenButtons);
showScreen(screenButtons.find(button => button.getAttribute('aria-selected') === 'true') || screenButtons[0]);

document.querySelectorAll('[data-palette]').forEach(button => button.addEventListener('click', () => {
  document.querySelectorAll('[data-palette]').forEach(item => {
    const active = item === button;
    item.classList.toggle('active', active);
    item.setAttribute('aria-pressed', String(active));
    item.setAttribute('aria-label', item.dataset.name + (active ? ', ' + item.dataset.selectedSuffix : ''));
  });
  $('.brand-art').dataset.theme = button.dataset.palette;
  $('.palette-feedback').textContent = button.dataset.feedback;
}));

const promptButtons = [...document.querySelectorAll('[data-prompt]')];
let currentPrompt = promptButtons.find(button => button.getAttribute('aria-pressed') === 'true')?.dataset.prompt || promptButtons[0].dataset.prompt;
function getPromptTemplate() {
  return document.getElementById('prompt-' + currentPrompt);
}
promptButtons.forEach(button => button.addEventListener('click', () => {
  currentPrompt = button.dataset.prompt;
  promptButtons.forEach(item => {
    const active = item === button;
    item.classList.toggle('active', active);
    item.setAttribute('aria-pressed', String(active));
  });
  const template = getPromptTemplate();
  $('#prompt-text').textContent = template.content.querySelector('[data-user-text]').textContent;
  $('#response-text').textContent = template.content.querySelector('[data-assistant-text]').textContent;
  const result = $('#chat-result');
  result.style.background = template.dataset.background;
  result.style.borderColor = template.dataset.border;
  result.style.setProperty('--result-accent', template.dataset.accent);
  result.replaceChildren(template.content.querySelector('template[data-card]').content.cloneNode(true));
}));

let toastTimer;
function toast(message) {
  const el = $('#toast');
  el.textContent = message;
  el.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('show'), 4200);
}
const copyButton = $('#copy-prompt');
copyButton.addEventListener('click', async () => {
  const prompt = getPromptTemplate().content.querySelector('[data-user-text]').textContent;
  const context = $('#prompt-copy-context').textContent.trim();
  const text = prompt + ' ' + context;
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
    } else {
      const input = document.createElement('textarea');
      input.value = text;
      input.style.position = 'fixed';
      input.style.opacity = '0';
      document.body.append(input);
      input.select();
      const copied = document.execCommand('copy');
      input.remove();
      if (!copied) throw new Error('Clipboard unavailable');
    }
    toast(copyButton.dataset.toastSuccess);
  } catch (_) {
    toast(copyButton.dataset.toastError);
  }
});

const stepButtons = [...document.querySelectorAll('[data-step]')];
const wizardPanel = $('#wizard-panel');
function showStep(index) {
  const template = document.getElementById('wizard-step-' + index);
  wizardPanel.replaceChildren(template.content.cloneNode(true));
  wizardPanel.setAttribute('aria-labelledby', stepButtons[index].id);
  selectTab(stepButtons, stepButtons[index]);
}
stepButtons.forEach(button => button.addEventListener('click', () => showStep(Number(button.dataset.step))));
keyboardTabs(stepButtons);
showStep(0);

const menuToggle = $('.menu-toggle');
const mobileNav = $('#mobile-nav');
function closeMenu() {
  mobileNav.hidden = true;
  menuToggle.setAttribute('aria-expanded', 'false');
  menuToggle.setAttribute('aria-label', menuToggle.dataset.labelOpen);
}
menuToggle.addEventListener('click', () => {
  const open = mobileNav.hidden;
  mobileNav.hidden = !open;
  menuToggle.setAttribute('aria-expanded', String(open));
  menuToggle.setAttribute('aria-label', open ? menuToggle.dataset.labelClose : menuToggle.dataset.labelOpen);
});
mobileNav.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && !mobileNav.hidden) {
    closeMenu();
    menuToggle.focus();
  }
});
document.addEventListener('click', event => {
  if (!mobileNav.hidden && !event.target.closest('.header')) closeMenu();
});

const installDialog = $('#install-dialog');
$('#open-install').addEventListener('click', () => {
  installDialog.showModal();
  document.body.style.overflow = 'hidden';
});
$('.dialog-close').addEventListener('click', () => installDialog.close());
installDialog.addEventListener('close', () => { document.body.style.overflow = ''; });
installDialog.addEventListener('click', event => {
  if (event.target === installDialog) {
    const r = installDialog.getBoundingClientRect();
    if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) {
      installDialog.close();
    }
  }
});
