<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    public static function getFloat(string $key, float $default): float
    {
        $value = static::getValue($key, $default);
        if (!is_numeric($value)) {
            return $default;
        }

        return (float) $value;
    }

    public static function putValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value)],
        );
    }
}
