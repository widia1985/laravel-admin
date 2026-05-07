<?php

namespace Encore\Admin\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Build a URL for admin-uploaded files for use in <img src> / JSON previews.
 *
 * S3/MinIO private buckets: plain Storage::url() is not anonymously readable — use presigned URLs.
 */
class AdminUploadStorageUrl
{
    /**
     * @param  string|null  $path  Relative path on the disk (e.g. images/foo.jpg)
     * @param  string|null  $diskName  Disk name from config/filesystems.php; defaults to admin.upload.disk
     */
    public static function url(?string $path, ?string $diskName = null): string
    {
        $path = is_string($path) ? trim(str_replace('\\', '/', $path)) : '';
        if ($path === '') {
            return '';
        }

        $diskName = $diskName ?? (string) config('admin.upload.disk');
        if ($diskName === '') {
            return '';
        }

        $driver = config("filesystems.disks.{$diskName}.driver");

        $factoryDisk = (string) config('factoryadmin.upload.disk', '');
        $useTemporary = $diskName === $factoryDisk
            ? (bool) config('factoryadmin.upload.s3_use_temporary_urls', true)
            : (bool) config('admin.upload.s3_use_temporary_urls', true);
        $previewMinutes = $diskName === $factoryDisk
            ? (int) config('factoryadmin.upload.s3_preview_expires_minutes', 720)
            : (int) config('admin.upload.s3_preview_expires_minutes', 720);

        if ($driver === 's3' && $useTemporary) {
            try {
                $disk = Storage::disk($diskName);
                $minutes = $previewMinutes;
                if ($minutes < 5) {
                    $minutes = 5;
                }

                return $disk->temporaryUrl($path, now()->addMinutes($minutes));
            } catch (\Throwable $e) {
                // Fall back to public URL (e.g. misconfigured IAM but object is public)
            }
        }

        try {
            return Storage::disk($diskName)->url($path);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
