<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-15
 * Contact: https://t.me/alif_coder
 * Time: 6:11 PM
 */

namespace Alif\Permissions\Traits;

use Alif\Permissions\Models\Role;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRolesTrait
{
    protected static function bootHasRolesTrait(): void
    {
        // clear cache when user is deleted and updated
        static::deleted(function (self $_model) {
            \Cache::tags(['user_role:' . $this->id, 'alif_permission'])->flush();
        });
        static::updated(function (self $_model) {
            \Cache::tags(['user_role:' . $this->id, 'alif_permission'])->flush();
        });
    }

    /**
     * Get all roles of user
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        if (permissionCacheable()) {
            return \Cache::tags(['user_role:' . $this->id, 'alif_permission'])->rememberForever('roles_' . $this->id,
                    function () {
                        return $this->belongsToMany(config('permissions.models.role'), 'user_role', 'user_id', 'role_id');
                    }
            );
        } else {
            return $this->belongsToMany(config('permissions.models.role'), 'user_role', 'user_id', 'role_id');
        }
    }

    /**
     * Get all roles of user
     *
     * @return Collection
     */
    public function getPermissionsAttribute(): Collection
    {
        // get unique permissions from all roles
        if (permissionCacheable()) {
            return \Cache::tags(['user_role:' . $this->id, 'alif_permission'])->rememberForever('permissions_' .
                                                                                              $this->id,
                    function () {
                        return $this->roles()
                                ->with('permissions')
                                ->get()
                                ->pluck('permissions')
                                ->flatten()
                                ->unique('id');
                    }
            );
        } else {
            return $this->roles()
                    ->with('permissions')
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->unique('id');
        }
    }

    /**
     * Check if user is super admin
     *
     * @return bool
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
     *
     * @param Collection|EloquentCollection|array|Role|string|int $value
     *
     * @return void
     */
    function assignRoles(Collection|EloquentCollection|array|Role|string|int $value): void
    {
        $this->syncOrAttachRoles($value, 'attach');
    }

    /**
     * Attach roles to user
     * You can give to parameter:
     * - Collection -> Collection of Role or id or s_code or name
     * - EloquentCollection -> Collection of Role
     * - array -> Array of Role or id or s_code or name
     * - Role -> Role model
     * - string -> Role name or s_code or id
     * - int -> Role id
     *
     * @param Collection|EloquentCollection|array|Role|string|int $value
     *
     * @return void
     */
    public function syncRoles(Collection|EloquentCollection|array|Role|string|int $value): void
    {
        $this->syncOrAttachRoles($value, 'sync');
    }

    /**
     * Check if user has the given role
     * You can give to parameter:
     *  - Collection -> Collection of Role or id or s_code or name
     *  - EloquentCollection -> Collection of Role
     *  - array -> Array of Role or id or s_code or name
     *  - Role -> Role model
     *  - string -> Role name or s_code or id
     *  - int -> Role id
     *
     * @param Collection|EloquentCollection|array|Role|string|int $value
     *
     * @return bool
     */
    public function hasAllRoles(Collection|EloquentCollection|array|Role|string|int $value): bool
    {
        if (permissionCacheable()) {
            return \Cache::tags(['user_role:' . $this->id, 'alif_permission'])
                    ->rememberForever('has_all_roles_' . $this->id . ':' . $this->safe_md5($value),
                            function () use ($value) {
                                return $this->hasAllRolesFunc($value);
                            }
                    );
        } else {
            return $this->hasAllRolesFunc($value);
        }
    }

    private function hasAllRolesFunc(Collection|EloquentCollection|array|Role|string|int $value): bool
    {
        if (checkToUUID($value) || is_int($value)) {
            return $this->roles()->where('id', $value)->exists();
        } elseif (is_string($value)) {
            return $this->roles()->where('name', $value)->orWhere('s_code', $value)->exists();
        } elseif ($value instanceof Role) {
            return $this->roles()->where('id', $value->id)->exists();
        } elseif ($value instanceof Collection || is_array($value)) {
            $ids = [];
            foreach ($value as $item) {
                if ($item instanceof Role) {
                    $ids[] = $item->id;
                } elseif (is_int($item) || checkToUUID($item)) {
                    $ids[] = $item;
                } elseif (is_string($item)) {
                    $role = Role::where('name', $item)->orWhere('s_code', $item)->first();
                    if ($role) {
                        $ids[] = $role->id;
                    }
                }
            }

            return $this->roles()->whereIn('id', $ids)->count() === count($ids);
        } elseif ($value instanceof EloquentCollection) {
            return $this->roles()->whereIn('id', $value->pluck('id'))->count() === $value->count();
        }

        return false;
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
     *
     * @param Collection|EloquentCollection|array|Role|string|int $value
     *
     * @return bool
     */
    public function hasAnyRole(Collection|EloquentCollection|array|Role|string|int $value): bool
    {
        if (permissionCacheable()) {
            return \Cache::tags(['user_role:' . $this->id, 'alif_permission'])
                    ->rememberForever('has_any_role_' . $this->id . ':' . $this->safe_md5($value),
                            function () use ($value) {
                                return $this->hasAnyRoleFunc($value);
                            }
                    );
        } else {
            return $this->hasAnyRoleFunc($value);
        }
    }

    private function hasAnyRoleFunc(Collection|EloquentCollection|array|Role|string|int $value): bool
    {
        if (checkToUUID($value) || is_int($value)) {
            return $this->roles()->where('id', $value)->exists();
        } elseif (is_string($value)) {
            return $this->roles()->where('name', $value)->orWhere('s_code', $value)->exists();
        } elseif ($value instanceof Role) {
            return $this->roles()->where('id', $value->id)->exists();
        } elseif ($value instanceof Collection || is_array($value)) {
            $ids = [];
            foreach ($value as $item) {
                if ($item instanceof Role) {
                    $ids[] = $item->id;
                } elseif (is_int($item) || checkToUUID($item)) {
                    $ids[] = $item;
                } elseif (is_string($item)) {
                    $role = Role::where('name', $item)->orWhere('s_code', $item)->first();
                    if ($role) {
                        $ids[] = $role->id;
                    }
                }
            }

            return $this->roles()->whereIn('id', $ids)->exists();
        } elseif ($value instanceof EloquentCollection) {
            return $this->roles()->whereIn('id', $value->pluck('id')->toArray())->exists();
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     * You can give to parameter:
     *  - array -> Array of permission names
     *
     * @param array $permissionNames
     *
     * @return bool
     */
    public function hasAllPermissions(array $permissionNames): bool
    {
        if (permissionCacheable()) {
            return \Cache::tags(['user_role:' . $this->id, 'alif_permission'])
                    ->rememberForever('has_all_permissions_' . $this->id . ':' . $this->safe_md5($permissionNames),
                            function () use ($permissionNames) {
                                return $this->hasAllPermissionsFunc($permissionNames);
                            }
                    );
        } else {
            return $this->hasAllPermissionsFunc($permissionNames);
        }
    }

    private function hasAllPermissionsFunc(array $permissionNames): bool
    {
        if (count($permissionNames) === 0) {
            return false;
        }

        $permissions = $this->getPermissionsAttribute()->pluck('name')->toArray();

        return count(array_intersect($permissions, $permissionNames)) === count($permissionNames);
    }

    /**
     * Check if user has any of the given permissions
     * You can give to parameter:
     *  - array -> Array of permission names
     *
     * @param array $permissionNames
     *
     * @return bool
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        if (permissionCacheable()) {
            return \Cache::tags(['user_role:' . $this->id, 'alif_permission'])
                    ->rememberForever('has_any_permissions_' . $this->id . ':' . $this->safe_md5($permissionNames),
                            function () use ($permissionNames) {
                                return $this->hasAnyPermissionFunc($permissionNames);
                            }
                    );
        } else {
            return $this->hasAnyPermissionFunc($permissionNames);
        }
    }

    private function hasAnyPermissionFunc(array $permissionNames): bool
    {
        if (count($permissionNames) === 0) {
            return false;
        }

        $permissions = $this->getPermissionsAttribute()->pluck('name')->toArray();

        return count(array_intersect($permissions, $permissionNames)) > 0;
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
     *
     * @param Collection|EloquentCollection|array|Role|string|int $value
     *
     * @return void
     */
    public function removeRole(Collection|EloquentCollection|array|Role|string|int $value): void
    {
        if ($value instanceof Collection || is_array($value)) {
            $ids = [];
            foreach ($value as $item) {
                if ($item instanceof Role) {
                    $ids[] = $item->id;
                } elseif (is_int($item) || checkToUUID($item)) {
                    $ids[] = $item;
                } elseif (is_string($item)) {
                    $role = Role::where('name', $item)->orWhere('s_code', $item)->first();
                    if ($role) {
                        $ids[] = $role->id;
                    }
                }
            }
            $this->roles()->detach($ids);
        } elseif ($value instanceof EloquentCollection) {
            $this->roles()->detach($value->pluck('id')->toArray());
        } elseif ($value instanceof Role) {
            $this->roles()->detach($value->id);
        } elseif (checkToUUID($value) || is_int($value)) {
            $this->roles()->detach($value);
        } elseif (is_string($value)) {
            $role = Role::where('name', $value)->orWhere('s_code', $value)->first();
            if ($role) {
                $this->roles()->detach($role->id);
            }
        }
    }


    // helper functions
    private function syncOrAttachRoles(mixed $value, string $method): void
    {
        if ($value instanceof Collection || is_array($value)) {
            $ids = [];
            foreach ($value as $item) {
                if ($item instanceof Role) {
                    $ids[] = $item->id;
                } elseif (is_int($item) || checkToUUID($item)) {
                    $ids[] = $item;
                } elseif (is_string($item)) {
                    $role = Role::where('name', $item)->orWhere('s_code', $item)->first();
                    if ($role) {
                        $ids[] = $role->id;
                    }
                }
            }
            $this->roles()->$method($ids);
        } elseif ($value instanceof EloquentCollection) {
            $this->roles()->$method($value->pluck('id')->toArray());
        } elseif ($value instanceof Role) {
            $this->roles()->$method($value->id);
        } elseif (checkToUUID($value) || is_int($value)) {
            $this->roles()->$method($value);
        } elseif (is_string($value)) {
            $role = Role::where('name', $value)->orWhere('s_code', $value)->first();
            if ($role) {
                $this->roles()->$method($role->id);
            }
        }
    }

    private function safe_md5($value): string
    {
        return md5(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
