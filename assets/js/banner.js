/* Banner galeri sinematik: zoom halus + reveal, autoplay yang menunggu foto siap, hemat data di koneksi lambat. */
(function () {
  'use strict';
  var root = document.querySelector('[data-nf]');
  if (!root) return;
  var slides = root.querySelectorAll('[data-nf-slide]');
  var infos = root.querySelectorAll('[data-nf-info]');
  var thumbs = root.querySelectorAll('[data-nf-thumb]');
  var count = root.querySelector('[data-nf-count]');
  var toggle = root.querySelector('[data-nf-toggle]');
  var toggleIc = root.querySelector('[data-nf-toggle-ic]');
  var row = root.querySelector('[data-nf-row]');
  var stage = root.querySelector('[data-nf-stage]');
  var n = slides.length;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  var slow = !!(conn && (conn.saveData || /2g|3g/.test(conn.effectiveType || '')));
  var DUR = slow ? 9000 : 7000;
  var MAX_WAIT = 6000;
  var cur = 0, timer = 0, startAt = 0, left = DUR, paused = reduce, visible = false, leaveTimer = 0;

  root.style.setProperty('--nf-dur', DUR + 'ms');
  function pad(x) { return (x < 10 ? '0' : '') + x; }
  function imgOf(i) { return slides[i] && slides[i].querySelector('img'); }
  function ready(img) { return img && img.complete && img.naturalWidth > 0; }

  function markLoaded(img) {
    if (!img) return;
    if (ready(img)) { img.classList.add('is-loaded'); return; }
    img.addEventListener('load', function () { img.classList.add('is-loaded'); }, { once: true });
    img.addEventListener('error', function () { img.classList.add('is-loaded'); }, { once: true });
  }

  function load(i) {
    var img = imgOf(i);
    if (!img) return;
    if (img.dataset.src || img.dataset.srcset) {
      // Koneksi lambat: pakai versi kecil saja (±60–120 KB) agar slide tetap cepat tampil.
      if (img.dataset.srcset && !slow) img.srcset = img.dataset.srcset;
      if (slow) img.removeAttribute('srcset');
      img.src = img.dataset.src;
      img.removeAttribute('data-src'); img.removeAttribute('data-srcset');
    }
    if (img.loading === 'lazy') img.loading = 'eager';
    markLoaded(img);
  }
  if (slow) { var first = imgOf(0); if (first) { first.removeAttribute('srcset'); } }
  markLoaded(imgOf(0));

  function whenReady(i, cb) {
    var img = imgOf(i);
    load(i);
    if (!img || ready(img)) { cb(); return; }
    root.classList.add('is-loading');
    var done = false;
    function fin() { if (done) return; done = true; clearTimeout(t); root.classList.remove('is-loading'); cb(); }
    var t = setTimeout(fin, MAX_WAIT);
    img.addEventListener('load', fin, { once: true });
    img.addEventListener('error', fin, { once: true });
  }

  function restartTick() {
    root.classList.remove('is-ticking');
    void root.offsetWidth;
    root.classList.add('is-ticking');
  }

  function go(i, user) {
    if (n < 2) return;
    i = (i + n) % n;
    if (i === cur) return;
    load(i); load((i + 1) % n);
    var prev = slides[cur];
    clearTimeout(leaveTimer);
    Array.prototype.forEach.call(slides, function (s) { s.classList.remove('is-leaving'); });
    prev.classList.remove('is-active', 'is-enter'); prev.classList.add('is-leaving'); prev.setAttribute('aria-hidden', 'true');
    leaveTimer = setTimeout(function () { prev.classList.remove('is-leaving'); }, 1700);
    infos[cur].classList.remove('is-active'); infos[cur].hidden = true;
    if (thumbs[cur]) { thumbs[cur].classList.remove('is-active'); thumbs[cur].setAttribute('aria-selected', 'false'); }
    cur = i;
    var next = slides[cur];
    next.classList.add('is-active');
    if (!reduce) { next.classList.remove('is-enter'); void next.offsetWidth; next.classList.add('is-enter'); }
    next.removeAttribute('aria-hidden');
    infos[cur].hidden = false;
    void infos[cur].offsetWidth;
    infos[cur].classList.add('is-active');
    if (thumbs[cur]) {
      thumbs[cur].classList.add('is-active'); thumbs[cur].setAttribute('aria-selected', 'true');
      if (row && user) {
        var t = thumbs[cur], target = t.offsetLeft - (row.clientWidth - t.clientWidth) / 2;
        row.scrollTo({ left: Math.max(0, target), behavior: reduce ? 'auto' : 'smooth' });
      }
    }
    if (count) count.textContent = pad(cur + 1);
    left = DUR; schedule(true);
  }

  function clear() { clearTimeout(timer); timer = 0; }
  function schedule(fresh) {
    clear();
    if (paused || !visible || document.hidden || n < 2) { root.classList.add('is-paused'); return; }
    root.classList.remove('is-paused');
    if (fresh || !root.classList.contains('is-ticking')) restartTick();
    startAt = Date.now();
    timer = setTimeout(function () {
      var target = (cur + 1) % n;
      whenReady(target, function () { if (!paused && visible) go(target); });
    }, left);
    // Muat slide berikutnya di belakang layar setelah slide aktif selesai dimuat.
    var curImg = imgOf(cur);
    if (ready(curImg)) load((cur + 1) % n);
    else if (curImg) curImg.addEventListener('load', function () { load((cur + 1) % n); }, { once: true });
  }
  function hold() { if (timer) { left = Math.max(400, left - (Date.now() - startAt)); } clear(); root.classList.add('is-paused'); }

  function setPaused(p) {
    paused = p;
    if (toggle) {
      toggleIc.innerHTML = p ? '<i class="bi bi-play-fill"></i>' : '<i class="bi bi-pause-fill"></i>';
      toggle.setAttribute('aria-label', p ? 'Putar slider' : 'Jeda slider');
    }
    p ? hold() : schedule();
  }

  var prevBtn = root.querySelector('[data-nf-prev]'), nextBtn = root.querySelector('[data-nf-next]');
  prevBtn && prevBtn.addEventListener('click', function () { go(cur - 1, true); });
  nextBtn && nextBtn.addEventListener('click', function () { go(cur + 1, true); });
  toggle && toggle.addEventListener('click', function () { setPaused(!paused); });
  Array.prototype.forEach.call(thumbs, function (t) {
    t.addEventListener('click', function () { go(parseInt(t.dataset.nfThumb, 10), true); });
    t.addEventListener('mouseenter', function () { load(parseInt(t.dataset.nfThumb, 10)); }, { passive: true });
  });

  if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    stage.addEventListener('mouseenter', function () { if (!paused) hold(); });
    stage.addEventListener('mouseleave', function () {
      stage.style.setProperty('--mx', 0); stage.style.setProperty('--my', 0);
      if (!paused) schedule();
    });
    if (!reduce) {
      var raf = 0;
      stage.addEventListener('mousemove', function (e) {
        if (raf) return;
        raf = requestAnimationFrame(function () {
          raf = 0;
          var r = stage.getBoundingClientRect();
          stage.style.setProperty('--mx', ((e.clientX - r.left) / r.width - .5).toFixed(3));
          stage.style.setProperty('--my', ((e.clientY - r.top) / r.height - .5).toFixed(3));
        });
      }, { passive: true });
    }
  }
  var sx = 0, sy = 0, tracking = false;
  stage.addEventListener('touchstart', function (e) { var p = e.touches[0]; sx = p.clientX; sy = p.clientY; tracking = true; }, { passive: true });
  stage.addEventListener('touchend', function (e) {
    if (!tracking) return; tracking = false;
    var p = e.changedTouches[0], dx = p.clientX - sx, dy = p.clientY - sy;
    if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.4) go(cur + (dx < 0 ? 1 : -1), true);
  }, { passive: true });
  root.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') go(cur + 1, true);
    else if (e.key === 'ArrowLeft') go(cur - 1, true);
  });
  document.addEventListener('visibilitychange', function () { document.hidden ? hold() : schedule(); });

  if ('IntersectionObserver' in window) {
    // Mulai memuat foto pertama sedikit sebelum banner masuk layar.
    new IntersectionObserver(function (en, ob) {
      if (en[0].isIntersecting) { load(0); ob.disconnect(); }
    }, { rootMargin: '600px 0px' }).observe(root);
    new IntersectionObserver(function (en) {
      visible = en[0].isIntersecting;
      if (visible) { root.classList.add('is-inview'); schedule(); } else hold();
    }, { threshold: 0.25 }).observe(root);
  } else { visible = true; root.classList.add('is-inview'); load(0); schedule(); }

  if (paused && toggle) setPaused(true);
})();
