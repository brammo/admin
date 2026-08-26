# FilterComponent

Config-driven GET filters for admin index actions. Applies WHERE conditions to an ORM query used with Cake’s Paginator, persists active filters in the session, and restores them by redirecting to a filter URL when the list is opened without query params.

Load the component only on controllers that need list filters (not on `AppController` by default).

## Basic usage

```php
// In controller initialize() or the action
$this->loadComponent('Brammo/Admin.Filter');
// Optional: override session key
// $this->loadComponent('Brammo/Admin.Filter', [
//     'sessionKey' => 'Filter.Events.index',
// ]);

public function index(): void
{
    $query = $this->Events
        ->find()
        ->contain(['Countries', 'Festivals']);

    $this->Filter->configure([
        'title' => ['field' => 'Events.title', 'type' => 'like'],
        'town' => ['field' => 'Events.town', 'type' => 'like'],
        'date_from' => ['field' => 'Events.start_date', 'type' => 'gte', 'validate' => 'date'],
        'date_to' => ['field' => 'Events.end_date', 'type' => 'lte', 'validate' => 'date'],
        'country_id' => ['field' => 'Events.country_id', 'type' => 'equal'],
        'active' => ['field' => 'Events.active', 'type' => 'boolean'],
    ]);

    $query = $this->Filter->apply($query); // may redirect when restoring/clearing filters

    $this->set('events', $this->paginate($query));
    $this->set('filters', $this->Filter->values()); // form defaults
}
```

Keep `$paginate` (`limit`, `order`, `sortableFields`) on the controller. Filter does not replace Paginator sorting.

## Filter types


| Type                        | Behavior                                                                                         |
| --------------------------- | ------------------------------------------------------------------------------------------------ |
| `equal`                     | `field = value`                                                                                  |
| `like`                      | `LIKE %value%` (optional `before` / `after`; optional `explode` => `OR` or `AND` for multi-word) |
| `starts_with`               | `LIKE value%`                                                                                    |
| `ends_with`                 | `LIKE %value`                                                                                    |
| `boolean`                   | Cast to `0`/`1`; empty string = no filter (for an “All” option)                                  |
| `gte` / `lte` / `gt` / `lt` | Comparison operators; optional `validate` => `date` or `numeric`                                 |
| `date`                      | Equality; requires a parseable date                                                              |
| `date_range`                | From/to bounds (see below)                                                                       |
| `in` / `not_in`             | Array or comma-separated list                                                                    |


Only configured query keys are read. Pagination keys (`sort`, `direction`, `page`, `limit`, `_`, `lang`, `clear_filters`) are never treated as filters.

### `date_range`

```php
// One field, default query keys period_from / period_to
'period' => [
    'type' => 'date_range',
    'field' => 'Events.start_date',
],

// Two fields with custom query keys (Events-style date_from / date_to)
'range' => [
    'type' => 'date_range',
    'fields' => ['Events.start_date', 'Events.end_date'],
    'fromKey' => 'date_from',
    'toKey' => 'date_to',
],
```

A single `Y-m-d - Y-m-d` value on the config key is also accepted.

Separate `gte` / `lte` keys (as in the Events example) are usually clearer than `date_range`.

## Session and redirects

1. **Query string is the source of truth** — bookmarkable and shareable with Paginator links.
2. Request has **no** configured filter keys, but session has saved filters → **302** to the same action with those query params (pagination params like `sort` are kept).
3. Request includes configured filter keys (even empty) → build conditions and **write** session (empty values clear the saved map).
4. `?clear_filters=1` → delete session and redirect without filter params (pagination params kept).

Default session key: `Filter.{Plugin}.{Controller}.{action}` (dots; plugin `/` becomes `.`). Override with `sessionKey` in component config.

## Cooperation with Paginator

Pass active filters into paginator URLs so page/sort links keep the current filter set:

```php
$this->set('events', $this->paginate($query, [
    'url' => $this->Filter->getUrl(),
]));
```

Or set URL options on the view Paginator helper so [templates/element/pagination.php](../templates/element/pagination.php) links retain filters. BootstrapUI’s Paginator can also merge request query params depending on configuration — prefer an explicit `url` / helper option so behavior is obvious.

Useful accessors after `apply()`:

- `values()` — form field defaults
- `getUrl()` — query map for links / paginator
- `getConditions()` — ORM condition array



## Filter forms

Use **GET** forms (`method="get"`). Example:

```php
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query']]) ?>
<?= $this->Form->control('title', ['value' => $filters['title'] ?? '']) ?>
<?= $this->Form->control('active', [
    'type' => 'select',
    'empty' => __d('brammo/admin', 'All'),
    'options' => [1 => __d('brammo/admin', 'Yes'), 0 => __d('brammo/admin', 'No')],
    'value' => $filters['active'] ?? '',
]) ?>
<?= $this->Button->filter() ?>
<?= $this->Form->end() ?>

<?= $this->Html->link(
    __d('brammo/admin', 'Clear'),
    ['action' => 'index', '?' => ['clear_filters' => 1]],
) ?>
```

There is no auto-generated filter form element in v1.

## Out of scope

- Session persistence of sort/page (Paginator owns those)
- File Manager’s non-ORM filename filter
- Auto-generated filter UI

