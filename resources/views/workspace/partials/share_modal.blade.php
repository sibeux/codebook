<!-- Modal: Share Note -->
<div class="modal-overlay" id="shareModal">
    <div class="modal" style="max-width:500px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
            <div>
                <h3 class="modal-title" style="margin:0;">Bagikan Catatan</h3>
                <p id="share-modal-note-title" style="font-size:12px;color:var(--muted);margin:4px 0 0 0;font-weight:500;word-break:break-word;"></p>
            </div>
            <button type="button" class="icon-btn" onclick="closeModal('shareModal')" title="Tutup Modal" style="padding:4px 8px;font-size:12px;">&times;</button>
        </div>

        {{-- Visibility Switcher --}}
        <div class="form-group" style="margin-bottom:16px;">
            <label style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block;">Pengaturan Visibilitas</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <button type="button" id="share-visibility-private" class="icon-btn" style="justify-content:center;padding:10px 8px;font-size:12px;display:flex;align-items:center;gap:6px;border-radius:8px;" onclick="setNoteVisibility(false)">
                    <span>&#128274; Pribadi</span>
                </button>
                <button type="button" id="share-visibility-public" class="icon-btn" style="justify-content:center;padding:10px 8px;font-size:12px;display:flex;align-items:center;gap:6px;border-radius:8px;" onclick="setNoteVisibility(true)">
                    <span>&#127760; Publik</span>
                </button>
            </div>
            <div id="share-visibility-desc" style="font-size:11px;color:var(--muted);margin-top:6px;line-height:1.4;">
                <!-- dynamic description -->
            </div>
        </div>

        {{-- Share Link --}}
        <div class="form-group" style="margin-bottom:12px;">
            <label style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block;">Tautan Berbagi (Share Link)</label>
            <div style="display:flex;gap:8px;">
                <input type="text" id="share-url-input" class="modal-input" readonly onclick="this.select()" style="font-family:'JetBrains Mono', monospace;font-size:12px;background:var(--panel-2);cursor:pointer;" title="Klik untuk pilih semua">
                <button type="button" class="icon-btn btn-primary" id="copy-share-url-btn" onclick="copyShareUrl()" style="white-space:nowrap;padding:0 14px;font-size:12px;">
                    <span id="copy-btn-text">Salin Tautan</span>
                </button>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                <a id="open-share-link" href="#" target="_blank" rel="noopener noreferrer" style="font-size:11.5px;color:var(--primary);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                    <span>Pratinjau tampilan share &nearr;</span>
                </a>
                <button type="button" class="mini-btn" onclick="regenerateShareLink()" title="Ganti tautan acak jika tautan lama perlu dicabut" style="font-size:11px;color:var(--muted);background:transparent;border:none;cursor:pointer;">
                    &#8635; Buat Ulang Tautan
                </button>
            </div>
        </div>

        <div class="modal-actions" style="margin-top:16px;">
            <button type="button" class="icon-btn" onclick="closeModal('shareModal')">Selesai</button>
        </div>
    </div>
</div>
