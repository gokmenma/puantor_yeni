---
trigger: always_on
---

# Puantor UI/UX Tasarım Sistemi & Standartları (Design System)

Tüm sayfa geliştirmelerinde Tabler ERP UI standardı ve tasarım sürekliliği kesin olarak uygulanacaktır. Yeni liste/yönetim sayfalarında ve mevcut sayfalar yenilenirken aşağıdaki düzen referans alınacaktır. Personel listesi (`pages/persons/list.php`) bu standardın çalışan örneğidir.

---

### 1. Sayfa Zemini, Başlık ve Hızlı Aksiyon Alanı
- **Global İçerik Zemini:** Topbarın altındaki tüm `.page-wrapper` alanları merkezi tema üzerinden açık gri-mavi `#eef3f8` olmalıdır; sayfaya özel `:has(#pageId)` zemin kuralları yazılmamalıdır. Koyu temada ortak zemin `#0f172a` olmalıdır. Kartlar beyaz zeminde, ince çerçeveli ve hafif gölgeli görünmelidir.
- **Global Kart Yuvarlaklığı:** Sistemdeki bütün `.card` bileşenleri tema radius seçeneğinden bağımsız olarak 12px köşe yarıçapı kullanmalıdır. Sayfalarda farklı kart radius değerleri tanımlanmamalıdır; merkezi `premium-theme.css` kuralı kullanılmalıdır.
- Her liste/yönetim sayfasının en üstünde `.page-header` alanı yer almalıdır:
- **Sol Taraf:**
  - Sayfa modül ikonu (44x44px yumuşak zeminli avatar kutusu: `avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm`).
  - Sayfa Başlığı: `h2.page-title.fw-bold` (1.25rem / 20px, harf aralığı sıkı).
  - Alt Açıklama: `.text-secondary.small` (Modülün amacını özetleyen gri kısa metin).
- **Sağ Taraf (Aksiyon Butonları Grubu):**
  1. **Sütunlar:** Birincil aksiyonun solunda, yalnızca `ti ti-layout-columns` ikonu bulunan 32x32px outline buton. İkon yaklaşık 18px olmalıdır. Metin yazılmamalı; `title` ve `aria-label` eklenmelidir.
  2. **Birincil Aksiyon:** `[+ Yeni ... Ekle/Oluştur]` (`btn btn-dark`), 32px yükseklikte olmalıdır.
  3. **İşlemler:** Birincil aksiyonun hemen sağında 32px yükseklikte outline dropdown olmalıdır.
- Dashboard ve Yenile gibi genel butonlar liste sayfası başlığında yer almamalıdır.
- Excel/PDF dışa aktarma, içe aktarma ve toplu işlemler ayrı üst butonlar yerine **İşlemler** dropdown'unda toplanmalıdır.

---

### 2. Dörtlü KPI / İstatistik Özet Kartları
Başlık ile ana tablo arasında sayfa verilerini özetleyen 4'lü kart ızgarası (`row row-cards g-3 mb-3`):
- **Kart Yapısı (`col-sm-6 col-xl-3`):**
  - **Üst Satır:** Sol tarafta küçük büyük-harf etiket (`font-size: 11px; font-weight: 600; text-muted; text-transform: uppercase; letter-spacing: 0.5px;`) + Sağ üstte mikro renkli ikon kutusu (`avatar avatar-sm rounded-2 bg-*-lt text-*`, 32x32px).
  - **Ana Rakam:** Dengeli, kalın sayısal/parasal metrik (`font-size: 1.35rem - 1.45rem; font-weight: 700; color: #1e293b; line-height: 1.25; letter-spacing: -0.3px;`). Çok büyük (1.85rem+) fontlar kullanılmamalı, metrik kart sınırlarına ferahça sığmalıdır.
  - **Kart Altı (Footer):** İnce üst çizgi (`border-top: 1px solid #f1f5f9; padding-top: 6px; margin-top: 6px;`), solda alt bilgi/kırılım detayı (`font-size: 11.5px; text-muted`), sağda durum rozeti/filtre hapı (`badge bg-*-lt`, `font-size: 10px; font-weight: 600; padding: 3px 8px;`).
- **Kart Görünümü:** Beyaz zemin (`#ffffff`), `1px solid #dbe3ec` çerçeve, 12px köşe yarıçapı ve `0 2px 8px rgba(15, 23, 42, .06)` hafif gölge kullanılmalıdır.
- **Durum Filtreleri:** Tümü/Aktif/Pasif gibi durum filtreleri tablo kartının başlığında ayrı bir buton grubu oluşturmamalı; ilgili özet kartlarının altına yerleştirilmelidir.
- **Standart Renk Dağılımı:**
  1. Kart: Toplam / Genel Metrik (Gri/Nötr - `bg-secondary-lt`)
  2. Kart: Aktif / Devam Eden / Süreçte (Sarı/Turuncu - `bg-warning-lt`)
  3. Kart: Tamamlanan / Başarı / Kapanan (Yeşil/Zümrüt - `bg-success-lt`)
  4. Kart: Dönemsel / Yeni / Bu Ay (Mavi/Camgöbeği - `bg-info-lt`)
