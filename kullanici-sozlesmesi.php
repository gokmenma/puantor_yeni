<?php
date_default_timezone_set('Europe/Istanbul');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Sözleşmesi & KVKK Aydınlatma Metni | Puantor</title>
    <link rel="stylesheet" href="./dist/css/tabler.min.css">
    <style>
        body { background: #f4f6fb; }
        .sozlesme-wrapper { max-width: 860px; margin: 40px auto; padding: 0 16px 60px; }
        .sozlesme-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); padding: 48px 52px; }
        h1 { font-size: 1.7rem; font-weight: 700; color: #1a1a2e; border-bottom: 3px solid #0054a6; padding-bottom: 12px; margin-bottom: 8px; }
        .subtitle { color: #666; font-size: .92rem; margin-bottom: 32px; }
        h2 { font-size: 1.2rem; font-weight: 700; color: #0054a6; margin-top: 36px; margin-bottom: 6px; border-left: 4px solid #0054a6; padding-left: 10px; }
        h3 { font-size: 1rem; font-weight: 600; color: #333; margin-top: 20px; margin-bottom: 4px; }
        p, li { font-size: .95rem; color: #444; line-height: 1.75; }
        ul, ol { padding-left: 22px; }
        li { margin-bottom: 6px; }
        .definition-grid { display: grid; grid-template-columns: 160px 1fr; gap: 6px 16px; margin: 12px 0; }
        .def-term { font-weight: 600; color: #333; font-size: .9rem; }
        .def-desc { font-size: .9rem; color: #555; line-height: 1.6; }
        .info-box { background: #f0f5ff; border: 1px solid #c8d9f5; border-radius: 8px; padding: 16px 20px; margin: 16px 0; }
        .info-box p { margin: 0; font-size: .9rem; color: #2c4a8a; }
        .toc { background: #f8f9fa; border-radius: 8px; padding: 20px 28px; margin-bottom: 32px; }
        .toc h4 { font-size: .95rem; font-weight: 700; margin: 0 0 12px; color: #333; }
        .toc ol { margin: 0; padding-left: 20px; }
        .toc li { font-size: .88rem; color: #0054a6; margin-bottom: 4px; }
        .toc a { color: #0054a6; text-decoration: none; }
        .toc a:hover { text-decoration: underline; }
        .footer-note { font-size: .82rem; color: #888; text-align: center; margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px; }
        @media (max-width: 640px) { .sozlesme-card { padding: 28px 20px; } .definition-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="sozlesme-wrapper">

    <div style="text-align:center; margin-bottom:28px;">
        <a href="./index.php">
            <img src="./dist/img/puantor-logo.png" alt="Puantor" style="height:40px;" onerror="this.style.display='none'">
            <span style="font-size:1.4rem;font-weight:700;color:#0054a6;vertical-align:middle;">Puantor</span>
        </a>
    </div>

    <div class="sozlesme-card">

        <h1>Kullanıcı Sözleşmesi ve Hizmet Şartları</h1>
        <p class="subtitle">Son güncelleme: <?php echo date('d.m.Y'); ?> &nbsp;|&nbsp; Yürürlük tarihi: <?php echo date('d.m.Y'); ?></p>

        <div class="toc">
            <h4>İçindekiler</h4>
            <ol>
                <li><a href="#s1">Taraflar ve Tanımlar</a></li>
                <li><a href="#s2">Hizmetin Kapsamı</a></li>
                <li><a href="#s3">Üyelik ve Hesap Yönetimi</a></li>
                <li><a href="#s4">Abonelik Paketleri ve Ödeme Koşulları</a></li>
                <li><a href="#s5">Kullanıcı Yükümlülükleri</a></li>
                <li><a href="#s6">Hizmet Sağlayıcının Hak ve Yükümlülükleri</a></li>
                <li><a href="#s7">Fikri Mülkiyet Hakları</a></li>
                <li><a href="#s8">Veri İşleme Kuralları</a></li>
                <li><a href="#s9">Sınırlı Garanti ve Sorumluluk</a></li>
                <li><a href="#s10">Sözleşmenin Askıya Alınması ve Feshi</a></li>
                <li><a href="#s11">Değişiklikler</a></li>
                <li><a href="#s12">Uygulanacak Hukuk ve Uyuşmazlık Çözümü</a></li>
                <li><a href="#s13">KVKK Aydınlatma Metni</a></li>
            </ol>
        </div>

        <!-- BÖLÜM 1 -->
        <h2 id="s1">1. Taraflar ve Tanımlar</h2>
        <p>İşbu Kullanıcı Sözleşmesi ve Hizmet Şartları ("Sözleşme"), aşağıda tanımlanan taraflar arasında akdedilmiştir. Platforma kayıt olarak veya hizmetlerden yararlanarak bu Sözleşme'nin tüm hükümlerini okuduğunuzu, anladığınızı ve kabul ettiğinizi beyan etmiş olursunuz.</p>

        <h3>Taraflar</h3>
        <div class="definition-grid">
            <span class="def-term">Hizmet Sağlayıcı</span>
            <span class="def-desc"><strong>PUANTOR</strong> — www.puantor.com.tr alan adıyla faaliyet gösteren çevrimiçi puantaj, maaş ve personel yönetimi platformunu işleten tüzel/gerçek kişi (bundan böyle "PUANTOR" olarak anılacaktır).</span>
            <span class="def-term">Kullanıcı / Üye</span>
            <span class="def-desc">www.puantor.com.tr adresine kayıt olarak hizmetlerden yararlanan gerçek veya tüzel kişi (bundan böyle "Kullanıcı" veya "Üye" olarak anılacaktır).</span>
        </div>

        <h3>Tanımlar</h3>
        <div class="definition-grid">
            <span class="def-term">Platform</span>
            <span class="def-desc">PUANTOR'un sunduğu web tabanlı SaaS (yazılım hizmeti) uygulaması ve bütün alt sistemleri.</span>
            <span class="def-term">SaaS</span>
            <span class="def-desc">Yazılımın kullanıcıya internet üzerinden abonelik modeliyle sunulması (Software as a Service).</span>
            <span class="def-term">Abonelik</span>
            <span class="def-desc">Kullanıcının belirli bir süre ve kapsam dahilinde hizmetten yararlanmasına imkân tanıyan ücretli veya deneme planı.</span>
            <span class="def-term">Ana Hesap</span>
            <span class="def-desc">Platforma kaydolan ve aboneliği yöneten birincil kullanıcı hesabı.</span>
            <span class="def-term">Alt Kullanıcı</span>
            <span class="def-desc">Ana hesap tarafından sisteme davet edilen ve yetki kısıtları dahilinde platformu kullanan ikincil hesaplar.</span>
            <span class="def-term">Firma</span>
            <span class="def-desc">Kullanıcının platform üzerinde tanımladığı ve personel/puantaj verilerini yönettiği şirket ya da işletme birimi.</span>
            <span class="def-term">İçerik</span>
            <span class="def-desc">Kullanıcının platforma yüklediği veya oluşturduğu tüm veri, belge, dosya ve kayıtlar.</span>
            <span class="def-term">KVKK</span>
            <span class="def-desc">6698 sayılı Kişisel Verilerin Korunması Kanunu.</span>
        </div>

        <!-- BÖLÜM 2 -->
        <h2 id="s2">2. Hizmetin Kapsamı</h2>
        <p>PUANTOR; personel yönetimi, puantaj (çalışma saati) takibi, maaş bordrosu hesaplama, proje ve görev yönetimi, avans talepleri, raporlama, cari hesap takibi ve benzeri insan kaynakları ile işletme yönetimi işlevlerini bulut tabanlı SaaS modeli ile sunar.</p>
        <p>Sunulan başlıca modüller şunlardır (paket kapsamına göre değişebilir):</p>
        <ul>
            <li><strong>Puantaj Takibi:</strong> Çalışan devam/devamsızlık ve çalışma saati kayıtları.</li>
            <li><strong>Bordro ve Maaş Hesaplama:</strong> Maaş çizelgesi, maaş bordrosu, banka listesi ve kesinti raporları.</li>
            <li><strong>Personel Yönetimi:</strong> Personel bilgi kartları, özlük dosyaları ve izin/avans takibi.</li>
            <li><strong>Proje ve Görev Takibi:</strong> Proje oluşturma, görev atama, ilerleme izleme.</li>
            <li><strong>Avans Talepleri:</strong> Çalışan avans talep ve onay akışı.</li>
            <li><strong>Raporlar:</strong> Detaylı puantaj, maaş ve proje raporları (Excel/PDF çıktısı dahil).</li>
            <li><strong>Çoklu Firma Desteği:</strong> Abonelik paketine bağlı olarak birden fazla firma yönetimi.</li>
            <li><strong>Çoklu Kullanıcı ve Rol Yönetimi:</strong> Alt kullanıcı ekleyerek yetki bazlı erişim kontrolü.</li>
        </ul>
        <p>PUANTOR, sunduğu hizmetlerin kapsamını önceden bildirmek kaydıyla genişletme, daraltma veya değiştirme hakkını saklı tutar.</p>

        <!-- BÖLÜM 3 -->
        <h2 id="s3">3. Üyelik ve Hesap Yönetimi</h2>

        <h3>3.1 Kayıt Koşulları</h3>
        <ul>
            <li>Üye olabilmek için 18 (on sekiz) yaşını doldurmuş olmak gerekmektedir.</li>
            <li>Tüzel kişiler adına kayıt yapan kişinin, şirketi temsil ve ilzama yetkili olması zorunludur.</li>
            <li>Kayıt formunda istenen tüm bilgiler doğru, güncel ve eksiksiz doldurulmalıdır. Yanıltıcı bilgi verilmesi hesabın askıya alınmasına veya kapatılmasına neden olabilir.</li>
            <li>Her e-posta adresi ile yalnızca bir Ana Hesap açılabilir.</li>
        </ul>

        <h3>3.2 Hesap Güvenliği</h3>
        <ul>
            <li>Kullanıcı adı ve şifre tamamen Kullanıcı'nın sorumluluğundadır; başkalarıyla paylaşılamaz.</li>
            <li>Yetkisiz erişim ya da şüpheli bir durumun fark edilmesi halinde Kullanıcı derhal <a href="mailto:info@puantor.com.tr">info@puantor.com.tr</a> adresine bildirmekle yükümlüdür.</li>
            <li>Hesap üzerinden gerçekleştirilen tüm işlemlerden Kullanıcı sorumludur.</li>
        </ul>

        <h3>3.3 Deneme Süresi</h3>
        <div class="info-box">
            <p>Sisteme ilk kez kaydolan Kullanıcılar, kayıt tarihinden itibaren <strong>15 (on beş) takvim günü</strong> süresince ücretsiz deneme hakkından yararlanır. Deneme süresinin sona ermesinden önce ücretli bir abonelik planı seçilmezse hesap erişimi askıya alınır; veriler silinmez ve Kullanıcı sonradan abonelik satın alarak erişimini yeniden aktif edebilir.</p>
        </div>

        <!-- BÖLÜM 4 -->
        <h2 id="s4">4. Abonelik Paketleri ve Ödeme Koşulları</h2>

        <h3>4.1 Paketler</h3>
        <p>PUANTOR, farklı firma ve kullanıcı limitlerine sahip çeşitli abonelik paketleri sunar. Aktif paketler ve güncel fiyatlar www.puantor.com.tr adresinde ilan edilir. Paketler; süre (gün), firma hakkı ve alt kullanıcı hakkı bakımından birbirinden ayrılır.</p>

        <h3>4.2 Abonelik ve Yenileme</h3>
        <ul>
            <li>Abonelikler, seçilen paket süresince geçerlidir; süre dolduğunda otomatik olarak yenilenmez.</li>
            <li>Aboneliğini sürdürmek isteyen Kullanıcı, yeni bir abonelik satın almalıdır.</li>
            <li>Abonelik süresi dolduğunda platforma erişim kısıtlanır; veriler belirtilen saklama politikası kapsamında korunmaya devam eder.</li>
        </ul>

        <h3>4.3 Fiyatlandırma ve Fatura</h3>
        <ul>
            <li>Tüm fiyatlar Türk Lirası (TL) cinsinden olup KDV dahil/hariç olduğu ayrıca belirtilir.</li>
            <li>PUANTOR, abonelik ücretlerini önceden platforma bildirimde bulunarak değiştirme hakkını saklı tutar. Fiyat değişiklikleri mevcut abonelik süresi tamamlanmadan geçerli olmaz.</li>
            <li>Kullanıcıya e-posta ile fatura/dekont iletilir.</li>
        </ul>

        <h3>4.4 İptal ve İade Politikası</h3>
        <ul>
            <li>Kullanıcı aboneliğini istediği zaman iptal edebilir; iptal işlemi mevcut abonelik döneminin sonunda geçerli olur ve kalan süre için ücret iadesi yapılmaz.</li>
            <li>Teknik aksaklık veya hizmet kesintisinden kaynaklanan durumlarda PUANTOR'un takdir yetkisi dahilinde telafi veya uzatma uygulanabilir.</li>
            <li>Tüketici mevzuatından doğan yasal iade hakları saklıdır.</li>
        </ul>

        <!-- BÖLÜM 5 -->
        <h2 id="s5">5. Kullanıcı Yükümlülükleri</h2>

        <h3>5.1 Yasal Uyumluluk</h3>
        <ul>
            <li>Kullanıcı, platformu yürürlükteki Türk hukuku başta olmak üzere tüm ilgili mevzuata uygun biçimde kullanmakla yükümlüdür.</li>
            <li>Çalışanlara ait kişisel verilerin (TC kimlik no, maaş bilgisi, biyometrik veri vb.) işlenmesi, depolanması ve raporlanmasında KVKK başta olmak üzere ilgili mevzuata uyulması münhasıran Kullanıcı'nın sorumluluğundadır.</li>
            <li>İşçi ücretleri, fazla mesai ve bordro hesaplamalarına ilişkin Türk İş Kanunu ve ilgili yönetmeliklere uyum Kullanıcı'ya aittir; PUANTOR bu konuda danışmanlık hizmeti vermez.</li>
        </ul>

        <h3>5.2 Yasaklı Kullanım</h3>
        <p>Kullanıcı aşağıdaki eylemleri gerçekleştirmemeyi kabul ve taahhüt eder:</p>
        <ul>
            <li>Platforma yetkisiz erişim sağlamak, güvenlik açıklarını istismar etmek veya başkasının hesabına izinsiz girmek.</li>
            <li>Kötü amaçlı yazılım, virüs veya zararlı kod yüklemek ya da iletmek.</li>
            <li>Sistemi aşırı yükleyecek otomatik istek, bot veya tarama araçları kullanmak.</li>
            <li>Başkalarına ait kişisel verileri izinsiz toplamak veya işlemek.</li>
            <li>Platformu yasadışı, ahlaka aykırı veya üçüncü kişilerin haklarına zarar verebilecek amaçlarla kullanmak.</li>
            <li>Platform yazılımını tersine mühendislik yöntemiyle çözmeye çalışmak, kopyalamak veya türev ürün oluşturmak.</li>
            <li>Hesabı üçüncü kişilere devretmek veya kiralamak.</li>
        </ul>

        <h3>5.3 İçerik Sorumluluğu</h3>
        <p>Kullanıcının platforma yüklediği tüm veriler, belgeler ve bilgilerin doğruluğu, yasallığı ve uygunluğundan yalnızca Kullanıcı sorumludur. PUANTOR, kullanıcı tarafından yüklenen içeriklerin doğruluğunu kontrol etmez.</p>

        <!-- BÖLÜM 6 -->
        <h2 id="s6">6. Hizmet Sağlayıcının Hak ve Yükümlülükleri</h2>

        <h3>6.1 Hizmet Kalitesi</h3>
        <ul>
            <li>PUANTOR, platformun kesintisiz ve hatasız çalışması için makul teknik önlemleri almayı taahhüt eder.</li>
            <li>Planlı bakım çalışmaları için Kullanıcılar önceden bilgilendirilir; acil durumlarda bu bildirim sonradan da yapılabilir.</li>
            <li>PUANTOR, hizmet kalitesini artırmak amacıyla platformu önceden bildirmeksizin güncelleyebilir.</li>
        </ul>

        <h3>6.2 Veri Yedekleme</h3>
        <ul>
            <li>PUANTOR, kullanıcı verilerini düzenli aralıklarla yedekler. Ancak yedekleme işlemi ek bir güvence niteliği taşımaz; Kullanıcı kritik verilerinin yedeğini bağımsız olarak almalıdır.</li>
        </ul>

        <h3>6.3 Destek</h3>
        <ul>
            <li>Teknik destek, platform içindeki destek modülü veya <a href="mailto:info@puantor.com.tr">info@puantor.com.tr</a> adresi aracılığıyla sunulur.</li>
            <li>Destek talepleri iş günleri içinde 2 (iki) iş günü içinde yanıtlanmaya çalışılır.</li>
        </ul>

        <h3>6.4 Hesap Askıya Alma</h3>
        <p>PUANTOR, aşağıdaki durumlarda Kullanıcı hesabını önceden bildirimde bulunmaksızın askıya alma veya kapatma hakkını saklı tutar:</p>
        <ul>
            <li>Bu Sözleşme hükümlerinin ihlal edilmesi.</li>
            <li>Ödeme yükümlülüklerinin yerine getirilmemesi.</li>
            <li>Yasadışı faaliyet şüphesi veya üçüncü kişilere zarar verme riski.</li>
            <li>Yetkili makamların talebi.</li>
        </ul>

        <!-- BÖLÜM 7 -->
        <h2 id="s7">7. Fikri Mülkiyet Hakları</h2>
        <ul>
            <li>Platform yazılımı, arayüz tasarımı, logolar, markalar ve tüm yazılım bileşenleri PUANTOR'a aittir ve Türk ve uluslararası fikri mülkiyet mevzuatı kapsamında korunmaktadır.</li>
            <li>Kullanıcıya, yalnızca bu Sözleşme kapsamında ve abonelik süresiyle sınırlı olmak üzere platformu kullanmaya yönelik münhasır olmayan, devredilemez ve alt lisans verilemez bir kullanım hakkı tanınır.</li>
            <li>Kullanıcı, platforma yüklediği verilerin sahibi olmaya devam eder. PUANTOR, bu verileri yalnızca hizmetin sunulması amacıyla kullanır.</li>
            <li>PUANTOR'un yazılı izni olmaksızın platform içeriklerinin kopyalanması, çoğaltılması, dağıtılması veya ticari amaçla kullanılması yasaktır.</li>
        </ul>

        <!-- BÖLÜM 8 -->
        <h2 id="s8">8. Veri İşleme Kuralları</h2>
        <p>Kullanıcı, platforma yüklediği çalışan verilerini işlemeden önce ilgili çalışanlardan KVKK kapsamında gerekli aydınlatma ve rıza işlemlerini tamamlamakla yükümlüdür. PUANTOR bu süreçte veri işleyen ("processor") konumundadır; veri sorumlusu ("controller") Kullanıcı'dır.</p>
        <p>PUANTOR'un kendi kullanıcı verilerini nasıl işlediğine ilişkin ayrıntılar için bu belgenin <a href="#s13">13. bölümüne</a> (KVKK Aydınlatma Metni) bakınız.</p>

        <!-- BÖLÜM 9 -->
        <h2 id="s9">9. Sınırlı Garanti ve Sorumluluk</h2>

        <h3>9.1 Garanti Sınırlaması</h3>
        <p>PUANTOR platformu "olduğu gibi" sunar. Hizmetin kesintisiz, hatasız veya tamamen güvenli çalışacağına dair açık veya zımni bir garanti verilmemektedir.</p>

        <h3>9.2 Sorumluluk Sınırı</h3>
        <ul>
            <li>PUANTOR; Kullanıcı'nın bordrolarını, SGK bildirimlerini veya diğer yasal yükümlülüklerini doğru hesaplayıp hesaplamamasından sorumlu değildir. Platform araç niteliğinde olup nihai doğrulama sorumluluğu Kullanıcı'ya aittir.</li>
            <li>PUANTOR'un herhangi bir tazminat yükümlülüğü, her halükarda son 12 aylık abonelik bedelini aşamaz.</li>
            <li>Dolaylı, özel veya cezai zararlardan PUANTOR sorumlu tutulamaz.</li>
            <li>Mücbir sebep (doğal afet, siber saldırı, altyapı kesintisi, yasal düzenleme vb.) durumlarında PUANTOR'un yükümlülüğü askıya alınır.</li>
        </ul>

        <!-- BÖLÜM 10 -->
        <h2 id="s10">10. Sözleşmenin Askıya Alınması ve Feshi</h2>

        <h3>10.1 Kullanıcı Tarafından Fesih</h3>
        <p>Kullanıcı, hesabını istediği zaman platform ayarları üzerinden ya da <a href="mailto:info@puantor.com.tr">info@puantor.com.tr</a> adresine e-posta göndererek kapatabilir. Aktif abonelik süresi sona erene kadar erişim devam eder; erken kapanma halinde kalan süre iade edilmez.</p>

        <h3>10.2 PUANTOR Tarafından Fesih</h3>
        <p>PUANTOR; sözleşme ihlali, ödeme yapılmaması veya yasal gereklilik halinde hesabı kapatabilir. Sözleşme ihlaline bağlı fesihlerde Kullanıcı'ya önceden bildirim yapılmaya çalışılır; acil durumlarda bildirim sonradan yapılabilir.</p>

        <h3>10.3 Fesih Sonrası Veri</h3>
        <p>Hesap kapatılmasının ardından Kullanıcı verileri <strong>30 (otuz) takvim günü</strong> içinde sistemden kalıcı olarak silinir. Kullanıcı bu süre içinde veri ihracı talebinde bulunabilir. Yasal saklama yükümlülükleri kapsamındaki veriler ilgili mevzuat süresince saklanır.</p>

        <!-- BÖLÜM 11 -->
        <h2 id="s11">11. Değişiklikler</h2>
        <p>PUANTOR, bu Sözleşme'yi, gizlilik politikasını veya fiyatlandırmayı önceden platforma bildirimde bulunarak ya da kayıtlı e-posta adresine bildirim göndererek değiştirebilir. Değişikliklerin yürürlüğe girdiği tarihten sonra platformu kullanmaya devam etmek, güncellenmiş şartların kabul edildiği anlamına gelir. Değişiklikleri kabul etmeyen Kullanıcı hesabını kapatabilir.</p>

        <!-- BÖLÜM 12 -->
        <h2 id="s12">12. Uygulanacak Hukuk ve Uyuşmazlık Çözümü</h2>
        <p>Bu Sözleşme, Türkiye Cumhuriyeti hukukuna tabidir. Sözleşmeden doğan uyuşmazlıklarda öncelikle taraflar arasında müzakere yoluyla çözüm aranır. Müzakerede uzlaşı sağlanamaması halinde İstanbul (Çağlayan) Mahkemeleri ve İcra Daireleri yetkilidir.</p>

        <!-- BÖLÜM 13 -->
        <h2 id="s13">13. KVKK Aydınlatma Metni</h2>
        <p style="font-weight:600; color:#0054a6;">6698 Sayılı Kişisel Verilerin Korunması Kanunu Kapsamında Kişisel Verilerin İşlenmesine İlişkin Aydınlatma ve Rıza Metni</p>

        <h3>13.1 Veri Sorumlusu</h3>
        <p>PUANTOR, 6698 sayılı KVKK uyarınca veri sorumlusu sıfatıyla hareket etmektedir. İletişim: <a href="mailto:info@puantor.com.tr">info@puantor.com.tr</a> | www.puantor.com.tr</p>

        <h3>13.2 İşlenen Kişisel Veriler ve Amaçları</h3>
        <table style="width:100%;border-collapse:collapse;font-size:.88rem;margin:10px 0;">
            <thead style="background:#f0f5ff;">
                <tr>
                    <th style="text-align:left;padding:8px 12px;border:1px solid #d0dff5;">Veri Kategorisi</th>
                    <th style="text-align:left;padding:8px 12px;border:1px solid #d0dff5;">İşleme Amacı</th>
                    <th style="text-align:left;padding:8px 12px;border:1px solid #d0dff5;">Hukuki Dayanak</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Ad, soyad, e-posta, telefon</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Üye kaydı oluşturma, kimlik doğrulama ve iletişim</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Sözleşmenin ifası (md. 5/2-c)</td>
                </tr>
                <tr style="background:#fafcff;">
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Firma adı ve ticari bilgiler</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Hesap yönetimi ve faturalama</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Sözleşmenin ifası (md. 5/2-c)</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Ödeme ve fatura bilgileri</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Abonelik takibi ve yasal yükümlülüklerin yerine getirilmesi</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Yasal yükümlülük (md. 5/2-ç)</td>
                </tr>
                <tr style="background:#fafcff;">
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">IP adresi, giriş logları</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Güvenlik, hata ayıklama ve yetkisiz erişim tespiti</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Meşru menfaat (md. 5/2-f)</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Kullanım verileri (modül tercihleri, işlem logları)</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Hizmet kalitesinin artırılması ve destek</td>
                    <td style="padding:8px 12px;border:1px solid #e5eef8;">Meşru menfaat (md. 5/2-f)</td>
                </tr>
            </tbody>
        </table>

        <h3>13.3 Verilerin Aktarılması</h3>
        <p>Kişisel verileriniz; yasal zorunluluk dışında üçüncü kişilerle paylaşılmaz. Ancak PUANTOR'un hizmet altyapısını sağlayan bulut/sunucu sağlayıcıları, ödeme altyapısı ve e-posta servisleri gibi iş ortaklarıyla, yalnızca hizmetin sunulması için gerekli ölçüde ve KVKK'nın 8. ve 9. maddeleri çerçevesinde paylaşılabilir.</p>

        <h3>13.4 Saklama Süresi</h3>
        <p>Kişisel veriler, üyelik süresi boyunca ve üyelik sona erdikten sonra yasal saklama yükümlülükleri kapsamında (ticaret ve vergi mevzuatı için 10 yıl, iş mevzuatı kapsamındaki veriler için ilgili mevzuatta öngörülen süre) saklanır; bu süreler dolduktan sonra imha edilir.</p>

        <h3>13.5 Veri Güvenliği</h3>
        <p>PUANTOR; verilerinizi korumak için endüstri standardı teknik ve idari güvenlik önlemleri uygular. Bu önlemler arasında SSL/TLS şifrelemesi, erişim kontrolleri, güvenlik duvarı ve düzenli güvenlik denetimleri yer almaktadır.</p>

        <h3>13.6 KVKK'dan Doğan Haklarınız</h3>
        <p>KVKK'nın 11. maddesi uyarınca aşağıdaki haklara sahipsiniz:</p>
        <ul>
            <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme,</li>
            <li>İşlenen verileriniz hakkında bilgi talep etme,</li>
            <li>İşlenme amacını ve amaca uygun kullanılıp kullanılmadığını öğrenme,</li>
            <li>Yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri öğrenme,</li>
            <li>Eksik veya yanlış işlenmiş verilerin düzeltilmesini isteme,</li>
            <li>KVKK'nın 7. maddesi kapsamında silinmesini veya yok edilmesini isteme,</li>
            <li>Düzeltme/silme işlemlerinin aktarılan üçüncü kişilere bildirilmesini talep etme,</li>
            <li>İşlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme,</li>
            <li>Kanuna aykırı işleme nedeniyle zarara uğramanız hâlinde tazminat talep etme.</li>
        </ul>

        <div class="info-box">
            <p>Taleplerinizi <a href="mailto:info@puantor.com.tr"><strong>info@puantor.com.tr</strong></a> adresine e-posta göndererek iletebilirsiniz. PUANTOR, başvurunuzu en geç <strong>30 (otuz) gün</strong> içinde ücretsiz olarak sonuçlandırır. Talebinizin reddedilmesi, yetersiz yanıt verilmesi veya süresinde yanıt alınamaması durumunda Kişisel Verileri Koruma Kurulu'na şikâyette bulunabilirsiniz.</p>
        </div>

        <div class="footer-note">
            <p>Bu sözleşme <strong>www.puantor.com.tr</strong> için hazırlanmıştır. &copy; <?php echo date('Y'); ?> Puantor. Tüm hakları saklıdır.</p>
            <p style="margin-top:6px;">İletişim: <a href="mailto:info@puantor.com.tr">info@puantor.com.tr</a></p>
        </div>

    </div>
</div>
</body>
</html>
