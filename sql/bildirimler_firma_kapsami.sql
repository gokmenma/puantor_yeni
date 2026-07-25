-- Bildirim gönderim geçmişinde personel ve sistem kullanıcısı hedeflerini ayırır.
ALTER TABLE `gonderilen_bildirimler`
    ADD COLUMN `hedef_turu` VARCHAR(20) NOT NULL DEFAULT 'personel' AFTER `gonderen_id`,
    ADD COLUMN `kullanici_ids` TEXT DEFAULT NULL AFTER `personel_ids`,
    ADD KEY `idx_firma_hedef_turu` (`firma_id`, `hedef_turu`);

-- Bildirimler sayfası firma kullanıcıları tarafından da kullanılabilir.
UPDATE `menu`
SET `is_authorize` = 0
WHERE `page_link` = 'bildirimler/push';
