<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Controller;

use Brammo\Admin\Test\TestApp\Application;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Core\PluginCollection;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Brammo\Admin\Controller\UsersController Test Case
 *
 * @uses \Brammo\Admin\Controller\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'plugin.Brammo/Admin.Users',
    ];

    /**
     * Plugin collection to restore after the application under test replaces it.
     *
     * @var \Cake\Core\PluginCollection
     */
    protected PluginCollection $originalPlugins;

    /**
     * Locale active before the test
     *
     * @var string
     */
    protected string $originalLocale;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->originalPlugins = Plugin::getCollection();
        $this->originalLocale = I18n::getLocale();

        parent::setUp();

        $this->configApplication(Application::class, [TESTS . DS . 'test_app' . DS . 'config' . DS]);

        Configure::write('Admin.Auth.Users.table', 'Brammo/Admin.Users');
        Configure::write('Admin.Auth.Routes.login', '/admin/login');
        Configure::write('Admin.Auth.Routes.logout', '/admin/logout');
        Configure::write('Admin.Auth.Routes.loginRedirect', '/admin');
        Configure::write('Admin.Auth.Templates.login', 'Brammo/Admin.Users/login');
        Configure::write('Admin.Auth.Authentication.fields', [
            'username' => 'email',
            'password' => 'password',
        ]);
        Configure::write('Admin.Auth.Authentication.passwordHasher', [
            'className' => 'Authentication.Default',
        ]);
        Configure::write('Admin.Auth.Authentication.finder', 'active');
        Configure::write('Admin.Auth.Authentication.sessionKey', 'AdminAuth');
        Configure::write('Admin.Auth.Authentication.rehashPasswords', false);
        Configure::write('Admin.Auth.Messages', [
            'invalidCredentials' => 'Invalid email or password',
            'blocked' => 'Your account has been blocked',
            'notActivated' => 'Your account is not yet activated',
            'enumerateAccounts' => true,
        ]);

        $this->enableRetainFlashMessages();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Plugin::setCollection($this->originalPlugins);
        I18n::setLocale($this->originalLocale);
    }

    /**
     * Test login GET request displays form
     *
     * @return void
     */
    public function testLoginGet(): void
    {
        $this->get('/admin/login');

        $this->assertResponseOk();
        $this->assertResponseContains('Sign in');
    }

    /**
     * Test login with valid credentials
     *
     * @return void
     */
    public function testLoginPostWithValidCredentials(): void
    {
        $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertRedirect('/admin');
    }

    /**
     * Test login with invalid credentials
     *
     * @return void
     */
    public function testLoginPostWithInvalidCredentials(): void
    {
        $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertResponseOk();
        $this->assertFlashMessage('Invalid email or password');
    }

    /**
     * Test login with redirect parameter
     *
     * @return void
     */
    public function testLoginWithRedirectParameter(): void
    {
        $this->post('/admin/login?redirect=/admin/profile', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertRedirect('/admin/profile');
    }

    /**
     * Test login ignores an external redirect target
     *
     * @return void
     */
    public function testLoginIgnoresExternalRedirect(): void
    {
        $this->post('/admin/login?redirect=https://evil.example/path', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertRedirect('/admin');
    }

    /**
     * Test logout
     *
     * @return void
     */
    public function testLogout(): void
    {
        $this->session([
            'AdminAuth' => [
                'id' => 1,
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
            ],
        ]);

        $this->post('/admin/logout');

        $this->assertRedirect('/admin/login');
        $this->assertFlashMessage('You have been logged out');
    }

    /**
     * Test logout rejects GET so it cannot be triggered cross-site by a link
     *
     * @return void
     */
    public function testLogoutRejectsGet(): void
    {
        $this->session([
            'AdminAuth' => [
                'id' => 1,
                'email' => 'test@example.com',
                'status' => 'active',
            ],
        ]);

        $this->disableErrorHandlerMiddleware();
        $this->expectException(MethodNotAllowedException::class);

        $this->get('/admin/logout');
    }

    /**
     * Test login with blocked user shows blocked message
     *
     * @return void
     */
    public function testLoginWithBlockedUser(): void
    {
        $this->post('/admin/login', [
            'email' => 'blocked@example.com',
            'password' => 'password',
        ]);

        $this->assertResponseOk();
        $this->assertNoRedirect();
        $this->assertFlashMessage('Your account has been blocked');
    }

    /**
     * Test login with a new user shows the not-activated message
     *
     * @return void
     */
    public function testLoginWithNewUser(): void
    {
        $this->post('/admin/login', [
            'email' => 'new@example.com',
            'password' => 'password',
        ]);

        $this->assertResponseOk();
        $this->assertNoRedirect();
        $this->assertFlashMessage('Your account is not yet activated');
    }

    /**
     * Test a blocked user with a wrong password gets the generic message
     *
     * @return void
     */
    public function testLoginWithBlockedUserWrongPassword(): void
    {
        $this->post('/admin/login', [
            'email' => 'blocked@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertResponseOk();
        $this->assertFlashMessage('Invalid email or password');
    }

    /**
     * Test a new user with a wrong password gets the generic message
     *
     * @return void
     */
    public function testLoginWithNewUserWrongPassword(): void
    {
        $this->post('/admin/login', [
            'email' => 'new@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertResponseOk();
        $this->assertFlashMessage('Invalid email or password');
    }

    /**
     * Test enumerateAccounts disabled shows a generic message
     *
     * @return void
     */
    public function testEnumerateAccountsDisabled(): void
    {
        Configure::write('Admin.Auth.Messages.enumerateAccounts', false);

        $this->post('/admin/login', [
            'email' => 'blocked@example.com',
            'password' => 'password',
        ]);

        $this->assertResponseOk();
        $this->assertFlashMessage('Invalid email or password');
    }

    /**
     * Test password is rehashed when rehashPasswords is enabled
     *
     * @return void
     */
    public function testPasswordRehashAfterLoginWhenEnabled(): void
    {
        Configure::write('Admin.Auth.Authentication.rehashPasswords', true);

        $lowCostHash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 4]);
        $users = $this->getTableLocator()->get('Brammo/Admin.Users');
        $users->getConnection()->update('users', ['password' => $lowCostHash], ['id' => 1]);

        $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertRedirect('/admin');

        $updated = $users->get(1);
        $this->assertNotSame($lowCostHash, $updated->password);
        $this->assertTrue(password_verify('password', (string)$updated->password));
    }

    /**
     * Test profile requires an authenticated user
     *
     * @return void
     */
    public function testProfileRedirectsWhenUnauthenticated(): void
    {
        $this->get('/admin/profile');

        $this->assertRedirectContains('/admin/login');
    }

    /**
     * Test profile displays the signed-in user
     *
     * @return void
     */
    public function testProfileGet(): void
    {
        $this->session([
            'AdminAuth' => [
                'id' => 1,
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
            ],
        ]);

        $this->get('/admin/profile');

        $this->assertResponseOk();
        $this->assertResponseContains('Test User');
    }

    /**
     * Test profile update
     *
     * @return void
     */
    public function testProfilePost(): void
    {
        $this->session([
            'AdminAuth' => [
                'id' => 1,
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
            ],
        ]);

        $this->post('/admin/profile', [
            'name' => 'Updated Name',
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $this->assertRedirect('/admin/profile');
        $this->assertFlashMessage('Your profile has been updated.');

        $user = $this->getTableLocator()->get('Brammo/Admin.Users')->get(1);
        $this->assertSame('Updated Name', $user->name);
    }

    /**
     * Test profile update cannot change protected fields such as status
     *
     * @return void
     */
    public function testProfilePostIgnoresStatus(): void
    {
        $this->session([
            'AdminAuth' => [
                'id' => 3,
                'email' => 'blocked@example.com',
                'name' => 'Blocked User',
                'status' => 'blocked',
            ],
        ]);

        $this->post('/admin/profile', [
            'name' => 'Still Blocked',
            'email' => 'blocked@example.com',
            'status' => 'active',
        ]);

        $this->assertRedirect('/admin/profile');

        $user = $this->getTableLocator()->get('Brammo/Admin.Users')->get(3);
        $this->assertSame('Still Blocked', $user->name);
        $this->assertSame('blocked', $user->status);
    }
}
