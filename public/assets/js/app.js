/* Wruch Personal — JavaScript da interface (sem dependências). */
(() => {
  'use strict';
  document.documentElement.classList.add('js');
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const csrf = () => document.body.dataset.csrf || '';
  const ICON = (name, cls = 'icon-sm') => `<svg class="${cls}" aria-hidden="true"><use href="/assets/icons/sprite.svg#${name}"></use></svg>`;
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  /* ---------- Requisições ---------- */
  async function api(url, { method = 'GET', body = null, json = null } = {}) {
    const headers = { 'Accept': 'application/json', 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf() };
    let payload = body;
    if (json !== null) { headers['Content-Type'] = 'application/json'; payload = JSON.stringify(json); }
    let res;
    try {
      res = await fetch(url, { method, headers, body: payload, credentials: 'same-origin' });
    } catch (e) {
      throw new Error('Sem conexão. Tente novamente quando a internet voltar.');
    }
    let data = {};
    try { data = await res.json(); } catch (e) { /* resposta sem JSON */ }
    if (!res.ok || data.ok === false) {
      if (res.status === 401) { location.href = '/entrar'; }
      throw new Error(data.error || `Erro ${res.status}`);
    }
    return data;
  }

  /** Upload com barra de progresso (XHR). */
  function upload(url, formData, onProgress) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', url);
      xhr.setRequestHeader('X-CSRF-Token', csrf());
      xhr.setRequestHeader('X-Requested-With', 'fetch');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.onprogress = (e) => { if (e.lengthComputable && onProgress) onProgress(e.loaded / e.total); };
      xhr.onload = () => {
        let data = {};
        try { data = JSON.parse(xhr.responseText); } catch (e) { /* */ }
        if (xhr.status >= 200 && xhr.status < 300 && data.ok !== false) resolve(data);
        else reject(new Error(data.error || (xhr.status === 413 ? 'Arquivo maior que o limite do servidor.' : `Falha no envio (${xhr.status}).`)));
      };
      xhr.onerror = () => reject(new Error('Sem conexão. O arquivo não foi enviado.'));
      xhr.send(formData);
    });
  }

  /* ---------- Toasts ---------- */
  function toast(message, type = 'info') {
    const box = $('#toasts');
    if (!box) return;
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = `${ICON(type === 'error' ? 'alert' : type === 'success' ? 'check' : 'bell', 'icon')}<span>${esc(message)}</span><button type="button" aria-label="Fechar">${ICON('x')}</button>`;
    el.querySelector('button').onclick = () => el.remove();
    box.appendChild(el);
    setTimeout(() => el.remove(), type === 'error' ? 9000 : 5000);
  }
  window.wpToast = toast;
  try {
    JSON.parse($('#toasts')?.dataset.flashes || '[]').forEach((f) => toast(f.message, f.type));
  } catch (e) { /* */ }

  /* ---------- Tema claro / noturno ---------- */
  const mqDark = window.matchMedia('(prefers-color-scheme: dark)');
  const effectiveTheme = () => document.documentElement.dataset.theme || (mqDark.matches ? 'dark' : 'light');
  $$('[data-theme-toggle]').forEach((b) => b.addEventListener('click', () => {
    const next = effectiveTheme() === 'dark' ? 'light' : 'dark';
    const apply = () => { document.documentElement.dataset.theme = next; };
    if (document.startViewTransition && !reduceMotion()) document.startViewTransition(apply); else apply();
    document.cookie = `wp_theme=${next}; path=/; max-age=31536000; samesite=lax`;
  }));
  function reduceMotion() { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; }

  /* ---------- Movimento: cascata, números, anéis, barra inferior ---------- */
  $$('.enter').forEach((wrap) => Array.from(wrap.children).forEach((c, i) => c.style.setProperty('--i', Math.min(i, 12))));
  const tabbar = $('.tabbar');
  if (tabbar) {
    const idx = +getComputedStyle(tabbar).getPropertyValue('--idx') || 0;
    let prev = null;
    try { prev = sessionStorage.getItem('wp-tab'); sessionStorage.setItem('wp-tab', String(idx)); } catch (e) { /* */ }
    const pill = $('.pill', tabbar);
    if (pill && prev !== null && +prev !== idx && !reduceMotion()) {
      pill.style.transition = 'none';
      tabbar.style.setProperty('--idx', prev);
      requestAnimationFrame(() => requestAnimationFrame(() => { pill.style.transition = ''; tabbar.style.setProperty('--idx', idx); }));
    }
  }
  const countUp = (el) => {
    const target = parseFloat(el.dataset.count);
    if (Number.isNaN(target)) return;
    const dec = +(el.dataset.decimals || 0);
    const fmt = (v) => (el.dataset.prefix || '') + v.toLocaleString('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + (el.dataset.suffix || '');
    if (reduceMotion()) { el.textContent = fmt(target); return; }
    const t0 = performance.now(); const dur = 900;
    const step = (t) => { const k = Math.min(1, (t - t0) / dur); const e = 1 - Math.pow(1 - k, 3); el.textContent = fmt(target * e); if (k < 1) requestAnimationFrame(step); };
    requestAnimationFrame(step);
  };
  $$('[data-count]').forEach(countUp);
  $$('.ring .val').forEach((c) => {
    if (reduceMotion()) return;
    const full = c.getAttribute('stroke-dasharray');
    const off = c.dataset.ringOff;
    c.style.transition = 'none';
    c.setAttribute('stroke-dashoffset', full);
    requestAnimationFrame(() => requestAnimationFrame(() => { c.style.transition = ''; c.setAttribute('stroke-dashoffset', off); }));
  });
  function confetti(root) {
    if (!root || reduceMotion()) return;
    const box = document.createElement('div');
    box.className = 'confetti';
    const colors = ['#b4daf3', '#b4daf3', '#ffffff', '#ffd66e', '#8fd9b0', '#c2b2ff', '#ff9b7a', '#ffae96'];
    for (let i = 0; i < 70; i++) {
      const p = document.createElement('i');
      p.style.left = `${Math.random() * 100}%`;
      p.style.background = colors[i % colors.length];
      p.style.setProperty('--x', `${(Math.random() - .5) * 160}px`);
      p.style.setProperty('--r', `${360 + Math.random() * 720}deg`);
      p.style.setProperty('--d', `${1.8 + Math.random() * 1.6}s`);
      p.style.setProperty('--delay', `${Math.random() * .5}s`);
      box.appendChild(p);
    }
    root.appendChild(box);
    setTimeout(() => box.remove(), 4200);
  }
  confetti($('[data-confetti]'));

  /* ---------- Menus "⋯" ---------- */
  document.addEventListener('click', (e) => { $$('details.menu-pop[open]').forEach((d) => { if (!d.contains(e.target)) d.open = false; }); });

  /* ---------- Caixa de entrada: item resolvido desliza para fora ---------- */
  $$('[data-inbox-form]').forEach((f) => f.addEventListener('submit', (e) => { if (!e.defaultPrevented) f.closest('.inbox-item')?.classList.add('leaving'); }));

  /* ---------- Menu lateral ---------- */
  const sidebar = $('#sidebar');
  const scrim = $('.scrim');
  const menuBtns = Array.from(document.querySelectorAll('[data-open-menu]'));
  const menuBtn = menuBtns[0];
  const closeMenu = () => { sidebar?.classList.remove('open'); scrim?.classList.remove('show'); menuBtns.forEach((b) => b.setAttribute('aria-expanded', 'false')); };
  menuBtns.forEach((b) => b.addEventListener('click', () => { sidebar.classList.add('open'); scrim.classList.add('show'); b.setAttribute('aria-expanded', 'true'); sidebar.querySelector('a')?.focus(); }));
  scrim?.addEventListener('click', closeMenu);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });

  /* ---------- Menu lateral do Renan: seção "Mais" em acordeão ----------
     Estado único: { more: aberto?, section: qual subseção está aberta (ou null) }.
     A tela é sempre redesenhada a partir desse estado, então nenhuma subseção fica aberta por engano. */
  $$('[data-nav-accordion]').forEach((root) => {
    const moreBtn = $('[data-nav-more]', root);
    const morePanel = document.getElementById(moreBtn.getAttribute('aria-controls'));
    const sectionBtns = $$('[data-nav-section]', root);
    const initial = sectionBtns.find((b) => b.getAttribute('aria-expanded') === 'true');
    const state = { more: moreBtn.getAttribute('aria-expanded') === 'true', section: initial ? initial.dataset.navSection : null };
    const setPanel = (btn, panel, open, animate) => {
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (!animate || reduceMotion()) { panel.hidden = !open; panel.classList.toggle('open', open); return; }
      if (open) {
        panel.hidden = false;
        requestAnimationFrame(() => panel.classList.add('open'));
      } else {
        panel.classList.remove('open');
        const done = () => { if (btn.getAttribute('aria-expanded') === 'false') panel.hidden = true; };
        panel.addEventListener('transitionend', done, { once: true });
        setTimeout(done, 260); // garante o fechamento mesmo sem evento de transição
      }
    };
    const render = (animate = true) => {
      if (!state.more) state.section = null; // fechar "Mais" recolhe todas as subseções
      setPanel(moreBtn, morePanel, state.more, animate);
      sectionBtns.forEach((b) => {
        const panel = document.getElementById(b.getAttribute('aria-controls'));
        setPanel(b, panel, state.more && state.section === b.dataset.navSection, animate);
      });
    };
    moreBtn.addEventListener('click', () => { state.more = !state.more; render(); });
    sectionBtns.forEach((b) => b.addEventListener('click', () => {
      const k = b.dataset.navSection;
      state.section = state.section === k ? null : k; // mesma subseção: fecha; outra: abre e fecha a anterior
      render();
    }));
    render(false);
    // Links com âncora (ex.: Financeiro > Planos) marcam o item atual.
    const markHash = () => {
      const anchors = $$('a.sub', root).filter((a) => { const u = new URL(a.href, location.href); return u.hash && u.pathname === location.pathname; });
      anchors.forEach((a, i) => {
        const u = new URL(a.href, location.href);
        const on = u.hash === location.hash || (!location.hash && i === 0);
        a.classList.toggle('active', on);
        if (on) a.setAttribute('aria-current', 'location'); else a.removeAttribute('aria-current');
      });
    };
    markHash();
    window.addEventListener('hashchange', markHash);
  });

  /* ---------- Filtros que recarregam a página: estado de carregamento ---------- */
  $$('[data-filter-form]').forEach((f) => {
    const region = $('[data-filter-region]');
    const busy = () => region?.classList.add('is-busy');
    f.addEventListener('submit', busy);
    $$('select[data-autosubmit]', f).forEach((el) => el.addEventListener('change', busy));
    $$('a', f).forEach((a) => a.addEventListener('click', busy));
  });
  if ($('[data-filter-region]')) $$('.tabs a').forEach((a) => a.addEventListener('click', () => $('[data-filter-region]').classList.add('is-busy')));
  window.addEventListener('pageshow', () => $$('.is-busy').forEach((r) => r.classList.toggle('is-busy', false)));

  /* ---------- Gráfico de barras: dica ao passar o mouse ou tocar ---------- */
  $$('[data-chart]').forEach((fig) => {
    const tip = $('[data-chart-tip]', fig);
    const clear = () => $$('.bar-hit.on', fig).forEach((x) => x.classList.toggle('on', false));
    const show = (g) => {
      clear();
      if (!g) { tip.hidden = true; return; }
      g.classList.add('on');
      tip.textContent = g.dataset.tip;
      tip.hidden = false;
      const r = fig.getBoundingClientRect();
      const b = g.getBoundingClientRect();
      tip.style.left = `${Math.min(Math.max(b.left + b.width / 2 - r.left, 60), r.width - 60)}px`;
    };
    fig.addEventListener('pointermove', (e) => show(e.target.closest('.bar-hit')));
    fig.addEventListener('pointerleave', () => show(null));
  });

  /* ---------- Mostrar / ocultar senha (aplicado a todo campo de senha) ---------- */
  const enhancePassword = (input) => {
    if (input.dataset.pwReady) return;
    input.dataset.pwReady = '1';
    if (!input.id) input.id = `pw-${Math.random().toString(36).slice(2, 8)}`;
    const wrap = document.createElement('span');
    wrap.className = 'pw-field';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    const btn = document.createElement('button');
    btn.type = 'button'; // nunca envia o formulário
    btn.className = 'pw-toggle';
    btn.setAttribute('aria-controls', input.id);
    const sync = () => {
      const shown = input.type === 'text';
      btn.innerHTML = ICON(shown ? 'eye-off' : 'eye', 'icon');
      btn.setAttribute('aria-label', shown ? 'Ocultar senha' : 'Mostrar senha');
      btn.setAttribute('aria-pressed', shown ? 'true' : 'false');
      btn.title = shown ? 'Ocultar senha' : 'Mostrar senha';
    };
    btn.addEventListener('click', () => {
      const pos = [input.selectionStart, input.selectionEnd];
      input.type = input.type === 'password' ? 'text' : 'password'; // o valor digitado não muda
      sync();
      input.focus({ preventScroll: true });
      try { if (pos[0] !== null) input.setSelectionRange(pos[0], pos[1]); } catch (e) { /* */ }
    });
    // Volta a ocultar ao enviar, para o navegador não guardar a senha como texto comum.
    input.form?.addEventListener('submit', () => { input.type = 'password'; sync(); });
    sync();
    wrap.appendChild(btn);
  };
  $$('input[type=password]').forEach(enhancePassword);

  /* ---------- Formulários: confirmação e estado de carregamento ---------- */
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.dataset.confirm && !confirm(form.dataset.confirm)) { e.preventDefault(); return; }
    if (!form.matches('[data-chat-form]') && !form.matches('[data-no-loading]')) {
      const btn = e.submitter || form.querySelector('[type=submit]');
      if (btn && !form.hasAttribute('data-steps')) {
        setTimeout(() => btn.classList.add('is-loading'), 0);
      }
    }
  });
  window.addEventListener('pageshow', () => $$('.is-loading').forEach((b) => b.classList.remove('is-loading')));
  $$('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form.submit()));
  $$('[data-reload-on-change]').forEach((el) => el.addEventListener('change', () => { location.href = el.dataset.reloadOnChange + encodeURIComponent(el.value); }));
  $$('[data-reload]').forEach((el) => el.addEventListener('click', () => location.reload()));
  $$('[data-show-form]').forEach((el) => el.addEventListener('click', () => { const f = document.getElementById(el.dataset.showForm); if (f) { f.hidden = false; f.classList.add('card'); f.scrollIntoView({ behavior: 'smooth' }); } }));

  /* ---------- Diálogos ---------- */
  $$('[data-open-dialog]').forEach((b) => b.addEventListener('click', () => document.getElementById(b.dataset.openDialog)?.showModal()));
  $$('dialog').forEach((d) => {
    $$('[data-close-dialog]', d).forEach((b) => b.addEventListener('click', () => d.close()));
    d.addEventListener('click', (e) => { if (e.target === d) d.close(); });
    if (d.hasAttribute('data-open-on-load')) d.showModal();
  });

  /* ---------- Conexão e PWA ---------- */
  const setOnline = () => document.body.classList.toggle('is-offline', !navigator.onLine);
  window.addEventListener('online', () => { setOnline(); if ($('[data-offline-page]')) location.reload(); });
  window.addEventListener('offline', setOnline);
  setOnline();
  if ('serviceWorker' in navigator && location.protocol === 'https:' || location.hostname === 'localhost') {
    navigator.serviceWorker?.register('/sw.js').then((reg) => {
      const banner = $('#update-banner');
      const show = (worker) => {
        if (!banner) return;
        banner.classList.add('show');
        $('[data-sw-update]', banner).onclick = () => worker.postMessage('skipWaiting');
      };
      if (reg.waiting && navigator.serviceWorker.controller) show(reg.waiting);
      reg.addEventListener('updatefound', () => {
        const w = reg.installing;
        w?.addEventListener('statechange', () => { if (w.state === 'installed' && navigator.serviceWorker.controller) show(w); });
      });
      let reloaded = false;
      navigator.serviceWorker.addEventListener('controllerchange', () => { if (!reloaded) { reloaded = true; location.reload(); } });
    }).catch(() => { /* PWA indisponível */ });
  }

  /* ---------- Fotos (avaliação inicial e física) ---------- */
  $$('[data-photo-uploader]').forEach((root) => {
    const url = root.dataset.uploadUrl;
    const delBase = root.dataset.deleteBase;
    async function send(slotEl, file) {
      if (!file) return;
      if (!file.type.startsWith('image/')) { toast('Escolha um arquivo de imagem.', 'error'); return; }
      const fd = new FormData();
      fd.append('photo', file);
      fd.append('slot', slotEl.dataset.slot);
      const bar = document.createElement('span');
      bar.className = 'upl-bar';
      bar.style.width = '0%';
      slotEl.appendChild(bar);
      slotEl.classList.add('uploading');
      // Pré-visualização imediata
      const preview = URL.createObjectURL(file);
      try {
        const res = await upload(url, fd, (p) => { bar.style.width = `${Math.round(p * 100)}%`; });
        if (slotEl.hasAttribute('data-add-extra')) {
          const el = document.createElement('div');
          el.className = 'photo-slot filled';
          el.dataset.slot = 'extra';
          el.dataset.fileId = res.id;
          el.innerHTML = `<img src="${esc(res.url)}" alt="Foto adicional"><div class="slot-actions"><button type="button" class="btn sm" data-remove>Remover</button></div>`;
          slotEl.before(el);
        } else {
          let img = slotEl.querySelector('img');
          if (!img) { img = document.createElement('img'); slotEl.insertBefore(img, slotEl.querySelector('.slot-empty')); }
          img.src = res.url;
          img.alt = 'Foto enviada';
          slotEl.classList.add('filled');
          slotEl.dataset.fileId = res.id;
          slotEl.querySelector('.slot-empty')?.setAttribute('hidden', '');
          slotEl.querySelector('.slot-actions')?.removeAttribute('hidden');
        }
        toast('Foto salva.', 'success');
      } catch (err) {
        toast(err.message, 'error');
      } finally {
        URL.revokeObjectURL(preview);
        bar.remove();
        slotEl.classList.remove('uploading');
      }
    }
    root.addEventListener('change', (e) => {
      if (e.target.matches('input[type=file]')) {
        const slot = e.target.closest('.photo-slot');
        send(slot, e.target.files[0]);
        e.target.value = '';
      }
    });
    root.addEventListener('click', async (e) => {
      const replace = e.target.closest('[data-replace]');
      if (replace) { e.preventDefault(); replace.closest('.photo-slot').querySelector('input[type=file]').click(); return; }
      const remove = e.target.closest('[data-remove]');
      if (remove) {
        const slot = remove.closest('.photo-slot');
        if (!confirm('Remover esta foto?')) return;
        try {
          await api(delBase.replace('{id}', slot.dataset.fileId), { method: 'POST' });
          slot.remove();
        } catch (err) { toast(err.message, 'error'); }
      }
    });
  });

  /* ---------- Avaliação inicial: etapas + salvamento automático ---------- */
  const stepsForm = $('[data-steps]');
  if (stepsForm) {
    const panels = $$('.step-panel', stepsForm);
    const stepper = $('[data-stepper]');
    const buttons = $$('button', stepper);
    let current = 1;
    const max = panels.length;
    const go = (n) => {
      current = Math.max(1, Math.min(max, n));
      panels.forEach((p) => p.classList.toggle('current', +p.dataset.step === current));
      buttons.forEach((b) => {
        const k = +b.dataset.goto;
        b.classList.toggle('current', k === current);
        b.classList.toggle('done', k < current);
      });
      $('[data-prev]', stepsForm).style.visibility = current === 1 ? 'hidden' : 'visible';
      $('[data-next]', stepsForm).style.display = current === max ? 'none' : '';
      if (current === max) renderMissing();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    const renderMissing = () => {
      const missing = [];
      $$('[data-required]', stepsForm).forEach((el) => { if (!el.value.trim()) missing.push(el.closest('.field').querySelector('label')?.textContent.trim()); });
      $$('.field', stepsForm).forEach((f) => {
        const lbl = f.querySelector('.label.req');
        if (lbl && !f.querySelector('input:checked')) missing.push(lbl.textContent.trim());
      });
      $$('.photo-slot[data-slot]:not([data-slot=extra])', stepsForm).forEach((s) => { if (!s.classList.contains('filled')) missing.push(`Foto: ${s.querySelector('.slot-label').textContent}`); });
      const box = $('[data-missing-list]', stepsForm);
      box.innerHTML = missing.length
        ? `<div class="callout warn">${ICON('alert', 'icon')}<div><b>Ainda faltam:</b><ul class="small" style="margin:6px 0 0;padding-left:18px">${missing.map((m) => `<li>${esc(m)}</li>`).join('')}</ul></div></div>`
        : `<div class="callout ok">${ICON('check', 'icon')}<p class="small">Tudo preenchido. Você já pode enviar.</p></div>`;
    };
    buttons.forEach((b) => b.addEventListener('click', () => go(+b.dataset.goto)));
    $('[data-next]', stepsForm).addEventListener('click', () => { save(); go(current + 1); });
    $('[data-prev]', stepsForm).addEventListener('click', () => go(current - 1));
    go(1);

    const state = $('[data-save-state]');
    let timer = null;
    let saving = false;
    async function save() {
      if (saving) return;
      saving = true;
      clearTimeout(timer);
      timer = null;
      const fd = new FormData(stepsForm);
      fd.set('action', 'save');
      state && (state.textContent = 'Salvando…');
      try {
        const res = await api(stepsForm.dataset.autosave, { method: 'POST', body: fd });
        state && (state.textContent = `Rascunho salvo às ${res.saved_at}`);
      } catch (err) {
        state && (state.textContent = 'Não foi possível salvar agora');
      } finally { saving = false; }
    }
    stepsForm.addEventListener('input', (e) => { if (e.target.type === 'file') return; clearTimeout(timer); timer = setTimeout(save, 1500); });
    stepsForm.addEventListener('change', (e) => { if (e.target.type === 'file') return; clearTimeout(timer); timer = setTimeout(save, 600); });
    window.addEventListener('beforeunload', () => { if (timer) navigator.sendBeacon?.(stepsForm.dataset.autosave, (() => { const f = new FormData(stepsForm); f.set('action', 'save'); return f; })()); });
  }

  /* ---------- Autoavaliação: descrição da nota ---------- */
  $$('.scale-row').forEach((row) => {
    const out = $('[data-desc-out]', row);
    row.addEventListener('change', (e) => { if (e.target.dataset.desc) out.textContent = e.target.dataset.desc; });
  });

  /* ---------- Autoavaliação: uma pergunta por tela ---------- */
  const obo = $('[data-one-by-one]');
  if (obo) {
    const rows = $$('.scale-row', obo);
    const bar = $$('[data-obo-bar] i');
    const count = $('[data-obo-count]');
    let k = rows.findIndex((r) => !$('input:checked', r));
    if (k < 0) k = rows.length;
    const go = (n) => {
      k = Math.max(0, Math.min(rows.length, n));
      rows.forEach((r, i) => r.classList.toggle('current', i === k));
      obo.classList.toggle('at-end', k >= rows.length);
      bar.forEach((b, i) => b.classList.toggle('done', i < k));
      if (count) count.textContent = k >= rows.length ? 'Quase lá' : `${k + 1} de ${rows.length}`;
      const back = $('[data-obo-back]'); if (back) back.hidden = k === 0;
    };
    obo.classList.add('one-by-one');
    go(k);
    obo.addEventListener('change', (e) => {
      const row = e.target.closest('.scale-row');
      if (!row || !e.target.matches('input[type=radio]')) return;
      const i = rows.indexOf(row);
      if (i === k) setTimeout(() => go(k + 1), reduceMotion() ? 0 : 380);
    });
    $('[data-obo-back]')?.addEventListener('click', () => go(k - 1));
  }

  /* ---------- Hidratação rápida ---------- */
  $$('[data-water-quick] [data-add]').forEach((b) => b.addEventListener('click', () => {
    const input = $('#water');
    input.value = (parseInt(input.value || '0', 10) + parseInt(b.dataset.add, 10));
  }));

  /* ---------- Publicação de treino ---------- */
  $$('[data-publish-form]').forEach((pub) => {
    const field = $('[data-schedule-field]', pub);
    if (field) $$('[data-when]', pub).forEach((r) => r.addEventListener('change', () => { field.hidden = r.value !== 'schedule' || !r.checked; if (!field.hidden) field.querySelector('input').required = true; else field.querySelector('input').required = false; }));
    pub.addEventListener('submit', async (e) => {
      // Garante que o rascunho atual foi salvo antes de publicar.
      if (window.wpEditorSave && !pub.dataset.saved) {
        e.preventDefault();
        try { await window.wpEditorSave(); pub.dataset.saved = '1'; pub.requestSubmit(e.submitter); }
        catch (err) { toast(err.message, 'error'); $$('.is-loading').forEach((b) => b.classList.remove('is-loading')); }
      }
    });
  });
  const metaForm = $('[data-meta-form]');
  if (metaForm) {
    const btn = $('[data-meta-save]', metaForm);
    const show = () => { if (btn) btn.hidden = false; };
    metaForm.addEventListener('input', show);
    metaForm.addEventListener('change', show);
  }

  /* ---------- Registro do treino (aluno) ---------- */
  const sessionRoot = $('[data-session]');
  if (sessionRoot) {
    const url = sessionRoot.dataset.saveUrl;
    const state = $('[data-save-state]');
    const timers = new Map();
    const collect = (entry) => {
      const data = { sets: [] };
      $$('[data-set]', entry).forEach((row) => {
        const set = {};
        $$('[data-k]', row).forEach((i) => { set[i.dataset.k] = i.value; });
        set.done = $('[data-set-done]', row)?.getAttribute('aria-pressed') === 'true';
        data.sets.push(set);
      });
      $$('[data-k]', entry).forEach((i) => { if (!i.closest('[data-set]')) data[i.dataset.k] = i.value; });
      return data;
    };
    const updateProgress = (done) => {
      const counter = $('[data-done-count]');
      const prog = $('.progress');
      if (counter) counter.textContent = done;
      if (prog) prog.firstElementChild.style.width = `${Math.round(done / (+prog.dataset.total || 1) * 100)}%`;
      const rv = $('.ring .val');
      if (rv && prog) rv.setAttribute('stroke-dashoffset', String(parseFloat(rv.getAttribute('stroke-dasharray')) * (1 - done / (+prog.dataset.total || 1))));
    };
    const save = async (entry) => {
      const completed = $('[data-complete]', entry).getAttribute('aria-pressed') === 'true';
      state.textContent = 'Salvando…';
      try {
        const res = await api(url, { method: 'POST', json: { item_id: entry.dataset.itemId, completed: completed ? 1 : 0, data: collect(entry) } });
        state.textContent = `Salvo às ${res.saved_at}`;
        updateProgress(res.done);
        entry.dataset.dirty = '';
      } catch (err) {
        state.textContent = 'Sem conexão — tentaremos de novo';
        entry.dataset.dirty = '1';
        setTimeout(() => save(entry), 8000);
      }
    };
    const schedule = (entry, ms = 900) => { clearTimeout(timers.get(entry)); timers.set(entry, setTimeout(() => save(entry), ms)); };
    sessionRoot.addEventListener('input', (e) => { const entry = e.target.closest('[data-entry]'); if (entry) schedule(entry); });
    sessionRoot.addEventListener('click', (e) => {
      const entry = e.target.closest('[data-entry]');
      if (!entry) return;
      const setBtn = e.target.closest('[data-set-done]');
      if (setBtn) { setBtn.setAttribute('aria-pressed', setBtn.getAttribute('aria-pressed') === 'true' ? 'false' : 'true'); schedule(entry, 300); return; }
      const comp = e.target.closest('[data-complete]');
      if (comp) {
        const on = comp.getAttribute('aria-pressed') !== 'true';
        comp.setAttribute('aria-pressed', on ? 'true' : 'false');
        comp.classList.toggle('accent', on);
        comp.classList.toggle('secondary', !on);
        comp.querySelector('span').textContent = on ? 'Feito' : 'Concluir';
        entry.classList.toggle('done', on);
        if (on) $$('[data-set-done]', entry).forEach((b) => b.setAttribute('aria-pressed', 'true'));
        schedule(entry, 100);
        return;
      }
      const add = e.target.closest('[data-add-set]');
      if (add) {
        const sets = $('[data-sets]', entry);
        const rows = $$('[data-set]', sets);
        const last = rows[rows.length - 1];
        const clone = last.cloneNode(true);
        $$('input', clone).forEach((i) => { i.value = ''; });
        $('[data-set-done]', clone).setAttribute('aria-pressed', 'false');
        clone.querySelector('.n').textContent = rows.length + 1;
        sets.appendChild(clone);
        clone.querySelector('input')?.focus();
        schedule(entry);
      }
    });
    window.addEventListener('online', () => $$('[data-entry][data-dirty="1"]').forEach((en) => save(en)));
  }

  /* ---------- Treino: um exercício por vez + cronômetro de descanso ---------- */
  if (sessionRoot) {
    const entries = $$('[data-entry]', sessionRoot);
    const segs = $$('.seg-progress i');
    const prevBtn = $('[data-prev-ex]');
    const nextBtn = $('[data-next-ex]');
    const counter = $('[data-focus-count]');
    let cur = entries.findIndex((en) => !en.classList.contains('done'));
    if (cur < 0) cur = 0;
    const refresh = () => segs.forEach((sg, k) => { sg.classList.toggle('done', entries[k]?.classList.contains('done')); sg.classList.toggle('cur', k === cur && !entries[k]?.classList.contains('done')); });
    const show = (i, scroll = true) => {
      cur = Math.max(0, Math.min(entries.length - 1, i));
      entries.forEach((en, k) => en.classList.toggle('current', k === cur));
      refresh();
      if (counter) counter.textContent = `Exercício ${cur + 1} de ${entries.length}`;
      if (prevBtn) prevBtn.disabled = cur === 0;
      if (nextBtn) nextBtn.querySelector('span').textContent = cur === entries.length - 1 ? 'Finalizar' : 'Próximo';
      if (scroll) window.scrollTo({ top: 0, behavior: reduceMotion() ? 'auto' : 'smooth' });
    };
    if (entries.length > 1) { sessionRoot.classList.add('focus-mode'); show(cur, false); } else refresh();
    prevBtn?.addEventListener('click', () => show(cur - 1));
    nextBtn?.addEventListener('click', () => {
      if (cur === entries.length - 1) { $('[data-finish-form]')?.scrollIntoView({ behavior: 'smooth' }); $('[data-finish-form] textarea')?.focus({ preventScroll: true }); }
      else show(cur + 1);
    });
    const parseRest = (txt) => {
      const t = String(txt || '').toLowerCase().replace(',', '.');
      let m = t.match(/(\d+):(\d{1,2})/);
      if (m) return (+m[1]) * 60 + (+m[2]);
      m = t.match(/(\d+(?:\.\d+)?)\s*(min|m\b|')/);
      if (m) return Math.round(parseFloat(m[1]) * 60);
      m = t.match(/(\d+)/);
      return m ? +m[1] : 0;
    };
    let restEl = null; let restTimer = null;
    const closeRest = () => { clearInterval(restTimer); restEl?.remove(); restEl = null; };
    const startRest = (secs, label) => {
      closeRest();
      if (!secs || secs > 900) return;
      restEl = document.createElement('div');
      restEl.className = 'rest';
      restEl.setAttribute('role', 'timer');
      const size = 56; const w = 6; const r = (size - w) / 2; const C = 2 * Math.PI * r;
      restEl.innerHTML = `<span class="ring" style="width:${size}px;height:${size}px"><svg width="${size}" height="${size}"><circle class="track" cx="${size / 2}" cy="${size / 2}" r="${r}" stroke-width="${w}"/><circle class="val" cx="${size / 2}" cy="${size / 2}" r="${r}" stroke-width="${w}" stroke-dasharray="${C}" stroke-dashoffset="0" style="transition:stroke-dashoffset 1s linear"/></svg><span class="v" data-rest-left></span></span><div class="grow"><b>Descanso</b><div class="xs" style="opacity:.7">${esc(label)}</div></div><button type="button" class="btn sm">Pular</button>`;
      document.body.appendChild(restEl);
      $('button', restEl).onclick = closeRest;
      const end = Date.now() + secs * 1000;
      const val = $('.val', restEl); const left = $('[data-rest-left]', restEl);
      const tick = () => {
        const l = Math.max(0, Math.ceil((end - Date.now()) / 1000));
        left.textContent = l >= 60 ? `${Math.floor(l / 60)}:${String(l % 60).padStart(2, '0')}` : String(l);
        val.setAttribute('stroke-dashoffset', String(C * (1 - l / secs)));
        if (l <= 0) { closeRest(); navigator.vibrate?.([120, 60, 120]); toast('Descanso terminado. Bora!', 'success'); }
      };
      tick();
      restTimer = setInterval(tick, 250);
    };
    sessionRoot.addEventListener('click', (e) => {
      const entry = e.target.closest('[data-entry]');
      if (!entry) return;
      const setBtn = e.target.closest('[data-set-done]');
      if (setBtn && setBtn.getAttribute('aria-pressed') === 'true') {
        const rows = $$('[data-set]', entry);
        const doneAll = rows.every((r) => $('[data-set-done]', r)?.getAttribute('aria-pressed') === 'true');
        if (doneAll) {
          const comp = $('[data-complete]', entry);
          if (comp && comp.getAttribute('aria-pressed') !== 'true') setTimeout(() => comp.click(), 250);
        } else {
          const nextN = rows.findIndex((r) => $('[data-set-done]', r)?.getAttribute('aria-pressed') !== 'true') + 1;
          startRest(parseRest(entry.dataset.rest), `Próxima: série ${nextN}`);
        }
      }
      if (e.target.closest('[data-complete]')) {
        setTimeout(() => { refresh(); if (entry.classList.contains('done') && sessionRoot.classList.contains('focus-mode') && cur < entries.length - 1) show(cur + 1); }, 550);
      }
    });
  }

  /* ---------- Editor de treinos (Renan) ---------- */
  const editor = $('[data-editor]');
  if (editor) {
    const cfg = JSON.parse($('[data-editor-data]', editor).textContent);
    const list = $('[data-editor-list]', editor);
    const tpl = $('[data-item-template]').content;
    const emptyBox = $('[data-editor-empty]', editor);
    const state = $('[data-save-state]');
    let items = cfg.items.map((it) => ({ ...it, params: { ...(it.params || {}) }, fields: it.fields && it.fields.length ? it.fields : [...cfg.modality_fields] }));
    let dirty = false;
    let timer = null;
    let dragIndex = null;

    const render = () => {
      list.innerHTML = '';
      emptyBox.hidden = items.length > 0;
      items.forEach((it, idx) => {
        const node = tpl.cloneNode(true).firstElementChild;
        node.dataset.idx = idx;
        $('[data-num]', node).textContent = idx + 1;
        const th = it.exercise_id && cfg.lib_thumbs ? cfg.lib_thumbs[it.exercise_id] : null;
        if (th) {
          const box = $('[data-thumb]', node);
          box.hidden = false;
          box.innerHTML = th.image_url ? `<span class="ex-thumb sm"><img src="${esc(th.image_url)}" alt=""></span>` : `<span class="ex-thumb pict sm ${esc(th.tone || '')}" aria-hidden="true">${ICON(th.equipment_icon || 'eq-acessorios', 'icon')}</span>`;
        }
        $$('[data-f]', node).forEach((inp) => { inp.value = it[inp.dataset.f] || ''; });
        const pg = $('[data-params]', node);
        it.fields.forEach((f) => {
          const meta = cfg.catalog[f];
          if (!meta) return;
          const id = `p_${idx}_${f}`;
          pg.insertAdjacentHTML('beforeend', `<div class="field"><label for="${id}">${esc(meta.label)}${meta.unit ? ` (${esc(meta.unit)})` : ''}</label><input id="${id}" data-param="${f}" value="${esc(it.params[f] || '')}" placeholder="${esc(meta.placeholder || '')}"></div>`);
        });
        const fieldsBox = $('[data-fields]', node);
        Object.entries(cfg.catalog).forEach(([k, meta]) => {
          fieldsBox.insertAdjacentHTML('beforeend', `<label class="pill-check"><input type="checkbox" data-field-toggle="${k}"${it.fields.includes(k) ? ' checked' : ''}><span>${esc(meta.label)}</span></label>`);
        });
        if (it.video_file_id) $('[data-video-status]', node).innerHTML = `Vídeo enviado. <a href="${esc(it.video_file_url || '#')}" target="_blank">ver</a> · <button type="button" class="link" data-clear-video>remover</button>`;
        list.appendChild(node);
      });
    };

    const markDirty = () => { dirty = true; state.textContent = 'Alterações não salvas…'; clearTimeout(timer); timer = setTimeout(save, 1500); };
    async function save() {
      clearTimeout(timer);
      state.textContent = 'Salvando…';
      try {
        const res = await api(cfg.save_url, { method: 'POST', json: { items, general_notes: $('[data-general-notes]').value } });
        dirty = false;
        state.textContent = `Rascunho salvo às ${res.saved_at}`;
      } catch (err) {
        state.textContent = 'Erro ao salvar';
        toast(err.message, 'error');
        throw err;
      }
    }
    window.wpEditorSave = save;
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    list.addEventListener('input', (e) => {
      const node = e.target.closest('.editor-item');
      if (!node) return;
      const it = items[+node.dataset.idx];
      if (e.target.dataset.f) it[e.target.dataset.f] = e.target.value;
      if (e.target.dataset.param) it.params[e.target.dataset.param] = e.target.value;
      markDirty();
    });
    list.addEventListener('change', async (e) => {
      const node = e.target.closest('.editor-item');
      if (!node) return;
      const it = items[+node.dataset.idx];
      if (e.target.dataset.fieldToggle) {
        const k = e.target.dataset.fieldToggle;
        it.fields = e.target.checked ? [...new Set([...it.fields, k])].sort((a, b) => Object.keys(cfg.catalog).indexOf(a) - Object.keys(cfg.catalog).indexOf(b)) : it.fields.filter((f) => f !== k);
        render();
        markDirty();
      }
      if (e.target.matches('[data-video-upload]') && e.target.files[0]) {
        const status = $('[data-video-status]', node);
        const fd = new FormData();
        fd.append('video', e.target.files[0]);
        status.textContent = 'Enviando vídeo… 0%';
        try {
          const res = await upload('/painel/treinos/video', fd, (p) => { status.textContent = `Enviando vídeo… ${Math.round(p * 100)}%`; });
          it.video_file_id = res.id;
          it.video_file_url = res.url;
          status.textContent = 'Vídeo enviado.';
          markDirty();
        } catch (err) { status.textContent = ''; toast(err.message, 'error'); }
      }
    });
    list.addEventListener('click', (e) => {
      const node = e.target.closest('.editor-item');
      if (!node) return;
      const idx = +node.dataset.idx;
      const mv = e.target.closest('[data-move]');
      if (mv) {
        const to = idx + (+mv.dataset.move);
        if (to < 0 || to >= items.length) return;
        [items[idx], items[to]] = [items[to], items[idx]];
        render();
        list.children[to]?.querySelector(`[data-move="${mv.dataset.move}"]`)?.focus();
        markDirty();
      }
      if (e.target.closest('[data-remove-item]') && confirm(`Remover "${items[idx].name || 'exercício'}" deste treino?`)) {
        items.splice(idx, 1);
        render();
        markDirty();
      }
      if (e.target.closest('[data-clear-video]')) { items[idx].video_file_id = null; items[idx].video_file_url = null; render(); markDirty(); }
    });
    // Arrastar e soltar
    list.addEventListener('dragstart', (e) => {
      const node = e.target.closest('.editor-item');
      if (!node || ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) && node.contains(document.activeElement)) { e.preventDefault(); return; }
      dragIndex = +node.dataset.idx;
      node.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    list.addEventListener('dragover', (e) => {
      const node = e.target.closest('.editor-item');
      if (dragIndex === null || !node) return;
      e.preventDefault();
      $$('.drop-target', list).forEach((n) => n.classList.remove('drop-target'));
      node.classList.add('drop-target');
    });
    list.addEventListener('drop', (e) => {
      const node = e.target.closest('.editor-item');
      if (dragIndex === null || !node) return;
      e.preventDefault();
      const to = +node.dataset.idx;
      const [moved] = items.splice(dragIndex, 1);
      items.splice(to, 0, moved);
      dragIndex = null;
      render();
      markDirty();
    });
    list.addEventListener('dragend', () => { dragIndex = null; $$('.dragging,.drop-target', list).forEach((n) => n.classList.remove('dragging', 'drop-target')); });

    $('[data-add-custom]', editor).addEventListener('click', () => {
      items.push({ exercise_id: null, name: '', instructions: '', notes: '', video_url: '', video_file_id: null, params: {}, fields: [...cfg.modality_fields] });
      render();
      list.lastElementChild?.querySelector('.item-name')?.focus();
      markDirty();
    });
    $('[data-general-notes]').addEventListener('input', markDirty);

    // Biblioteca: busca com filtros, favoritos primeiro, adicionar ou substituir
    const dlg = $('#library-dialog');
    const q = $('[data-lib-q]', dlg);
    const all = $('[data-lib-all]', dlg);
    const favOnly = $('[data-lib-fav]', dlg);
    const muscleSel = $('[data-lib-muscle]', dlg);
    const equipSel = $('[data-lib-equip]', dlg);
    const results = $('[data-lib-results]', dlg);
    const libTitle = $('[data-lib-title]', dlg);
    let lastResults = [];
    let searchTimer = null;
    let replaceIdx = null; // índice do exercício a substituir (null = adicionar)
    let searchSeq = 0;
    const thumbHtml = (r) => r.image_url
      ? `<span class="ex-thumb sm"><img src="${esc(r.image_url)}" alt="" loading="lazy"></span>`
      : `<span class="ex-thumb pict sm ${esc(r.tone || '')}" aria-hidden="true">${ICON(r.equipment_icon || 'eq-acessorios', 'icon')}</span>`;
    const search = async () => {
      const seq = ++searchSeq;
      results.innerHTML = '<div class="loading-block">Buscando</div>';
      try {
        const params = new URLSearchParams({ q: q.value });
        if (!all.checked) params.set('modality', cfg.modality_id);
        if (muscleSel.value) params.set('musculo', muscleSel.value);
        if (equipSel.value) params.set('equipamento', equipSel.value);
        if (favOnly.checked) params.set('favoritos', '1');
        const res = await api(`/api/biblioteca?${params}`);
        if (seq !== searchSeq) return; // resposta antiga
        lastResults = res.results;
        results.innerHTML = res.results.length ? res.results.map((r, i) => `<div class="lib-result">${thumbHtml(r)}<div class="grow" style="min-width:0"><div class="strong truncate">${esc(r.name)}</div><div class="xs muted truncate">${esc([r.muscle, r.equipment, r.difficulty].filter(Boolean).join(' · ') || r.modality)}${r.video_status === 'ok' ? ' · vídeo' : ''}</div></div><button type="button" class="fav-btn${r.favorite ? ' on' : ''}" data-fav="${i}" aria-pressed="${r.favorite}" aria-label="Favoritar ${esc(r.name)}">${ICON('star')}</button><button type="button" class="btn pw sm" data-pick="${i}">${replaceIdx === null ? `${ICON('plus')} Adicionar` : 'Usar este'}</button></div>`).join('')
          : '<div class="empty small">Nenhum exercício encontrado. <a href="/painel/biblioteca/novo" target="_blank">Cadastrar</a></div>';
      } catch (err) { if (seq === searchSeq) results.innerHTML = `<div class="empty small">${esc(err.message)}</div>`; }
    };
    const openLibrary = (idx = null) => {
      replaceIdx = idx;
      libTitle.textContent = idx === null ? 'Adicionar exercício' : `Substituir “${items[idx].name || 'exercício'}”`;
      if (idx !== null) { q.value = ''; }
      if (!dlg.open) dlg.showModal();
      search();
      setTimeout(() => q.focus(), 50);
    };
    $('[data-open-dialog="library-dialog"]').addEventListener('click', () => openLibrary(null));
    const createBtn = $('[data-lib-create]', dlg);
    const syncCreate = () => { if (createBtn) createBtn.querySelector('span').textContent = q.value.trim() ? `Criar “${q.value.trim()}” como exercício novo` : 'Criar exercício personalizado'; };
    createBtn?.addEventListener('click', () => {
      const blank = { exercise_id: null, name: q.value.trim(), instructions: '', notes: '', video_url: '', video_file_id: null, params: {}, fields: [...cfg.modality_fields] };
      if (replaceIdx !== null) { blank.params = items[replaceIdx].params; blank.notes = items[replaceIdx].notes; items[replaceIdx] = blank; }
      else items.push(blank);
      render();
      markDirty();
      dlg.close();
      list.lastElementChild?.querySelector(q.value.trim() ? '[data-param]' : '.item-name')?.focus();
    });
    q.addEventListener('input', () => { syncCreate(); clearTimeout(searchTimer); searchTimer = setTimeout(search, 300); });
    [all, favOnly, muscleSel, equipSel].forEach((el) => el.addEventListener('change', search));
    results.addEventListener('click', async (e) => {
      const fb = e.target.closest('[data-fav]');
      if (fb) {
        const r = lastResults[+fb.dataset.fav];
        try {
          const res = await api(`/painel/biblioteca/${r.id}/favorito`, { method: 'POST' });
          r.favorite = res.favorite;
          fb.classList.toggle('on', res.favorite);
          fb.setAttribute('aria-pressed', String(res.favorite));
        } catch (err) { toast(err.message, 'error'); }
        return;
      }
      const b = e.target.closest('[data-pick]');
      if (!b) return;
      const r = lastResults[+b.dataset.pick];
      // Vídeo da biblioteca só é copiado quando já foi revisado; as orientações aparecem para o aluno a partir da biblioteca.
      const videoOk = r.video_status === 'ok';
      const picked = { exercise_id: r.id, name: r.name, instructions: '', notes: '', video_url: videoOk ? (r.video_url || '') : '', video_file_id: videoOk ? r.video_file_id : null, video_file_url: videoOk ? r.video_file_url : null, params: {}, fields: r.fields && r.fields.length ? r.fields : [...cfg.modality_fields] };
      cfg.lib_thumbs[r.id] = { equipment_icon: r.equipment_icon, tone: r.tone, image_url: r.image_url };
      if (replaceIdx !== null) {
        const old = items[replaceIdx];
        picked.params = { ...old.params };   // mantém séries, repetições, carga e descanso
        picked.instructions = old.instructions || '';
        picked.notes = old.notes || '';
        items[replaceIdx] = picked;
        const at = replaceIdx;
        render();
        markDirty();
        dlg.close();
        toast(`Substituído por ${r.name}.`, 'success');
        list.children[at]?.querySelector('[data-param]')?.focus();
        return;
      }
      items.push(picked);
      render();
      markDirty();
      b.textContent = 'Adicionado';
      b.disabled = true;
    });
    list.addEventListener('click', (e) => {
      const rb = e.target.closest('[data-replace-item]');
      if (rb) openLibrary(+rb.closest('.editor-item').dataset.idx);
    });
    render();
  }

  /* ---------- Áudio: player com ondas, velocidade e um áudio por vez ---------- */
  const Voice = (() => {
    let current = null; // player tocando agora
    const RATES = [1, 1.5, 2];
    let rate = 1;
    try { rate = RATES.includes(+localStorage.getItem('wp-audio-rate')) ? +localStorage.getItem('wp-audio-rate') : 1; } catch (e) { /* */ }
    const fmt = (s) => { s = Math.max(0, Math.round(s || 0)); return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`; };
    const BARS = 44;
    const peakCache = new Map();
    /** Lê o áudio real e calcula a forma de onda (picos normalizados). */
    async function computePeaks(src, bars = BARS) {
      if (peakCache.has(src)) return peakCache.get(src);
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return null;
      const p = (async () => {
        const buf = await (await fetch(src, { credentials: 'same-origin' })).arrayBuffer();
        const ctx = new Ctx();
        try {
          const audio = await new Promise((res, rej) => { const r = ctx.decodeAudioData(buf, res, rej); if (r && r.then) r.then(res, rej); });
          return { peaks: peaksFromBuffer(audio, bars), duration: audio.duration };
        } finally { ctx.close?.(); }
      })().catch(() => null);
      peakCache.set(src, p);
      return p;
    }
    function peaksFromBuffer(audio, bars = BARS) {
      const data = audio.getChannelData(0);
      const step = Math.max(1, Math.floor(data.length / bars));
      const out = [];
      for (let i = 0; i < bars; i++) {
        let sum = 0;
        const start = i * step;
        for (let j = 0; j < step && start + j < data.length; j++) sum += data[start + j] * data[start + j];
        out.push(Math.sqrt(sum / step));
      }
      return normalize(out);
    }
    function normalize(arr) {
      const max = Math.max(...arr, 0.0001);
      return arr.map((v) => Math.round(Math.min(1, v / max) * 1000) / 1000);
    }
    function resample(arr, bars = BARS) {
      if (!arr || !arr.length) return null;
      if (arr.length === bars) return arr;
      return Array.from({ length: bars }, (_, i) => arr[Math.min(arr.length - 1, Math.floor(i * arr.length / bars))]);
    }
    function drawBars(wave, peaks) {
      const p = resample(peaks) || Array(BARS).fill(0.18);
      wave.innerHTML = p.map((v) => `<i style="--h:${Math.max(0.12, v).toFixed(3)}"></i>`).join('');
      wave.classList.toggle('flat', !peaks);
    }
    /** Cria o player dentro de `root` para a URL `src`. */
    function mount(root, src, meta = {}) {
      root.classList.add('voice');
      root.innerHTML = `<button type="button" class="voice-btn" aria-label="Reproduzir áudio">${ICON('play', 'icon')}</button>
        <div class="voice-main"><div class="voice-wave"></div><input type="range" class="voice-seek" min="0" max="1000" value="0" step="1" aria-label="Posição do áudio">
        <div class="voice-meta"><span class="voice-time">0:00</span><span class="voice-err" hidden>Não foi possível carregar o áudio. <button type="button" class="link">Tentar de novo</button></span></div></div>
        <button type="button" class="voice-rate" aria-label="Velocidade de reprodução">${String(rate).replace('.', ',')}x</button>`;
      const btn = root.querySelector('.voice-btn');
      const wave = root.querySelector('.voice-wave');
      const seek = root.querySelector('.voice-seek');
      const time = root.querySelector('.voice-time');
      const err = root.querySelector('.voice-err');
      const rateBtn = root.querySelector('.voice-rate');
      const audio = new Audio();
      audio.preload = 'metadata';
      audio.src = src;
      let total = meta.duration > 0 ? meta.duration : 0;
      drawBars(wave, meta.peaks && meta.peaks.length ? meta.peaks : null);
      const totalNow = () => (Number.isFinite(audio.duration) && audio.duration > 0 ? audio.duration : total);
      const paint = () => {
        const d = totalNow();
        const pos = d ? Math.min(1, audio.currentTime / d) : 0;
        root.style.setProperty('--p', pos.toFixed(4));
        if (!seek.matches(':active')) seek.value = Math.round(pos * 1000);
        time.textContent = audio.currentTime > 0 || !audio.paused ? `${fmt(audio.currentTime)} / ${fmt(d)}` : fmt(d);
        seek.setAttribute('aria-valuetext', `${fmt(audio.currentTime)} de ${fmt(d)}`);
      };
      const setState = (s) => {
        root.dataset.state = s;
        btn.innerHTML = ICON(s === 'playing' ? 'pause' : 'play', 'icon');
        btn.setAttribute('aria-label', s === 'playing' ? 'Pausar áudio' : 'Reproduzir áudio');
      };
      setState('paused');
      paint();
      if (!meta.peaks || !meta.peaks.length) {
        // Sem forma de onda salva (áudio antigo ou enviado como arquivo): calcula a partir do áudio real quando aparecer na tela.
        const load = () => computePeaks(src).then((r) => { if (r) { drawBars(wave, r.peaks); if (!total && r.duration) { total = r.duration; paint(); } } });
        if ('IntersectionObserver' in window) {
          const io = new IntersectionObserver((ents) => { if (ents.some((x) => x.isIntersecting)) { io.disconnect(); load(); } });
          io.observe(root);
        } else load();
      }
      audio.addEventListener('loadedmetadata', paint);
      audio.addEventListener('durationchange', paint);
      audio.addEventListener('timeupdate', paint);
      audio.addEventListener('waiting', () => root.classList.add('loading'));
      audio.addEventListener('canplay', () => root.classList.remove('loading'));
      audio.addEventListener('playing', () => { root.classList.remove('loading'); setState('playing'); });
      audio.addEventListener('pause', () => setState('paused'));
      audio.addEventListener('ended', () => { setState('paused'); audio.currentTime = 0; paint(); if (current === player) current = null; });
      audio.addEventListener('error', () => { root.classList.remove('loading'); err.hidden = false; time.hidden = true; setState('paused'); });
      err.querySelector('button').addEventListener('click', () => { err.hidden = true; time.hidden = false; audio.load(); });
      const player = {
        root, audio,
        play() {
          if (current && current !== player) current.pause();
          current = player;
          audio.playbackRate = rate;
          root.classList.add('loading');
          const pr = audio.play();
          if (pr && pr.catch) pr.catch(() => { root.classList.remove('loading'); setState('paused'); });
        },
        pause() { audio.pause(); },
        destroy() { audio.pause(); audio.removeAttribute('src'); audio.load(); if (current === player) current = null; },
      };
      btn.addEventListener('click', () => (audio.paused ? player.play() : player.pause()));
      seek.addEventListener('input', () => {
        const d = totalNow();
        if (!d) return;
        audio.currentTime = (seek.value / 1000) * d;
        paint();
      });
      rateBtn.addEventListener('click', () => {
        rate = RATES[(RATES.indexOf(rate) + 1) % RATES.length];
        try { localStorage.setItem('wp-audio-rate', String(rate)); } catch (e) { /* */ }
        document.querySelectorAll('.voice-rate').forEach((b) => { b.textContent = `${String(rate).replace('.', ',')}x`; });
        if (current) current.audio.playbackRate = rate;
      });
      root._player = player;
      return player;
    }
    return { mount, fmt, normalize, peaksFromBuffer, resample, BARS };
  })();
  window.wpVoice = Voice;

  /* ---------- Chat ---------- */
  const chat = $('[data-chat]');
  if (chat) {
    const endpoint = chat.dataset.endpoint;
    const log = $('[data-chat-log]', chat);
    const form = $('[data-chat-form]', chat);
    const input = $('[data-chat-input]', chat);
    const fileInput = $('[data-chat-file]', chat);
    const preview = $('[data-attach-preview]', chat);
    const recBtn = $('[data-chat-record]', chat);
    let lastId = 0;
    let firstId = 0;
    let lastDate = '';
    let attach = null;
    let polling = null;
    let readUpTo = 0;
    let since = '';

    const fmtDay = (d) => {
      const today = new Date();
      const iso = (x) => `${x.getFullYear()}-${String(x.getMonth() + 1).padStart(2, '0')}-${String(x.getDate()).padStart(2, '0')}`;
      const y = new Date(); y.setDate(y.getDate() - 1);
      if (d === iso(today)) return 'Hoje';
      if (d === iso(y)) return 'Ontem';
      const [yy, mm, dd] = d.split('-');
      return `${dd}/${mm}/${yy}`;
    };
    const bubbleInner = (m) => {
      let html = '';
      if (m.deleted) {
        html += `<div class="deleted-msg">${ICON('ban')} ${m.mine ? 'Você apagou esta mensagem' : 'Esta mensagem foi apagada'}</div>`;
      } else {
        if (m.file) {
          if (m.file.removed) html += '<em class="small">Arquivo removido</em>';
          else if (m.file.kind === 'image') html += `<a href="${esc(m.file.url)}" target="_blank"><img src="${esc(m.file.url)}" alt="Imagem enviada" loading="lazy"></a>`;
          else if (m.file.kind === 'video') html += `<video controls preload="metadata" playsinline src="${esc(m.file.url)}"></video>`;
          else if (m.file.kind === 'audio') html += '<div data-voice></div>';
          else html += `<a class="file-link" href="${esc(m.file.url)}&download=1">${ICON('file', 'icon')}<span><span class="strong">${esc(m.file.name)}</span><br><span class="xs">${esc(m.file.size)}</span></span></a>`;
        }
        if (m.body) html += `<div class="msg-text">${esc(m.body)}</div>`;
      }
      html += `<div class="meta"><span>${esc(m.time)}</span>${m.mine && !m.deleted ? `<span data-read title="${m.read ? 'Lida' : 'Enviada'}">${m.read ? '✓✓' : '✓'}</span>` : ''}</div>`;
      if (!m.deleted) html += `<button type="button" class="msg-menu" data-msg-menu aria-label="Opções da mensagem">${ICON('dots')}</button>`;
      return html;
    };
    const fillBubble = (el, m) => {
      el.querySelector('[data-voice]')?._player?.destroy?.();
      el.className = `bubble${m.mine ? ' mine' : ''}${m.deleted ? ' deleted' : ''}`;
      el.dataset.id = m.id;
      el._msg = m;
      el.innerHTML = bubbleInner(m);
      const v = el.querySelector('[data-voice]');
      if (v && m.file) Voice.mount(v, m.file.url, m.file.audio || {});
    };
    const bubble = (m) => { const el = document.createElement('div'); fillBubble(el, m); return el; };
    const append = (msgs, prepend = false) => {
      if (!msgs.length) return;
      const atBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 80;
      const frag = document.createDocumentFragment();
      let day = prepend ? '' : lastDate;
      msgs.forEach((m) => {
        if (m.date !== day) {
          const d = document.createElement('div');
          d.className = 'chat-day';
          d.textContent = fmtDay(m.date);
          frag.appendChild(d);
          day = m.date;
        }
        frag.appendChild(bubble(m));
      });
      if (prepend) {
        const h = log.scrollHeight;
        log.insertBefore(frag, log.querySelector('.chat-day, .bubble'));
        log.scrollTop = log.scrollHeight - h;
        firstId = msgs[0].id;
      } else {
        log.appendChild(frag);
        lastDate = day;
        lastId = Math.max(lastId, msgs[msgs.length - 1].id);
        if (!firstId) firstId = msgs[0].id;
        if (atBottom || msgs.some((m) => m.mine)) log.scrollTop = log.scrollHeight;
      }
    };
    const removeBubble = (id) => {
      const el = log.querySelector(`.bubble[data-id="${id}"]`);
      if (!el) return;
      el.querySelector('[data-voice]')?._player?.destroy?.();
      el.classList.add('leaving');
      setTimeout(() => {
        const prev = el.previousElementSibling;
        el.remove();
        // Remove o separador de dia que ficou vazio.
        if (prev?.classList.contains('chat-day') && (!prev.nextElementSibling || prev.nextElementSibling.classList.contains('chat-day'))) prev.remove();
      }, reduceMotion() ? 0 : 220);
    };
    const applyChanges = (res) => {
      (res.changed || []).forEach((m) => { const el = log.querySelector(`.bubble[data-id="${m.id}"]`); if (el) fillBubble(el, m); });
      (res.hidden || []).forEach(removeBubble);
      if (res.server_time) since = res.server_time;
    };
    const markRead = () => {
      $$('.bubble.mine', log).forEach((b) => {
        if (+b.dataset.id <= readUpTo) { const r = $('[data-read]', b); if (r) { r.textContent = '✓✓'; r.title = 'Lida'; } }
      });
    };
    const load = async () => {
      try {
        const res = await api(`${endpoint}?after=0`);
        log.innerHTML = '';
        if (res.has_more) addLoadMore();
        if (!res.messages.length) log.insertAdjacentHTML('beforeend', '<div class="empty small" data-empty-chat>Nenhuma mensagem ainda. Escreva a primeira!</div>');
        append(res.messages);
        readUpTo = res.read_up_to;
        since = res.server_time || '';
        markRead();
        log.scrollTop = log.scrollHeight;
      } catch (err) { log.innerHTML = `<div class="empty small">${esc(err.message)}</div>`; }
    };
    const addLoadMore = () => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn ghost sm';
      b.style.alignSelf = 'center';
      b.dataset.loadMore = '';
      b.textContent = 'Carregar mensagens anteriores';
      b.onclick = async () => {
        try {
          const res = await api(`${endpoint}?before=${firstId}`);
          b.remove();
          append(res.messages, true);
          if (res.has_more) { addLoadMore(); }
        } catch (err) { toast(err.message, 'error'); }
      };
      log.prepend(b);
    };
    const poll = async () => {
      if (document.hidden) return;
      try {
        const res = await api(`${endpoint}?after=${lastId}&since=${encodeURIComponent(since)}`);
        if (res.messages.length) { $('[data-empty-chat]', log)?.remove(); append(res.messages.filter((m) => !log.querySelector(`.bubble[data-id="${m.id}"]`))); }
        applyChanges(res);
        readUpTo = res.read_up_to;
        markRead();
      } catch (e) { /* tenta na próxima */ }
    };
    load().then(() => { polling = setInterval(poll, 4000); });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

    // Mostra o microfone quando não há texto nem anexo; senão, o botão de enviar.
    const syncCompose = () => chat.classList.toggle('has-content', !!(input.value.trim() || attach));
    const setAttach = (file) => {
      attach = file;
      preview.classList.toggle('show', !!file);
      $('[data-attach-name]', preview).textContent = file ? `${file.name || 'Arquivo'} · ${(file.size / 1048576).toFixed(1)} MB` : '';
      syncCompose();
    };
    fileInput.addEventListener('change', () => setAttach(fileInput.files[0] || null));
    $('[data-attach-clear]', preview).addEventListener('click', () => { setAttach(null); fileInput.value = ''; });
    input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = `${Math.min(140, input.scrollHeight)}px`; syncCompose(); });
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 720px)').matches) { e.preventDefault(); form.requestSubmit(); } });

    async function sendForm(fd, hasFile, btn) {
      btn?.classList.add('is-loading');
      try {
        const res = hasFile ? await upload(endpoint, fd) : await api(endpoint, { method: 'POST', body: fd });
        $('[data-empty-chat]', log)?.remove();
        if (!log.querySelector(`.bubble[data-id="${res.message.id}"]`)) append([res.message]);
        return true;
      } catch (err) { toast(err.message, 'error'); return false; }
      finally { btn?.classList.remove('is-loading'); }
    }
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body = input.value.trim();
      if (!body && !attach) return;
      const fd = new FormData();
      fd.append('body', body);
      if (attach) fd.append('file', attach, attach.name || 'arquivo');
      if (await sendForm(fd, !!attach, form.querySelector('[type=submit]'))) {
        input.value = '';
        input.style.height = '';
        setAttach(null);
        fileInput.value = '';
      }
    });

    /* ----- Gravação de áudio ----- */
    const recBar = $('[data-rec-bar]', chat);
    const recLive = $('[data-rec-live]', chat);
    const recTime = $('[data-rec-time]', chat);
    const recWave = $('[data-rec-wave]', chat);
    const recPreview = $('[data-rec-preview]', chat);
    const recStop = $('[data-rec-stop]', chat);
    const recSend = $('[data-rec-send]', chat);
    const MAX_SECONDS = 5 * 60;
    let rec = null; // { recorder, stream, chunks, started, levels, timer, ctx, raf, blob, duration, peaks, url, player, cancelled }

    const micError = (err) => {
      if (!window.isSecureContext) return 'A gravação de áudio só funciona em conexão segura (https).';
      if (err?.name === 'NotAllowedError' || err?.name === 'SecurityError') return 'O microfone está bloqueado. Libere o acesso ao microfone nas configurações do navegador e tente de novo.';
      if (err?.name === 'NotFoundError' || err?.name === 'OverconstrainedError') return 'Nenhum microfone encontrado neste aparelho.';
      if (err?.name === 'NotReadableError') return 'O microfone está sendo usado por outro aplicativo.';
      return 'Não foi possível acessar o microfone.';
    };
    const showRecBar = (on) => {
      recBar.hidden = !on;
      form.hidden = on;
      chat.classList.toggle('is-recording', on);
    };
    const cleanupRec = () => {
      if (!rec) return;
      clearInterval(rec.timer);
      cancelAnimationFrame(rec.raf);
      rec.stream?.getTracks().forEach((t) => t.stop());
      rec.ctx?.close?.();
      rec.player?.destroy();
      if (rec.url) URL.revokeObjectURL(rec.url);
      rec = null;
      recPreview.hidden = true;
      recPreview.innerHTML = '';
      recLive.hidden = false;
      recStop.hidden = false;
      recWave.innerHTML = '';
      recTime.textContent = '0:00';
      showRecBar(false);
      recBtn.focus();
    };
    const elapsed = () => (rec ? (Date.now() - rec.started) / 1000 : 0);
    /** Para o gravador e devolve o áudio final (Blob). */
    const stopRecorder = () => new Promise((resolve) => {
      if (!rec || !rec.recorder || rec.recorder.state === 'inactive') { resolve(rec?.blob || null); return; }
      rec.recorder.onstop = () => {
        const mime = (rec.recorder.mimeType || 'audio/webm').split(';')[0];
        rec.blob = new Blob(rec.chunks, { type: mime });
        rec.duration = elapsed();
        clearInterval(rec.timer);
        cancelAnimationFrame(rec.raf);
        rec.stream.getTracks().forEach((t) => t.stop());
        resolve(rec.blob);
      };
      rec.recorder.stop();
    });
    const finalPeaks = async () => {
      // Forma de onda do áudio final; se o navegador não conseguir decodificar, usa os níveis medidos durante a gravação.
      try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        const ctx = new Ctx();
        const buf = await rec.blob.arrayBuffer();
        const audio = await new Promise((res, rej) => { const r = ctx.decodeAudioData(buf, res, rej); if (r && r.then) r.then(res, rej); });
        ctx.close?.();
        if (audio.duration && Number.isFinite(audio.duration)) rec.duration = audio.duration;
        return Voice.peaksFromBuffer(audio);
      } catch (e) {
        return rec.levels.length ? Voice.resample(Voice.normalize(rec.levels)) : [];
      }
    };
    const sendRecording = async () => {
      if (!rec) return;
      const blob = await stopRecorder();
      if (!blob || blob.size < 600 || rec.duration < 0.8) { toast('Áudio muito curto. Segure um pouco mais.', 'error'); cleanupRec(); return; }
      if (!rec.peaks) rec.peaks = await finalPeaks();
      const ext = blob.type.includes('mp4') || blob.type.includes('aac') ? 'm4a' : blob.type.includes('ogg') ? 'ogg' : 'webm';
      const fd = new FormData();
      fd.append('body', '');
      fd.append('audio', '1');
      fd.append('duration', rec.duration.toFixed(2));
      fd.append('peaks', JSON.stringify(rec.peaks || []));
      fd.append('file', new File([blob], `audio-${Date.now()}.${ext}`, { type: blob.type }));
      recSend.disabled = true;
      const ok = await sendForm(fd, true, recSend);
      recSend.disabled = false;
      if (ok) cleanupRec();
      else if (!recPreview.innerHTML) await showPreview(); // falhou: mantém o áudio para tentar de novo
    };
    const showPreview = async () => {
      const blob = await stopRecorder();
      if (!blob || blob.size < 600 || rec.duration < 0.8) { toast('Áudio muito curto.', 'error'); cleanupRec(); return; }
      rec.peaks = await finalPeaks();
      rec.url = URL.createObjectURL(blob);
      recLive.hidden = true;
      recStop.hidden = true;
      recPreview.hidden = false;
      rec.player = Voice.mount(recPreview, rec.url, { duration: rec.duration, peaks: rec.peaks });
    };
    recBtn.addEventListener('click', async () => {
      if (rec) return;
      if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
        toast(window.isSecureContext ? 'Seu navegador não permite gravar áudio. Envie um arquivo de áudio pelo clipe.' : micError(), 'error');
        return;
      }
      let stream;
      try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true } });
      } catch (err) { toast(micError(err), 'error'); return; }
      const type = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/webm'].find((t) => MediaRecorder.isTypeSupported?.(t)) || '';
      let recorder;
      try { recorder = new MediaRecorder(stream, type ? { mimeType: type } : {}); } catch (e) { stream.getTracks().forEach((t) => t.stop()); toast('Não foi possível iniciar a gravação neste navegador.', 'error'); return; }
      rec = { recorder, stream, chunks: [], started: Date.now(), levels: [], timer: null, ctx: null, raf: 0, blob: null, duration: 0, peaks: null };
      recorder.ondataavailable = (ev) => { if (ev.data.size) rec?.chunks.push(ev.data); };
      recorder.start(250);
      showRecBar(true);
      recWave.innerHTML = '';
      // Nível do microfone em tempo real (ondas ao vivo).
      try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        rec.ctx = new Ctx();
        const an = rec.ctx.createAnalyser();
        an.fftSize = 512;
        rec.ctx.createMediaStreamSource(stream).connect(an);
        const data = new Uint8Array(an.fftSize);
        let last = 0;
        const tick = (t) => {
          if (!rec) return;
          if (t - last > 90) {
            last = t;
            an.getByteTimeDomainData(data);
            let sum = 0;
            for (let i = 0; i < data.length; i++) { const v = (data[i] - 128) / 128; sum += v * v; }
            const lvl = Math.min(1, Math.sqrt(sum / data.length) * 3.2);
            rec.levels.push(lvl);
            const bar = document.createElement('i');
            bar.style.setProperty('--h', Math.max(0.1, lvl).toFixed(3));
            recWave.appendChild(bar);
            while (recWave.children.length > 60) recWave.firstChild.remove();
          }
          rec.raf = requestAnimationFrame(tick);
        };
        rec.raf = requestAnimationFrame(tick);
      } catch (e) { /* sem ondas ao vivo; a gravação continua */ }
      rec.timer = setInterval(() => {
        const s = elapsed();
        recTime.textContent = Voice.fmt(s);
        if (s >= MAX_SECONDS) { toast('Limite de 5 minutos atingido. Ouça e envie.', 'info'); showPreview(); }
      }, 250);
      recSend.focus();
    });
    $('[data-rec-cancel]', chat).addEventListener('click', async () => {
      if (rec?.recorder && rec.recorder.state !== 'inactive') { rec.recorder.onstop = null; rec.recorder.stop(); }
      cleanupRec();
      toast('Gravação descartada.');
    });
    recStop.addEventListener('click', () => showPreview());
    recSend.addEventListener('click', sendRecording);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && rec && !document.querySelector('dialog[open]')) $('[data-rec-cancel]', chat).click(); });

    /* ----- Apagar mensagens (toque longo no celular, menu ⋯ ou botão direito no computador) ----- */
    const sheet = $('[data-msg-sheet]');
    let target = null;
    const openSheet = (bubbleEl) => {
      const m = bubbleEl?._msg;
      if (!m || m.deleted) return;
      target = bubbleEl;
      $('[data-sheet-step="choose"]', sheet).hidden = false;
      $('[data-sheet-step="confirm"]', sheet).hidden = true;
      $('[data-del="all"]', sheet).hidden = !m.can_delete_all;
      bubbleEl.classList.add('selected');
      sheet.showModal();
      $('[data-del="me"]', sheet).focus();
      navigator.vibrate?.(12);
    };
    sheet.addEventListener('close', () => { target?.classList.remove('selected'); });
    $$('[data-sheet-close]', sheet).forEach((b) => b.addEventListener('click', () => sheet.close()));
    sheet.addEventListener('click', (e) => { if (e.target === sheet) sheet.close(); });
    const doDelete = async (scope) => {
      const el = target;
      const m = el?._msg;
      if (!m) return;
      sheet.close();
      try {
        const fd = new FormData();
        fd.append('scope', scope);
        const res = await api(`${endpoint}/${m.id}/apagar`, { method: 'POST', body: fd });
        if (scope === 'me') removeBubble(m.id);
        else fillBubble(el, res.message);
        toast(scope === 'me' ? 'Mensagem apagada para você.' : 'Mensagem apagada para todos.', 'success');
      } catch (err) { toast(err.message, 'error'); }
    };
    $('[data-del="me"]', sheet).addEventListener('click', () => doDelete('me'));
    $('[data-del="all"]', sheet).addEventListener('click', () => {
      $('[data-sheet-step="choose"]', sheet).hidden = true;
      $('[data-sheet-step="confirm"]', sheet).hidden = false;
      $('[data-del-confirm]', sheet).focus();
    });
    $('[data-del-confirm]', sheet).addEventListener('click', () => doDelete('all'));
    log.addEventListener('click', (e) => { const b = e.target.closest('[data-msg-menu]'); if (b) openSheet(b.closest('.bubble')); });
    log.addEventListener('contextmenu', (e) => {
      const el = e.target.closest('.bubble');
      if (!el || el.classList.contains('deleted') || e.target.closest('a, video')) return;
      e.preventDefault();
      openSheet(el);
    });
    let pressTimer = null;
    let pressStart = null;
    log.addEventListener('pointerdown', (e) => {
      if (e.pointerType === 'mouse') return;
      const el = e.target.closest('.bubble');
      if (!el || el.classList.contains('deleted') || e.target.closest('button, a, input, video')) return;
      pressStart = { x: e.clientX, y: e.clientY };
      pressTimer = setTimeout(() => { pressTimer = null; openSheet(el); }, 480);
    });
    const cancelPress = () => { clearTimeout(pressTimer); pressTimer = null; };
    log.addEventListener('pointerup', cancelPress);
    log.addEventListener('pointercancel', cancelPress);
    log.addEventListener('pointermove', (e) => { if (pressTimer && pressStart && Math.hypot(e.clientX - pressStart.x, e.clientY - pressStart.y) > 10) cancelPress(); });
  }

  /* ---------- Contadores no menu ---------- */
  if (document.body.dataset.mode && !chat) {
    setInterval(async () => {
      if (document.hidden) return;
      try {
        const c = await api('/api/contadores');
        const map = { chat: c.unread, students: c.pending, assessments: c.assessments, requests: c.requests };
        Object.entries(map).forEach(([k, v]) => {
          const link = $(`.nav a[href="${{ chat: document.body.dataset.mode === 'trainer' ? '/painel/mensagens' : '/app/mensagens', students: '/painel/alunos', assessments: '/painel/avaliacoes', requests: '/painel/solicitacoes' }[k]}"]`);
          if (!link || v === undefined) return;
          let badge = link.querySelector('.count');
          if (v > 0) { if (!badge) { badge = document.createElement('span'); badge.className = 'count'; link.appendChild(badge); } badge.textContent = v; }
          else badge?.remove();
        });
      } catch (e) { /* silencioso */ }
    }, 60000);
  }
})();
