-- Eski izin talebi akışının her yönetici için açtığı aynı bildirimleri
-- tek kayıtta birleştirir ve yöneticileri bu kaydın hedefleri olarak korur.
CREATE TEMPORARY TABLE `tmp_tekrar_sistem_bildirimleri` AS
SELECT
    MIN(`id`) AS `kalacak_id`,
    `baslik`,
    `icerik`,
    `hedef_firma_id`,
    `created_at`
FROM `duyurular`
WHERE `kaynak_turu` = 'sistem'
  AND `baslik` = 'Yeni İzin Talebi'
  AND `hedef_tip` = 'bazi_kullanicilar'
GROUP BY `baslik`, `icerik`, `hedef_firma_id`, `created_at`
HAVING COUNT(*) > 1;

INSERT INTO `duyuru_hedefler` (`duyuru_id`, `hedef_tip`, `hedef_id`)
SELECT DISTINCT
    tekrar.`kalacak_id`,
    'kullanici',
    duyuru.`olusturan_id`
FROM `tmp_tekrar_sistem_bildirimleri` tekrar
JOIN `duyurular` duyuru
  ON duyuru.`baslik` = tekrar.`baslik`
 AND duyuru.`icerik` = tekrar.`icerik`
 AND duyuru.`hedef_firma_id` = tekrar.`hedef_firma_id`
 AND duyuru.`created_at` = tekrar.`created_at`
WHERE duyuru.`olusturan_id` > 0
  AND NOT EXISTS (
      SELECT 1
      FROM `duyuru_hedefler` hedef
      WHERE hedef.`duyuru_id` = tekrar.`kalacak_id`
        AND hedef.`hedef_tip` = 'kullanici'
        AND hedef.`hedef_id` = duyuru.`olusturan_id`
  );

DELETE duyuru
FROM `duyurular` duyuru
JOIN `tmp_tekrar_sistem_bildirimleri` tekrar
  ON duyuru.`baslik` = tekrar.`baslik`
 AND duyuru.`icerik` = tekrar.`icerik`
 AND duyuru.`hedef_firma_id` = tekrar.`hedef_firma_id`
 AND duyuru.`created_at` = tekrar.`created_at`
WHERE duyuru.`id` <> tekrar.`kalacak_id`;

DROP TEMPORARY TABLE `tmp_tekrar_sistem_bildirimleri`;
