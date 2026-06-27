-- Hakediş yılı takvim yılı (örn. 2026) olarak girilen manuel kayıtların hakediş tarihlerini düzeltir
UPDATE izin_hakedis h
JOIN persons p ON p.id = h.personel_id
SET h.hakedis_tarihi = CONCAT(h.yil, '-', DATE_FORMAT(STR_TO_DATE(p.job_start_date, '%d.%m.%Y'), '%m-%d'))
WHERE h.yil > 1000;
