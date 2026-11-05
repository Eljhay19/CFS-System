<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DeploymentPreflight extends Command
{
    protected $signature = 'cfs:deploy-check';

    protected $description = 'Check production configuration before deploying CFS';

    private $failures = 0;

    public function handle()
    {
        $this->failures = 0;
        $this->check('Production environment', app()->environment('production'), 'Set APP_ENV=production.');
        $this->check('Debug mode disabled', config('app.debug') === false, 'Set APP_DEBUG=false.');
        $this->check('Application key configured', $this->validKey(config('app.key')), 'Set APP_KEY to a strong key; preserve it when updating an existing installation.');
        $this->check('Public HTTPS URL', $this->validPublicUrl(config('app.url')), 'Set APP_URL to the public HTTPS URL customers will use.');

        $requiredExtensions = ['openssl', 'pdo', 'mbstring', 'fileinfo', 'gd'];
        foreach ($requiredExtensions as $extension) {
            $this->check('PHP extension: ' . $extension, extension_loaded($extension), 'Enable the ' . $extension . ' PHP extension.');
        }

        $this->check('Runtime directories writable', is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), 'Grant the PHP user write access to storage/ and bootstrap/cache/.');
        $uploadDirectory = public_path('uploads/logos');
        $uploadParent = dirname($uploadDirectory);
        $this->check('Business logo uploads writable', is_dir($uploadDirectory) ? is_writable($uploadDirectory) : is_writable($uploadParent), 'Create public/uploads/logos/ and grant the PHP user write access; business profile logo uploads need it.');

        try {
            DB::connection()->getPdo();
            $this->line('<info>PASS</info> Database connection');

            $requiredTables = ['users', 'businesses', 'feedback', 'questions', 'answers', 'admin_notifications', 'contact_messages', 'password_reset_codes'];
            foreach ($requiredTables as $table) {
                $this->check('Database table: ' . $table, Schema::hasTable($table), 'Run php artisan migrate --force.');
            }

            $this->check('Migrations are current', $this->migrationsAreCurrent(), 'Run php artisan migrate --force, then run this check again.');

            if (config('queue.default') === 'database') {
                $this->check('Database queue table', Schema::hasTable('jobs'), 'Run php artisan migrate --force.');
                $this->warn('A supervised queue worker must be running: php artisan queue:work --tries=3.');
            }
        } catch (Throwable $error) {
            report($error);
            $this->check('Database connection', false, 'Verify DB_CONNECTION, database credentials, network access, and that the database exists.');
        }

        $mailer = config('mail.default');
        $this->check('Email transport is deliverable', ! in_array($mailer, ['log', 'array'], true), 'Configure a real production mail transport; log and array transports do not deliver email.');
        if ($mailer === 'smtp') {
            $smtpReady = (bool) config('mail.mailers.smtp.host')
                && (bool) config('mail.mailers.smtp.port')
                && (bool) config('mail.mailers.smtp.username')
                && (bool) config('mail.mailers.smtp.password')
                && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL);
            $this->check('SMTP sender and credentials configured', $smtpReady, 'Set MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, and a valid MAIL_FROM_ADDRESS.');
        }

        $this->newLine();
        if ($this->failures > 0) {
            $this->error($this->failures . ' deployment requirement(s) need attention. No data was changed.');
            return 1;
        }

        $this->info('Production configuration checks passed.');
        $this->warn('Before launch, send a real test email and verify the queue worker, HTTPS links, uploads, and a restorable database backup on the hosting environment.');
        return 0;
    }

    private function check($label, $passed, $remedy)
    {
        if ($passed) {
            $this->line('<info>PASS</info> ' . $label);
            return;
        }

        $this->line('<error>FAIL</error> ' . $label . ' — ' . $remedy);
        $this->failures++;
    }

    private function validKey($key)
    {
        if (! is_string($key) || $key === '') {
            return false;
        }

        if (strpos($key, 'base64:') === 0) {
            $decoded = base64_decode(substr($key, 7), true);
            return is_string($decoded) && strlen($decoded) >= 32;
        }

        return strlen($key) >= 32;
    }

    private function validPublicUrl($url)
    {
        $parts = is_string($url) ? parse_url($url) : false;
        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return false;
        }

        $host = strtolower($parts['host']);
        return ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && substr($host, -6) !== '.local'
            && substr($host, -5) !== '.test'
            && substr($host, -8) !== '.example';
    }

    private function migrationsAreCurrent()
    {
        if (! Schema::hasTable('migrations')) {
            return false;
        }

        $files = app('migrator')->getMigrationFiles(database_path('migrations'));
        $ran = app('migrator')->getRepository()->getRan();
        return count(array_diff(array_keys($files), $ran)) === 0;
    }
}
