ALTER TABLE abonelik_paketleri
  ADD COLUMN kullaniciya_goster_mi TINYINT(1) NOT NULL DEFAULT 1
  COMMENT 'Paket, musterinin kendi satin alma ekraninda listelensin mi? 1=goster, 0=sadece yonetici atayabilir.'
  AFTER aktif_mi;
