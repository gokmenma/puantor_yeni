-- Fazla Mesai Oranı Varsayılan Değeri Ekleme Scripti
-- Bu script, settings tablosunda 'overtime_rate' kaydı bulunmayan firmalara varsayılan olarak 50 (%50) değerini ekler.

INSERT INTO `settings` (`firm_id`, `user_id`, `set_name`, `set_value`)
SELECT DISTINCT f.id, 0, 'overtime_rate', '50'
FROM `myfirms` f
WHERE NOT EXISTS (
    SELECT 1 FROM `settings` s WHERE s.firm_id = f.id AND s.set_name = 'overtime_rate'
);
