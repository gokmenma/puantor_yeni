ALTER TABLE puantajturu ADD COLUMN is_deductable TINYINT(1) NOT NULL DEFAULT 0;
UPDATE puantajturu SET is_deductable = 1 WHERE PuantajKod IN ('DVZ', 'Uİ');
