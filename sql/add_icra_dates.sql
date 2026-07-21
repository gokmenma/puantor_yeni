-- İcra dosyalarına başlama ve bitiş tarihi ekleme scripti
ALTER TABLE `person_icra_files` 
ADD COLUMN `baslama_tarihi` DATE DEFAULT NULL AFTER `toplam_borc`,
ADD COLUMN `bitis_tarihi` DATE DEFAULT NULL AFTER `baslama_tarihi`;
