<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\Fixture;

use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'users';

    /**
     * Fields
     *
     * @var array<string, mixed>
     */
    public array $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
        'name' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null],
        'email' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null],
        'password' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null],
        'status' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => 'new'],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'UNIQUE_EMAIL' => ['type' => 'unique', 'columns' => ['email'], 'length' => []],
        ],
    ];

    /**
     * Records
     *
     * @var array<array<string, mixed>>
     */
    public array $records = [
        [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            // Password: 'password' hashed with the default hasher
            'password' => '$2y$10$ZiAEOHsyqVUflvVRIHqxjOFzYMfygjU7B7apVsmhNE.i/2RJYkGri',
            'status' => 'active',
            'created' => '2023-01-01 00:00:00',
            'modified' => '2023-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            // Password: 'password' hashed with the default hasher
            'password' => '$2y$10$ZiAEOHsyqVUflvVRIHqxjOFzYMfygjU7B7apVsmhNE.i/2RJYkGri',
            'status' => 'active',
            'created' => '2023-01-02 00:00:00',
            'modified' => '2023-01-02 00:00:00',
        ],
        [
            'id' => 3,
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            // Password: 'password' hashed with the default hasher
            'password' => '$2y$10$ZiAEOHsyqVUflvVRIHqxjOFzYMfygjU7B7apVsmhNE.i/2RJYkGri',
            'status' => 'blocked',
            'created' => '2023-01-03 00:00:00',
            'modified' => '2023-01-03 00:00:00',
        ],
        [
            'id' => 4,
            'name' => 'New User',
            'email' => 'new@example.com',
            // Password: 'password' hashed with the default hasher
            'password' => '$2y$10$ZiAEOHsyqVUflvVRIHqxjOFzYMfygjU7B7apVsmhNE.i/2RJYkGri',
            'status' => 'new',
            'created' => '2023-01-04 00:00:00',
            'modified' => '2023-01-04 00:00:00',
        ],
    ];

    /**
     * Create the users table before CakePHP reflects the fixture schema.
     *
     * @return void
     */
    public function init(): void
    {
        $connection = ConnectionManager::get('test');
        $tables = $connection->getSchemaCollection()->listTables();
        if (!in_array('users', $tables, true)) {
            $schema = new TableSchema('users');
            foreach ($this->fields as $name => $column) {
                if ($name === '_constraints' || !is_array($column)) {
                    continue;
                }
                $schema->addColumn($name, $column);
            }
            $schema->addConstraint('primary', [
                'type' => 'primary',
                'columns' => ['id'],
            ]);
            $schema->addConstraint('UNIQUE_EMAIL', [
                'type' => 'unique',
                'columns' => ['email'],
            ]);

            foreach ($schema->createSql($connection) as $sql) {
                $connection->execute($sql);
            }
        }

        parent::init();
    }
}
