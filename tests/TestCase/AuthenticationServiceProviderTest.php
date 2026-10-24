<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase;

use Authentication\AuthenticationService;
use Brammo\Admin\AuthenticationServiceProvider;
use Cake\Core\Configure;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;

/**
 * Brammo\Admin\AuthenticationServiceProvider Test Case
 */
class AuthenticationServiceProviderTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Brammo\Admin\AuthenticationServiceProvider
     */
    protected AuthenticationServiceProvider $provider;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Admin.Auth.Authentication.fields', [
            'username' => 'email',
            'password' => 'password',
        ]);
        Configure::write('Admin.Auth.Routes.login', '/admin/login');
        Configure::write('Admin.Auth.Users.table', 'Brammo/Admin.Users');
        Configure::write('Admin.Auth.Authentication.passwordHasher', [
            'className' => 'Authentication.Default',
        ]);

        $this->provider = new AuthenticationServiceProvider();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->provider);

        parent::tearDown();
    }

    /**
     * Test getAuthenticationService method
     *
     * @return void
     */
    public function testGetAuthenticationService(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $service = $this->provider->getAuthenticationService($request);

        $this->assertInstanceOf(AuthenticationService::class, $service);
    }

    /**
     * Test authentication service configuration
     *
     * @return void
     */
    public function testAuthenticationServiceConfiguration(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $service = $this->provider->getAuthenticationService($request);
        $result = $service->authenticate($request);

        $this->assertFalse($result->isValid());
    }

    /**
     * Test authentication service with custom configuration
     *
     * @return void
     */
    public function testGetAuthenticationServiceWithCustomConfig(): void
    {
        Configure::write('Admin.Auth.Authentication.fields', [
            'username' => 'custom_email',
            'password' => 'custom_password',
        ]);
        Configure::write('Admin.Auth.Routes.login', '/custom-login');
        Configure::write('Admin.Auth.Users.table', 'CustomUsers');

        $request = ServerRequestFactory::fromGlobals();
        $service = $this->provider->getAuthenticationService($request);

        $this->assertInstanceOf(AuthenticationService::class, $service);
        $this->assertSame('/custom-login', $service->getConfig('unauthenticatedRedirect'));
    }

    /**
     * Test authentication with fallback password hasher
     *
     * @return void
     */
    public function testAuthenticationServiceWithFallbackHasher(): void
    {
        Configure::write('Admin.Auth.Authentication.passwordHasher', [
            'className' => 'Authentication.Fallback',
            'hashers' => [
                'Authentication.Default',
                [
                    'className' => 'Authentication.Legacy',
                    'hashType' => 'sha1',
                ],
            ],
        ]);

        $request = ServerRequestFactory::fromGlobals();
        $service = $this->provider->getAuthenticationService($request);

        $this->assertInstanceOf(AuthenticationService::class, $service);
    }

    /**
     * Test defaults when session and cookie names are not configured
     *
     * @return void
     */
    public function testGetAuthenticationServiceDefaultSessionKeyAndCookieName(): void
    {
        Configure::delete('Admin.Auth.Authentication.sessionKey');
        Configure::delete('Admin.Auth.Authentication.cookieName');
        Configure::delete('Admin.Auth.Authentication.rememberMeField');
        Configure::delete('Auth');

        $request = ServerRequestFactory::fromGlobals();
        $service = $this->provider->getAuthenticationService($request);
        $this->assertInstanceOf(AuthenticationService::class, $service);

        $authenticators = $service->getConfig('authenticators');
        $this->assertSame('AdminAuth', $authenticators['Authentication.Session']['sessionKey']);
        $this->assertSame('AdminCookieAuth', $authenticators['Authentication.Cookie']['cookie']['name']);
        $this->assertSame('remember_me', $authenticators['Authentication.Cookie']['rememberMeField']);
    }

    /**
     * Test custom session key and cookie name are applied to authenticators
     *
     * @return void
     */
    public function testAuthenticatorConfigUsesCustomSessionAndCookie(): void
    {
        Configure::write('Admin.Auth.Authentication.sessionKey', 'AdminAuth');
        Configure::write('Admin.Auth.Authentication.cookieName', 'AdminCookieAuth');
        Configure::write('Admin.Auth.Authentication.rememberMeField', 'stay_logged_in');

        $request = ServerRequestFactory::fromGlobals();
        $service = $this->provider->getAuthenticationService($request);
        $this->assertInstanceOf(AuthenticationService::class, $service);

        $authenticators = $service->getConfig('authenticators');
        $this->assertSame('AdminAuth', $authenticators['Authentication.Session']['sessionKey']);
        $this->assertSame('AdminCookieAuth', $authenticators['Authentication.Cookie']['cookie']['name']);
        $this->assertSame('stay_logged_in', $authenticators['Authentication.Cookie']['rememberMeField']);
    }

    /**
     * Test authentication finder is passed to the password identifier
     *
     * @return void
     */
    public function testAuthenticationFinderConfiguration(): void
    {
        Configure::write('Admin.Auth.Authentication.finder', 'active');

        $request = ServerRequestFactory::fromGlobals();
        $service = $this->provider->getAuthenticationService($request);
        $this->assertInstanceOf(AuthenticationService::class, $service);

        $identifier = $service->getConfig('authenticators')['Authentication.Form']['identifier'];
        $resolver = $identifier['Authentication.Password']['resolver'];

        $this->assertSame('active', $resolver['finder']);
        $this->assertSame('Brammo/Admin.Users', $resolver['userModel']);
    }

    /**
     * Legacy Auth.* is used when Admin.Auth is not set.
     *
     * @return void
     */
    public function testLegacyAuthConfigFallback(): void
    {
        Configure::delete('Admin.Auth');
        Configure::write('Auth.Routes.login', '/legacy-login');
        Configure::write('Auth.Users.table', 'LegacyUsers');
        Configure::write('Auth.Authentication.sessionKey', 'LegacyAuth');
        Configure::write('Auth.Authentication.fields', [
            'username' => 'email',
            'password' => 'password',
        ]);

        $request = ServerRequestFactory::fromGlobals();
        $service = $this->provider->getAuthenticationService($request);
        $this->assertInstanceOf(AuthenticationService::class, $service);

        $authenticators = $service->getConfig('authenticators');
        $this->assertSame('LegacyAuth', $authenticators['Authentication.Session']['sessionKey']);
        $this->assertSame('/legacy-login', $service->getConfig('unauthenticatedRedirect'));

        $resolver = $authenticators['Authentication.Form']['identifier']['Authentication.Password']['resolver'];
        $this->assertSame('LegacyUsers', $resolver['userModel']);
    }
}
