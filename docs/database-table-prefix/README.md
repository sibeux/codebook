# Codebook Database Table Prefix

Codebook uses the `codebook_` prefix for tables it owns. Laravel adds this prefix automatically when the application refers to a logical table name. The `users` table is owned by Cybeat SSO, so it remains unprefixed and Codebook reads it through a separate connection without a table prefix.

## Rename Existing Tables

Before changing a production database, take a database backup. The rename preserves the existing table data, but the backup provides a recovery point if the database has an unexpected naming conflict or the migration is interrupted.

Run the following command from the Codebook project directory:

```bash
php artisan app:prefix-codebook-tables --force
```

If the local machine cannot connect to the production MySQL server, run the SQL script in `rename-codebook-tables.sql` from the database hosting panel instead. Select the Codebook database before running it.

The command performs these steps:

1. **Enables maintenance mode.** This prevents web requests from using tables while their names are changing.
2. **Clears the cached configuration.** This lets the command use the current table-prefix configuration.
3. **Renames Codebook's existing tables.** Each known unprefixed table is renamed with the configured prefix. Existing prefixed tables are skipped, so the command can be retried after a partial rename. If both the old and prefixed names exist for a table, the command stops to avoid overwriting either table.
4. **Keeps the migration history.** The `migrations` table is renamed too, so Laravel retains its record of applied migrations and does not replay them against populated tables.
5. **Runs pending Laravel migrations.** This creates or updates tables using the configured prefix.
6. **Caches the configuration and brings the application online.** On success, maintenance mode is disabled automatically.

The command currently handles these tables:

```text
password_reset_tokens, sessions, cache, cache_locks,
jobs, job_batches, failed_jobs, migrations,
folders, notes, note_messages, todo_projects, todos, todo_items,
todo_lists, todo_workspaces
```

## Expected Result

The command reports the renamed table names, then Laravel's migration result. A successful run ends with:

```text
Table prefix migration complete. The application is online.
```

If a rename, migration, or config-cache step fails, the command leaves the application in maintenance mode. Read the error before retrying. Resolve any name collision first; do not manually drop either table. The rename command is retryable after the conflict or interruption is resolved.

## New Installations

The database connection configuration defaults to `codebook_`. New installations do not need the rename command; run the normal migration command:

```bash
php artisan migrate --force
```

To override the prefix for an environment, set `DB_TABLE_PREFIX` in that environment. Keep the same prefix for every deployment that connects to that database.

Do not rename or create `users` as a Codebook table. It is shared with Cybeat SSO and must stay named `users`.
