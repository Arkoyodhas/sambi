/* Background interaktif: jaringan partikel halus yang merespon kursor */
(function () {
  'use strict';
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var canvas = document.createElement('canvas');
  canvas.id = 'bgfx-canvas';
  canvas.setAttribute('aria-hidden', 'true');
  canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;z-index:-1;pointer-events:none;';
  document.body.prepend(canvas);
  var ctx = canvas.getContext('2d');

  var densityMap = { rendah: 30, sedang: 55, tinggi: 85 };
  var isMobile = window.innerWidth < 768;
  var COUNT = Math.round((densityMap[(window.DESA && window.DESA.bgfxDensity) || 'sedang'] || 55) * (isMobile ? 0.45 : 1));
  var LINK_DIST = isMobile ? 110 : 150;
  var mouse = { x: -9999, y: -9999 };
  var particles = [];
  var running = true;

  function themeColor() {
    var v = getComputedStyle(document.documentElement).getPropertyValue('--c-primary').trim();
    return v || '#0f6b4e';
  }
  function hexRgb(hex) {
    var h = hex.replace('#', '');
    if (h.length === 3) h = h.split('').map(function (c) { return c + c; }).join('');
    var n = parseInt(h, 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
  }
  var rgb = hexRgb(themeColor());
  new MutationObserver(function () { rgb = hexRgb(themeColor()); })
    .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'style'] });

  function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  function make() {
    return {
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      vx: (Math.random() - 0.5) * 0.35,
      vy: (Math.random() - 0.5) * 0.35,
      r: Math.random() * 1.8 + 0.7,
    };
  }
  function init() {
    resize();
    particles = [];
    for (var i = 0; i < COUNT; i++) particles.push(make());
  }

  function step() {
    if (!running) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    var dotAlpha = dark ? 0.5 : 0.35;
    var lineAlpha = dark ? 0.16 : 0.12;
    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      // tarik lembut ke arah kursor dalam radius
      var dxm = mouse.x - p.x, dym = mouse.y - p.y;
      var dm = Math.sqrt(dxm * dxm + dym * dym);
      if (dm < 180 && dm > 0.001) {
        p.vx += (dxm / dm) * 0.012;
        p.vy += (dym / dm) * 0.012;
      }
      p.vx = Math.max(-0.6, Math.min(0.6, p.vx));
      p.vy = Math.max(-0.6, Math.min(0.6, p.vy));
      p.x += p.vx; p.y += p.vy;
      if (p.x < -20) p.x = canvas.width + 20; if (p.x > canvas.width + 20) p.x = -20;
      if (p.y < -20) p.y = canvas.height + 20; if (p.y > canvas.height + 20) p.y = -20;

      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + dotAlpha + ')';
      ctx.fill();

      for (var j = i + 1; j < particles.length; j++) {
        var q = particles[j];
        var dx = p.x - q.x, dy = p.y - q.y;
        var d2 = dx * dx + dy * dy;
        if (d2 < LINK_DIST * LINK_DIST) {
          var a = (1 - Math.sqrt(d2) / LINK_DIST) * lineAlpha;
          ctx.beginPath();
          ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y);
          ctx.strokeStyle = 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + a + ')';
          ctx.lineWidth = 1;
          ctx.stroke();
        }
      }
    }
    requestAnimationFrame(step);
  }

  window.addEventListener('resize', function () { resize(); });
  window.addEventListener('mousemove', function (e) { mouse.x = e.clientX; mouse.y = e.clientY; }, { passive: true });
  window.addEventListener('mouseout', function () { mouse.x = -9999; mouse.y = -9999; });
  window.addEventListener('click', function (e) {
    // dorongan kecil saat klik (riak)
    particles.forEach(function (p) {
      var dx = p.x - e.clientX, dy = p.y - e.clientY;
      var d = Math.sqrt(dx * dx + dy * dy) || 1;
      if (d < 160) { p.vx += (dx / d) * 1.6; p.vy += (dy / d) * 1.6; }
    });
  });
  document.addEventListener('visibilitychange', function () {
    running = !document.hidden;
    if (running) requestAnimationFrame(step);
  });

  init();
  requestAnimationFrame(step);
})();
