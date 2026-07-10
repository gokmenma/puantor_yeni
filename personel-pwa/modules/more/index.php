<?php
require_once __DIR__ . "/../../../Model/PersonIcra.php";
$personIcraObj = new PersonIcra();
$icraFiles = $personIcraObj->getByPersonId($_SESSION['personel_id']);
$hasIcra = count($icraFiles) > 0;
?>
<div id="more-tab" class="tab-content active">
    <!-- Header/Avatar Area inside More -->
    <div class="profile-cover mb-4">
        <div class="d-flex align-items-center gap-4">
            <div class="avatar avatar-xl rounded bg-primary text-white fw-bold shadow-sm" id="more-initials" style="font-size: 1.5rem;">
                ??
            </div>
            <div>
                <h2 id="more-name" class="h2 mb-1" style="font-weight: 800;">İsim Soyisim</h2>
                <p id="more-id" class="text-muted small mb-0" style="font-weight: 500;">ID: EMP-000</p>
            </div>
        </div>
    </div>

    <!-- Menü Elemanları Listesi -->
    <div class="list-group shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
        <!-- Profilim Linki -->
        <a href="?route=profile" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 border-0 border-bottom border-light">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-sm bg-primary-lt text-primary rounded-circle">
                    <i class="ti ti-user fs-2"></i>
                </div>
                <div>
                    <strong class="text-dark d-block">Profil Bilgilerim</strong>
                    <span class="text-muted small">Kişisel bilgiler, IBAN ve şifre işlemleri</span>
                </div>
            </div>
            <i class="ti ti-chevron-right text-muted fs-2"></i>
        </a>

        <?php if ($hasIcra): ?>
        <!-- İcra Kesintilerim Linki -->
        <a href="?route=icra" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-sm bg-warning-lt text-warning rounded-circle">
                    <i class="ti ti-scale fs-2"></i>
                </div>
                <div>
                    <strong class="text-dark d-block">İcra Kesintilerim</strong>
                    <span class="text-muted small">İcra dosyaları, ödeme durumu ve detaylar</span>
                </div>
            </div>
            <i class="ti ti-chevron-right text-muted fs-2"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
