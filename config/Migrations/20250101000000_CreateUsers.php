<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * CreateUsers migration
 *
 * Creates the users table for admin authentication.
 */
class CreateUsers extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');

        $table->addColumn('name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ]);

        $table->addColumn('email', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);

        $table->addColumn('password', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);

        $table->addColumn('status', 'string', [
            'default' => 'new',
            'limit' => 20,
            'null' => false,
        ]);

        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => true,
        ]);

        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => true,
        ]);

        $table->addIndex(['email'], [
            'name' => 'UNIQUE_EMAIL',
            'unique' => true,
        ]);

        $table->addIndex(['status'], [
            'name' => 'IDX_STATUS',
        ]);

        $table->create();
    }
}
