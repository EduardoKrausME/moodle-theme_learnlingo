'use strict';

const {translate, localize} = window.LearnLingoI18n;

const icon = name => `<svg aria-hidden="true"><use href="#i-${name}"/></svg>`;
const panel = document.querySelector('#preview-panel');
const nav = `<div class="mock-nav"><span class="mock-logo">learnlingo</span><span class="active">Página inicial</span><span>Meus cursos</span><span>Painel</span><span class="mock-avatar">AL</span></div>`;
const courseCard = (color, symbol, category, title, progress) => `<article class="mock-card"><div class="mock-art art-${color}">${icon(symbol)}</div><span>${category}</span><strong>${title}</strong><div class="mock-progress"><i style="width:${progress}%"></i></div><small>${progress}% concluído</small></article>`;
const screens = {
  home: `${nav}<div class="mock-body"><div class="mock-welcome"><div><span class="eyebrow">APRENDA NO SEU RITMO</span><h3>Olá, Alex! Vamos dar o próximo passo?</h3><p>Um pouco de curiosidade hoje. Novas possibilidades amanhã.</p></div><img src="assets/lio.svg" width="105" height="110" alt=""></div><h4>Continue de onde parou</h4><div class="mock-courses">${courseCard('purple','palette','CRIATIVIDADE','Solte a criatividade',60)}${courseCard('blue','chat','COMUNICAÇÃO','Ideias que conectam',35)}${courseCard('orange','spark','DESENVOLVIMENTO','Aprender a aprender',80)}</div></div>`,
  course: `${nav}<div class="course-layout"><aside class="course-sidebar"><strong>Solte a criatividade</strong><span>✓ Comece por aqui</span><span class="selected">02 · Um novo olhar</span><span>03 · Do papel para o mundo</span><span>04 · Seu projeto final</span></aside><div class="course-main"><div class="course-banner"><span class="eyebrow">CONTINUE AVANÇANDO</span><h3>Solte a criatividade</h3><p>Explore novas ideias e transforme possibilidades em projetos.</p>${icon('palette')}</div><div class="course-progress-row"><span>Seu progresso</span><div class="progress-track"><span></span></div><span>60% concluído</span></div><h4>02 · Um novo olhar</h4><div class="activity-row">${icon('book')}<span>Enxergando outras possibilidades</span><span>✓</span></div><div class="activity-row">${icon('play')}<span>Uma ideia pode mudar tudo</span><span>Vídeo</span></div><div class="activity-row">${icon('chat')}<span>Compartilhe uma descoberta</span><span>Fórum</span></div></div></div>`,
  login: `<div class="mock-login"><div class="mock-login-panel"><span class="eyebrow">LEARNLINGO</span><h3>Aprender um pouco todos os dias faz você ir mais longe.</h3><p>Desafios de aprendizagem, metas claras e progresso que você acompanha a cada etapa.</p><img src="assets/lio.svg" width="150" height="157" alt=""><span>Aprenda. Pratique. Evolua.</span></div><div class="mock-login-form"><span class="mock-logo">learnlingo</span><h3>Que bom ter você de volta!</h3><p>Entre para continuar aprendendo.</p><div class="mock-field">Identificação do usuário<span>Seu nome de usuário</span></div><div class="mock-field">Senha<span>••••••••</span></div><span class="mock-submit">Entrar</span><span class="mock-forgot">Esqueceu sua senha?</span><small>Prévia visual, sem autenticação real.</small></div></div>`
};
const captions = {home:'Uma home com boas-vindas, cursos e progresso à vista.',course:'O conteúdo do Moodle em uma apresentação com personalidade.',login:'Uma recepção com a identidade da sua instituição.'};
function selectTab(buttons, selected) {
  buttons.forEach(button => { const active = button === selected; button.setAttribute('aria-selected', String(active)); button.tabIndex = active ? 0 : -1; button.classList.toggle('active', active); });
}
function keyboardTabs(buttons) {
  buttons.forEach((button, index) => button.addEventListener('keydown', event => {
    let target;
    if(event.key==='ArrowRight') target=(index+1)%buttons.length;
    if(event.key==='ArrowLeft') target=(index-1+buttons.length)%buttons.length;
    if(event.key==='Home') target=0;
    if(event.key==='End') target=buttons.length-1;
    if(target!==undefined){event.preventDefault(); buttons[target].click(); buttons[target].focus();}
  }));
}
const screenButtons=[...document.querySelectorAll('[data-screen]')];
screenButtons.forEach(button => button.addEventListener('click', () => {
  selectTab(screenButtons,button);
  panel.innerHTML=screens[button.dataset.screen];
  panel.setAttribute('aria-labelledby',button.id);
  document.querySelector('#preview-caption').textContent=captions[button.dataset.screen];
  localize();
}));
keyboardTabs(screenButtons);
panel.innerHTML=screens.home;

