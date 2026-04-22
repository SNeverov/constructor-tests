ALTER TABLE `tests`
  ADD COLUMN `status` ENUM('draft', 'published') NOT NULL DEFAULT 'published' AFTER `access_level`,
  ADD COLUMN `published_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN `last_saved_at` TIMESTAMP NULL DEFAULT NULL AFTER `published_at`;

UPDATE `tests`
SET
  `status` = 'published',
  `published_at` = COALESCE(`published_at`, `created_at`, CURRENT_TIMESTAMP),
  `last_saved_at` = COALESCE(`last_saved_at`, `updated_at`, `created_at`, CURRENT_TIMESTAMP)
WHERE `status` IS NULL OR `status` <> 'draft';

CREATE INDEX `idx_tests_status_deleted_created` ON `tests` (`status`, `deleted_at`, `created_at`);
CREATE INDEX `idx_tests_user_status_deleted_saved` ON `tests` (`user_id`, `status`, `deleted_at`, `last_saved_at`);
