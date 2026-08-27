# FilterComponent

Config-driven GET filters for admin index actions. Applies WHERE conditions to an ORM query used with Cake’s Paginator, persists **filters** and **sort/page** in the session, and restores them by redirecting to a query URL when the list is opened without those params.

Load the component only on controllers that need list filters (not on `AppController` by default).

## Basic usage

```php
// In controller initialize() or the action
$this->loadComponent('Brammo/Admin.Filter', [
    // Optional paging persistence config:
    'sortableFields' => [
        'Events.id',
        'Events.title',
        'Events.start_date',
    ],
    'defaultSort' => 'Events.start_date',
    'defaultDirection' => 'desc',
    // 'sessionKey' => 'Filter.Events.index',
    // 'pagingSessionKey' => 'Paging.Events.index',
]);

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
        'type' => ['field' => 'Festivals.type', 'type' => 'equal'],
        'rank' => ['field' => 'Festivals.rank', 'type' => 'equal'],
        'active' => ['field' => 'Events.active', 'type' => 'boolean'],
    ]);

    $query = $this->Filter->apply($query); // may redirect when restoring/clearing

    $this->set('events', $this->paginate($query, [
        'url' => $this->Filter->getUrl(),
    ]));
    $this->set('filters', $this->Filter->values()); // form defaults (filters only)
}
```

Keep `$paginate` (`limit`, `order`, `sortableFields`) on the controller for Cake Paginator. Duplicate `sortableFields` in the Filter component config when you want session restore validated the same way (no automatic sync).

## Filter types

| Type | Behavior |
|------|----------|
| `equal` | `field = value` |
| `like` | `LIKE %value%` (optional `before` / `after`; optional `explode` => `OR` or `AND` for multi-word) |
| `starts_with` | `LIKE value%` |
| `ends_with` | `LIKE %value` |
| `boolean` | Cast to `0`/`1`; empty string = no filter (for an “All” option) |
| `gte` / `lte` / `gt` / `lt` | Comparison operators; optional `validate` => `date` or `numeric` |
| `date` | Equality; requires a parseable date |
| `date_range` | From/to bounds (see below) |
| `in` / `not_in` | Array or comma-separated list |

Only configured query keys are read as filters. Pagination keys (`sort`, `direction`, `page`, `limit`, `_`, `lang`, `clear_filters`) are never treated as filters.

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

**Filters** — session key `Filter.{Plugin}.{Controller}.{action}` (override with `sessionKey`).

**Paging** — session key `Paging.{Plugin}.{Controller}.{action}` storing `{sort, direction, page}` (override with `pagingSessionKey`).

1. **Query string is the source of truth** — bookmarkable and shareable with Paginator links.
2. Missing filter keys but filter session non-empty → include saved filters in the redirect target.
3. Missing `sort` (and optional `page` > 1) but paging session / `defaultSort` set → include them in the same redirect.
4. Request includes configured filter keys (even empty) → build conditions and **write** filter session; **reset page to 1** when `page` is absent (filter submit should not keep an old page).
5. `?clear_filters=1` → delete filter session, set paging `page` to 1, redirect with saved/current `sort`/`direction` only (no filters, no old page).

`sort` / `direction` in the query are validated when `sortableFields` is non-empty; invalid values fall back to `defaultSort` / `defaultDirection` via redirect when defaults are configured.

## Cooperation with Paginator

`getUrl()` returns **filters + paging** (`sort`, `direction`, and `page` when > 1):

```php
$this->set('events', $this->paginate($query, [
    'url' => $this->Filter->getUrl(),
]));
```

Or set URL options on the view Paginator helper so [templates/element/pagination.php](../templates/element/pagination.php) links retain state.

Useful accessors after `apply()`:

- `values()` — form field defaults (filters only)
- `getUrl()` — query map for links / paginator (filters + sort/direction/page)
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

- File Manager’s non-ORM filename filter
- Auto-generated filter UI
- Persisting `limit` (Paginator default / request only)
