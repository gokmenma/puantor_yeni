ALTER TABLE `puantaj`
    ADD INDEX `idx_puantaj_person_gun_project` (`person`, `gun`, `project_id`),
    ADD INDEX `idx_puantaj_project_gun_type` (`project_id`, `gun`, `puantaj_id`);

ALTER TABLE `settings`
    ADD INDEX `idx_settings_firm_name` (`firm_id`, `set_name`);

ALTER TABLE `job_groups`
    ADD INDEX `idx_job_groups_firm` (`firm_id`);

ALTER TABLE `national_holidays`
    ADD INDEX `idx_national_holidays_active_date` (`is_active`, `holiday_date`);
