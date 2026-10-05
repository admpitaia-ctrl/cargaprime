document.querySelector('.menu-button')?.addEventListener('click', (event) => {
  const nav = document.querySelector('#main-nav');
  const open = nav.classList.toggle('open');
  event.currentTarget.setAttribute('aria-expanded', String(open));
});

const showAjaxToast = (message, ok = true) => {
  let toast = document.querySelector('.ajax-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.className = 'ajax-toast';
    toast.setAttribute('role', 'status');
    document.body.appendChild(toast);
  }
  toast.classList.toggle('is-error', !ok);
  toast.textContent = `${ok ? '✓' : '!'} ${message}`;
  toast.classList.add('is-visible');
  clearTimeout(window.cpAjaxToastTimer);
  window.cpAjaxToastTimer = setTimeout(() => toast.classList.remove('is-visible'), 3600);
};

const refreshAjaxSections = async (selectors) => {
  const unique = [...new Set((selectors || []).filter(Boolean))];
  if (!unique.length) return;
  const response = await fetch(window.location.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}, cache: 'no-store'});
  if (!response.ok) return;
  const page = new DOMParser().parseFromString(await response.text(), 'text/html');
  unique.forEach((selector) => {
    const current = [...document.querySelectorAll(selector)];
    const updated = [...page.querySelectorAll(selector)];
    current.forEach((node, index) => {
      if (updated[index]) node.replaceWith(updated[index].cloneNode(true));
    });
  });
};

// Validação visual imediata do limite de stock.
// O servidor continua a ser a fonte de verdade e repete a validação no POST/checkout.
document.addEventListener('input', (event) => {
  const stockInput = event.target.closest?.('[data-stock-quantity]');
  if (!stockInput) return;
  const stock = Number(stockInput.dataset.stockLimit);
  const quantity = Number(stockInput.value);
  const feedback = stockInput.closest('form')?.querySelector('.save-feedback');
  if (Number.isFinite(stock) && Number.isFinite(quantity) && quantity > stock) {
    const message = `Só existem ${stock.toLocaleString('pt-PT', {maximumFractionDigits: 1})} t disponíveis. Reduza a quantidade.`;
    stockInput.setAttribute('aria-invalid', 'true');
    if (feedback) feedback.textContent = message;
  } else {
    stockInput.removeAttribute('aria-invalid');
    if (feedback && feedback.textContent.startsWith('Só existem ')) feedback.textContent = '';
  }
});

document.addEventListener('submit', async (event) => {
  const form = event.target.closest('.ajax-form');
  if (!form) return;
  event.preventDefault();
  if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;

  const button = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
  const feedback = form.querySelector('.save-feedback');
  const requestedAction = button?.name === 'action'
    ? button.value
    : form.querySelector('input[name="action"]')?.value;
  const stockInput = form.querySelector('[data-stock-quantity]');

  // A remoção deve continuar disponível mesmo que o stock tenha mudado.
  // Nas restantes operações, bloqueamos no navegador qualquer quantidade
  // inválida ou superior ao limite atual. O PHP repete esta validação.
  if (requestedAction !== 'remove' && stockInput) {
    const quantity = Number(stockInput.value);
    const stock = Number(stockInput.dataset.stockLimit || stockInput.max);
    const validStep = Number.isFinite(quantity) && Math.abs(quantity * 2 - Math.round(quantity * 2)) < 0.000001;
    if (!Number.isFinite(quantity) || quantity <= 0 || !validStep || !Number.isFinite(stock) || quantity > stock) {
      const message = Number.isFinite(stock)
        ? `Só existem ${stock.toLocaleString('pt-PT')} t disponíveis. Reduza a quantidade.`
        : 'Indique uma quantidade válida.';
      if (feedback) feedback.textContent = message;
      showAjaxToast(message, false);
      stockInput.focus();
      return;
    }
  }
  if (requestedAction !== 'remove' && !form.checkValidity()) {
    form.reportValidity();
    showAjaxToast('Verifique os campos indicados.', false);
    return;
  }
  const formData = new FormData(form);
  if (button?.name && !formData.has(button.name)) formData.append(button.name, button.value);
  if (button) { button.disabled = true; button.classList.add('is-loading'); }
  if (feedback) feedback.textContent = 'A guardar…';

  try {
    // Use the HTML attributes directly. A form control named "action" can
    // shadow form.action and turn the request URL into "[object HTMLInputElement]".
    const requestUrl = form.getAttribute('action') || window.location.href;
    const requestMethod = (form.getAttribute('method') || 'POST').toUpperCase();
    const response = await fetch(requestUrl, {
      method: requestMethod,
      body: formData,
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
    });
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
      const body = await response.text();
      console.error('Resposta inesperada do servidor:', body);
      const plainMessage = body.replace(/<script[\s\S]*?<\/script>/gi, ' ').replace(/<style[\s\S]*?<\/style>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
      throw new Error(plainMessage && plainMessage.length < 220 ? plainMessage : 'O servidor devolveu uma resposta inesperada. Atualize a página e tente novamente.');
    }

    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.message || 'Não foi possível guardar.');
    if (feedback) feedback.textContent = '✓ ' + result.message;
    showAjaxToast(result.message, true);
    document.dispatchEvent(new CustomEvent('cargaprime:ajax-success', {detail: {form, result}}));

    if (result.status && form.dataset.removeOnSuccess === 'true') {
      form.closest('.order-card')?.querySelector('.status-pill')?.replaceChildren(result.status);
      form.remove();
    }
    if (result.close_dialog) form.closest('dialog')?.close();
    const selectors = [...(Array.isArray(result.refresh) ? result.refresh : [])];
    if (form.dataset.refreshSelector) selectors.push(...form.dataset.refreshSelector.split('|'));
    await refreshAjaxSections(selectors);
    if (form.dataset.resetOnSuccess === 'true') form.reset();
    if (result.redirect) window.location.assign(result.redirect);
  } catch (error) {
    if (feedback) feedback.textContent = error.message;
    showAjaxToast(error.message, false);
  } finally {
    if (button?.isConnected) { button.disabled = false; button.classList.remove('is-loading'); }
  }
});

