  marked.setOptions({ breaks: true, gfm: true });

  var thread = document.getElementById('thread');
  var emptyState = document.getElementById('empty-state');
  var input = document.getElementById('msg-input');
  var sendBtn = document.getElementById('send-btn');
  var clearBtn = document.getElementById('clear-btn');
  var senderButtons = document.querySelectorAll('.sender-toggle button');
  var themeBtn = document.getElementById('theme-btn');
  var fontSlider = document.getElementById('font-size');
  var fontSizeOut = document.getElementById('font-size-out');
  var currentSender = 'me';
  var messages = [];
  var idCounter = 0;

  senderButtons.forEach(function(btn){
    btn.addEventListener('click', function(){
      senderButtons.forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      currentSender = btn.getAttribute('data-sender');
    });
  });

  var hljsDark = document.getElementById('hljs-dark');
  var hljsLight = document.getElementById('hljs-light');

  themeBtn.addEventListener('click', function(){
    var isLight = document.documentElement.getAttribute('data-theme') === 'light';
    if (isLight) {
      document.documentElement.removeAttribute('data-theme');
      themeBtn.textContent = 'Light mode';
      hljsDark.disabled = false;
      hljsLight.disabled = true;
    } else {
      document.documentElement.setAttribute('data-theme', 'light');
      themeBtn.textContent = 'Dark mode';
      hljsDark.disabled = true;
      hljsLight.disabled = false;
    }
  });

  fontSlider.addEventListener('input', function(){
    var val = fontSlider.value;
    document.documentElement.style.setProperty('--chat-font-size', val + 'px');
    fontSizeOut.textContent = val + 'px';
  });

  input.addEventListener('keydown', function(e){
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      sendMessage();
    }
  });

  sendBtn.addEventListener('click', sendMessage);

  clearBtn.addEventListener('click', function(){
    if (messages.length === 0) return;
    if (confirm('Hapus semua pesan di sesi ini?')) {
      messages = [];
      renderAll();
    }
  });

  function sendMessage(){
    var text = input.value;
    if (!text.trim()) return;
    idCounter++;
    messages.push({
      id: idCounter,
      sender: currentSender,
      text: text,
      time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    });
    input.value = '';
    renderAll();
    thread.scrollTop = thread.scrollHeight;
  }

  function deleteMessage(id){
    messages = messages.filter(function(m){ return m.id !== id; });
    renderAll();
  }

  function toggleCollapse(id){
    var el = document.getElementById('md-' + id);
    var fade = document.getElementById('fade-' + id);
    var btn = document.getElementById('toggle-' + id);
    var collapsed = el.classList.toggle('collapsed');
    if (fade) fade.style.display = collapsed ? 'block' : 'none';
    if (btn) btn.innerHTML = collapsed ? '&#8964; tampilkan semua' : '&#8963; sembunyikan';
  }

  function renderAll(){
    thread.innerHTML = '';
    if (messages.length === 0) {
      thread.appendChild(emptyState);
      return;
    }
    messages.forEach(function(m){
      var row = document.createElement('div');
      row.className = 'row ' + m.sender;

      var wrap = document.createElement('div');
      wrap.className = 'bubble-wrap';

      var meta = document.createElement('div');
      meta.className = 'meta';
      meta.innerHTML = '<span class="who">' + (m.sender === 'me' ? 'Me' : 'Other') + '</span><span>' + m.time + '</span>';

      var bubble = document.createElement('div');
      bubble.className = 'bubble';

      var mdWrap = document.createElement('div');
      mdWrap.className = 'md-wrap';

      var mdDiv = document.createElement('div');
      mdDiv.className = 'md';
      mdDiv.id = 'md-' + m.id;
      var rawHtml = marked.parse(m.text);
      mdDiv.innerHTML = DOMPurify.sanitize(rawHtml);
      mdDiv.querySelectorAll('pre code').forEach(function(block){
        hljs.highlightElement(block);
      });

      var fade = document.createElement('div');
      fade.className = 'fade';
      fade.id = 'fade-' + m.id;

      mdWrap.appendChild(mdDiv);
      mdWrap.appendChild(fade);
      bubble.appendChild(mdWrap);

      var lineCount = m.text.split('\n').length;
      var charCount = m.text.length;

      var tools = document.createElement('div');
      tools.className = 'bubble-tools';
      tools.innerHTML =
        '<span class="linecount">' + lineCount + ' baris &middot; ' + charCount + ' karakter</span>' +
        '<div style="display:flex;align-items:center;gap:12px;">' +
        '<button class="toggle-btn" id="toggle-' + m.id + '">&#8964; tampilkan semua</button>' +
        '<button class="del-btn" title="Hapus pesan" data-id="' + m.id + '">&times;</button>' +
        '</div>';

      bubble.appendChild(tools);

      wrap.appendChild(meta);
      wrap.appendChild(bubble);
      row.appendChild(wrap);
      thread.appendChild(row);

      tools.querySelector('.del-btn').addEventListener('click', function(){
        deleteMessage(m.id);
      });

      setTimeout(function(){
        var toggleBtn = document.getElementById('toggle-' + m.id);
        if (mdDiv.scrollHeight > 340) {
          mdDiv.classList.add('collapsed');
          fade.style.display = 'block';
          toggleBtn.style.display = 'flex';
          toggleBtn.addEventListener('click', function(){ toggleCollapse(m.id); });
        }
      }, 0);
    });
  }
