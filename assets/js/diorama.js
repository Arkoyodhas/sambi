/* Desa Dawung — clay-style 3D mini diorama (pendopo, trees, fish pond).
   Desktop + pointer halus saja; ponsel tetap memakai gambar diam. */
(function () {
  'use strict';
  var box = document.querySelector('.hx-diorama');
  var canvas = document.getElementById('dioramaCanvas');
  if (!box || !canvas || !window.DESA_DIORAMA) return;
  var ok = window.matchMedia('(min-width: 992px) and (hover: hover) and (pointer: fine)').matches
    && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!ok) return;
  try { var t = document.createElement('canvas'); if (!(t.getContext('webgl2') || t.getContext('webgl'))) return; } catch (e) { return; }

  var start = function () {
    var s = document.createElement('script');
    s.src = window.DESA_DIORAMA; s.async = true;
    s.onload = function () { try { build(window.THREE); } catch (e) { console.warn('Diorama:', e); } };
    document.head.appendChild(s);
  };
  if ('requestIdleCallback' in window) requestIdleCallback(start, { timeout: 1500 }); else setTimeout(start, 600);

  function build(T) {
    var clay = function (hex, rough) { return new T.MeshStandardMaterial({ color: hex, roughness: rough == null ? 0.92 : rough, metalness: 0 }); };
    var M = {
      grass: clay(0x9BD48A), grassDark: clay(0x7CC27A), soil: clay(0xC8946A), soilDark: clay(0xA9754F),
      stone: clay(0xEDE3D1), stoneDark: clay(0xD8CCB6), wood: clay(0x8A5A3B), woodLight: clay(0xB07B52),
      roof: clay(0x9C4F33), roofTop: clay(0x7E3D27), tile: clay(0xD97A4E), wall: clay(0xF5EBDD), wallMint: clay(0xBFE7D8),
      leaf1: clay(0x48B072), leaf2: clay(0x5FC383), leaf3: clay(0x379563), trunk: clay(0x7A4E33),
      water: new T.MeshStandardMaterial({ color: 0x5ECFC9, roughness: 0.25, metalness: 0.05, transparent: true, opacity: 0.92 }),
      lily: clay(0x3E9E5E), fish: clay(0xF59E0B, 0.6), rice: clay(0xB6DE7E), amber: clay(0xF59E0B)
    };
    var renderer = new T.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.25));
    renderer.outputColorSpace = T.SRGBColorSpace;
    renderer.toneMapping = T.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = T.PCFSoftShadowMap;

    var scene = new T.Scene();
    var camera = new T.PerspectiveCamera(24, 1, 0.1, 200);
    var camBase = new T.Vector3(27, 21, 27);
    camera.position.copy(camBase);

    scene.add(new T.HemisphereLight(0xffffff, 0xCFEFE3, 1.25));
    var sun = new T.DirectionalLight(0xFFF1DC, 2.1);
    sun.position.set(9, 16, 6);
    sun.castShadow = true;
    sun.shadow.mapSize.set(512, 512);
    sun.shadow.camera.left = -10; sun.shadow.camera.right = 10; sun.shadow.camera.top = 10; sun.shadow.camera.bottom = -10;
    sun.shadow.radius = 5; sun.shadow.bias = -0.0008;
    scene.add(sun);

    var world = new T.Group();
    scene.add(world);
    var add = function (geo, mat, x, y, z, parent) {
      var m = new T.Mesh(geo, mat); m.position.set(x || 0, y || 0, z || 0);
      m.castShadow = true; m.receiveShadow = true; (parent || world).add(m); return m;
    };
    var rounded = function (w, d, h, r, bevel) {
      var s = new T.Shape(), x = -w / 2, y = -d / 2;
      s.moveTo(x + r, y); s.lineTo(x + w - r, y); s.quadraticCurveTo(x + w, y, x + w, y + r);
      s.lineTo(x + w, y + d - r); s.quadraticCurveTo(x + w, y + d, x + w - r, y + d);
      s.lineTo(x + r, y + d); s.quadraticCurveTo(x, y + d, x, y + d - r);
      s.lineTo(x, y + r); s.quadraticCurveTo(x, y, x + r, y);
      var g = new T.ExtrudeGeometry(s, { depth: h, bevelEnabled: true, bevelThickness: bevel, bevelSize: bevel, bevelSegments: 4, curveSegments: 10 });
      g.rotateX(-Math.PI / 2); g.translate(0, -h / 2, 0);
      return g;
    };

    // Tanah & rumput
    add(rounded(10.4, 10.4, 1.6, 1.6, 0.25), M.soil, 0, -0.95, 0);
    add(rounded(10.2, 10.2, 0.25, 1.5, 0.22), M.soilDark, 0, -1.85, 0);
    add(rounded(10.6, 10.6, 0.36, 1.7, 0.18), M.grass, 0, 0, 0);
    var shadowPlane = new T.Mesh(new T.CircleGeometry(9, 48), new T.ShadowMaterial({ opacity: 0.18 }));
    shadowPlane.rotation.x = -Math.PI / 2; shadowPlane.position.y = -2.2; shadowPlane.receiveShadow = true; world.add(shadowPlane);

    // Pendopo (joglo)
    var pend = new T.Group(); pend.position.set(-0.6, 0.18, -0.9); world.add(pend);
    add(rounded(4.6, 4.0, 0.42, 0.25, 0.06), M.stone, 0, 0.3, 0, pend);
    add(rounded(4.1, 3.5, 0.2, 0.2, 0.04), M.stoneDark, 0, 0.6, 0, pend);
    var pillarGeo = new T.CylinderGeometry(0.11, 0.13, 2.0, 12);
    [[-1.7, -1.4], [1.7, -1.4], [-1.7, 1.4], [1.7, 1.4], [-0.6, -0.5], [0.6, -0.5], [-0.6, 0.5], [0.6, 0.5]].forEach(function (p, i) {
      add(pillarGeo, i < 4 ? M.wood : M.woodLight, p[0], 1.7, p[1], pend);
    });
    add(new T.BoxGeometry(4.0, 0.16, 3.3), M.wood, 0, 2.72, 0, pend);
    var roof1 = add(new T.CylinderGeometry(0.9, 3.35, 0.9, 4, 1), M.roof, 0, 3.22, 0, pend); roof1.rotation.y = Math.PI / 4; roof1.scale.set(1.12, 1, 0.98);
    var roof2 = add(new T.CylinderGeometry(0.18, 1.35, 1.35, 4, 1), M.roofTop, 0, 4.3, 0, pend); roof2.rotation.y = Math.PI / 4; roof2.scale.set(1.3, 1, 0.9);
    add(new T.SphereGeometry(0.16, 16, 12), M.amber, 0, 5.05, 0, pend);
    add(new T.BoxGeometry(0.5, 0.25, 0.6), M.stone, 0, 0.35, 2.15, pend);

    // Jalan setapak
    for (var i = 0; i < 6; i++) {
      var st = add(new T.CylinderGeometry(0.34, 0.36, 0.1, 10), i % 2 ? M.stone : M.stoneDark, -0.4 + Math.sin(i * 0.9) * 0.35, 0.2, 1.75 + i * 0.55);
      st.scale.set(1, 1, 0.8);
    }

    // Pohon rimbun
    var trees = [];
    var tree = function (x, z, s, mat) {
      var g = new T.Group(); g.position.set(x, 0.18, z); g.scale.setScalar(s); world.add(g);
      add(new T.CylinderGeometry(0.14, 0.22, 1.5, 8), M.trunk, 0, 0.75, 0, g);
      add(new T.IcosahedronGeometry(0.95, 1), mat, 0, 2.0, 0, g);
      add(new T.IcosahedronGeometry(0.7, 1), M.leaf2, 0.55, 1.75, 0.2, g);
      add(new T.IcosahedronGeometry(0.62, 1), M.leaf3, -0.45, 1.65, -0.3, g);
      add(new T.IcosahedronGeometry(0.55, 1), M.leaf1, 0.1, 2.65, -0.1, g);
      g.userData.phase = Math.random() * Math.PI * 2; trees.push(g); return g;
    };
    tree(-3.8, -3.6, 1.15, M.leaf1); tree(-1.9, -4.0, 0.95, M.leaf3); tree(3.9, -3.7, 1.05, M.leaf1);
    tree(4.1, -0.6, 0.85, M.leaf3); tree(-4.1, -0.4, 0.8, M.leaf2); tree(1.5, -4.1, 0.75, M.leaf2);
    // Semak
    [[-3.9, 2.2], [-2.6, 4.0], [1.4, 4.2], [4.2, 3.8], [2.0, 0.9]].forEach(function (b, k) { var m = add(new T.IcosahedronGeometry(0.42 + (k % 2) * 0.12, 1), k % 2 ? M.leaf2 : M.grassDark, b[0], 0.42, b[1]); m.scale.y = 0.75; });

    // Kolam ikan
    var pond = new T.Group(); pond.position.set(2.6, 0.12, 2.1); world.add(pond);
    var rim = add(new T.TorusGeometry(1.55, 0.18, 10, 40), M.stone, 0, 0.12, 0, pond); rim.rotation.x = Math.PI / 2; rim.scale.set(1.2, 1, 1);
    var water = add(new T.CylinderGeometry(1.55, 1.55, 0.1, 40), M.water, 0, 0.1, 0, pond); water.scale.set(1.2, 1, 1); water.castShadow = false;
    [[-0.6, 0.4], [0.7, -0.5], [0.2, 0.8]].forEach(function (l) { var p = add(new T.CylinderGeometry(0.24, 0.24, 0.03, 16), M.lily, l[0], 0.17, l[1], pond); p.castShadow = false; });
    add(new T.SphereGeometry(0.08, 10, 8), M.amber, -0.6, 0.22, 0.4, pond);
    var fishes = [0, 1, 2].map(function (k) {
      var f = add(new T.CapsuleGeometry(0.07, 0.24, 4, 8), M.fish, 0, 0.11, 0, pond); f.rotation.z = Math.PI / 2; f.castShadow = false;
      f.userData = { r: 0.55 + k * 0.28, sp: 0.35 + k * 0.12, ph: k * 2.1 }; return f;
    });

    // Rumah kecil
    var house = function (x, z, rot, wallMat) {
      var g = new T.Group(); g.position.set(x, 0.18, z); g.rotation.y = rot; world.add(g);
      add(new T.BoxGeometry(1.5, 1.0, 1.2), wallMat, 0, 0.5, 0, g);
      var r = add(new T.CylinderGeometry(0.85, 0.85, 1.75, 3, 1), M.tile, 0, 1.25, 0, g); r.rotation.z = Math.PI / 2; r.rotation.y = 0; r.scale.set(0.62, 1, 1.05);
      add(new T.BoxGeometry(0.34, 0.56, 0.06), M.wood, 0.2, 0.28, 0.62, g);
      add(new T.BoxGeometry(0.28, 0.24, 0.06), M.wallMint, -0.38, 0.55, 0.62, g);
      return g;
    };
    house(-3.2, 3.0, 0.25, M.wallMint); house(-1.7, 3.6, -0.1, M.wall);

    // Petak sawah
    for (var r2 = 0; r2 < 4; r2++) add(new T.BoxGeometry(2.4, 0.16, 0.28), M.rice, 3.4, 0.22, -2.3 + r2 * 0.42);

    // Ukuran & kamera
    var resize = function () {
      var w = canvas.clientWidth, h = canvas.clientHeight;
      if (!w || !h) return;
      renderer.setSize(w, h, false); camera.aspect = w / h; camera.updateProjectionMatrix();
    };
    resize();
    window.addEventListener('resize', resize);

    var pointer = { x: 0, y: 0 }, cur = { x: 0, y: 0 }, prog = 0, curProg = 0;
    window.addEventListener('hero:pointer', function (e) { pointer.x = e.detail.x; pointer.y = e.detail.y; });
    window.addEventListener('hero:progress', function (e) { prog = e.detail; });
    var visible = true;
    if ('IntersectionObserver' in window) new IntersectionObserver(function (en) { visible = en[0].isIntersecting; }).observe(box);

    var clock = new T.Clock(), first = true, lookAt = new T.Vector3(0, 0.6, 0);
    var last = 0, lastScroll = -1;
    window.addEventListener('scroll', function () { lastScroll = performance.now(); }, { passive: true });
    var tick = function (now) {
      requestAnimationFrame(tick);
      if (!visible || document.hidden) return;
      // ~30fps saat diam, dan jeda render saat pengguna sedang menggulir agar scroll tetap mulus
      if (now - last < (now - lastScroll < 150 ? 45 : 33)) return;
      if (!first && now - lastScroll < 120 && Math.abs(prog - curProg) < 0.002) return;
      last = now;
      var t = clock.getElapsedTime();
      cur.x += (pointer.x - cur.x) * 0.05; cur.y += (pointer.y - cur.y) * 0.05; curProg += (prog - curProg) * 0.08;
      world.rotation.y = -0.18 + cur.x * 0.22 + curProg * 0.35;
      world.rotation.x = cur.y * 0.05;
      camera.position.set(camBase.x - curProg * 2, camBase.y - curProg * 4, camBase.z - curProg * 2);
      lookAt.y = 0.6 - curProg * 0.6;
      camera.lookAt(lookAt);
      trees.forEach(function (g) { g.rotation.z = Math.sin(t * 0.8 + g.userData.phase) * 0.018; });
      fishes.forEach(function (f) { var a = t * f.userData.sp + f.userData.ph; f.position.set(Math.cos(a) * f.userData.r * 1.15, 0.11, Math.sin(a) * f.userData.r * 0.8); f.rotation.y = -a; });
      water.material.opacity = 0.9 + Math.sin(t * 1.3) * 0.03;
      renderer.render(scene, camera);
      if (first) { first = false; box.classList.add('is-3d'); }
    };
    requestAnimationFrame(tick);
  }
})();
