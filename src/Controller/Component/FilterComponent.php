<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;

/**
 * Applies config-driven GET filters to ORM queries and persists filters plus
 * sort/page in session.
 *
 * Query string is the source of truth. When the URL is missing saved filters or
 * paging params, redirects to the same action with those params restored.
 * Use `?clear_filters=1` to wipe filter session, reset page to 1, and redirect.
 *
 * Cake Paginator still performs paging/sorting; this component only persists and
 * restores query params (`sortableFields` on `$paginate` remain required).
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FilterComponent extends Component
{
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
     * Active filter + paging query params for URL / Paginator `url` option.
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
     * - `sessionKey`: Filter session key; null = `Filter.{Plugin}.{Controller}.{action}`
     * - `pagingSessionKey`: Paging session key; null = `Paging.{Plugin}.{Controller}.{action}`
     * - `clearParam`: Query param that clears saved filters
     * - `sortableFields`: Optional whitelist for persisted sort
     * - `defaultSort` / `defaultDirection`: Used when query/session have no sort
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'sessionKey' => null,
        'pagingSessionKey' => null,
        'clearParam' => 'clear_filters',
        'sortableFields' => [],
        'defaultSort' => null,
        'defaultDirection' => 'asc',
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
     * Read filters/paging from the query (or restore from session), apply WHERE, persist session.
     *
     * May throw RedirectException when restoring or clearing filters/paging.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to filter
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     * @throws \Cake\Http\Exception\RedirectException When redirecting to restore or clear
     */
    public function apply(SelectQuery $query): SelectQuery
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $queryParams = $request->getQueryParams();
        $clearParam = (string)$this->getConfig('clearParam');
        $session = $request->getSession();
        $sessionKey = $this->resolveSessionKey();
        $pagingSessionKey = $this->resolvePagingSessionKey();

        if (!empty($queryParams[$clearParam])) {
            $session->delete($sessionKey);
            $redirect = $this->clearFiltersRedirectParams($queryParams, $pagingSessionKey);
            $this->redirectWithQuery($redirect);
        }

        $filterKeysPresent = $this->queryHasFilterKeys($queryParams);
        $filterUrl = [];
        $needsRedirect = false;

        if ($filterKeysPresent) {
            $this->processQueryParams($queryParams);
            $filterUrl = $this->url;
            $session->write($sessionKey, $filterUrl);
        } else {
            $this->values = [];
            $this->conditions = [];
            $this->url = [];
            $saved = $session->read($sessionKey);
            if (is_array($saved) && $saved !== []) {
                $filterUrl = $saved;
                $needsRedirect = true;
            }
        }

        [$pagingUrl, $pagingSession, $pagingNeedsRedirect] = $this->resolvePaging(
            $queryParams,
            $filterKeysPresent,
        );
        if ($pagingNeedsRedirect) {
            $needsRedirect = true;
        }

        $targetUrl = array_merge($filterUrl, $pagingUrl);

        if ($needsRedirect) {
            $this->redirectWithQuery($targetUrl);
        }

        $session->write($pagingSessionKey, $pagingSession);
        $this->url = $targetUrl;

        if ($this->conditions !== []) {
            $query->where($this->conditions);
        }

        return $query;
    }

    /**
     * Active filter values for form defaults (no paging keys).
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * Active filter + paging query params for Paginator / link generation.
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
     * Build clear-filters redirect params and reset paging page to 1.
     *
     * @param array<string, mixed> $queryParams Request query params
     * @param string $pagingSessionKey Paging session key
     * @return array<string, mixed>
     */
    protected function clearFiltersRedirectParams(array $queryParams, string $pagingSessionKey): array
    {
        $session = $this->getController()->getRequest()->getSession();
        $saved = $session->read($pagingSessionKey);
        if (!is_array($saved)) {
            $saved = [];
        }

        [$sort, $direction] = $this->resolveSortDirection($queryParams, $saved);
        $pagingSession = ['page' => 1];
        $redirect = [];

        if ($sort !== null) {
            $pagingSession['sort'] = $sort;
            $pagingSession['direction'] = $direction;
            $redirect['sort'] = $sort;
            $redirect['direction'] = $direction;
        }

        $session->write($pagingSessionKey, $pagingSession);

        return $redirect;
    }

    /**
     * Resolve sort/direction/page for URL and session.
     *
     * @param array<string, mixed> $queryParams Request query params
     * @param bool $filterKeysPresent Whether configured filter keys are in the query
     * @return array{
     *     0: array<string, mixed>,
     *     1: array{sort?: string, direction?: string, page: int},
     *     2: bool
     * }
     */
    protected function resolvePaging(array $queryParams, bool $filterKeysPresent): array
    {
        $session = $this->getController()->getRequest()->getSession();
        $saved = $session->read($this->resolvePagingSessionKey());
        if (!is_array($saved)) {
            $saved = [];
        }

        $needsRedirect = false;
        $pagingUrl = [];
        $pagingSession = ['page' => 1];

        [$sort, $direction, $sortFromQuery, $sortNeedsRedirect] = $this->resolveSortDirectionDetailed(
            $queryParams,
            $saved,
        );
        if ($sortNeedsRedirect) {
            $needsRedirect = true;
        }
        if ($sort !== null) {
            $pagingSession['sort'] = $sort;
            $pagingSession['direction'] = $direction;
            $pagingUrl['sort'] = $sort;
            $pagingUrl['direction'] = $direction;
            if (!$sortFromQuery) {
                $needsRedirect = true;
            }
        }

        if (array_key_exists('page', $queryParams) && is_numeric($queryParams['page'])) {
            $page = max(1, (int)$queryParams['page']);
            $pagingSession['page'] = $page;
            if ($page > 1) {
                $pagingUrl['page'] = $page;
            }
        } elseif ($filterKeysPresent) {
            $pagingSession['page'] = 1;
        } elseif (!empty($saved['page']) && (int)$saved['page'] > 1) {
            $page = (int)$saved['page'];
            $pagingSession['page'] = $page;
            $pagingUrl['page'] = $page;
            $needsRedirect = true;
        } else {
            $pagingSession['page'] = 1;
        }

        return [$pagingUrl, $pagingSession, $needsRedirect];
    }

    /**
     * Resolve sort and direction from query, session, or defaults.
     *
     * @param array<string, mixed> $queryParams Request query
     * @param array<string, mixed> $saved Saved paging session
     * @return array{0: string|null, 1: string}
     */
    protected function resolveSortDirection(array $queryParams, array $saved): array
    {
        $detailed = $this->resolveSortDirectionDetailed($queryParams, $saved);

        return [$detailed[0], $detailed[1]];
    }

    /**
     * Resolve sort/direction with flags for query source and redirect need.
     *
     * @param array<string, mixed> $queryParams Request query
     * @param array<string, mixed> $saved Saved paging session
     * @return array{0: string|null, 1: string, 2: bool, 3: bool}
     */
    protected function resolveSortDirectionDetailed(array $queryParams, array $saved): array
    {
        $sortableFields = $this->getConfig('sortableFields');
        if (!is_array($sortableFields)) {
            $sortableFields = [];
        }
        /** @var list<string> $sortableFields */

        $defaultSort = $this->getConfig('defaultSort');
        $defaultDirection = $this->normalizeDirection($this->getConfig('defaultDirection'));

        if (!empty($queryParams['sort'])) {
            $sort = (string)$queryParams['sort'];
            $direction = array_key_exists('direction', $queryParams)
                ? $this->normalizeDirection($queryParams['direction'])
                : 'asc';

            if ($sortableFields !== [] && !in_array($sort, $sortableFields, true)) {
                if (is_string($defaultSort) && $defaultSort !== '') {
                    return [$defaultSort, $defaultDirection, false, true];
                }

                // Invalid sort with no default: do not persist; leave request as-is.
                return [null, 'asc', true, false];
            }

            return [$sort, $direction, true, false];
        }

        if (!empty($saved['sort']) && is_string($saved['sort'])) {
            $sort = $saved['sort'];
            if ($sortableFields !== [] && !in_array($sort, $sortableFields, true)) {
                if (is_string($defaultSort) && $defaultSort !== '') {
                    return [$defaultSort, $defaultDirection, false, true];
                }

                return [null, 'asc', false, false];
            }

            return [
                $sort,
                $this->normalizeDirection($saved['direction'] ?? 'asc'),
                false,
                true,
            ];
        }

        if (is_string($defaultSort) && $defaultSort !== '') {
            return [$defaultSort, $defaultDirection, false, true];
        }

        return [null, 'asc', false, false];
    }

    /**
     * @param mixed $direction Raw direction
     * @return string
     */
    protected function normalizeDirection(mixed $direction): string
    {
        $direction = is_string($direction) ? strtolower($direction) : 'asc';

        return in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
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
     * Extract values, build conditions and filter URL map from query params.
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
     * Resolve filter session storage key.
     *
     * @return string
     */
    protected function resolveSessionKey(): string
    {
        $configured = $this->getConfig('sessionKey');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return $this->buildScopedSessionKey('Filter');
    }

    /**
     * Resolve paging session storage key.
     *
     * @return string
     */
    protected function resolvePagingSessionKey(): string
    {
        $configured = $this->getConfig('pagingSessionKey');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return $this->buildScopedSessionKey('Paging');
    }

    /**
     * Build `Prefix.{Plugin}.{Controller}.{action}` session key.
     *
     * @param string $prefix Filter or Paging
     * @return string
     */
    protected function buildScopedSessionKey(string $prefix): string
    {
        $controller = $this->getController();
        $parts = [$prefix];
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
