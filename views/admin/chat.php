<?php
$initialRoom = mb_substr(trim((string) ($_GET['room'] ?? '')), 0, 64);
$rateSettings = $rateSettings ?? ['limit' => 20, 'minutes' => 1];
?>
<div class="chat-wrap" data-testid="chat-admin">
  <div class="chat-rooms">
    <div class="chat-rooms-head">
      <strong id="chatListTitle"><i class="bi bi-people me-1"></i> Room Aktif</strong>
      <div class="d-flex align-items-center gap-1 flex-wrap justify-content-end">
        <button class="btn btn-sm btn-light position-relative" id="btnVisitors" title="Pengunjung aktif" data-testid="chat-active-visitors">
          <i class="bi bi-broadcast-pin"></i>
          <span class="visitor-count-badge" id="visitorCountBadge"><?= (int) ($activeVisitorCount ?? 0) ?></span>
        </button>
        <button class="btn btn-sm btn-light" id="btnArsip" title="Arsip percakapan"><i class="bi bi-archive"></i></button>
        <button class="btn btn-sm btn-light" id="btnQuickManager" title="Kelola balasan cepat"><i class="bi bi-lightning-charge"></i></button>
        <button class="btn btn-sm btn-light" id="btnChatSettings" title="Pengaturan pembatasan pesan"><i class="bi bi-sliders"></i></button>
        <label class="chat-sound-toggle" title="Notifikasi suara"><input type="checkbox" id="chatSound" checked> <i class="bi bi-volume-up"></i></label>
      </div>
    </div>
    <div id="chatRoomList" class="chat-room-list"><div class="text-muted small p-3">Memuat...</div></div>
  </div>

  <div class="chat-main">
    <div id="chatEmpty" class="chat-empty">
      <i class="bi bi-chat-square-dots"></i>
      <p>Pilih room atau buka daftar pengunjung aktif untuk memulai chat.</p>
    </div>
    <div id="chatPanel" class="chat-panel" hidden>
      <div class="chat-panel-head">
        <button type="button" class="btn btn-sm btn-light chat-mobile-back" id="chatMobileBack" aria-label="Kembali"><i class="bi bi-arrow-left"></i></button>
        <div class="chat-person">
          <strong id="cpName">-</strong>
          <div class="chat-meta" id="cpMeta"></div>
        </div>
        <div class="chat-actions">
          <button class="btn btn-sm btn-outline-secondary" id="btnMute" data-testid="chat-mute"><i class="bi bi-mic-mute"></i> Bisukan</button>
          <button class="btn btn-sm btn-outline-danger" id="btnBan" data-testid="chat-ban"><i class="bi bi-shield-x"></i> Ban IP</button>
          <button class="btn btn-sm btn-outline-dark" id="btnClose" data-testid="chat-close"><i class="bi bi-x-circle"></i> Tutup</button>
        </div>
      </div>

      <div id="chatMessages" class="chat-messages"></div>
      <div id="cUserTyping" class="chat-typing" style="display:none"><span></span><span></span><span></span> Pengunjung sedang mengetik…</div>

      <div class="chat-saved-quick" id="chatSavedQuick" aria-label="Balasan cepat"></div>
      <div class="chat-quick">
        <button class="btn btn-sm btn-light" data-pick="berita"><i class="bi bi-newspaper"></i> Berita</button>
        <button class="btn btn-sm btn-light" data-pick="agenda"><i class="bi bi-calendar-event"></i> Agenda</button>
        <button class="btn btn-sm btn-light" data-pick="pengumuman"><i class="bi bi-megaphone"></i> Pengumuman</button>
        <button class="btn btn-sm btn-light" data-pick="layanan"><i class="bi bi-file-earmark-text"></i> Layanan</button>
        <div id="chatPicker" class="chat-picker" hidden></div>
      </div>
      <form id="chatForm" class="chat-input">
        <input type="text" id="chatInput" class="form-control" placeholder="Ketik balasan..." autocomplete="off" maxlength="2000" data-testid="chat-admin-input">
        <button class="btn btn-primary" data-testid="chat-admin-send"><i class="bi bi-send"></i></button>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deviceChatModal" tabindex="-1" aria-labelledby="deviceChatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="deviceChatForm">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="deviceChatModalLabel"><i class="bi bi-chat-dots me-2 text-primary"></i>Mulai Chat Pengunjung</h5>
            <div class="small text-muted mt-1" id="deviceChatTarget">Perangkat belum dipilih.</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="deviceChatId">
          <label class="form-label" for="deviceChatBody">Pesan pertama</label>
          <textarea class="form-control" id="deviceChatBody" rows="5" maxlength="2000" required>Halo, kami dari admin desa. Ada yang bisa kami bantu?</textarea>
          <div class="chat-modal-quick mt-2" id="deviceModalQuick"></div>
          <div class="form-text">Pesan ditujukan ke token perangkat. IP hanya ditampilkan sebagai metadata.</div>
          <div class="alert alert-danger py-2 mt-3 mb-0" id="deviceChatError" hidden></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="deviceChatSend"><i class="bi bi-send me-1"></i> Kirim Pesan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="chatSettingsModal" tabindex="-1" aria-labelledby="chatSettingsTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form id="chatSettingsForm">
        <div class="modal-header">
          <h5 class="modal-title" id="chatSettingsTitle"><i class="bi bi-sliders me-2"></i>Pembatasan Pesan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <label class="form-label" for="chatLimit">Maksimal pesan</label>
          <input type="number" class="form-control" id="chatLimit" min="1" max="200" value="<?= (int) $rateSettings['limit'] ?>" required>
          <label class="form-label mt-3" for="chatMinutes">Dalam waktu (menit)</label>
          <input type="number" class="form-control" id="chatMinutes" min="1" max="60" value="<?= (int) $rateSettings['minutes'] ?>" required>
          <div class="form-text">Berlaku per room dan per pengirim untuk mencegah spam.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary w-100" type="submit">Simpan Pengaturan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="quickReplyModal" tabindex="-1" aria-labelledby="quickReplyTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="quickReplyTitle"><i class="bi bi-lightning-charge me-2"></i>Balasan Cepat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="quickReplyList" class="quick-reply-list mb-3"></div>
        <hr>
        <form id="quickReplyForm" class="row g-2">
          <input type="hidden" id="quickReplyId">
          <div class="col-md-8">
            <label class="form-label" for="quickReplyName">Judul</label>
            <input type="text" class="form-control" id="quickReplyName" maxlength="80" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="quickReplyOrder">Urutan</label>
            <input type="number" class="form-control" id="quickReplyOrder" min="0" max="9999" value="0">
          </div>
          <div class="col-12">
            <label class="form-label" for="quickReplyBody">Isi pesan</label>
            <textarea class="form-control" id="quickReplyBody" rows="3" maxlength="1000" required></textarea>
          </div>
          <div class="col-12 d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-outline-secondary" id="quickReplyReset">Form Baru</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$base = url('');
