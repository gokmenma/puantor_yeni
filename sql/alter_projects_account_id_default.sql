-- Projeler tablosundaki account_id ve type kolonlarının varsayılan değerlerinin ayarlanması
ALTER TABLE `projects` 
  MODIFY COLUMN `account_id` int(11) NOT NULL DEFAULT 0,
  MODIFY COLUMN `type` int(2) NOT NULL DEFAULT 1;
