<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class SetupMinioStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minio:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup MinIO storage bucket and directories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up MinIO storage...');

        try {
            $disk = Storage::disk('minio');
            $bucketName = config('filesystems.disks.minio.bucket');

            // Create S3 client to check/create bucket
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => config('filesystems.disks.minio.region'),
                'endpoint' => config('filesystems.disks.minio.endpoint'),
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => config('filesystems.disks.minio.key'),
                    'secret' => config('filesystems.disks.minio.secret'),
                ],
            ]);

            // Check if bucket exists
            if (!$s3Client->doesBucketExist($bucketName)) {
                $this->info("Creating bucket: {$bucketName}");
                $s3Client->createBucket(['Bucket' => $bucketName]);
                $this->info("Bucket {$bucketName} created successfully!");
            } else {
                $this->info("Bucket {$bucketName} already exists.");
            }

            // Create necessary directories
            $directories = [
                'travel-expenses',
                'attachments/travel_expenses',
                'attachments/maintenance_records',
                'attachments/vehicles',
                'attachments/operators',
            ];

            foreach ($directories as $directory) {
                if (!$disk->exists($directory)) {
                    $disk->makeDirectory($directory);
                    $this->info("Created directory: {$directory}");
                } else {
                    $this->info("Directory already exists: {$directory}");
                }
            }

            $this->info('MinIO storage setup completed successfully!');
            $this->info("MinIO Console: http://localhost:8900");
            $this->info("Access Key: " . config('filesystems.disks.minio.key'));
            $this->info("Secret Key: " . config('filesystems.disks.minio.secret'));

        } catch (AwsException $e) {
            $this->error('AWS Error: ' . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}