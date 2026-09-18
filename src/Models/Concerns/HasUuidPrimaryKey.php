<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 */

namespace Alif\Permissions\Models\Concerns;

use Ramsey\Uuid\Uuid;

/**
 * Gives a model a UUID primary key when "permissions.is_model_uuid" is enabled.
 */
trait HasUuidPrimaryKey
{
    protected static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function (self $model) {
            if (static::usesUuid() === true && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
        });
    }

    /**
     * Check if the package is configured to use UUID primary keys.
     */
    public static function usesUuid(): bool
    {
        return config('permissions.is_model_uuid', true) === true;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return static::usesUuid() === false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return static::usesUuid() ? 'string' : 'int';
    }
}
