<?php

//Sayfaya erişim yetkisi kontrolü
$Auths->checkAuthorize('upload_payment_permission');

?>


<div class="container-xl mt-3">
    <div class="page-header">
        <h2>Personel Yükleme</h2>
    </div>

    <div class="alert alert-info border-0 shadow-sm rounded-3">
        <div class="d-flex align-items-center">
            <i class="ti ti-info-circle fs-2 me-3"></i>
            <div>
                <h4 class="alert-title mb-1">Önemli Bilgilendirme</h4>
                <div class="text-secondary">
                    <ul class="mb-0">
                        <li><strong>TC Kimlik Kontrolü:</strong> Sistem, yüklediğiniz dosyadaki TC Kimlik numaralarını kontrol eder. Eğer personel sistemde kayıtlıysa bilgileri <strong>güncellenir</strong>, kayıtlı değilse <strong>yeni kayıt</strong> olarak eklenir.</li>
                        <li><strong>Projeler Sütunu:</strong> Şablona eklenen <strong>"Çalıştığı Projeler"</strong> (K Sütunu) alanına personelin çalıştığı projeleri virgül ile ayırarak yazabilirsiniz (Örn: Çamlıca Kule, İspir Site). Sistem bu projeleri otomatik olarak bulur veya yoksa yeni oluşturur.</li>
                        <li><strong>Ekip Sütunu:</strong> Şablona eklenen <strong>"Ekip"</strong> (L Sütunu) alanına personelin ekibini yazabilirsiniz.</li>
                        <li><strong>Meslek Sütunu:</strong> Şablona eklenen <strong>"Meslek"</strong> (M Sütunu) alanına personelin mesleğini/görevini yazabilirsiniz.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <form action="" method="post" id="personsLoadForm">


        <div class="row g-3 align-items-end mt-3">
            <div class="col-md-6 col-lg-7">
                <label for="persons-load-file" class="form-label fw-semibold">Dosya Seçin:</label>
                <input type="file" name="persons-load-file" id="persons-load-file" class="form-control shadow-sm">
            </div>

            <div class="col-md-6 col-lg-5">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="#" class="btn btn-primary d-inline-flex align-items-center" id="personsLoadButton" data-tooltip="Personelleri yükleyin">
                        <i class="ti ti-upload icon me-1"></i> Yükle
                    </a>
                    <a href="pages/persons/xls/person-load-from.xls" class="btn btn-outline-secondary d-inline-flex align-items-center" data-tooltip="Yüklenecek Şablonu indirin">
                        <i class="ti ti-download icon me-1"></i> Örnek Dosya İndir
                    </a>
                    <a href="#" class="btn btn-ghost-danger d-inline-flex align-items-center clear" data-tooltip="Formu Temizleyin">
                        <i class="ti ti-trash icon me-1"></i> Temizle
                    </a>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header">
                <h5>Personel Bilgileri</h5>
            </div>
            <div class="card-body">

                <div class="row">
                    <div id="result">
                        <table class="table" id="persons-load-table">
                            <thead>
                                <tr>
                                    
                                    <th>Ad Soyad</th>
                                    <th>Tc Kimlik</th>
                                    <th>İşe Başlama Tarihi</th>
                                    <th>İban Numarası</th>
                                    <th>Günlük/Aylık Ücret</th>
                                    <th>Telefon</th>
                                    <th>Email Adresi</th>
                                    <th>Beyaz/Mavi Yaka</th>
                                    <th>Adresi</th>
                                    <th>Açıklama</th>
                                    <th>Çalıştığı Projeler</th>
                                    <th>Ekip</th>
                                    <th>Meslek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr></tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </form>
</div>