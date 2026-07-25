-- Duyuru ve bildirim kayıtlarını birbirinden ayırır.
ALTER TABLE `duyurular`
    ADD COLUMN `kaynak_turu` ENUM('duyuru','sistem','kullanici')
        NOT NULL DEFAULT 'duyuru' AFTER `icerik`,
    ADD KEY `idx_kaynak_turu` (`kaynak_turu`);

-- Daha önce duyuru tablosuna yazılmış otomatik izin bildirimlerini sınıflandır.
UPDATE `duyurular`
SET `kaynak_turu` = 'sistem'
WHERE `baslik` IN (
    'Yeni İzin Talebi',
    'İzin Talebiniz Onaylandı',
    'İzin Talebiniz Kısmi Onaylandı',
    'İzin Talebiniz Reddedildi',
    'Yeni Avans Talebi'
);
