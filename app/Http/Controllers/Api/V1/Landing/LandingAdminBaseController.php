<?php

namespace App\Http\Controllers\Api\V1\Landing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Shared helpers for the Landing CMS admin (mobile) API controllers.
 *
 * Mirrors the web portal admin behaviour established by the existing
 * `LandingProjectController` / `LandingAwardController` etc.:
 *   - Stores multilingual fields as JSON maps (`{"en":"...","sw":"..."}`),
 *     preserving any existing non-English entries during partial updates.
 *   - Stores uploaded images under `storage/app/public/landing/...` using safe,
 *     random server-generated filenames (never the client filename).
 *   - Deletes stored files only from under the `landing/` tree (defense-in-depth).
 */
abstract class LandingAdminBaseController extends Controller
{
    /**
     * Merge an English-language value into an existing JSON-localized map.
     * Preserves any pre-existing non-English entries.
     *
     * @param mixed       $existing  Eloquent attribute value (array or null).
     * @param string|null $value     English text from the request.
     */
    protected function mergeLocale($existing, ?string $value): ?array
    {
        if ($value === null || $value === '') {
            return is_array($existing) ? $existing : null;
        }
        $map = is_array($existing) ? $existing : [];
        $map['en'] = $value;
        return $map;
    }

    /**
     * Merge a per-language scalar value into an existing JSON-localized map.
     * Used for ?lang=sw style updates from the mobile admin form.
     */
    protected function mergeLocaleLang($existing, ?string $value, string $lang): ?array
    {
        if ($value === null) {
            return is_array($existing) ? $existing : null;
        }
        $map = is_array($existing) ? $existing : [];
        if ($value === '') {
            unset($map[$lang]);
        } else {
            $map[$lang] = $value;
        }
        return !empty($map) ? $map : null;
    }

    /**
     * Store an uploaded image under a `landing/...` directory and return the
     * `/storage/landing/...` public path. Returns null if no file was provided.
     */
    protected function storeUploadedImage(?UploadedFile $file, string $dir): ?string
    {
        if ($file === null || !$file->isValid()) {
            return null;
        }
        // server-generated filename, never trust the client name
        $path = $file->store($dir, 'public');
        return '/storage/' . $path;
    }

    /**
     * Defense-in-depth deletion: only ever touch files under the landing/ tree
     * within the public disk.
     */
    protected function deleteStoredFile(?string $publicPath): void
    {
        if (!$publicPath) {
            return;
        }
        $relative = preg_replace('#^/storage/#', '', $publicPath);
        if ($relative
            && str_starts_with($relative, 'landing/')
            && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    /**
     * Pull a list of multilingual values out of the request as a JSON map.
     * Accepts either an existing JSON string ({"en":"x","sw":"y"}) or just a
     * plain string (treated as the English entry).
     */
    protected function readLocalized(Request $request, string $key, $existing = null): ?array
    {
        $raw = $request->input($key);
        if ($raw === null) {
            return is_array($existing) ? $existing : null;
        }
        if (is_array($raw)) {
            $clean = array_filter($raw, fn ($v) => $v !== null && $v !== '');
            return !empty($clean) ? $clean : null;
        }
        // Plain string → English entry, merged onto existing.
        return $this->mergeLocale($existing, (string) $raw);
    }
}
