<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Controller\Component;

use Brammo\Admin\Controller\Component\SortableComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * SortableComponent Test Case
 */
class SortableComponentTest extends TestCase
{
    /**
     * @var \Cake\Controller\Controller
     */
    protected Controller $controller;

    /**
     * @var \Brammo\Admin\Controller\Component\SortableComponent
     */
    protected SortableComponent $Sortable;

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $table;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS sortable_items');
        $connection->execute(
            'CREATE TABLE sortable_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                owner_id INTEGER,
                sort_order INTEGER NOT NULL DEFAULT 0
            )',
        );

        $this->table = $this->getTableLocator()->get('SortableItems', [
            'table' => 'sortable_items',
            'connection' => $connection,
        ]);
        $this->table->addBehavior('ADmad/Sequence.Sequence', [
            'sequenceField' => 'sort_order',
            'scope' => ['owner_id'],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->getTableLocator()->remove('SortableItems');
        ConnectionManager::get('test')->execute('DROP TABLE IF EXISTS sortable_items');
        unset($this->Sortable, $this->controller, $this->table);
        parent::tearDown();
    }

    /**
     * Create controller + Sortable component for a request.
     *
     * @param string $method HTTP method
     * @param array<string, mixed> $data POST data
     * @return void
     */
    protected function createSortable(string $method = 'POST', array $data = []): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => $data,
            'params' => [
                'controller' => 'Items',
                'action' => 'sort',
                'plugin' => null,
                'pass' => [],
            ],
        ]);

        $this->controller = new Controller($request);
        $this->controller->setName('Items');
        $registry = new ComponentRegistry($this->controller);
        $this->Sortable = new SortableComponent($registry);
    }

    /**
     * Insert scoped rows and return their ids in insert order.
     *
     * @param int|null $ownerId Owner foreign key
     * @param list<string> $titles Row titles
     * @return list<int>
     */
    protected function insertItems(?int $ownerId, array $titles): array
    {
        $ids = [];
        foreach ($titles as $title) {
            $entity = $this->table->newEntity([
                'title' => $title,
                'owner_id' => $ownerId,
            ]);
            $saved = $this->table->saveOrFail($entity);
            $ids[] = (int)$saved->id;
        }

        return $ids;
    }

    /**
     * Decode JSON response body.
     *
     * @param \Cake\Http\Response $response Response
     * @return array<string, mixed>
     */
    protected function decode(Response $response): array
    {
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($payload);

        return $payload;
    }

    /**
     * GET requests are rejected.
     *
     * @return void
     */
    public function testReorderRejectsNonPost(): void
    {
        $this->createSortable('GET');

        $this->expectException(BadRequestException::class);
        $this->Sortable->reorder($this->table, [1]);
    }

    /**
     * Empty id list still returns ok.
     *
     * @return void
     */
    public function testReorderEmptyIdsReturnsOk(): void
    {
        $this->createSortable('POST', ['ids' => []]);
        $response = $this->Sortable->reorder($this->table, []);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['ok' => true], $this->decode($response));
    }

    /**
     * Posted order is persisted via Sequence.
     *
     * @return void
     */
    public function testReorderUpdatesSortOrder(): void
    {
        $ids = $this->insertItems(1, ['First', 'Second', 'Third']);
        $this->assertSame(1, $this->table->get($ids[0])->sort_order);
        $this->assertSame(2, $this->table->get($ids[1])->sort_order);
        $this->assertSame(3, $this->table->get($ids[2])->sort_order);

        $this->createSortable('POST', ['ids' => [$ids[2], $ids[0], $ids[1]]]);
        $response = $this->Sortable->reorder(
            $this->table,
            [$ids[2], $ids[0], $ids[1]],
            ['owner_id' => 1],
        );

        $this->assertSame(['ok' => true], $this->decode($response));
        $this->assertSame(1, $this->table->get($ids[2])->sort_order);
        $this->assertSame(2, $this->table->get($ids[0])->sort_order);
        $this->assertSame(3, $this->table->get($ids[1])->sort_order);
    }

    /**
     * IDs outside the owner scope are ignored.
     *
     * @return void
     */
    public function testReorderFiltersIdsToOwnerScope(): void
    {
        $ownerOne = $this->insertItems(1, ['A', 'B']);
        $ownerTwo = $this->insertItems(2, ['C']);

        $this->createSortable();
        $this->Sortable->reorder(
            $this->table,
            [$ownerOne[1], $ownerTwo[0], $ownerOne[0]],
            ['owner_id' => 1],
        );

        $this->assertSame(1, $this->table->get($ownerOne[1])->sort_order);
        $this->assertSame(2, $this->table->get($ownerOne[0])->sort_order);
        $this->assertSame(1, $this->table->get($ownerTwo[0])->sort_order);
    }

    /**
     * Null owner scope uses IS NULL conditions.
     *
     * @return void
     */
    public function testReorderAllowsNullOwnerScope(): void
    {
        $ids = $this->insertItems(null, ['Root A', 'Root B']);

        $this->createSortable();
        $this->Sortable->reorder(
            $this->table,
            [$ids[1], $ids[0]],
            ['owner_id' => null],
        );

        $this->assertSame(1, $this->table->get($ids[1])->sort_order);
        $this->assertSame(2, $this->table->get($ids[0])->sort_order);
    }

    /**
     * Missing Sequence behavior fails when there are IDs to persist.
     *
     * @return void
     */
    public function testReorderRequiresSequenceBehavior(): void
    {
        $this->table->removeBehavior('Sequence');
        $this->createSortable();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sequence behavior is required for sorting');
        $this->Sortable->reorder($this->table, [1]);
    }

    /**
     * UUID primary keys are kept as strings instead of being cast to int.
     *
     * @return void
     */
    public function testReorderSupportsUuidPrimaryKeys(): void
    {
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS sortable_uuid_items');
        $connection->execute(
            'CREATE TABLE sortable_uuid_items (
                id CHAR(36) PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                owner_id INTEGER,
                sort_order INTEGER NOT NULL DEFAULT 0
            )',
        );

        $table = $this->getTableLocator()->get('SortableUuidItems', [
            'table' => 'sortable_uuid_items',
            'connection' => $connection,
        ]);
        $table->addBehavior('ADmad/Sequence.Sequence', [
            'sequenceField' => 'sort_order',
            'scope' => ['owner_id'],
        ]);

        $ids = [];
        foreach (['A', 'B', 'C'] as $title) {
            $ids[] = (string)$table->saveOrFail($table->newEntity(['title' => $title, 'owner_id' => 1]))->id;
        }

        try {
            $this->createSortable();
            $this->Sortable->reorder($table, [$ids[2], $ids[0], $ids[1]], ['owner_id' => 1]);

            $this->assertSame(1, $table->get($ids[2])->sort_order);
            $this->assertSame(2, $table->get($ids[0])->sort_order);
            $this->assertSame(3, $table->get($ids[1])->sort_order);
        } finally {
            $this->getTableLocator()->remove('SortableUuidItems');
            $connection->execute('DROP TABLE IF EXISTS sortable_uuid_items');
        }
    }
}
