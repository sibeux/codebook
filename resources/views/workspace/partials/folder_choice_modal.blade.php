<!-- Modal: Choice Add Item (Note or Folder) -->
<div class="modal-overlay" id="folderAddChoiceModal" role="dialog" aria-modal="true" aria-labelledby="choice-modal-title">
    <div class="modal choice-modal">
        <div class="choice-modal-header">
            <h3 class="modal-title" id="choice-modal-title">Tambah Item Baru</h3>
            <button type="button" class="mini-btn" onclick="closeModal('folderAddChoiceModal')" title="Tutup" aria-label="Tutup" style="font-size:16px;padding:2px 6px;">&times;</button>
        </div>

        <p class="choice-modal-subtitle">
            Pilih item yang ingin ditambahkan ke <strong id="choice-modal-folder-name">Folder</strong>:
        </p>

        <div class="choice-options-list">
            <button type="button" class="choice-option-btn" id="choice-note-btn" onclick="chooseAddNote()">
                <div class="choice-option-icon">&#128221;</div>
                <div class="choice-option-content">
                    <div class="choice-option-title">Catatan Baru</div>
                    <div class="choice-option-desc">Buat lembar catatan baru di dalam folder ini</div>
                </div>
                <span class="choice-option-arrow">&rarr;</span>
            </button>

            <button type="button" class="choice-option-btn" id="choice-folder-btn" onclick="chooseAddFolder()">
                <div class="choice-option-icon">&#128194;</div>
                <div class="choice-option-content">
                    <div class="choice-option-title">Subfolder Baru</div>
                    <div class="choice-option-desc" id="choice-folder-desc">Buat subfolder untuk mengelompokkan catatan</div>
                </div>
                <span class="choice-option-arrow">&rarr;</span>
            </button>
        </div>

        <div class="modal-actions" style="margin-top:4px;">
            <button type="button" class="icon-btn" onclick="closeModal('folderAddChoiceModal')">Batal</button>
        </div>
    </div>
</div>
