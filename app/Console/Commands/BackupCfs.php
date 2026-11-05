<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class BackupCfs extends Command
{
    protected $signature = 'cfs:backup {--source : Include application source and uploaded logos} {--label=manual}';
    protected $description = 'Create a private database backup and optional source snapshot';

    public function handle()
    {
        $directory = storage_path('app/backups/' . now()->format('Ymd-His') . '-' . Str::slug(substr($this->option('label'), 0, 40)) . '-' . Str::random(6));
        File::makeDirectory($directory, 0700, true);
        $manifest = ['complete' => false, 'created_at' => now()->toIso8601String(), 'driver' => DB::connection()->getDriverName()];
        File::put($directory . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        try {
            if ($manifest['driver'] === 'mysql') {
                $config = DB::connection()->getConfig();
                $binary = dirname(PHP_BINARY, 2) . '/mysql/bin/mysqldump.exe';
                $dump = $directory . '/database.sql';
                $process = new Process([
                    is_file($binary) ? $binary : 'mysqldump', '--single-transaction', '--skip-lock-tables',
                    '--hex-blob', '--no-tablespaces', '--default-character-set=utf8mb4',
                    '--host=' . $config['host'], '--port=' . ($config['port'] ?? 3306),
                    '--user=' . $config['username'], '--result-file=' . $dump, '--databases', $config['database'],
                ], base_path(), ['MYSQL_PWD' => (string) $config['password']]);
                $process->setTimeout(180);
                $process->mustRun();
            } elseif ($manifest['driver'] === 'sqlite') {
                $dump = $directory . '/database.sqlite';
                DB::statement('VACUUM INTO ?', [$dump]);
            } else {
                throw new \RuntimeException('This database driver is not supported by the backup command.');
            }
            if (! is_file($dump) || filesize($dump) === 0) throw new \RuntimeException('The database backup is empty.');
            $manifest['database_sha256'] = hash_file('sha256', $dump);
            if ($this->option('source')) {
                foreach (['app', 'resources', 'routes', 'database', 'config', 'tests', 'public/css', 'public/js', 'public/icons', 'public/uploads'] as $folder) {
                    if (File::isDirectory(base_path($folder)) && ! File::copyDirectory(base_path($folder), $directory . '/source/' . $folder)) throw new \RuntimeException('Source backup could not be copied.');
                }
                foreach (['.env', '.env.example', 'composer.json', 'composer.lock', 'README.md', 'artisan', 'server.php'] as $file) {
                    if (File::isFile(base_path($file)) && ! File::copy(base_path($file), $directory . '/source/' . $file)) throw new \RuntimeException('Source file could not be copied.');
                }
                $manifest['source'] = true;
            }
            $manifest['complete'] = true;
            File::put($directory . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $this->info('Backup verified: ' . $directory);
            return 0;
        } catch (Throwable $error) {
            report($error);
            $this->error('Backup incomplete. No existing data was changed. Check the private backup manifest and application log.');
            return 1;
        }
    }
}
