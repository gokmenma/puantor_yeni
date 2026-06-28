-- Duyurular icerik alanini LONGTEXT olarak guncelleme
-- Bu sayede duyuru icerigine buyuk boyutlu resimler/base64 verileri eklenebilir.
ALTER TABLE `duyurular` MODIFY COLUMN `icerik` LONGTEXT NOT NULL;