- **Daraltma Davranışı:** Arama kutusunun sağında 32x32px yukarı/aşağı ok butonu bulunmalıdır. Bu buton özet kartlarını `max-height`, `opacity` ve `transform` geçişleriyle açıp kapatmalıdır.
- **Kalıcı Tercih:** Kartların açık/kapalı durumu sayfaya özel bir `localStorage` anahtarında tutulmalıdır. Değer, içerik çizilmeden önce senkron olarak okunup kök elemana sınıf eklenmelidir; sayfa yüklenirken kartların kısa süre görünüp kaybolmasına izin verilmemelidir.
- Kartlar kapalıyken sayfa başlığı ile ana tablo kartı arasında yaklaşık 12px boşluk korunmalıdır.

---

### 3. Ana Tablo Kartı ve DataTable Standartları
- **Kart Başlığı (`.card-header`):**
  - Sol: Liste ikonu (`ti ti-list` mikro kutu) + Başlık (Örn: "Personel Listesi") + `+` hızlı ekle butonu + Alt açıklama.
  - Sağ: 32px yüksekliğinde hızlı arama kutusu (`#*-fast-search`, `ti ti-search` ikonu) + özet kartlarını açıp kapatan 32x32px ok butonu.
  - Arama metni varken alanın sağında temizleme (`ti ti-x`) butonu görünmeli; temizlendiğinde DataTable genel araması da sıfırlanmalıdır.
  - Card header altında ayırıcı border kullanılmamalıdır.
- **Tablo Görünümü & Sütun Filtreleme:**
  - Ana kart: `border: 1px solid #dbe3ec`, 12px köşe yarıçapı ve `0 4px 14px rgba(15, 23, 42, .07)` hafif gölge kullanmalıdır.
  - Tablo, kartın sağ ve sol kenarına yapışmamalıdır. Tablo kapsayıcısına her iki yanda 8px görünür boşluk verilmeli (`width: calc(100% - 16px); margin: 0 8px 8px`) ve bu boşluk satır içi `margin: 0 !important` ile ezilmemelidir.
  - Tablo ızgarasının kendi dış kenarlığı `1px solid #dbe3ec`, köşeleri yaklaşık 8px olmalıdır. `border-collapse: separate` ve `border-spacing: 0` kullanılmalıdır.
  - Son veri satırının altında siyah/koyu fazladan çizgi bulunmamalıdır. Son satır hücreleri, `tbody`, DataTable layout hücresi ve gölgeleri gerektiğinde açıkça sıfırlanmalıdır; yalnızca açık renkli tablo dış çerçevesi görünmelidir.
  - **Merkezi Sütun Filtreleme (Popover Filter):** Hantal sabit input satırları yerine Aydınoğulları standardı olan başlık içi huni ikonu (`.dt-col-filter-btn`) kullanılacaktır. Tıklandığında açılan Popover ile şart ("İçerir", "Eşittir", "İle Başlar", "Boş Olanlar" vb.) seçilerek filtreleme yapılır. Filtre aktif olduğunda huni ikonu mavi soft arka planla vurgulanır (`active text-primary bg-primary-lt`). Tüm DataTables için `window.initDataTableColumnFilters` / `createDataTable` otomatik çalışacaktır.
  - **Aktif Filtre Barı:** En az bir sütun filtresi uygulandığında kart başlığı ile tablo arasında otomatik görünmelidir. Bar, tablodaki sağ-sol boşlukla hizalı (`width: calc(100% - 16px); margin: 0 8px 8px`), açık gri zeminli, `1px solid #dbe3ec` çerçeveli ve 8px yuvarlatılmış olmalıdır. Solda “Aktif filtreler:” metni ve kaldırılabilir filtre chip'leri, sağda kırmızı `Filtreleri Temizle` aksiyonu bulunmalıdır. Filtre kalmadığında bar tamamen gizlenmelidir.
  - **Kompakt Satırlar:** Gövde hücrelerinde yaklaşık `6px 10px` padding ve 13px yazı kullanılmalıdır. Gereksiz yüksek satırlar oluşturulmamalıdır.
  - **Seçim Kutuları:** Başlıktaki “tümünü seç” kutusu ve satır seçim kutuları aynı ölçüde (yaklaşık 18x18px), aynı köşe yarıçapında ve dikey olarak ortalanmış olmalıdır.
  - **Durum Rozetleri (Badges):** Mutlaka pastel dolgulu yuvarlak hap rozetler kullanılmalıdır (`badge bg-success-lt`, `badge bg-danger-lt`, `badge bg-warning-lt`, `badge bg-blue-lt`, `badge bg-purple-lt`).
  - **Para / Bakiye Alanları:** `Helper::formattedMoney()` ile kalın (`fw-semibold`) fontla yazılmalı, bakiye durumuna göre `Helper::balanceColor()` uygulanmalıdır.
  - **İşlem Sütunu:** Sağda `Düzenle` (kalem), `Sil` (kırmızı çöp kutusu) ve varsa `Diğer` (üç nokta `ti ti-dots-vertical` dropdown) butonları hizalı yer almalıdır. Bu butonlar kompakt, yaklaşık 28x28px; ikonlar yaklaşık 13px olmalıdır.