const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) { entry.target.classList.add('is-visible'); revealObserver.unobserve(entry.target); }
  });
}, {threshold: 0.14});
document.querySelectorAll('.reveal').forEach((element) => revealObserver.observe(element));

document.querySelectorAll('.password-toggle').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.target);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.textContent = show ? 'Ocultar' : 'Ver';
  });
});

const story = document.querySelector('.logistics-story');
if (story) {
  const scenes = [...story.querySelectorAll('[data-story-scene]')];
  const navButtons = [...story.querySelectorAll('[data-story-go]')];
  const progress = story.querySelector('.story-progress-fill');
  const activateScene = (index) => {
    scenes.forEach((scene, i) => scene.classList.toggle('active', i === index));
    navButtons.forEach((button, i) => button.classList.toggle('active', i === index));
  };
  navButtons.forEach((button) => button.addEventListener('click', () => scenes[Number(button.dataset.storyGo)]?.scrollIntoView({behavior: 'smooth', block: 'center'})));
  const updateStory = () => {
    const rect = story.getBoundingClientRect();
    const available = Math.max(1, story.offsetHeight - window.innerHeight);
    const ratio = Math.min(1, Math.max(0, -rect.top / available));
    if (progress) progress.style.transform = `scaleX(${ratio})`;
    activateScene(Math.min(scenes.length - 1, Math.floor(ratio * scenes.length)));
    scenes.forEach((scene) => {
      const sceneRect = scene.getBoundingClientRect();
      const local = (window.innerHeight - sceneRect.top) / (window.innerHeight + sceneRect.height);
      scene.style.setProperty('--scene-shift', `${Math.max(-1, Math.min(1, local - .5)) * 34}px`);
    });
  };
  updateStory();
  window.addEventListener('scroll', updateStory, {passive: true});
  window.addEventListener('resize', updateStory);
}

