DELIMITER $$

-- 1. Yıllık İzin Hakedişlerini Hesaplayan Stored Procedure
DROP PROCEDURE IF EXISTS `sp_calculate_all_leave_hakedis`$$

CREATE PROCEDURE `sp_calculate_all_leave_hakedis`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE p_id INT;
    DECLARE p_firm_id INT;
    DECLARE p_job_start DATE;
    DECLARE p_birth_date DATE;
    
    -- Aktif ve silinmemiş personelleri seçen cursor
    DECLARE cur CURSOR FOR 
        SELECT id, firm_id, job_start_date, birth_date 
        FROM persons 
        WHERE deleted_at IS NULL AND job_start_date IS NOT NULL AND job_start_date != '';
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO p_id, p_firm_id, p_job_start, p_birth_date;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Her personel için eksik hakedişleri hesapla ve ekle
        BEGIN
            DECLARE completed_years INT;
            DECLARE current_yil INT DEFAULT 1;
            DECLARE hakedis_tarihi DATE;
            DECLARE age_at_hakedis INT;
            DECLARE gun_sayisi INT;
            DECLARE mevcut_count INT;

            -- İşe giriş tarihinden bugüne kadar geçen tam yıl sayısı
            SET completed_years = TIMESTAMPDIFF(YEAR, p_job_start, CURRENT_DATE());

            IF completed_years >= 1 THEN
                year_loop: LOOP
                    IF current_yil > completed_years THEN
                        LEAVE year_loop;
                    END IF;

                    -- Bu yıl için hakediş kaydı var mı kontrol et
                    SELECT COUNT(*) INTO mevcut_count 
                    FROM izin_hakedis 
                    WHERE personel_id = p_id AND yil = current_yil;

                    IF mevcut_count = 0 THEN
                        -- Hakediş tarihini hesapla
                        SET hakedis_tarihi = DATE_ADD(p_job_start, INTERVAL current_yil YEAR);

                        -- Temel gün sayısını belirle (İş Kanunu Md. 9)
                        IF current_yil <= 5 THEN
                            SET gun_sayisi = 14;
                        ELSEIF current_yil < 15 THEN
                            SET gun_sayisi = 20;
                        ELSE
                            SET gun_sayisi = 26;
                        END IF;

                        -- Yaş kuralı kontrolü (18 yaş altı veya 50 yaş üstü için en az 20 gün)
                        IF p_birth_date IS NOT NULL THEN
                            SET age_at_hakedis = TIMESTAMPDIFF(YEAR, p_birth_date, hakedis_tarihi);
                            IF age_at_hakedis <= 18 OR age_at_hakedis >= 50 THEN
                                IF gun_sayisi < 20 THEN
                                    SET gun_sayisi = 20;
                                END IF;
                            END IF;
                        END IF;

                        -- Hakedişi kaydet
                        INSERT INTO izin_hakedis (
                            firma_id, 
                            personel_id, 
                            yil, 
                            hakedis_tarihi, 
                            gun_sayisi, 
                            tip, 
                            aciklama, 
                            created_at
                        ) VALUES (
                            p_firm_id, 
                            p_id, 
                            current_yil, 
                            hakedis_tarihi, 
                            gun_sayisi, 
                            'otomatik', 
                            'Sistem tarafından otomatik hesaplandı', 
                            NOW()
                        );
                    END IF;

                    SET current_yil = current_yil + 1;
                END LOOP year_loop;
            END IF;
        END;

    END LOOP;

    CLOSE cur;
END$$

-- 2. Her gün saat 00:01'de çalışacak MySQL Event Tanımı
DROP EVENT IF EXISTS `daily_calculate_leave_hakedis`$$

CREATE EVENT `daily_calculate_leave_hakedis`
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(CURDATE(), ' 00:01:00')
DO
BEGIN
    CALL sp_calculate_all_leave_hakedis();
END$$

DELIMITER ;
