# 🔐 Alif Permissions

A simple, flexible role and permission management system for Laravel applications — designed to support
`Gate::before`, `SUPER ADMIN` logic, modular apps (`nwidart/laravel-modules`), and dynamic user model resolution.

---

## ✨ Features

- Role and permission management with pivot tables
- `SUPER ADMIN` bypass support using `Gate::before()`
- `HasRoles` trait for easy user integration
- Dynamically configurable user model
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
    'models'    => [
        'role'       => \Alif\Permissions\Models\Role::class,
        'permission' => \Alif\Permissions\Models\Permission::class,
    ],
    
    'cacheable' => true,
    
    'is_model_uuid' => true,
        
];
```

You can override the default user model or super-admin slug here.

---

## 🧬 Traits

In your `User` model, add the trait:

```php
use Alif\Permissions\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

---

## 🔐 Super Admin Access

Add this in your app (e.g., `AuthServiceProvider`) — or it's auto-registered by the package:

```php
Gate::before(function ($user, $ability) {
    return $user->isSuperAdmin() ? true : null;
});
```

This lets `super-admin` users bypass all policy/gate checks.

---

## 🧠 Usage

### Assign Roles & Permissions

```php
$admin = Role::create(['name' => 'Admin', 's_code' => 'admin']);
$edit = Permission::create(['name' => 'products.update']);

$admin->permissions()->attach($edit->id);
$user->syncRoles($admin->id);
```

### Check Permissions

```php
$user->hasAllRoles('admin'); // true
$user->hasAnyRoles('admin'); // true
$user->hasPermission('products.update'); // true
$user->isSuperAdmin(); // true or false
```

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
```

---

## 🧩 Folder Structure

```
src/
├── Models/
│   ├── Role.php
│   └── Permission.php
├── Traits/
│   └── HasRoles.php
├── Middleware/
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
php artisan permission:cache:clear
```

---

## 🧹 Uninstall (Clean Up)

Run this command before removing the package:

```bash
php artisan permission:uninstall
```

---

## 📜 License

MIT © [Shukhratjon Yuldashev](https://t.me/alif_coder)

---

## 🙌 Contributing

Pull requests and suggestions are welcome!
