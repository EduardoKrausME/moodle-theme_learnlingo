'use strict';


const icon = name => `<svg aria-hidden="true"><use href="#i-${name}"/></svg>`;
const panel = document.querySelector('#preview-panel');
const nav = `<div class="mock-nav"><span class="mock-logo">learnlingo</span><span class="active">Homepage</span><span>My courses</span><span>Dashboard</span><span class="mock-avatar">AL</span></div>`;
const courseCard = (color, symbol, category, title, progress) => `<article class="mock-card"><div class="mock-art art-${color}">${icon(symbol)}</div><span>${category}</span><strong>${title}</strong><div class="mock-progress"><i style="width:${progress}%"></i></div><small>${progress}% complete</small></article>`;
const screens = {
  home: `${nav}<div class="mock-body"><div class="mock-welcome"><div><span class="eyebrow">LEARN AT YOUR OWN PACE</span><h3>Hi, Alex! Ready for the next step?</h3><p>A little curiosity today. New possibilities tomorrow.</p></div><img src="../assets/lio.svg" width="105" height="110" alt=""></div><h4>Pick up where you left off</h4><div class="mock-courses">${courseCard('purple','palette','CREATIVITY','Let creativity flow',60)}${courseCard('blue','chat','COMMUNICATION','Ideas that connect',35)}${courseCard('orange','spark','DEVELOPMENT','Learning to learn',80)}</div></div>`,
  course: `${nav}<div class="course-layout"><aside class="course-sidebar"><strong>Let creativity flow</strong><span>✓ Start here</span><span class="selected">02 · A fresh perspective</span><span>03 · From paper to the world</span><span>04 · Your final project</span></aside><div class="course-main"><div class="course-banner"><span class="eyebrow">KEEP MOVING FORWARD</span><h3>Let creativity flow</h3><p>Explore new ideas and turn possibilities into projects.</p>${icon('palette')}</div><div class="course-progress-row"><span>Your progress</span><div class="progress-track"><span></span></div><span>60% complete</span></div><h4>02 · A fresh perspective</h4><div class="activity-row">${icon('book')}<span>Seeing new possibilities</span><span>✓</span></div><div class="activity-row">${icon('play')}<span>One idea can change everything</span><span>Video</span></div><div class="activity-row">${icon('chat')}<span>Share a discovery</span><span>Forum</span></div></div></div>`,
  login: `<div class="mock-login"><div class="mock-login-panel"><span class="eyebrow">LEARNLINGO</span><h3>Learning a little every day takes you further.</h3><p>Learning challenges, clear goals and progress you can follow at every step.</p><img src="../assets/lio.svg" width="150" height="157" alt=""><span>Learn. Practice. Grow.</span></div><div class="mock-login-form"><span class="mock-logo">learnlingo</span><h3>Welcome back!</h3><p>Sign in to keep learning.</p><div class="mock-field">Username<span>Your username</span></div><div class="mock-field">Password<span>••••••••</span></div><span class="mock-submit">Sign in</span><span class="mock-forgot">Forgot your password?</span><small>Visual preview, no real authentication.</small></div></div>`
};
const captions = {home:'A welcoming homepage with courses and progress in view.',course:'Your Moodle content, presented with personality.',login:'A welcome that reflects your institution.'};
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
}));
keyboardTabs(screenButtons);
panel.innerHTML=screens.home;

const paletteMessages={green:'A fresh green with a thirst for learning.',blue:'Blue for discovering new horizons.',purple:'Purple to give creativity room to grow.',coral:'Coral for a warm, energetic welcome.'};
const paletteNames={green:'Green',blue:'Blue',purple:'Purple',coral:'Coral'};
document.querySelectorAll('[data-palette]').forEach(button => button.addEventListener('click',() => {
  document.querySelectorAll('[data-palette]').forEach(item=>{const active=item===button; item.classList.toggle('active',active); item.setAttribute('aria-pressed',String(active)); item.setAttribute('aria-label',paletteNames[item.dataset.palette]+(active?', selected':''));});
  document.querySelector('.brand-art').dataset.theme=button.dataset.palette;
  document.querySelector('.palette-feedback').textContent=paletteMessages[button.dataset.palette];
}));

const prompts={
  escola:{text:'Customize my Moodle homepage for a language school. Use blue, a welcoming tone and highlight the courses.',response:'The proposal combines blue, a welcome message and a featured courses area.',category:'LANGUAGE SCHOOL',headline:'The world opens up<br>with a conversation.',cta:'Discover your next language ↗',mark:'a.',background:'#e3effd',border:'#d0e3f4',accent:'#4f8cbd'},
  empresa:{text:'Customize my Moodle for our team\'s training. Use purple, clear language and a footer with internal contact channels.',response:'The proposal uses purple, a welcome message for the team and an institutional footer.',category:'TEAM DEVELOPMENT',headline:'Your next idea<br>starts here.',cta:'Explore your next skill ↗',mark:'↗',background:'#ede4f8',border:'#dfcff1',accent:'#8c68b3'},
  universidade:{text:'Create a homepage proposal for our university. Use coral, highlight our institutional identity and access links.',response:'The proposal highlights the brand, coral colors and access to university areas.',category:'OUR UNIVERSITY',headline:'Knowledge to<br>transform your journey.',cta:'Explore your learning space ↗',mark:'u.',background:'#fae9e1',border:'#f0d8c8',accent:'#ba795d'}
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
}));
let toastTimer;
function toast(message){const el=document.querySelector('#toast'); el.textContent=message;el.classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(()=>el.classList.remove('show'),4200);}
document.querySelector('#copy-prompt').addEventListener('click',async()=>{
  const text=prompts[currentPrompt].text+' '+'Use the authorized connection between ChatGPT or Claude and my Moodle to review the current LearnLingo settings before proposing changes. Preserve settings unrelated to the request and show me a preview for confirmation before saving.';
  try{
    if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(text);}
    else{const input=document.createElement('textarea');input.value=text;input.style.position='fixed';input.style.opacity='0';document.body.append(input);input.select();const copied=document.execCommand('copy');input.remove();if(!copied)throw new Error('Clipboard unavailable');}
    toast('Prompt copied. Paste it into your AI conversation.');
  }catch(error){toast('Could not copy. Select the prompt text and copy it manually.');}
});

