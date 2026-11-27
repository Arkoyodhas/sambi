    </main>
  </div>
</div>
<div id="effect-layer"></div>
<script>
window.DESA = { baseUrl: <?= json_encode(base_url()) ?>, page: 'admin', photoProtection: false };
window.DESA_ADMIN = true;
window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
</script>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/admin.js') ?>"></script>
<script>
/* Sidebar sticky: simpan posisi scroll sebelum pindah menu, lalu restore setelah halaman baru dimuat.
   Ini mencegah user harus scroll ulang dari atas setiap kali klik menu. */
(function(){
  var nav = document.querySelector('.sidebar-nav'); if (!nav) return;
  var KEY = 'desa_admin_sidebar_scroll';
  // Restore
  var saved = parseInt(sessionStorage.getItem(KEY) || '0', 10);
  if (saved > 0) { requestAnimationFrame(function(){ nav.scrollTop = saved; }); }
  // Save on link click
  nav.addEventListener('click', function(e){
    var a = e.target.closest('a'); if (!a || !a.getAttribute('href')) return;
    sessionStorage.setItem(KEY, String(nav.scrollTop));
  });
  // Save on scroll (throttled) — supaya bila user membuka via keyboard shortcut posisi terjaga
  var t = 0;
  nav.addEventListener('scroll', function(){
    clearTimeout(t); t = setTimeout(function(){ sessionStorage.setItem(KEY, String(nav.scrollTop)); }, 150);
  }, {passive:true});
})();
</script>
<script>
(function(){
  var links=[].slice.call(document.querySelectorAll('.sidebar-nav a[href]'));
  if(document.querySelector('.sidebar-nav a.active'))return;
  var path=location.pathname.replace(/\/$/,''),best=null,len=0;
  links.forEach(function(a){var p=a.pathname.replace(/\/$/,'');if(p&&(path===p||path.indexOf(p+'/')===0)&&p.length>len){best=a;len=p.length;}});
  if(best)best.classList.add('active');
})();
</script>
<?php if (isset($inlineScript)) echo $inlineScript; ?>
</body>
</html>
