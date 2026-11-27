/* Zona Hiburan — interaksi dan animasi permainan publik. */
(function () {
  'use strict';

  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));
  const page = $('.ent-main');
  if (!page || page.dataset.enabled !== '1') return;

  const BASE = String((window.DESA && DESA.baseUrl) || '').replace(/\/$/, '');
  const stageWrap = $('#entStageWrap');
  const stage = $('#entStage');
  let profile = null;
  let toastTimer = null;
  let soundContext = null;
  let soundEnabled = true;
  let dailyChallenge = null;
  let challengeResolved = false;
  let activeTimer = null;
  let selectedAvatar = 'leaf';
  let selectedFrame = 'simple';

  const avatarIcons = {
    leaf: 'bi-tree-fill',
    sun: 'bi-sun-fill',
    smile: 'bi-emoji-smile-fill',
    star: 'bi-star-fill',
    book: 'bi-book-fill',
    camera: 'bi-camera-fill'
  };

  function readCookie(name) {
    const prefix = name + '=';
    const part = document.cookie.split(';').map((v) => v.trim()).find((v) => v.indexOf(prefix) === 0);
    if (!part) return '';
    try { return decodeURIComponent(part.slice(prefix.length)); } catch (_) { return ''; }
  }

  function persistToken(token) {
    try { localStorage.setItem('desa_visitor_token', token); } catch (_) {}
    try {
      const secure = location.protocol === 'https:' ? '; Secure' : '';
      document.cookie = 'desa_visitor_token=' + encodeURIComponent(token) + '; Max-Age=315360000; Path=/; SameSite=Lax' + secure;
    } catch (_) {}
  }

  function getToken() {
    let token = String((window.DESA && DESA.visitorToken) || '');
    try { if (!token) token = localStorage.getItem('desa_visitor_token') || ''; } catch (_) {}
    if (!token) token = readCookie('desa_visitor_token');
    if (!/^[a-f0-9]{64}$/i.test(token)) {
      try {
        const bytes = new Uint8Array(32);
        crypto.getRandomValues(bytes);
        token = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
      } catch (_) {
        token = '';
      }
    }
    if (/^[a-f0-9]{64}$/i.test(token)) {
      token = token.toLowerCase();
      persistToken(token);
      if (window.DESA) DESA.visitorToken = token;
      return token;
    }
    return '';
  }

  const token = getToken();

  function post(path, payload) {
    const body = Object.assign({ visitor_token: token }, payload || {});
    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    const timeout = controller ? setTimeout(() => controller.abort(), 12000) : null;
    return fetch(BASE + path, {
      method: 'POST',
      cache: 'no-store',
      credentials: 'same-origin',
      signal: controller ? controller.signal : undefined,
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(body)
    }).then(async (response) => {
      let data = {};
      try { data = await response.json(); } catch (_) {}
      if (!response.ok && !data.error) data.error = 'Permintaan gagal (' + response.status + ').';
      return data;
    }).catch((error) => {
      if (error && error.name === 'AbortError') return { ok: false, error: 'Permintaan terlalu lama. Silakan coba lagi.' };
      throw error;
    }).finally(() => {
      if (timeout) clearTimeout(timeout);
    });
  }

  function showToast(message) {
    const toast = $('#entToast');
    if (!toast) return;
    $('span', toast).textContent = String(message || '');
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.hidden = true; }, 3200);
  }

  function playTone(ok) {
    if (!soundEnabled) return;
    try {
      soundContext = soundContext || new (window.AudioContext || window.webkitAudioContext)();
      if (soundContext.state === 'suspended') soundContext.resume();
      const osc = soundContext.createOscillator();
      const gain = soundContext.createGain();
      osc.connect(gain);
      gain.connect(soundContext.destination);
      osc.type = 'sine';
      osc.frequency.value = ok ? 740 : 230;
      gain.gain.setValueAtTime(.001, soundContext.currentTime);
      gain.gain.exponentialRampToValueAtTime(.12, soundContext.currentTime + .02);
      gain.gain.exponentialRampToValueAtTime(.001, soundContext.currentTime + .25);
      osc.start();
      osc.stop(soundContext.currentTime + .27);
    } catch (_) {}
  }


  function prefersReducedMotion() {
    return document.documentElement.classList.contains('ent-comfort-mode')
      || !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function readPreference(key, fallback) {
    try {
      const value = localStorage.getItem(key);
      return value === null ? fallback : value === '1';
    } catch (_) {
      return fallback;
    }
  }

  function savePreference(key, value) {
    try { localStorage.setItem(key, value ? '1' : '0'); } catch (_) {}
  }

  function initComfortControls() {
    const comfortButton = $('#entComfortToggle');
    const soundButton = $('#entSoundToggle');
    const comfortEnabled = readPreference('desa_ent_comfort', false);
    soundEnabled = readPreference('desa_ent_sound', true);

    function applyComfort(enabled) {
      document.documentElement.classList.toggle('ent-comfort-mode', enabled);
      if (!comfortButton) return;
      comfortButton.setAttribute('aria-pressed', enabled ? 'true' : 'false');
      comfortButton.classList.toggle('active', enabled);
      const label = $('span', comfortButton);
      if (label) label.textContent = enabled ? 'Mode nyaman aktif' : 'Mode nyaman';
    }

    function applySound(enabled) {
      soundEnabled = enabled;
      if (!soundButton) return;
      soundButton.setAttribute('aria-pressed', enabled ? 'true' : 'false');
      soundButton.classList.toggle('active', enabled);
      const icon = $('i', soundButton);
      const label = $('span', soundButton);
      if (icon) icon.className = 'bi ' + (enabled ? 'bi-volume-up' : 'bi-volume-mute');
      if (label) label.textContent = enabled ? 'Suara aktif' : 'Suara mati';
    }

    applyComfort(comfortEnabled);
    applySound(soundEnabled);

    if (comfortButton) comfortButton.addEventListener('click', () => {
      const enabled = comfortButton.getAttribute('aria-pressed') !== 'true';
      savePreference('desa_ent_comfort', enabled);
      applyComfort(enabled);
    });
    if (soundButton) soundButton.addEventListener('click', () => {
      const enabled = soundButton.getAttribute('aria-pressed') !== 'true';
      savePreference('desa_ent_sound', enabled);
      applySound(enabled);
      if (enabled) playTone(true);
    });
  }

  function gameMessage(result, fallback) {
    let message = String((result && result.message) || fallback || '');
    if (result && result.streak_increased) {
      message += (message ? ' ' : '') + '🔥 Streak ' + Number(result.streak || 1) + ' hari.';
    }
    return message;
  }

  function animateNumber(element, target, formatter) {
    if (!element) return;
    const end = Math.max(0, Number(target || 0));
    if (prefersReducedMotion()) {
      element.textContent = formatter ? formatter(end) : String(end);
      return;
    }
    const from = Number(String(element.textContent || '0').replace(/[^\d.-]/g, '')) || 0;
    const started = performance.now();
    const duration = 650;
    function frame(now) {
      const progress = Math.min(1, (now - started) / duration);
      const eased = 1 - Math.pow(1 - progress, 3);
      const value = Math.round(from + (end - from) * eased);
      element.textContent = formatter ? formatter(value) : String(value);
      if (progress < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  function ensureConfettiLayer() {
    let layer = $('.ent-confetti-layer');
    if (!layer) {
      layer = document.createElement('div');
      layer.className = 'ent-confetti-layer';
      layer.setAttribute('aria-hidden', 'true');
      document.body.appendChild(layer);
    }
    return layer;
  }

  function burstConfetti(anchor, amount) {
    if (prefersReducedMotion()) return;
    const layer = ensureConfettiLayer();
    const rect = anchor && anchor.getBoundingClientRect ? anchor.getBoundingClientRect() : null;
    const originX = rect ? rect.left + rect.width / 2 : window.innerWidth / 2;
    const originY = rect ? rect.top + Math.min(rect.height / 2, 180) : window.innerHeight / 2;
    const colors = ['#ffd166', '#06d6a0', '#118ab2', '#ef476f', '#7c3aed', '#ffffff'];
    const total = Math.min(80, Math.max(18, Number(amount || 36)));
    for (let i = 0; i < total; i += 1) {
      const piece = document.createElement('i');
      piece.className = 'ent-confetti-piece';
      const angle = ((Math.PI * 2) / total) * i + (Math.random() * .7 - .35);
      const distance = 90 + Math.random() * 190;
      piece.style.setProperty('--ent-x', originX + 'px');
      piece.style.setProperty('--ent-y', originY + 'px');
      piece.style.setProperty('--ent-dx', Math.cos(angle) * distance + 'px');
      piece.style.setProperty('--ent-dy', (Math.sin(angle) * distance + 110 + Math.random() * 80) + 'px');
      piece.style.setProperty('--ent-size', (5 + Math.random() * 7) + 'px');
      piece.style.setProperty('--ent-color', colors[i % colors.length]);
      piece.style.setProperty('--ent-rotate', Math.round(Math.random() * 180) + 'deg');
      piece.style.setProperty('--ent-duration', (1.05 + Math.random() * .75) + 's');
      layer.appendChild(piece);
      setTimeout(() => piece.remove(), 2100);
    }
    setTimeout(() => {
      if (layer && !layer.children.length) layer.remove();
    }, 2300);
  }

  function sparkBurst(anchor, color) {
    if (prefersReducedMotion() || !anchor || !anchor.getBoundingClientRect) return;
    const rect = anchor.getBoundingClientRect();
    const x = rect.left + rect.width / 2;
    const y = rect.top + rect.height / 2;
    for (let i = 0; i < 10; i += 1) {
      const spark = document.createElement('i');
      spark.className = 'ent-spark';
      const angle = (Math.PI * 2 * i) / 10;
      const distance = 24 + Math.random() * 30;
      spark.style.left = x + 'px';
      spark.style.top = y + 'px';
      spark.style.setProperty('--ent-dx', Math.cos(angle) * distance + 'px');
      spark.style.setProperty('--ent-dy', Math.sin(angle) * distance + 'px');
      spark.style.setProperty('--ent-color', color || '#ffd166');
      document.body.appendChild(spark);
      setTimeout(() => spark.remove(), 850);
    }
  }

  function safeDate(value) {
    if (!value) return '';
    const d = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(d.getTime()) ? '' : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  function setAvatar(element, avatar, frame) {
    if (!element) return;
    const key = avatarIcons[avatar] ? avatar : 'leaf';
    const compact = element.classList.contains('ent-leader-avatar');
    element.className = 'ent-avatar' + (compact ? ' ent-leader-avatar' : '') + ' ent-frame-' + (frame || 'simple');
    element.innerHTML = '<i class="bi ' + avatarIcons[key] + '"></i>';
  }

  function renderProfileOptions(data) {
    const options = data.profile_options || {};
    selectedAvatar = data.avatar || 'leaf';
    selectedFrame = data.frame || 'simple';

    const avatarHolder = $('#entAvatarOptions');
    if (avatarHolder) {
      avatarHolder.innerHTML = '';
      (options.avatars || []).forEach((item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ent-avatar-choice' + (item.key === selectedAvatar ? ' active' : '');
        button.dataset.avatar = item.key;
        button.setAttribute('aria-label', item.label);
        button.innerHTML = '<i class="bi ' + item.icon + '"></i>';
        button.addEventListener('click', () => {
          selectedAvatar = item.key;
          $$('.ent-avatar-choice', avatarHolder).forEach((el) => el.classList.toggle('active', el === button));
        });
        avatarHolder.appendChild(button);
      });
    }

    const frameHolder = $('#entFrameOptions');
    if (frameHolder) {
      frameHolder.innerHTML = '';
      (options.frames || []).forEach((item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ent-frame-choice' + (item.key === selectedFrame ? ' active' : '');
        button.dataset.frame = item.key;
        button.textContent = item.label;
        button.addEventListener('click', () => {
          selectedFrame = item.key;
          $$('.ent-frame-choice', frameHolder).forEach((el) => el.classList.toggle('active', el === button));
        });
        frameHolder.appendChild(button);
      });
    }

    const badgeSelect = $('#entFeaturedBadgeSelect');
    if (badgeSelect) {
      badgeSelect.innerHTML = '<option value="">Tidak ditampilkan</option>';
      (options.badges || []).forEach((item) => {
        const option = document.createElement('option');
        option.value = item.key;
        option.textContent = item.label;
        option.selected = item.key === (data.featured_badge || '');
        badgeSelect.appendChild(option);
      });
    }
  }

  function renderProfile(data) {
    profile = data;
    dailyChallenge = data.daily_challenge || null;
    $('#entProfileLoading').hidden = true;
    $('#entProfileError').hidden = true;
    $('#entProfileContent').hidden = false;
    $('#entDisplayName').textContent = data.display_name;
    animateNumber($('#entPoints'), Number(data.points || 0), (value) => value.toLocaleString('id-ID'));
    animateNumber($('#entStreak'), Number(data.streak || 0));
    const streakLabel = $('#entStreakLabel');
    if (streakLabel) {
      streakLabel.textContent = data.streak_active_today ? 'Aktif hari ini' : 'Selesaikan 1 game';
    }
    $('#entRank').textContent = '#' + String(data.rank || '-');
    animateNumber($('#entBadgeCount'), Number((data.badges || []).length));
    $('#entNickname').value = data.nickname || '';

    setAvatar($('#entProfileAvatar'), data.avatar, data.frame);
    const featuredLabel = (data.badges || []).find((badge) => badge.key === data.featured_badge);
    const badgePill = $('#entFeaturedBadgePill');
    if (badgePill) {
      badgePill.textContent = featuredLabel ? featuredLabel.name : '';
      badgePill.hidden = !featuredLabel;
    }

    renderProfileOptions(data);
    renderMissions(data.missions || { items: [], done: 0, total: 0 });
    renderBadges(data.badges || []);
    renderGameStatuses(data.games || {});
    renderDailyChallenge(dailyChallenge);
    renderPoll(data.poll || null);
    renderStory(data.story || null);
    renderFact(data.fact || null);
    renderDailyBox(data.daily_box || null);
  }

  function renderDailyChallenge(challenge) {
    const config = challenge && challenge.game_config ? challenge.game_config : {};
    const memory = config.memory || {};
    const puzzle = config.puzzle || {};
    const chip = $('#entChallengeChip');
    const memoryDescription = $('#entMemoryDescription');
    const puzzleDescription = $('#entPuzzleDescription');

    if (chip) {
      chip.innerHTML = '<i class="bi bi-calendar2-check"></i> Tantangan harian';
      chip.title = 'Variasi permainan lokal berubah otomatis setiap hari.';
    }
    if (memoryDescription && memory.description) memoryDescription.textContent = memory.description;
    if (puzzleDescription) {
      const grid = Number(puzzle.grid || 3);
      puzzleDescription.textContent = (puzzle.tip || 'Susun potongan hingga menjadi gambar lengkap.')
        + ' Level ' + grid + '×' + grid + '.';
    }
  }

  function renderMissions(missions) {
    const holder = $('#entMissionList');
    holder.innerHTML = '';
    (missions.items || []).forEach((item) => {
      const row = document.createElement('div');
      row.className = 'ent-mission-item' + (item.done ? ' done' : '');
      row.style.animationDelay = Math.min(.28, holder.children.length * .06) + 's';
      const icon = document.createElement('i');
      icon.className = 'bi ' + (item.done ? 'bi-check-lg' : 'bi-circle');
      const label = document.createElement('span');
      label.textContent = item.title;
      row.append(icon, label);
      holder.appendChild(row);
    });
    $('#entMissionCount').textContent = String(missions.done || 0) + '/' + String(missions.total || 4);
    $('#entMissionBar').style.width = Math.min(100, Math.round((Number(missions.done || 0) / Math.max(1, Number(missions.total || 4))) * 100)) + '%';
  }

  function renderDailyBox(box) {
    const card = $('#entDailyBoxCard');
    const button = $('#entDailyBoxBtn');
    const text = $('#entDailyBoxText');
    if (!card || !button || !text) return;
    if (!box || box.enabled === false) {
      card.hidden = true;
      return;
    }
    card.hidden = false;
    card.classList.toggle('is-opened', !!box.claimed);
    if (box.claimed && box.reward) {
      text.textContent = box.reward.label + ' · +' + Number(box.reward.points || 0) + ' poin';
      button.disabled = true;
      button.innerHTML = '<i class="bi bi-check2 me-1"></i> Sudah dibuka';
    } else {
      text.textContent = 'Buka satu kotak untuk melihat hadiah hari ini.';
      button.disabled = false;
      button.innerHTML = '<i class="bi bi-box2-heart me-1"></i> Buka kotak';
    }
    button.onclick = () => {
      button.disabled = true;
      post('/hiburan/daily-box').then((result) => {
        if (!result.ok) throw new Error(result.error || 'Kotak belum dapat dibuka.');
        const reward = result.reward || {};
        text.textContent = String(reward.label || 'Hadiah harian') + ' · +' + Number(reward.points || 0) + ' poin';
        button.innerHTML = '<i class="bi bi-check2 me-1"></i> Sudah dibuka';
        card.classList.add('is-opened');
        if (!result.already_claimed) {
          burstConfetti(card, 28);
          playTone(true);
          showToast('Kamu mendapat +' + Number(reward.points || 0) + ' poin.');
        }
        loadProfile(true);
      }).catch((error) => {
        button.disabled = false;
        showToast(error.message);
      });
    };
  }

  function renderPoll(poll) {
    const question = $('#entPollQuestion');
    const holder = $('#entPollContent');
    const card = $('#entPollCard');
    if (!question || !holder || !card) return;
    holder.innerHTML = '';
    if (!poll) {
      card.hidden = true;
      return;
    }
    card.hidden = false;
    question.textContent = poll.question || 'Polling hari ini';

    if (poll.voted) {
      (poll.options || []).forEach((item) => {
        const row = document.createElement('div');
        row.className = 'ent-poll-result';
        const head = document.createElement('div');
        head.className = 'ent-poll-result-head';
        const label = document.createElement('span');
        label.textContent = item.label + (Number(item.id) === Number(poll.selected_option) ? ' · pilihanmu' : '');
        const percent = document.createElement('strong');
        percent.textContent = Number(item.percent || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%';
        head.append(label, percent);
        const bar = document.createElement('div');
        bar.className = 'ent-poll-bar';
        const fill = document.createElement('span');
        fill.style.width = Math.max(0, Math.min(100, Number(item.percent || 0))) + '%';
        bar.appendChild(fill);
        row.append(head, bar);
        holder.appendChild(row);
      });
      const meta = document.createElement('div');
      meta.className = 'ent-poll-meta';
      meta.textContent = Number(poll.total_votes || 0).toLocaleString('id-ID') + ' tanggapan';
      holder.appendChild(meta);
      return;
    }

    const options = document.createElement('div');
    options.className = 'ent-poll-options';
    (poll.options || []).forEach((item) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'ent-poll-option';
      const label = document.createElement('span');
      label.textContent = item.label;
      const icon = document.createElement('i');
      icon.className = 'bi bi-arrow-right';
      button.append(label, icon);
      button.addEventListener('click', () => {
        $$('.ent-poll-option', options).forEach((el) => { el.disabled = true; });
        post('/hiburan/poll-vote', { poll_id: poll.id, option_id: item.id }).then((result) => {
          if (!result.ok) throw new Error(result.error || 'Pilihan belum dapat disimpan.');
          renderPoll(result.poll);
          if (Number(result.points || 0) > 0) showToast('Tanggapan tersimpan · +' + result.points + ' poin');
          playTone(true);
          loadProfile(true);
        }).catch((error) => {
          $$('.ent-poll-option', options).forEach((el) => { el.disabled = false; });
          showToast(error.message);
        });
      });
      options.appendChild(button);
    });
    holder.appendChild(options);
  }

  function renderStory(story) {
    const card = $('#entStoryCard');
    const title = $('#entStoryTitle');
    const holder = $('#entStoryContent');
    if (!card || !title || !holder) return;
    holder.innerHTML = '';
    if (!story) {
      card.hidden = true;
      return;
    }
    card.hidden = false;
    title.textContent = story.node_title || story.title || 'Cerita interaktif';

    const copy = document.createElement('p');
    copy.className = 'ent-story-copy';
    copy.textContent = story.text || '';
    holder.appendChild(copy);

    if (story.completed) {
      const ending = document.createElement('div');
      ending.className = 'ent-story-ending';
      const text = document.createElement('small');
      text.textContent = 'Cerita selesai. Kamu dapat mencoba jalur yang berbeda.';
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-outline-primary btn-sm mt-3';
      button.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> Mulai lagi';
      button.addEventListener('click', () => {
        button.disabled = true;
        post('/hiburan/story-choose', { reset: true }).then((result) => {
          if (!result.ok) throw new Error(result.error || 'Cerita belum dapat dimulai ulang.');
          renderStory(result.story);
        }).catch((error) => {
          button.disabled = false;
          showToast(error.message);
        });
      });
      ending.append(text, button);
      holder.appendChild(ending);
      return;
    }

    const choices = document.createElement('div');
    choices.className = 'ent-story-choices';
    (story.choices || []).forEach((choice) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'ent-story-choice';
      const label = document.createElement('span');
      label.textContent = choice.label;
      const icon = document.createElement('i');
      icon.className = 'bi bi-chevron-right';
      button.append(label, icon);
      button.addEventListener('click', () => {
        $$('.ent-story-choice', choices).forEach((el) => { el.disabled = true; });
        post('/hiburan/story-choose', { choice: choice.key }).then((result) => {
          if (!result.ok) throw new Error(result.error || 'Pilihan belum dapat disimpan.');
          renderStory(result.story);
          if (result.story && result.story.completed) {
            playTone(true);
            if (Number(result.points || 0) > 0) showToast('Cerita selesai · +' + result.points + ' poin');
            loadProfile(true);
          }
        }).catch((error) => {
          $$('.ent-story-choice', choices).forEach((el) => { el.disabled = false; });
          showToast(error.message);
        });
      });
      choices.appendChild(button);
    });
    holder.appendChild(choices);
  }

  function renderFact(fact) {
    const card = $('#entFactCard');
    const category = $('#entFactCategory');
    const text = $('#entFactText');
    const button = $('#entNextFactBtn');
    if (!card || !category || !text || !button) return;
    if (!fact) {
      card.hidden = true;
      return;
    }
    card.hidden = false;
    category.textContent = fact.category || 'Fakta pilihan';
    text.textContent = fact.content || '';
    button.dataset.factId = String(fact.id || 0);
    button.disabled = false;
  }

  function renderBadges(items) {
    const holder = $('#entBadgeGrid');
    holder.innerHTML = '';
    if (!items.length) {
      const empty = document.createElement('div');
      empty.className = 'ent-empty';
      empty.textContent = 'Belum ada badge. Selesaikan aktivitas untuk membuka koleksi.';
      holder.appendChild(empty);
      return;
    }
    items.forEach((badge) => {
      const row = document.createElement('div');
      row.className = 'ent-badge-item';
      row.style.animationDelay = Math.min(.35, holder.children.length * .06) + 's';
      const icon = document.createElement('i');
      icon.className = 'bi ' + badge.icon;
      const copy = document.createElement('div');
      const strong = document.createElement('strong');
      strong.textContent = badge.name;
      const small = document.createElement('small');
      small.textContent = safeDate(badge.awarded_at);
      copy.append(strong, small);
      row.append(icon, copy);
      holder.appendChild(row);
    });
  }

  function renderGameStatuses(games) {
    Object.keys(games).forEach((key) => {
      const game = games[key] || {};
      const card = $('[data-game-card="' + key + '"]');
      const status = $('[data-game-status="' + key + '"]');
      const button = $('[data-open-game="' + key + '"]');
      if (!card || !status || !button) return;

      card.classList.toggle('disabled', game.enabled === false);
      button.disabled = game.enabled === false;
      if (game.enabled === false) {
        status.textContent = 'Dinonaktifkan admin';
        status.classList.remove('done');
        return;
      }

      if (key === 'quiz') {
        status.textContent = game.completed ? (game.correct ? 'Selesai · Jawaban benar' : 'Selesai · Coba lagi besok') : 'Belum dimainkan';
      } else if (key === 'wheel') {
        status.textContent = game.completed && game.prize ? 'Hari ini: +' + game.prize.prize_points + ' poin' : 'Belum diputar';
      } else {
        status.textContent = game.completed ? 'Skor terbaik hari ini: ' + Number(game.best_score || 0) : 'Belum dimainkan';
      }
      status.classList.toggle('done', !!game.completed);
    });
  }

  function renderLeaderboard(items, enabled) {
    const holder = $('#entLeaderboard');
    holder.innerHTML = '';
    if (!enabled) {
      const empty = document.createElement('div');
      empty.className = 'ent-empty';
      empty.textContent = 'Papan peringkat sedang dinonaktifkan admin.';
      holder.appendChild(empty);
      return;
    }
    if (!items || !items.length) {
      const empty = document.createElement('div');
      empty.className = 'ent-empty';
      empty.textContent = 'Belum ada pemain. Jadilah yang pertama!';
      holder.appendChild(empty);
      return;
    }
    items.forEach((item, index) => {
      const row = document.createElement('div');
      row.className = 'ent-leader-row';
      row.style.animationDelay = Math.min(.45, index * .045) + 's';
      const rank = document.createElement('div');
      rank.className = 'ent-leader-rank';
      rank.textContent = String(index + 1);
      const avatar = document.createElement('div');
      avatar.className = 'ent-leader-avatar';
      setAvatar(avatar, item.avatar || 'leaf', item.frame || 'simple');

      const name = document.createElement('div');
      name.className = 'ent-leader-name';
      const strong = document.createElement('strong');
      strong.textContent = item.name;
      const small = document.createElement('small');
      small.textContent = '🔥 ' + Number(item.streak || 0) + ' hari · ' + Number(item.badges || 0) + ' badge';
      name.append(strong, small);
      const points = document.createElement('div');
      points.className = 'ent-leader-points';
      points.textContent = Number(item.points || 0).toLocaleString('id-ID') + ' poin';
      row.append(rank, avatar, name, points);
      holder.appendChild(row);
    });
  }

  function loadProfile(silent) {
    if (!token) {
      $('#entProfileLoading').hidden = true;
      $('#entProfileError').hidden = false;
      $('#entProfileError').textContent = 'Profil pemain belum dapat disiapkan. Muat ulang halaman lalu coba kembali.';
      return Promise.resolve();
    }
    return post('/hiburan/profile').then((data) => {
      if (!data.ok) throw new Error(data.error || 'Profil belum dapat dimuat.');
      renderProfile(data.profile);
      renderLeaderboard(data.leaderboard || [], data.leaderboard_enabled !== false);
    }).catch((error) => {
      if (!silent) {
        $('#entProfileLoading').hidden = true;
        $('#entProfileError').hidden = false;
        $('#entProfileError').textContent = error.message || 'Koneksi ke Zona Hiburan gagal.';
      }
    });
  }

  function refreshLeaderboard() {
    const button = $('#entRefreshLeaderboard');
    if (button) button.classList.add('is-loading');
    post('/hiburan/leaderboard').then((data) => {
      if (!data.ok) throw new Error(data.error || 'Gagal memuat peringkat.');
      renderLeaderboard(data.items || [], data.enabled !== false);
      showToast('Peringkat terbaru berhasil dimuat.');
    }).catch((error) => showToast(error.message)).finally(() => {
      if (button) button.classList.remove('is-loading');
    });
  }

  function ensureDailyChallenge() {
    if (challengeResolved) return Promise.resolve(dailyChallenge);
    challengeResolved = true;
    return post('/hiburan/challenge').then((result) => {
      if (result && result.ok && result.challenge) {
        dailyChallenge = result.challenge;
        renderDailyChallenge(dailyChallenge);
      }
      return dailyChallenge;
    }).catch(() => dailyChallenge);
  }

  function openStage(title, eyebrow) {
    if (activeTimer) {
      clearInterval(activeTimer);
      activeTimer = null;
    }
    $('#entStageTitle').textContent = title;
    $('#entStageEyebrow').textContent = eyebrow || 'Permainan';
    stage.innerHTML = '';
    stageWrap.hidden = false;
    stageWrap.classList.remove('is-opening');
    requestAnimationFrame(() => {
      stageWrap.classList.add('is-opening');
      stageWrap.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
    });
  }

  function closeStage() {
    if (activeTimer) {
      clearInterval(activeTimer);
      activeTimer = null;
    }
    stageWrap.classList.remove('is-opening');
    stageWrap.hidden = true;
    stage.innerHTML = '';
  }

  function gameResult(title, text) {
    const box = document.createElement('div');
    box.className = 'ent-game-result';
    const strong = document.createElement('strong');
    strong.textContent = title;
    const p = document.createElement('p');
    p.textContent = text;
    box.append(strong, p);
    return box;
  }

  function startQuiz() {
    openStage('Kuis Harian', 'Uji pengetahuan');
    stage.innerHTML = '<div class="ent-list-loading"><span class="spinner-border spinner-border-sm"></span> Menyiapkan soal hari ini…</div>';
    post('/hiburan/quiz').then((data) => {
      if (!data.ok) throw new Error(data.error || 'Soal belum dapat dimuat.');
      stage.innerHTML = '';
      const shell = document.createElement('div');
      shell.className = 'ent-game-shell';

      if (data.completed) {
        shell.appendChild(gameResult(data.correct ? 'Jawaban hari ini benar!' : 'Kuis hari ini sudah selesai', (data.explanation || '') + ' Poin: +' + Number(data.points || 0)));
        stage.appendChild(shell);
        return;
      }

      const quizMeta = document.createElement('div');
      quizMeta.className = 'ent-quiz-context';
      const quizMetaIcon = document.createElement('i');
      quizMetaIcon.className = 'bi bi-bookmark-check';
      const quizMetaText = document.createElement('span');
      quizMetaText.textContent = 'Soal pilihan · ' + String(data.question.category || 'Pengetahuan')
        + ' · ' + String(data.question.difficulty || 'mudah');
      quizMeta.append(quizMetaIcon, quizMetaText);

      const question = document.createElement('h3');
      question.className = 'ent-quiz-question';
      question.textContent = data.question.text;
      const options = document.createElement('div');
      options.className = 'ent-quiz-options';

      Object.keys(data.question.options).forEach((key) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ent-quiz-option';
        button.dataset.answer = key;
        const letter = document.createElement('span');
        letter.className = 'letter';
        letter.textContent = key;
        const text = document.createElement('span');
        text.textContent = data.question.options[key];
        button.append(letter, text);
        button.addEventListener('click', () => {
          $$('.ent-quiz-option', options).forEach((b) => { b.disabled = true; });
          post('/hiburan/quiz-answer', { question_id: data.question.id, answer: key }).then((result) => {
            if (!result.ok) throw new Error(result.error || 'Jawaban belum dapat disimpan.');
            const selected = $('[data-answer="' + key + '"]', options);
            const correct = $('[data-answer="' + result.correct_option + '"]', options);
            if (correct) correct.classList.add('correct');
            if (!result.correct && selected) selected.classList.add('wrong');
            shell.appendChild(gameResult(
              result.correct ? 'Benar! +' + result.points + ' poin' : 'Belum tepat · +' + result.points + ' poin',
              gameMessage(result, result.explanation || 'Coba lagi pada kuis besok.')
            ));
            if (result.correct) {
              sparkBurst(correct || selected, '#06d6a0');
              burstConfetti(stage, 34);
            }
            playTone(!!result.correct);
            loadProfile(true);
          }).catch((error) => {
            $$('.ent-quiz-option', options).forEach((b) => { b.disabled = false; });
            showToast(error.message);
          });
        });
        options.appendChild(button);
      });
      shell.append(quizMeta, question, options);
      stage.appendChild(shell);
    }).catch((error) => {
      stage.innerHTML = '';
      stage.appendChild(gameResult('Kuis belum tersedia', error.message));
    });
  }

  function shuffle(array) {
    const a = array.slice();
    for (let i = a.length - 1; i > 0; i -= 1) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  function startMemory() {
    const config = (dailyChallenge && dailyChallenge.game_config && dailyChallenge.game_config.memory) || {};
    openStage(config.title || 'Memory Card', 'Cocokkan semua pasangan');
    const gameSession = post('/hiburan/game-start', { game: 'memory' });
    const shell = document.createElement('div');
    shell.className = 'ent-game-shell';
    const meta = document.createElement('div');
    meta.className = 'ent-game-meta';
    const movesEl = document.createElement('span');
    const timeEl = document.createElement('span');
    const levelEl = document.createElement('span');
    movesEl.textContent = 'Langkah: 0';
    timeEl.textContent = 'Waktu: 0 dtk';

    const fallbackSymbols = ['🌾', '🏡', '🌳', '🐟', '⭐', '🎨', '🌻', '💧'];
    const sourceSymbols = Array.isArray(config.symbols) ? config.symbols : fallbackSymbols;
    const uniqueSymbols = sourceSymbols
      .map((symbol) => String(symbol || '').trim())
      .filter((symbol, index, array) => symbol && array.indexOf(symbol) === index);
    const pairCount = Math.max(4, Math.min(8, Number(config.pair_count || 6), uniqueSymbols.length || 6));
    const symbols = (uniqueSymbols.length >= pairCount ? uniqueSymbols : fallbackSymbols).slice(0, pairCount);
    levelEl.textContent = 'Level: ' + pairCount + ' pasang';
    meta.append(movesEl, timeEl, levelEl);

    const context = document.createElement('p');
    context.className = 'ent-game-context';
    context.textContent = config.description || 'Temukan seluruh pasangan dengan langkah sesedikit mungkin.';

    const board = document.createElement('div');
    board.className = 'ent-memory-board';
    board.style.setProperty('--ent-memory-columns', pairCount <= 4 ? '4' : '4');
    const cards = shuffle(symbols.concat(symbols));
    let first = null;
    let lock = false;
    let matched = 0;
    let moves = 0;
    let seconds = 0;
    let completed = false;

    function finish() {
      if (completed) return;
      completed = true;
      clearInterval(activeTimer);
      activeTimer = null;
      const timePenalty = seconds * (pairCount > 6 ? 0.35 : 0.55);
      const movePenalty = Math.max(0, moves - pairCount) * 7;
      const score = Math.max(10, Math.min(100, Math.round(100 - movePenalty - timePenalty)));
      gameSession.then((session) => {
        if (!session.ok || !session.session_token) throw new Error(session.error || 'Sesi permainan tidak tersedia.');
        return post('/hiburan/game-complete', {
          game: 'memory', score, moves, seconds, session_token: session.session_token
        });
      }).then((result) => {
        if (!result.ok) throw new Error(result.error || 'Hasil belum dapat disimpan.');
        shell.appendChild(gameResult(
          'Memory selesai · skor ' + score,
          gameMessage(result, '+' + Number(result.points || 0) + ' poin')
        ));
        burstConfetti(board, 46);
        playTone(true);
        loadProfile(true);
      }).catch((error) => {
        completed = false;
        showToast(error.message);
      });
    }

    cards.forEach((symbol, index) => {
      const card = document.createElement('button');
      card.type = 'button';
      card.className = 'ent-memory-card';
      card.dataset.index = String(index);
      card.dataset.symbol = symbol;
      card.setAttribute('aria-label', 'Kartu tertutup');
      const front = document.createElement('span');
      front.className = 'front';
      front.innerHTML = '<i class="bi bi-question-lg"></i>';
      const back = document.createElement('span');
      back.className = 'back';
      back.textContent = symbol;
      card.append(front, back);
      card.addEventListener('click', () => {
        if (lock || completed || card.classList.contains('open') || card.classList.contains('matched')) return;
        if (!activeTimer) activeTimer = setInterval(() => {
          seconds += 1;
          timeEl.textContent = 'Waktu: ' + seconds + ' dtk';
        }, 1000);
        card.classList.add('open');
        card.setAttribute('aria-label', 'Kartu ' + symbol);
        if (!first) {
          first = card;
          return;
        }
        moves += 1;
        movesEl.textContent = 'Langkah: ' + moves;
        if (first.dataset.symbol === card.dataset.symbol) {
          first.classList.add('matched');
          card.classList.add('matched');
          sparkBurst(card, '#8b5cf6');
          first = null;
          matched += 2;
          playTone(true);
          if (matched === cards.length) finish();
        } else {
          const previous = first;
          first = null;
          lock = true;
          setTimeout(() => {
            previous.classList.remove('open');
            card.classList.remove('open');
            previous.setAttribute('aria-label', 'Kartu tertutup');
            card.setAttribute('aria-label', 'Kartu tertutup');
            lock = false;
          }, prefersReducedMotion() ? 250 : 650);
        }
      });
      board.appendChild(card);
    });

    shell.append(meta, context, board);
    stage.appendChild(shell);
  }

  function startPuzzle() {
    const config = (dailyChallenge && dailyChallenge.game_config && dailyChallenge.game_config.puzzle) || {};
    const requestedGrid = Number(config.grid || 3);
    const grid = requestedGrid === 4 ? 4 : 3;
    openStage(config.title || 'Puzzle Foto', 'Susun kembali gambar');
    const gameSession = post('/hiburan/game-start', { game: 'puzzle' });
    const shell = document.createElement('div');
    shell.className = 'ent-game-shell';
    const meta = document.createElement('div');
    meta.className = 'ent-game-meta';
    const movesEl = document.createElement('span');
    const timeEl = document.createElement('span');
    const levelEl = document.createElement('span');
    movesEl.textContent = 'Langkah: 0';
    timeEl.textContent = 'Waktu: 0 dtk';
    levelEl.textContent = 'Level: ' + grid + '×' + grid;
    meta.append(movesEl, timeEl, levelEl);

    const context = document.createElement('p');
    context.className = 'ent-game-context';
    context.textContent = config.tip || 'Mulai dari potongan sudut, lalu cocokkan garis dan warna.';

    const board = document.createElement('div');
    board.className = 'ent-puzzle-board';
    board.style.setProperty('--ent-puzzle-grid', String(grid));
    const image = String(stage.dataset.puzzleImage || '');
    const tileCount = grid * grid;
    let tiles = shuffle(Array.from({ length: tileCount }, (_, index) => index));
    if (tiles.every((value, index) => value === index)) [tiles[0], tiles[1]] = [tiles[1], tiles[0]];
    let selected = null;
    let moves = 0;
    let seconds = 0;
    let completed = false;

    function render() {
      board.innerHTML = '';
      tiles.forEach((value, position) => {
        const tile = document.createElement('button');
        tile.type = 'button';
        tile.className = 'ent-puzzle-tile' + (selected === position ? ' selected' : '');
        tile.dataset.position = String(position);
        tile.dataset.number = String(value + 1);
        tile.setAttribute('aria-label', 'Potongan ' + (value + 1));
        tile.style.backgroundImage = 'url(' + JSON.stringify(image) + ')';
        tile.style.backgroundSize = (grid * 100) + '% ' + (grid * 100) + '%';
        const divisor = Math.max(1, grid - 1);
        tile.style.backgroundPosition = (((value % grid) / divisor) * 100) + '% '
          + ((Math.floor(value / grid) / divisor) * 100) + '%';
        tile.addEventListener('click', () => choose(position));
        board.appendChild(tile);
      });
    }

    function choose(position) {
      if (completed) return;
      if (!activeTimer) activeTimer = setInterval(() => {
        seconds += 1;
        timeEl.textContent = 'Waktu: ' + seconds + ' dtk';
      }, 1000);
      if (selected === null) {
        selected = position;
        render();
        return;
      }
      if (selected === position) {
        selected = null;
        render();
        return;
      }
      const firstPosition = selected;
      [tiles[selected], tiles[position]] = [tiles[position], tiles[selected]];
      selected = null;
      moves += 1;
      movesEl.textContent = 'Langkah: ' + moves;
      render();
      [firstPosition, position].forEach((index) => {
        const tile = board.children[index];
        if (tile) {
          tile.classList.add('ent-tile-swap');
          setTimeout(() => tile.classList.remove('ent-tile-swap'), prefersReducedMotion() ? 0 : 320);
        }
      });
      if (tiles.every((value, index) => value === index)) finish();
    }

    function finish() {
      if (completed) return;
      completed = true;
      clearInterval(activeTimer);
      activeTimer = null;
      const moveWeight = grid === 4 ? 1.35 : 3;
      const timeWeight = grid === 4 ? 0.28 : 0.7;
      const score = Math.max(10, Math.min(100, Math.round(110 - moves * moveWeight - seconds * timeWeight)));
      gameSession.then((session) => {
        if (!session.ok || !session.session_token) throw new Error(session.error || 'Sesi permainan tidak tersedia.');
        return post('/hiburan/game-complete', {
          game: 'puzzle', score, moves, seconds, session_token: session.session_token
        });
      }).then((result) => {
        if (!result.ok) throw new Error(result.error || 'Hasil belum dapat disimpan.');
        shell.appendChild(gameResult(
          'Puzzle selesai · skor ' + score,
          gameMessage(result, '+' + Number(result.points || 0) + ' poin')
        ));
        burstConfetti(board, 48);
        playTone(true);
        loadProfile(true);
      }).catch((error) => {
        completed = false;
        showToast(error.message);
      });
    }

    render();
    shell.append(meta, context, board);
    stage.appendChild(shell);
  }

  function startWheel() {
    const config = (dailyChallenge && dailyChallenge.game_config && dailyChallenge.game_config.wheel) || {};
    openStage(config.title || 'Roda Kejutan', 'Satu putaran per hari');
    const shell = document.createElement('div');
    shell.className = 'ent-game-shell ent-wheel-shell';

    const wrap = document.createElement('div');
    wrap.className = 'ent-wheel-wrap';
    const pointer = document.createElement('div');
    pointer.className = 'ent-wheel-pointer';
    const wheel = document.createElement('div');
    wheel.className = 'ent-wheel';
    wrap.append(pointer, wheel);

    const info = document.createElement('div');
    info.className = 'ent-wheel-info';
    const title = document.createElement('h3');
    title.textContent = config.title || 'Roda Hadiah Harian';
    const text = document.createElement('p');
    text.textContent = 'Putar roda dan lihat kejutan poin yang kamu dapatkan hari ini!';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-primary btn-lg';
    button.innerHTML = '<i class="bi bi-disc me-1"></i> Putar Sekarang';
    info.append(title, text, button);
    shell.append(wrap, info);
    stage.appendChild(shell);

    if (profile && profile.games && profile.games.wheel && profile.games.wheel.completed) {
      button.disabled = true;
      const prize = profile.games.wheel.prize;
      info.appendChild(gameResult('Sudah diputar hari ini', prize ? prize.prize_label + ' · +' + prize.prize_points + ' poin' : 'Kembali lagi besok.'));
      return;
    }

    button.addEventListener('click', () => {
      button.disabled = true;
      post('/hiburan/wheel').then((result) => {
        if (!result.ok) throw new Error(result.error || 'Roda belum dapat diputar.');
        const turns = 6 + Math.floor(Math.random() * 3);
        const segmentAngle = 72;
        const target = turns * 360 + (result.segment * segmentAngle) + 30;
        wheel.style.transform = 'rotate(' + target + 'deg)';
        setTimeout(() => {
          info.appendChild(gameResult(result.label, gameMessage(result, 'Bonus +' + result.points + ' poin')));
          burstConfetti(wrap, 60);
          playTone(true);
          loadProfile(true);
        }, 4300);
      }).catch((error) => {
        button.disabled = false;
        info.appendChild(gameResult('Belum dapat diputar', error.message));
      });
    });
  }


  $$('[data-open-game]').forEach((button) => {
    button.addEventListener('click', () => {
      sparkBurst(button, '#ffd166');
      const game = button.dataset.openGame;
      const launch = () => {
        if (game === 'quiz') startQuiz();
        if (game === 'memory') startMemory();
        if (game === 'puzzle') startPuzzle();
        if (game === 'wheel') startWheel();
      };

      if (game === 'quiz') {
        launch();
        return;
      }

      button.disabled = true;
      ensureDailyChallenge()
        .then(launch)
        .finally(() => { button.disabled = false; });
    });
  });

  const closeStageButton = $('#entCloseStage');
  const refreshLeaderboardButton = $('#entRefreshLeaderboard');
  if (closeStageButton) closeStageButton.addEventListener('click', closeStage);
  if (refreshLeaderboardButton) refreshLeaderboardButton.addEventListener('click', refreshLeaderboard);

  const renameModalEl = $('#entRenameModal');
  function openProfileModal() {
    const errorBox = $('#entRenameError');
    if (errorBox) errorBox.hidden = true;
    if (!renameModalEl || !window.bootstrap || !bootstrap.Modal) {
      showToast('Panel profil belum dapat dibuka. Muat ulang halaman.');
      return;
    }
    if (profile) renderProfileOptions(profile);
    bootstrap.Modal.getOrCreateInstance(renameModalEl).show();
    setTimeout(() => {
      const nicknameInput = $('#entNickname');
      if (nicknameInput) nicknameInput.focus();
    }, 200);
  }

  const renameButton = $('#entRenameBtn');
  if (renameButton) renameButton.addEventListener('click', openProfileModal);
  $$('[data-open-profile]').forEach((button) => button.addEventListener('click', openProfileModal));

  $('#entRenameForm').addEventListener('submit', (event) => {
    event.preventDefault();
    const nickname = $('#entNickname').value.trim();
    const featured = $('#entFeaturedBadgeSelect').value;
    const errorBox = $('#entRenameError');
    const submit = event.submitter;
    errorBox.hidden = true;
    if (submit) submit.disabled = true;
    post('/hiburan/profile-customize', {
      nickname,
      avatar: selectedAvatar,
      frame: selectedFrame,
      featured_badge: featured
    }).then((result) => {
      if (!result.ok) throw new Error(result.error || 'Profil belum dapat disimpan.');
      $('#entDisplayName').textContent = result.display_name;
      setAvatar($('#entProfileAvatar'), result.avatar, result.frame);
      const modalInstance = window.bootstrap && bootstrap.Modal ? bootstrap.Modal.getInstance(renameModalEl) : null;
      if (modalInstance) modalInstance.hide();
      showToast('Profil berhasil diperbarui.');
      loadProfile(true);
    }).catch((error) => {
      errorBox.textContent = error.message;
      errorBox.hidden = false;
    }).finally(() => {
      if (submit) submit.disabled = false;
    });
  });

  const nextFactButton = $('#entNextFactBtn');
  if (nextFactButton) {
    nextFactButton.addEventListener('click', () => {
      nextFactButton.disabled = true;
      post('/hiburan/fact', { exclude_id: Number(nextFactButton.dataset.factId || 0) }).then((result) => {
        if (!result.ok) throw new Error(result.error || 'Fakta berikutnya belum dapat dimuat.');
        renderFact(result.fact);
        if (Number(result.points || 0) > 0) {
          showToast('Fakta baru dibaca · +' + result.points + ' poin');
          loadProfile(true);
        }
      }).catch((error) => {
        nextFactButton.disabled = false;
        showToast(error.message);
      });
    });
  }

  $$('.ent-section-nav a').forEach((link) => {
    link.addEventListener('click', () => {
      $$('.ent-section-nav a').forEach((item) => item.classList.toggle('active', item === link));
    });
  });

  document.addEventListener('pointerdown', () => {
    try { if (soundEnabled && soundContext && soundContext.state === 'suspended') soundContext.resume(); } catch (_) {}
  }, { once: true });

  initComfortControls();
  loadProfile(false);
}());
