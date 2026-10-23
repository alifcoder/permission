<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-15
 * Contact: https://t.me/alif_coder
 * Time: 6:11 PM
 */

namespace Alif\Permissions\Traits;

use Alif\Permissions\Models\Role;
use Alif\Permissions\Support\PermissionCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @property-read EloquentCollection $roles
 * @property-read Collection         $permissions
 */
trait HasRolesTrait
{
    /**
     * Pivot table between users and roles.
     */
    public const ROLE_PIVOT = 'user_role';

    /**
     * Roles/permissions already resolved by this instance.
     *
     * @var array<string, mixed>
     */
    protected array $alifPermissionMemo = [];

    protected static function bootHasRolesTrait(): void
    {
        // Drop the cache of the user only, a deleted user can not affect the others.
        static::deleted(function (Model $model) {
            PermissionCache::flushUser($model);
        });
    }

    /**
     * Roles relation of the user.
     *
     * ATTENTION! This is a query builder, it is never cached.
     * Use the "$user->roles" attribute to read the (cached) roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(config('permissions.models.role'), self::ROLE_PIVOT, 'user_id', 'role_id');
    }

    /**
     * Get all roles of the user with their permissions, from the cache when it is enabled.
     */
    public function getRolesAttribute(): EloquentCollection
    {
        // an eager loaded relation always wins
        if ($this->relationLoaded('roles')) {
            return $this->getRelation('roles')->loadMissing('permissions');
        }

        $this->discardOutdatedMemo();

        if (isset($this->alifPermissionMemo['roles'])) {
            return $this->alifPermissionMemo['roles'];
        }

        $roles = PermissionCache::enabled() ? $this->cachedRoles() : $this->fetchRoles();

        return $this->alifPermissionMemo['roles'] = $roles;
    }

    /**
     * Get the unique permissions of all roles of the user.
     */
    public function getPermissionsAttribute(): Collection
    {
        $this->discardOutdatedMemo();

        if (isset($this->alifPermissionMemo['permissions'])) {
            return $this->alifPermissionMemo['permissions'];
        }

        return $this->alifPermissionMemo['permissions'] = $this->roles
                ->pluck('permissions')
                ->flatten()
                ->unique(fn(Model $permission) => $permission->getKey())
                ->values();
    }

    /**
     * Get the names of all permissions of the user.
     *
     * @return array<int, string>
     */
    public function permissionNames(): array
    {
        $this->discardOutdatedMemo();

        return $this->alifPermissionMemo['permission_names'] ??= $this->permissions->pluck('name')->all();
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasAllRoles(Role::SUPER_ADMIN);
    }

    /**
     * Assign roles to user
     * You can give to parameter:
     *  - Collection -> Collection of Role or id or s_code or name
     *  - EloquentCollection -> Collection of Role
     *  - array -> Array of Role or id or s_code or name
     *  - Role -> Role model
     *  - string -> Role name or s_code or id
     *  - int -> Role id
     */
    public function assignRoles(Collection|EloquentCollection|array|Role|string|int $value): void
    {
        $keys = $this->resolveRoleKeys($value);

        if ($keys === []) {
            return;
        }

        // syncWithoutDetaching() is used instead of attach() to stay
        // idempotent and to avoid duplicate key violations on the pivot.
        $this->roles()->syncWithoutDetaching($keys);

        $this->forgetPermissionCache();
    }

    /**
     * Replace the roles of the user.
     * You can give to parameter:
     *  - Collection -> Collection of Role or id or s_code or name
     *  - EloquentCollection -> Collection of Role
     *  - array -> Array of Role or id or s_code or name
     *  - Role -> Role model
     *  - string -> Role name or s_code or id
     *  - int -> Role id
     */
    public function syncRoles(Collection|EloquentCollection|array|Role|string|int $value): void
    {
        $this->roles()->sync($this->resolveRoleKeys($value));

        $this->forgetPermissionCache();
    }

