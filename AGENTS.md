# Brammo Admin — Agent Instructions

CakePHP 5 **plugin** (`brammo/admin`) providing an admin dashboard UI, file manager, and view helpers. Host apps load it via `$this->addPlugin('Brammo/Admin')`.

## Stack

| Item | Version / tool |
|------|----------------|
| PHP | >= 8.2 (`composer.json`; README still says 8.1) |
| CakePHP | ^5.3 |
| UI | Bootstrap 5 via `friendsofcake/bootstrap-ui`, `brammo/bootstrap-ui` |
| Auth | `brammo/auth` (loaded in `AdminPlugin::bootstrap`) |
| Content helpers | `brammo/content` |
| Quality | PHPUnit 10, PHPStan 8 (`src/` only), Psalm 5, PHPCS (CakePHP ruleset) |

## Layout

```
src/AdminPlugin.php          Plugin bootstrap, middleware, Auth dependency
src/Controller/              AppController, UserController, FileManagerController
src/Controller/Component/    FilterComponent, SortableComponent, AttachmentsComponent
src/FileManager/             FileManagerService (path validation, uploads, image resize)
src/Model/Behavior/          AttachmentFileBehavior (optional unused-file unlink)
src/View/AppView.php         Registers helpers (BootstrapUI, Brammo, Admin)
src/View/Helper/             FormHelper, ButtonHelper
config/admin.php             Admin.* configuration (see docs/CONFIGURATION.md)
config/routes.php            Plugin routes under /admin
templates/                   Layouts, elements (Form/, Sidebar/, etc.)
templates/element/Form/      image.php, editor.php (wraps Brammo/Content.editor), translations.php
webroot/css|js/              Plugin assets
resources/locales/           bg/brammo_admin.po
tests/TestCase/              PHPUnit by component
docs/                        HELPERS, FILEMANAGER, CONFIGURATION, PHPSTAN, PSALM
```

## Conventions

- **Namespace**: `Brammo\Admin\`, tests `Brammo\Admin\Test\`
- **Strict types**: `declare(strict_types=1);` on all PHP files
- **i18n**: User-facing strings use `__d('brammo/admin', '...')`; domain file `resources/locales/*/brammo_admin.po`
- **Plugin paths**: Templates/elements `Brammo/Admin.*` or `Brammo/Admin.Form/image`; assets `Brammo/Admin.script-name`
- **Views**: Controllers set `AppView`; layout `Brammo/Admin.default`
- **Config**: Read with `Configure::read('Admin.*')`; defaults in `config/admin.php`
- **File manager**: Requires `Admin.FileManager` (basePath, topFolders, fileTypes); path validation via `isValidFolder` / `isPathWithinBase` / `sanitizeFilename`
- **FormHelper**: Extends BootstrapUI; custom `control` types: `image`, `html` (WYSIWYG HTML editor), `dateRange`
- **FilterComponent**: Opt-in list filters + sort/page session; see [docs/FILTER.md](docs/FILTER.md)
- **SortableComponent**: Drag-and-drop reorder; see [docs/SORTABLE.md](docs/SORTABLE.md)
- **AttachmentsComponent**: Bulk upload + optional AttachmentFileBehavior; see [docs/ATTACHMENTS.md](docs/ATTACHMENTS.md)
- **SortableComponent**: Drag-and-drop reorder via SortableJS + ADmad Sequence; see [docs/SORTABLE.md](docs/SORTABLE.md)
- **AttachmentsComponent**: Bulk upload into File Manager + ORM rows; see [docs/ATTACHMENTS.md](docs/ATTACHMENTS.md)
- **AttachmentFileBehavior**: Unlink unused attachment paths after delete (File Manager path checks)

## Commands

```bash
composer test          # PHPUnit
composer cs-check      # PHPCS (src + tests)
composer cs-fix        # PHPCBF
composer stan          # PHPStan level 8 on src/
composer psalm         # Psalm on project
composer check         # test + cs-check + stan
composer check-full    # check + psalm
composer analyse       # stan + psalm
composer stan-tests    # PHPStan level 6 on tests/
```

Use `COMPOSER_ALLOW_SUPERUSER=1` if Composer refuses to run as root.

## When changing code

1. Match existing style (PSR-12, CakePHP plugin patterns, typed properties/methods).
2. Update `docs/` when adding config keys, helpers, or file-manager behavior.
3. Add or adjust tests under `tests/TestCase/` mirroring `src/` structure.
4. Run `composer test` and `composer cs-check` before finishing.
5. PHPStan does not analyse `tests/`; still keep tests typed and consistent.
6. **Security** (file manager): never weaken folder/path checks; use `realpath` + `isPathWithinBase` for destructive ops.
7. **Security** (sortable): never skip owner-scope filtering in `SortableComponent::reorder()`.
8. **Security** (attachments): unlink only via `AttachmentFileBehavior` path checks; keep `isValidFolder` on upload folders.
9. **Scope**: Minimal diffs; do not refactor unrelated controllers/helpers.

## Test patterns

- **Helpers**: Instantiate with `View`, load BootstrapUI `Html` where icons are needed; use `Entity` + `Form->create()` for form context.
- **FileManagerController**: Temp dir under `TMP`, `Configure::write('Admin.FileManager', ...)`, reflection for private methods; AJAX via `X-Requested-With: XMLHttpRequest`.
- **SortableComponent**: Isolated sqlite table, Sequence behavior, POST via `ServerRequest`.
- **AttachmentsComponent**: Temp File Manager dir, `Laminas\Diactoros\UploadedFile`, sqlite child table.
- **Integration**: `IntegrationTestTrait` where full HTTP stack is needed.
- Avoid tests that call removed private APIs (e.g. `getEntityFromContext` on `FormHelper`).

## Related docs

- [README.md](README.md) — install & features
- [docs/HELPERS.md](docs/HELPERS.md) — ButtonHelper, FormHelper
- [docs/FILTER.md](docs/FILTER.md) — FilterComponent for index filters
- [docs/SORTABLE.md](docs/SORTABLE.md) — drag-and-drop reorder
- [docs/ATTACHMENTS.md](docs/ATTACHMENTS.md) — multiupload and attachment UI
- [docs/FILEMANAGER.md](docs/FILEMANAGER.md) — file manager
- [docs/CONFIGURATION.md](docs/CONFIGURATION.md) — all `Admin.*` options
