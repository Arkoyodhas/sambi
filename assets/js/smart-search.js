/* Pencarian pintar beranda: palet perintah dengan saran instan, jawaban cepat, navigasi keyboard, riwayat & suara. */
(function () {
  'use strict';
  var trigger = document.querySelector('[data-ss-trigger]');
  if (!trigger) return;
  var API = trigger.getAttribute('data-api');
  var heroInput = trigger.querySelector('[data-ss-hero-input]');
  var typed = trigger.querySelector('[data-ss-typed]');
  var RKEY = 'desa_ss_recent';
  var cache = {}, ctrl = null, debounce = 0, active = -1, flat = [], lastQ = null;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isMac = /Mac|iPhone|iPad/.test(navigator.platform || '');
  var kbd = trigger.querySelector('.ss-kbd'); if (kbd && isMac) kbd.textContent = '⌘ K';

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function hl(text, q) {
    var out = esc(text);
    var words = (q || '').toLowerCase().split(/\s+/).filter(function (w) { return w.length > 1; });
    if (!words.length) return out;
    words.sort(function (a, b) { return b.length - a.length; });
    var re = new RegExp('(' + words.map(function (w) { return esc(w).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('|') + ')', 'gi');
    return out.replace(re, '<mark>$1</mark>');
  }
  function recent() { try { return JSON.parse(localStorage.getItem(RKEY) || '[]').slice(0, 5); } catch (e) { return []; } }
  function remember(q) {
    q = (q || '').trim(); if (q.length < 2) return;
    var r = recent().filter(function (x) { return x.toLowerCase() !== q.toLowerCase(); });
    r.unshift(q);
    try { localStorage.setItem(RKEY, JSON.stringify(r.slice(0, 5))); } catch (e) {}
  }

  /* ---------- Placeholder mengetik otomatis di hero ---------- */
  var hints = ['Cara buat surat domisili…', 'Berapa jumlah penduduk?', 'Jam pelayanan kantor desa', 'Produk UMKM keripik', 'Jadwal posyandu', 'Info bantuan sosial'];
  if (typed && !reduce) {
    var hi = 0, ci = 0, del = false;
    (function tick() {
      if (heroInput.value || document.activeElement === heroInput) { typed.textContent = ''; setTimeout(tick, 600); return; }
      var h = hints[hi];
      ci += del ? -1 : 1;
      typed.textContent = h.slice(0, ci);
      var wait = del ? 28 : 62;
      if (!del && ci >= h.length) { del = true; wait = 1700; }
      else if (del && ci <= 0) { del = false; hi = (hi + 1) % hints.length; wait = 380; }
      setTimeout(tick, wait);
    })();
    heroInput.placeholder = '';
  }

  /* ---------- Palet ---------- */
  var pal = document.createElement('div');
  pal.className = 'ss-pal';
  pal.hidden = true;
  pal.setAttribute('data-testid', 'smart-search-palette');
  pal.innerHTML =
    '<div class="ss-backdrop" data-ss-close></div>' +
    '<div class="ss-dialog" role="dialog" aria-modal="true" aria-label="Pencarian pintar">' +
      '<div class="ss-head">' +
        '<i class="bi bi-search ss-head-ic" aria-hidden="true"></i>' +
        '<input class="ss-input" type="search" autocomplete="off" spellcheck="false" placeholder="Tanya atau cari apa saja… mis. &quot;syarat surat pindah&quot;" aria-label="Kata kunci pencarian" role="combobox" aria-expanded="true" aria-controls="ssList" data-testid="smart-search-input">' +
        '<span class="ss-spin" aria-hidden="true"></span>' +
        '<button type="button" class="ss-voice" aria-label="Cari dengan suara" data-testid="smart-search-voice" hidden><i class="bi bi-mic-fill"></i></button>' +
        '<button type="button" class="ss-esc" data-ss-close aria-label="Tutup pencarian" data-testid="smart-search-close">Esc</button>' +
      '</div>' +
      '<div class="ss-body" id="ssList" role="listbox" data-lenis-prevent data-testid="smart-search-results"></div>' +
      '<div class="ss-foot"><span><kbd>↑</kbd><kbd>↓</kbd> pilih</span><span><kbd>Enter</kbd> buka</span><span><kbd>Esc</kbd> tutup</span><span class="ss-foot-count" data-testid="smart-search-count"></span></div>' +
    '</div>';
  document.body.appendChild(pal);
  var input = pal.querySelector('.ss-input');
  var body = pal.querySelector('.ss-body');
  var countEl = pal.querySelector('.ss-foot-count');
  var voiceBtn = pal.querySelector('.ss-voice');
  var lastFocus = null, hideTimer = 0;

  function open(q) {
    clearTimeout(hideTimer);
    if (!pal.hidden && pal.classList.contains('is-open')) { if (q != null) { input.value = q; run(); } return; }
    lastFocus = document.activeElement;
    pal.hidden = false;
    document.documentElement.classList.add('ss-open');
    if (window.desaLenis && window.desaLenis.stop) window.desaLenis.stop();
    input.value = q != null ? q : (heroInput.value || '');
    input.focus({ preventScroll: true });
    requestAnimationFrame(function () { pal.classList.add('is-open'); });
    lastQ = null;
    run(true);
  }
  function close() {
    if (pal.hidden) return;
    pal.classList.remove('is-open');
    document.documentElement.classList.remove('ss-open');
    if (window.desaLenis && window.desaLenis.start) window.desaLenis.start();
    hideTimer = setTimeout(function () { pal.hidden = true; }, reduce ? 0 : 220);
    heroInput.value = input.value;
    if (lastFocus && lastFocus.focus && lastFocus !== heroInput) lastFocus.focus();
  }

  trigger.addEventListener('submit', function (e) { e.preventDefault(); open(heroInput.value); });
  heroInput.addEventListener('focus', function () { open(heroInput.value); heroInput.blur(); });
  heroInput.addEventListener('pointerdown', function (e) { e.preventDefault(); open(heroInput.value); });
  Array.prototype.forEach.call(document.querySelectorAll('[data-ss-chip]'), function (b) {
    b.addEventListener('click', function () { open(b.getAttribute('data-ss-chip')); });
  });
  pal.addEventListener('click', function (e) {
    if (e.target.closest('[data-ss-close]')) { close(); return; }
    var chip = e.target.closest('[data-q]');
    if (chip) { input.value = chip.getAttribute('data-q'); input.focus(); run(true); return; }
    var clr = e.target.closest('[data-ss-clear]');
    if (clr) { try { localStorage.removeItem(RKEY); } catch (er) {} render(cache[''] || { groups: [], answers: [] }, ''); return; }
    var a = e.target.closest('a.ss-item');
    if (a) remember(input.value);
  });
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); pal.hidden ? open() : close(); return; }
    if (e.key === '/' && pal.hidden && !/INPUT|TEXTAREA|SELECT/.test((document.activeElement || {}).tagName || '') && !(document.activeElement || {}).isContentEditable) { e.preventDefault(); open(); return; }
    if (pal.hidden) return;
    if (e.key === 'Escape') { e.preventDefault(); close(); }
    else if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
    else if (e.key === 'Enter' && document.activeElement === input) {
      e.preventDefault();
      var t = flat[active >= 0 ? active : 0];
      if (t) { remember(input.value); t.click(); }
    } else if (e.key === 'Tab') {
      var f = pal.querySelectorAll('input,button:not([hidden]),a[href]');
      if (!f.length) return;
      if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
    }
  });

  function move(d) {
    if (!flat.length) return;
    active = (active + d + flat.length) % flat.length;
    flat.forEach(function (el, i) { el.classList.toggle('is-active', i === active); el.setAttribute('aria-selected', i === active ? 'true' : 'false'); });
    flat[active].scrollIntoView({ block: 'nearest' });
  }

  input.addEventListener('input', function () { clearTimeout(debounce); debounce = setTimeout(run, 200); });

  function skeleton() {
    var s = '<div class="ss-skel" data-testid="smart-search-loading">';
    for (var i = 0; i < 4; i++) s += '<div class="ss-skel-row" style="--d:' + (i * 90) + 'ms"><i></i><span><b></b><em></em></span></div>';
    body.innerHTML = s + '</div>';
  }

  function run(force) {
    var q = input.value.trim();
    if (!force && q === lastQ) return;
    lastQ = q;
    var key = q.toLowerCase();
    if (cache[key]) { render(cache[key], q); return; }
    if (ctrl) ctrl.abort();
    ctrl = 'AbortController' in window ? new AbortController() : null;
    pal.classList.add('is-busy');
    if (q && !body.querySelector('.ss-item')) skeleton();
    fetch(API + '?q=' + encodeURIComponent(q), { signal: ctrl ? ctrl.signal : undefined, headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status === 429 ? 'Terlalu banyak pencarian, tunggu sebentar.' : 'Gagal memuat'); return r.json(); })
      .then(function (d) { cache[key] = d; if (input.value.trim() === q) render(d, q); })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        body.innerHTML = '<div class="ss-empty" data-testid="smart-search-error"><i class="bi bi-wifi-off"></i><b>Koneksi bermasalah</b><span>' + esc(err.message || 'Periksa internet Anda lalu coba lagi.') + '</span></div>';
      })
      .then(function () { pal.classList.remove('is-busy'); });
  }

  function itemHtml(it, q, gi, ii, icon) {
    var media = it.thumb ? '<img src="' + esc(it.thumb) + '" alt="" loading="lazy" decoding="async" onerror="this.outerHTML=\'<i class=&quot;bi ' + esc(icon) + '&quot;></i>\'">' : '<i class="bi ' + esc(it.icon || icon) + '"></i>';
    return '<a class="ss-item" role="option" href="' + esc(it.url) + '" style="--d:' + Math.min(ii * 35 + gi * 50, 400) + 'ms" data-testid="smart-search-item-' + gi + '-' + ii + '">' +
      '<span class="ss-item-media">' + media + '</span>' +
      '<span class="ss-item-text"><b>' + hl(it.title, q) + '</b>' + (it.desc ? '<small>' + hl(it.desc, q) + '</small>' : '') + '</span>' +
      (it.meta ? '<span class="ss-item-meta">' + esc(it.meta) + '</span>' : '') +
      '<i class="bi bi-arrow-return-left ss-item-go" aria-hidden="true"></i></a>';
  }

  function render(d, q) {
    var html = '';
    if (!q) {
      var r = recent();
      if (r.length) {
        html += '<div class="ss-sec"><div class="ss-sec-h"><span><i class="bi bi-clock-history"></i> Pencarian terakhir</span><button type="button" class="ss-link" data-ss-clear data-testid="smart-search-clear-recent">Hapus</button></div><div class="ss-chips">' +
          r.map(function (x, i) { return '<button type="button" class="ss-chip" data-q="' + esc(x) + '" data-testid="smart-search-recent-' + i + '"><i class="bi bi-arrow-counterclockwise"></i> ' + esc(x) + '</button>'; }).join('') + '</div></div>';
      }
      html += '<div class="ss-sec"><div class="ss-sec-h"><span><i class="bi bi-magic"></i> Coba tanyakan</span></div><div class="ss-chips">' +
        (d.chips || []).map(function (x, i) { return '<button type="button" class="ss-chip" data-q="' + esc(x) + '" data-testid="smart-search-suggest-' + i + '">' + esc(x) + '</button>'; }).join('') + '</div></div>';
    } else if (d.corrected) {
      html += '<div class="ss-fix" data-testid="smart-search-corrected"><i class="bi bi-magic"></i> Menampilkan hasil untuk <b>' + esc(d.corrected) + '</b></div>';
    }
    if (d.answers && d.answers.length) {
      html += '<div class="ss-answers">' + d.answers.map(function (a, i) {
        return '<a class="ss-answer ss-item" role="option" href="' + esc(a.url) + '" style="--d:' + (i * 60) + 'ms" data-testid="smart-search-answer-' + i + '">' +
          '<span class="ss-answer-ic"><i class="bi ' + esc(a.icon) + '"></i></span><span class="ss-answer-t"><small>' + esc(a.label) + '</small><b>' + esc(a.value) + '</b>' + (a.sub ? '<em>' + esc(a.sub) + '</em>' : '') + '</span>' +
          '<span class="ss-answer-tag">Jawaban cepat</span></a>';
      }).join('') + '</div>';
    }
    (d.groups || []).forEach(function (g, gi) {
      html += '<div class="ss-sec" data-testid="smart-search-group-' + esc(g.key) + '"><div class="ss-sec-h"><span><i class="bi ' + esc(g.icon) + '"></i> ' + esc(g.label) + '</span><em>' + g.items.length + '</em></div>' +
        g.items.map(function (it, ii) { return itemHtml(it, q, gi, ii, g.icon); }).join('') + '</div>';
    });
    if (q && !d.total) {
      html += '<div class="ss-empty" data-testid="smart-search-empty"><i class="bi bi-search-heart"></i><b>Belum ketemu untuk “' + esc(q) + '”</b><span>Coba kata lain yang lebih umum, atau tanyakan langsung ke petugas desa.</span>' +
        '<div class="ss-chips">' + ['Layanan surat', 'Pengaduan', 'Kontak desa', 'Berita'].map(function (x) { return '<button type="button" class="ss-chip" data-q="' + x + '">' + x + '</button>'; }).join('') + '</div></div>';
    }
    body.innerHTML = html;
    flat = Array.prototype.slice.call(body.querySelectorAll('.ss-item'));
    active = -1;
    if (q && flat.length) move(1);
    countEl.textContent = q ? (d.total ? d.total + ' hasil' : '') : '';
  }

  /* ---------- Pencarian suara (bila didukung browser) ---------- */
  var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (SR) {
    voiceBtn.hidden = false;
    var rec = null;
    voiceBtn.addEventListener('click', function () {
      if (rec) { rec.stop(); return; }
      rec = new SR(); rec.lang = 'id-ID'; rec.interimResults = true; rec.maxAlternatives = 1;
      pal.classList.add('is-listening');
      rec.onresult = function (ev) {
        var t = ''; for (var i = 0; i < ev.results.length; i++) t += ev.results[i][0].transcript;
        input.value = t; run();
      };
      rec.onend = rec.onerror = function () { pal.classList.remove('is-listening'); rec = null; input.focus(); };
      rec.start();
    });
  }
})();