const paletteMessages={green:'Um verde cheio de vontade de aprender.',blue:'Azul para descobrir novos horizontes.',purple:'Violeta para dar espaço à criatividade.',coral:'Coral para receber com energia e proximidade.'};
const paletteNames={green:'Verde',blue:'Azul',purple:'Violeta',coral:'Coral'};
document.querySelectorAll('[data-palette]').forEach(button => button.addEventListener('click',() => {
  document.querySelectorAll('[data-palette]').forEach(item=>{const active=item===button; item.classList.toggle('active',active); item.setAttribute('aria-pressed',String(active)); item.setAttribute('aria-label',paletteNames[item.dataset.palette]+(active?', selecionado':''));});
  document.querySelector('.brand-art').dataset.theme=button.dataset.palette;
  document.querySelector('.palette-feedback').textContent=paletteMessages[button.dataset.palette];
  localize();
}));

const prompts={
  escola:{text:'Personalize a home do meu Moodle para uma escola de idiomas. Use azul, um tom acolhedor e destaque os cursos.',response:'A proposta combina azul, boas-vindas e uma área de cursos em destaque.',category:'ESCOLA DE IDIOMAS',headline:'O mundo começa<br>com uma conversa.',cta:'Descubra seu próximo idioma ↗',mark:'a.',background:'#e3effd',border:'#d0e3f4',accent:'#4f8cbd'},
  empresa:{text:'Personalize meu Moodle para a formação da nossa equipe. Use violeta, linguagem direta e um rodapé com os canais internos.',response:'A proposta usa violeta, uma mensagem de boas-vindas para a equipe e um rodapé institucional.',category:'DESENVOLVIMENTO DA EQUIPE',headline:'Sua próxima ideia<br>começa aqui.',cta:'Explore sua próxima habilidade ↗',mark:'↗',background:'#ede4f8',border:'#dfcff1',accent:'#8c68b3'},
  universidade:{text:'Crie uma proposta de home para nossa universidade. Use coral, destaque a identidade institucional e os links de acesso.',response:'A proposta valoriza a marca, a cor coral e o acesso às áreas da universidade.',category:'NOSSA UNIVERSIDADE',headline:'Conhecimento para<br>transformar caminhos.',cta:'Conheça seu espaço de aprendizagem ↗',mark:'u.',background:'#fae9e1',border:'#f0d8c8',accent:'#ba795d'}
};
let currentPrompt='escola';
document.querySelectorAll('[data-prompt]').forEach(button=>button.addEventListener('click',()=>{
  currentPrompt=button.dataset.prompt;
  const prompt=prompts[currentPrompt];
  document.querySelectorAll('[data-prompt]').forEach(item=>{item.classList.toggle('active',item===button);item.setAttribute('aria-pressed',String(item===button));});
  document.querySelector('#prompt-text').textContent=prompt.text;
  document.querySelector('#response-text').textContent=prompt.response;
  const result=document.querySelector('#chat-result');
  result.style.background=prompt.background;result.style.borderColor=prompt.border;result.style.setProperty('--result-accent',prompt.accent);
  result.innerHTML=`<span class="result-mark">${prompt.mark}</span><div><span>${prompt.category}</span><strong>${prompt.headline}</strong><span class="result-button">${prompt.cta}</span></div>`;
  localize();
}));
let toastTimer;
function toast(message){const el=document.querySelector('#toast'); el.textContent=message;localize(el);el.classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(()=>el.classList.remove('show'),4200);}
document.querySelector('#copy-prompt').addEventListener('click',async()=>{
  const text=translate(prompts[currentPrompt].text)+' '+translate('Use a conexão MCP autorizada para ler o estado atual do LearnLingo antes de propor mudanças. Preserve as configurações não relacionadas ao pedido e apresente a prévia para minha confirmação antes de gravar.');
  try{
    if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(text);}
    else{const input=document.createElement('textarea');input.value=text;input.style.position='fixed';input.style.opacity='0';document.body.append(input);input.select();const copied=document.execCommand('copy');input.remove();if(!copied)throw new Error('Clipboard unavailable');}
    toast('Pedido copiado. Cole na sua conversa com a IA.');
  }catch(error){toast('Não foi possível copiar. Selecione o texto do pedido e copie manualmente.');}
});