    /**
     * Remove roles from user
     * You can give to parameter:
     *  - Collection -> Collection of Role or id or s_code or name
     *  - EloquentCollection -> Collection of Role
     *  - array -> Array of Role or id or s_code or name
     *  - Role -> Role model
     *  - string -> Role name or s_code or id
     *  - int -> Role id
     */
    public function removeRole(Collection|EloquentCollection|array|Role|string|int $value): void
    {
        $keys = $this->resolveRoleKeys($value);

        if ($keys === []) {
            return;
        }

        $this->roles()->detach($keys);

        $this->forgetPermissionCache();
    }

    /**
     * Check if user has all the given roles
     * You can give to parameter:
     *  - Collection -> Collection of Role or id or s_code or name
     *  - EloquentCollection -> Collection of Role
     *  - array -> Array of Role or id or s_code or name
     *  - Role -> Role model
     *  - string -> Role name or s_code or id
     *  - int -> Role id
     */
    public function hasAllRoles(Collection|EloquentCollection|array|Role|string|int $value): bool
    {
        $needles = $this->roleNeedles($value);

        if ($needles === []) {
            return false;
        }

        foreach ($needles as $needle) {
            if ($this->matchesRole($needle) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has any of the given roles
     * You can give to parameter:
     *  - Collection -> Collection of Role or id or s_code or name
     *  - EloquentCollection -> Collection of Role
     *  - array -> Array of Role or id or s_code or name
     *  - Role -> Role model
     *  - string -> Role name or s_code or id
     *  - int -> Role id
     */
    public function hasAnyRole(Collection|EloquentCollection|array|Role|string|int $value): bool
    {
        foreach ($this->roleNeedles($value) as $needle) {
            if ($this->matchesRole($needle) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     * You can give to parameter:
     *  - array -> Array of permission names
     *  - string -> Permission name
     */
    public function hasAllPermissions(array|string $permissionNames): bool
    {
        $permissionNames = array_filter(Arr::wrap($permissionNames), 'is_string');

        if ($permissionNames === []) {
            return false;
        }

        $permissions = $this->permissionNames();

        foreach ($permissionNames as $name) {
            if (in_array($name, $permissions, true) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has any of the given permissions
     * You can give to parameter:
     *  - array -> Array of permission names
     *  - string -> Permission name
     */
    public function hasAnyPermission(array|string $permissionNames): bool
    {
        $permissionNames = array_filter(Arr::wrap($permissionNames), 'is_string');

        if ($permissionNames === []) {
            return false;
        }

        return array_intersect($permissionNames, $this->permissionNames()) !== [];
    }

    /**
     * Drop the cached roles and permissions of the user.
     * Called automatically after every role change.
     */
    public function forgetPermissionCache(): void
    {
        $this->alifPermissionMemo = [];
        $this->unsetRelation('roles');

        PermissionCache::flushUser($this);
    }

    /**
     * Forget what this instance resolved before the last cache flush.
     */
    private function discardOutdatedMemo(): void
    {
        $generation = PermissionCache::generation();

        if (($this->alifPermissionMemo['generation'] ?? null) !== $generation) {
            $this->alifPermissionMemo = ['generation' => $generation];
        }
    }


    // helper functions

    /**
     * Read the roles of the user from the database.
     */
    private function fetchRoles(): EloquentCollection
    {
        return $this->roles()->with('permissions')->get();
    }

    /**
     * Cache only arrays of raw attributes. Serialized Eloquent objects can become
     * __PHP_Incomplete_Class when a cache entry outlives its model class.
     */
    private function cachedRoles(): EloquentCollection
    {
        $key = PermissionCache::key($this, 'roles');
        $roles = null;
        $remember = function () use (&$roles): array {
            $roles = $this->fetchRoles();

            return $this->rolesToCache($roles);
        };
        $payload = PermissionCache::remember($this, $key, $remember);

        // Replace entries written by older versions, which stored the collection itself.
        if (!is_array($payload) || ($payload['version'] ?? null) !== 1 || !is_array($payload['roles'] ?? null)) {
            PermissionCache::forget($this, $key);
            $payload = PermissionCache::remember($this, $key, $remember);
        }

        return $roles ?? $this->rolesFromCache($payload['roles']);
    }

    private function rolesToCache(EloquentCollection $roles): array
    {
        $rolePivot = $this->roles()->getPivotAccessor();

        return [
                'version' => 1,
                'roles' => $roles->map(function (Model $role) use ($rolePivot) {
                    $permissionPivot = $role->permissions()->getPivotAccessor();

                    return [
                            'attributes' => $role->getRawOriginal(),
                            'connection' => $role->getConnectionName(),
                            'pivot' => $this->pivotAttributes($role, $rolePivot),
                            'permissions' => $role->permissions->map(fn(Model $permission) => [
                                    'attributes' => $permission->getRawOriginal(),
                                    'connection' => $permission->getConnectionName(),
                                    'pivot' => $this->pivotAttributes($permission, $permissionPivot),
                            ])->all(),
                    ];
                })->all(),
        ];
    }

    private function rolesFromCache(array $cachedRoles): EloquentCollection
    {
        $relation = $this->roles();
        $roleModel = $relation->getRelated();
        $roles = [];

        foreach ($cachedRoles as $cachedRole) {
            $role = $roleModel->newFromBuilder($cachedRole['attributes'], $cachedRole['connection']);

            if ($cachedRole['pivot'] !== null) {
                $role->setRelation($relation->getPivotAccessor(), $relation->newExistingPivot($cachedRole['pivot']));
            }

            $permissionRelation = $role->permissions();
            $permissionModel = $permissionRelation->getRelated();
            $permissions = [];

            foreach ($cachedRole['permissions'] as $cachedPermission) {
                $permission = $permissionModel->newFromBuilder($cachedPermission['attributes'], $cachedPermission['connection']);

                if ($cachedPermission['pivot'] !== null) {
                    $permission->setRelation($permissionRelation->getPivotAccessor(), $permissionRelation->newExistingPivot($cachedPermission['pivot']));
                }

                $permissions[] = $permission;
            }

            $role->setRelation('permissions', $permissionModel->newCollection($permissions));
            $roles[] = $role;
        }

        return $roleModel->newCollection($roles);
    }

    private function pivotAttributes(Model $model, string $accessor): ?array
    {
        return $model->relationLoaded($accessor) ? $model->getRelation($accessor)?->getRawOriginal() : null;
    }

    /**
     * Resolve the given value into role primary keys.
     *
     * @return array<int, int|string>
     */
    private function resolveRoleKeys(mixed $value): array
    {
        /** @var class-string<Role> $role */
        $role = config('permissions.models.role');

        return $role::resolveKeys($value);
    }

    /**
     * Flatten the given value into a list of ids, names or s_codes.
     *
     * @return array<int, int|string>
     */
    private function roleNeedles(mixed $value): array
    {
        $needles = [];

        foreach (is_iterable($value) ? $value : [$value] as $item) {
            if ($item instanceof Model) {
                if ($item->getKey() !== null) {
                    $needles[] = $item->getKey();
                }
            } elseif (is_int($item)) {
                $needles[] = $item;
            } elseif (is_string($item) && trim($item) !== '') {
                $needles[] = trim($item);
            }
        }

        return array_values(array_unique($needles, SORT_REGULAR));
    }

    /**
     * Check the given id, name or s_code against the roles of the user in memory.
     */
    private function matchesRole(int|string $needle): bool
    {
        $needle = (string)$needle;
        $upper  = mb_strtoupper($needle);

        return $this->roles->contains(function (Model $role) use ($needle, $upper) {
            return (string)$role->getKey() === $needle
                    || (string)$role->getAttribute('s_code') === $needle
                    || mb_strtoupper((string)$role->getAttribute('name')) === $upper;
        });
    }
}
