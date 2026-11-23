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

    // Biblioteca
    const dlg = $('#library-dialog');
    const q = $('[data-lib-q]', dlg);
    const all = $('[data-lib-all]', dlg);
    const results = $('[data-lib-results]', dlg);
    let lastResults = [];
    let searchTimer = null;
    const search = async () => {
      results.innerHTML = '<div class="loading-block">Buscando</div>';
      try {
        const params = new URLSearchParams({ q: q.value });
        if (!all.checked) params.set('modality', cfg.modality_id);
        const res = await api(`/api/biblioteca?${params}`);
        lastResults = res.results;
        results.innerHTML = res.results.length ? res.results.map((r, i) => `<div class="lib-result"><div class="grow"><div class="strong">${esc(r.name)}</div><div class="xs muted">${esc(r.modality)}${r.category ? ' · ' + esc(r.category) : ''}${r.video_url || r.video_file_id ? ' · vídeo' : ''}</div></div><button type="button" class="btn pw sm" data-pick="${i}">${ICON('plus')} Adicionar</button></div>`).join('')
          : '<div class="empty small">Nenhum exercício encontrado. <a href="/painel/biblioteca/novo" target="_blank">Cadastrar</a></div>';
      } catch (err) { results.innerHTML = `<div class="empty small">${esc(err.message)}</div>`; }
    };
    $('[data-open-dialog="library-dialog"]').addEventListener('click', () => { search(); setTimeout(() => q.focus(), 50); });
    const createBtn = $('[data-lib-create]', dlg);
    const syncCreate = () => { if (createBtn) createBtn.querySelector('span').textContent = q.value.trim() ? `Criar “${q.value.trim()}” como exercício novo` : 'Criar exercício personalizado'; };
    createBtn?.addEventListener('click', () => {
      items.push({ exercise_id: null, name: q.value.trim(), instructions: '', notes: '', video_url: '', video_file_id: null, params: {}, fields: [...cfg.modality_fields] });
      render();
      markDirty();
      dlg.close();
      list.lastElementChild?.querySelector(q.value.trim() ? '[data-param]' : '.item-name')?.focus();
    });
    q.addEventListener('input', () => { syncCreate(); clearTimeout(searchTimer); searchTimer = setTimeout(search, 300); });
    all.addEventListener('change', search);
    results.addEventListener('click', (e) => {
      const b = e.target.closest('[data-pick]');
      if (!b) return;
      const r = lastResults[+b.dataset.pick];
      items.push({ exercise_id: r.id, name: r.name, instructions: r.instructions || '', notes: r.notes || '', video_url: r.video_url || '', video_file_id: r.video_file_id, video_file_url: r.video_file_url, params: {}, fields: r.fields && r.fields.length ? r.fields : [...cfg.modality_fields] });
      render();
      markDirty();
      b.textContent = 'Adicionado';
      b.disabled = true;
    });
    render();
  }

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
    let audioMode = false;
    let polling = null;
    let readUpTo = 0;

    const fmtDay = (d) => {
      const today = new Date();
      const iso = (x) => `${x.getFullYear()}-${String(x.getMonth() + 1).padStart(2, '0')}-${String(x.getDate()).padStart(2, '0')}`;
      const y = new Date(); y.setDate(y.getDate() - 1);
      if (d === iso(today)) return 'Hoje';
      if (d === iso(y)) return 'Ontem';
      const [yy, mm, dd] = d.split('-');
      return `${dd}/${mm}/${yy}`;
    };
    const bubble = (m) => {
      const el = document.createElement('div');
      el.className = `bubble${m.mine ? ' mine' : ''}`;
      el.dataset.id = m.id;
      let html = '';
      if (m.file) {
        if (m.file.removed) html += '<em class="small">Arquivo removido</em>';
        else if (m.file.kind === 'image') html += `<a href="${esc(m.file.url)}" target="_blank"><img src="${esc(m.file.url)}" alt="Imagem enviada" loading="lazy"></a>`;
        else if (m.file.kind === 'video') html += `<video controls preload="metadata" playsinline src="${esc(m.file.url)}"></video>`;
        else if (m.file.kind === 'audio') html += `<audio controls preload="metadata" src="${esc(m.file.url)}"></audio>`;
        else html += `<a class="file-link" href="${esc(m.file.url)}&download=1">${ICON('file', 'icon')}<span><span class="strong">${esc(m.file.name)}</span><br><span class="xs">${esc(m.file.size)}</span></span></a>`;
      }
      if (m.body) html += `<div>${esc(m.body)}</div>`;
      html += `<div class="meta"><span>${esc(m.time)}</span>${m.mine ? `<span data-read title="${m.read ? 'Lida' : 'Enviada'}">${m.read ? '✓✓' : '✓'}</span>` : ''}</div>`;
      el.innerHTML = html;
      return el;
    };
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
        log.insertBefore(frag, log.firstChild);
        log.scrollTop = log.scrollHeight - h;
        firstId = msgs[0].id;
      } else {
        log.appendChild(frag);
        lastDate = day;
        lastId = msgs[msgs.length - 1].id;
        if (!firstId) firstId = msgs[0].id;
        if (atBottom || msgs.some((m) => m.mine)) log.scrollTop = log.scrollHeight;
      }
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
        const res = await api(`${endpoint}?after=${lastId}`);
        if (res.messages.length) { $('[data-empty-chat]', log)?.remove(); append(res.messages.filter((m) => !log.querySelector(`.bubble[data-id="${m.id}"]`))); }
        readUpTo = res.read_up_to;
        markRead();
      } catch (e) { /* tenta na próxima */ }
    };
    load().then(() => { polling = setInterval(poll, 4000); });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

    const setAttach = (file, isAudio = false) => {
      attach = file;
      audioMode = isAudio;
      preview.classList.toggle('show', !!file);
      $('[data-attach-name]', preview).textContent = file ? `${file.name || 'Áudio gravado'} · ${(file.size / 1048576).toFixed(1)} MB` : '';
    };
    fileInput.addEventListener('change', () => setAttach(fileInput.files[0] || null));
    $('[data-attach-clear]', preview).addEventListener('click', () => { setAttach(null); fileInput.value = ''; });
    input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = `${Math.min(140, input.scrollHeight)}px`; });
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 720px)').matches) { e.preventDefault(); form.requestSubmit(); } });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body = input.value.trim();
      if (!body && !attach) return;
      const fd = new FormData();
      fd.append('body', body);
      if (attach) { fd.append('file', attach, attach.name || 'audio.webm'); if (audioMode) fd.append('audio', '1'); }
      const btn = form.querySelector('[type=submit]');
      btn.classList.add('is-loading');
      try {
        const res = attach ? await upload(endpoint, fd) : await api(endpoint, { method: 'POST', body: fd });
        $('[data-empty-chat]', log)?.remove();
        if (!log.querySelector(`.bubble[data-id="${res.message.id}"]`)) append([res.message]);
        input.value = '';
        input.style.height = '';
        setAttach(null);
        fileInput.value = '';
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.classList.remove('is-loading'); }
    });

    // Gravação de áudio
    let recorder = null;
    let chunks = [];
    recBtn.addEventListener('click', async () => {
      if (recorder && recorder.state === 'recording') { recorder.stop(); return; }
      if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) { toast('Seu navegador não permite gravar áudio. Envie um arquivo de áudio pelo clipe.', 'error'); return; }
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const type = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus'].find((t) => MediaRecorder.isTypeSupported?.(t)) || '';
        recorder = new MediaRecorder(stream, type ? { mimeType: type } : {});
        chunks = [];
        const started = Date.now();
        recorder.ondataavailable = (ev) => { if (ev.data.size) chunks.push(ev.data); };
        recorder.onstop = () => {
          stream.getTracks().forEach((t) => t.stop());
          recBtn.classList.remove('recording');
          recBtn.setAttribute('aria-label', 'Gravar áudio');
          const mime = recorder.mimeType || 'audio/webm';
          const ext = mime.includes('mp4') ? 'm4a' : mime.includes('ogg') ? 'ogg' : 'webm';
          const blob = new Blob(chunks, { type: mime.split(';')[0] });
          if (Date.now() - started < 800) return;
          setAttach(new File([blob], `audio-${Date.now()}.${ext}`, { type: blob.type }), true);
          toast('Áudio pronto. Toque em enviar.', 'success');
        };
        recorder.start();
        recBtn.classList.add('recording');
        recBtn.setAttribute('aria-label', 'Parar gravação');
        setTimeout(() => { if (recorder?.state === 'recording') recorder.stop(); }, 5 * 60 * 1000);
      } catch (err) { toast('Permita o uso do microfone para gravar áudio.', 'error'); }
    });
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
