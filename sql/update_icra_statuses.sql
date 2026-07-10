-- İcra Dosyaları Durum Alanı Güncellemesi
-- Eski durumları yeni tanımlarla eşleştiriyoruz

UPDATE `person_icra_files` SET `durum` = 'Kesilen' WHERE `durum` = 'Aktif';
UPDATE `person_icra_files` SET `durum` = 'Kesinti Bitti' WHERE `durum` = 'Kapatıldı' OR `durum` = 'Ödendi';
