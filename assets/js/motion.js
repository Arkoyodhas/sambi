/* Desa Dawung — motion layer: Lenis smooth scroll + GSAP ScrollTrigger reveals,
   hero parallax, odometer stats, self-drawing flow line, image parallax, tilt, filter grid, FAQ. */
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var G, ST, lenis = null;

  function ready(fn) { if (document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  ready(init);

  /* ---- Background video: paksa autoplay senyap tanpa tombol play ---- */
  function initVideo() {
    $$('video.hx-video, video[autoplay][muted]').forEach(function (v) {
      v.muted = true; v.defaultMuted = true; v.playsInline = true; v.controls = false;
      v.setAttribute('muted', ''); v.setAttribute('playsinline', '');
      var tryPlay = function () { var p = v.play(); if (p && p.catch) p.catch(function () {}); };
      if (v.readyState >= 2) tryPlay(); else v.addEventListener('canplay', tryPlay, { once: true });
      ['touchstart', 'pointerdown', 'scroll', 'keydown'].forEach(function (ev) { window.addEventListener(ev, function () { if (v.paused) tryPlay(); }, { once: true, passive: true }); });
      document.addEventListener('visibilitychange', function () { if (!document.hidden && v.paused) tryPlay(); });
      if ('IntersectionObserver' in window) new IntersectionObserver(function (en) { en[0].isIntersecting ? tryPlay() : v.pause(); }).observe(v);
    });
  }

  function init() {
    initVideo();
    initSiaga();
    G = window.gsap; ST = window.ScrollTrigger;
    if (!G || !ST) {
      $$('.odo').forEach(function (o) { o.classList.add('odo-ready'); });
      $$('.tl-cta-scene').forEach(function (e) { e.classList.add('is-inview'); });
      initFaq(); initFilters();
      return;
    }
    G.registerPlugin(ST);
    if (window.Flip) G.registerPlugin(window.Flip);
    document.documentElement.classList.add('motion-ready');
    $$('.lc-popup,.fb-modal,.offcanvas,.offcanvas-lg,.dropdown-menu,.modal,.glightbox-container').forEach(function (el) { el.setAttribute('data-lenis-prevent', ''); });

    if (!reduce && window.Lenis) initLenis();
    ST.config({ ignoreMobileResize: true, limitCallbacks: true });
    G.config({ force3D: true });
    initInview();
    $$('.page-header h1').forEach(function (h) { h.removeAttribute('data-aos'); h.setAttribute('data-split', ''); });
    initScaleIns();
    initReveals();
    initApple();
    initOdometers();
    initHero();
    initEditorial();
    initFlow();
    initParallax();
    initSketches();
    initSocial();
    initTilt();
    initFilters();
    initFaq();
    var splitTargets = $$('[data-split]');
    var runSplit = function () { initSplit(splitTargets); ST.refresh(); };
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(runSplit); else runSplit();
    window.addEventListener('load', function () { ST.refresh(); });
  }

  function initLenis() {
    lenis = new window.Lenis({
      lerp: 0.12,
      smoothWheel: true,
      wheelMultiplier: 1,
      anchors: { offset: -84 }
    });
    window.desaLenis = lenis;
    lenis.on('scroll', ST.update);
    G.ticker.add(function (t) { lenis.raf(t * 1000); });
    G.ticker.lagSmoothing(0);
    ['show.bs.modal', 'show.bs.offcanvas'].forEach(function (ev) { document.addEventListener(ev, function () { lenis.stop(); }); });
    ['hidden.bs.modal', 'hidden.bs.offcanvas'].forEach(function (ev) { document.addEventListener(ev, function () { lenis.start(); }); });
    var btt = document.getElementById('backToTop');
    btt && btt.addEventListener('click', function (e) { e.stopImmediatePropagation(); lenis.scrollTo(0, { duration: 1.6 }); }, true);
    // Modal kustom (survei, live chat penuh layar) mengunci body -> hentikan Lenis selama terkunci
    new MutationObserver(function () {
      var locked = getComputedStyle(document.body).overflow === 'hidden' || document.documentElement.classList.contains('lc-locked');
      locked ? lenis.stop() : lenis.start();
    }).observe(document.body, { attributes: true, attributeFilter: ['style', 'class'] });
  }

  /* ---- Tombol Siaga: lingkaran yang mekar menjadi panel kontak darurat ---- */
  function initSiaga() {
    var root = document.querySelector('[data-siaga]');
    if (!root) return;
    var btn = root.querySelector('[data-siaga-open]');
    var panel = root.querySelector('.siaga-panel');
    var back = root.querySelector('.siaga-backdrop');
    document.body.appendChild(back); document.body.appendChild(panel);
    var timer, lastFocus;
    function origin() {
      var b = btn.getBoundingClientRect(), p = panel.getBoundingClientRect();
      panel.style.setProperty('--ox', (b.left + b.width / 2 - p.left) + 'px');
      panel.style.setProperty('--oy', (b.top + b.height / 2 - p.top) + 'px');
    }
    function open() {
      clearTimeout(timer); lastFocus = document.activeElement;
      back.hidden = false; panel.hidden = false;
      origin();
      requestAnimationFrame(function () { requestAnimationFrame(function () {
        back.classList.add('is-open'); panel.classList.add('is-open'); root.classList.add('is-open');
      }); });
      btn.setAttribute('aria-expanded', 'true');
      if (window.desaLenis) window.desaLenis.stop();
      setTimeout(function () { var x = panel.querySelector('.siaga-x'); x && x.focus({ preventScroll: true }); }, 350);
    }
    function close() {
      origin();
      back.classList.remove('is-open'); panel.classList.remove('is-open'); root.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
      if (window.desaLenis) window.desaLenis.start();
      timer = setTimeout(function () { back.hidden = true; panel.hidden = true; }, reduce ? 0 : 560);
      lastFocus && lastFocus.focus && lastFocus.focus({ preventScroll: true });
    }
    btn.addEventListener('click', function () { panel.classList.contains('is-open') ? close() : open(); });
    [back].concat(Array.prototype.slice.call(panel.querySelectorAll('[data-siaga-close]'))).forEach(function (el) { el.addEventListener('click', close); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('is-open')) close(); });
    window.addEventListener('resize', function () { if (panel.classList.contains('is-open')) origin(); });
  }

  /* ---- Pause decorative CSS loops when off-screen ---- */
  function initInview() {
    var els = $$('.tl-cta-scene,.soc-float');
    if (!els.length || !('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('is-inview'); }); return; }
    var io = new IntersectionObserver(function (en) { en.forEach(function (x) { x.target.classList.toggle('is-inview', x.isIntersecting); }); }, { rootMargin: '80px' });
    els.forEach(function (e) { io.observe(e); });
  }

  /* ---- Heading line split ---- */
  function initSplit(els) {
    // Tulis semua -> baca semua -> tulis semua: satu kali layout, tanpa forced reflow berulang.
    els = els.filter(function (el) { return !el.dataset.splitDone; });
    els.forEach(function (el) {
      el.dataset.splitDone = '1';
      var words = el.textContent.trim().split(/\s+/);
      el.setAttribute('aria-label', el.textContent.trim());
      el.innerHTML = words.map(function (w) { return '<span class="split-w" style="display:inline-block">' + w.replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }) + '</span>'; }).join(' ');
    });
    var allLines = els.map(function (el) {
      var lines = [], top = null;
      $$('.split-w', el).forEach(function (w) {
        var t = w.offsetTop;
        if (top === null || Math.abs(t - top) > 4) { lines.push([]); top = t; }
        lines[lines.length - 1].push(w.outerHTML);
      });
      return lines;
    });
    els.forEach(function (el, i) {
      el.innerHTML = allLines[i].map(function (l) { return '<span class="split-line" aria-hidden="true"><span>' + l.join(' ') + '</span></span>'; }).join('');
      if (reduce) return;
      var inner = $$('.split-line > span', el);
      var inHero = !!el.closest('.hx-hero');
      G.from(inner, {
        yPercent: 110, duration: inHero ? 1.3 : 1.1, ease: 'power4.out', stagger: 0.09, delay: inHero ? 0.25 : 0,
        scrollTrigger: inHero ? null : { trigger: el, start: 'top 88%', once: true }
      });
    });
  }

  /* Matikan CSS transition transform selama tween GSAP agar tidak saling tarik (stutter). */
  var TRANS_SEL = '.tl-card,.card-3d,.news-card,.stat-card,.tr-card';
  function transTargets(els) { return els.reduce(function (a, el) { if (el.matches(TRANS_SEL)) a.push(el); a.push.apply(a, $$(TRANS_SEL, el)); return a; }, []); }
  function noTrans(els) { return function () { transTargets(els).forEach(function (el) { el.style.transition = 'none'; }); }; }
  function restoreTrans(els) { return function () { transTargets(els).forEach(function (el) { el.style.transition = ''; }); }; }

  /* ---- Generic reveals (incl. legacy data-aos) ---- */
  function initReveals() {
    if (reduce) return;
    $$('[data-reveal-group]').forEach(function (g) {
      var kids = Array.prototype.slice.call(g.children);
      G.from(kids, { y: 48, rotateX: 10, transformPerspective: 1000, transformOrigin: '50% 0%', opacity: 0, duration: 1.2, ease: 'power3.out', stagger: 0.08, clearProps: 'transform', onStart: noTrans(kids), onComplete: restoreTrans(kids), scrollTrigger: { trigger: g, start: 'top 86%', once: true } });
    });
    $$('[data-reveal],[data-aos]').forEach(function (el) {
      if (el.closest('[data-reveal-group]') || el.closest('.hx-hero') || el.matches(SCALE_SEL)) return;
      G.from(el, { y: 32, opacity: 0, duration: 1.1, ease: 'power3.out', delay: (parseInt(el.getAttribute('data-aos-delay') || '0', 10) / 1000) || 0, clearProps: 'transform', onStart: noTrans([el]), onComplete: restoreTrans([el]), scrollTrigger: { trigger: el, start: 'top 90%', once: true } });
    });
  }

  /* ---- Apple-style scrubbed scale-in for large panels ---- */
  var SCALE_SEL = '.tl-flow,.tl-map-card,.tl-footer-cta,.tl-stats,.tl-gallery,.social-slider,.tl-grid';
  function initScaleIns() {
    if (reduce) return;
    $$(SCALE_SEL).forEach(function (el) {
      G.fromTo(el, { scale: 0.94, opacity: 0, y: 50 }, { scale: 1, opacity: 1, y: 0, duration: 1.3, ease: 'expo.out', clearProps: 'transform', scrollTrigger: { trigger: el, start: 'top 88%', once: true } });
    });
  }

  /* ---- Apple-like section details: eyebrows, word-by-word text, gallery zoom, inner pages ---- */
  function initApple() {
    if (reduce) return;
    $$('.tl-eyebrow,.section-head .eyebrow').forEach(function (el) {
      if (el.closest('.hx-hero') || el.closest('[data-clip-reveal]')) return;
      G.from(el, { opacity: 0, letterSpacing: '0.42em', duration: 1.3, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 90%', once: true } });
    });
    $$('.tl-head p').forEach(function (p) {
      if (p.closest('.hx-hero') || p.children.length || p.hasAttribute('data-no-scrub')) return;
      var words = p.textContent.trim().split(/\s+/);
      if (words.length < 4 || words.length > 140) return;
      p.innerHTML = words.map(function (w) { return '<span class="w-scrub">' + w.replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }) + '</span>'; }).join(' ');
      G.fromTo($$('.w-scrub', p), { opacity: 0.18 }, { opacity: 1, stagger: 0.05, ease: 'none', scrollTrigger: { trigger: p, start: 'top 88%', end: 'bottom 62%', scrub: 0.6 } });
    });
    var ph = document.querySelector('.page-header .container');
    if (ph) G.to(ph, { y: 60, opacity: 0.2, ease: 'none', scrollTrigger: { trigger: '.page-header', start: 'top top', end: 'bottom top', scrub: 0.6 } });
    // Halaman dalam: elemen konten muncul bertahap saat digulir
    var skip = '[data-reveal-group],[data-reveal],[data-aos],.hx-hero,.modal,.offcanvas,.offcanvas-lg,.lc-popup,.fb-modal,.page-header,.tl-faq,.site-footer,.site-navbar,' + SCALE_SEL;
    var picked = $$('main .card, main .card-3d, main .tl-card, main .stat-card, main .table-responsive, main .list-group, main .accordion, main .news-card, main form, main .ratio, main .leaflet-container').filter(function (el) {
      if (el.closest(skip)) return false;
      var parent = el.parentElement && el.parentElement.closest('.card, .card-3d, .tl-card, .stat-card, .table-responsive, .list-group, .accordion, .news-card, form');
      return !(parent && parent.closest('main'));
    });
    if (!picked.length) return;
    G.set(picked, { opacity: 0, y: 44 });
    ST.batch(picked, {
      start: 'top 92%', once: true,
      onEnter: function (batch) { noTrans(batch)(); G.to(batch, { opacity: 1, y: 0, duration: 1.1, ease: 'power3.out', stagger: 0.08, clearProps: 'transform', onComplete: restoreTrans(batch) }); }
    });
    setTimeout(function () {
      picked.forEach(function (el) { if (el.getBoundingClientRect().top < window.innerHeight && getComputedStyle(el).opacity === '0') G.to(el, { opacity: 1, y: 0, duration: 0.6 }); });
    }, 4000);
  }

  /* ---- Odometer counters ---- */
  function initOdometers() {
    $$('.odo').forEach(function (o) {
      var val = parseInt(o.dataset.value || '0', 10) || 0;
      var str = val.toLocaleString('id-ID');
      if (reduce) { o.classList.add('odo-ready'); return; }
      var cols = [];
      var frag = document.createElement('span');
      frag.className = 'odo-reel';
      frag.style.display = 'inline-flex';
      frag.setAttribute('aria-hidden', 'true');
      str.split('').forEach(function (ch) {
        if (!/\d/.test(ch)) { var s = document.createElement('span'); s.className = 'odo-sep'; s.textContent = ch; frag.appendChild(s); return; }
        var col = document.createElement('span');
        col.className = 'odo-col';
        var html = '';
        for (var i = 0; i < 20; i++) html += '<span>' + (i % 10) + '</span>';
        col.innerHTML = html;
        col.dataset.d = ch;
        frag.appendChild(col); cols.push(col);
      });
      ST.create({
        trigger: o, start: 'top 92%', once: true,
        onEnter: function () {
          o.querySelector('.odo-static').style.display = 'none';
          o.appendChild(frag);
          o.classList.add('odo-ready');
          cols.forEach(function (col, i) {
            var d = parseInt(col.dataset.d, 10);
            G.fromTo(col, { yPercent: 0 }, { yPercent: -(10 + d) * 5, duration: 1.5 + (cols.length - i) * 0.12, ease: 'power3.out', delay: i * 0.04 });
          });
        }
      });
    });
  }

  /* ---- Hero layered parallax ---- */
  function initHero() {
    var hero = document.querySelector('.hx-hero');
    if (!hero) return;
    var q = function (k) { return hero.querySelector('[data-hx="' + k + '"]'); };
    var copy = q('copy'), dio = q('diorama');
    if (!reduce) {
      $$('.hx-cloud-track', hero).forEach(function (t, i) { G.to(t, { xPercent: -50, duration: i === 0 ? 180 : 110, ease: 'none', repeat: -1 }); });
      if (copy) G.from(Array.prototype.slice.call(copy.children).filter(function (c) { return !c.hasAttribute('data-split'); }), { y: 26, opacity: 0, duration: 1.2, ease: 'power3.out', stagger: 0.1, delay: 0.35 });
      if (dio) G.from(dio, { y: 60, opacity: 0, duration: 1.6, ease: 'power3.out', delay: 0.4 });
      var H = function () { return hero.offsetHeight; };
      var layers = [['sky', 0.42], ['sun', 0.5], ['cloudsFar', 0.36], ['hills', 0.2], ['cloudsNear', 0.12], ['village', -0.02]];
      var tl = G.timeline({ scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true, invalidateOnRefresh: true,
        onUpdate: function (self) { window.dispatchEvent(new CustomEvent('hero:progress', { detail: self.progress })); } } });
      layers.forEach(function (l) { var el = q(l[0]); if (el) tl.to(el, { y: function () { return H() * l[1]; }, ease: 'none' }, 0); });
      var near = q('cloudsNear'); if (near) tl.to(near, { x: -80, ease: 'none' }, 0);
      if (copy) tl.to(copy, { y: function () { return H() * 0.16; }, opacity: 0.15, ease: 'none' }, 0);
      if (dio) tl.to(dio, { y: function () { return H() * 0.1; }, scale: 0.9, ease: 'none' }, 0);
    }
    // Tilt halus foto diorama (fallback sebelum/selain 3D)
    if (dio && finePointer && !reduce) {
      var still = dio.querySelector('.hx-diorama-still');
      hero.addEventListener('pointermove', function (e) {
        var r = hero.getBoundingClientRect();
        var nx = (e.clientX - r.left) / r.width - 0.5, ny = (e.clientY - r.top) / r.height - 0.5;
        window.dispatchEvent(new CustomEvent('hero:pointer', { detail: { x: nx, y: ny } }));
        if (still && !dio.classList.contains('is-3d')) G.to(still, { rotateY: nx * 10, rotateX: -ny * 8, x: nx * 14, transformPerspective: 1000, duration: 1.2, ease: 'power3.out' });
      });
    }
  }

  /* ---- Sambutan clip-path reveal ---- */
  function initEditorial() {
    if (reduce) return;
    $$('[data-clip-reveal]').forEach(function (el) {
      G.fromTo(el, { clipPath: 'inset(0% 100% 0% 0% round 28px)' }, { clipPath: 'inset(0% 0% 0% 0% round 28px)', duration: 1.5, ease: 'power3.inOut', scrollTrigger: { trigger: el, start: 'top 82%', once: true } });
      var body = el.querySelector('.tl-editorial-body');
      if (body) G.from(Array.prototype.slice.call(body.children).filter(function (c) { return !c.hasAttribute('data-split'); }), { x: 30, opacity: 0, duration: 1, ease: 'power3.out', stagger: 0.08, delay: 0.5, scrollTrigger: { trigger: el, start: 'top 82%', once: true } });
    });
  }

  /* ---- Alur pengajuan: SVG line draws with scroll ---- */
  function initFlow() {
    $$('[data-flow]').forEach(function (flow) {
      var paths = $$('.tl-flow-draw', flow), steps = $$('[data-step]', flow), trav = flow.querySelector('.tl-flow-traveler'), track = flow.querySelector('.tl-flow-track');
      if (reduce) { steps.forEach(function (s) { s.classList.add('is-active'); }); return; }
      paths.forEach(function (p) { p.style.strokeDasharray = '1'; p.style.strokeDashoffset = '1'; });
      ST.create({
        trigger: flow.querySelector('.tl-flow-track'), start: 'top 78%', end: 'bottom 45%', scrub: true,
        onUpdate: function (self) {
          var p = self.progress;
          paths.forEach(function (path) { path.style.strokeDashoffset = String(1 - p); });
          if (trav) trav.style.transform = 'translate3d(' + (p * track.clientWidth * 0.8).toFixed(1) + 'px,0,0)';
          steps.forEach(function (s, i) { s.classList.toggle('is-active', p >= (i / Math.max(1, steps.length - 1)) * 0.98 - 0.001 && (p > 0.01 || i === 0)); });
        }
      });
      G.from(steps, { y: 24, opacity: 0, duration: 1, ease: 'power3.out', stagger: 0.1, scrollTrigger: { trigger: flow, start: 'top 85%', once: true } });
    });
  }

  /* ---- Image parallax inside rounded frames ---- */
  function initParallax() {
    if (reduce) return;
    $$('[data-parallax-img]').forEach(function (img) {
      var frame = img.parentElement;
      G.fromTo(img, { yPercent: -5 }, { yPercent: 5, ease: 'none', scrollTrigger: { trigger: frame, start: 'top bottom', end: 'bottom top', scrub: true } });
    });
  }


  /* ---- Sketsa Nusantara: parallax berlapis + garis tergambar mengikuti scroll ---- */
  function initSketches() {
    var secs = $$('[data-sketch]');
    if (!secs.length) return;
    var me = document.querySelector('script[src*="js/motion.js"]');
    var base = me ? me.src.replace(/js\/motion\.js.*$/, 'img/ornament/sketch-') : '/assets/img/ornament/sketch-';
    var cache = {};
    var load = function (n) { return cache[n] || (cache[n] = fetch(base + n + '.svg').then(function (r) { return r.ok ? r.text() : ''; }).catch(function () { return ''; })); };
    var mobile = window.matchMedia('(max-width: 767.98px)').matches;
    secs.forEach(function (sec) {
      var names = sec.dataset.sketch.split(/\s+/);
      var left = sec.dataset.sketchPos === 'left';
      var layer = document.createElement('div');
      layer.className = 'sk-layer'; layer.setAttribute('aria-hidden', 'true');
      sec.insertBefore(layer, sec.firstChild);
      names.slice(0, 2).forEach(function (n, i) {
        load(n).then(function (svg) {
          if (!svg) return;
          var box = document.createElement('div');
          box.className = 'sk ' + (i ? 'sk-b ' : 'sk-a ') + ((i ? !left : left) ? 'sk-l' : 'sk-r') + (reduce ? '' : ' sk-draw');
          box.innerHTML = svg;
          layer.appendChild(box);
          if (reduce) return;
          var shapes = $$('path,circle,ellipse', box);
          shapes.forEach(function (s) { s.setAttribute('pathLength', '1'); });
          var depth = i ? 1.7 : 1;
          G.fromTo(box, { y: 70 * depth, x: i ? (left ? 18 : -18) : 0, rotation: i ? -3 : 0 },
            { y: -70 * depth, x: 0, rotation: i ? 3 : 0, ease: 'none', scrollTrigger: { trigger: sec, start: 'top bottom', end: 'bottom top', scrub: mobile ? true : 0.6 } });
          G.fromTo(shapes, { '--d': 1 }, { '--d': 0, ease: 'none', stagger: 0.04,
            scrollTrigger: { trigger: sec, start: 'top 85%', end: i ? 'top 25%' : 'center 55%', scrub: mobile ? true : 0.8 } });
        });
      });
    });
  }

  function initSocial() {
    var sec = document.querySelector('.social-showcase');
    if (!sec || reduce || !('IntersectionObserver' in window)) return;
    $$('.social-media-card', sec).forEach(function (c, i) { c.style.setProperty('--i', i); });
    sec.classList.add('soc-wait');
    var io = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { sec.classList.remove('soc-wait'); sec.classList.add('soc-in'); io.disconnect(); } }, { threshold: 0.2 });
    io.observe(sec);
  }

  /* ---- Cursor-reactive tilt ---- */
  function initTilt() {
    if (!finePointer || reduce) return;
    $$('[data-tilt]').forEach(function (el) {
      var max = parseFloat(el.dataset.tilt) || 6;
      el.addEventListener('pointermove', function (e) {
        var r = el.getBoundingClientRect();
        var nx = (e.clientX - r.left) / r.width - 0.5, ny = (e.clientY - r.top) / r.height - 0.5;
        G.to(el, { rotateY: nx * max * 2, rotateX: -ny * max * 2, y: -4, transformPerspective: 1000, duration: 0.6, ease: 'power3.out' });
      });
      el.addEventListener('pointerleave', function () { G.to(el, { rotateY: 0, rotateX: 0, y: 0, duration: 0.9, ease: 'power3.out' }); });
    });
  }

  /* ---- UMKM & Potensi filter with Flip layout transitions ---- */
  function initFilters() {
    $$('[data-filter-bar]').forEach(function (bar) {
      var grid = bar.parentElement.querySelector('[data-filter-grid]');
      if (!grid) return;
      var items = $$('[data-cat]', grid);
      $$('[data-filter]', bar).forEach(function (btn) {
        var f = btn.dataset.filter;
        if (f !== 'all' && !items.some(function (it) { return it.dataset.cat === f; })) btn.hidden = true;
        btn.addEventListener('click', function () {
          if (btn.classList.contains('active')) return;
          $$('[data-filter]', bar).forEach(function (b) { var on = b === btn; b.classList.toggle('active', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
          var Flip = window.Flip, state = null;
          if (G && Flip && !reduce) { G.set(items, { rotateX: 0, rotateY: 0, y: 0 }); state = Flip.getState(items); }
          items.forEach(function (it) { it.classList.toggle('is-hidden', !(f === 'all' || it.dataset.cat === f)); });
          if (state) {
            Flip.from(state, {
              duration: 0.75, ease: 'power3.inOut', absolute: true, scale: false,
              onEnter: function (els) { return G.fromTo(els, { opacity: 0, scale: 0.96 }, { opacity: 1, scale: 1, duration: 0.6, ease: 'power3.out' }); },
              onLeave: function (els) { return G.to(els, { opacity: 0, scale: 0.96, duration: 0.35, ease: 'power2.in' }); },
              onComplete: function () { G.set(items.filter(function (i) { return !i.classList.contains('is-hidden'); }), { clearProps: 'opacity,scale,transform' }); ST && ST.refresh(); }
            });
          } else if (ST) { ST.refresh(); }
        });
      });
    });
  }

  /* ---- FAQ accordion ---- */
  function initFaq() {
    var items = $$('.tl-acc-item');
    items.forEach(function (item) {
      var btn = item.querySelector('.tl-acc-btn'), panel = item.querySelector('.tl-acc-panel');
      btn.addEventListener('click', function () {
        var open = !item.classList.contains('is-open');
        items.forEach(function (o) { if (o !== item && o.classList.contains('is-open')) toggle(o, false); });
        toggle(item, open);
      });
    });
    function toggle(item, open) {
      var btn = item.querySelector('.tl-acc-btn'), panel = item.querySelector('.tl-acc-panel');
      item.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (!G || reduce) { panel.hidden = !open; return; }
      if (open) {
        panel.hidden = false;
        G.fromTo(panel, { height: 0, opacity: 0 }, { height: 'auto', opacity: 1, duration: 0.55, ease: 'power3.out', onComplete: function () { ST && ST.refresh(); } });
      } else {
        G.to(panel, { height: 0, opacity: 0, duration: 0.4, ease: 'power3.inOut', onComplete: function () { panel.hidden = true; ST && ST.refresh(); } });
      }
    }
  }
})();
