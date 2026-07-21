-- "Hakedişler" (menu id 131, izin/hakedis) icin ayri bir auths kaydi yoktu, bu yuzden
-- is_authorize=1 yapilamiyordu (yetkisi olmayan hicbir sey hep gorunur/hep gizli kalirdi).
-- Bu script: (1) Yillik Izin (auth id 131) altina yeni bir "Hakedişler" yetkisi ekler,
-- (2) mevcut tum rollere bu yetkiyi ekler ki kimse aniden erisim kaybetmesin
--     (sayfa su ana kadar zaten herkese acikti), (3) menu satirini is_authorize=1 yapar.

INSERT INTO auths (title, auth_name, description, parent_id, is_active, superadmin)
VALUES ('Hakedişler', 'izin_hakedis', 'Personel yıllık izin hakediş listesini görüntüleme yetkisi.', 131, 1, 0);

SET @yeni_auth_id = LAST_INSERT_ID();

UPDATE role_auths
SET auth_ids = CONCAT(auth_ids, ',', @yeni_auth_id)
WHERE FIND_IN_SET(@yeni_auth_id, auth_ids) = 0;

UPDATE menu SET is_authorize = 1 WHERE id = 131;
