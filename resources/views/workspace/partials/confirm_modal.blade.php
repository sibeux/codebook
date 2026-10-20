<!-- Modal: Confirmation Dialog -->
<div class="modal-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-message">
    <div class="modal confirm-modal">
        <div class="confirm-modal-body">
            <div class="confirm-modal-icon danger" id="confirm-modal-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"></path>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
            </div>
            <div class="confirm-modal-text">
                <h3 class="modal-title" id="confirm-modal-title">Konfirmasi Hapus</h3>
                <p class="confirm-modal-message" id="confirm-modal-message">Apakah Anda yakin ingin menghapus item ini?</p>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="icon-btn" id="confirm-modal-cancel-btn">Batal</button>
            <button type="button" class="icon-btn btn-danger" id="confirm-modal-confirm-btn">Hapus</button>
        </div>
    </div>
</div>