const wizardSteps=[
 {name:'Your brand comes first.',description:'Choose colors and upload your logo and favicon. The proposed wizard resizes images automatically while preserving their proportions.',extra:'Ready-made palettes and resizing with PHP GD.',visual:`<span>A PALETTE TO CALL YOUR OWN</span><div class="wizard-color-line"><i style="background:#82ce37"></i><i style="background:#5ca1e4"></i><i style="background:#a283d5"></i><i style="background:#e68b6b"></i><i style="background:#e8bc50"></i></div><div class="wizard-upload"><span>${icon('palette')}</span><div>Logo + favicon<small>Your identity, from the header to the browser tab.</small></div></div>`},
 {name:'How will your homepage look?',description:'Compare the LearnLingo homepage with Moodle\'s native homepage. Want a unique homepage? There\'s room for HTML and AI customization too.',extra:'Visual choices, with options to connect ChatGPT or Claude or copy and paste.',visual:`<span>TWO HOMEPAGES, TWO WAYS TO WELCOME</span><div class="choice-previews"><div class="choice-preview"><div class="choice-art">${icon('spark')}</div><strong>LearnLingo</strong><small>Visual and welcoming</small></div><div class="choice-preview"><div class="choice-art">${icon('grid')}</div><strong>Native Moodle</strong><small>The familiar lists</small></div></div>`},
 {name:'Personality down to the footer.',description:'Choose the native footer or custom HTML, adjust the homepage width and the course header presentation.',extra:'AI can also help create your footer.',visual:`<span>THE DETAILS COUNT TOO</span><div class="footer-sample"><strong>your school.</strong><div><span>About us</span><span>Our courses</span><span>Contact us</span></div><hr><small>Knowledge that goes with you.</small></div>`},
 {name:'A thoughtful learner journey.',description:'Dashboard for calendars, timelines and blocks; My courses for direct access. Choose one or both, knowing when course lists might be repeated.',extra:'This step appears when at least one of these pages is enabled.',visual:`<span>CHOOSE WHAT MAKES SENSE</span><div class="choice-previews"><div class="choice-preview"><div class="choice-art">${icon('grid')}</div><strong>Dashboard</strong><small>Schedule and progress</small></div><div class="choice-preview"><div class="choice-art">${icon('book')}</div><strong>My courses</strong><small>Direct access to courses</small></div></div>`},
 {name:'A warm welcome matters.',description:'Customize the login title, message and institutional tagline, with a separate logo and background image. Authentication stays with Moodle.',extra:'The final wizard step, with a preview and optional AI customization.',visual:`<span>A WELCOME WITH YOUR IDENTITY</span><div class="login-sample"><div><strong>So good<br>to have you here.</strong><small>Your next step starts now.</small></div><div><small>Sign in to your account</small><i></i><i></i><i class="login-sample-button"></i></div></div>`}
];
const stepButtons=[...document.querySelectorAll('[data-step]')];
const wizardPanel=document.querySelector('#wizard-panel');
function showStep(index){const step=wizardSteps[index];wizardPanel.innerHTML=`<div><span class="eyebrow">STEP ${index+1} · WIZARD PREVIEW</span><h3>${step.name}</h3><p>${step.description}</p><p class="step-extra">${icon('check')}${step.extra}</p></div><div class="wizard-visual">${step.visual}</div>`;wizardPanel.setAttribute('aria-labelledby',`step-tab-${index}`);selectTab(stepButtons,stepButtons[index]);}
stepButtons.forEach(button=>button.addEventListener('click',()=>showStep(Number(button.dataset.step))));
keyboardTabs(stepButtons);showStep(0);

const menuToggle=document.querySelector('.menu-toggle');
const mobileNav=document.querySelector('#mobile-nav');
function closeMenu(){mobileNav.hidden=true;menuToggle.setAttribute('aria-expanded','false');menuToggle.setAttribute('aria-label','Open menu');}
menuToggle.addEventListener('click',()=>{const open=mobileNav.hidden;mobileNav.hidden=!open;menuToggle.setAttribute('aria-expanded',String(open));menuToggle.setAttribute('aria-label',open?'Close menu':'Open menu');});
mobileNav.querySelectorAll('a').forEach(link=>link.addEventListener('click',closeMenu));
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!mobileNav.hidden){closeMenu();menuToggle.focus();}});
document.addEventListener('click',event=>{if(!mobileNav.hidden&&!event.target.closest('.header'))closeMenu();});
const installDialog=document.querySelector('#install-dialog');
document.querySelector('#open-install').addEventListener('click',()=>{installDialog.showModal();document.body.style.overflow='hidden';});
document.querySelector('.dialog-close').addEventListener('click',()=>installDialog.close());
installDialog.addEventListener('close',()=>{document.body.style.overflow='';});
installDialog.addEventListener('click',event=>{if(event.target===installDialog){const r=installDialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)installDialog.close();}});

// Localize the initial dynamic previews after they are mounted.
