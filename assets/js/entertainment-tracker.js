/* Pencatat aktivitas publik untuk poin dan misi Zona Hiburan. */
(function () {
  'use strict';

  const cfg = window.DESA && DESA.entertainment;
  if (!cfg || !cfg.enabled) return;

  const base = String(DESA.baseUrl || '').replace(/\/$/, '');

  function readCookie(name) {
    const prefix = name + '=';
    const item = document.cookie.split(';').map((part) => part.trim()).find((part) => part.indexOf(prefix) === 0);
    if (!item) return '';
    try { return decodeURIComponent(item.slice(prefix.length)); } catch (_) { return ''; }
  }

  function persist(token) {
    try { localStorage.setItem('desa_visitor_token', token); } catch (_) {}
    try {
      const secure = location.protocol === 'https:' ? '; Secure' : '';
      document.cookie = 'desa_visitor_token=' + encodeURIComponent(token) + '; Max-Age=315360000; Path=/; SameSite=Lax' + secure;
    } catch (_) {}
  }

  function token() {
    let value = String(DESA.visitorToken || '');
    try { if (!value) value = localStorage.getItem('desa_visitor_token') || ''; } catch (_) {}
    if (!value) value = readCookie('desa_visitor_token');
    if (!/^[a-f0-9]{64}$/i.test(value)) {
      try {
        const bytes = new Uint8Array(32);
        crypto.getRandomValues(bytes);
        value = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
      } catch (_) {
        return '';
      }
    }
    value = value.toLowerCase();
    persist(value);
    DESA.visitorToken = value;
    return value;
  }

  const visitorToken = token();
  if (!visitorToken) return;

  function onceKey(activity, source) {
    const day = new Date().toISOString().slice(0, 10);
    return 'desa_ent_activity:' + day + ':' + activity + ':' + String(source || '').slice(0, 120);
  }

  function send(activity, source) {
    if (!activity) return Promise.resolve(null);
    const key = onceKey(activity, source);
    try {
      if (sessionStorage.getItem(key) === '1') return Promise.resolve(null);
      sessionStorage.setItem(key, '1');
    } catch (_) {}

    return fetch(base + '/hiburan/activity', {
      method: 'POST',
      cache: 'no-store',
      credentials: 'same-origin',
      keepalive: true,
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        visitor_token: visitorToken,
        activity: activity,
        source: source || location.pathname
      })
    }).then((response) => response.json()).then((data) => {
      if (data && data.ok && data.awarded) {
        window.dispatchEvent(new CustomEvent('desa:entertainment-points', { detail: data }));
      }
      return data;
    }).catch(() => null);
  }

  function classify(path) {
    const normalized = String(path || '/').toLowerCase().replace(/\/+$/, '') || '/';
    if (/\/berita\/[^/]+$/.test(normalized)) return 'read_news';
    if (normalized.indexOf('/galeri') !== -1) return 'open_gallery';
    if (/(\/pengumuman|\/agenda|\/layanan|\/profil|\/transparansi|\/dokumen|\/umkm|\/potensi)(\/|$)/.test(normalized)) return 'read_info';
    return '';
  }

  const pageActivity = classify(location.pathname);
  if (pageActivity) {
    let tracked = false;
    const trackVisiblePage = function () {
      if (tracked || document.visibilityState !== 'visible') return;
      tracked = true;
      send(pageActivity, location.pathname);
    };
    setTimeout(trackVisiblePage, 6500);
    document.addEventListener('visibilitychange', trackVisiblePage, { passive: true });
  }

  if (cfg.loggedIn) send('login', location.pathname);

  window.addEventListener('desa:feedback-submitted', function () {
    send('survey', location.pathname);
  });
}());
