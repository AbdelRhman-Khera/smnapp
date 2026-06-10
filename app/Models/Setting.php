<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'label',
        'type',
        'value',
        'description',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'value' => 'array',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->first()?->value ?? $default;
    }

    public static function paymentMethods(): array
    {
        return collect(static::getValue('payment_methods', []))
            ->sortBy(fn (array $method): int => (int) ($method['sort_order'] ?? 0))
            ->values()
            ->all();
    }

    public static function activePaymentMethodCodes(): array
    {
        return collect(static::paymentMethods())
            ->filter(fn (array $method): bool => (bool) ($method['is_active'] ?? false))
            ->pluck('code')
            ->filter()
            ->values()
            ->all();
    }
}
