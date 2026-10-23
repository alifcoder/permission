<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 10:54 AM
 */

namespace Alif\Permissions\Models;

use Alif\Permissions\Models\Concerns\HasUuidPrimaryKey;
use Alif\Permissions\Models\Concerns\ResolvesKeys;
use Alif\Permissions\Support\PermissionCache;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasUuidPrimaryKey;
    use ResolvesKeys;

    public $timestamps    = false;
    protected $guarded    = false;

    protected static function booted(): void
    {
        // Permission names are cached inside the role set of every user having it,
        // so renaming or deleting one only invalidates those cached sets.
        $bump = static fn(self $permission) => PermissionCache::bump('permission', $permission->getKey());

        static::updated($bump);
        static::deleted($bump);

        // restored and forceDeleted exist only with SoftDeletes, applications may add it
        static::registerModelEvent('restored', $bump);
        static::registerModelEvent('forceDeleted', $bump);
    }

    /**
     * Columns used to look up a permission by a human readable value.
     *
     * @return array<string, bool>
     */
    protected static function searchableColumns(): array
    {
        return ['name' => false];
    }
}
