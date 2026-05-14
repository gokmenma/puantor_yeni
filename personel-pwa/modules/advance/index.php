<div id="advance-tab" class="tab-content active">
    <!-- Header Title -->
    <div class="page-header mb-4">
        <h2 class="h1 mb-0 fw-bold text-dark" style="letter-spacing: -1px;">Avans & Finans</h2>
        <p class="text-muted small mb-0">Avans talepleriniz ve güncel bakiyeniz.</p>
    </div>

    <!-- Blue Summary Card -->
    <div class="summary-card mb-4" style="background: linear-gradient(135deg, #206bc4 0%, #115099 100%); position: relative; overflow: hidden; border-radius: 24px; min-height: 160px; display: flex; flex-direction: column; justify-content: center; padding: 1.5rem;">
        <p class="text-white-50 extra-small text-uppercase mb-1 fw-bold" style="letter-spacing: 1px;">KULLANILABİLİR AVANS LİMİTİ</p>
        <div class="d-flex align-items-baseline gap-1 text-white">
            <span class="fs-1 fw-bold opacity-75">₺</span>
            <h2 id="available-advance-limit-large" class="display-5 mb-0 fw-bold" style="letter-spacing: -1px;">0,00</h2>
        </div>
        <div class="mt-3">
            <span class="badge rounded-pill px-3 py-2 extra-small border-0 shadow-none" style="background: rgba(255,255,255,0.15); color: #fff;">
                <i class="ti ti-trending-up me-1"></i> + ₺ 0,00 Bugün
            </span>
        </div>
        <!-- Wallet Icon Illustration (Decorative) -->
        <i class="ti ti-wallet text-white-50 position-absolute" style="font-size: 9rem; right: -1.5rem; bottom: -2rem; opacity: 0.12; transform: rotate(-10deg);"></i>
    </div>

    <!-- List Section Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
        <h3 class="h3 mb-0 fw-bold text-dark">Son İşlemler</h3>
        <div class="btn-group btn-group-sm p-1 bg-light rounded-pill">
            <button class="btn btn-white border-0 rounded-pill px-3 py-1 shadow-none active extra-small fw-bold">Tümü</button>
            <button class="btn btn-light border-0 rounded-pill px-3 py-1 shadow-none extra-small fw-bold opacity-50">Onaylı</button>
        </div>
    </div>

    <div class="mobile-card p-0 border-0 shadow-sm overflow-hidden mb-5" style="border-radius: 20px; background: #fff;">
        <div id="advance-list" class="divide-y">
            <!-- Dynamic -->
        </div>
    </div>
</div>

<!-- FAB -->
<button id="btn-new-advance" class="mobile-fab">
    <i class="ti ti-plus fs-1"></i>
</button>
