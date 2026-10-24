# Migrating from 1.x to 2.0

Version 2.0 moves authentication into this plugin and drops the `brammo/auth` dependency. Most other changes are security fixes that need no action. This guide covers what a host application has to change.

See [CHANGELOG.md](../CHANGELOG.md) for the full list of changes.

## Checklist

1. [Upgrade to PHP 8.3](#requirements)
2. [Update Composer dependencies](#composer)
3. [Keep the existing `users` table](#database)
4. [Move auth configuration to `Admin.Auth`](#configuration)
5. [Replace `Brammo\Auth` classes and table names in your code](#code-references)
6. [Change logout links to POST](#logout-is-post-only)
7. [Move overridden templates](#templates)
8. [Move translations of login messages](#translations)
9. [Expect admins to log in again](#sessions)

## Requirements

- PHP **8.3** or higher (was 8.2)
- CakePHP 5.3 (unchanged)

## Composer

`brammo/auth` is no longer required. The plugin now depends on `cakephp/authentication` ^3.0 and `cakephp/migrations` ^5.0 directly.

```bash
composer require brammo/admin:^2.0
```

If your application does not use `brammo/auth` anywhere else, remove it:

```bash
composer remove brammo/auth
```

`AdminPlugin` no longer calls `addPlugin('Brammo/Auth')`. If you keep `brammo/auth` for another part of your site (for example a public login), load it yourself in `Application::bootstrap()` and see [Sites that also use brammo/auth](#sites-that-also-use-brammoauth).

## Database

The `users` table schema is the same as in `brammo/auth` 1.x: `name`, `email`, `password`, `status`, `created`, `modified`, a unique index `UNIQUE_EMAIL` and an index `IDX_STATUS`. Existing rows work without changes.

The plugin ships its own migration, `config/Migrations/20250101000000_CreateUsers.php`. It creates the table and **fails if `users` already exists**.

**Existing installs** (the table was created by `brammo/auth`): do not run the migration. Mark it as applied instead, so a later `migrations migrate -p Brammo/Admin` does not try to create the table again:

```bash
bin/cake migrations mark_migrated -p Brammo/Admin
```

**New installs**:

```bash
bin/cake migrations migrate -p Brammo/Admin
```

Optional cleanup: once `brammo/auth` is removed, you can drop its migration log table (`brammo_auth_phinxlog`).

## Configuration

Auth settings moved from the top-level `Auth` key to `Admin.Auth`. Defaults are in the plugin's `config/auth.php`. See [CONFIGURATION.md](CONFIGURATION.md#authentication) for every key.

### Legacy `Auth` key

The legacy key still works, with limits:

- `Auth` is merged into the defaults **only when `Admin.Auth` is not set**. If both are set, `Auth` is ignored.
- The merge happens when the plugin bootstraps. `Auth` values written after that point are not picked up.

Rename the key to stop relying on the fallback:

```php
// 1.x
'Auth' => [
    'Authentication' => ['sessionKey' => 'AdminAuth'],
],

// 2.0
'Admin' => [
    'Auth' => [
        'Authentication' => ['sessionKey' => 'AdminAuth'],
    ],
],
```

### Values to check

| Key | 1.x | 2.0 | Action |
|-----|-----|-----|--------|
| `Users.table` | `Brammo/Auth.Users` | `Brammo/Admin.Users` | Remove the override, or point it at your own table. `Brammo/Auth.Users` no longer exists once `brammo/auth` is removed |
| `Users.controller` | `Brammo/Auth.User` | not used | Remove. Routes no longer read it |
| `Templates.login` | `Brammo/Admin.User/login` | `Brammo/Admin.Users/login` | Change to `Brammo/Admin.Users/login`. The old template no longer exists, so the login page fails to render |
| `Routes.login` / `Routes.logout` | connected by `brammo/auth` | fixed at `/admin/login` and `/admin/logout` | See below |

### Custom login and logout URLs

In 1.x, `brammo/auth` connected whatever URLs `Routes.login` and `Routes.logout` held. In 2.0 the plugin connects fixed routes under `/admin`:

| URL | Action |
|-----|--------|
| `/admin/login` | `Users::login` |
| `/admin/logout` | `Users::logout` (POST only) |
| `/admin/profile` | `Users::profile` |
| `/admin/user/profile` | `User::profile` (alias) |

`Routes.login` is still used for the unauthenticated redirect and the redirect after logout, and `Routes.loginRedirect` for the redirect after login. If you set `Routes.login` to a different URL, connect that route yourself in `config/routes.php`:

```php
$routes->connect('/login', ['plugin' => 'Brammo/Admin', 'controller' => 'Users', 'action' => 'login']);
```

### Sites that also use brammo/auth

`brammo/auth` stores its config under `Auth`. If your site keeps it for a public area and does not set `Admin.Auth`, the admin merges the public `Auth` config into its own and shares its session key, cookie name and user table. Always set `Admin.Auth` in that setup so the two areas stay separate:

```php
'Admin' => [
    'Auth' => [
        'Authentication' => [
            'sessionKey' => 'AdminAuth',
            'cookieName' => 'AdminCookieAuth',
        ],
    ],
],
```

`AdminPlugin::middleware()` adds `AuthenticationMiddleware` to the whole application. If you build one authentication service for both areas, load the plugin with `$this->addPlugin('Brammo/Admin', ['middleware' => false])` and read `Admin.Auth` for the admin part.

## Code references

Replace `Brammo\Auth` classes and table aliases:

| 1.x | 2.0 |
|-----|-----|
| `Brammo\Auth\Model\Entity\User` | `Brammo\Admin\Model\Entity\User` |
| `Brammo\Auth\Model\Table\UsersTable` | `Brammo\Admin\Model\Table\UsersTable` |
| `Brammo\Auth\AuthenticationServiceProvider` | `Brammo\Admin\AuthenticationServiceProvider` |
| `fetchTable('Brammo/Auth.Users')` | `fetchTable('Brammo/Admin.Users')` |
| `['plugin' => 'Brammo/Auth', 'controller' => 'User', ...]` | `['plugin' => 'Brammo/Admin', 'controller' => 'Users', ...]` |

The entity keeps the same status constants (`STATUS_ACTIVE`, `STATUS_NEW`, `STATUS_BLOCKED`) and helpers (`isActive()`, `isBlocked()`, `isStatusNew()`). Passwords are hashed with `Admin.Auth.Authentication.passwordHasher`.

`UserController` still exists and extends `UsersController`, so URL arrays with `'controller' => 'User'` inside the `Brammo/Admin` plugin keep working. Prefer `Users` in new code.

If you use your own users table through `Users.table`, its entity should implement `isBlocked()` and `isStatusNew()` to get the blocked / not-activated login messages. Without them, every failed login shows the invalid-credentials message.

## Logout is POST only

`/admin/logout` now rejects GET requests (405), so other sites cannot log admins out with a link or an image. The built-in header menu already uses a POST link. Update any logout links in your own templates:

```php
// 1.x
<?= $this->Html->link(__('Log out'), ['plugin' => 'Brammo/Auth', 'controller' => 'User', 'action' => 'logout']) ?>

// 2.0
<?= $this->Form->postLink(__('Log out'), ['plugin' => 'Brammo/Admin', 'controller' => 'Users', 'action' => 'logout']) ?>
```

## Templates

`templates/User/` was renamed to `templates/Users/`. Move overrides in your application:

| 1.x override | 2.0 override |
|--------------|--------------|
| `templates/plugin/Brammo/Admin/User/login.php` | `templates/plugin/Brammo/Admin/Users/login.php` |
| `templates/plugin/Brammo/Admin/User/profile.php` | `templates/plugin/Brammo/Admin/Users/profile.php` |
| `templates/plugin/Brammo/Auth/User/login.php` | `templates/plugin/Brammo/Admin/Users/login.php` |

In a custom profile template, change the `@var` annotation for `$user` to `\Brammo\Admin\Model\Entity\User`.

The login page uses the `Brammo/Admin.login` layout, as before.

## Translations

Login flash messages (`Messages.invalidCredentials`, `Messages.blocked`, `Messages.notActivated`, and "You have been logged out") are translated in the `brammo/admin` domain. `brammo/auth` used the default domain. If you translated these strings in your app's `default.po`, move the entries to `resources/locales/<locale>/brammo_admin.po`. Bulgarian is included in the plugin.

## Sessions

The session key (`AdminAuth`) is unchanged, but sessions created by 1.x store a `Brammo\Auth\Model\Entity\User` object. After `brammo/auth` is removed, that class no longer exists and those sessions cannot be restored. Admins have to log in again after the upgrade. Clear stored sessions if you want a clean cut-over.

## Behavior changes that need no code changes

These are listed so they are not mistaken for regressions:

- **Profile**: only `name`, `email` and `password` are saved. Posted `status`, `created` or `modified` values are ignored.
- **Login**: the `redirect` query parameter only accepts local URLs. External URLs fall back to `Routes.loginRedirect`.
- **Login**: with `Messages.enumerateAccounts` enabled, the blocked / not-activated message is shown only when the password is correct. A wrong password always gets the invalid-credentials message.
- **File Manager**: array query values (`folder[]=…`) return an error instead of a 500. An empty folder shows page 1 of 1. Image resize is skipped when `ext-imagick` is missing. `.jpeg` files use `jpegQuality`.
- **FilterComponent**: `in` / `not_in` filters with no usable values are skipped. Array values on scalar filters are ignored.
- **SortableComponent**: non-integer primary keys (e.g. UUID) are kept as strings instead of being dropped. Pass `$ownerScope` when rows belong to a parent record (see [SORTABLE.md](SORTABLE.md)).
- **AttachmentsComponent**: the uploaded file is deleted when its row cannot be saved.
