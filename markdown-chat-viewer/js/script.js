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

  function setupCodeBlocks(container) {
    if (!container) return;
    container.querySelectorAll('pre').forEach(function(pre) {
      if (pre.parentElement && pre.parentElement.classList.contains('code-block-wrap')) {
        return;
      }

      var code = pre.querySelector('code');
      if (!code) return;

      if (!code.classList.contains('hljs') && window.hljs) {
        hljs.highlightElement(code);
      }

      var langName = '';
      var langMatch = code.className.match(/\blanguage-([a-zA-Z0-9_-]+)\b/i);
      if (langMatch && langMatch[1]) {
        var rawLang = langMatch[1].toLowerCase();
        if (rawLang !== 'undefined' && rawLang !== 'hljs') {
          langName = rawLang;
        }
      }

      var wrapper = document.createElement('div');
      wrapper.className = 'code-block-wrap' + (langName ? ' has-lang' : '');

      pre.parentNode.insertBefore(wrapper, pre);
      wrapper.appendChild(pre);

      var tools = document.createElement('div');
      tools.className = 'code-block-tools';

      if (langName) {
        var langBadge = document.createElement('span');
        langBadge.className = 'code-lang-badge';
        langBadge.textContent = langName;
        tools.appendChild(langBadge);
      }

      var copyBtn = document.createElement('button');
      copyBtn.type = 'button';
      copyBtn.className = 'code-copy-btn';
      copyBtn.setAttribute('title', 'Salin kode');
      copyBtn.setAttribute('aria-label', 'Salin kode');
      copyBtn.innerHTML =
        '<svg class="copy-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>' +
          '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>' +
        '</svg>' +
        '<svg class="check-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;">' +
          '<polyline points="20 6 9 17 4 12"></polyline>' +
        '</svg>' +
        '<span class="copy-btn-text">Salin</span>';

      copyBtn.addEventListener('click', async function(e) {
        e.stopPropagation();
        var textToCopy = (code.innerText || code.textContent || '')
          .replace(/\r\n/g, '\n')
          .replace(/\n$/, '');

        var copyText = async function(text) {
          if (navigator.clipboard && window.isSecureContext) {
            try {
              await navigator.clipboard.writeText(text);
              return true;
            } catch(err) {
              console.warn('Clipboard API error, using fallback', err);
            }
          }
          try {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            ta.style.top = '-9999px';
            ta.setAttribute('readonly', '');
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            var success = document.execCommand('copy');
            document.body.removeChild(ta);
            return success;
          } catch(fallbackErr) {
            console.error('Fallback copy failed', fallbackErr);
            return false;
          }
        };

        var isSuccess = await copyText(textToCopy);
        var copyIcon = copyBtn.querySelector('.copy-icon');
        var checkIcon = copyBtn.querySelector('.check-icon');
        var btnText = copyBtn.querySelector('.copy-btn-text');

        if (isSuccess !== false) {
          copyBtn.classList.add('copied');
          if (copyIcon) copyIcon.style.display = 'none';
          if (checkIcon) checkIcon.style.display = 'inline-block';
          if (btnText) btnText.textContent = 'Tersalin!';

          setTimeout(function() {
            copyBtn.classList.remove('copied');
            if (copyIcon) copyIcon.style.display = 'inline-block';
            if (checkIcon) checkIcon.style.display = 'none';
            if (btnText) btnText.textContent = 'Salin';
          }, 2000);
        } else {
          if (btnText) btnText.textContent = 'Gagal!';
          setTimeout(function() {
            if (btnText) btnText.textContent = 'Salin';
          }, 2000);
        }
      });

      tools.appendChild(copyBtn);
      wrapper.appendChild(tools);
    });
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
      setupCodeBlocks(mdDiv);

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
