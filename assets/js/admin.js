/* ============ Admin JS ============ */
(function () {
  'use strict';
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  /* Sidebar mobile */
  const sb = $('#adminSidebar'), bd = $('#sidebarBackdrop'), tg = $('#sidebarToggle');
  tg && tg.addEventListener('click', () => { sb.classList.add('open'); bd.classList.add('show'); });
  bd && bd.addEventListener('click', () => { sb.classList.remove('open'); bd.classList.remove('show'); });

  /* Tandai menu aktif */
  const path = location.pathname.replace(/\/$/, '');
  $$('.sidebar-nav a').forEach((a) => {
    const href = a.getAttribute('href').replace(/\/$/, '');
    if (href && (path === href || (path.startsWith(href + '/') && href.split('/').length > 2))) a.classList.add('active');
  });

  /* Editor sederhana */
  $$('.editor-init').forEach((ta) => {
    ta.style.display = 'none';
    const toolbar = document.createElement('div');
    toolbar.className = 'editor-toolbar';
    const tools = [
      ['bold', 'type-bold'], ['italic', 'type-italic'], ['underline', 'type-underline'],
      ['insertUnorderedList', 'list-ul'], ['insertOrderedList', 'list-ol'],
      ['h3', 'type-h3'], ['link', 'link-45deg'], ['image', 'image'], ['removeFormat', 'eraser'],
    ];
    const area = document.createElement('div');
    area.className = 'editor-area';
    area.contentEditable = 'true';
    area.setAttribute('role', 'textbox');
    area.setAttribute('aria-multiline', 'true');
    area.innerHTML = ta.value;

    tools.forEach(([cmd, icon]) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.innerHTML = '<i class="bi bi-' + icon + '"></i>';
      b.title = cmd;
      b.setAttribute('aria-label', cmd);
      b.addEventListener('click', () => {
        area.focus();
        if (cmd === 'link') {
          const u = prompt('URL tautan:', 'https://');
          if (u && /^https?:\/\//i.test(u)) document.execCommand('createLink', false, u);
        } else if (cmd === 'image') {
          const u = prompt('URL gambar:', 'https://');
          if (u && /^https?:\/\//i.test(u)) document.execCommand('insertImage', false, u);
        } else if (cmd === 'h3') {
          document.execCommand('formatBlock', false, 'h3');
        } else {
          document.execCommand(cmd, false, null);
        }
        ta.value = area.innerHTML;
      });
      toolbar.appendChild(b);
    });

    ta.parentNode.insertBefore(toolbar, ta);
    ta.parentNode.insertBefore(area, ta);
    const sync = () => {
      ta.value = area.innerHTML;
      area.classList.remove('is-invalid');
    };
    area.addEventListener('input', sync);
    ta._editorArea = area;
    ta._editorSync = sync;
  });

  /* Validasi form CRUD dan cegah penyimpanan ganda. */
  $$('form[data-crud-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      let firstInvalid = null;

      $$('.editor-init', form).forEach((ta) => {
        ta._editorSync && ta._editorSync();
        if (ta.dataset.editorRequired === '1') {
          const area = ta._editorArea;
          const text = area ? area.textContent.trim() : '';
          const hasMedia = area ? !!area.querySelector('img,iframe,table') : false;
          if (!text && !hasMedia) {
            event.preventDefault();
            area && area.classList.add('is-invalid');
            if (!firstInvalid) firstInvalid = area;
          }
        }
      });

      $$('[required]', form).forEach((field) => {
        if (field.type === 'file' && !field.value) return;
        if (!field.checkValidity()) {
          event.preventDefault();
          field.classList.add('is-invalid');
          if (!firstInvalid) firstInvalid = field;
        } else {
          field.classList.remove('is-invalid');
        }
      });

      if (firstInvalid) {
        firstInvalid.focus();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        alert('Periksa kembali kolom wajib atau format data yang belum benar.');
        return;
      }

      const submit = form.querySelector('[type="submit"]');
      if (submit) {
        submit.disabled = true;
        submit.dataset.originalHtml = submit.innerHTML;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Menyimpan…';
      }
    });

    $$('input,select,textarea', form).forEach((field) => {
      field.addEventListener('input', () => field.classList.remove('is-invalid'));
      field.addEventListener('change', () => field.classList.remove('is-invalid'));
    });
  });

  /* Konfirmasi hapus */
  $$('form[data-confirm]').forEach((f) => {
    f.addEventListener('submit', (e) => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });

  /* Drag & drop reorder tabel */
  $$('table[data-reorder-url] tbody').forEach((tbody) => {
    let dragged = null;
    $$('tr[data-id]', tbody).forEach((tr) => {
      tr.draggable = true;
      tr.classList.add('drag-row');
      tr.addEventListener('dragstart', () => { dragged = tr; tr.classList.add('dragging'); });
      tr.addEventListener('dragend', () => {
        tr.classList.remove('dragging');
        const url = tbody.closest('table').dataset.reorderUrl;
        const ids = $$('tr[data-id]', tbody).map((r) => r.dataset.id);
        const fd = new FormData();
        fd.append('csrf_token', window.CSRF_TOKEN);
        ids.forEach((id) => fd.append('ids[]', id));
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then((response) => response.json())
          .then((result) => {
            if (!result.ok) throw new Error(result.error || 'Urutan gagal disimpan.');
          })
          .catch((error) => {
            alert(error.message || 'Urutan gagal disimpan. Muat ulang halaman lalu coba kembali.');
            location.reload();
          });
      });
      tr.addEventListener('dragover', (e) => {
        e.preventDefault();
        const rows = $$('tr[data-id]', tbody);
        const after = rows.indexOf(tr) > rows.indexOf(dragged);
        tbody.insertBefore(dragged, after ? tr.nextSibling : tr);
      });
    });
  });

  /* Preview Event Effect (form event) */
  const prevBtn = $('#previewEffectBtn');
  prevBtn && prevBtn.addEventListener('click', () => {
    const form = prevBtn.closest('form');
    const val = (n) => { const el = form.querySelector('[name="' + n + '"]'); return el ? el.value : ''; };
    window.DesaEffects && DesaEffects.start({
      effect: val('effect_type') || 'confetti',
      colors: (val('color_scheme') || '#e63946,#ffffff').split(',').map((s) => s.trim()).filter(Boolean),
      intensity: val('intensity') || 'sedang',
      duration: Math.min(parseInt(val('duration') || '8', 10) || 8, 15),
    });
    setTimeout(() => window.DesaEffects && DesaEffects.stop(), 16000);
  });

  /* Preview posisi & pengingat player musik */
  $$('audio.audio-preview').forEach((a) => { a.volume = 0.6; });
})();
