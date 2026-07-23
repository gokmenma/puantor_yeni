ALTER TABLE `persons`
    ADD INDEX `idx_persons_firm_deleted` (`firm_id`, `deleted_at`);

ALTER TABLE `person_daily_wages`
    ADD INDEX `idx_person_daily_wages_person` (`person_id`);

ALTER TABLE `puantaj`
    ADD INDEX `idx_puantaj_person` (`person`);

ALTER TABLE `maas_gelir_kesinti`
    ADD INDEX `idx_maas_gelir_kesinti_person` (`person_id`);

ALTER TABLE `case_transactions`
    ADD INDEX `idx_case_transactions_person` (`person_id`);

ALTER TABLE `projects`
    ADD INDEX `idx_projects_firm` (`firm_id`);

ALTER TABLE `project_person`
    ADD INDEX `idx_project_person_project_person` (`project_id`, `person_id`),
    ADD INDEX `idx_project_person_person` (`person_id`);
