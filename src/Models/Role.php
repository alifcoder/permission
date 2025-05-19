<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-15
 * Contact: https://t.me/alif_coder
 * Time: 6:16 PM
 */

namespace Alif\Permissions\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class Role extends Model
{
    use SoftDeletes;

    protected $guarded = false;

    protected $casts = [
            'deleted_at' => 'immutable_datetime:Y-m-d H:i:s',
            'created_at' => 'immutable_datetime:Y-m-d H:i:s',
            'updated_at' => 'immutable_datetime:Y-m-d H:i:s',
    ];

    const string SUPER_ADMIN = 'SUPER ADMIN';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->{$model->getKeyName()}) && config('permissions.is_model_uuid', true) === true) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
        });

        // clear cache when user is deleted and updated
        static::deleted(function (self $model) {
            if (permissionCacheable() === true) {
                foreach ($this->usersId() as $user) {
                    \Cache::tags(['user_role:' . $user->id])->flush();
                }
            }
        });
        static::updated(function (self $model) {
            if (permissionCacheable() === true) {
                foreach ($this->usersId() as $user) {
                    \Cache::tags(['user_role:' . $user->id])->flush();
                }
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

    public function name(): Attribute
    {
        return Attribute::make(
                set: fn($value) => mb_strtoupper($value),
        );
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(config('permissions.models.permission'), 'role_permission', 'role_id', 'permission_id');
    }

    private function usersId(): array
    {
        return \DB::table('user_role')
                ->where('role_id', $this->id)
                ->pluck('user_id')
                ->toArray() ?? [];
    }
}