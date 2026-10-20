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

  var activeEditCancel = null;

  function startEditMessage(m, bubble, mdWrap, tools) {
    if (activeEditCancel) {
      activeEditCancel();
      activeEditCancel = null;
    }

    mdWrap.style.display = 'none';
    tools.style.display = 'none';

    var editBox = document.createElement('div');
    editBox.className = 'message-edit-box';

    var textarea = document.createElement('textarea');
    textarea.className = 'message-edit-textarea';
    textarea.value = m.text;
    textarea.setAttribute('autofocus', '');
    textarea.setAttribute('tabindex', '0');

    var footer = document.createElement('div');
    footer.className = 'message-edit-footer';

    var hint = document.createElement('span');
    hint.className = 'message-edit-hint';
    hint.innerHTML = 'Ctrl+Enter simpan &middot; Esc batal';

    var actions = document.createElement('div');
    actions.className = 'message-edit-actions';

    var cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'icon-btn';
    cancelBtn.textContent = 'Batal';

    var saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.className = 'icon-btn';
    saveBtn.style.background = 'var(--me)';
    saveBtn.style.color = '#fff';
    saveBtn.textContent = 'Simpan';

    actions.appendChild(cancelBtn);
    actions.appendChild(saveBtn);

    footer.appendChild(hint);
    footer.appendChild(actions);

    if (window.getSelection) {
      window.getSelection().removeAllRanges();
    }

    editBox.appendChild(textarea);
    editBox.appendChild(footer);

    bubble.appendChild(editBox);

    function ensureCursorVisible() {
      if (!thread || !textarea) return;
      var taRect = textarea.getBoundingClientRect();
      var threadRect = thread.getBoundingClientRect();
      var text = textarea.value;
      var cursorPos = textarea.selectionEnd || 0;
      var textBeforeCursor = text.substring(0, cursorPos);
      var totalLines = (text.split('\n').length) || 1;
      var currentLine = (textBeforeCursor.split('\n').length) || 1;
      var lineRatio = currentLine / totalLines;
      var estimatedCursorY = taRect.top + (taRect.height * lineRatio);

      if (estimatedCursorY > threadRect.bottom - 60) {
        var diff = estimatedCursorY - (threadRect.bottom - 60);
        thread.scrollTop += diff;
      } else if (estimatedCursorY < threadRect.top + 40) {
        var diff = (threadRect.top + 40) - estimatedCursorY;
        thread.scrollTop -= diff;
      }
    }

    function adjustEditHeight() {
      var prevTaScrollTop = textarea.scrollTop;
      var prevThreadScrollTop = thread ? thread.scrollTop : 0;

      var mirror = document.getElementById('chat-viewer-resize-mirror');
      if (!mirror) {
        mirror = document.createElement('textarea');
        mirror.id = 'chat-viewer-resize-mirror';
        mirror.setAttribute('aria-hidden', 'true');
        mirror.setAttribute('tabindex', '-1');
        mirror.style.position = 'fixed';
        mirror.style.top = '-9999px';
        mirror.style.left = '-9999px';
        mirror.style.visibility = 'hidden';
        mirror.style.pointerEvents = 'none';
        mirror.style.zIndex = '-9999';
        mirror.style.opacity = '0';
        mirror.style.overflow = 'hidden';
        mirror.style.resize = 'none';
        document.body.appendChild(mirror);
      }

      var computed = window.getComputedStyle(textarea);
      mirror.style.boxSizing = computed.boxSizing;
      mirror.style.width = textarea.clientWidth + 'px';
      mirror.style.fontFamily = computed.fontFamily;
      mirror.style.fontSize = computed.fontSize;
      mirror.style.lineHeight = computed.lineHeight;
      mirror.style.padding = computed.padding;
      mirror.style.border = computed.border;
      mirror.style.letterSpacing = computed.letterSpacing;
      mirror.style.whiteSpace = 'pre-wrap';
      mirror.style.wordBreak = 'break-word';
      mirror.value = textarea.value;

      mirror.style.height = '0px';
      var targetHeight = Math.max(80, mirror.scrollHeight + 4);
      textarea.style.height = targetHeight + 'px';

      if (thread && thread.scrollTop !== prevThreadScrollTop) {
        thread.scrollTop = prevThreadScrollTop;
      }
      if (prevTaScrollTop > 0) {
        textarea.scrollTop = prevTaScrollTop;
      }
    }

    adjustEditHeight();
    textarea.addEventListener('input', adjustEditHeight);
    textarea.addEventListener('keyup', function(e) {
      if (e.key === 'Enter' || e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        ensureCursorVisible();
      }
    });

    function cancelEdit() {
      if (editBox.parentNode) {
        editBox.parentNode.removeChild(editBox);
      }
      mdWrap.style.display = '';
      tools.style.display = '';
      activeEditCancel = null;
    }
    activeEditCancel = cancelEdit;

    cancelBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      cancelEdit();
    });

    function saveEdit() {
      var newText = textarea.value.trim();
      if (!newText) {
        alert('Pesan tidak boleh kosong.');
        textarea.focus();
        return;
      }
      m.text = newText;
      m.isEdited = true;
      activeEditCancel = null;
      renderAll();
    }

    saveBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      saveEdit();
    });

    textarea.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        e.preventDefault();
        e.stopPropagation();
        cancelEdit();
      } else if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        saveEdit();
      }
    });

    function focusAndPlaceCursor() {
      if (!textarea || !document.body.contains(textarea)) return;
      textarea.focus({ preventScroll: true });
      try {
        textarea.setSelectionRange(0, 0);
      } catch (err) {}
      textarea.scrollTop = 0;

      if (thread && typeof editBox.scrollIntoView === 'function') {
        var threadRect = thread.getBoundingClientRect();
        var editRect = editBox.getBoundingClientRect();
        if (editRect.top < threadRect.top || editRect.top > threadRect.bottom - 40) {
          editBox.scrollIntoView({ behavior: 'auto', block: 'start' });
        }
      }
    }

    focusAndPlaceCursor();
    requestAnimationFrame(focusAndPlaceCursor);
  }

  function renderAll(){
    activeEditCancel = null;
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
      var editedBadge = m.isEdited ? '<span class="edited-tag">(diedit)</span>' : '';
      meta.innerHTML = '<span class="who">' + (m.sender === 'me' ? 'Me' : 'Other') + '</span><span>' + m.time + '</span>' + editedBadge;

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
        '<div style="display:flex;align-items:center;gap:8px;">' +
        '<button class="toggle-btn" id="toggle-' + m.id + '">&#8964; tampilkan semua</button>' +
        '<button type="button" class="edit-btn" title="Edit teks pesan" data-id="' + m.id + '" aria-label="Edit pesan">' +
        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>' +
        '<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>' +
        '</svg>' +
        '</button>' +
        '<button type="button" class="del-btn" title="Hapus pesan" data-id="' + m.id + '">&times;</button>' +
        '</div>';

      bubble.appendChild(tools);

      wrap.appendChild(meta);
      wrap.appendChild(bubble);
      row.appendChild(wrap);
      thread.appendChild(row);

      var editBtn = tools.querySelector('.edit-btn');
      if (editBtn) {
        editBtn.addEventListener('mousedown', function(e) {
          e.preventDefault();
        });
        editBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          startEditMessage(m, bubble, mdWrap, tools);
        });
      }

      bubble.addEventListener('dblclick', function(e) {
        if (e.target.closest('pre, code, a, button, input, textarea, .code-block-tools, .bubble-tools, .message-edit-box')) {
          return;
        }
        if (window.getSelection) {
          window.getSelection().removeAllRanges();
        }
        startEditMessage(m, bubble, mdWrap, tools);
      });

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
