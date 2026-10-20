<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Model\Table;

use Brammo\Admin\Model\Entity\User;
use Brammo\Admin\Model\Table\UsersTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Brammo\Admin\Model\Table\UsersTable Test Case
 */
class UsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Brammo\Admin\Model\Table\UsersTable
     */
    protected UsersTable $Users;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'plugin.Brammo/Admin.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = TableRegistry::getTableLocator()->exists('Users') ? [] : ['className' => UsersTable::class];
        $this->Users = TableRegistry::getTableLocator()->get('Users', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Users);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->assertEquals('users', $this->Users->getTable());
        $this->assertEquals('id', $this->Users->getPrimaryKey());
        $this->assertEquals('name', $this->Users->getDisplayField());

        // Test behaviors
        $this->assertTrue($this->Users->hasBehavior('Timestamp'));
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $user = $this->Users->newEntity([
            'email' => '',
            'password' => '',
        ]);

        $errors = $user->getErrors();

        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayNotHasKey('name', $errors);
    }

    /**
     * Test user can be created without a name
     *
     * @return void
     */
    public function testCreateUserWithoutName(): void
    {
        $data = [
            'email' => 'noname@example.com',
            'password' => 'password123',
            'status' => User::STATUS_ACTIVE,
        ];

        $user = $this->Users->newEntity($data);

        $this->assertEmpty($user->getErrors());
        $result = $this->Users->save($user);

        $this->assertInstanceOf(User::class, $result);
        $this->assertNull($result->name);
    }

    /**
     * Test valid user creation
     *
     * @return void
     */
    public function testCreateValidUser(): void
    {
        $data = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ];

        $user = $this->Users->newEntity($data);

        $this->assertEmpty($user->getErrors());
        $this->assertEquals('New User', $user->name);
        $this->assertEquals('newuser@example.com', $user->email);
    }

    /**
     * Test email validation
     *
     * @return void
     */
    public function testEmailValidation(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $user = $this->Users->newEntity($data);

        $errors = $user->getErrors();
        $this->assertArrayHasKey('email', $errors);
    }

    /**
     * Test email uniqueness rule
     *
     * @return void
     */
    public function testEmailUniqueness(): void
    {
        // Try to create a user with an existing email
        $data = [
            'name' => 'Duplicate User',
            'email' => 'test@example.com', // This email already exists in fixtures
            'password' => 'password123',
        ];

        $user = $this->Users->newEntity($data);
        $result = $this->Users->save($user);

        // Save should fail due to unique constraint
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $user->getErrors());
    }

    /**
     * Test password is required on create
     *
     * @return void
     */
    public function testPasswordRequiredOnCreate(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test2@example.com',
        ];

        $user = $this->Users->newEntity($data);

        // Password is required on create but can be empty (for password hashing)
        // The validation is set to allowEmptyString
        $this->assertEmpty($user->getErrors());
    }

    /**
     * Test saving a user
     *
     * @return void
     */
    public function testSaveUser(): void
    {
        $data = [
            'name' => 'Save Test User',
            'email' => 'savetest@example.com',
            'password' => 'password123',
        ];

        $user = $this->Users->newEntity($data);
        $result = $this->Users->save($user);

        $this->assertInstanceOf(User::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals('Save Test User', $result->name);
    }

    /**
     * Test finding a user by email
     *
     * @return void
     */
    public function testFindByEmail(): void
    {
        $user = $this->Users->find()
            ->where(['email' => 'test@example.com'])
            ->first();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
    }

    /**
     * Test updating a user
     *
     * @return void
     */
    public function testUpdateUser(): void
    {
        $user = $this->Users->get(1);
        $user->name = 'Updated Name';

        $result = $this->Users->save($user);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Updated Name', $result->name);
    }

    /**
     * Test deleting a user
     *
     * @return void
     */
    public function testDeleteUser(): void
    {
        $user = $this->Users->get(1);
        $result = $this->Users->delete($user);

        $this->assertTrue($result);

        // Verify user is deleted
        $count = $this->Users->find()->where(['id' => 1])->count();
        $this->assertEquals(0, $count);
    }

    /**
     * Test timestamp behavior
     *
     * @return void
     */
    public function testTimestampBehavior(): void
    {
        $data = [
            'name' => 'Timestamp Test',
            'email' => 'timestamp@example.com',
            'password' => 'password123',
        ];

        $user = $this->Users->newEntity($data);
        $user = $this->Users->save($user);

        // Timestamps should be automatically set
        $this->assertNotNull($user->created);
        $this->assertNotNull($user->modified);
    }

    /**
     * Test name max length validation
     *
     * @return void
     */
    public function testNameMaxLength(): void
    {
        $data = [
            'name' => str_repeat('a', 256), // 256 characters, max is 255
            'email' => 'longname@example.com',
            'password' => 'password123',
        ];

        $user = $this->Users->newEntity($data);

        $errors = $user->getErrors();
        $this->assertArrayHasKey('name', $errors);
    }

    /**
     * Test password max length validation
     *
     * @return void
     */
    public function testPasswordMaxLength(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'longpass@example.com',
            'password' => str_repeat('a', 256), // 256 characters, max is 255
        ];

        $user = $this->Users->newEntity($data);

        $errors = $user->getErrors();
        $this->assertArrayHasKey('password', $errors);
    }

    /**
     * Test findActive finder returns only active users
     *
     * @return void
     */
    public function testFindActive(): void
    {
        $activeUsers = $this->Users->find('active')->all();

        foreach ($activeUsers as $user) {
            $this->assertEquals('active', $user->status);
        }

        // Should have 2 active users from fixtures
        $this->assertCount(2, $activeUsers);
    }

    /**
     * Test findActive excludes blocked users
     *
     * @return void
     */
    public function testFindActiveExcludesBlockedUsers(): void
    {
        $activeUsers = $this->Users->find('active')->all()->toArray();
        $emails = array_column($activeUsers, 'email');

        $this->assertContains('test@example.com', $emails);
        $this->assertContains('admin@example.com', $emails);
        $this->assertNotContains('blocked@example.com', $emails);
    }

    /**
     * Test findActive excludes new users
     *
     * @return void
     */
    public function testFindActiveExcludesNewUsers(): void
    {
        $activeUsers = $this->Users->find('active')->all()->toArray();
        $emails = array_column($activeUsers, 'email');

        $this->assertNotContains('new@example.com', $emails);
    }

    /**
     * Test status validation with valid values
     *
     * @return void
     */
    public function testStatusValidationWithValidValues(): void
    {
        $validStatuses = ['active', 'new', 'blocked'];

        foreach ($validStatuses as $status) {
            $data = [
                'name' => 'Test',
                'email' => "status-{$status}@example.com",
                'password' => 'password123',
                'status' => $status,
            ];

            $user = $this->Users->newEntity($data);
            $errors = $user->getErrors();

            $this->assertArrayNotHasKey('status', $errors, "Status '{$status}' should be valid");
        }
    }

    /**
     * Test status validation with invalid value
     *
     * @return void
     */
    public function testStatusValidationWithInvalidValue(): void
    {
        $data = [
            'name' => 'Test',
            'email' => 'statustest@example.com',
            'password' => 'password123',
            'status' => 'invalid_status',
        ];

        $user = $this->Users->newEntity($data);
        $errors = $user->getErrors();

        $this->assertArrayHasKey('status', $errors);
    }

    /**
     * Test status defaults to 'new' when not provided
     *
     * Note: The default value is set in the database migration.
     * In test fixtures, we verify the schema definition includes the default.
     *
     * @return void
     */
    public function testStatusDefaultValue(): void
    {
        // Verify the schema has the correct default
        $schema = $this->Users->getSchema();
        $column = $schema->getColumn('status');

        $this->assertEquals('new', $column['default']);
    }

    /**
     * Test status is not empty validation
     *
     * @return void
     */
    public function testStatusNotEmpty(): void
    {
        $data = [
            'name' => 'Test',
            'email' => 'emptystatustest@example.com',
            'password' => 'password123',
            'status' => '',
        ];

        $user = $this->Users->newEntity($data);
        $errors = $user->getErrors();

        $this->assertArrayHasKey('status', $errors);
    }
}
