<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 10:54 AM
 */

namespace Alif\Permissions\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Permission extends Model
{
    public    $timestamps = false;
    protected $guarded    = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->{$model->getKeyName()}) && config('permissions.is_model_uuid', true) === true) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return config('permissions.is_model_uuid', true) === false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return config('permissions.is_model_uuid', true) === false ? 'int' : 'string';
    }
}