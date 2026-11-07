<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('app:prefix-codebook-tables {--force : Run without an interactive confirmation.}')]
#[Description('Rename existing Codebook tables with the configured database prefix.')]
class PrefixCodebookTables extends Command
{
    private const TABLES = [
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations',
        'folders',
        'notes',
        'note_messages',
        'todo_projects',
        'todos',
        'todo_items',
        'todo_lists',
        'todo_workspaces',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connectionName = config('database.default');
        $prefix = (string) config("database.connections.{$connectionName}.prefix", 'codebook_');
        $prefix = $prefix !== '' ? $prefix : 'codebook_';

        if (! $this->option('force') && ! $this->confirm("Rename existing Codebook tables using prefix [{$prefix}]?")) {
            return self::SUCCESS;
        }

        Artisan::call('down');
        Artisan::call('config:clear');

        $connection = DB::connection($connectionName);
        $connection->setTablePrefix('');
        $renamedTables = [];

        try {
            foreach (self::TABLES as $table) {
                $prefixedTable = $prefix.$table;
                $hasTable = Schema::connection($connectionName)->hasTable($table);
                $hasPrefixedTable = Schema::connection($connectionName)->hasTable($prefixedTable);

                if ($hasTable && $hasPrefixedTable) {
                    $this->error("Both [{$table}] and [{$prefixedTable}] exist; resolve this collision before retrying.");

                    return self::FAILURE;
                }

                if ($hasTable) {
                    Schema::connection($connectionName)->rename($table, $prefixedTable);
                    $renamedTables[] = $prefixedTable;
                }
            }
        } finally {
            $connection->setTablePrefix($prefix);
        }

        config(["database.connections.{$connectionName}.prefix" => $prefix]);

        if ($renamedTables !== []) {
            $this->info('Renamed: '.implode(', ', $renamedTables));
        }

        $migrationExitCode = Artisan::call('migrate', ['--force' => true]);
        $this->output->write(Artisan::output());

        if ($migrationExitCode !== self::SUCCESS) {
            $this->error('Migration failed. The application remains in maintenance mode.');

            return self::FAILURE;
        }

        $configCacheExitCode = Artisan::call('config:cache');
        $this->output->write(Artisan::output());

        if ($configCacheExitCode !== self::SUCCESS) {
            $this->error('Config caching failed. The application remains in maintenance mode.');

            return self::FAILURE;
        }

        Artisan::call('up');
        $this->info('Table prefix migration complete. The application is online.');

        return self::SUCCESS;
    }
}
