# Proje Kuralları

- PDO kullanılacak.
- Tüm veritabanı işlemleri Model katmanından geçecek.
- Veritabanı değişiklikleri her zaman ayrı SQL scripti olarak verilecek.
- Kod yazmadan önce mevcut dosya yapısı analiz edilecek.
- Yeni özellik eklenirken mevcut sistemi bozacak değişiklik yapılmayacak.
- Her hata merkezi sistem loguna yazılacak; yakalanan exception'lar sessizce yutulmayacak ve `system_log_exception()` ile kaydedilecek.
- Yeni bağımsız PHP giriş noktaları `App/bootstrap.php` dosyasını yükleyecek. Ayrıntılı kural `docs/error-logging.md` içindedir.
- Personel, Maaş, İcra, SGK, Bordro modülleri ayrı tutulacak.
- Yetki kontrolü olmayan işlem yapılmayacak.
- Test için yazılan geçici dosyalar iş bitince silinecek.
- Sonuç açıklamaları Türkçe yazılacak.
- Kod içine yorum satırı eklenmeyecek; gerekmedikçe docstring yazılmayacak.
- Model içinden başka Model'e direkt `require_once` ile erişilmeyecek; bağımlılıklar constructor injection ile verilecek (DIP).
- Model, kendi sorumluluğu dışındaki işleri (bildirim, log gibi) doğrudan yapmamalı; bunlar servis katmanından çağrılacak (SRP).
