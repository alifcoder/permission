# 🔐 Alif Permissions

A simple, flexible role and permission management system for Laravel applications — designed to support
`Gate::before`, `SUPER ADMIN` logic, modular apps (`nwidart/laravel-modules`), and dynamic user model resolution.

---

## ✨ Features

- Role and permission management with pivot tables
- `SUPER ADMIN` bypass support using `Gate::before()`
- `HasRolesTrait` trait for easy user integration
- Every role and permission check is answered from memory — one query per user, per request
- Automatic cache invalidation on every role/permission change
- Dynamically configurable role and permission models (UUID or auto-increment keys)
- Language file localization (EN, customizable)
- Clean service provider with publishable config and migrations
- Works with modular Laravel apps (like `nwidart/laravel-modules`)

---

## 📦 Requirements

- PHP `>=8.2`
- Laravel `^11.0 || ^12.0 || ^13.0`

---

## 🚀 Installation

```bash
composer require alifcoder/permissions
```

Then publish the config, translations, and migrations:

```bash
php artisan vendor:publish --tag=permissions
php artisan migrate
```

This will publish:

- `lang/vendor/permissions`
- `config/permission.php`
- `database/migrations/xxxx_xx_xx_xxxxxx_create_permissions_table.php`

---

## ⚙️ Configuration

Inside `config/permissions.php`:

```php
return [
    'models'        => [
        'role'       => \Alif\Permissions\Models\Role::class,
        'permission' => \Alif\Permissions\Models\Permission::class,
    ],

    // cache the roles and permissions of every user
    'cacheable'     => true,

    // cache store used by the package (null = default store)
    'cache_store'   => null,

    // lifetime in seconds of a cached entry (null = forever)
    'cache_ttl'     => null,

    // set to false when your primary keys are auto-incrementing integers
    'is_model_uuid' => true,

];
```

You can override the role and permission models here.

> ⚠️ Caching requires a cache store that supports tags (`redis`, `memcached`, `array`).
> When the store is not taggable the package keeps working, it simply reads from the database.

---

## 🧬 Traits

In your `User` model, add the trait:

```php
use Alif\Permissions\Traits\HasRolesTrait;

class User extends Authenticatable
{
    use HasRolesTrait;
}
```

---

## 🔐 Super Admin Access

Add this in your app (e.g., `AuthServiceProvider`) — or it's auto-registered by the package:

```php
Gate::before(function ($user) {
    // $user is null on guest checks
    return $user !== null && $user->isSuperAdmin() ? true : null;
});
```

This lets `SUPER ADMIN` users bypass all policy/gate checks, and the `role` / `permission` middleware.

---

## 🧠 Usage

### Assign Roles & Permissions

```php
$admin = Role::create(['name' => 'Admin', 's_code' => 'admin']);
$edit  = Permission::create(['name' => 'products.update']);

// role <-> permissions (these methods keep the cache in sync)
$admin->givePermissionTo($edit);           // model, id or name
$admin->syncPermissions(['products.update', 'products.read']);
$admin->revokePermissionTo('products.read');

// user <-> roles (these methods keep the cache in sync)
$user->assignRoles('admin');               // add, keeps the existing roles
$user->syncRoles([$admin, 'manager']);     // replace
$user->removeRole($admin->id);             // detach
```

Every method accepts a model, a primary key, a role `name`, a role `s_code`, or an array/collection of them.

> ⚠️ `$user->roles()->attach()` / `$role->permissions()->attach()` bypass the package, so they leave a stale
> cache behind. Use the methods above, or call `$user->forgetPermissionCache()` / `$role->forgetPermissionCache()`
> yourself afterwards.

### Check Roles & Permissions

```php
$user->hasAllRoles('admin');                              // true
$user->hasAnyRole(['admin', 'manager']);                  // true
$user->hasAllPermissions(['products.update']);            // true
$user->hasAnyPermission('products.update');               // true
$user->isSuperAdmin();                                    // true or false

$user->roles;               // roles of the user, with their permissions (cached)
$user->permissions;         // unique permissions of all roles (cached)
$user->permissionNames();   // the same, as a plain array of names
```

All checks above are resolved in memory: the roles of a user are read **once** (from the cache when it is
enabled, otherwise with a single query) and every following check reuses them.

### Cache invalidation

The cache is dropped automatically when:

| Change                                                        | What is dropped          |
|---------------------------------------------------------------|--------------------------|
| `assignRoles()`, `syncRoles()`, `removeRole()`                 | the cache of that user   |
| the user is deleted                                            | the cache of that user   |
| `givePermissionTo()`, `syncPermissions()`, `revokePermissionTo()` | the whole package cache  |
| a role is updated, deleted or restored                         | the whole package cache  |
| a permission is updated or deleted                             | the whole package cache  |

---

## 🌐 Localization

The package includes English (`en`) translations. To override or translate:

Then add `resources/lang/vendor/permissions/{locale}/permissions.php`.

---

## 🧑‍💻 Usage macro

Also you can use Route macro to check permissions and roles:

```php
Route::put('/products/{product}', function () {
    // Your logic here
})->permission('products.update');

Route::put('/admin', function () {
    // Your logic here
})->role('admin');

// or the middleware aliases directly, an optional second argument selects the guard
Route::put('/admin', fn() => null)->middleware('role:admin|manager,web');
```

> ⚠️ Several roles/permissions are checked with **AND**: `role:admin|manager` requires *both* roles.

---

## 🧩 Folder Structure

```
src/
├── Models/
│   ├── Concerns/
│   ├── Role.php
│   └── Permission.php
├── Traits/
│   └── HasRolesTrait.php
├── Support/
│   └── PermissionCache.php
├── Middleware/
├── Macros/
├── Exceptions/
├── Helpers/
├── Console/
├── PermissionServiceProvider.php
config/
└── permissions.php
resources/
└── lang/en/permissions.php
database/
└── migrations/
```

---

## 🧹 Clear permission caches

Run this command to clear the permission cache:

```bash
php artisan permission:cache-clear
```

---

## 🧹 Uninstall (Clean Up)

Run this command before removing the package:

```bash
php artisan permission:uninstall
```

It rolls back the migration, deletes the published files and clears the cache. In production it asks for a
confirmation, use `--force` to skip it.

---

## 🧪 Tests

```bash
composer install
composer test
```

The suite runs against `orchestra/testbench` with an in-memory SQLite database, and covers both UUID and
auto-incrementing primary keys, the cache invalidation matrix, the middlewares and the console commands.

---

## 📜 License

MIT © [Shukhratjon Yuldashev](https://t.me/alif_coder)

---

## 🙌 Contributing

Pull requests and suggestions are welcome!
