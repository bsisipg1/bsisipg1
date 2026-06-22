<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    public const APP_DOWNLOAD_URL = 'app_download_url';

    public const DEFAULT_APP_DOWNLOAD_URL = 'https://github.com/baklod/bnapp/releases/download/V2/i-baao.apk';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $setting = static::query()->where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        $value = $setting->value;

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value !== '' ? $value : null],
        );
    }

    public static function appDownloadUrl(): ?string
    {
        return static::get(static::APP_DOWNLOAD_URL, static::DEFAULT_APP_DOWNLOAD_URL);
    }
}
