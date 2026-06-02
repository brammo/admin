# View Helpers

The Brammo Admin plugin provides several view helpers to simplify building Bootstrap-based admin interfaces. All helpers use CakePHP's `StringTemplateTrait` for flexible template customization.

## Table of Contents

- [ButtonHelper](#buttonhelper)
- [FormHelper](#formhelper)

---

## ButtonHelper

Generate Bootstrap-styled buttons with consistent styling and icons.

### Basic Usage

```php
// Simple link button
echo $this->Button->link('View Details', ['action' => 'view', $id]);

// Post link button
echo $this->Button->postLink('Submit', ['action' => 'submit', $id]);
```

### Preset Buttons

The helper provides convenient preset buttons for common actions:

```php
// Create button (green with plus icon)
echo $this->Button->create(['action' => 'add']);

// View button (info/cyan with eye icon)
echo $this->Button->view(['action' => 'view', $id]);

// Edit button (blue with pencil icon)
echo $this->Button->edit(['action' => 'edit', $id]);

// Compact edit button (small, icon-only)
echo $this->Button->editCompact(['action' => 'edit', $id]);

// Delete button (red with trash icon, includes confirmation)
echo $this->Button->delete(['action' => 'delete', $id]);

// Compact delete button (small, icon-only)
echo $this->Button->deleteCompact(['action' => 'delete', $id]);

// Preview button (info with external link icon, compact style)
echo $this->Button->preview(['action' => 'preview', $id]);
```

### Custom Buttons

Use the `render()` method for full control:

```php
echo $this->Button->render('Download', '/files/download', [
    'variant' => 'info',
    'icon' => 'download',
    'size' => 'lg',
]);
```

### Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `method` | string | `'get'` | HTTP method: `'get'` or `'post'` |
| `variant` | string | `'secondary'` | Bootstrap button variant: `primary`, `secondary`, `success`, `danger`, `warning`, `info`, `light`, `dark` |
| `icon` | string | `''` | Bootstrap Icons name (e.g., `'plus-circle'`, `'pencil'`) |
| `size` | string | `''` | Button size: `'sm'`, `'lg'`, or empty for default |
| `style` | string | `'normal'` | Display style: `'normal'` or `'compact'` (icon only with title as tooltip) |
| `confirm` | string | `''` | Confirmation message for post links |
| `target` | string | `''` | Link `target` attribute (e.g. `'_blank'` for `preview()`) |

### Examples

```php
// Large primary button with icon
echo $this->Button->link('Add New', ['action' => 'add'], [
    'variant' => 'primary',
    'icon' => 'plus',
    'size' => 'lg',
]);

// Compact icon-only button
echo $this->Button->link('Settings', ['action' => 'settings'], [
    'icon' => 'gear',
    'style' => 'compact',
]);

// Post button with confirmation
echo $this->Button->postLink('Archive', ['action' => 'archive', $id], [
    'variant' => 'warning',
    'icon' => 'archive',
    'confirm' => 'Are you sure you want to archive this item?',
]);
```

---

## FormHelper

Extends BootstrapUI Form helper with additional control types.

### Image Control

The `image` type renders an image picker/uploader that integrates with the [File Manager](FILEMANAGER.md).

#### Basic Usage

```php
// In a form context
echo $this->Form->create($entity);

// Simple image field
echo $this->Form->control('image', ['type' => 'image']);

// With custom folder
echo $this->Form->control('thumbnail', [
    'type' => 'image',
    'folder' => 'images/thumbnails',
]);

echo $this->Form->end();
```

#### Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `folder` | string | `'images'` | The folder path for image selection in File Manager |
| `label` | string | Field name | Custom label for the control |
| `allowEmpty` | bool | `true` | Whether empty values are allowed |

#### Examples

```php
// Featured image with custom label
echo $this->Form->control('featured_image', [
    'type' => 'image',
    'label' => 'Featured Image',
    'folder' => 'images/featured',
]);

// Required product image
echo $this->Form->control('product_image', [
    'type' => 'image',
    'folder' => 'images/products',
    'allowEmpty' => false,
]);

// Gallery image in specific subfolder
echo $this->Form->control('gallery_image', [
    'type' => 'image',
    'folder' => 'images/gallery/2024',
    'label' => 'Gallery Image',
]);
```

#### Features

The image control provides:

- **Preview**: Displays a thumbnail of the currently selected image
- **Select**: Opens the File Manager modal to browse and select existing images
- **Upload**: Direct upload button to upload new images to the specified folder
- **Delete**: Remove the selected image (clears the field value)

The control automatically:
- Stores the full path relative to the webroot (e.g., `/images/photo.jpg`)
- Validates that the folder is within the configured File Manager base path
- Uses AJAX for image selection and upload operations

### HTML Editor Control

The `html` type renders a textarea with TinyMCE (loaded once per page via the `Form/editor` element).

#### Basic Usage

```php
echo $this->Form->create($entity);

echo $this->Form->control('body', ['type' => 'html', 'label' => 'Content']);

echo $this->Form->end();
```

Configure a TinyMCE API key in `Admin.Editor.apiKey` (see [CONFIGURATION.md](CONFIGURATION.md)). The editor opens the File Manager `browseImages` action for image selection.

#### Options

Supports standard BootstrapUI textarea options (`label`, `class`, etc.). The helper adds the `editor` CSS class automatically; additional classes are merged.

### Date Range Control

The `dateRange` type renders two `type="date"` fields in a Bootstrap input group. By default they are named `{name}_from` and `{name}_to`. `control()` adds the label and form-group wrapper like other types; `dateRange()` returns only the input group.

#### Basic Usage

```php
echo $this->Form->control('period', ['type' => 'dateRange', 'label' => 'Period']);

// Custom field suffixes (period_start, period_end):
echo $this->Form->control('period', [
    'type' => 'dateRange',
    'suffixes' => ['start', 'end'],
]);

// Input group only (no label/container):
echo $this->Form->dateRange('period');
```

#### Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `label` | string | (via `control()`) | Label for the control when using `control()` |
| `suffixes` | array | `['from', 'to']` | Suffixes for the two fields. List `['start', 'end']` or `['from' => 'start', 'to' => 'end']` |
| `from` | array | `[]` | Extra options for the first date input |
| `to` | array | `[]` | Extra options for the second date input |

Other options (e.g. `class`, `required`) are applied to both date inputs.

## Loading Helpers

Helpers are automatically available when using the plugin's layouts. To use them in custom views:

```php
// In AppView.php or specific view
$this->loadHelper('Brammo/Admin.Button');
```
