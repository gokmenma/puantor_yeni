-- pages/izin/list.php ve hakedis.php sayfalarina gercek yetki kontrolu eklenmeden once,
-- mevcut tum rollere "Yillik Izin" (auth id 131) ve "Izin Talepleri" (auth id 132)
-- yetkilerini ekler. Boylece kontrol devreye girdiginde hicbir mevcut kullanici
-- aniden erisim kaybetmez (bu tarihe kadar sayfa zaten herkese acikti).

UPDATE role_auths
SET auth_ids = CONCAT(auth_ids, ',131')
WHERE FIND_IN_SET('131', auth_ids) = 0;

UPDATE role_auths
SET auth_ids = CONCAT(auth_ids, ',132')
WHERE FIND_IN_SET('132', auth_ids) = 0;
