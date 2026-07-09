<?php
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/KvkkTalepModel.php';
require_once ROOT . '/Model/VeriIhlalerModel.php';

$authsObj = new Auths();
$authsObj->checkAuthorize('kvkk_talepler_yonet');

$firma_id    = (int) $_SESSION['firm_id'];
$talepModel  = new KvkkTalepModel();
$ihlalModel  = new VeriIhlalerModel();

$talepOzet   = $talepModel->getOzet($firma_id);
$bekleyenIhlaller = $ihlalModel->getBekleyenBildirimler($firma_id);
?>

<div class="container-xl mt-3">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">KVKK Uyum Merkezi</h2>
                <div class="text-muted mt-1">Kişisel Verilerin Korunması Kanunu yükümlülükleri</div>
            </div>
        </div>
    </div>

    <?php if (!empty($bekleyenIhlaller)): ?>
    <div class="alert alert-danger mb-4">
        <div class="d-flex">
            <div class="me-3"><i class="ti ti-alert-triangle" style="font-size:2rem"></i></div>
            <div>
                <h4 class="alert-title">72 Saat Uyarısı!</h4>
                <strong><?php echo count($bekleyenIhlaller); ?> adet veri ihlali</strong> için KVKK bildirim süresi dolmak üzere veya geçmiş.
                <a href="#" class="route-link" data-page="kvkk/ihlaller">Veri ihlallerine git →</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ((int)$talepOzet->suresi_gecen > 0): ?>
    <div class="alert alert-warning mb-4">
        <i class="ti ti-clock me-2"></i>
        <strong><?php echo (int)$talepOzet->suresi_gecen; ?> adet KVKK başvurusu</strong> için 30 günlük yanıt süresi geçmiş.
        <a href="#" class="route-link" data-page="kvkk/talepler">Başvurulara git →</a>
    </div>
    <?php endif; ?>

    <div class="row row-deck row-cards">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-file-text me-2 text-blue"></i>İlgili Kişi Başvuruları</h3>
                    <div class="ms-auto">
                        <a href="#" class="btn btn-sm btn-primary route-link" data-page="kvkk/talepler">Tümünü Gör</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-6 col-lg-3">
                            <div class="h2 mb-0 text-warning"><?php echo (int)$talepOzet->bekliyor; ?></div>
                            <div class="text-muted small">Bekliyor</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="h2 mb-0 text-info"><?php echo (int)$talepOzet->isleniyor; ?></div>
                            <div class="text-muted small">İşleniyor</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="h2 mb-0 text-success"><?php echo (int)$talepOzet->tamamlandi; ?></div>
                            <div class="text-muted small">Tamamlandı</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="h2 mb-0 text-danger"><?php echo (int)$talepOzet->suresi_gecen; ?></div>
                            <div class="text-muted small">Süresi Geçen</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-shield-x me-2 text-red"></i>Veri İhlalleri</h3>
                    <div class="ms-auto">
                        <a href="#" class="btn btn-sm btn-danger route-link" data-page="kvkk/ihlaller">İhlal Kaydet</a>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Veri ihlali tespit edildiğinde <strong>72 saat</strong> içinde KVKK Kuruluna bildirim yapılmalıdır.</p>
                    <?php if (empty($bekleyenIhlaller)): ?>
                    <span class="badge bg-success-lt">Bekleyen bildirimi olmayan ihlal yok</span>
                    <?php else: ?>
                    <span class="badge bg-danger"><?php echo count($bekleyenIhlaller); ?> bildirim bekliyor</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-checklist me-2 text-teal"></i>KVKK Teknik Kontrol Listesi</h3>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success"><i class="ti ti-check"></i></span>
                                TC Kimlik No, IBAN — AES-256-GCM ile şifreleniyor
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success"><i class="ti ti-check"></i></span>
                                Telefon ve e-posta — şifreli saklanıyor
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success"><i class="ti ti-check"></i></span>
                                Şifreleme anahtarı .env dosyasında, kod içinde değil
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success"><i class="ti ti-check"></i></span>
                                Kullanıcı şifreleri bcrypt ile saklanıyor
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success"><i class="ti ti-check"></i></span>
                                İlgili kişi başvuruları (KVKK Md.11) takip ediliyor
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success"><i class="ti ti-check"></i></span>
                                Veri ihlali kayıt ve 72s bildirim takibi yapılıyor
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success"><i class="ti ti-check"></i></span>
                                Personel ekleme sırasında aydınlatma onayı kaydediliyor
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning-lt"><i class="ti ti-clock"></i></span>
                                Otomatik anonimleştirme: cron/kvkk_anonymize.php (10 yıl)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
