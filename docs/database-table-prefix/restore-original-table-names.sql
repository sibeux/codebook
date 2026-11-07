-- Emergency rollback if the production application still expects unprefixed tables.
-- Select the Codebook database and confirm it with SELECT DATABASE() before running.
-- Run only if the corresponding codebook_* tables exist and original names do not.
RENAME TABLE
    `codebook_users` TO `users`,
    `codebook_password_reset_tokens` TO `password_reset_tokens`,
    `codebook_sessions` TO `sessions`,
    `codebook_cache` TO `cache`,
    `codebook_cache_locks` TO `cache_locks`,
    `codebook_jobs` TO `jobs`,
    `codebook_job_batches` TO `job_batches`,
    `codebook_failed_jobs` TO `failed_jobs`,
    `codebook_migrations` TO `migrations`,
    `codebook_folders` TO `folders`,
    `codebook_notes` TO `notes`,
    `codebook_note_messages` TO `note_messages`,
    `codebook_todo_projects` TO `todo_projects`,
    `codebook_todos` TO `todos`,
    `codebook_todo_items` TO `todo_items`,
    `codebook_todo_lists` TO `todo_lists`,
    `codebook_todo_workspaces` TO `todo_workspaces`;
