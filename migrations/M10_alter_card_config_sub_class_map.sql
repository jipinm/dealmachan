-- Migration M10: card_config_sub_class_map — add gender_filter and profession_ids columns
-- Required by Tasks: C3, C4
-- NOTE: gender_filter is only meaningful when sub_class_id points to the Gender sub-classification.
--       profession_ids stores a comma-separated list of profession IDs (e.g. "1,3,7") or NULL for all.

ALTER TABLE `card_config_sub_class_map`
  ADD COLUMN `gender_filter` enum('male','female','both') DEFAULT NULL
    COMMENT 'Used when sub_class_id is the Gender sub-classification: male, female, or both'
    AFTER `sub_class_id`,
  ADD COLUMN `profession_ids` text DEFAULT NULL
    COMMENT 'Comma-separated profession IDs when sub_class_id is Profession; NULL = all professions';
