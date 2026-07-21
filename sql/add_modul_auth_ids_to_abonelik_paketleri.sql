ALTER TABLE abonelik_paketleri
  ADD COLUMN modul_auth_ids TEXT NULL DEFAULT NULL
  COMMENT 'Pakete dahil ust seviye modul (auths.parent_id=0) id listesi, CSV. NULL/bos = kisitlama yok, tum moduller.'
  AFTER ozellikler;
