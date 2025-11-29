# View Helpers

The Brammo Admin plugin provides several view helpers to simplify building Bootstrap-based admin interfaces. All helpers use CakePHP's `StringTemplateTrait` for flexible template customization.

## Table of Contents

- [ButtonHelper](#buttonhelper)
- [CardHelper](#cardhelper)
- [TableHelper](#tablehelper)
- [DescriptionHelper](#descriptionhelper)
- [Template Customization](#template-customization)

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

// Edit button (blue with pencil icon)
echo $this->Button->edit(['action' => 'edit', $id]);

// Delete button (red with trash icon, includes confirmation)
echo $this->Button->delete(['action' => 'delete', $id]);
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

---

## CardHelper

Create Bootstrap card components with optional header and footer sections.

### Basic Usage

```php
// Simple card with body content
echo $this->Card->render('This is the card body content.');

// Card with header
echo $this->Card->render('Card body content', [
    'header' => 'Card Title',
]);

// Card with header and footer
echo $this->Card->render('Card body content', [
    'header' => 'Card Title',
    'footer' => 'Card footer text',
]);
```

### Templates

The CardHelper uses the following default templates:

| Template | Default HTML |
|----------|--------------|
| `card` | `<div{{attrs}}>{{content}}</div>` |
| `header` | `<div{{attrs}}>{{content}}</div>` |
| `body` | `<div{{attrs}}>{{content}}</div>` |
| `footer` | `<div{{attrs}}>{{content}}</div>` |

### Default Classes

| Element | Default Class |
|---------|---------------|
| card | `card` |
| header | `card-header` |
| body | `card-body` |
| footer | `card-footer` |

### Options

| Option | Type | Description |
|--------|------|-------------|
| `header` | string\|null | Header content |
| `footer` | string\|null | Footer content |
| `headerAttrs` | array | HTML attributes for the header element |
| `bodyAttrs` | array | HTML attributes for the body element |
| `footerAttrs` | array | HTML attributes for the footer element |
| Other options | mixed | Applied as HTML attributes to the card element |

### Examples

```php
// Card with custom classes
echo $this->Card->render('Content', [
    'header' => 'Title',
    'class' => 'card shadow-sm',
]);

// Card with custom body padding
echo $this->Card->render('Content', [
    'bodyAttrs' => ['class' => 'card-body p-4'],
]);

// Card with styled header
echo $this->Card->render('Content', [
    'header' => 'Important Notice',
    'headerAttrs' => ['class' => 'card-header bg-warning text-dark'],
]);

// Card with HTML content
$header = $this->Html->tag('h5', 'User Details', ['class' => 'mb-0']);
$body = $this->element('user_info', ['user' => $user]);
echo $this->Card->render($body, [
    'header' => $header,
    'class' => 'card mb-4',
]);
```

---

## TableHelper

Build responsive HTML tables with headers and data rows.

### Basic Usage

```php
// Define header
$this->Table->header(['ID', 'Name', 'Email', 'Actions']);

// Add rows
$this->Table->row([1, 'John Doe', 'john@example.com', $actions]);
$this->Table->row([2, 'Jane Smith', 'jane@example.com', $actions]);

// Render table
echo $this->Table->render();
```

### Templates

The TableHelper uses the following default templates:

| Template | Default HTML |
|----------|--------------|
| `wrapper` | `<div{{attrs}}>{{content}}</div>` |
| `table` | `<table{{attrs}}>{{content}}</table>` |
| `header` | `<thead{{attrs}}>{{content}}</thead>` |
| `body` | `<tbody{{attrs}}>{{content}}</tbody>` |
| `row` | `<tr{{attrs}}>{{content}}</tr>` |
| `headerCell` | `<th{{attrs}}>{{content}}</th>` |
| `bodyCell` | `<td{{attrs}}>{{content}}</td>` |

### Default Classes

| Element | Default Class |
|---------|---------------|
| wrapper | `table-responsive` |
| table | `table` |

### Header with Attributes

You can specify attributes for individual header cells:

```php
// Using array syntax for cell attributes
$this->Table->header([
    'ID',
    ['Name' => ['class' => 'w-25']],
    ['Email' => ['class' => 'w-50']],
    ['Actions' => ['class' => 'text-end']],
]);

// Alternative syntax
$this->Table->header([
    'ID',
    ['Name', ['class' => 'w-25']],
    ['Email', ['class' => 'w-50']],
    ['Actions', ['class' => 'text-end']],
]);
```

### Row Cells with Attributes

```php
$this->Table->row([
    $user->id,
    $user->name,
    $user->email,
    [$actions, ['class' => 'text-end']],
]);
```

### Render Options

```php
echo $this->Table->render([
    'wrapper' => ['class' => 'table-responsive-lg'],
    'table' => ['class' => 'table table-striped table-hover'],
]);
```

### Complete Example

```php
// Setup header
$this->Table->header([
    '#',
    'Name',
    'Email',
    'Status',
    ['Actions' => ['class' => 'text-end']],
]);

// Add data rows
foreach ($users as $user) {
    $status = $user->active 
        ? $this->Html->badge('Active', ['class' => 'bg-success'])
        : $this->Html->badge('Inactive', ['class' => 'bg-secondary']);
    
    $actions = $this->Button->edit(['action' => 'edit', $user->id], ['size' => 'sm']) . ' ' .
               $this->Button->delete(['action' => 'delete', $user->id], ['size' => 'sm']);
    
    $this->Table->row([
        $user->id,
        $user->name,
        $user->email,
        $status,
        [$actions, ['class' => 'text-end']],
    ]);
}

// Render with custom table classes
echo $this->Table->render([
    'table' => ['class' => 'table table-striped table-hover'],
]);
```

---

## DescriptionHelper

Generate HTML description lists (`<dl>`) for displaying key-value pairs.

### Basic Usage

```php
echo $this->Description
    ->add('Name', 'John Doe')
    ->add('Email', 'john@example.com')
    ->add('Phone', '+1 234 567 890')
    ->render();
```

### Templates

The DescriptionHelper uses the following default templates:

| Template | Default HTML |
|----------|--------------|
| `list` | `<dl{{attrs}}>{{content}}</dl>` |
| `term` | `<dt{{attrs}}>{{content}}</dt>` |
| `definition` | `<dd{{attrs}}>{{content}}</dd>` |

### Options

```php
echo $this->Description
    ->add('Label', 'Value')
    ->render([
        'list' => ['class' => 'row'],
    ]);
```

### Complete Example

```php
// Display user details
echo $this->Description
    ->add(__('Username'), h($user->username))
    ->add(__('Email'), h($user->email))
    ->add(__('Role'), h($user->role->name))
    ->add(__('Created'), $user->created->nice())
    ->add(__('Modified'), $user->modified->nice())
    ->render([
        'list' => ['class' => 'dl-horizontal'],
    ]);
```

---

## Template Customization

All helpers use CakePHP's `StringTemplateTrait`, allowing you to customize templates at runtime or through configuration.

### Runtime Customization

```php
// Customize CardHelper templates
$this->Card->setTemplates([
    'card' => '<article{{attrs}}>{{content}}</article>',
    'header' => '<header{{attrs}}>{{content}}</header>',
    'body' => '<section{{attrs}}>{{content}}</section>',
    'footer' => '<footer{{attrs}}>{{content}}</footer>',
]);

// Customize TableHelper templates
$this->Table->setTemplates([
    'wrapper' => '<div{{attrs}}>{{content}}</div>',
    'table' => '<table{{attrs}}>{{content}}</table>',
]);
```

### Configuration-based Customization

In your `AppView.php`:

```php
public function initialize(): void
{
    parent::initialize();
    
    $this->loadHelper('Brammo/Admin.Card', [
        'templates' => [
            'card' => '<div{{attrs}}>{{content}}</div>',
        ],
    ]);
}
```

### Template Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{{attrs}}` | HTML attributes formatted as string |
| `{{content}}` | Inner content |

---

## Loading Helpers

Helpers are automatically available when using the plugin's layouts. To use them in custom views:

```php
// In AppView.php or specific view
$this->loadHelper('Brammo/Admin.Button');
$this->loadHelper('Brammo/Admin.Card');
$this->loadHelper('Brammo/Admin.Table');
$this->loadHelper('Brammo/Admin.Description');
```

Or load all at once:

```php
$this->loadHelper('Brammo/Admin.Button');
$this->loadHelper('Brammo/Admin.Card');
$this->loadHelper('Brammo/Admin.Table');
$this->loadHelper('Brammo/Admin.Description');
```
