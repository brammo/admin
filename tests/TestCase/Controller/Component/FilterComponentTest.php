<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Controller\Component;

use Brammo\Admin\Controller\Component\FilterComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\RedirectException;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\ORM\Table;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * FilterComponent Test Case
 */
class FilterComponentTest extends TestCase
{
    /**
     * @var \Cake\Controller\Controller
     */
    protected Controller $controller;

    /**
     * @var \Brammo\Admin\Controller\Component\FilterComponent
     */
    protected FilterComponent $Filter;

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $table;

    /**
     * @var \Cake\Http\Session
     */
    protected Session $session;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Router::reload();
        $routes = Router::createRouteBuilder('/');
        $routes->setRouteClass(DashedRoute::class);
        $routes->connect('/{controller}/{action}/*');

        $this->session = new Session(['_name' => 'php']);

        $schema = new TableSchema('events');
        $schema->addColumn('id', ['type' => 'integer'])
            ->addColumn('title', ['type' => 'string'])
            ->addColumn('town', ['type' => 'string'])
            ->addColumn('start_date', ['type' => 'date'])
            ->addColumn('end_date', ['type' => 'date'])
            ->addColumn('country_id', ['type' => 'integer'])
            ->addColumn('active', ['type' => 'boolean'])
            ->addColumn('code', ['type' => 'string'])
            ->addColumn('slug', ['type' => 'string'])
            ->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ]);

        $this->table = new Table([
            'alias' => 'Events',
            'table' => 'events',
            'schema' => $schema,
            'connection' => ConnectionManager::get('test'),
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Filter, $this->controller, $this->table, $this->session);
        Router::reload();
        parent::tearDown();
    }

    /**
     * Create controller + Filter component for a request.
     *
     * @param array<string, mixed> $query Query string params
     * @param array<string, mixed> $componentConfig Component config
     * @return void
     */
    protected function createFilter(array $query = [], array $componentConfig = []): void
    {
        $request = new ServerRequest([
            'params' => [
                'controller' => 'Events',
                'action' => 'index',
                'plugin' => null,
                'pass' => [],
            ],
            'query' => $query,
            'session' => $this->session,
        ]);

        $this->controller = new Controller($request);
        $this->controller->setName('Events');
        $registry = new ComponentRegistry($this->controller);
        $this->Filter = new FilterComponent($registry, $componentConfig);
    }

    /**
     * Default Events-style filter map.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function eventsFilters(): array
    {
        return [
            'title' => ['field' => 'Events.title', 'type' => 'like'],
            'town' => ['field' => 'Events.town', 'type' => 'like'],
            'date_from' => ['field' => 'Events.start_date', 'type' => 'gte', 'validate' => 'date'],
            'date_to' => ['field' => 'Events.end_date', 'type' => 'lte', 'validate' => 'date'],
            'country_id' => ['field' => 'Events.country_id', 'type' => 'equal'],
            'type' => ['field' => 'Festivals.type', 'type' => 'equal'],
            'rank' => ['field' => 'Festivals.rank', 'type' => 'equal'],
            'active' => ['field' => 'Events.active', 'type' => 'boolean'],
        ];
    }

    /**
     * Test like, equal, gte/lte and boolean filters from the query string.
     *
     * @return void
     */
    public function testApplyBuildsConditionsFromQuery(): void
    {
        $this->createFilter([
            'title' => 'Folk',
            'country_id' => '5',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
            'active' => '1',
            'sort' => 'Events.title',
            'page' => '2',
        ]);

        $this->Filter->configure($this->eventsFilters());
        $query = $this->Filter->apply($this->table->find());

        $this->assertSame([
            'Events.title LIKE' => '%Folk%',
            'Events.start_date >=' => '2024-01-01',
            'Events.end_date <=' => '2024-12-31',
            'Events.country_id' => '5',
            'Events.active' => 1,
        ], $this->Filter->getConditions());

        $this->assertSame([
            'title' => 'Folk',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
            'country_id' => '5',
            'active' => '1',
        ], $this->Filter->values());

        $this->assertSame([
            'title' => 'Folk',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
            'country_id' => '5',
            'active' => 1,
        ], $this->Filter->getUrl());

        $this->assertNotNull($query->clause('where'));
        $this->assertSame(
            [
                'title' => 'Folk',
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
                'country_id' => '5',
                'active' => 1,
            ],
            $this->session->read('Filter.Events.index'),
        );
    }

    /**
     * Test unknown query keys are ignored (whitelist).
     *
     * @return void
     */
    public function testApplyIgnoresUnconfiguredQueryKeys(): void
    {
        $this->createFilter([
            'title' => 'Folk',
            'evil' => '1; DROP TABLE events',
        ]);
        $this->Filter->configure([
            'title' => ['field' => 'Events.title', 'type' => 'like'],
        ]);
        $this->Filter->apply($this->table->find());

        $this->assertSame([
            'Events.title LIKE' => '%Folk%',
        ], $this->Filter->getConditions());
        $this->assertArrayNotHasKey('evil', $this->Filter->getUrl());
    }

    /**
     * Test boolean empty string means no filter; "0" still applies.
     *
     * @return void
     */
    public function testBooleanEmptyIsUnsetButZeroApplies(): void
    {
        $this->createFilter(['active' => '']);
        $this->Filter->configure([
            'active' => ['field' => 'Events.active', 'type' => 'boolean'],
        ]);
        $this->Filter->apply($this->table->find());
        $this->assertSame([], $this->Filter->getConditions());

        $this->createFilter(['active' => '0']);
        $this->Filter->configure([
            'active' => ['field' => 'Events.active', 'type' => 'boolean'],
        ]);
        $this->Filter->apply($this->table->find());
        $this->assertSame(['Events.active' => 0], $this->Filter->getConditions());
        $this->assertSame(['active' => 0], $this->Filter->getUrl());
    }

    /**
     * Test invalid dates are skipped when validate=date.
     *
     * @return void
     */
    public function testInvalidDateIsSkipped(): void
    {
        $this->createFilter([
            'date_from' => 'not-a-date',
            'title' => 'Ok',
        ]);
        $this->Filter->configure($this->eventsFilters());
        $this->Filter->apply($this->table->find());

        $this->assertSame([
            'Events.title LIKE' => '%Ok%',
        ], $this->Filter->getConditions());
    }

    /**
     * Test starts_with, ends_with, in, and like explode.
     *
     * @return void
     */
    public function testAdditionalFilterTypes(): void
    {
        $this->createFilter([
            'code' => 'BG',
            'suffix' => 'fest',
            'ids' => '1,2,3',
            'keywords' => 'folk dance',
        ]);
        $this->Filter->configure([
            'code' => ['field' => 'Events.code', 'type' => 'starts_with'],
            'suffix' => ['field' => 'Events.slug', 'type' => 'ends_with'],
            'ids' => ['field' => 'Events.id', 'type' => 'in'],
            'keywords' => ['field' => 'Events.title', 'type' => 'like', 'explode' => 'OR'],
        ]);
        $this->Filter->apply($this->table->find());

        $this->assertSame([
            'Events.code LIKE' => 'BG%',
            'Events.slug LIKE' => '%fest',
            'Events.id IN' => ['1', '2', '3'],
            'OR' => [
                ['Events.title LIKE' => '%folk%'],
                ['Events.title LIKE' => '%dance%'],
            ],
        ], $this->Filter->getConditions());
    }

    /**
     * Test date_range with from/to keys on one field.
     *
     * @return void
     */
    public function testDateRangeSingleField(): void
    {
        $this->createFilter([
            'period_from' => '2024-01-01',
            'period_to' => '2024-06-30',
        ]);
        $this->Filter->configure([
            'period' => [
                'type' => 'date_range',
                'field' => 'Events.start_date',
            ],
        ]);
        $this->Filter->apply($this->table->find());

        $this->assertSame([
            'Events.start_date >=' => '2024-01-01',
            'Events.start_date <=' => '2024-06-30',
        ], $this->Filter->getConditions());
        $this->assertSame([
            'period_from' => '2024-01-01',
            'period_to' => '2024-06-30',
        ], $this->Filter->getUrl());
    }

    /**
     * Test date_range with two fields and custom fromKey/toKey.
     *
     * @return void
     */
    public function testDateRangeTwoFields(): void
    {
        $this->createFilter([
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ]);
        $this->Filter->configure([
            'range' => [
                'type' => 'date_range',
                'fields' => ['Events.start_date', 'Events.end_date'],
                'fromKey' => 'date_from',
                'toKey' => 'date_to',
            ],
        ]);
        $this->Filter->apply($this->table->find());

        $this->assertSame([
            'Events.start_date >=' => '2024-01-01',
            'Events.end_date <=' => '2024-12-31',
        ], $this->Filter->getConditions());
    }

    /**
     * Test empty filter URL restores from session via redirect.
     *
     * @return void
     */
    public function testSessionRestoreRedirects(): void
    {
        $this->session->write('Filter.Events.index', [
            'title' => 'Saved',
            'active' => 1,
        ]);

        $this->createFilter([]);
        $this->Filter->configure($this->eventsFilters());

        try {
            $this->Filter->apply($this->table->find());
            $this->fail('Expected RedirectException');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('title=Saved', $e->getMessage());
            $this->assertStringContainsString('active=1', $e->getMessage());
        }
    }

    /**
     * Test clear_filters deletes session and redirects without filters.
     *
     * @return void
     */
    public function testClearFiltersRedirectsAndWipesSession(): void
    {
        $this->session->write('Filter.Events.index', ['title' => 'Saved']);

        $this->createFilter([
            'clear_filters' => '1',
            'sort' => 'Events.id',
        ]);
        $this->Filter->configure($this->eventsFilters());

        try {
            $this->Filter->apply($this->table->find());
            $this->fail('Expected RedirectException');
        } catch (RedirectException $e) {
            $this->assertNull($this->session->read('Filter.Events.index'));
            $this->assertStringContainsString('sort=Events.id', $e->getMessage());
            $this->assertStringNotContainsString('title=', $e->getMessage());
            $this->assertStringNotContainsString('clear_filters', $e->getMessage());
        }
    }

    /**
     * Test submitting empty filter keys clears session without redirect.
     *
     * @return void
     */
    public function testEmptyFilterKeysClearSession(): void
    {
        $this->session->write('Filter.Events.index', ['title' => 'Old']);

        $this->createFilter(['title' => '']);
        $this->Filter->configure($this->eventsFilters());
        $this->Filter->apply($this->table->find());

        $this->assertSame([], $this->Filter->getConditions());
        $this->assertSame([], $this->session->read('Filter.Events.index'));
    }

    /**
     * Test custom sessionKey config.
     *
     * @return void
     */
    public function testCustomSessionKey(): void
    {
        $this->createFilter(['title' => 'X'], ['sessionKey' => 'Filter.Custom']);
        $this->Filter->configure([
            'title' => ['field' => 'Events.title', 'type' => 'like'],
        ]);
        $this->Filter->apply($this->table->find());

        $this->assertSame(['title' => 'X'], $this->session->read('Filter.Custom'));
        $this->assertNull($this->session->read('Filter.Events.index'));
    }

    /**
     * Test not_in filter type.
     *
     * @return void
     */
    public function testNotInFilter(): void
    {
        $this->createFilter(['ids' => ['4', '5']]);
        $this->Filter->configure([
            'ids' => ['field' => 'Events.id', 'type' => 'not_in'],
        ]);
        $this->Filter->apply($this->table->find());

        $this->assertSame([
            'Events.id NOT IN' => ['4', '5'],
        ], $this->Filter->getConditions());
        $this->assertSame(['ids' => '4,5'], $this->Filter->getUrl());
    }
}
