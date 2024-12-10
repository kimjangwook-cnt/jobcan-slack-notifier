<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
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

            $filename = 'backup-' . date('Ymd') . '.sql';
            $backupFilePath = storage_path('app/backups/' . $filename);

            if (file_exists($backupFilePath)) {
                $result = Storage::disk('s3')->put('backups/' . $filename, file_get_contents($backupFilePath));

                if ($result) {
                    unlink($backupFilePath);
                }
            }

            $this->info('The backup has been proceed successfully.');
        } catch (ProcessFailedException $exception) {
            $this->error($exception->getMessage());
            $this->error('The backup process has been failed.');
        }
    }
}
