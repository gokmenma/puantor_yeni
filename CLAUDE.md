# Proje Kuralları

- PDO kullanılacak.
- Tüm veritabanı işlemleri Model katmanından geçecek.
- Veritabanı değişiklikleri her zaman ayrı SQL scripti olarak verilecek.
- Kod yazmadan önce mevcut dosya yapısı analiz edilecek.
- Yeni özellik eklenirken mevcut sistemi bozacak değişiklik yapılmayacak.
- Her işlem için log kaydı oluşturulacak.
- Personel, Maaş, İcra, SGK, Bordro modülleri ayrı tutulacak.
- Yetki kontrolü olmayan işlem yapılmayacak.
- Test için yazılan geçici dosyalar iş bitince silinecek.
- Sonuç açıklamaları Türkçe yazılacak.
- Kod içine yorum satırı eklenmeyecek; gerekmedikçe docstring yazılmayacak.