// Versão 2.0: a filmagem tem vida própria; o scroll conduz a câmara.
const driveStory = document.querySelector('[data-drive-story]');
if (driveStory && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const stage = driveStory.querySelector('.drive-stage');
  const copies = [...driveStory.querySelectorAll('[data-drive-copy]')];
  const nav = [...driveStory.querySelectorAll('[data-drive-go]')];
  let ticking = false;
  let cameraProgress = 0;
  let filmProgress = 0;
  let lastFrame = performance.now();
  let lastInteraction = 0;
  const clamp = (value, min = 0, max = 1) => Math.min(max, Math.max(min, value));
  const readScroll = () => {
    const rect = driveStory.getBoundingClientRect();
    const distance = Math.max(1, driveStory.offsetHeight - stage.offsetHeight);
    return clamp(-rect.top / distance);
  };
  const renderDrive = (now = performance.now()) => {
    const delta = Math.min(50, now - lastFrame); lastFrame = now;
    cameraProgress += (readScroll() - cameraProgress) * .075;
    const visible = driveStory.getBoundingClientRect().bottom > 0 && driveStory.getBoundingClientRect().top < innerHeight;
    if (now - lastInteraction > 1300 && visible) filmProgress = (filmProgress + delta / 70000) % 1;
    else filmProgress += (cameraProgress - filmProgress) * .065;
    const progress = clamp(filmProgress);
    const chapter = Math.min(3, Math.floor(progress * 4));
    const viewport = window.innerWidth;
    const truckStart = -Math.min(650, viewport * .5);
    const truckEnd = viewport - Math.min(260, viewport * .18);
    const truckX = truckStart + (truckEnd - truckStart) * progress + Math.sin(now / 1350) * Math.min(22, viewport * .018);
    const panoramaX = -(Math.max(viewport, viewport * 1.4) * progress);
    const workerProgress = clamp(progress / .23);
    stage.style.setProperty('--drive-progress', progress.toFixed(4));
    stage.style.setProperty('--panorama-x', `${panoramaX}px`);
    stage.style.setProperty('--truck-x', `${truckX}px`);
    stage.style.setProperty('--truck-y', `${Math.sin(now / 155) * 1.8}px`);
    stage.style.setProperty('--worker-x', `${-viewport * .18 + workerProgress * viewport * .34}px`);
    copies.forEach((copy, index) => copy.classList.toggle('active', index === chapter));
    nav.forEach((button, index) => button.classList.toggle('active', index === chapter));
    stage.dataset.chapter = String(chapter);
    ticking = false;
    requestAnimationFrame(renderDrive);
  };
  const requestDriveRender = () => { lastInteraction = performance.now(); ticking = true; };
  nav.forEach((button) => button.addEventListener('click', () => {
    const destination = driveStory.offsetTop + (driveStory.offsetHeight - stage.offsetHeight) * (Number(button.dataset.driveGo) / 3);
    window.scrollTo({top: destination, behavior: 'smooth'});
  }));
  cameraProgress = readScroll(); filmProgress = cameraProgress; requestAnimationFrame(renderDrive);
  window.addEventListener('scroll', requestDriveRender, {passive: true});
  window.addEventListener('resize', requestDriveRender);
}

// O navegador exige um gesto do utilizador antes de iniciar áudio.
const soundButton = document.querySelector('.sound-toggle');
if (soundButton) {
  let audioContext, master;
  const startSound = async () => {
    audioContext ||= new (window.AudioContext || window.webkitAudioContext)(); await audioContext.resume();
    if (!master) {
      master = audioContext.createGain(); master.gain.value = .055; master.connect(audioContext.destination);
      const engine = audioContext.createOscillator(); engine.type = 'sawtooth'; engine.frequency.value = 47;
      const filter = audioContext.createBiquadFilter(); filter.type = 'lowpass'; filter.frequency.value = 135;
      const engineGain = audioContext.createGain(); engineGain.gain.value = .72;
      engine.connect(filter).connect(engineGain).connect(master); engine.start();
      const lfo = audioContext.createOscillator(), lfoGain = audioContext.createGain();
      lfo.frequency.value = 1.7; lfoGain.gain.value = 4; lfo.connect(lfoGain).connect(engine.frequency); lfo.start();
    }
    master.gain.setTargetAtTime(.055, audioContext.currentTime, .08);
  };
  soundButton.addEventListener('click', async () => {
    const enabled = soundButton.getAttribute('aria-pressed') !== 'true';
    if (enabled) await startSound(); else master?.gain.setTargetAtTime(0, audioContext.currentTime, .06);
    soundButton.setAttribute('aria-pressed', String(enabled));
    soundButton.querySelector('.sound-label').textContent = enabled ? 'Silenciar' : 'Ativar som';
    soundButton.querySelector('.sound-icon').textContent = enabled ? '◖))' : '♪';
  });
}

/* --------------------------------------------------------------------------
 * Stock em tempo quase real
 * --------------------------------------------------------------------------
 * A base de dados é a fonte de verdade. Adicionar ao carrinho NÃO reserva
 * stock. O desconto só acontece no checkout, dentro de uma transação MySQL.
 * Este sincronizador apenas mantém páginas já abertas atualizadas; a proteção
 * definitiva continua no PHP/MySQL.
 */
const formatTons = (value) => Number(value).toLocaleString('pt-PT', {
  minimumFractionDigits: 1,
  maximumFractionDigits: 1,
});

let cpLastStockSignature = '';
let cpStockSyncRunning = false;

