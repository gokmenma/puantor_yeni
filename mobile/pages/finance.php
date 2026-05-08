<?php
// Puantor Mobil - Kasa & Finans Özeti
// Not: Gerçek finansal modellerinizi burada include etmelisiniz.
?>

<div class="container px-0">
  <div class="mb-4">
    <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Kasa & Finans</h2>
    <p class="text-muted text-xs mb-0">Bugünkü mali durumunuzun özeti.</p>
  </div>

  <!-- Kasa Bakiyesi Kartı -->
  <div class="mobile-card bg-primary text-white p-4 mb-4" style="border: none;">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold">Toplam Kasa Bakiyesi</span>
      <i class="ti ti-wallet" style="font-size: 1.5rem; opacity: 0.5;"></i>
    </div>
    <h3 class="mb-0 text-bold" style="font-size: 2rem;">₺ 124.500,00</h3>
    <div class="mt-3 d-flex gap-2">
      <span class="badge bg-white-10 text-white text-xs">+ ₺ 12.000 Bugün</span>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0 bg-green-lt text-green">
        <div class="text-xs text-uppercase font-weight-bold mb-1">Gelirler</div>
        <div class="text-bold h4 mb-0">₺ 45.200</div>
      </div>
    </div>
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0 bg-red-lt text-red">
        <div class="text-xs text-uppercase font-weight-bold mb-1">Giderler</div>
        <div class="text-bold h4 mb-0">₺ 18.450</div>
      </div>
    </div>
  </div>

  <h4 class="mt-4 mb-3 text-semibold" style="font-size: 0.95rem;">Son İşlemler</h4>
  <div class="list-group list-group-mobile">
    <div class="list-group-item d-flex align-items-center justify-content-between py-3">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-sm rounded bg-green-lt text-green">
          <i class="ti ti-arrow-up-right"></i>
        </div>
        <div>
          <div class="text-bold text-sm">Hakediş Ödemesi</div>
          <div class="text-muted text-xs">A-Blok İnşaat</div>
        </div>
      </div>
      <div class="text-green text-bold text-sm">+ ₺ 12.000</div>
    </div>
    <div class="list-group-item d-flex align-items-center justify-content-between py-3">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-sm rounded bg-red-lt text-red">
          <i class="ti ti-arrow-down-left"></i>
        </div>
        <div>
          <div class="text-bold text-sm">Akaryakıt Alımı</div>
          <div class="text-muted text-xs">Şantiye Araçları</div>
        </div>
      </div>
      <div class="text-red text-bold text-sm">- ₺ 3.500</div>
    </div>
  </div>
</div>
