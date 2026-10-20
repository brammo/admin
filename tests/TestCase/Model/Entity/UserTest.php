<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Authentication\PasswordHasher\PasswordHasherFactory;
use Brammo\Admin\Model\Entity\User;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Brammo\Admin\Model\Entity\User Test Case
 */
class UserTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Brammo\Admin\Model\Entity\User
     */
    protected User $User;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Admin.Auth.Authentication.passwordHasher', [
            'className' => 'Authentication.Default',
        ]);

        $this->User = new User([
            'id' => 1,
            'email' => 'test@example.com',
            'password' => 'hashedpassword',
            'name' => 'Test User',
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->User);
        Configure::delete('Admin.Auth');

        parent::tearDown();
    }

    /**
     * Test accessible fields
     *
     * @return void
     */
    public function testAccessibleFields(): void
    {
        $user = new User();

        // Test that accessible fields can be set
        $this->assertTrue($user->isAccessible('email'));
        $this->assertTrue($user->isAccessible('password'));
        $this->assertTrue($user->isAccessible('name'));
        $this->assertTrue($user->isAccessible('created'));
        $this->assertTrue($user->isAccessible('modified'));

        // Test that id is not accessible by default
        $this->assertFalse($user->isAccessible('id'));
    }

    /**
     * Test hidden fields
     *
     * @return void
     */
    public function testHiddenFields(): void
    {
        $array = $this->User->toArray();

        // Password should be hidden
        $this->assertArrayNotHasKey('password', $array);

        // Other fields should be present
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('name', $array);
    }

    /**
     * Test JSON serialization
     *
     * @return void
     */
    public function testJsonSerialization(): void
    {
        $json = json_encode($this->User);
        $data = json_decode($json, true);

        // Password should not be in JSON
        $this->assertArrayNotHasKey('password', $data);

        // Other fields should be present
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('test@example.com', $data['email']);
        $this->assertEquals('Test User', $data['name']);
    }

    /**
     * Test entity properties
     *
     * @return void
     */
    public function testEntityProperties(): void
    {
        $this->assertEquals(1, $this->User->id);
        $this->assertEquals('test@example.com', $this->User->email);
        $this->assertEquals('Test User', $this->User->name);

        // Password should be hashed, not plain text
        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check('hashedpassword', $this->User->password));
    }

    /**
     * Test mass assignment
     *
     * @return void
     */
    public function testMassAssignment(): void
    {
        $data = [
            'email' => 'new@example.com',
            'name' => 'New User',
            'password' => 'newpassword',
        ];

        $user = new User($data);

        $this->assertEquals('new@example.com', $user->email);
        $this->assertEquals('New User', $user->name);

        // Password should be hashed, not plain text
        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check('newpassword', $user->password));
    }

    /**
     * Test that id cannot be mass assigned by default
     *
     * @return void
     */
    public function testIdNotMassAssignable(): void
    {
        $data = [
            'id' => 999,
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $user = new User($data, ['guard' => true]);

        // ID should not be set via mass assignment when guard is enabled
        // Note: CakePHP allows setting it unless guard is true
        $this->assertNull($user->id);
    }

    /**
     * Test setting individual properties
     *
     * @return void
     */
    public function testSetIndividualProperties(): void
    {
        $this->User->email = 'updated@example.com';
        $this->User->name = 'Updated Name';

        $this->assertEquals('updated@example.com', $this->User->email);
        $this->assertEquals('Updated Name', $this->User->name);
    }

    /**
     * Test password is hashed when set
     *
     * @return void
     */
    public function testPasswordIsHashed(): void
    {
        $plainPassword = 'mySecretPassword123';

        $user = new User();
        $user->password = $plainPassword;

        // Password should not be stored as plain text
        $this->assertNotEquals($plainPassword, $user->password);

        // Password should be a valid hash that can be verified
        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check($plainPassword, $user->password));
    }

    /**
     * Test password hashing with empty string returns null
     *
     * @return void
     */
    public function testEmptyPasswordReturnsNull(): void
    {
        $user = new User();
        $user->password = '';

        $this->assertNull($user->password);
    }

    /**
     * Test password hashing produces different hashes for same password
     *
     * @return void
     */
    public function testPasswordHashingProducesDifferentHashes(): void
    {
        $plainPassword = 'samePassword';

        $user1 = new User();
        $user1->password = $plainPassword;

        $user2 = new User();
        $user2->password = $plainPassword;

        // Each hash should be unique (due to random salt)
        $this->assertNotEquals($user1->password, $user2->password);

        // But both should verify against the original password
        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check($plainPassword, $user1->password));
        $this->assertTrue($hasher->check($plainPassword, $user2->password));
    }

    /**
     * Test password hashing with special characters
     *
     * @return void
     */
    public function testPasswordHashingWithSpecialCharacters(): void
    {
        $specialPassword = 'P@$$w0rd!#%^&*()_+-=[]{}|;:,.<>?';

        $user = new User();
        $user->password = $specialPassword;

        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check($specialPassword, $user->password));
    }

    /**
     * Test password hashing with unicode characters
     *
     * @return void
     */
    public function testPasswordHashingWithUnicodeCharacters(): void
    {
        $unicodePassword = 'пароль密码パスワード';

        $user = new User();
        $user->password = $unicodePassword;

        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check($unicodePassword, $user->password));
    }

    /**
     * Test status constants
     *
     * @return void
     */
    public function testStatusConstants(): void
    {
        $this->assertEquals('active', User::STATUS_ACTIVE);
        $this->assertEquals('new', User::STATUS_NEW);
        $this->assertEquals('blocked', User::STATUS_BLOCKED);
    }

    /**
     * Test status field is accessible
     *
     * @return void
     */
    public function testStatusIsAccessible(): void
    {
        $user = new User();
        $this->assertTrue($user->isAccessible('status'));
    }

    /**
     * Test isActive method returns true for active status
     *
     * @return void
     */
    public function testIsActiveWithActiveStatus(): void
    {
        $user = new User(['status' => User::STATUS_ACTIVE]);
        $this->assertTrue($user->isActive());
    }

    /**
     * Test isActive method returns false for non-active status
     *
     * @return void
     */
    public function testIsActiveWithNonActiveStatus(): void
    {
        $user = new User(['status' => User::STATUS_BLOCKED]);
        $this->assertFalse($user->isActive());

        $user->status = User::STATUS_NEW;
        $this->assertFalse($user->isActive());
    }

    /**
     * Test isBlocked method returns true for blocked status
     *
     * @return void
     */
    public function testIsBlockedWithBlockedStatus(): void
    {
        $user = new User(['status' => User::STATUS_BLOCKED]);
        $this->assertTrue($user->isBlocked());
    }

    /**
     * Test isBlocked method returns false for non-blocked status
     *
     * @return void
     */
    public function testIsBlockedWithNonBlockedStatus(): void
    {
        $user = new User(['status' => User::STATUS_ACTIVE]);
        $this->assertFalse($user->isBlocked());

        $user->status = User::STATUS_NEW;
        $this->assertFalse($user->isBlocked());
    }

    /**
     * Test isNew method returns true for new status
     *
     * @return void
     */
    public function testIsStatusNewWithNewStatus(): void
    {
        $user = new User(['status' => User::STATUS_NEW]);
        $this->assertTrue($user->isStatusNew());
    }

    /**
     * Test isStatusNew method returns false for other statuses
     *
     * @return void
     */
    public function testIsStatusNewWithOtherStatuses(): void
    {
        $user = new User(['status' => User::STATUS_ACTIVE]);
        $this->assertFalse($user->isStatusNew());

        $user->status = User::STATUS_BLOCKED;
        $this->assertFalse($user->isStatusNew());
    }

    /**
     * Test Entity::isNew() is not overridden by status helpers
     *
     * @return void
     */
    public function testEntityIsNewTracksPersistence(): void
    {
        $user = new User(['status' => User::STATUS_NEW, 'email' => 'a@example.com']);
        $this->assertTrue($user->isNew());
    }

    /**
     * Test password hashing uses configured password hasher
     *
     * @return void
     */
    public function testPasswordUsesConfiguredHasher(): void
    {
        $plainPassword = 'configuredHasherPassword';

        $user = new User();
        $user->password = $plainPassword;

        $hasher = PasswordHasherFactory::build(Configure::read('Admin.Auth.Authentication.passwordHasher'));
        $this->assertTrue($hasher->check($plainPassword, $user->password));
    }

    /**
     * Test status can be set via mass assignment
     *
     * @return void
     */
    public function testStatusMassAssignment(): void
    {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => User::STATUS_ACTIVE,
        ];

        $user = new User($data);

        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
    }
}