const applyLiveStock = (products) => {
  const signature = JSON.stringify(products.map((p) => [p.id, Number(p.stock_tons), Boolean(p.active)]));
  const changed = cpLastStockSignature !== '' && signature !== cpLastStockSignature;
  cpLastStockSignature = signature;

  products.forEach((product) => {
    const stock = Number(product.stock_tons);
    const out = !Number.isFinite(stock) || stock < 0.5 || product.active === false;

    document.querySelectorAll(`[data-product-card][data-product-id="${product.id}"]`).forEach((card) => {
      card.classList.toggle('is-out-of-stock', out);
      const label = card.querySelector('[data-stock-label]');
      if (label) {
        label.classList.toggle('is-out', out);
        label.innerHTML = `<i></i>${out ? 'Sem stock' : `${formatTons(stock)} t em stock`}`;
      }
      const input = card.querySelector('[data-product-stock-input]');
      const submit = card.querySelector('[data-stock-submit]');
      const help = card.querySelector('[data-stock-help]');
      if (input) {
        // Mantemos o limite real no data-attribute, mas não usamos o atributo HTML max.
        // Assim o utilizador consegue escrever uma quantidade superior e recebe uma
        // mensagem explícita da aplicação, em vez de o navegador apenas travar o valor.
        input.dataset.stockLimit = String(Math.max(0, stock));
        input.disabled = out;
        const currentQuantity = Number(input.value);
        const feedback = input.closest('form')?.querySelector('.save-feedback');
        if (!out && Number.isFinite(currentQuantity) && currentQuantity > stock) {
          input.setAttribute('aria-invalid', 'true');
          if (feedback) feedback.textContent = `Só existem ${formatTons(stock)} t disponíveis. Reduza a quantidade.`;
        } else {
          input.removeAttribute('aria-invalid');
          if (feedback && feedback.textContent.startsWith('Só existem ')) feedback.textContent = '';
        }
      }
      if (submit) {
        submit.disabled = out;
        submit.innerHTML = out ? 'Indisponível' : 'Adicionar ao carrinho <b>↗</b>';
      }
      if (help) help.textContent = out
        ? 'Stock esgotado.'
        : `Stock atual: ${formatTons(stock)} t. O desconto ocorre ao confirmar a encomenda.`;
    });

    document.querySelectorAll(`[data-admin-product-id="${product.id}"] [data-admin-stock-label]`).forEach((label) => {
      label.textContent = `${formatTons(stock)} t em stock`;
    });

    document.querySelectorAll(`form.product-editor input[name="product_id"][value="${product.id}"]`).forEach((productIdInput) => {
      const form = productIdInput.closest('form');
      const input = form?.querySelector('[data-admin-stock-input]');
      const original = form?.querySelector('[data-admin-stock-original]');
      if (!input || !original) return;
      // Atualiza formulários administrativos ainda intocados. Se o administrador
      // já começou a alterar o stock, respeitamos a edição manual.
      if (document.activeElement !== input && String(input.value) === String(original.value)) {
        input.value = String(stock);
        original.value = String(stock);
      }
    });
  });

  // No carrinho, a marcação de erro e o botão de checkout dependem do stock.
  // Quando outra sessão compra, recarregamos só a secção do carrinho.
  if (changed && document.querySelector('#cart-content')) {
    refreshAjaxSections(['#cart-content']);
  }
  if (changed) {
    document.dispatchEvent(new CustomEvent('cargaprime:stock-changed', {detail: {products}}));
  }
};

const syncLiveStock = async () => {
  if (cpStockSyncRunning || document.hidden) return;
  cpStockSyncRunning = true;
  try {
    const response = await fetch(`${document.documentElement.dataset.appBase || ''}/api/stock.php`, {
      headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
      cache: 'no-store',
    });
    if (!response.ok) return;
    const result = await response.json();
    if (result.ok && Array.isArray(result.products)) applyLiveStock(result.products);
  } catch (error) {
    // Falha de sincronização visual não deve impedir a compra: o checkout
    // volta a validar o stock diretamente na base de dados.
    console.debug('Sincronização de stock indisponível:', error);
  } finally {
    cpStockSyncRunning = false;
  }
};

window.addEventListener('focus', syncLiveStock);
document.addEventListener('visibilitychange', () => { if (!document.hidden) syncLiveStock(); });
document.addEventListener('cargaprime:ajax-success', syncLiveStock);
setInterval(syncLiveStock, 5000);
syncLiveStock();