### 4. Yerleşim Kontrol Listesi
Bir liste sayfası tamamlanmadan önce aşağıdakiler doğrulanmalıdır:
- Açık gri-mavi içerik zemini ile beyaz kartlar birbirinden ayırt ediliyor mu?
- Özet ve tablo kartlarında ince çerçeve, hafif gölge ve tutarlı köşe yarıçapı var mı?
- Sütunlar ikonu üstte ve birincil aksiyonun solunda mı; İşlemler dropdown'u birincil aksiyonun sağında mı?
- Excel/PDF ve toplu işlemler İşlemler menüsünde mi?
- Arama alanı ve yanındaki butonlar aynı yükseklikte mi?
- Tablo karttan sağda ve solda eşit 8px içeride mi ve kendi açık renkli dış çerçevesine sahip mi?
- Satırlar, seçim kutuları ve işlem butonları kompakt ve birbirleriyle tutarlı mı?
- Özet kartlarının daraltma tercihi yenileme sonrasında korunuyor mu ve ilk boyamada sıçrama/parlama oluyor mu?
- Masaüstü, dar ekran ve koyu tema görünümleri kontrol edildi mi?
- Sayfaya özel stiller bir sayfa kök ID/class'ı altında scope edildi mi? `.card`, `.card-body`, `.table-responsive` gibi genel seçicilere sayfa içinden `border: none` veya `box-shadow: none` verilmemelidir; bu kurallar özet ve tablo kartlarının görünümünü bozar.
- Tekrarlanan ölçü ve renkler mümkün olduğunda ortak yardımcı sınıflara taşınmalı; satır içi `!important` kuralları yalnızca zorunlu olduğunda kullanılmalıdır.

---

### 5. Form & Fonksiyonel Standartlar
- **Select Elemanları:** Daima `select2` olacak.
- **Tarih Seçiciler:** Daima `flatpickr` olacak.
- **Bildirimler/Uyarılar:** Native `alert()` asla kullanılmayacak; daima `sweetalert2` kullanılacak.
- **DataTable Entegrasyonu:** `window.createDataTable` veya standart ServerSide AJAX mimarisi kullanılacak; `datatable-column-filter.js` otomatik devrede olacak.
- **Yetki & Menü Güvenliği:** Yeni bir menü eklendiğinde `auth` tablosuna ve `menu` tablosuna eklenecek, yetkisiz sayfa erişimi olmayacak.

---

### 6. Modal Tasarım & Form Standartları (Modal Standards)
Tüm ekleme, düzenleme ve detay modallarında Tabler ERP tasarım dili ve ferah sekmeli yapı uygulanacaktır. Proje modalı (`pages/projects/modals/project-modal.php`) bu standardın referans örneğidir.

