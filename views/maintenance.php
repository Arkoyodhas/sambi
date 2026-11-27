<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>Sedang Pemeliharaan · <?= e($name) ?></title>
<style>
@font-face{font-family:"Plus Jakarta Sans";font-weight:400 800;font-display:swap;src:url("<?= e(asset('vendor/fonts/pjs-latin.woff2')) ?>") format("woff2")}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:"Plus Jakarta Sans",system-ui,sans-serif;background:#F4FBF9;color:#1E293B;display:grid;place-items:center;padding:24px;position:relative;overflow:hidden}
body::after{content:"";position:fixed;left:0;right:0;bottom:0;height:40vh;background:url("<?= e(asset('img/hero/hills.webp')) ?>") center bottom/cover no-repeat;opacity:.35;-webkit-mask-image:linear-gradient(180deg,transparent,#000 50%);mask-image:linear-gradient(180deg,transparent,#000 50%);pointer-events:none}
.card{position:relative;z-index:1;max-width:560px;background:#fff;border:1px solid #E3EFEB;border-radius:28px;padding:44px 40px;box-shadow:0 40px 80px -40px rgba(5,58,48,.45);animation:in .8s cubic-bezier(.22,1,.36,1) both}
@keyframes in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
.kicker{font-size:.75rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#00805F}
h1{font-size:clamp(1.8rem,5vw,2.4rem);line-height:1.15;letter-spacing:-.03em;margin:.6rem 0 1rem}
p{color:#64748B;line-height:1.7;margin:0 0 1rem}
.tools{display:flex;gap:10px;align-items:center;margin:0 0 1.4rem}
.gear{width:54px;height:54px;border-radius:16px;background:#E8F8F3;display:grid;place-items:center}
.gear svg{width:30px;height:30px;animation:spin 6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.until{display:inline-block;background:#FEF6E4;color:#B45309;font-weight:700;font-size:.85rem;padding:.4rem .8rem;border-radius:999px;margin-bottom:1rem}
a.btn{display:inline-flex;gap:.4rem;align-items:center;background:#00B589;color:#fff;text-decoration:none;font-weight:700;padding:.75rem 1.3rem;border-radius:999px}
@media (prefers-reduced-motion:reduce){.gear svg,.card{animation:none}}
</style>
</head>
<body>
<main class="card" data-testid="maintenance-page">
  <div class="tools"><span class="gear" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="#00B589" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg></span><span class="kicker">Pemeliharaan Sistem</span></div>
  <h1><?= e($name) ?> sedang dirapikan.</h1>
  <p><?= nl2br(e($msg)) ?></p>
  <?php if ($until): ?><span class="until">Perkiraan selesai: <?= e($until) ?></span><br><?php endif; ?>
  <?php if ($phone): ?><p>Butuh layanan mendesak? Hubungi kantor desa.</p><a class="btn" href="https://wa.me/<?= e(preg_replace('/\D/', '', normalize_phone_id((string) $phone) ?: (string) $phone)) ?>" rel="noopener" data-testid="maintenance-wa">Hubungi via WhatsApp</a><?php endif; ?>
</main>
</body>
</html>
