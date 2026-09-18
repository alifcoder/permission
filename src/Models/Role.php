<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-15
 * Contact: https://t.me/alif_coder
 * Time: 6:16 PM
 */

namespace Alif\Permissions\Models;

use Alif\Permissions\Models\Concerns\HasUuidPrimaryKey;
use Alif\Permissions\Models\Concerns\ResolvesKeys;
use Alif\Permissions\Support\PermissionCache;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Role extends Model
{
    use SoftDeletes;
    use HasUuidPrimaryKey;
    use ResolvesKeys;

    public const SUPER_ADMIN = 'SUPER ADMIN';

    /**
     * Pivot table between roles and permissions.
     */
    public const PERMISSION_PIVOT = 'role_permission';

    protected $guarded = false;

    protected $casts = [
            'deleted_at' => 'immutable_datetime:Y-m-d H:i:s',
            'created_at' => 'immutable_datetime:Y-m-d H:i:s',
            'updated_at' => 'immutable_datetime:Y-m-d H:i:s',
    ];

    protected static function booted(): void
    {
        // A role change (name, s_code, deletion, restore) may affect any user,
        // so the whole permission cache of the package is dropped.
        $flush = static fn() => PermissionCache::flush();

        static::updated($flush);
        static::deleted($flush);
        static::restored($flush);
    }

    /**
     * Columns used to look up a role by a human readable value.
     *
     * @return array<string, bool>
     */
    protected static function searchableColumns(): array
    {
        return ['name' => true, 's_code' => false];
    }

    /**
     * Role names are always stored upper-cased.
     */
    public function name(): Attribute
    {
        return Attribute::make(
                set: fn($value) => $value === null ? null : mb_strtoupper($value),
        );
    }

    /**
     * Get all permissions of the role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(config('permissions.models.permission'), self::PERMISSION_PIVOT, 'role_id', 'permission_id');
    }

    /**
     * Replace the permissions of the role.
     * You can give to parameter:
     *  - Collection|EloquentCollection|array -> Permission models, ids or names
     *  - Permission -> Permission model
     *  - string -> Permission name or id
     *  - int -> Permission id
     */
    public function syncPermissions(Collection|EloquentCollection|array|Permission|string|int $value): void
    {
        $this->permissions()->sync($this->resolvePermissionKeys($value));

        $this->forgetPermissionCache();
    }

    /**
     * Give the given permissions to the role, keeping the existing ones.
     */
    public function givePermissionTo(Collection|EloquentCollection|array|Permission|string|int $value): void
    {
        $keys = $this->resolvePermissionKeys($value);

        if ($keys === []) {
            return;
        }

        // syncWithoutDetaching() is used instead of attach() to stay
        // idempotent and to avoid duplicate key violations on the pivot.
        $this->permissions()->syncWithoutDetaching($keys);

        $this->forgetPermissionCache();
    }

    /**
     * Revoke the given permissions from the role.
     */
    public function revokePermissionTo(Collection|EloquentCollection|array|Permission|string|int $value): void
    {
        $keys = $this->resolvePermissionKeys($value);

        if ($keys === []) {
            return;
        }

        $this->permissions()->detach($keys);

        $this->forgetPermissionCache();
    }

    /**
     * Drop every cached role/permission set, because the permissions of this
     * role are embedded in the cache of every user owning it.
     */
    public function forgetPermissionCache(): void
    {
        $this->unsetRelation('permissions');

        PermissionCache::flush();
    }

    /**
     * @return array<int, int|string>
     */
    private function resolvePermissionKeys(mixed $value): array
    {
        /** @var class-string<Permission> $permission */
        $permission = config('permissions.models.permission');

        return $permission::resolveKeys($value);
    }
}
