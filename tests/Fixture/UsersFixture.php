<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Import table schema
     *
     * @var array
     */
    public array $import = ['table' => 'users', 'connection' => 'test'];

    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'users';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'email' => 'user@example.com',
                'password' => '$2y$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', // password
                'first_name' => 'Test',
                'last_name' => 'User',
                'created' => '2025-01-01 00:00:00',
                'modified' => '2025-01-01 00:00:00',
            ],
            [
                'id' => 2,
                'email' => 'admin@example.com',
                'password' => '$2y$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO', // password
                'first_name' => 'Admin',
                'last_name' => 'User',
                'created' => '2025-01-01 00:00:00',
                'modified' => '2025-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
