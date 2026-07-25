-- activity_logs tablosuna platform (Mobil / Masaüstü) sütunu ekleme scripti

SET @dbname = DATABASE();
SET @tablename = "activity_logs";
SET @columnname = "platform";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE activity_logs ADD COLUMN platform VARCHAR(50) DEFAULT 'Masaüstü'"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