- **Modal Kapsayıcısı & Ölçüler:**
  - Kapsamlı (5'ten fazla girdi içeren) form modalları daima `modal-lg` (veya çok geniş tablolarda `modal-xl`) ve `modal-dialog-centered` olmalıdır.
  - Kart köşe yarıçapı 14px (`border-radius: 14px; overflow: hidden;`), çerçeve kaldırılmış ve derin gölgeli (`shadow-lg border-0`) olmalıdır.
  - Form alanlarını alt alta tek bir dar sütunda yığarak dikey kaydırma çubuğu (scroll) oluşturan hantal düzenler **kullanılmayacaktır**.
- **Sekmeli (Tabs) Düzen (`nav nav-tabs nav-tabs-alt`):**
  - Çok alanlı formlar mantıksal olarak sekmelere bölünmelidir (Örn: *Genel & Finansal*, *Konum & İletişim*, *Notlar & Ayarlar*).
  - Sekme çubuğu modal başlığının hemen altında `bg-light-subtle border-bottom` şeridinde yer almalı; ikon ve metin içermelidir (`ti ti-*`).
  - Modal body alanı ferah (`p-4 bg-white`, `min-height: 380px`), form alanları dengeli 2 sütunlu (`col-md-6`) veya tam satır (`col-12`) gridde dizilmelidir.
- **Modal Başlığı (Header):**
  - Sol Taraf: 42x42px yumuşak zeminli modül ikonu (`avatar avatar-md rounded-3 bg-primary-lt text-primary`).
  - Başlık: `h4.modal-title.fw-bold.text-dark` (yaklaşık 1.15rem / 18px, sıkı harf aralığı).
  - Alt Açıklama: `.text-secondary.small` (Modaldaki formun amacını özetleyen gri mikro metin).
  - Sağ Taraf: Standart `btn-close` kapatma butonu. Header altında ince çizgi (`border-bottom`) yer almalıdır.
- **Form Girdileri & Kontroller:**
  - Zorunlu alanlar `.form-label.required` (kırmızı yıldız `*`) ile gösterilmelidir.
  - Metin, e-posta, telefon, tarih, para ve IBAN alanlarında Tabler `input-icon` ve gri `input-icon-addon` ikonu kullanılmalıdır.
  - Durum ve tür seçimlerinde hantal dropdown yerine modern `form-selectgroup` radio butonları tercih edilmelidir.
  - Açık/kapalı ayarlar için `card border bg-light-subtle` içinde switch ve açıklayıcı alt metin yer almalıdır.
- **Modal Alt Bilgisi (Footer):**
  - `modal-footer py-2.5 px-4 bg-light-subtle border-top` düzeninde olmalıdır.
  - Sol Taraf: `btn btn-link link-secondary px-2 text-decoration-none` ("Vazgeç" / "İptal").
  - Sağ Taraf: `btn btn-primary px-4 shadow-sm fw-semibold` (İçinde `ti ti-device-floppy` ikonu bulunan kaydet butonu).
- **JS ve Bileşen Entegrasyonu (Select2 & Flatpickr):**
  - Modal her açıldığında (`#addNew...` veya `.update-...`) form temizlenmeli ve ilk sekmeye odaklanılmalıdır (`Tab.show()`).
  - Select2 alanları modal içerisinde `dropdownParent: $('#modalId')` ile açılmalı, z-index veya arama kutusu odaklanma sorunları önlenmelidir.
  - Tarih seçiciler `shown.bs.modal` eventinde flatpickr ile (`dateFormat: 'd.m.Y', locale: 'tr'`) initialize edilmelidir.
  - Form gönderimi AJAX ile yönetilmeli; kullanıcıya native `alert()` yerine daima `sweetalert2` ile sonuç bildirilmelidir.

---

### 7. Sağ Tık Bağlam Menüsü Standartları (Context Menu Standards)
Tüm tablo ve liste yönetim sayfalarında fare ile satır üzerine sağ tıklandığında modern ve hızlı aksiyon sağlayan özel sağ tık bağlam menüsü (`.custom-context-menu`) yer alacaktır.

- **Zorunlu Entegrasyon:** Tablodaki her veri satırında (`#tableId tbody tr`) `contextmenu` olayı yakalanmalı, varsayılan tarayıcı menüsü engellenmeli (`e.preventDefault()`) ve özel menü açılmalıdır.
- **Aktif Satır Vurgusu:** Menü açıkken ilgili satıra `.context-menu-active` sınıfı eklenmeli; menü kapandığında kaldırılmalıdır.
- **Menü Yapısı:**
  - **Başlık (`.cm-header`):** İlgili kaydın adı (Örn: Abone adı, personel adı, proje adı) ve mikro ikon (`ti ti-*`).
  - **Birincil Aksiyonlar:** Detay/Görüntüle, Düzenle/Güncelle (`<a href="#" class="route-link" data-page="...">` veya modal tetikleyicisi).
  - **Hızlı / Modüler İşlemler:** İlgili kayda ait alt modül veya hareket sayfalarına doğrudan geçiş linkleri.
  - **Ayırıcı Çizgi:** Gruplar arasında `.cm-divider` kullanılmalıdır.
  - **Tehlikeli Aksiyonlar (`.cm-danger`):** Kırmızı renkle vurgulanmış silme/iptal aksiyonu (`ti ti-trash`).
- **Ekran Sınır Taşma Kontrolü:** Menü konumu tıklandığı koordinata göre hesaplanmalı; ekranın sağına veya altına taştığında otomatik olarak görünür alanın içine çekilmelidir (`windowWidth`, `windowHeight` kontrolü).
- **Otomatik Gizlenme:** Menü dışına tıklandığında, bir menü elemanı tıklandığında, sayfa kaydırıldığında (`scroll`), pencere yeniden boyutlandırıldığında (`resize`) veya odak kaybedildiğinde (`blur`) menü otomatik olarak gizlenmelidir (`$('#customContextMenu').hide()`).

---

### Test ve Geliştirme Bilgileri
- Web adresi: http://puantor.site
- Kullanıcı adı: admin
- Email: admin@admin.com
- Şifre: 245963

