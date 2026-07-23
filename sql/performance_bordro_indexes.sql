ALTER TABLE `maas_gelir_kesinti`
    ADD INDEX `idx_mgk_person_period_category` (`person_id`, `yil`, `ay`, `kategori`);

ALTER TABLE `case_transactions`
    ADD INDEX `idx_case_transactions_person_date_type` (`person_id`, `date`, `users_type_id`);

ALTER TABLE `defines`
    ADD INDEX `idx_defines_type_id` (`type_id`, `id`);
