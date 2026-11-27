/* ============ SISTEM INFORMASI DESA - Public JS ============ */
(function () {
  'use strict';
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  function afterFirstInteraction(callback) {
    if (!window.DESA || DESA.lazyWidgets === false) {
      callback();
      return;
    }
    let done = false;
    const run = () => {
      if (done) return;
      done = true;
      ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((eventName) => {
        window.removeEventListener(eventName, run);
      });
      callback();
    };
    ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((eventName) => {
      window.addEventListener(eventName, run, { once: true, passive: eventName !== 'keydown' });
    });
  }

  /* ====== Sinkronisasi versi kode (diatur admin: Pengaturan → Fitur → Reset Cache) ======
     Jika admin menaikkan versi, perangkat pengunjung otomatis: bersihkan cache PWA,
     perbarui service worker, dan reset penyimpanan lokal lama agar kode lama & baru tidak bentrok. */
  try {
    const ver = String((window.DESA && window.DESA.assetVer) || '1');
    if (localStorage.getItem('desa_cache_ver') !== ver) {
      Object.keys(localStorage).forEach((k) => {
        if (k.indexOf('desa_') === 0 && k !== 'desa_theme' && k !== 'desa_visitor_token') localStorage.removeItem(k);
      });
      localStorage.setItem('desa_cache_ver', ver);
      if (window.caches && caches.keys) caches.keys().then((keys) => keys.forEach((k) => caches.delete(k))).catch(() => {});
      if ('serviceWorker' in navigator) navigator.serviceWorker.getRegistrations().then((regs) => regs.forEach((r) => { r.update().catch(() => {}); })).catch(() => {});
    }
  } catch (_) { /* penyimpanan diblokir browser */ }

  /* Token perangkat 256-bit. Browser memakai Web Crypto; seed server CSPRNG menjadi
     fallback. Token mentah hanya tersimpan di perangkat, server menyimpan HMAC-nya.
     Cookie persisten menjadi cadangan bila localStorage sementara tidak tersedia. */
  const visitorToken = (() => {
    let token = '';
    const readCookie = (name) => {
      const prefix = name + '=';
      const part = document.cookie.split(';').map((v) => v.trim()).find((v) => v.indexOf(prefix) === 0);
      if (!part) return '';
      try { return decodeURIComponent(part.slice(prefix.length)); } catch (_) { return ''; }
    };
    const persist = (value) => {
      try { localStorage.setItem('desa_visitor_token', value); } catch (_) {
        try { sessionStorage.setItem('desa_visitor_token', value); } catch (_) {}
      }
      try {
        const secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = 'desa_visitor_token=' + encodeURIComponent(value) + '; Max-Age=315360000; Path=/; SameSite=Lax' + secure;
      } catch (_) {}
    };

    try { token = localStorage.getItem('desa_visitor_token') || ''; } catch (_) {}
    if (!/^[a-f0-9]{64}$/i.test(token)) token = readCookie('desa_visitor_token');
    if (!/^[a-f0-9]{64}$/i.test(token)) {
      try { token = sessionStorage.getItem('desa_visitor_token') || ''; } catch (_) {}
    }
    if (/^[a-f0-9]{64}$/i.test(token)) {
      token = token.toLowerCase();
      persist(token);
      return token;
    }

    try {
      const bytes = new Uint8Array(32);
      window.crypto.getRandomValues(bytes);
      token = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
    } catch (_) {
      token = String((window.DESA && DESA.liveChat && DESA.liveChat.visitorTokenSeed) || '');
    }
    if (!/^[a-f0-9]{64}$/i.test(token)) return '';
    token = token.toLowerCase();
    persist(token);
    return token;
  })();
  if (window.DESA) window.DESA.visitorToken = visitorToken;

  /* Heartbeat membuat daftar pengunjung real-time berbasis perangkat, bukan IP. */
  function sendDeviceHeartbeat() {
    if (!visitorToken || !window.DESA || !DESA.liveChat || !DESA.liveChat.enabled || document.hidden) return;
    let activeRoom = '';
    try { activeRoom = localStorage.getItem('desa_chat_room') || ''; } catch (_) {}
    fetch((DESA.baseUrl || '') + '/api/device-heartbeat', {
      method: 'POST',
      cache: 'no-store',
      keepalive: true,
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({
        visitor_token: visitorToken,
        room_id: activeRoom,
        path: location.pathname + location.search,
        title: document.title,
      }),
    }).catch(() => {});
  }
  if (visitorToken && window.DESA && DESA.liveChat && DESA.liveChat.enabled) {
    afterFirstInteraction(() => {
      sendDeviceHeartbeat();
      setInterval(sendDeviceHeartbeat, 30000);
      window.addEventListener('pageshow', sendDeviceHeartbeat);
      document.addEventListener('visibilitychange', () => { if (!document.hidden) sendDeviceHeartbeat(); });
    });
  }

  /* ====== Skeleton loading: sembunyikan saat halaman siap, tampilkan lagi saat pindah halaman ====== */
  const pageReady = () => document.documentElement.classList.add('page-ready');
  pageReady();
  if ($('#pageSkeleton')) {
    document.addEventListener('click', (e) => {
      const a = e.target.closest ? e.target.closest('a[href]') : null;
      if (!a || e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;
      if (a.target === '_blank' || a.hasAttribute('download') || a.getAttribute('data-bs-toggle') || a.classList.contains('glightbox')) return;
      const href = a.getAttribute('href') || '';
      if (!href || href.charAt(0) === '#' || /^(mailto:|tel:|javascript:)/i.test(href)) return;
      let u; try { u = new URL(a.href, location.href); } catch (_) { return; }
      if (u.origin !== location.origin) return;
      if (u.pathname === location.pathname && u.search === location.search && u.hash) return;
      if (u.pathname.indexOf('/uploads/') !== -1 || /\.(pdf|xlsx?|docx?|zip|png|jpe?g|webp|mp3|mp4|wav)$/i.test(u.pathname)) return;
      document.documentElement.classList.remove('page-ready');
      setTimeout(pageReady, 10000); // failsafe bila navigasi dibatalkan
    });
    window.addEventListener('pageshow', pageReady);
  }

  /* AOS */
  if (window.AOS) AOS.init({ duration: 700, once: true, offset: 60 });

  /* Lightbox — gunakan tinggi viewport visual agar seluruh foto tetap terlihat
     saat address bar/browser HP berubah ukuran atau perangkat diputar. */
  const syncPhotoViewerViewport = () => {
    const viewportHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;
    if (viewportHeight > 0) {
      document.documentElement.style.setProperty('--photo-viewer-height', `${Math.round(viewportHeight)}px`);
    }
  };
  syncPhotoViewerViewport();
  window.addEventListener('resize', syncPhotoViewerViewport, { passive: true });
  window.addEventListener('orientationchange', () => {
    window.setTimeout(syncPhotoViewerViewport, 80);
    window.setTimeout(syncPhotoViewerViewport, 280);
  }, { passive: true });
  if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', syncPhotoViewerViewport, { passive: true });
  }

  if (window.GLightbox) {
    const photoLightbox = GLightbox({
      selector: '.glightbox',
      touchNavigation: false,
      openEffect: 'fade',
      closeEffect: 'fade',
      slideEffect: 'fade',
      zoomable: false,
      draggable: false,
      preload: true,
      moreLength: 0
    });
    photoLightbox.on('open', () => {
      syncPhotoViewerViewport();
      window.requestAnimationFrame(syncPhotoViewerViewport);
    });
    photoLightbox.on('slide_after_load', () => {
      syncPhotoViewerViewport();
    });
  }

  /* Pindahkan modal ke body agar tidak terjebak parent ber-transform/overflow */
  $$('.modal').forEach((m) => { if (m.parentElement !== document.body) document.body.appendChild(m); });

  /* Proteksi Screenshot (diatur admin lewat Pengaturan → Fitur Website)
     Mode 'ketat': veil buram + block PrintScreen + tutup layar saat blur + log upaya
     Mode 'normal': HANYA log upaya screenshot (tidak buramkan, tidak block) — cocok untuk transparansi tanpa mengganggu UX */
  if (window.DESA && window.DESA.screenshotProtection) {
    document.body.classList.add('ss-protect');
    const mode = (window.DESA.screenshotMode || 'ketat');

    // Fungsi laporan upaya screenshot ke backend (aktif di kedua mode)
    let lastLog = 0;
    const logAttempt = (method) => {
      const now = Date.now();
      if (now - lastLog < 1500) return;
      lastLog = now;
      try {
        const url = ((window.DESA && DESA.baseUrl) ? DESA.baseUrl.replace(/\/$/, '') : '') + '/api/screenshot-attempt';
        let activeRoom = '';
        try { activeRoom = localStorage.getItem('desa_chat_room') || ''; } catch (_) {}
        const body = JSON.stringify({
          method: method,
          path: location.pathname + location.search,
          visitor_token: visitorToken,
          room_id: activeRoom
        });
        if (navigator.sendBeacon) navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
        else fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true }).catch(() => {});
      } catch (_) {}
    };

    // Mode NORMAL: cukup log upaya, tidak ada veil/block, lalu skip handler ketat
    if (mode === 'normal') {
      document.addEventListener('keydown', (e) => {
        if (e.key === 'PrintScreen') logAttempt('PrintScreen');
        else if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) logAttempt('Print');
        else if (e.shiftKey && e.metaKey && ['s','S','3','4','5'].includes(e.key)) logAttempt('Cmd+Shift+' + e.key);
        else if (e.key === 'Meta') logAttempt('Meta/Windows key');
      });
      window.addEventListener('beforeprint', () => logAttempt('Print'));
      let blurAt = 0;
      window.addEventListener('blur', () => { blurAt = Date.now(); });
      window.addEventListener('focus', () => {
        if (blurAt && Date.now() - blurAt < 1500) logAttempt('Quick-blur (' + (Date.now() - blurAt) + 'ms)');
        blurAt = 0;
      });
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) blurAt = Date.now();
        else {
          if (blurAt && Date.now() - blurAt < 1500) logAttempt('Quick-visibility (' + (Date.now() - blurAt) + 'ms)');
          blurAt = 0;
        }
      });
    } else {
      // Mode KETAT (default): veil + block full
      const ov = document.createElement('div');
      ov.className = 'ss-overlay';
      ov.setAttribute('data-testid', 'screenshot-overlay');
      ov.innerHTML = '<div><i class="bi bi-shield-lock" style="font-size:2.6rem"></i><br>Halaman ini bersifat pribadi.<br><small style="font-weight:400;opacity:.7">Screenshot & pencetakan tidak diizinkan.</small></div>';
      document.body.appendChild(ov);

    let blackBlob = null;
    const makeBlackImage = () => new Promise((res) => {
      const c = document.createElement('canvas');
      c.width = 1280; c.height = 720;
      const x = c.getContext('2d');
      x.fillStyle = '#000'; x.fillRect(0, 0, 1280, 720);
      x.fillStyle = '#fff'; x.font = '600 30px sans-serif'; x.textAlign = 'center';
      x.fillText('Halaman ini bersifat pribadi', 640, 340);
      x.font = '20px sans-serif'; x.globalAlpha = 0.7;
      x.fillText('Screenshot tidak diizinkan', 640, 385);
      c.toBlob((b) => res(b), 'image/png');
    });
    const wipeClipboard = async () => {
      try {
        if (!blackBlob) blackBlob = await makeBlackImage();
        if (navigator.clipboard && window.ClipboardItem && blackBlob) {
          await navigator.clipboard.write([new ClipboardItem({ 'image/png': blackBlob })]);
        } else if (navigator.clipboard) {
          await navigator.clipboard.writeText('Halaman ini bersifat pribadi.');
        }
      } catch (_) {
        try { await navigator.clipboard.writeText('Halaman ini bersifat pribadi.'); } catch (__) {}
      }
    };

    const setPrivate = (on) => {
      document.body.classList.toggle('ss-private', on);
      ov.classList.toggle('show', on);
    };
    const flashOv = (ms) => { ov.classList.add('show'); setTimeout(() => { if (!document.body.classList.contains('ss-private')) ov.classList.remove('show'); }, ms || 1400); };

    /* PrintScreen: timpa hasil di clipboard dengan gambar hitam (handler keyup digabung di bawah) */
    document.addEventListener('keydown', (e) => {
      if (e.key === 'PrintScreen') { e.preventDefault(); wipeClipboard(); flashOv(1600); logAttempt('PrintScreen'); }
      if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) { e.preventDefault(); flashOv(1200); logAttempt('Print'); }
      if (e.shiftKey && e.metaKey && ['s', 'S', '3', '4', '5'].includes(e.key)) { e.preventDefault(); wipeClipboard(); flashOv(1600); logAttempt('Cmd+Shift+' + e.key); }
      if (e.ctrlKey && e.shiftKey && (e.key === 'S' || e.key === 's')) { logAttempt('Ctrl+Shift+S'); }
      if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'PrintScreen' || e.key === 'p')) { logAttempt('Ctrl+Shift+PrintScreen'); }
    });

    /* Tombol OS (Windows/Cmd): Snipping Tool dipanggil via Win+Shift+S — layar langsung ditutup
       begitu tombol Windows ditekan, sehingga hasil snip berupa layar hitam.
       holdUntil menahan mode privat agar tidak dilepas oleh penjaga polling fokus di bawah. */
    let metaTimer = null, holdUntil = 0;
    const metaRelease = (ms) => {
      clearTimeout(metaTimer);
      holdUntil = Date.now() + ms;
      metaTimer = setTimeout(() => {
        holdUntil = 0;
        let f = true; try { f = document.hasFocus(); } catch (_) {}
        if (f && !document.hidden && !evtBlur) setPrivate(false);
      }, ms);
    };
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Meta' || (e.metaKey && e.key !== 'Shift' && e.key !== 'Control' && e.key !== 'Alt')) {
        setPrivate(true); wipeClipboard(); metaRelease(2600); logAttempt('Meta/Windows key');
      }
    });
    document.addEventListener('keyup', (e) => {
      if (e.key === 'Meta') metaRelease(400);
      if (e.key === 'PrintScreen') { wipeClipboard(); flashOv(1600); }
    });

    /* Mode privasi: layar ditutup penuh saat window tidak fokus (snipping tool, pindah aplikasi, app-switcher HP)
       Blur + focus yang sangat cepat (< 1500ms) sering merupakan indikasi screenshot dgn Snipping Tool desktop
       atau Control Center iOS/Android — dicatat sebagai upaya. */
    let blurAt = 0, evtBlur = false;
    window.addEventListener('blur', () => { evtBlur = true; blurAt = Date.now(); setPrivate(true); });
    window.addEventListener('focus', () => {
      evtBlur = false;
      setPrivate(false);
      if (blurAt && Date.now() - blurAt < 6000) wipeClipboard();
      if (blurAt && Date.now() - blurAt < 1500) logAttempt('Quick-blur (' + (Date.now() - blurAt) + 'ms)');
      blurAt = 0;
    });
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) { evtBlur = true; blurAt = Date.now(); setPrivate(true); }
      else {
        if (blurAt && Date.now() - blurAt < 1500) logAttempt('Quick-visibility (' + (Date.now() - blurAt) + 'ms)');
        evtBlur = false; setPrivate(false);
      }
    });
    /* Penjaga tambahan: cek fokus tiap 300ms tanpa melawan event blur/focus */
    setInterval(() => {
      let focused = true;
      try { focused = document.hasFocus(); } catch (_) { return; }
      if (!focused && !document.hidden) {
        if (!document.body.classList.contains('ss-private')) { blurAt = Date.now(); setPrivate(true); }
      } else if (focused && !evtBlur && Date.now() > holdUntil && !document.hidden && document.body.classList.contains('ss-private')) {
        setPrivate(false);
      }
    }, 300);
    window.addEventListener('beforeprint', () => { flashOv(2000); logAttempt('Print'); });

    /* MODE KETAT: konten diburamkan permanen; hanya area sekitar kursor (desktop) atau
       titik sentuh (HP, selama disentuh & bergerak) yang terlihat jelas. Screenshot apa pun
       hanya merekam halaman buram. Di mobile spotlight jauh lebih kecil & memudar bila
       jari diam ≥900ms — memaksa user aktif menggeser jari untuk membaca. */
    if ((window.DESA.screenshotMode || 'ketat') === 'ketat') {
      document.body.classList.add('ss-strict');
      const veil = document.createElement('div');
      veil.className = 'ss-veil';
      veil.setAttribute('data-testid', 'privacy-veil');
      veil.innerHTML = '<div class="ss-veil-hint"><i class="bi bi-shield-lock"></i> Halaman ini dilindungi — <span class="d-none d-md-inline">gerakkan kursor</span><span class="d-md-none">geser jari</span> di layar untuk membaca</div>';
      document.body.appendChild(veil);
      let idleTimer = 0;
      const setSpot = (x, y) => {
        veil.style.setProperty('--sx', x + 'px');
        veil.style.setProperty('--sy', y + 'px');
        veil.classList.add('lit');
        // Reset idle timer — spotlight otomatis padam bila tidak ada gerakan
        clearTimeout(idleTimer);
        idleTimer = setTimeout(unlit, 900);
      };
      const unlit = () => { veil.classList.remove('lit'); clearTimeout(idleTimer); };
      window.addEventListener('pointermove', (e) => setSpot(e.clientX, e.clientY), { passive: true });
      window.addEventListener('pointerdown', (e) => setSpot(e.clientX, e.clientY), { passive: true });
      const touchOnly = window.matchMedia && matchMedia('(hover: none)').matches;
      if (touchOnly) {
        window.addEventListener('pointerup', unlit, { passive: true });
        window.addEventListener('pointercancel', unlit, { passive: true });
        window.addEventListener('touchmove', (e) => { const t = e.touches[0]; if (t) setSpot(t.clientX, t.clientY); }, { passive: true });
        window.addEventListener('touchend', unlit, { passive: true });
        window.addEventListener('touchcancel', unlit, { passive: true });
        // Antisipasi user mengaktifkan screen recording / control center: setiap perubahan orientasi/scroll cepat → padamkan
        window.addEventListener('orientationchange', unlit, { passive: true });
        // Jika sedang menuju keluar (app-switcher iOS/Android), tutup total sebelum snapshot
        window.addEventListener('pagehide', () => { unlit(); setPrivate(true); });
      }
      document.documentElement.addEventListener('mouseleave', unlit);
      window.addEventListener('blur', unlit);
      /* blokir klik kanan, seleksi, drag, long-press save image di mode ketat */
      document.addEventListener('contextmenu', (e) => { e.preventDefault(); flashOv(900); });
      document.addEventListener('dragstart', (e) => e.preventDefault());
      // Cegah long-press "Save Image" di iOS/Android untuk gambar (tidak block tap/click)
      document.addEventListener('touchstart', (e) => {
        const t = e.target; if (t && t.tagName === 'IMG') { try { e.preventDefault(); } catch (_) {} }
      }, { passive: false, capture: true });
    }
    }  // tutup else block (mode ketat)
  }

  /* Theme toggle */
  const themeBtn = $('#themeToggle');
  const setThemeIcon = () => {
    if (!themeBtn) return;
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';
    themeBtn.innerHTML = dark ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
  };
  setThemeIcon();
  themeBtn && themeBtn.addEventListener('click', () => {
    const cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', cur);
    localStorage.setItem('desa_theme', cur);
    setThemeIcon();
  });

  /* Navbar scroll + back-to-top */
  const nav = $('#siteNavbar'), btt = $('#backToTop');
  window.addEventListener('scroll', () => {
    const y = window.scrollY;
    nav && nav.classList.toggle('scrolled', y > 30);
    btt && btt.classList.toggle('show', y > 400);
    const heroBg = $('.hero .hero-bg-img');
    if (heroBg && y < window.innerHeight) heroBg.style.transform = 'scale(1.05) translateY(' + y * 0.25 + 'px)';
  }, { passive: true });
  btt && btt.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  /* Animated counters */
  const counters = $$('.counter');
  if (counters.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        io.unobserve(en.target);
        const el = en.target, target = parseInt(el.dataset.count || '0', 10);
        const dur = 1400, t0 = performance.now();
        const step = (t) => {
          const p = Math.min((t - t0) / dur, 1);
          el.textContent = Math.floor(target * (1 - Math.pow(1 - p, 3))).toLocaleString('id-ID');
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      });
    }, { threshold: 0.4 });
    counters.forEach((c) => io.observe(c));
  }

  /* Photo protection */
  if (window.DESA && DESA.photoProtection) {
    document.addEventListener('contextmenu', (e) => {
      if (e.target.closest('.protected-img, .official-photo')) e.preventDefault();
    });
    document.addEventListener('dragstart', (e) => {
      if (e.target.closest('.protected-img, .official-photo')) e.preventDefault();
    });
  }

  /* ====== PWA: service worker + tombol pasang aplikasi ====== */
  const allowPwaInstallUi = () => (
    window.DESA &&
    DESA.pwaEnabled !== false &&
    window.DESA_ADMIN !== true &&
    !(document.body && document.body.classList.contains('admin-body'))
  );
  if ('serviceWorker' in navigator && window.DESA && allowPwaInstallUi()) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(DESA.baseUrl + '/sw.js?v=' + encodeURIComponent((window.DESA && DESA.assetVer) || '1')).catch(() => {});
    });
  }
  function reflowPwaChips() {
    const chips = Array.from(document.querySelectorAll('.pwa-install:not(.pwa-out)'));
    const mobile = window.matchMedia('(max-width:480px)').matches;
    const baseBottom = document.body.classList.contains('has-feedback') ? (mobile ? 78 : 88) : (mobile ? 16 : 20);
    chips.forEach((chip, index) => { chip.style.bottom = (baseBottom + (index * 44)) + 'px'; });
  }
  const pwaObserver = new MutationObserver(reflowPwaChips);
  if (document.body) pwaObserver.observe(document.body, { childList: true });
  window.addEventListener('resize', reflowPwaChips, { passive: true });
  let installEvt = null;
  window.addEventListener('beforeinstallprompt', (e) => {
    if (!allowPwaInstallUi()) {
      e.preventDefault();
      installEvt = null;
      const staleButton = $('#pwaInstallBtn');
      if (staleButton) staleButton.remove();
      return;
    }
    e.preventDefault();
    installEvt = e;
    if ($('#pwaInstallBtn') || localStorage.getItem('desa_pwa_dismissed')) return;
    const box = document.createElement('div');
    box.id = 'pwaInstallBtn';
    box.className = 'pwa-install';
    box.setAttribute('data-testid', 'pwa-install-btn');
    box.innerHTML = '<button type="button" class="pwa-go" title="Pasang aplikasi ke perangkat"><i class="bi bi-phone"></i><span>Pasang</span></button><button type="button" class="pwa-x" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>';
    document.body.appendChild(box);
    autoHide(box, 'desa_pwa_dismissed', 8000);
    $('.pwa-go', box).addEventListener('click', async () => {
      if (!installEvt) return;
      installEvt.prompt();
      try { await installEvt.userChoice; } catch (_) {}
      installEvt = null; box.remove();
    });
    $('.pwa-x', box).addEventListener('click', () => { localStorage.setItem('desa_pwa_dismissed', '1'); box.remove(); });
  });
  window.addEventListener('appinstalled', () => { const b = $('#pwaInstallBtn'); b && b.remove(); });

  /* iOS Safari tidak memicu beforeinstallprompt — tampilkan chip manual dgn tap "Bagikan → Ke Layar Utama".
     Cek: iPhone/iPad UA + navigator.standalone === false → show chip. */
  (function () {
    if (!allowPwaInstallUi()) {
      const staleButton = $('#pwaInstallBtn');
      if (staleButton) staleButton.remove();
      return;
    }
    if (localStorage.getItem('desa_pwa_dismissed')) return;
    const ua = navigator.userAgent || '';
    const isIOS = /iPhone|iPad|iPod/i.test(ua) && !/CriOS|FxiOS/.test(ua);
    if (!isIOS) return;
    if (window.navigator.standalone === true) return; // sudah dipasang
    if ($('#pwaInstallBtn')) return;
    // Tunggu 2 detik supaya tidak muncul terlalu cepat
    setTimeout(function () {
      if (!allowPwaInstallUi()) return;
      if ($('#pwaInstallBtn') || localStorage.getItem('desa_pwa_dismissed')) return;
      const box = document.createElement('div');
      box.id = 'pwaInstallBtn'; box.className = 'pwa-install';
      box.setAttribute('data-testid', 'pwa-install-btn');
      box.innerHTML = '<button type="button" class="pwa-go" title="Tap Bagikan lalu Tambahkan ke Layar Utama"><i class="bi bi-phone"></i><span>Pasang</span></button><button type="button" class="pwa-x" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>';
      document.body.appendChild(box);
      autoHide(box, 'desa_pwa_dismissed', 8000);
      $('.pwa-go', box).addEventListener('click', () => {
        alert('Untuk memasang aplikasi:\n\n1. Tap tombol Bagikan (📤) di bawah/atas Safari\n2. Pilih "Tambahkan ke Layar Utama"\n3. Tap "Tambah"');
      });
      $('.pwa-x', box).addEventListener('click', () => { localStorage.setItem('desa_pwa_dismissed', '1'); box.remove(); });
    }, 2000);
  })();

  /* ====== Notifikasi Web: lonceng + popup otomatis (diatur admin lewat Pengaturan → Notifikasi Web) ====== */
  (function () {
    const NW = window.DESA && window.DESA.webnotif;
    if (!NW || !NW.enabled || window.DESA_ADMIN) return;
    const KEY_SEEN = 'desa_nw_seen', KEY_TOAST = 'desa_nw_toast', KEY_IMP = 'desa_nw_imp';
    const load = (k) => { try { return JSON.parse(localStorage.getItem(k) || '[]'); } catch (_) { return []; } };
    const save = (k, a) => { try { localStorage.setItem(k, JSON.stringify(a.slice(-150))); } catch (_) {} };
    const esc = (s) => String(s || '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const ago = (s) => {
      const t = new Date(String(s || '').replace(' ', 'T'));
      if (isNaN(t)) return '';
      const d = Math.floor((Date.now() - t.getTime()) / 1000);
      if (d < 60) return 'baru saja';
      if (d < 3600) return Math.floor(d / 60) + ' menit lalu';
      if (d < 86400) return Math.floor(d / 3600) + ' jam lalu';
      if (d < 2592000) return Math.floor(d / 86400) + ' hari lalu';
      return t.toLocaleDateString('id-ID');
    };
    const ICO = { berita: ['bi-newspaper', 'nw-blue'], pengumuman: ['bi-megaphone-fill', 'nw-amber'], agenda: ['bi-calendar-event-fill', 'nw-green'] };

    const bellTrigger = $('#notifBell');
    if (bellTrigger) bellTrigger.classList.remove('d-none');
    afterFirstInteraction(() => {
      fetch(DESA.baseUrl + '/api/notifications', { cache: 'no-store' }).then((r) => r.json()).then((data) => {
      if (!data || !data.enabled || !Array.isArray(data.items) || !data.items.length) return;
      const items = data.items;
      const seen = load(KEY_SEEN);
      const unseen = items.filter((i) => seen.indexOf(i.id) === -1);

      /* --- Lonceng di navbar --- */
      const bell = $('#notifBell'), badge = $('#notifBadge');
      let panel = null;
      if (bell) {
        bell.classList.remove('d-none');
        if (unseen.length && badge) {
          badge.textContent = unseen.length > 9 ? '9+' : String(unseen.length);
          badge.hidden = false;
          bell.classList.add('has-new');
        }
        bell.addEventListener('click', (e) => { e.stopPropagation(); panel ? closePanel() : openPanel(); });
      }
      function itemHtml(i) {
        const ic = ICO[i.type] || ICO.berita;
        return '<a class="notif-item' + (seen.indexOf(i.id) === -1 ? ' unread' : '') + (i.important ? ' imp' : '') + '" href="' + esc(i.url) + '" data-testid="notif-item">' +
          '<span class="notif-ico ' + (i.important ? 'nw-red' : ic[1]) + '"><i class="bi ' + (i.important ? 'bi-exclamation-circle-fill' : ic[0]) + '"></i></span>' +
          '<span class="notif-txt"><strong>' + esc(i.title) + '</strong>' +
          (i.excerpt ? '<small>' + esc(i.excerpt) + '</small>' : '') +
          '<em>' + (i.important ? '<b>PENTING</b> \u2022 ' : '') + ago(i.time) + '</em></span></a>';
      }
      function openPanel() {
        panel = document.createElement('div');
        panel.className = 'notif-panel';
        panel.setAttribute('data-testid', 'notif-panel');
        panel.innerHTML = '<div class="notif-head"><span><i class="bi bi-bell-fill me-2"></i>Notifikasi Desa</span>' +
          (unseen.length ? '<span class="notif-new-count">' + unseen.length + ' baru</span>' : '') + '</div>' +
          '<div class="notif-list">' + items.map(itemHtml).join('') + '</div>';
        document.body.appendChild(panel);
        if (bell && window.innerWidth > 576) {
          const r = bell.getBoundingClientRect();
          panel.style.top = (r.bottom + 10) + 'px';
          panel.style.right = Math.max(8, window.innerWidth - r.right - 8) + 'px';
        }
        requestAnimationFrame(() => panel.classList.add('open'));
        save(KEY_SEEN, seen.concat(unseen.map((i) => i.id)));
        if (badge) badge.hidden = true;
        bell && bell.classList.remove('has-new');
        setTimeout(() => document.addEventListener('click', onDocClick), 0);
      }
      function onDocClick(e) { if (panel && !panel.contains(e.target)) closePanel(); }
      function closePanel() {
        document.removeEventListener('click', onDocClick);
        if (panel) { const p = panel; panel = null; p.classList.remove('open'); setTimeout(() => p.remove(), 220); }
      }

      /* --- Popup tengah layar untuk pengumuman PENTING (sekali per pengunjung) --- */
      if (data.popupImportant) {
        const dismissed = load(KEY_IMP);
        const imp = items.find((i) => i.important && dismissed.indexOf(i.id) === -1);
        if (imp) {
          const bg = document.createElement('div');
          bg.className = 'nw-modal-bg';
          bg.setAttribute('data-testid', 'notif-important-modal');
          bg.innerHTML = '<div class="nw-modal"><div class="nw-modal-ico"><i class="bi bi-megaphone-fill"></i></div>' +
            '<span class="nw-modal-tag">Pengumuman Penting</span>' +
            '<h5>' + esc(imp.title) + '</h5>' +
            (imp.excerpt ? '<p>' + esc(imp.excerpt) + '\u2026</p>' : '') +
            '<div class="nw-modal-btns"><a class="btn btn-primary btn-sm px-3" href="' + esc(imp.url) + '" data-testid="notif-important-read">Baca Selengkapnya</a>' +
            '<button type="button" class="btn btn-outline-secondary btn-sm px-3" data-testid="notif-important-close">Tutup</button></div></div>';
          document.body.appendChild(bg);
          requestAnimationFrame(() => bg.classList.add('show'));
          const close = () => { bg.classList.remove('show'); setTimeout(() => bg.remove(), 250); };
          bg.querySelector('[data-testid="notif-important-close"]').addEventListener('click', close);
          bg.addEventListener('click', (e) => { if (e.target === bg) close(); });
          save(KEY_IMP, dismissed.concat([imp.id]));
        }
      }

      /* --- Toast kecil otomatis untuk info baru (sekali per item per pengunjung) --- */
      if (data.popup) {
        const toasted = load(KEY_TOAST);
        const fresh = unseen.filter((i) => !i.important && toasted.indexOf(i.id) === -1).slice(0, 3);
        if (fresh.length) {
          let wrap = $('#nwToasts');
          if (!wrap) { wrap = document.createElement('div'); wrap.id = 'nwToasts'; document.body.appendChild(wrap); }
          fresh.forEach((i, idx) => setTimeout(() => {
            const ic = ICO[i.type] || ICO.berita;
            const t = document.createElement('div');
            t.className = 'notif-toast';
            t.setAttribute('data-testid', 'notif-toast');
            t.innerHTML = '<span class="notif-ico ' + ic[1] + '"><i class="bi ' + ic[0] + '"></i></span>' +
              '<span class="notif-txt"><strong>' + esc(i.title) + '</strong>' + (i.excerpt ? '<small>' + esc(i.excerpt) + '</small>' : '') + '</span>' +
              '<button type="button" class="notif-toast-x" aria-label="Tutup"><i class="bi bi-x-lg"></i></button><span class="notif-toast-bar"></span>';
            wrap.appendChild(t);
            requestAnimationFrame(() => t.classList.add('show'));
            const kill = () => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); };
            t.querySelector('.notif-toast-x').addEventListener('click', (e) => { e.stopPropagation(); kill(); });
            t.addEventListener('click', () => { window.location.href = i.url; });
            setTimeout(kill, 9000);
          }, 900 + idx * 800));
          save(KEY_TOAST, toasted.concat(fresh.map((i) => i.id)));
        }
      }
      }).catch(() => {});
    });
  })();

  /* ====== Peta desa cantik (dipakai beranda, kontak, profil) ====== */
  window.desaMap = function (o) {
    const el = document.getElementById('villageMap');
    if (!el) return;
    if (!window.L) {
      const src = window.DESA_LEAFLET;
      if (!src) return;
      const boot = () => {
        if (window.__leafletLoading) return;
        window.__leafletLoading = true;
        const css = document.createElement('link'); css.rel = 'stylesheet'; css.href = src.css; document.head.appendChild(css);
        const js = document.createElement('script'); js.src = src.js; js.async = true;
        js.onload = () => window.desaMap(o);
        document.head.appendChild(js);
      };
      if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((en) => { if (en[0].isIntersecting) { io.disconnect(); boot(); } }, { rootMargin: '400px 0px' });
        io.observe(el);
      } else boot();
      return;
    }
    const locked = !!o.lockInteraction;
    const satellite = !!o.satellite;
    const hideMarker = !!o.hideMarker;
    const hideRadius = !!o.hideRadius;
    if (locked) el.classList.add('is-locked');
    if (satellite) el.classList.add('is-satellite');
    const map = L.map('villageMap', {
      scrollWheelZoom: false,
      zoomControl: false,
      dragging: !locked,
      touchZoom: !locked,
      doubleClickZoom: !locked,
      boxZoom: !locked,
      keyboard: !locked,
      tap: !locked,
    }).setView([o.lat, o.lng], o.zoom || 14);
    if (!locked) L.control.zoom({ position: 'bottomright' }).addTo(map);
    if (satellite) {
      L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri', maxZoom: 19, maxNativeZoom: 18,
      }).addTo(map);
      L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19, maxNativeZoom: 18, opacity: .9,
      }).addTo(map);
    } else {
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors', maxZoom: 19,
      }).addTo(map);
    }
    const popupHtml = '<div class="desa-map-pop"><strong>' + o.title + '</strong>' + (o.address ? '<span>' + o.address + '</span>' : '') +
      '<a href="https://www.google.com/maps?q=' + o.lat + ',' + o.lng + '" target="_blank" rel="noopener"><i class="bi bi-sign-turn-right"></i> Petunjuk Arah</a></div>';
    if (hideMarker) {
      L.popup({
        closeButton: false,
        autoClose: false,
        closeOnClick: false,
        autoPan: false,
      }).setLatLng([o.lat, o.lng]).setContent(popupHtml).openOn(map);
      map.panBy([0, -45], { animate: false });
    } else {
      const icon = L.divIcon({
        className: 'desa-pin-wrap',
        html: '<span class="desa-pin"><i class="bi bi-buildings"></i></span><span class="desa-pin-pulse"></span>',
        iconSize: [46, 46], iconAnchor: [23, 44], popupAnchor: [0, -44],
      });
      L.marker([o.lat, o.lng], { icon }).addTo(map)
        .bindPopup(popupHtml, { closeButton: false, autoClose: !locked, closeOnClick: !locked })
        .openPopup();
    }
    if (!hideRadius) {
      const primaryCol = getComputedStyle(document.documentElement).getPropertyValue('--c-primary').trim() || '#0f6b4e';
      L.circle([o.lat, o.lng], satellite
        ? { radius: o.radius || 350, color: '#ffffff', weight: 1.5, opacity: .75, fillColor: primaryCol, fillOpacity: .16 }
        : { radius: o.radius || 350, color: primaryCol, weight: 1.5, opacity: .5, fillOpacity: .08 }
      ).addTo(map);
    }
  };

  /* ====== Live Chat pengunjung ====== */
  (function () {
    const launcher = $('#lcLauncher'), popup = $('#lcPopup');
    if (!launcher || !popup) return;

    const base = (window.DESA && DESA.baseUrl ? DESA.baseUrl.replace(/\/$/, '') : '');
    const esc = (s) => String(s || '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const linkify = (s) => esc(s).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>').replace(/\n/g, '<br>');
    const intro = $('#lcIntro'), room = $('#lcRoom'), msgs = $('#lcMsgs');
    const waBtn = document.querySelector('.livechat .lc-wa'), badge = $('#lcBadge');
    let roomId = '', lastId = 0, pollTimer = null, inviteTimer = null;
    let inviteRequestPending = false, resumed = false, roomClosed = false, sendPending = false;
    let noticeAudio = null, badgeCount = 0;
    try { roomId = localStorage.getItem('desa_chat_room') || ''; } catch (_) {}

    launcher.setAttribute('aria-expanded', popup.hasAttribute('hidden') ? 'false' : 'true');
    popup.dataset.view = roomId ? 'room' : 'intro';

    function postJson(path, data) {
      return fetch(base + path, {
        method: 'POST',
        cache: 'no-store',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data || {}),
      }).then((r) => r.json());
    }

    function setBadge(count) {
      badgeCount = Math.max(0, Number(count) || 0);
      if (!badge) return;
      badge.textContent = badgeCount > 99 ? '99+' : String(badgeCount);
      badge.hidden = badgeCount < 1;
      launcher.classList.toggle('has-unread', badgeCount > 0);
    }

    function unlockNoticeSound() {
      try {
        noticeAudio = noticeAudio || new (window.AudioContext || window.webkitAudioContext)();
        if (noticeAudio.state === 'suspended') noticeAudio.resume().catch(() => {});
      } catch (_) {}
    }
    document.addEventListener('pointerdown', unlockNoticeSound, { once: true });
    document.addEventListener('keydown', unlockNoticeSound, { once: true });

    function playNoticeSound() {
      try {
        unlockNoticeSound();
        if (!noticeAudio || noticeAudio.state !== 'running') return;
        const now = noticeAudio.currentTime;
        [740, 980].forEach((freq, index) => {
          const osc = noticeAudio.createOscillator();
          const gain = noticeAudio.createGain();
          osc.connect(gain); gain.connect(noticeAudio.destination);
          osc.type = 'sine'; osc.frequency.value = freq;
          const at = now + index * 0.13;
          gain.gain.setValueAtTime(0.001, at);
          gain.gain.exponentialRampToValueAtTime(0.16, at + 0.02);
          gain.gain.exponentialRampToValueAtTime(0.001, at + 0.12);
          osc.start(at); osc.stop(at + 0.13);
        });
      } catch (_) {}
    }

    function syncChatViewport() {
      const viewport = window.visualViewport;
      const h = viewport ? viewport.height : window.innerHeight;
      const top = viewport ? viewport.offsetTop : 0;
      document.documentElement.style.setProperty('--lc-vh', Math.max(240, Math.round(h)) + 'px');
      document.documentElement.style.setProperty('--lc-vtop', Math.max(0, Math.round(top)) + 'px');
    }

    function markRead() {
      if (!roomId || popup.hasAttribute('hidden')) return;
      postJson('/api/chat-read', { room_id: roomId }).then((d) => {
        if (d && d.ok) setBadge(0);
      }).catch(() => {});
    }

    function setPopupOpen(open) {
      if (open) {
        const feedbackModal = document.getElementById('fbModal');
        if (feedbackModal && !feedbackModal.hidden) feedbackModal.hidden = true;
        document.body.classList.remove('fb-open');
        syncChatViewport();
        popup.removeAttribute('hidden');
        launcher.classList.add('active');
        launcher.setAttribute('aria-expanded', 'true');
        document.body.classList.add('lc-open');
        if (roomId && resumed) {
          startPoll();
          markRead();
        }
        window.setTimeout(() => {
          const target = roomId ? document.getElementById('lcMsg') : document.getElementById('lcName');
          if (target && window.matchMedia('(min-width:577px)').matches) target.focus();
        }, 80);
      } else {
        popup.setAttribute('hidden', '');
        launcher.classList.remove('active');
        launcher.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('lc-open');
        // Polling tetap berjalan agar badge/suara/popup pesan admin tetap berfungsi.
      }
    }

    syncChatViewport();
    window.addEventListener('resize', syncChatViewport, { passive: true });
    window.addEventListener('orientationchange', syncChatViewport, { passive: true });
    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', syncChatViewport, { passive: true });
      window.visualViewport.addEventListener('scroll', syncChatViewport, { passive: true });
    }

    function showIntro() {
      popup.dataset.view = 'intro';
      if (intro) intro.hidden = false;
      if (room) room.hidden = true;
      if (waBtn) waBtn.hidden = false;
    }

    function showRoom() {
      popup.dataset.view = 'room';
      if (intro) intro.hidden = true;
      if (room) room.hidden = false;
      if (waBtn) waBtn.hidden = true;
    }

    function statusHtml(status) {
      if (status === 'read') return '<span class="lc-state read"><i class="bi bi-check2-all"></i> Dibaca</span>';
      if (status === 'delivered') return '<span class="lc-state"><i class="bi bi-check2-all"></i> Diterima</span>';
      return '<span class="lc-state"><i class="bi bi-check2"></i> Terkirim</span>';
    }

    function addMsg(m) {
      if (!msgs) return null;
      const d = document.createElement('div');
      d.className = 'lc-msg ' + (m.sender === 'admin' ? 'admin' : 'me');
      if (m.id !== undefined && m.id !== null) d.dataset.mid = String(m.id);
      const state = m.sender === 'user' ? ' · ' + statusHtml(m.status || 'sent') : '';
      d.innerHTML = '<div class="lc-b">' + linkify(m.body) + '</div><span class="lc-t">' + esc(m.time || '') + state + '</span>';
      msgs.appendChild(d);
      msgs.scrollTop = msgs.scrollHeight;
      return d;
    }

    function updateMessageStatuses(map) {
      if (!map || !msgs) return;
      Object.keys(map).forEach((id) => {
        const node = msgs.querySelector('[data-mid="' + String(id).replace(/[^0-9]/g, '') + '"]');
        if (!node || !node.classList.contains('me')) return;
        const time = node.querySelector('.lc-t');
        if (!time) return;
        const plainTime = (time.textContent || '').split(' · ')[0];
        time.innerHTML = esc(plainTime) + ' · ' + statusHtml(map[id]);
      });
    }

    function applyStatus(status) {
      const input = $('#lcMsg'), sendBtn = document.querySelector('#lcSendForm button');
      if (!input) return;
      if (status === 'muted') {
        input.disabled = true; input.placeholder = 'Anda dibisukan admin.';
        if (sendBtn) sendBtn.disabled = true;
        roomClosed = false; return;
      }
      if (status === 'closed') {
        roomClosed = true; input.disabled = true; input.placeholder = 'Obrolan ditutup admin.';
        if (sendBtn) sendBtn.disabled = true;
        let bar = document.getElementById('lcClosedBar');
        if (!bar && room) {
          bar = document.createElement('div');
          bar.id = 'lcClosedBar'; bar.className = 'lc-closed';
          bar.innerHTML = '<span><i class="bi bi-lock-fill me-1"></i> Obrolan ini telah ditutup.</span><button type="button" class="btn btn-sm btn-primary" id="lcNewChat"><i class="bi bi-plus-lg me-1"></i> Mulai Chat Baru</button>';
          room.appendChild(bar);
          const next = document.getElementById('lcNewChat');
          if (next) next.addEventListener('click', startNewChat);
        }
        return;
      }
      roomClosed = false; input.disabled = false; input.placeholder = 'Ketik pesan...';
      if (sendBtn) sendBtn.disabled = false;
      const closedBar = document.getElementById('lcClosedBar');
      if (closedBar) closedBar.remove();
    }

    function notifyAdminMessages(count, unread) {
      if (count < 1) return;
      playNoticeSound();
      setBadge(Math.max(unread || 0, badgeCount + count));
      if (document.visibilityState === 'visible' && popup.hasAttribute('hidden')) {
        // Permintaan fitur: pesan admin langsung tampil sebagai popup.
        setPopupOpen(true);
      }
    }

    let pollBusy = false;
    function poll() {
      if (!roomId || !resumed || document.hidden || pollBusy) return;
      pollBusy = true;
      fetch(base + '/api/chat-poll?room=' + encodeURIComponent(roomId) + '&after=' + lastId, { cache: 'no-store' })
        .then((r) => r.json())
        .finally(() => { pollBusy = false; })
        .then((d) => {
          if (!d.ok) {
            if (d.status === 'invalid') {
              roomId = ''; resumed = false; roomClosed = false;
              try { localStorage.removeItem('desa_chat_room'); } catch (_) {}
              clearInterval(pollTimer); pollTimer = null;
              showIntro(); setBadge(0);
            }
            return;
          }
          let newAdmin = 0;
          (d.messages || []).forEach((m) => {
            if (msgs && m.id != null && msgs.querySelector('[data-mid="' + String(m.id).replace(/"/g, '') + '"]')) { lastId = Math.max(lastId, Number(m.id) || 0); return; }
            addMsg(m);
            lastId = Math.max(lastId, Number(m.id) || 0);
            if (m.sender === 'admin') newAdmin++;
          });
          updateMessageStatuses(d.message_statuses || {});

          let typing = document.getElementById('lcTyping');
          if (d.admin_typing && !roomClosed) {
            if (!typing && msgs) {
              typing = document.createElement('div');
              typing.id = 'lcTyping'; typing.className = 'lc-msg admin lc-typing-bubble';
              typing.innerHTML = '<div class="lc-b"><span></span><span></span><span></span> Admin sedang mengetik…</div>';
              msgs.appendChild(typing); msgs.scrollTop = msgs.scrollHeight;
            }
          } else if (typing) typing.remove();

          applyStatus(d.status);
          if (newAdmin > 0 && popup.hasAttribute('hidden')) notifyAdminMessages(newAdmin, d.unread);
          if (!popup.hasAttribute('hidden')) markRead();
        }).catch(() => {});
    }

    function startPoll() {
      clearInterval(pollTimer);
      poll();
      pollTimer = setInterval(poll, 3000);
    }

    function acceptAdminInvite(d) {
      roomId = d.room_id;
      try { localStorage.setItem('desa_chat_room', roomId); } catch (_) {}
      resumed = true; roomClosed = false;
      showRoom();
      if (msgs) msgs.innerHTML = '';
      lastId = 0;
      (d.messages || []).forEach((m) => { addMsg(m); lastId = Math.max(lastId, Number(m.id) || 0); });
      applyStatus(d.status || 'open');
      const title = document.getElementById('lcPopupTitle');
      if (title) title.textContent = 'Pesan dari Admin Desa';
      playNoticeSound();
      setBadge(Number(d.unread_user) || 1);
      setPopupOpen(true);
      startPoll();
      sendDeviceHeartbeat();
    }

    function checkAdminInvite() {
      if (!room || document.hidden || inviteRequestPending) return;
      inviteRequestPending = true;
      postJson('/api/chat-invite', { visitor_token: visitorToken })
        .then((d) => { if (d && d.ok && d.pending && d.room_id) acceptAdminInvite(d); })
        .catch(() => {})
        .finally(() => { inviteRequestPending = false; });
    }

    function startInvitePoll() {
      clearInterval(inviteTimer);
      if (!room) return;
      checkAdminInvite();
      inviteTimer = setInterval(checkAdminInvite, 4000);
    }

    function resumeFromStorage() {
      if (!roomId || resumed) return;
      postJson('/api/chat-resume', {
        room_id: roomId,
        visitor_token: visitorToken,
        path: location.pathname + location.search,
        title: document.title,
      }).then((d) => {
        resumed = true;
        if (!d.ok) {
          roomId = '';
          try { localStorage.removeItem('desa_chat_room'); } catch (_) {}
          showIntro(); return;
        }
        showRoom();
        if (msgs) msgs.innerHTML = '';
        lastId = 0;
        (d.messages || []).forEach((m) => { addMsg(m); lastId = Math.max(lastId, Number(m.id) || 0); });
        applyStatus(d.status);
        if (Number(d.unread_user) > 0) setBadge(Number(d.unread_user));
        startPoll();
      }).catch(() => { showIntro(); });
    }

    function startNewChat() {
      clearInterval(pollTimer); pollTimer = null;
      roomId = ''; lastId = 0; roomClosed = false; resumed = false;
      try { localStorage.removeItem('desa_chat_room'); } catch (_) {}
      if (msgs) msgs.innerHTML = '';
      const bar = document.getElementById('lcClosedBar'); if (bar) bar.remove();
      const input = $('#lcMsg');
      if (input) { input.disabled = false; input.value = ''; input.placeholder = 'Ketik pesan...'; }
      const title = document.getElementById('lcPopupTitle');
      if (title) title.textContent = 'Butuh Bantuan?';
      setBadge(0); showIntro(); sendDeviceHeartbeat();
    }

    launcher.addEventListener('click', () => {
      const hidden = popup.hasAttribute('hidden');
      if (hidden) {
        setPopupOpen(true);
        if (roomId && !resumed) resumeFromStorage();
        else if (!roomId) showIntro();
      } else setPopupOpen(false);
    });
    const closeBtn = $('#lcClose');
    if (closeBtn) closeBtn.addEventListener('click', () => setPopupOpen(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !popup.hasAttribute('hidden')) setPopupOpen(false); });

    const startForm = $('#lcStart'), startButton = $('#lcStartBtn'), startError = $('#lcStartError');
    let startPending = false;
    function showStartError(message) {
      if (!startError) return;
      const text = startError.querySelector('span');
      if (text) text.textContent = message || '';
      startError.hidden = !message;
    }
    function setStartLoading(loading) {
      startPending = loading;
      if (!startButton) return;
      startButton.disabled = loading;
      startButton.classList.toggle('is-loading', loading);
      startButton.setAttribute('aria-busy', loading ? 'true' : 'false');
    }

    if (startForm) startForm.addEventListener('submit', (e) => {
      e.preventDefault();
      if (startPending) return;
      const nameInput = $('#lcName');
      const name = (nameInput.value || '').trim().replace(/\s+/g, ' ');
      showStartError('');
      if (name.length < 2) {
        showStartError('Nama minimal terdiri dari 2 karakter.');
        nameInput.focus(); return;
      }
      setStartLoading(true);
      postJson('/api/chat-start', {
        name,
        visitor_token: visitorToken,
        path: location.pathname + location.search,
        title: document.title,
      }).then((d) => {
        if (!d.ok) throw new Error(d.error || 'Live chat belum dapat dimulai.');
        roomId = d.room_id;
        try { localStorage.setItem('desa_chat_room', roomId); } catch (_) {}
        resumed = true; showRoom();
        if (msgs) msgs.innerHTML = '';
        lastId = 0; setBadge(0); startPoll(); sendDeviceHeartbeat();
      }).catch((error) => {
        showStartError(error.message || 'Gagal terhubung ke admin.');
      }).finally(() => setStartLoading(false));
    });

    const input = $('#lcMsg');
    let typingAt = 0;
    if (input) input.addEventListener('input', () => {
      if (!roomId || Date.now() - typingAt < 2500) return;
      typingAt = Date.now();
      postJson('/api/chat-typing', { room_id: roomId }).catch(() => {});
    });

    const sendForm = $('#lcSendForm');
    if (sendForm) sendForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const value = (input.value || '').trim();
      if (!value || !roomId || roomClosed || sendPending) return;
      sendPending = true;
      const sendBtn = sendForm.querySelector('button');
      if (sendBtn) sendBtn.disabled = true;
      input.value = '';
      const pending = addMsg({ id: 'pending-' + Date.now(), sender: 'user', body: value, time: '', status: 'sent' });
      postJson('/api/chat-send', { room_id: roomId, body: value })
        .then((d) => {
          if (!d.ok) throw new Error(d.error || 'Gagal mengirim pesan.');
          if (pending) pending.remove();
          poll();
        }).catch((error) => {
          if (pending) pending.remove();
          input.value = value;
          addMsg({ sender: 'admin', body: '⚠️ ' + (error.message || 'Koneksi terputus. Pesan belum terkirim.'), time: '' });
        }).finally(() => {
          sendPending = false;
          if (sendBtn && !roomClosed) sendBtn.disabled = false;
          input.focus();
        });
    });

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        clearInterval(pollTimer); pollTimer = null;
        clearInterval(inviteTimer); inviteTimer = null;
      } else {
        if (roomId && resumed) startPoll();
        else if (roomId) resumeFromStorage();
        startInvitePoll();
      }
    });

    afterFirstInteraction(() => {
      startInvitePoll();
      if (roomId) resumeFromStorage();
    });
  })();

  /* ====== Notifikasi push: langganan warga ====== */
  function urlB64ToU8(s) {
    const pad = '='.repeat((4 - (s.length % 4)) % 4);
    const raw = atob((s + pad).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from(raw, (c) => c.charCodeAt(0));
  }
  async function ensureSubscribed() {
    try {
      const reg = await navigator.serviceWorker.ready;
      let sub = await reg.pushManager.getSubscription();
      if (!sub) sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlB64ToU8(DESA.vapidKey) });
      await fetch(DESA.baseUrl + '/api/push-subscribe', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(sub) });
      return true;
    } catch (_) { return false; }
  }
  if ('serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window &&
      window.DESA && DESA.vapidKey && !window.DESA_ADMIN) {
    if (Notification.permission === 'granted') {
      afterFirstInteraction(() => { ensureSubscribed(); });
    } else if (Notification.permission === 'default' && !localStorage.getItem('desa_push_dismissed')) {
      const bell = document.createElement('div');
      bell.id = 'pushBellBtn';
      bell.className = 'pwa-install push-bell';
      bell.setAttribute('data-testid', 'push-bell-btn');
      bell.innerHTML = '<button type="button" class="pwa-go" title="Aktifkan notifikasi web"><i class="bi bi-bell"></i><span>Notifikasi</span></button><button type="button" class="pwa-x" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>';
      document.body.appendChild(bell);
      autoHide(bell, 'desa_push_dismissed', 8000);
      $('.pwa-go', bell).addEventListener('click', async () => {
        const perm = await Notification.requestPermission();
        if (perm === 'granted') await ensureSubscribed();
        bell.remove();
      });
      $('.pwa-x', bell).addEventListener('click', () => { localStorage.setItem('desa_push_dismissed', '1'); bell.remove(); });
    }
  }

  /* Auto-hide banner PWA/notif setelah beberapa detik (slide-out ke kiri), pause bila user hover.
     Bila user menutup manual atau tidak berinteraksi, sesi berikutnya tidak akan muncul lagi (localStorage flag). */
  function autoHide(el, dismissKey, ms) {
    let t = 0; let hovered = false;
    const dismiss = () => {
      if (hovered) return; // jangan sembunyikan bila user sedang hover/tap
      el.classList.add('pwa-out');
      setTimeout(() => { if (el.parentNode) el.remove(); reflowPwaChips(); }, 420);
    };
    const start = () => { clearTimeout(t); t = setTimeout(dismiss, ms); };
    el.addEventListener('mouseenter', () => { hovered = true; clearTimeout(t); });
    el.addEventListener('mouseleave', () => { hovered = false; start(); });
    el.addEventListener('focusin', () => { hovered = true; clearTimeout(t); });
    el.addEventListener('focusout', () => { hovered = false; start(); });
    start();
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-social-slide]');
    if (!btn) return;
    const slider = document.getElementById('homeSocialSlider');
    if (!slider) return;
    const direction = Number(btn.dataset.socialSlide) || 1;
    slider.scrollBy({ left: direction * Math.min(slider.clientWidth * 0.9, 410), behavior: 'smooth' });
  });

  /* Muat video YouTube di dalam kartu tanpa mengarahkan pengunjung ke situs lain. */
  document.addEventListener('click', (e) => {
    const poster = e.target.closest('[data-social-embed]');
    if (!poster) return;

    const src = poster.dataset.socialEmbed || '';
    if (!/^https:\/\/(?:www\.)?(?:youtube-nocookie\.com|youtube\.com)\/embed\//i.test(src)) return;

    const iframe = document.createElement('iframe');
    iframe.src = src + (src.includes('?') ? '&' : '?') + 'autoplay=1';
    iframe.title = poster.dataset.socialTitle || 'Konten media sosial';
    iframe.loading = 'eager';
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
    iframe.referrerPolicy = 'strict-origin-when-cross-origin';
    iframe.allowFullscreen = true;
    poster.replaceWith(iframe);
  });

  /* ====== Bagikan: salin tautan + share bawaan HP ====== */
  if (navigator.share) $$('.btn-native-share').forEach((b) => b.classList.remove('d-none'));
  function fallbackCopy(text, done) {
    const ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); done(); } catch (_) {}
    ta.remove();
  }
  document.addEventListener('click', (e) => {
    const cp = e.target.closest('.btn-copy-link');
    if (cp) {
      e.preventDefault();
      const link = cp.dataset.url || location.href;
      const done = () => {
        const old = cp.innerHTML;
        cp.innerHTML = '<i class="bi bi-check-lg"></i>';
        setTimeout(() => { cp.innerHTML = old; }, 1600);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(link).then(done).catch(() => fallbackCopy(link, done));
      else fallbackCopy(link, done);
      return;
    }
    const ns = e.target.closest('.btn-native-share');
    if (ns && navigator.share) {
      e.preventDefault();
      navigator.share({ title: ns.dataset.title || document.title, url: ns.dataset.url || location.href }).catch(() => {});
    }
  });

  /* ====== Experience: music + event effects ====== */
  const state = { music: null, event: null, audio: null, effect: null };

  function fetchExperience() {
    fetch(DESA.baseUrl + '/api/experience?halaman=' + DESA.page, { cache: 'no-store' })
      .then((r) => r.json())
      .then((data) => { applyMusic(data.music); applyEvent(data.event); })
      .catch(() => {});
  }
  if (!window.DESA_ADMIN) {
    afterFirstInteraction(() => {
      fetchExperience();
      setInterval(fetchExperience, 120000);
    });
  }

  /* ---- Music player ---- */
  function applyMusic(music) {
    const holder = $('#music-player');
    if (!holder) return;

    if (!music) {
      holder.innerHTML = '';
      if (state.audio) { state.audio.pause(); state.audio.src = ''; state.audio = null; }
      state.music = null;
      return;
    }

    const sameTrack = state.music && state.music.id === music.id && state.music.src === music.src;
    if (sameTrack && state.audio && holder.children.length && !state.audio.dataset.failed) return;
    if (sessionStorage.getItem('desa_music_closed') === String(music.id)) return;

    state.music = music;
    if (state.audio) { state.audio.pause(); state.audio.src = ''; state.audio = null; }

    const audio = new Audio();
    audio.preload = 'auto';
    audio.playsInline = true;
    audio.loop = !!music.loop;
    audio.volume = Math.min(1, Math.max(0, Number(music.volume || 50) / 100));
    const savedMuted = sessionStorage.getItem('desa_music_muted') === '1';
    audio.muted = savedMuted;
    audio.src = music.src;
    state.audio = audio;

    holder.innerHTML = '';
    const box = document.createElement('div');
    box.className = 'music-player pos-' + (music.position || 'kanan-bawah');
    box.setAttribute('data-testid', 'music-player');
    box.innerHTML =
      '<div class="music-eq paused"><span></span><span></span><span></span></div>' +
      '<span class="title" title="' + escapeHtml(music.title) + '">' + escapeHtml(music.title) + '</span>' +
      '<button class="mp-play" aria-label="Putar musik desa" title="Putar Musik Desa"><i class="bi bi-play-fill"></i></button>' +
      '<button class="mp-mute" aria-label="Bisukan"><i class="bi ' + (savedMuted ? 'bi-volume-mute' : 'bi-volume-up') + '"></i></button>' +
      '<input type="range" min="0" max="100" value="' + Number(music.volume || 50) + '" aria-label="Volume">' +
      '<button class="mp-close" aria-label="Tutup player"><i class="bi bi-x"></i></button>';
    holder.appendChild(box);

    const playBtn = $('.mp-play', box);
    const muteBtn = $('.mp-mute', box);
    const vol = $('input', box);
    const eq = $('.music-eq', box);
    const title = $('.title', box);
    let retryAdded = false;
    const shouldHideControls = !!(window.DESA && DESA.music && DESA.music.buttonVisible === false);
    const globalAutoplay = !window.DESA || !DESA.music || DESA.music.autoplay !== false;
    const wantsAutoplay = music.autoplay !== false && globalAutoplay;

    // Jangan tampilkan panel musik saat halaman pertama dibuka. Browser yang
    // menolak autoplay akan memulai audio pada interaksi umum pertama pengguna
    // (tap/klik/tombol keyboard), tanpa mewajibkan pengguna membuka panel musik.
    if (wantsAutoplay) box.classList.add('mp-hidden');

    const syncUI = () => {
      playBtn.innerHTML = audio.paused ? '<i class="bi bi-play-fill"></i>' : '<i class="bi bi-pause-fill"></i>';
      eq.classList.toggle('paused', audio.paused);
      box.classList.toggle('is-playing', !audio.paused);
    };

    const playAudio = () => {
      if (!audio.src) audio.src = music.src;
      audio.dataset.failed = '';
      return audio.play().then(() => {
        title.textContent = music.title;
        title.title = music.title;
        box.classList.remove('music-error', 'mp-needs-action');
        syncUI();
        if (shouldHideControls) box.classList.add('mp-hidden');
        else box.classList.remove('mp-hidden');
      });
    };

    const cleanupUnlock = () => {
      window.removeEventListener('pointerdown', unlockPlayback, true);
      window.removeEventListener('touchend', unlockPlayback, true);
      window.removeEventListener('keydown', unlockPlayback, true);
      retryAdded = false;
    };

    const unlockPlayback = () => {
      playAudio().then(cleanupUnlock).catch(() => {});
    };

    const queuePlaybackUnlock = () => {
      // Tetap tersembunyi: interaksi pertama pada bagian mana pun di halaman
      // menjadi izin pemutaran. Tidak ada popup/tab musik yang menghalangi user.
      box.classList.add('mp-hidden', 'mp-needs-action');
      title.textContent = music.title;
      title.title = 'Musik akan dimulai pada interaksi pertama di halaman';
      if (retryAdded) return;
      retryAdded = true;
      window.addEventListener('pointerdown', unlockPlayback, true);
      window.addEventListener('touchend', unlockPlayback, true);
      window.addEventListener('keydown', unlockPlayback, true);
    };

    playBtn.addEventListener('click', () => {
      if (audio.paused) playAudio().catch(() => queuePlaybackUnlock());
      else audio.pause();
    });
    audio.addEventListener('play', syncUI);
    audio.addEventListener('pause', () => {
      syncUI();
      if (shouldHideControls && !audio.ended) box.classList.remove('mp-hidden');
    });
    audio.addEventListener('ended', () => {
      syncUI();
      if (shouldHideControls) box.classList.remove('mp-hidden');
    });
    audio.addEventListener('canplay', () => {
      audio.dataset.failed = '';
      box.classList.remove('music-error');
      title.textContent = music.title;
    });
    audio.addEventListener('error', () => {
      audio.dataset.failed = '1';
      box.classList.remove('mp-hidden');
      box.classList.add('music-error', 'mp-needs-action');
      title.textContent = 'Musik gagal dimuat';
      title.title = 'Ketuk tombol putar untuk mencoba kembali';
      playBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
      eq.classList.add('paused');
    });

    muteBtn.addEventListener('click', () => {
      audio.muted = !audio.muted;
      sessionStorage.setItem('desa_music_muted', audio.muted ? '1' : '0');
      muteBtn.innerHTML = '<i class="bi ' + (audio.muted ? 'bi-volume-mute' : 'bi-volume-up') + '"></i>';
      if (!audio.muted && audio.paused) playAudio().catch(() => queuePlaybackUnlock());
    });

    vol.addEventListener('input', () => {
      audio.volume = Number(vol.value) / 100;
      if (audio.muted && Number(vol.value) > 0) {
        audio.muted = false;
        sessionStorage.setItem('desa_music_muted', '0');
        muteBtn.innerHTML = '<i class="bi bi-volume-up"></i>';
      }
    });

    $('.mp-close', box).addEventListener('click', () => {
      cleanupUnlock();
      audio.pause();
      audio.src = '';
      box.remove();
      sessionStorage.setItem('desa_music_closed', String(music.id));
    });

    if (wantsAutoplay) {
      // Browser dapat menahan autoplay. Interaksi umum pertama yang sah akan
      // memulai audio tanpa menampilkan panel atau meminta klik tombol musik.
      playAudio().catch(() => queuePlaybackUnlock());
    } else {
      audio.load();
    }

    // Jika admin menonaktifkan autoplay, panel tetap tersedia untuk pemutaran
    // manual. Jika tombol disembunyikan admin, panel tidak tampil setelah musik
    // berhasil berjalan.
  }

  /* ---- Event effects ---- */
  function applyEvent(ev) {
    const banner = $('#event-banner');
    if (!ev) { if (banner) banner.innerHTML = ''; stopEffect(); clearTheme(); state.event = null; return; }
    if (state.event && state.event.id === ev.id) return;
    state.event = ev;
    if (sessionStorage.getItem('desa_event_closed') === String(ev.id)) return;

    if (ev.theme_color) {
      document.documentElement.style.setProperty('--c-primary', ev.theme_color);
    }
    if (banner && ev.banner) {
      banner.innerHTML = '<div class="banner-inner" data-testid="event-banner">' + escapeHtml(ev.banner) +
        (ev.closable ? '<button class="banner-close" aria-label="Tutup">&times;</button>' : '') + '</div>';
      const closeBtn = $('.banner-close', banner);
      closeBtn && closeBtn.addEventListener('click', () => { banner.innerHTML = ''; });
    }
    const onceKey = 'desa_event_shown_' + ev.id;
    if (ev.once && sessionStorage.getItem(onceKey)) return;
    sessionStorage.setItem(onceKey, '1');

    startEffect(ev);
    if (ev.message) showMessage(ev.message, ev.duration > 0 ? Math.min(ev.duration, 8) : 6);
    if (ev.closable) addCloseButton(ev);
    if (ev.frequency > 0) {
      state.effectTimer && clearInterval(state.effectTimer);
      state.effectTimer = setInterval(() => {
        if (sessionStorage.getItem('desa_event_closed') === String(ev.id)) return;
        startEffect(ev);
      }, ev.frequency * 60000);
    }
  }

  function clearTheme() { document.documentElement.style.removeProperty('--c-primary'); }

  function showMessage(text, seconds) {
    const el = document.createElement('div');
    el.className = 'event-message'; el.setAttribute('data-testid', 'event-message');
    el.textContent = text;
    document.body.appendChild(el);
    setTimeout(() => { el.classList.add('fade-out'); setTimeout(() => el.remove(), 600); }, seconds * 1000);
  }

  function addCloseButton(ev) {
    if ($('.effect-close')) return;
    const btn = document.createElement('button');
    btn.className = 'effect-close'; btn.setAttribute('data-testid', 'event-close');
    btn.innerHTML = '<i class="bi bi-x-lg me-1"></i> Tutup efek';
    btn.addEventListener('click', () => {
      stopEffect(); clearTheme(); btn.remove();
      const banner = $('#event-banner'); banner && (banner.innerHTML = '');
      $$('.event-message').forEach((m) => m.remove());
      state.effectTimer && clearInterval(state.effectTimer);
      sessionStorage.setItem('desa_event_closed', String(ev.id));
    });
    document.body.appendChild(btn);
  }

  /* Mesin partikel ringan (canvas) */
  let canvas, ctx, particles = [], rafId = null, effectEndAt = 0, currentType = 'confetti', colors = [];
  const INTENSITY = { ringan: 40, sedang: 90, ramai: 160 };

  function startEffect(ev) {
    stopEffect(false);
    currentType = ev.effect || 'confetti';
    colors = (ev.colors && ev.colors.length) ? ev.colors : ['#e63946', '#ffffff'];
    const count = Math.min(INTENSITY[ev.intensity] || 90, window.innerWidth < 600 ? 70 : 200);
    canvas = document.getElementById('effect-canvas');
    if (!canvas) {
      canvas = document.createElement('canvas');
      canvas.id = 'effect-canvas';
      $('#effect-layer').appendChild(canvas);
    }
    canvas.width = window.innerWidth; canvas.height = window.innerHeight;
    ctx = canvas.getContext('2d');
    particles = [];
    for (let i = 0; i < count; i++) particles.push(makeParticle(true));
    effectEndAt = ev.duration > 0 ? Date.now() + ev.duration * 1000 : 0;
    loop();
  }

  function makeParticle(spread) {
    const W = canvas.width, H = canvas.height;
    const c = colors[Math.floor(Math.random() * colors.length)];
    const base = { color: c, x: Math.random() * W, y: spread ? Math.random() * -H : -20 };
    switch (currentType) {
      case 'snow': return { ...base, r: 1.5 + Math.random() * 3, vy: 0.6 + Math.random() * 1.4, vx: Math.sin(Math.random() * 6) * 0.5, sway: Math.random() * 2 };
      case 'leaves': return { ...base, r: 5 + Math.random() * 6, vy: 0.8 + Math.random() * 1.4, vx: -0.6 + Math.random() * 1.2, rot: Math.random() * 6, vr: 0.02 + Math.random() * 0.04 };
      case 'balloon': return { color: c, x: Math.random() * W, y: H + Math.random() * H * 0.5, r: 12 + Math.random() * 12, vy: -(0.7 + Math.random() * 1.2), vx: Math.sin(Math.random() * 6) * 0.4 };
      case 'sparkle': return { color: c, x: Math.random() * W, y: Math.random() * H, r: 1 + Math.random() * 2.5, life: 0, maxLife: 40 + Math.random() * 60 };
      case 'fireworks': {
        const cx = W * (0.15 + Math.random() * 0.7), cy = H * (0.1 + Math.random() * 0.4);
        const a = Math.random() * Math.PI * 2, sp = 1 + Math.random() * 4;
        return { color: c, x: cx, y: cy, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp, r: 1.5 + Math.random() * 2, life: 0, maxLife: 50 + Math.random() * 40, grav: 0.04 };
      }
      default: /* confetti */
        return { ...base, w: 6 + Math.random() * 6, h: 8 + Math.random() * 8, vy: 1.6 + Math.random() * 2.4, vx: -1 + Math.random() * 2, rot: Math.random() * 6, vr: -0.1 + Math.random() * 0.2 };
    }
  }

  function loop() {
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    const H = canvas.height, W = canvas.width;
    const expired = effectEndAt > 0 && Date.now() > effectEndAt;
    let alive = 0;
    particles.forEach((p, i) => {
      switch (currentType) {
        case 'sparkle':
        case 'fireworks':
          p.life++;
          if (p.grav) { p.vy += p.grav; p.x += p.vx; p.y += p.vy; }
          if (p.life > p.maxLife) { if (!expired) particles[i] = makeParticle(currentType === 'sparkle'); else return; }
          ctx.globalAlpha = Math.max(0, 1 - p.life / p.maxLife);
          ctx.fillStyle = p.color;
          ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, 7); ctx.fill();
          ctx.globalAlpha = 1; alive++;
          return;
        case 'balloon':
          p.x += p.vx + Math.sin(p.y / 40) * 0.4; p.y += p.vy;
          if (p.y < -60) { if (!expired) particles[i] = makeParticle(false); else return; }
          ctx.fillStyle = p.color;
          ctx.beginPath(); ctx.ellipse(p.x, p.y, p.r * 0.8, p.r, 0, 0, 7); ctx.fill();
          ctx.strokeStyle = 'rgba(120,120,120,.5)';
          ctx.beginPath(); ctx.moveTo(p.x, p.y + p.r); ctx.lineTo(p.x, p.y + p.r + 16); ctx.stroke();
          alive++; return;
        case 'snow':
          p.x += Math.sin((p.y + p.sway * 50) / 50) * 0.6; p.y += p.vy;
          if (p.y > H + 10) { if (!expired) particles[i] = makeParticle(false); else return; }
          ctx.fillStyle = 'rgba(255,255,255,.9)';
          ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, 7); ctx.fill();
          alive++; return;
        case 'leaves':
          p.x += p.vx + Math.sin(p.y / 60); p.y += p.vy; p.rot += p.vr;
          if (p.y > H + 20) { if (!expired) particles[i] = makeParticle(false); else return; }
          ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot);
          ctx.fillStyle = p.color;
          ctx.beginPath(); ctx.ellipse(0, 0, p.r, p.r / 2, 0, 0, 7); ctx.fill();
          ctx.restore(); alive++; return;
        default: /* confetti */
          p.x += p.vx; p.y += p.vy; p.rot += p.vr;
          if (p.y > H + 20) { if (!expired) particles[i] = makeParticle(false); else return; }
          ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot);
          ctx.fillStyle = p.color;
          ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
          ctx.restore(); alive++;
      }
    });
    if (expired && alive === 0) { stopEffect(); return; }
    rafId = requestAnimationFrame(loop);
  }

  function stopEffect(removeCanvas = true) {
    rafId && cancelAnimationFrame(rafId); rafId = null;
    particles = [];
    if (removeCanvas && canvas) { canvas.remove(); canvas = null; ctx = null; }
    else if (ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);
  }

  window.addEventListener('resize', () => {
    if (canvas) { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
  });

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  /* Ekspor untuk preview admin */
  window.DesaEffects = { start: startEffect, stop: stopEffect };

  /* ====== Feedback popup ====== */
  (function () {
    // 3D parallax hover untuk kartu transparansi (throttled via rAF supaya tidak lag saat gerak mouse)
    Array.prototype.forEach.call(document.querySelectorAll('.tr-card'), function (c) {
      let raf = 0, lastE = null;
      const apply = function () {
        raf = 0; if (!lastE) return;
        var r = c.getBoundingClientRect();
        var x = ((lastE.clientX - r.left) / r.width) * 100;
        var y = ((lastE.clientY - r.top) / r.height) * 100;
        c.style.setProperty('--mx', x + '%');
        c.style.setProperty('--my', y + '%');
        var rx = ((y - 50) / 50) * -3;
        var ry = ((x - 50) / 50) * 3;
        c.style.transform = 'translateY(-6px) perspective(900px) rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
      };
      c.addEventListener('pointermove', function (e) { if (e.pointerType !== 'mouse') return; lastE = e; if (!raf) raf = requestAnimationFrame(apply); }, { passive: true });
      c.addEventListener('pointerleave', function () { c.style.transform = ''; });
    });
  })();

  /* ====== Feedback popup (original) ====== */
  (function () {
    const launcher = $('#fbLauncher'), modal = $('#fbModal');
    if (!launcher || !modal) return;
    const KEY = 'desa_fb_shown';
    const settings = (window.DESA && DESA.feedback) || { enabled: true, delay: 25 };
    const backdrop = $('#fbBackdrop'), close = $('#fbClose'), form = $('#fbForm');
    const okBox = $('#fbOk');
    const answers = {}; // {questionId: value}

    function open() { if (document.body.classList.contains('lc-open')) return; modal.hidden = false; document.body.classList.add('fb-open'); }
    function shut() { modal.hidden = true; document.body.classList.remove('fb-open'); }

    launcher.addEventListener('click', open);
    close && close.addEventListener('click', shut);
    backdrop && backdrop.addEventListener('click', shut);

    // Auto-show setelah delay, hanya jika belum dilihat dalam 30 hari
    if (settings.enabled) {
      const last = parseInt(localStorage.getItem(KEY) || '0', 10);
      const now = Date.now();
      if (now - last > 30 * 24 * 3600 * 1000) {
        afterFirstInteraction(() => {
          setTimeout(open, Math.max(3, settings.delay || 25) * 1000);
        });
      }
    }

    // Rating stars: klik untuk pilih 1-5
    Array.prototype.forEach.call(modal.querySelectorAll('.fb-rating'), function (grp) {
      Array.prototype.forEach.call(grp.querySelectorAll('.fb-star'), function (star, idx) {
        star.addEventListener('click', function () {
          const val = parseInt(star.getAttribute('data-v'), 10);
          const qid = grp.getAttribute('data-q');
          answers[qid] = val;
          Array.prototype.forEach.call(grp.querySelectorAll('.fb-star'), function (s, i) {
            s.classList.toggle('on', i < val);
            s.innerHTML = i < val ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
          });
        });
      });
    });

    form && form.addEventListener('submit', function (e) {
      e.preventDefault();
      // Kumpulkan jawaban select/textarea/text
      Array.prototype.forEach.call(modal.querySelectorAll('[data-q]'), function (el) {
        if (el.tagName === 'DIV') return; // rating sudah tercatat
        const qid = el.getAttribute('data-q');
        if (el.value !== '') answers[qid] = el.value;
      });
      if (Object.keys(answers).length === 0) { alert('Mohon isi minimal satu pertanyaan.'); return; }
      const base = (window.DESA && DESA.baseUrl ? DESA.baseUrl.replace(/\/$/, '') : '');
      const payload = JSON.stringify({
        name: ($('#fbName').value || '').trim(),
        answers: answers,
        visitor_token: (window.DESA && DESA.visitorToken) || ''
      });
      fetch(base + '/api/feedback-submit', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: payload })
        .then((r) => r.json())
        .then((d) => {
          if (d.ok) {
            localStorage.setItem(KEY, String(Date.now()));
            [...form.children].forEach((c) => { if (c !== okBox) c.hidden = true; }); okBox.hidden = false;
            try { window.dispatchEvent(new CustomEvent('desa:feedback-submitted')); } catch (_) {}
            setTimeout(shut, 2400);
          } else {
            alert(d.error || 'Gagal mengirim. Coba lagi.');
          }
        }).catch(() => alert('Tidak bisa terhubung ke server.'));
    });
  })();
})();