<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;

/**
 * Applies config-driven GET filters to ORM queries and persists them in session.
 *
 * Query string is the source of truth. When the URL has no filter params but the
 * session has saved filters, redirects to the same action with those params.
 * Use `?clear_filters=1` to wipe the session and redirect to a clean URL.
 *
 * Sorting and paging remain Cake Paginator responsibilities (`sortableFields`, etc.).
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FilterComponent extends Component
{
    /**
     * Query keys managed by Paginator / routing that are never treated as filters.
     *
     * @var list<string>
     */
    protected array $paginationKeys = [
        'sort',
        'direction',
        'page',
        'limit',
        '_',
        'lang',
        'clear_filters',
    ];

    /**
     * Filter field configuration keyed by query parameter name.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $filters = [];

    /**
     * Active filter values for form defaults (query-param => value).
     *
     * @var array<string, mixed>
     */
    protected array $values = [];

    /**
     * Active filter values suitable for URL / Paginator `url` option.
     *
     * @var array<string, mixed>
     */
    protected array $url = [];

    /**
     * Last built ORM conditions.
     *
     * @var array<int|string, mixed>
     */
    protected array $conditions = [];

    /**
     * Default config.
     *
     * - `sessionKey`: Session storage key; null = auto `Filter.{Plugin}.{Controller}.{action}`
     * - `clearParam`: Query param that clears saved filters
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'sessionKey' => null,
        'clearParam' => 'clear_filters',
    ];

    /**
     * Define filterable query parameters.
     *
     * Each entry: query key => ['field' => 'Alias.column', 'type' => 'equal'|…, …]
     *
     * @param array<string, array<string, mixed>> $filters Filter map
     * @return $this
     */
    public function configure(array $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Read filters from the query (or restore from session), apply WHERE, persist session.
     *
     * May throw RedirectException when restoring or clearing filters.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to filter
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     * @throws \Cake\Http\Exception\RedirectException When redirecting to restore or clear filters
     */
    public function apply(SelectQuery $query): SelectQuery
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $queryParams = $request->getQueryParams();
        $clearParam = (string)$this->getConfig('clearParam');
        $sessionKey = $this->resolveSessionKey();

        if (!empty($queryParams[$clearParam])) {
            $request->getSession()->delete($sessionKey);
            $this->redirectWithQuery($this->paginationOnlyParams($queryParams));
        }

        if ($this->queryHasFilterKeys($queryParams)) {
            $this->processQueryParams($queryParams);
            $request->getSession()->write($sessionKey, $this->url);
        } else {
            $saved = $request->getSession()->read($sessionKey);
            if (is_array($saved) && $saved !== []) {
                $redirectParams = array_merge(
                    $this->paginationOnlyParams($queryParams),
                    $saved,
                );
                $this->redirectWithQuery($redirectParams);
            }
        }

        if ($this->conditions !== []) {
            $query->where($this->conditions);
        }

        return $query;
    }

    /**
     * Active filter values for form defaults.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * Active filter query params for Paginator / link generation.
     *
     * @return array<string, mixed>
     */
    public function getUrl(): array
    {
        return $this->url;
    }

    /**
     * ORM conditions built by the last apply() call.
     *
     * @return array<int|string, mixed>
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Whether the request query includes any configured filter key.
     *
     * @param array<string, mixed> $queryParams Request query params
     * @return bool
     */
    protected function queryHasFilterKeys(array $queryParams): bool
    {
        foreach ($this->filters as $key => $config) {
            $type = (string)($config['type'] ?? 'equal');
            if ($type === 'date_range') {
                [$fromKey, $toKey] = $this->dateRangeKeys($key, $config);
                if (array_key_exists($fromKey, $queryParams) || array_key_exists($toKey, $queryParams)) {
                    return true;
                }
                if (array_key_exists($key, $queryParams)) {
                    return true;
                }
                continue;
            }

            if (array_key_exists($key, $queryParams)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract values, build conditions and URL map from query params.
     *
     * @param array<string, mixed> $queryParams Request query params
     * @return void
     */
    protected function processQueryParams(array $queryParams): void
    {
        $this->values = [];
        $this->url = [];
        $this->conditions = [];

        foreach ($this->filters as $key => $config) {
            $type = (string)($config['type'] ?? 'equal');

            if ($type === 'date_range') {
                $this->processDateRange($key, $config, $queryParams);
                continue;
            }

            if (!array_key_exists($key, $queryParams)) {
                continue;
            }

            $value = $queryParams[$key];
            if (!$this->hasValue($value, $type)) {
                continue;
            }

            if (!$this->isValid($value, $config)) {
                continue;
            }

            $condition = $this->buildCondition($config, $value);
            if ($condition === null) {
                continue;
            }

            $this->values[$key] = $value;
            $this->url[$key] = $this->normalizeUrlValue($value, $type);
            $this->conditions = array_merge($this->conditions, $condition);
        }
    }

    /**
     * Handle date_range filter (from/to keys or single "Y-m-d - Y-m-d" value).
     *
     * @param string $key Config key
     * @param array<string, mixed> $config Filter config
     * @param array<string, mixed> $queryParams Request query params
     * @return void
     */
    protected function processDateRange(string $key, array $config, array $queryParams): void
    {
        [$fromKey, $toKey] = $this->dateRangeKeys($key, $config);
        $from = $queryParams[$fromKey] ?? null;
        $to = $queryParams[$toKey] ?? null;

        if (
            (!$this->hasValue($from, 'date') && !$this->hasValue($to, 'date'))
            && array_key_exists($key, $queryParams)
            && is_string($queryParams[$key])
            && preg_match('/^\d{4}-\d{2}-\d{2}\s+-\s+\d{4}-\d{2}-\d{2}$/', $queryParams[$key])
        ) {
            $parts = preg_split('/\s+-\s+/', $queryParams[$key], 2);
            if ($parts !== false && count($parts) === 2) {
                $from = $parts[0];
                $to = $parts[1];
            }
        }

        $fields = $this->dateRangeFields($config);
        if ($fields === null) {
            return;
        }

        [$startField, $endField] = $fields;

        if ($this->hasValue($from, 'date') && $this->isValidDate($from)) {
            $this->values[$fromKey] = $from;
            $this->url[$fromKey] = $from;
            $this->conditions[$startField . ' >='] = $from;
        }

        if ($this->hasValue($to, 'date') && $this->isValidDate($to)) {
            $this->values[$toKey] = $to;
            $this->url[$toKey] = $to;
            $this->conditions[$endField . ' <='] = $to;
        }
    }

    /**
     * Resolve from/to query parameter names for a date_range filter.
     *
     * @param string $key Config key
     * @param array<string, mixed> $config Filter config
     * @return array{0: string, 1: string}
     */
    protected function dateRangeKeys(string $key, array $config): array
    {
        $fromKey = isset($config['fromKey']) ? (string)$config['fromKey'] : $key . '_from';
        $toKey = isset($config['toKey']) ? (string)$config['toKey'] : $key . '_to';

        return [$fromKey, $toKey];
    }

    /**
     * Resolve start/end DB fields for a date_range filter.
     *
     * @param array<string, mixed> $config Filter config
     * @return array{0: string, 1: string}|null
     */
    protected function dateRangeFields(array $config): ?array
    {
        if (isset($config['fields']) && is_array($config['fields']) && count($config['fields']) >= 2) {
            return [(string)$config['fields'][0], (string)$config['fields'][1]];
        }

        if (!empty($config['field']) && is_string($config['field'])) {
            return [$config['field'], $config['field']];
        }

        return null;
    }

    /**
     * Build a single condition fragment for a filter value.
     *
     * @param array<string, mixed> $config Filter config
     * @param mixed $value Raw query value
     * @return array<int|string, mixed>|null
     */
    protected function buildCondition(array $config, mixed $value): ?array
    {
        $field = isset($config['field']) ? (string)$config['field'] : '';
        if ($field === '') {
            return null;
        }

        $type = (string)($config['type'] ?? 'equal');

        return match ($type) {
            'equal' => [$field => $value],
            'like' => $this->buildLikeCondition($field, (string)$value, $config, '%', '%'),
            'starts_with' => $this->buildLikeCondition($field, (string)$value, $config, '', '%'),
            'ends_with' => $this->buildLikeCondition($field, (string)$value, $config, '%', ''),
            'boolean' => [$field => $this->toBooleanInt($value)],
            'gte' => [$field . ' >=' => $value],
            'lte' => [$field . ' <=' => $value],
            'gt' => [$field . ' >' => $value],
            'lt' => [$field . ' <' => $value],
            'date' => [$field => $value],
            'in' => [$field . ' IN' => $this->toList($value)],
            'not_in' => [$field . ' NOT IN' => $this->toList($value)],
            default => null,
        };
    }

    /**
     * Build LIKE / multi-word LIKE conditions.
     *
     * @param string $field DB field
     * @param string $value Search value
     * @param array<string, mixed> $config Filter config
     * @param string $defaultBefore Default prefix wildcard
     * @param string $defaultAfter Default suffix wildcard
     * @return array<int|string, mixed>
     */
    protected function buildLikeCondition(
        string $field,
        string $value,
        array $config,
        string $defaultBefore,
        string $defaultAfter,
    ): array {
        $before = array_key_exists('before', $config) ? (string)$config['before'] : $defaultBefore;
        $after = array_key_exists('after', $config) ? (string)$config['after'] : $defaultAfter;
        $explode = $config['explode'] ?? null;

        if ($explode === 'OR' || $explode === 'AND') {
            $parts = preg_split('/\s+/', trim($value)) ?: [];
            $parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));
            if ($parts === []) {
                return [];
            }

            $group = [];
            foreach ($parts as $part) {
                $group[] = [$field . ' LIKE' => $before . $part . $after];
            }

            return [$explode => $group];
        }

        return [$field . ' LIKE' => $before . $value . $after];
    }

    /**
     * Whether a raw value should be applied as a filter.
     *
     * @param mixed $value Query value
     * @param string $type Filter type
     * @return bool
     */
    protected function hasValue(mixed $value, string $type): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_array($value)) {
            return $value !== [];
        }

        if ($type === 'boolean') {
            return $value !== '';
        }

        return !is_string($value) || $value !== '';
    }

    /**
     * Validate a value against optional config rules.
     *
     * @param mixed $value Query value
     * @param array<string, mixed> $config Filter config
     * @return bool
     */
    protected function isValid(mixed $value, array $config): bool
    {
        $validate = $config['validate'] ?? null;
        $type = (string)($config['type'] ?? 'equal');

        if ($type === 'date' || $validate === 'date') {
            return is_string($value) && $this->isValidDate($value);
        }

        if ($validate === 'numeric') {
            return is_numeric($value);
        }

        return true;
    }

    /**
     * @param mixed $value Candidate date string
     * @return bool
     */
    protected function isValidDate(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        return strtotime($value) !== false;
    }

    /**
     * Normalize list values for IN / NOT IN and URL serialization.
     *
     * @param mixed $value Array or comma-separated string
     * @return list<mixed>
     */
    protected function toList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value)) {
            return array_values(array_filter(
                array_map('trim', explode(',', $value)),
                static fn(string $item): bool => $item !== '',
            ));
        }

        return [$value];
    }

    /**
     * Normalize a value for the URL map.
     *
     * @param mixed $value Filter value
     * @param string $type Filter type
     * @return mixed
     */
    protected function normalizeUrlValue(mixed $value, string $type): mixed
    {
        if (in_array($type, ['in', 'not_in'], true)) {
            return is_array($value) ? implode(',', $value) : $value;
        }

        if ($type === 'boolean') {
            return $this->toBooleanInt($value);
        }

        return $value;
    }

    /**
     * Cast a filter value to 0 or 1.
     *
     * @param mixed $value Raw value
     * @return int
     */
    protected function toBooleanInt(mixed $value): int
    {
        if ($value === true || $value === 1 || $value === '1') {
            return 1;
        }

        return 0;
    }

    /**
     * Strip non-pagination params for clear / restore redirects.
     *
     * @param array<string, mixed> $queryParams Full query params
     * @return array<string, mixed>
     */
    protected function paginationOnlyParams(array $queryParams): array
    {
        $clearParam = (string)$this->getConfig('clearParam');
        $allowed = array_diff($this->paginationKeys, [$clearParam]);
        $result = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $queryParams)) {
                $result[$key] = $queryParams[$key];
            }
        }

        return $result;
    }

    /**
     * Resolve session storage key.
     *
     * @return string
     */
    protected function resolveSessionKey(): string
    {
        $configured = $this->getConfig('sessionKey');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $controller = $this->getController();
        $parts = ['Filter'];
        $plugin = $controller->getPlugin();
        if ($plugin) {
            $parts[] = str_replace('/', '.', $plugin);
        }
        $parts[] = $controller->getName();
        $parts[] = (string)$controller->getRequest()->getParam('action');

        return implode('.', $parts);
    }

    /**
     * Redirect to the current action with the given query string.
     *
     * @param array<string, mixed> $query Redirect query params
     * @return never
     * @throws \Cake\Http\Exception\RedirectException
     */
    protected function redirectWithQuery(array $query): never
    {
        $controller = $this->getController();
        $request = $controller->getRequest();

        $url = [
            'plugin' => $controller->getPlugin(),
            'prefix' => $request->getParam('prefix'),
            'controller' => $controller->getName(),
            'action' => $request->getParam('action'),
            '?' => $query,
        ];

        $pass = $request->getParam('pass');
        if (is_array($pass)) {
            foreach ($pass as $i => $value) {
                $url[$i] = $value;
            }
        }

        throw new RedirectException(Router::url($url));
    }
}
