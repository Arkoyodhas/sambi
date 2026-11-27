/* Face scan helper: local-first model, challenge liveness, and multi-template enrollment. */
(function (window) {
  'use strict';

  const DEFAULTS = {
    localHumanUrl: '',
    localModelPath: '',
    cdnHumanUrl: 'https://cdn.jsdelivr.net/npm/@vladmandic/human@3.3.6/dist/human.esm.js',
    cdnModelPath: 'https://cdn.jsdelivr.net/npm/@vladmandic/human@3.3.6/models/'
  };
  const EXPECTED_DESCRIPTOR_SIZE = 1024;
  const MIN_LOGIN_SAMPLE_SIMILARITY = 0.760;
  const MIN_ENROLL_SAMPLE_SIMILARITY = 0.720;
  const MIN_FACE_SCORE = 0.62;
  const MIN_REAL_SCORE = 0.50;
  const MIN_LIVE_SCORE = 0.50;
  const MAX_MODEL_LOAD_MS = 45000;
  let config = Object.assign({}, DEFAULTS, window.FACE_AUTH_CONFIG || {});
  let humanPromise = null;
  let activeModelSource = '';

  function configure(next) {
    config = Object.assign({}, config, next || {});
  }

  function setStatus(el, text, type) {
    if (!el) return;
    el.textContent = text;
    el.classList.add('face-scan-status');
    ['muted', 'primary', 'success', 'warning', 'danger'].forEach(function (name) {
      el.classList.remove('text-' + name);
    });
    el.classList.add('text-' + (type || 'muted'));
    el.dataset.statusType = type || 'muted';
  }

  function notifyStage(options, stage, detail) {
    if (options && typeof options.onStage === 'function') {
      try { options.onStage(stage, detail || {}); } catch (e) { /* callback UI tidak boleh menghentikan scan */ }
    }
  }

  function withTimeout(promise, ms, message) {
    return Promise.race([
      promise,
      new Promise(function (_, reject) {
        window.setTimeout(function () { reject(new Error(message)); }, ms);
      })
    ]);
  }

  async function importHuman() {
    const candidates = [];
    if (config.localHumanUrl) {
      candidates.push({ module: config.localHumanUrl, models: config.localModelPath, source: 'local' });
    }
    if (config.cdnHumanUrl) {
      candidates.push({ module: config.cdnHumanUrl, models: config.cdnModelPath, source: 'cdn' });
    }

    let lastError = null;
    for (const candidate of candidates) {
      try {
        const mod = await withTimeout(import(candidate.module), MAX_MODEL_LOAD_MS, 'Model wajah terlalu lama dimuat.');
        const HumanClass = mod.Human || (mod.default && mod.default.Human) || mod.default;
        if (typeof HumanClass !== 'function') throw new Error('Modul Human tidak kompatibel.');
        activeModelSource = candidate.source;
        return { HumanClass: HumanClass, modelPath: candidate.models };
      } catch (error) {
        lastError = error;
      }
    }
    throw lastError || new Error('Modul pemindai wajah tidak tersedia.');
  }

  function humanOptions(modelPath, backend) {
    return {
      backend: backend,
      modelBasePath: modelPath,
      cacheSensitivity: 0.72,
      face: {
        enabled: true,
        detector: {
          enabled: true,
          rotation: false,
          maxDetected: 2,
          minConfidence: 0.62,
          return: true
        },
        mesh: { enabled: true },
        iris: { enabled: true },
        description: { enabled: true },
        antispoof: { enabled: true },
        liveness: { enabled: true },
        emotion: { enabled: false }
      },
      body: { enabled: false },
      hand: { enabled: false },
      object: { enabled: false },
      gesture: { enabled: true }
    };
  }

  async function createHuman(HumanClass, modelPath) {
    const backends = ['webgl', 'wasm'];
    let lastError = null;
    for (const backend of backends) {
      try {
        const human = new HumanClass(humanOptions(modelPath, backend));
        await withTimeout(human.load(), MAX_MODEL_LOAD_MS, 'Model wajah terlalu lama dimuat.');
        await withTimeout(human.warmup(), MAX_MODEL_LOAD_MS, 'Pemanasan model wajah gagal.');
        return human;
      } catch (error) {
        lastError = error;
      }
    }
    throw lastError || new Error('Backend pemindai wajah tidak tersedia.');
  }

  async function loadHuman(statusEl) {
    if (!humanPromise) {
      humanPromise = (async function () {
        if (statusEl) setStatus(statusEl, 'Menyiapkan pengenal wajah…', 'muted');
        const imported = await importHuman();
        return createHuman(imported.HumanClass, imported.modelPath);
      })().catch(function (error) {
        humanPromise = null;
        throw error;
      });
    }
    return humanPromise;
  }

  function preload() {
    return loadHuman(null).catch(function () { return null; });
  }

  function normalize(vector) {
    const values = Array.from(vector || [], Number);
    if (values.length !== EXPECTED_DESCRIPTOR_SIZE) return null;
    let sum = 0;
    for (const value of values) {
      if (!Number.isFinite(value)) return null;
      sum += value * value;
    }
    const norm = Math.sqrt(sum);
    if (!Number.isFinite(norm) || norm < 0.000001) return null;
    return values.map(function (value) { return value / norm; });
  }

  function average(samples) {
    if (!samples.length) return null;
    const output = new Array(samples[0].length).fill(0);
    samples.forEach(function (sample) {
      sample.forEach(function (value, index) { output[index] += value; });
    });
    return normalize(output.map(function (value) { return value / samples.length; }));
  }

  function cosine(a, b) {
    if (!a || !b || a.length !== EXPECTED_DESCRIPTOR_SIZE || b.length !== EXPECTED_DESCRIPTOR_SIZE) return -1;
    let dot = 0;
    for (let index = 0; index < a.length; index += 1) dot += a[index] * b[index];
    return Math.max(-1, Math.min(1, dot));
  }

  function gestureNames(result) {
    return (result.gesture || []).map(function (item) {
      return String(item.gesture || item.name || item || '').toLowerCase();
    });
  }

  function yawOf(face) {
    const rotation = face && face.rotation ? face.rotation : {};
    const angle = rotation.angle || {};
    let value = angle.yaw;
    if (!Number.isFinite(Number(value)) && Array.isArray(angle)) value = angle[1];
    if (!Number.isFinite(Number(value))) value = rotation.yaw;
    value = Number(value);
    if (!Number.isFinite(value)) return null;
    // Human dapat mengembalikan radian atau derajat tergantung backend.
    return Math.abs(value) <= 3.2 ? value : value * Math.PI / 180;
  }

  function hasGesture(result, fragments) {
    const names = gestureNames(result);
    return names.some(function (name) {
      return fragments.some(function (fragment) { return name.indexOf(fragment) !== -1; });
    });
  }

  function challengeDone(step, result, face) {
    const yaw = yawOf(face);
    if (step === 'blink') return hasGesture(result, ['blink', 'kedip']);
    if (step === 'turn_left') {
      return hasGesture(result, ['head left', 'turn left', 'look left', 'kiri']) || (yaw !== null && yaw <= -0.13);
    }
    if (step === 'turn_right') {
      return hasGesture(result, ['head right', 'turn right', 'look right', 'kanan']) || (yaw !== null && yaw >= 0.13);
    }
    if (step === 'motion') {
      return hasGesture(result, ['blink', 'kedip', 'head left', 'head right', 'turn left', 'turn right', 'look left', 'look right'])
        || (yaw !== null && Math.abs(yaw) >= 0.12);
    }
    return false;
  }

  function instruction(step) {
    if (step === 'blink') return 'Kedipkan kedua mata satu kali.';
    if (step === 'turn_left') return 'Putar wajah sedikit ke kiri.';
    if (step === 'turn_right') return 'Putar wajah sedikit ke kanan.';
    if (step === 'motion') return 'Kedipkan mata atau gerakkan wajah sedikit ke samping.';
    return 'Ikuti instruksi pemindaian.';
  }

  function poseMatches(pose, result, face) {
    const yaw = yawOf(face);
    if (pose === 'left') return challengeDone('turn_left', result, face);
    if (pose === 'right') return challengeDone('turn_right', result, face);
    if (pose === 'front') return yaw === null || Math.abs(yaw) < 0.19;
    return true;
  }

  function poseInstruction(pose) {
    if (pose === 'left') return 'Sampel berikutnya: lihat sedikit ke kiri.';
    if (pose === 'right') return 'Sampel berikutnya: lihat sedikit ke kanan.';
    return 'Hadapkan wajah lurus ke kamera.';
  }

  function stopVideo(video) {
    const stream = video && video.srcObject;
    if (stream && stream.getTracks) stream.getTracks().forEach(function (track) { track.stop(); });
    if (video) {
      if (video.pause) video.pause();
      video.srcObject = null;
    }
  }

  function requestCamera(constraints, timeoutMs) {
    let expired = false;
    const rawRequest = navigator.mediaDevices.getUserMedia(constraints);
    return new Promise(function (resolve, reject) {
      const timer = window.setTimeout(function () {
        expired = true;
        const error = new Error('Permintaan izin kamera tidak mendapat respons.');
        error.name = 'CameraPermissionTimeout';
        reject(error);
      }, Math.max(10000, Number(timeoutMs || 22000)));

      rawRequest.then(function (stream) {
        window.clearTimeout(timer);
        if (expired) {
          stream.getTracks().forEach(function (track) { track.stop(); });
          return;
        }
        resolve(stream);
      }).catch(function (error) {
        window.clearTimeout(timer);
        if (!expired) reject(error);
      });
    });
  }

  function cameraError(error) {
    const name = error && error.name;
    if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
      return new Error('Izin kamera ditolak. Izinkan kamera pada pengaturan situs, lalu tekan Scan Ulang.');
    }
    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') return new Error('Kamera tidak ditemukan pada perangkat ini.');
    if (name === 'NotReadableError' || name === 'TrackStartError') return new Error('Kamera sedang digunakan aplikasi lain.');
    if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError') return new Error('Kamera tidak mendukung konfigurasi yang diminta.');
    if (name === 'SecurityError') return new Error('Akses kamera diblokir oleh pengaturan keamanan browser.');
    if (name === 'CameraPermissionTimeout') return new Error('Permintaan izin kamera terlalu lama. Periksa izin kamera browser.');
    return new Error((error && error.message) || 'Kamera gagal dibuka.');
  }

  async function scan(options) {
    options = options || {};
    const video = options.video;
    const statusEl = options.status;
    const enrollment = options.mode === 'enroll';
    const samplesNeeded = enrollment
      ? Math.max(5, Math.min(7, Number(options.samples || 5)))
      : Math.max(2, Math.min(3, Number(options.samples || 2)));
    const timeout = Math.max(15000, Math.min(45000, Number(options.timeout || (enrollment ? 35000 : 24000))));
    const challengeSteps = enrollment ? ['motion'] : Array.from((options.challenge && options.challenge.steps) || []);
    const samplePlan = enrollment
      ? ['front', 'front', 'left', 'right', 'front', 'left', 'right'].slice(0, samplesNeeded)
      : new Array(samplesNeeded).fill('front');

    if (!video || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) throw new Error('Browser tidak mendukung akses kamera.');
    if (!window.isSecureContext && location.hostname !== 'localhost') throw new Error('Pemindaian wajah memerlukan website HTTPS.');

    stopVideo(video);
    notifyStage(options, 'permission');
    setStatus(statusEl, 'Meminta izin kamera… pilih Izinkan pada browser.', 'primary');

    const permissionHintTimer = window.setTimeout(function () {
      notifyStage(options, 'permission-wait');
      setStatus(statusEl, 'Menunggu izin kamera. Periksa pesan izin browser.', 'warning');
    }, 1400);

    const cameraPromise = requestCamera({
      video: {
        facingMode: { ideal: 'user' },
        width: { ideal: 480, max: 640 },
        height: { ideal: 360, max: 480 },
        frameRate: { ideal: 24, max: 30 }
      },
      audio: false
    }, options.permissionTimeout || 22000);

    const modelPromise = loadHuman(null);
    modelPromise.catch(function () { /* ditangani setelah kamera aktif */ });

    let stream;
    try {
      stream = await cameraPromise;
    } catch (error) {
      window.clearTimeout(permissionHintTimer);
      notifyStage(options, 'error', { error: error });
      throw cameraError(error);
    }
    window.clearTimeout(permissionHintTimer);

    if (typeof options.isCancelled === 'function' && options.isCancelled()) {
      stream.getTracks().forEach(function (track) { track.stop(); });
      throw new Error('Pemindaian dibatalkan.');
    }

    video.srcObject = stream;
    video.playsInline = true;
    video.muted = true;
    try {
      await video.play();
    } catch (error) {
      stopVideo(video);
      throw new Error('Preview kamera tidak dapat diputar. Muat ulang halaman.');
    }

    notifyStage(options, 'camera-ready');
    setStatus(statusEl, 'Kamera aktif. Menyiapkan pengenal wajah…', 'primary');

    let human;
    try {
      human = await modelPromise;
    } catch (error) {
      stopVideo(video);
      notifyStage(options, 'error', { error: error });
      throw new Error('Model pengenal wajah gagal dimuat. Periksa koneksi atau instalasi model lokal.');
    }

    notifyStage(options, 'scanning', { modelSource: activeModelSource });
    const started = Date.now();
    const evidence = [];
    let challengeIndex = 0;
    let challengeArmed = false;
    let challengeReadySince = 0;
    let stableSince = 0;
    let lastSampleAt = 0;
    const samples = [];
    setStatus(statusEl, challengeSteps.length ? instruction(challengeSteps[0]) : 'Posisikan wajah di tengah.', 'primary');

    try {
      while (Date.now() - started < timeout) {
        if (typeof options.isCancelled === 'function' && options.isCancelled()) throw new Error('Pemindaian dibatalkan.');

        const result = await human.detect(video);
        const faces = result.face || [];
        if (faces.length !== 1) {
          stableSince = 0;
          setStatus(statusEl, faces.length > 1 ? 'Pastikan hanya satu wajah terlihat.' : 'Posisikan wajah di tengah bingkai.', 'warning');
          await new Promise(function (resolve) { setTimeout(resolve, 90); });
          continue;
        }

        const face = faces[0];
        const faceScore = Number(face.faceScore || face.score || 0);
        const real = (face.real === null || typeof face.real === 'undefined') ? NaN : Number(face.real);
        const live = (face.live === null || typeof face.live === 'undefined') ? NaN : Number(face.live);
        // Beberapa backend/perangkat tidak mengembalikan skor real/live pada
        // setiap frame. Bila tersedia, nilainya tetap wajib melewati batas;
        // bila tidak tersedia, keamanan ditopang challenge liveness sekali pakai.
        const realOk = !Number.isFinite(real) || real >= MIN_REAL_SCORE;
        const liveOk = !Number.isFinite(live) || live >= MIN_LIVE_SCORE;
        const authentic = realOk && liveOk;
        const descriptor = normalize(face.embedding);
        const box = face.box || [];
        const boxWidth = Number(box[2] || 0);
        const videoWidth = Number(video.videoWidth || 480);
        const sufficientlyLarge = boxWidth <= 0 || boxWidth / videoWidth >= 0.17;

        if (faceScore < MIN_FACE_SCORE || !authentic || !descriptor || !sufficientlyLarge) {
          stableSince = 0;
          if (!authentic) {
            setStatus(statusEl, 'Pastikan wajah asli terlihat jelas dan tidak tertutup layar/foto.', 'warning');
          } else {
            setStatus(statusEl, 'Perbaiki cahaya dan posisikan wajah di dalam bingkai.', 'warning');
          }
          await new Promise(function (resolve) { setTimeout(resolve, 110); });
          continue;
        }

        if (challengeIndex < challengeSteps.length) {
          const step = challengeSteps[challengeIndex];

          // Challenge motion harus diawali posisi netral agar foto statis atau
          // wajah yang sejak awal miring tidak langsung dianggap lulus.
          if (step === 'motion' && !challengeArmed) {
            const yaw = yawOf(face);
            const centered = yaw === null || Math.abs(yaw) < 0.09;
            if (!centered) {
              challengeReadySince = 0;
              setStatus(statusEl, 'Hadapkan wajah lurus ke kamera terlebih dahulu.', 'primary');
              await new Promise(function (resolve) { setTimeout(resolve, 90); });
              continue;
            }
            if (!challengeReadySince) challengeReadySince = Date.now();
            if (Date.now() - challengeReadySince < 220) {
              setStatus(statusEl, 'Tahan wajah lurus sebentar…', 'primary');
              await new Promise(function (resolve) { setTimeout(resolve, 90); });
              continue;
            }
            challengeArmed = true;
            setStatus(statusEl, instruction(step), 'primary');
            await new Promise(function (resolve) { setTimeout(resolve, 90); });
            continue;
          }

          if (challengeDone(step, result, face)) {
            evidence.push({ step: step, at: Date.now() - started });
            challengeIndex += 1;
            challengeArmed = false;
            challengeReadySince = 0;
            notifyStage(options, 'challenge', { current: challengeIndex, total: challengeSteps.length, step: step });
            stableSince = 0;
            if (challengeIndex < challengeSteps.length) {
              setStatus(statusEl, instruction(challengeSteps[challengeIndex]), 'primary');
            } else {
              setStatus(statusEl, enrollment ? poseInstruction(samplePlan[0]) : 'Gerakan benar. Tetap menghadap kamera.', 'success');
            }
          } else {
            setStatus(statusEl, instruction(step), 'primary');
          }
          await new Promise(function (resolve) { setTimeout(resolve, 90); });
          continue;
        }

        const pose = samplePlan[samples.length] || 'front';
        if (!poseMatches(pose, result, face)) {
          stableSince = 0;
          setStatus(statusEl, poseInstruction(pose), 'primary');
          await new Promise(function (resolve) { setTimeout(resolve, 90); });
          continue;
        }

        if (!stableSince) stableSince = Date.now();
        if (Date.now() - stableSince < 150 || Date.now() - lastSampleAt < 180) {
          await new Promise(function (resolve) { setTimeout(resolve, 80); });
          continue;
        }

        const reference = samples.length ? average(samples) : null;
        const sampleSimilarity = enrollment ? MIN_ENROLL_SAMPLE_SIMILARITY : MIN_LOGIN_SAMPLE_SIMILARITY;
        if (reference && cosine(reference, descriptor) < sampleSimilarity) {
          // Pada pendaftaran pose kiri/kanan boleh sedikit berbeda; jangan hapus
          // seluruh sampel, cukup minta posisi lebih stabil.
          stableSince = Date.now();
          setStatus(statusEl, 'Wajah kurang konsisten. Pertahankan posisi dan cahaya.', 'warning');
          await new Promise(function (resolve) { setTimeout(resolve, 110); });
          continue;
        }

        samples.push(descriptor);
        lastSampleAt = Date.now();
        stableSince = 0;
        notifyStage(options, 'sample', { current: samples.length, total: samplesNeeded, pose: pose });
        setStatus(statusEl, 'Mengambil sampel ' + samples.length + '/' + samplesNeeded + '…', 'success');

        if (samples.length >= samplesNeeded) {
          const descriptorAverage = average(samples);
          if (!descriptorAverage) throw new Error('Descriptor wajah tidak dapat diproses.');
          notifyStage(options, 'complete');
          setStatus(statusEl, 'Pemindaian wajah berhasil.', 'success');
          return {
            descriptor: descriptorAverage,
            templates: enrollment ? samples : [],
            sampleCount: samples.length,
            evidence: {
              events: evidence,
              elapsed_ms: Date.now() - started
            },
            modelSource: activeModelSource
          };
        }

        if (enrollment) setStatus(statusEl, poseInstruction(samplePlan[samples.length]), 'primary');
        await new Promise(function (resolve) { setTimeout(resolve, 100); });
      }

      throw new Error('Waktu pemindaian habis. Pastikan wajah terang dan ikuti instruksi gerakan.');
    } finally {
      stopVideo(video);
    }
  }

  window.FaceScanner = {
    scan: scan,
    stop: stopVideo,
    setStatus: setStatus,
    preload: preload,
    configure: configure,
    source: function () { return activeModelSource; }
  };
})(window);
