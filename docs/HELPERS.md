# View Helpers

The Brammo Admin plugin provides several view helpers to simplify building Bootstrap-based admin interfaces. All helpers use CakePHP's `StringTemplateTrait` for flexible template customization.

## Table of Contents

- [ButtonHelper](#buttonhelper)

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

## Loading Helpers

Helpers are automatically available when using the plugin's layouts. To use them in custom views:

```php
// In AppView.php or specific view
$this->loadHelper('Brammo/Admin.Button');
```
