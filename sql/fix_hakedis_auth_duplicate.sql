-- add_hakedis_auth_and_backfill.sql ile "Hakedişler" icin yeni bir auths kaydi (id=144,
-- izin_hakedis) eklenmisti, ancak ayni basliga (Hakedişler) sahip, hicbir role/pakete
-- baglanmamis eski bir kayit zaten vardi (id=133, izin_hakedisler). getAuthIdByTitle()
-- basliga gore tek satir dondurdugu icin daima eski kaydi (133) buluyor, bu yuzden
-- inc/menu.php ve checkAuthorize('izin_hakedis') hicbir zaman eslesmiyordu.
--
-- Bu script: dogru/orijinal kaydi (133) tum rollere ve halihazirda 144 iceren paketlere
-- ekler, ardindan mukerrer kaydi (144) role_auths, abonelik_paketleri ve auths tablosundan temizler.

UPDATE role_auths
SET auth_ids = CONCAT(auth_ids, ',133')
WHERE FIND_IN_SET('133', auth_ids) = 0;

UPDATE abonelik_paketleri
SET modul_auth_ids = CONCAT(modul_auth_ids, ',133')
WHERE FIND_IN_SET('144', modul_auth_ids) > 0
  AND FIND_IN_SET('133', modul_auth_ids) = 0;

UPDATE role_auths
SET auth_ids = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', auth_ids, ','), ',144,', ','))
WHERE FIND_IN_SET('144', auth_ids) > 0;

UPDATE abonelik_paketleri
SET modul_auth_ids = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', modul_auth_ids, ','), ',144,', ','))
WHERE FIND_IN_SET('144', modul_auth_ids) > 0;

DELETE FROM auths WHERE id = 144;
