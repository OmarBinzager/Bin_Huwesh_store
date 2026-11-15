<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait StorefrontMediaTrait
{
    public static function resolveMediaUrl(?string $path, string $folder = 'storefront'): string
    {
        if (empty($path)) {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        $relative = $folder . '/' . ltrim($path, '/');
        if (Storage::disk('public')->exists($relative)) {
            return asset('storage/app/public/' . $relative);
        }

        return asset('storage/app/public/' . $relative);
    }
}
