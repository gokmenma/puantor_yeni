-- Program ayarlarına "yıllık izinden düşmeyecek günler" ayarı için varsayılan değerleri ekler
-- Varsayılan olarak cumartesi ve pazar günlerinin düşmemesi (eski davranış) ayarlanır
INSERT INTO `settings` (firm_id, user_id, set_name, set_value)
SELECT id, 0, 'yillik_izin_dusmeyecek_gunler', '6,7'
FROM `companies`
WHERE id NOT IN (
    SELECT DISTINCT firm_id FROM `settings` WHERE set_name = 'yillik_izin_dusmeyecek_gunler'
);
