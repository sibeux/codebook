-- Run this once against the Codebook database after taking a backup.
-- Confirm the selected database before continuing:
SELECT DATABASE();

-- Stop if any destination table already exists. Do not run this script twice.
RENAME TABLE
    `password_reset_tokens` TO `codebook_password_reset_tokens`,
    `sessions` TO `codebook_sessions`,
    `cache` TO `codebook_cache`,
    `cache_locks` TO `codebook_cache_locks`,
    `jobs` TO `codebook_jobs`,
    `job_batches` TO `codebook_job_batches`,
    `failed_jobs` TO `codebook_failed_jobs`,
    `migrations` TO `codebook_migrations`,
    `folders` TO `codebook_folders`,
    `notes` TO `codebook_notes`,
    `note_messages` TO `codebook_note_messages`,
    `todo_projects` TO `codebook_todo_projects`,
    `todos` TO `codebook_todos`,
    `todo_items` TO `codebook_todo_items`,
    `todo_lists` TO `codebook_todo_lists`,
    `todo_workspaces` TO `codebook_todo_workspaces`;

-- Verify the renamed tables:
SHOW TABLES LIKE 'codebook\\_%';
