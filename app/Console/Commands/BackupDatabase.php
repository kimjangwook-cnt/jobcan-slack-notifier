<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Backup the database';

    protected $process;

    public function __construct()
    {
        parent::__construct();
        // Remove backups older than 7 days
        $backupPath = storage_path('app/backups');
        $files = glob($backupPath . '/backup-*.sql');
        $now = time();
        foreach ($files as $file) {
            if ($now - filemtime($file) >= 7 * 24 * 60 * 60) {
                unlink($file);
            }
        }

        $filename = 'backup-' . date('Ymd') . '.sql';
        $backupFilePath = storage_path('app/backups/' . $filename);

        $command = sprintf(
            'mysqldump -u%s -p%s %s > %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $backupFilePath
        );

        $this->process = Process::fromShellCommandline($command, null, null, null, 3600);
    }

    public function handle()
    {
        try {
            $this->process->mustRun();

            $this->info('The backup has been proceed successfully.');
        } catch (ProcessFailedException $exception) {
            $this->error($exception->getMessage());
            $this->error('The backup process has been failed.');
        }
    }
}
