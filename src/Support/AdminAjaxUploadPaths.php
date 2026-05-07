<?php

namespace Encore\Admin\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Staged ajax uploads live under {image_dir}/{staging}/… until a model save moves them out.
 */
class AdminAjaxUploadPaths
{
    private const FINALIZE_RETRY_ATTEMPTS = 4;
    private const FINALIZE_RETRY_SLEEP_US = 250000;

    /**
     * Safe subdirectory under the image root: segments are [a-zA-Z0-9_-]+, no "..".
     *
     * @return string|null normalized e.g. tmp/returns, or '' for empty input, or null if invalid
     */
    public static function sanitizeRelativeSubdir(?string $subdir): ?string
    {
        if ($subdir === null || $subdir === '') {
            return '';
        }
        $subdir = str_replace('\\', '/', trim($subdir));
        $subdir = trim($subdir, '/');
        if ($subdir === '' || strpos($subdir, '..') !== false || strpos($subdir, "\0") !== false) {
            return null;
        }
        foreach (explode('/', $subdir) as $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..') {
                return null;
            }
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $seg)) {
                return null;
            }
        }

        return $subdir;
    }

    /**
     * Directory (relative to admin upload disk) where finalized ajax images are stored after save.
     *
     * @param  string|null  $overrideSubdir  e.g. webarex/returns; invalid values fall back to config-only path
     */
    public static function permanentDirForFinalize(?string $overrideSubdir = null): string
    {
        $base = trim((string) config('admin.upload.directory.image'), '/');
        $extra = '';
        if ($overrideSubdir !== null && $overrideSubdir !== '') {
            $san = self::sanitizeRelativeSubdir($overrideSubdir);
            $extra = $san ?? '';
        } else {
            $fromConfig = (string) config('admin.upload.ajax_image_permanent_subdir', '');
            $san = self::sanitizeRelativeSubdir($fromConfig);
            $extra = $san ?? '';
        }

        if ($base === '') {
            return $extra;
        }

        return $extra === '' ? $base : $base.'/'.$extra;
    }

    public static function stagingRelativeDir(): string
    {
        $base = trim((string) config('admin.upload.directory.image'), '/');
        $subRaw = trim((string) config('admin.upload.ajax_image_staging_subdir', 'tmp'), '/');
        $subSan = self::sanitizeRelativeSubdir($subRaw);
        $sub = $subSan ?? ($subRaw !== '' ? 'tmp' : '');

        if ($sub === '') {
            return $base;
        }

        return $base === '' ? $sub : $base.'/'.$sub;
    }

    /**
     * @param  string  $path  relative path on admin upload disk
     */
    public static function isStagedRelativePath(string $path): bool
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $staging = self::stagingRelativeDir();
        if ($staging === '') {
            return false;
        }
        $prefix = $staging.'/';
        if (!Str::startsWith($path, $prefix)) {
            return false;
        }
        $rest = substr($path, strlen($prefix));

        return $rest !== '' && strpos($rest, '/') === false;
    }

    /**
     * Move one staged file into the permanent image directory; otherwise return $path unchanged.
     */
    public static function finalizeSingleStagedPath(Filesystem $disk, string $baseDir, string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (!self::isStagedRelativePath($path)) {
            return $path;
        }

        $file = basename($path);
        if ($file === '' || strpos($file, '/') !== false) {
            return $path;
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $ext = is_string($ext) ? strtolower($ext) : '';

        $dest = $baseDir === '' ? $file : $baseDir.'/'.$file;
        if ($disk->exists($dest)) {
            do {
                $name = Str::random(16).($ext !== '' ? '.'.$ext : '');
                $dest = $baseDir === '' ? $name : $baseDir.'/'.$name;
            } while ($disk->exists($dest));
        }

        for ($attempt = 1; $attempt <= self::FINALIZE_RETRY_ATTEMPTS; $attempt++) {
            try {
                if ($disk->copy($path, $dest)) {
                    try {
                        $disk->delete($path);
                    } catch (\Throwable $e) {
                    }

                    return $dest;
                }
            } catch (\Throwable $e) {
            }

            if ($attempt < self::FINALIZE_RETRY_ATTEMPTS) {
                usleep(self::FINALIZE_RETRY_SLEEP_US * $attempt);
            }
        }

        return $path;
    }

    /**
     * Finalize one staged path value outside model events (e.g. form saving hook).
     */
    public static function finalizeStagedPathValue($value, ?string $permanentSubdirOverride = null): string
    {
        if (!is_string($value) || trim($value) === '') {
            return is_string($value) ? trim($value) : '';
        }

        $disk = Storage::disk(config('admin.upload.disk'));
        $baseDir = self::permanentDirForFinalize($permanentSubdirOverride);

        return self::finalizeSingleStagedPath($disk, $baseDir, trim(str_replace('\\', '/', $value)));
    }

    /**
     * Finalize a list of staged paths outside model events.
     *
     * @param  mixed  $items
     * @return array<int, string>
     */
    public static function finalizeStagedPathArray($items, ?string $permanentSubdirOverride = null): array
    {
        if (!is_array($items) || $items === []) {
            return [];
        }

        $out = [];
        foreach ($items as $value) {
            $final = self::finalizeStagedPathValue($value, $permanentSubdirOverride);
            if ($final !== '') {
                $out[] = $final;
            }
        }

        return $out;
    }

    /**
     * Move staged files to the permanent image directory before DB write.
     *
     * @param  string[]  $columns  attribute names (e.g. LABEL_PICTURE1)
     * @param  string|null  $permanentSubdirOverride  under directory.image, e.g. webarex/returns
     */
    public static function finalizeStagedPathsOnModel(Model $model, array $columns, ?string $permanentSubdirOverride = null): void
    {
        $diskName = config('admin.upload.disk');
        $disk = Storage::disk($diskName);
        $baseDir = self::permanentDirForFinalize($permanentSubdirOverride);

        foreach ($columns as $col) {
            $v = $model->getAttribute($col);
            if (!is_string($v) || $v === '') {
                continue;
            }
            $new = self::finalizeSingleStagedPath($disk, $baseDir, $v);
            if ($new !== $v) {
                $model->setAttribute($col, $new);
            }
        }
    }

    /**
     * Finalize staged paths stored as an array on one attribute (e.g. JSON "pictures" column).
     *
     * @param  string|null  $permanentSubdirOverride  under directory.image
     */
    public static function finalizeStagedPathsInJsonPathArray(Model $model, string $attribute, ?string $permanentSubdirOverride = null): void
    {
        $items = $model->getAttribute($attribute);
        if (!is_array($items) || $items === []) {
            return;
        }

        $diskName = config('admin.upload.disk');
        $disk = Storage::disk($diskName);
        $baseDir = self::permanentDirForFinalize($permanentSubdirOverride);

        $out = [];
        foreach ($items as $p) {
            if (!is_string($p) || trim($p) === '') {
                continue;
            }
            $out[] = self::finalizeSingleStagedPath($disk, $baseDir, trim(str_replace('\\', '/', $p)));
        }

        $model->setAttribute($attribute, $out);
    }
}
