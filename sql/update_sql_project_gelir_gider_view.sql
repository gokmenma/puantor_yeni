-- sql_project_gelir_gider view guncellemesi
-- case_id alani eklendi ve kasa hareketleri turu otomatik eslestirildi.

CREATE OR REPLACE VIEW `sql_project_gelir_gider` AS 
SELECT 
    `ct`.`id` AS `id`,
    `ct`.`date` AS `tarih`,
    '' AS `ay`,
    '' AS `yil`,
    '' AS `kategori`,
    CASE 
        WHEN `ct`.`sub_type` > 0 THEN `ct`.`sub_type` 
        WHEN `ct`.`type_id` = 1 THEN 5
        WHEN `ct`.`type_id` = 2 THEN 11
        ELSE `ct`.`type_id` 
    END AS `turu`,
    `ct`.`amount` AS `tutar`,
    `ct`.`case_id` AS `case_id`,
    `ct`.`project_id` AS `project_id`,
    `ct`.`description` AS `aciklama`,
    `ct`.`created_at` AS `created_at`,
    'case_transaction' AS `tablename` 
FROM `case_transactions` `ct` 
UNION ALL 
SELECT 
    `pg`.`id` AS `id`,
    `pg`.`tarih` AS `tarih`,
    `pg`.`ay` AS `ay`,
    `pg`.`yil` AS `yil`,
    `pg`.`kategori` AS `kategori`,
    `pg`.`turu` AS `turu`,
    `pg`.`tutar` AS `tutar`,
    `pg`.`case_id` AS `case_id`,
    `pg`.`project_id` AS `project_id`,
    `pg`.`aciklama` AS `aciklama`,
    `pg`.`created_at` AS `created_at`,
    'project_gelir_gider' AS `tablename` 
FROM `project_gelir_gider` `pg` 
UNION ALL 
SELECT 
    '' AS `id`,
    '' AS `gun`,
    '' AS `ay`,
    '' AS `yil`,
    '' AS `kategori`,
    14 AS `turu`,
    SUM(`pt`.`tutar`) AS `sum(pt.tutar)`,
    0 AS `case_id`,
    `pt`.`project_id` AS `project_id`,
    'Proje Toplam Çalışma' AS `description`,
    '' AS `created_at`,
    'puantaj' AS `tablename` 
FROM (`puantaj` `pt` LEFT JOIN `persons` `p` ON(`p`.`id` = `pt`.`person`)) 
GROUP BY `pt`.`project_id`;
