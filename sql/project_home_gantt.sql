ALTER TABLE `projects`
  ADD COLUMN `is_home_gantt` tinyint(1) NOT NULL DEFAULT 0 AFTER `notes`;
