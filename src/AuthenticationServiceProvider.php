<?php
declare(strict_types=1);

namespace Brammo\Admin;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Cake\Core\Configure;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authentication Service Provider for /admin routes.
 *
 * Reads Admin.Auth and falls back to the legacy Auth key.
 */
class AuthenticationServiceProvider implements AuthenticationServiceProviderInterface
{
    /**
     * Returns a service provider instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $fields = $this->auth('Authentication.fields', [
            'username' => 'email',
            'password' => 'password',
        ]);
        if (!is_array($fields)) {
            $fields = [
                'username' => 'email',
                'password' => 'password',
            ];
        }

        $loginUrl = $this->auth('Routes.login', '/admin/login');
        if (!is_string($loginUrl) || $loginUrl === '') {
            $loginUrl = '/admin/login';
        }

        $userModel = $this->auth('Users.table', 'Brammo/Admin.Users');
        if (!is_string($userModel) || $userModel === '') {
            $userModel = 'Brammo/Admin.Users';
        }

        $passwordHasher = $this->auth('Authentication.passwordHasher', [
            'className' => 'Authentication.Default',
        ]);
        if (!is_array($passwordHasher)) {
            $passwordHasher = [
                'className' => 'Authentication.Default',
            ];
        }

        $finder = $this->auth('Authentication.finder', 'active');
        if (!is_string($finder) || $finder === '') {
            $finder = 'active';
        }

        $sessionKey = $this->auth('Authentication.sessionKey', 'AdminAuth');
        if (!is_string($sessionKey) || $sessionKey === '') {
            $sessionKey = 'AdminAuth';
        }

        $rememberMeField = $this->auth('Authentication.rememberMeField', 'remember_me');
        if (!is_string($rememberMeField) || $rememberMeField === '') {
            $rememberMeField = 'remember_me';
        }

        $cookieName = $this->auth('Authentication.cookieName', 'AdminCookieAuth');
        if (!is_string($cookieName) || $cookieName === '') {
            $cookieName = 'AdminCookieAuth';
        }

        $cookie = $this->auth('Authentication.cookie');
        if (!is_array($cookie)) {
            $cookie = [
                'name' => $cookieName,
            ];
        } elseif (!isset($cookie['name'])) {
            $cookie['name'] = $cookieName;
        }

        $identifier = [
            'Authentication.Password' => [
                'resolver' => [
                    'className' => 'Authentication.Orm',
                    'userModel' => $userModel,
                    'finder' => $finder,
                ],
                'fields' => $fields,
                'passwordHasher' => $passwordHasher,
            ],
        ];

        $service = new AuthenticationService([
            'authenticators' => [
                'Authentication.Session' => [
                    'sessionKey' => $sessionKey,
                ],
                'Authentication.Form' => [
                    'fields' => $fields,
                    'loginUrl' => $loginUrl,
                    'identifier' => $identifier,
                ],
                'Authentication.Cookie' => [
                    'fields' => $fields,
                    'loginUrl' => $loginUrl,
                    'identifier' => $identifier,
                    'rememberMeField' => $rememberMeField,
                    'cookie' => $cookie,
                ],
            ],
            'unauthenticatedRedirect' => $loginUrl,
            'queryParam' => 'redirect',
        ]);

        return $service;
    }

    /**
     * Read an auth config value from Admin.Auth, then Auth.
     *
     * @param string $path Dot path under the Auth config.
     * @param mixed $default Value when neither key is set.
     * @return mixed
     */
    private function auth(string $path, mixed $default = null): mixed
    {
        $value = Configure::read('Admin.Auth.' . $path);
        if ($value !== null) {
            return $value;
        }

        $legacy = Configure::read('Auth.' . $path);

        return $legacy ?? $default;
    }
}
