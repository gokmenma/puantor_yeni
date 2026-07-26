# Merkezi hata loglama kuralı

Uygulama başlatılırken `App/bootstrap.php` yüklenmelidir. Bu dosya PHP warning, notice, yakalanmamış exception ve fatal error kayıtlarını merkezi olarak `storage/logs/system-errors-YYYY-MM-DD.log` dosyasına JSON Lines biçiminde yazar.

Yeni bir giriş noktası oluşturulursa dosyanın ilk satırlarında aşağıdaki çağrı bulunmalıdır:

```php
require_once dirname(__DIR__) . '/App/bootstrap.php';
```

Dizin seviyesine göre yol düzeltilmelidir. `App/Helper/session_security.php`, `Database/db.php` veya `configs/require.php` kullanan giriş noktalarında bootstrap zaten otomatik yüklenir.

Yakalanan her hata sessizce yutulmamalı, merkezi kayda gönderilmelidir:

```php
try {
    $service->run();
} catch (Throwable $exception) {
    system_log_exception($exception, ['operation' => 'service_run']);
    return ['status' => 'error'];
}
```

Exception yeniden fırlatılacaksa ayrıca log yazılmamalıdır; merkezi exception handler kaydı oluşturur. Böylece aynı hata iki kez kaydedilmez.

İş kuralı hataları için:

```php
system_log_error('Ödeme sağlayıcısı işlemi reddetti.', [
    'operation' => 'payment_create',
    'order_id' => $orderId,
]);
```

Parola, token, cookie, authorization başlığı, CSRF ve API anahtarı context içine eklenmemelidir. Logger bu anahtarları ayrıca maskeler. Kullanıcıya exception detayı veya stack trace gösterilmez; inceleme superadmin `admin-home` ekranından yapılır.

Canlı ortamda log klasörü web kökünün dışında tutulmak istenirse `.env` içine `SYSTEM_LOG_PATH=/guvenli/yazilabilir/log/dizini` tanımlanmalıdır. Tanımlanmazsa `storage/logs` kullanılır; klasör web erişimine `.htaccess` ile kapalıdır ve web sunucusu kullanıcısının bu klasöre yazma yetkisi olmalıdır.
