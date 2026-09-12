# SortableComponent

Drag-and-drop reorder for admin lists and attachment grids. The plugin provides:

- SortableJS (loaded on demand) plus `webroot/js/sortable.js`
- `Brammo/Admin.sortable` and `Brammo/Admin.Sortable/handle` elements
- `SortableComponent::reorder()` which persists order via [ADmad Sequence](https://github.com/ADmad/cakephp-sequence)

Load the component only on controllers that need reorder (not on `AppController` by default). Domain tables stay in the host app.

## Requirements

Each sortable table must:

1. Have an integer order column (convention: `sort_order`)
2. Attach Sequence with that field (and a `scope` when rows belong to a parent):

```php
$this->addBehavior('ADmad/Sequence.Sequence', [
    'sequenceField' => 'sort_order',
    'scope' => ['parent_id'], // optional
]);
```

`admad/cakephp-sequence` is required by `brammo/admin`.

## Controller

```php
public function initialize(): void
{
    parent::initialize();
    $this->loadComponent('Brammo/Admin.Sortable');
}

public function sort(): \Cake\Http\Response
{
    return $this->Sortable->reorder(
        $this->Clubs,
        $this->request->getData('ids'),
    );
}

public function sortImages(int $articleId): \Cake\Http\Response
{
    $this->NewsArticles->get($articleId);

    return $this->Sortable->reorder(
        $this->NewsArticles->Images,
        $this->request->getData('ids'),
        ['news_article_id' => $articleId],
    );
}
```

`reorder()`:

- Accepts a `Table` or association
- Requires POST; otherwise throws `BadRequestException`
- Coerces `ids` to positive integers
- When `$ownerScope` is given, keeps only IDs that match those conditions (`null` values become `IS NULL`)
- Calls `SequenceBehavior::setOrder($ids)`
- Returns JSON `{ "ok": true }` (also when the list is empty)

The host action can be named `sort`, `sortFiles`, etc.; the component method is `reorder`.

## Template

Mark a container and its children, then include the loader:

```php
<?php
use Cake\Routing\Router;

$csrfToken = $this->getRequest()->getAttribute('csrfToken');
?>
<tbody
    data-sortable
    data-url="<?= h(Router::url(['action' => 'sort'])) ?>"
    data-csrf-token="<?= h((string)$csrfToken) ?>"
>
    <?php foreach ($clubs as $club): ?>
        <tr data-id="<?= (int)$club->id ?>">
            <td class="align-middle">
                <?= $this->element('Brammo/Admin.Sortable/handle') ?>
            </td>
            <td><?= h($club->name) ?></td>
        </tr>
    <?php endforeach ?>
</tbody>
<?= $this->element('Brammo/Admin.sortable') ?>
```

### Data attributes

| Attribute | Where | Required | Description |
|-----------|--------|----------|-------------|
| `data-sortable` | Container | Yes | Initializes SortableJS |
| `data-url` | Container | Yes | POST endpoint |
| `data-csrf-token` | Container | Yes | CSRF token |
| `data-id` | Direct children | Yes | Primary key posted as `ids[]` |
| `data-sortable-handle` | Handle inside each item | Yes (default) | Drag starts here only |
| `data-sortable-handle-selector` | Container | No | Override handle CSS selector |
| `data-sortable-draggable` | Container | No | SortableJS `draggable` selector (nested groups) |

Links and buttons are filtered so clicks do not start a drag. Nested trees (parent groups + child rows) use `data-sortable-draggable` and `data-sortable-handle-selector` on the outer container, plus a second `data-sortable` on each child group — see Trapezitca Pages index.

On drop, the script POSTs `ids[]` with `X-CSRF-Token` and `X-Requested-With: XMLHttpRequest`.

## Configuration

Optional CDN URL for SortableJS (`config/admin.php`):

```php
'Admin' => [
    'Sortable' => [
        'script' => 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js',
    ],
],
```

The plugin script `Brammo/Admin.sortable` is always queued after that URL. Scripts are not loaded globally; include `Brammo/Admin.sortable` on pages that have a sortable grid.

## Out of scope

- Attachment bulk upload (separate feature)
- Host add/edit/delete actions and `sort_order` migrations
- Automatic index table markup (columns besides the grip handle)