$csrf = csrf_token();
$initialQuick = array_map(static fn(array $q): array => [
    'id' => (int) $q['id'],
    'title' => $q['title'],
    'body' => $q['body'],
    'sort_order' => (int) $q['sort_order'],
], $quickReplies ?? []);
$inlineScript = '<script>
(function(){
  "use strict";
  var BASE=' . json_encode(rtrim($base, '/')) . ',CSRF=' . json_encode($csrf) . ',INITIAL_ROOM=' . json_encode($initialRoom) . ';
  var cur=null,lastTotal=0,ac=null,listMode="rooms",archiveMode=false,sendBusy=false,quickItems=' . json_encode($initialQuick, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';
  var esc=function(s){return String(s||"").replace(/[&<>"]/g,function(c){return({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[c]);});};
  var linkify=function(s){return esc(s).replace(/(https?:\/\/[^\s<]+)/g,"<a href=\'$1\' target=\'_blank\' rel=\'noopener\'>$1</a>").replace(/\\n/g,"<br>");};
  var statusLabel=function(s){if(s==="read")return "<span class=\'msg-state read\'><i class=\'bi bi-check2-all\'></i> Dibaca</span>";if(s==="delivered")return "<span class=\'msg-state\'><i class=\'bi bi-check2-all\'></i> Diterima</span>";return "<span class=\'msg-state\'><i class=\'bi bi-check2\'></i> Terkirim</span>";};
  function api(path){return fetch(BASE+path,{cache:"no-store",headers:{"X-Requested-With":"XMLHttpRequest"}}).then(function(r){return r.json();});}
  function post(path,data){var b=new URLSearchParams(data||{});b.append("csrf_token",CSRF);return fetch(BASE+path,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"},body:b}).then(function(r){return r.json();});}
  function beep(){var c=document.getElementById("chatSound");if(c&&!c.checked)return;try{ac=ac||new(window.AudioContext||window.webkitAudioContext)();if(ac.state==="suspended")ac.resume();var o=ac.createOscillator(),g=ac.createGain();o.connect(g);g.connect(ac.destination);o.type="sine";o.frequency.value=880;g.gain.setValueAtTime(.001,ac.currentTime);g.gain.exponentialRampToValueAtTime(.18,ac.currentTime+.03);g.gain.exponentialRampToValueAtTime(.001,ac.currentTime+.35);o.start();o.stop(ac.currentTime+.36);}catch(e){}}
  document.addEventListener("pointerdown",function(){try{if(ac&&ac.state==="suspended")ac.resume();}catch(e){}},{once:true});

  function renderRooms(rooms){
    var el=document.getElementById("chatRoomList");
    if(!rooms.length){el.innerHTML="<div class=\'text-muted small p-3\'>Belum ada room chat.</div>";return;}
    el.innerHTML=rooms.map(function(r){
      return "<button type=\'button\' class=\'chat-room-item"+(cur===r.id?" active":"")+(Number(r.unread)>0?" unread":"")+"\' data-id=\'"+esc(r.id)+"\'>"+
        "<div class=\'cr-top\'><span class=\'cr-name\'>"+esc(r.name)+"</span>"+(Number(r.unread)>0?"<span class=\'cr-badge\'>"+Number(r.unread)+"</span>":"")+"</div>"+
        "<div class=\'cr-last\'>"+esc(r.last||"(belum ada pesan)")+"</div>"+
        "<div class=\'cr-meta\'>"+esc(r.loc||"?")+" · "+esc(r.device||"")+" · "+esc(r.last_at||"")+(r.admin_started?" · <b>ADMIN MULAI</b>":"")+(r.status==="muted"?" · <b>MUTED</b>":"")+"</div></button>";
    }).join("");
    Array.prototype.forEach.call(el.querySelectorAll(".chat-room-item"),function(it){it.onclick=function(){openRoom(it.getAttribute("data-id"));};});
  }

  function renderVisitors(items){
    var el=document.getElementById("chatRoomList");
    if(!items.length){el.innerHTML="<div class=\'visitor-empty\'><i class=\'bi bi-broadcast\'></i><span>Belum ada pengunjung aktif.</span></div>";return;}
    el.innerHTML=items.map(function(v){
      var idle=v.idle_seconds<5?"baru saja":v.idle_seconds+" detik lalu";
      var roomButton=v.room_id&&v.room_status!=="closed"
        ?"<button type=\'button\' class=\'btn btn-sm btn-outline-primary js-open-device-room\' data-room=\'"+esc(v.room_id)+"\'>Buka Chat</button>"
        :"<button type=\'button\' class=\'btn btn-sm btn-primary js-start-device\' data-id=\'"+v.id+"\'>Mulai Chat</button>";
      return "<div class=\'visitor-device-item\' data-id=\'"+v.id+"\'>"+
        "<div class=\'visitor-device-top\'><span class=\'online-dot\'></span><strong>Perangkat #"+esc(v.hint)+"</strong><span class=\'visitor-idle\'>"+idle+"</span></div>"+
        "<div class=\'visitor-page\' title=\'"+esc(v.title)+"\'><i class=\'bi bi-window\'></i> "+esc(v.title)+"</div>"+
        "<div class=\'visitor-path\' title=\'"+esc(v.path)+"\'>"+esc(v.path)+"</div>"+
        "<div class=\'visitor-meta\'>"+esc(v.device)+" · "+esc(v.browser)+" · "+esc(v.os)+" · IP <code>"+esc(v.ip)+"</code></div>"+
        "<div class=\'visitor-action\'>"+(v.pending?"<span class=\'badge text-bg-warning\'>Menunggu perangkat</span>":"")+roomButton+"</div></div>";
    }).join("");
    Array.prototype.forEach.call(el.querySelectorAll(".js-open-device-room"),function(b){b.onclick=function(){showRooms();openRoom(b.getAttribute("data-room"));};});
    Array.prototype.forEach.call(el.querySelectorAll(".js-start-device"),function(b){b.onclick=function(){openDeviceModal(Number(b.getAttribute("data-id")),b.closest(".visitor-device-item"));};});
  }

  function pollRooms(){
    api("/admin/chat/poll").then(function(d){
      var badge=document.getElementById("chatSidebarBadge");
      if(badge){if(d.total_unread>0){badge.hidden=false;badge.textContent=d.total_unread;}else badge.hidden=true;}
      var vb=document.getElementById("visitorCountBadge");if(vb)vb.textContent=d.active_visitors||0;
      if(d.total_unread>lastTotal)beep();
      lastTotal=d.total_unread||0;
      if(listMode==="rooms"&&!archiveMode)renderRooms(d.rooms||[]);
    }).catch(function(){});
  }
  function pollVisitors(){if(listMode!=="visitors")return;api("/admin/chat/visitors").then(function(d){renderVisitors(d.visitors||[]);var vb=document.getElementById("visitorCountBadge");if(vb)vb.textContent=d.count||0;}).catch(function(){});}
  function showRooms(){listMode="rooms";archiveMode=false;document.getElementById("chatListTitle").innerHTML="<i class=\'bi bi-people me-1\'></i> Room Aktif";document.getElementById("btnVisitors").classList.remove("active");document.getElementById("btnArsip").classList.remove("active");pollRooms();}
  function showVisitors(){listMode="visitors";archiveMode=false;document.getElementById("chatListTitle").innerHTML="<i class=\'bi bi-broadcast-pin me-1\'></i> Pengunjung Aktif";document.getElementById("btnVisitors").classList.add("active");document.getElementById("btnArsip").classList.remove("active");pollVisitors();}

  function openRoom(id){
    if(!id)return;cur=id;
    document.querySelector(".chat-wrap").classList.add("room-open");
    document.getElementById("chatEmpty").hidden=true;
    document.getElementById("chatPanel").hidden=false;
    loadMsgs(true);
  }
  function loadMsgs(scroll){
    if(!cur)return;
    api("/admin/chat/messages?room="+encodeURIComponent(cur)).then(function(d){
      if(!d.ok)return;
      document.getElementById("cpName").textContent=d.room.name;
      var started=d.room.admin_started?" · <span class=\'badge text-bg-info\'>Dimulai "+esc(d.room.initiated_by||"Admin")+" · "+esc(d.room.initiated_at)+"</span>":"";
      document.getElementById("cpMeta").innerHTML="IP: <code>"+esc(d.room.ip)+"</code> · "+esc(d.room.loc)+" · "+esc(d.room.device)+" · "+esc(d.room.browser)+" · "+esc(d.room.os)+" · sejak "+esc(d.room.created)+started;
      var box=document.getElementById("chatMessages");
      var nearBottom=box.scrollHeight-box.scrollTop-box.clientHeight<80;
      box.innerHTML=(d.messages||[]).map(function(m){return "<div class=\'cmsg "+m.sender+"\' data-mid=\'"+m.id+"\'><div class=\'cmsg-b\'>"+linkify(m.body)+"</div><span class=\'cmsg-t\'>"+esc(m.time)+(m.sender==="admin"?" · "+statusLabel(m.status):"")+"</span></div>";}).join("");
      if(scroll||nearBottom)box.scrollTop=box.scrollHeight;
      var muted=d.room.status==="muted";
      document.getElementById("btnMute").innerHTML=muted?"<i class=\'bi bi-mic\'></i> Aktifkan":"<i class=\'bi bi-mic-mute\'></i> Bisukan";
      var bb=document.getElementById("btnBan");if(d.banned){bb.innerHTML="<i class=\'bi bi-unlock\'></i> Unban IP";bb.dataset.unban="1";}else{bb.innerHTML="<i class=\'bi bi-shield-x\'></i> Ban IP";bb.dataset.unban="";}
      var rb=document.getElementById("btnClose");if(d.room.status==="closed"){rb.innerHTML="<i class=\'bi bi-arrow-counterclockwise\'></i> Buka lagi";rb.dataset.reopen="1";}else{rb.innerHTML="<i class=\'bi bi-x-circle\'></i> Tutup";rb.dataset.reopen="";}
      var tp=document.getElementById("cUserTyping");if(tp)tp.style.display=d.user_typing?"flex":"none";
    }).catch(function(){});
  }

  function renderQuick(){
    var holder=document.getElementById("chatSavedQuick"),modalHolder=document.getElementById("deviceModalQuick");
    var html=quickItems.map(function(q,i){return "<button type=\'button\' class=\'quick-chip\' data-quick-i=\'"+i+"\' title=\'"+esc(q.body)+"\'><i class=\'bi bi-lightning-charge-fill\'></i> "+esc(q.title)+"</button>";}).join("");
    if(holder)holder.innerHTML=html;if(modalHolder)modalHolder.innerHTML=html;
    [holder,modalHolder].forEach(function(scope){if(!scope)return;Array.prototype.forEach.call(scope.querySelectorAll("[data-quick-i]"),function(b){b.onclick=function(){var q=quickItems[Number(b.getAttribute("data-quick-i"))];var target=scope===modalHolder?document.getElementById("deviceChatBody"):document.getElementById("chatInput");if(target&&q){target.value=q.body;target.focus();}};});});
  }
  function refreshQuick(){
    return api("/admin/chat/quick-list").then(function(d){quickItems=d.items||[];renderQuick();renderQuickManager();});
  }
  function renderQuickManager(){
    var el=document.getElementById("quickReplyList");if(!el)return;
    if(!quickItems.length){el.innerHTML="<div class=\'text-muted small\'>Belum ada balasan cepat.</div>";return;}
    el.innerHTML=quickItems.map(function(q,i){return "<div class=\'quick-manager-item\'><div><strong>"+esc(q.title)+"</strong><p>"+esc(q.body)+"</p></div><div class=\'text-nowrap\'><button type=\'button\' class=\'btn btn-sm btn-outline-primary js-quick-edit\' data-i=\'"+i+"\'><i class=\'bi bi-pencil\'></i></button> <button type=\'button\' class=\'btn btn-sm btn-outline-danger js-quick-delete\' data-id=\'"+q.id+"\'><i class=\'bi bi-trash\'></i></button></div></div>";}).join("");
    Array.prototype.forEach.call(el.querySelectorAll(".js-quick-edit"),function(b){b.onclick=function(){var q=quickItems[Number(b.dataset.i)];document.getElementById("quickReplyId").value=q.id;document.getElementById("quickReplyName").value=q.title;document.getElementById("quickReplyBody").value=q.body;document.getElementById("quickReplyOrder").value=q.sort_order||0;};});
    Array.prototype.forEach.call(el.querySelectorAll(".js-quick-delete"),function(b){b.onclick=function(){if(!confirm("Hapus balasan cepat ini?"))return;post("/admin/chat/quick-delete",{id:b.dataset.id}).then(refreshQuick);};});
  }

  document.getElementById("chatForm").addEventListener("submit",function(e){
    e.preventDefault();var inp=document.getElementById("chatInput"),v=inp.value.trim(),btn=this.querySelector("button");if(!v||!cur||sendBusy)return;
    sendBusy=true;inp.value="";if(btn)btn.disabled=true;
    post("/admin/chat/send",{room_id:cur,body:v}).then(function(d){if(!d.ok){inp.value=v;throw new Error(d.error||"Gagal mengirim.");}loadMsgs(true);}).catch(function(err){alert(err.message||"Koneksi gagal.");}).finally(function(){sendBusy=false;if(btn)btn.disabled=false;inp.focus();});
  });

  Array.prototype.forEach.call(document.querySelectorAll("[data-pick]"),function(b){b.onclick=function(){
    if(!cur)return;var t=b.getAttribute("data-pick"),box=document.getElementById("chatPicker"),title={berita:"Pilih Berita",agenda:"Pilih Agenda",pengumuman:"Pilih Pengumuman",layanan:"Pilih Layanan"}[t]||"Pilih";
    box.hidden=false;box.innerHTML="<div class=\'cp-head\'><span>"+title+"</span><button type=\'button\' class=\'cp-close\'><i class=\'bi bi-x-lg\'></i></button></div><div class=\'cp-body\'><div class=\'cp-load\'>Memuat...</div></div>";box.querySelector(".cp-close").onclick=function(){box.hidden=true;};
    api("/admin/chat/picker?type="+t).then(function(d){var body=box.querySelector(".cp-body");body.innerHTML=(d.items||[]).map(function(it,i){return "<button type=\'button\' class=\'cp-item\' data-i=\'"+i+"\'>"+esc(it.title)+"</button>";}).join("")||"<div class=\'cp-empty\'>Tidak ada data.</div>";Array.prototype.forEach.call(body.querySelectorAll(".cp-item"),function(x){x.onclick=function(){var it=d.items[Number(x.dataset.i)];document.getElementById("chatInput").value=it.title+"\\n"+it.url;box.hidden=true;document.getElementById("chatInput").focus();};});});
  };});

  document.getElementById("btnVisitors").onclick=function(){listMode==="visitors"?showRooms():showVisitors();};
  document.getElementById("btnArsip").onclick=function(){archiveMode=!archiveMode;listMode="rooms";this.classList.toggle("active",archiveMode);document.getElementById("btnVisitors").classList.remove("active");document.getElementById("chatListTitle").innerHTML=archiveMode?"<i class=\'bi bi-archive me-1\'></i> Arsip Chat":"<i class=\'bi bi-people me-1\'></i> Room Aktif";if(archiveMode)api("/admin/chat/arsip").then(function(d){renderRooms(d.rooms||[]);});else pollRooms();};
  document.getElementById("btnQuickManager").onclick=function(){refreshQuick();bootstrap.Modal.getOrCreateInstance(document.getElementById("quickReplyModal")).show();};
  document.getElementById("btnChatSettings").onclick=function(){bootstrap.Modal.getOrCreateInstance(document.getElementById("chatSettingsModal")).show();};

  function openDeviceModal(id,item){
    document.getElementById("deviceChatId").value=id;
    var title=item.querySelector(".visitor-page")?item.querySelector(".visitor-page").textContent.trim():"Perangkat #"+id;
    var meta=item.querySelector(".visitor-meta")?item.querySelector(".visitor-meta").textContent.trim():"";
    document.getElementById("deviceChatTarget").textContent=title+" · "+meta;
    document.getElementById("deviceChatError").hidden=true;
    bootstrap.Modal.getOrCreateInstance(document.getElementById("deviceChatModal")).show();
  }
  document.getElementById("deviceChatForm").addEventListener("submit",function(e){e.preventDefault();var id=document.getElementById("deviceChatId").value,body=document.getElementById("deviceChatBody").value.trim(),btn=document.getElementById("deviceChatSend"),err=document.getElementById("deviceChatError");if(!body)return;btn.disabled=true;err.hidden=true;post("/admin/chat/start-device",{device_id:id,body:body}).then(function(d){if(!d.ok)throw new Error(d.error||"Gagal memulai chat.");bootstrap.Modal.getInstance(document.getElementById("deviceChatModal")).hide();showRooms();openRoom(d.room_id);}).catch(function(ex){err.textContent=ex.message;err.hidden=false;}).finally(function(){btn.disabled=false;});});

  document.getElementById("chatSettingsForm").addEventListener("submit",function(e){e.preventDefault();var limit=document.getElementById("chatLimit").value,minutes=document.getElementById("chatMinutes").value;post("/admin/chat/settings-save",{limit:limit,minutes:minutes}).then(function(d){if(!d.ok)throw new Error(d.error||"Gagal menyimpan.");bootstrap.Modal.getInstance(document.getElementById("chatSettingsModal")).hide();}).catch(function(ex){alert(ex.message);});});

  function resetQuickForm(){document.getElementById("quickReplyId").value="";document.getElementById("quickReplyName").value="";document.getElementById("quickReplyBody").value="";document.getElementById("quickReplyOrder").value="0";}
  document.getElementById("quickReplyReset").onclick=resetQuickForm;
  document.getElementById("quickReplyForm").addEventListener("submit",function(e){e.preventDefault();post("/admin/chat/quick-save",{id:document.getElementById("quickReplyId").value,title:document.getElementById("quickReplyName").value,body:document.getElementById("quickReplyBody").value,sort_order:document.getElementById("quickReplyOrder").value}).then(function(d){if(!d.ok)throw new Error(d.error||"Gagal menyimpan.");resetQuickForm();return refreshQuick();}).catch(function(ex){alert(ex.message);});});

  document.getElementById("btnMute").onclick=function(){if(!cur)return;var activate=this.textContent.indexOf("Aktifkan")>=0;post("/admin/chat/"+(activate?"unmute":"mute"),{room_id:cur}).then(function(){loadMsgs(false);});};
  document.getElementById("btnBan").onclick=function(){if(!cur)return;if(this.dataset.unban){post("/admin/chat/unban",{room_id:cur}).then(function(){loadMsgs(false);});return;}if(!confirm("Blokir IP pengunjung ini dari seluruh situs dan tutup room?"))return;post("/admin/chat/ban",{room_id:cur}).then(function(){cur=null;document.querySelector(".chat-wrap").classList.remove("room-open");document.getElementById("chatPanel").hidden=true;document.getElementById("chatEmpty").hidden=false;pollRooms();});};
  document.getElementById("btnClose").onclick=function(){if(!cur)return;if(this.dataset.reopen){post("/admin/chat/reopen",{room_id:cur}).then(function(){loadMsgs(false);pollRooms();});return;}if(!confirm("Tutup room ini?"))return;post("/admin/chat/close",{room_id:cur}).then(function(){cur=null;document.querySelector(".chat-wrap").classList.remove("room-open");document.getElementById("chatPanel").hidden=true;document.getElementById("chatEmpty").hidden=false;pollRooms();});};

  var ti=document.getElementById("chatInput"),typingAt=0;ti.addEventListener("input",function(){if(!cur||Date.now()-typingAt<2500)return;typingAt=Date.now();post("/admin/chat/typing",{room_id:cur});});
  document.getElementById("chatMobileBack").onclick=function(){document.querySelector(".chat-wrap").classList.remove("room-open");ti.blur();};
  function syncHeight(){document.documentElement.style.setProperty("--chat-vh",Math.max(420,Math.round(window.visualViewport?window.visualViewport.height:window.innerHeight))+"px");}
  syncHeight();window.addEventListener("resize",syncHeight);if(window.visualViewport)window.visualViewport.addEventListener("resize",syncHeight);

  renderQuick();pollRooms();if(INITIAL_ROOM)openRoom(INITIAL_ROOM);
  setInterval(pollRooms,5000);setInterval(pollVisitors,5000);setInterval(function(){if(cur)loadMsgs(false);},3000);
})();
</script>';
?>
