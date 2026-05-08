<?php
// Puantor Mobil - Bordro Listesi (Kasa Tasarımı Uyumlu)
?>

<div class="container px-0">
  <div class="mb-4 d-flex align-items-center justify-content-between">
    <div>
      <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Bordrolar</h2>
      <p class="text-muted text-xs mb-0">Personel hakediş ve ödemeleri.</p>
    </div>
    <select class="form-select form-select-sm border-0 bg-secondary-lt" style="width: auto;">
      <option>2024</option>
      <option>2023</option>
    </select>
  </div>

  <!-- Aktif Dönem Özeti Kartı (Kasa Bakiyesi Kartı Tasarımı) -->
  <div class="mobile-card bg-primary text-white p-4 mb-4" style="border: none;">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold">Nisan 2024 (Aktif Dönem)</span>
      <i class="ti ti-calendar-event" style="font-size: 1.5rem; opacity: 0.5;"></i>
    </div>
    <h3 class="mb-0 text-bold" style="font-size: 2rem;">₺ 845.000,00</h3>
    <div class="mt-3 d-flex align-items-center justify-content-between w-100">
      <span class="badge bg-white-10 text-white text-xs">42 Personel</span>
      <button class="btn btn-sm text-white px-3 py-1 text-xs btn-active-scale" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); border-radius: 8px;">Hesapla</button>
    </div>
  </div>

  <!-- Hakediş ve Ödeme İki Kolonlu Kart Yapısı -->
  <div class="row g-3">
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0 bg-green-lt text-green">
        <div class="text-xs text-uppercase font-weight-bold mb-1">Toplam Ödenen</div>
        <div class="text-bold h4 mb-0">₺ 120.000</div>
      </div>
    </div>
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0 bg-red-lt text-red">
        <div class="text-xs text-uppercase font-weight-bold mb-1">Kalan Ödeme</div>
        <div class="text-bold h4 mb-0">₺ 725.000</div>
      </div>
    </div>
  </div>

  <h4 class="mt-4 mb-3 text-semibold" style="font-size: 0.95rem;">Geçmiş Dönemler</h4>
  <div class="list-group list-group-mobile">
    <div class="list-group-item d-flex align-items-center justify-content-between py-3">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-sm rounded bg-blue-lt text-blue">
          <i class="ti ti-calendar-event"></i>
        </div>
        <div>
          <div class="text-bold text-sm">Mart 2024</div>
          <div class="text-muted text-xs">40 Personel • Tamamı Ödendi</div>
        </div>
      </div>
      <div class="text-bold text-sm text-dark">₺ 790.000</div>
    </div>
    <div class="list-group-item d-flex align-items-center justify-content-between py-3">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-sm rounded bg-blue-lt text-blue">
          <i class="ti ti-calendar-event"></i>
        </div>
        <div>
          <div class="text-bold text-sm">Şubat 2024</div>
          <div class="text-muted text-xs">38 Personel • Tamamı Ödendi</div>
        </div>
      </div>
      <div class="text-bold text-sm text-dark">₺ 750.000</div>
    </div>
  </div>
</div>