const wizardSteps=[
 {name:'Sua marca vem primeiro.',description:'Escolha as cores, envie a logo e o favicon. O assistente proposto ajusta as imagens automaticamente, preservando as proporções.',extra:'Paletas prontas e redimensionamento com PHP GD.',visual:`<span>UMA PALETA PARA CHAMAR DE SUA</span><div class="wizard-color-line"><i style="background:#82ce37"></i><i style="background:#5ca1e4"></i><i style="background:#a283d5"></i><i style="background:#e68b6b"></i><i style="background:#e8bc50"></i></div><div class="wizard-upload"><span>${icon('palette')}</span><div>Logo + favicon<small>Sua identidade, do cabeçalho à aba do navegador.</small></div></div>`},
 {name:'Que cara terá a sua home?',description:'Compare a página inicial LearnLingo com a home nativa do Moodle. Prefere uma home exclusiva? A personalização com HTML e IA também tem espaço.',extra:'Escolhas visuais, com opções para conectar ChatGPT ou Claude ou copiar e colar.',visual:`<span>DUAS HOMES, DOIS JEITOS DE RECEBER</span><div class="choice-previews"><div class="choice-preview"><div class="choice-art">${icon('spark')}</div><strong>LearnLingo</strong><small>Visual e acolhedora</small></div><div class="choice-preview"><div class="choice-art">${icon('grid')}</div><strong>Moodle nativo</strong><small>As listas que você conhece</small></div></div>`},
 {name:'Personalidade até o rodapé.',description:'Escolha o rodapé nativo ou um conteúdo em HTML, ajuste a largura da home e a apresentação do topo dos cursos.',extra:'A IA também pode ajudar a criar o seu rodapé.',visual:`<span>OS DETALHES TAMBÉM CONTAM</span><div class="footer-sample"><strong>sua escola.</strong><div><span>Institucional</span><span>Nossos cursos</span><span>Fale conosco</span></div><hr><small>Conhecimento que vai com você.</small></div>`},
 {name:'O caminho do aluno, bem pensado.',description:'Painel para agenda, linha do tempo e blocos; Meus cursos para acesso direto. Você escolhe um ou ambos, entendendo quando as listas podem se repetir.',extra:'Esta etapa aparece quando ao menos uma dessas páginas está habilitada.',visual:`<span>ESCOLHA O QUE FAZ SENTIDO</span><div class="choice-previews"><div class="choice-preview"><div class="choice-art">${icon('grid')}</div><strong>Painel</strong><small>Agenda e acompanhamento</small></div><div class="choice-preview"><div class="choice-art">${icon('book')}</div><strong>Meus cursos</strong><small>Acesso direto aos cursos</small></div></div>`},
 {name:'Uma boa recepção faz diferença.',description:'Personalize o título, a mensagem e a frase institucional do login, com logo própria e imagem de fundo. A autenticação continua sendo do Moodle.',extra:'Última etapa do assistente, com prévia e personalização opcional por IA.',visual:`<span>BOAS-VINDAS COM A SUA IDENTIDADE</span><div class="login-sample"><div><strong>Que bom<br>ter você aqui.</strong><small>Seu próximo passo começa agora.</small></div><div><small>Acesse sua conta</small><i></i><i></i><i class="login-sample-button"></i></div></div>`}
];
const stepButtons=[...document.querySelectorAll('[data-step]')];
const wizardPanel=document.querySelector('#wizard-panel');
function showStep(index){const step=wizardSteps[index];wizardPanel.innerHTML=`<div><span class="eyebrow">ETAPA ${index+1} · PRÉVIA DO ASSISTENTE</span><h3>${step.name}</h3><p>${step.description}</p><p class="step-extra">${icon('check')}${step.extra}</p></div><div class="wizard-visual">${step.visual}</div>`;wizardPanel.setAttribute('aria-labelledby',`step-tab-${index}`);selectTab(stepButtons,stepButtons[index]);localize(wizardPanel);}
stepButtons.forEach(button=>button.addEventListener('click',()=>showStep(Number(button.dataset.step))));
keyboardTabs(stepButtons);showStep(0);

const menuToggle=document.querySelector('.menu-toggle');
const mobileNav=document.querySelector('#mobile-nav');
function closeMenu(){mobileNav.hidden=true;menuToggle.setAttribute('aria-expanded','false');menuToggle.setAttribute('aria-label','Abrir menu');localize(menuToggle);}
menuToggle.addEventListener('click',()=>{const open=mobileNav.hidden;mobileNav.hidden=!open;menuToggle.setAttribute('aria-expanded',String(open));menuToggle.setAttribute('aria-label',open?'Fechar menu':'Abrir menu');localize(menuToggle);});
mobileNav.querySelectorAll('a').forEach(link=>link.addEventListener('click',closeMenu));
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!mobileNav.hidden){closeMenu();menuToggle.focus();}});
document.addEventListener('click',event=>{if(!mobileNav.hidden&&!event.target.closest('.header'))closeMenu();});
const installDialog=document.querySelector('#install-dialog');
document.querySelector('#open-install').addEventListener('click',()=>{installDialog.showModal();document.body.style.overflow='hidden';});
document.querySelector('.dialog-close').addEventListener('click',()=>installDialog.close());
installDialog.addEventListener('close',()=>{document.body.style.overflow='';});
installDialog.addEventListener('click',event=>{if(event.target===installDialog){const r=installDialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)installDialog.close();}});

// Localize the initial dynamic previews after they are mounted.
localize();
