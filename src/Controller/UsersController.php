<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller;

use Authentication\Identifier\IdentifierCollection;
use Authentication\Identifier\PasswordIdentifier;
use Authentication\PasswordHasher\PasswordHasherFactory;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\Table;

/**
 * Users Controller
 *
 * Login, logout, and the signed-in user's profile.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class UsersController extends AppController
{
    /**
     * Called before the controller action
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['login']);
    }

    /**
     * Displays the login form
     *
     * @return \Cake\Http\Response|null Response with redirection on success login
     */
    public function login(): ?Response
    {
        $this->getRequest()->allowMethod(['get', 'post']);

        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $this->rehashUserPassword();

            // getLoginRedirect() validates the `redirect` query param (local URLs only)
            $redirectUrl = $this->Authentication->getLoginRedirect()
                ?? $this->authConfig('Routes.loginRedirect', '/admin');

            return $this->redirect(is_array($redirectUrl) || is_string($redirectUrl) ? $redirectUrl : '/admin');
        }

        if ($this->getRequest()->is('post')) {
            $this->showLoginError();
        }

        $template = $this->authConfig('Templates.login');
        if (is_string($template) && $template !== '') {
            $this->viewBuilder()->setTemplate($template);
        }

        return null;
    }

    /**
     * Logs out the user
     *
     * @return \Cake\Http\Response|null Response with redirection
     */
    public function logout(): ?Response
    {
        $this->Authentication->logout();

        $this->Flash->success(__d('brammo/admin', 'You have been logged out'));

        $loginUrl = $this->authConfig('Routes.login', '/admin/login');

        return $this->redirect(is_string($loginUrl) && $loginUrl !== '' ? $loginUrl : '/admin/login');
    }

    /**
     * View and edit the logged-in user
     *
     * @return \Cake\Http\Response|null
     */
    public function profile(): ?Response
    {
        $this->getRequest()->allowMethod(['get', 'post', 'put', 'patch']);

        $userId = $this->Authentication->getIdentityData('id');
        if (!is_int($userId) && !is_string($userId)) {
            $loginUrl = $this->authConfig('Routes.login', '/admin/login');

            return $this->redirect(is_string($loginUrl) && $loginUrl !== '' ? $loginUrl : '/admin/login');
        }

        $usersTable = $this->usersTable();
        $user = $usersTable->get($userId);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();
            if (!is_array($data)) {
                $data = [];
            }

            if (empty($data['password'])) {
                unset($data['password']);
            }

            $user = $usersTable->patchEntity($user, $data, [
                'validate' => 'default',
                'fields' => ['name', 'email', 'password'],
            ]);

            if ($usersTable->save($user)) {
                $this->Flash->success(__d('brammo/admin', 'Your profile has been updated.'));

                return $this->redirect(['action' => 'profile']);
            }

            $this->Flash->error(__d('brammo/admin', 'Unable to update your profile. Please try again.'));
        }

        $this->set(compact('user'));

        return null;
    }

    /**
     * Rehash password if needed
     *
     * @return void
     */
    private function rehashUserPassword(): void
    {
        if (!$this->authConfig('Authentication.rehashPasswords', false)) {
            return;
        }

        $authentication = $this->Authentication->getAuthenticationService();
        $provider = $authentication->getAuthenticationProvider();
        if ($provider === null) {
            return;
        }

        $identifiers = $provider->getIdentifier();
        if (!$identifiers instanceof IdentifierCollection || !$identifiers->has('Password')) {
            return;
        }

        $passwordIdentifier = $identifiers->get('Password');
        if (!$passwordIdentifier instanceof PasswordIdentifier || !$passwordIdentifier->needsPasswordRehash()) {
            return;
        }

        $identity = $authentication->getIdentity();
        if ($identity === null) {
            return;
        }

        $userId = $identity->getIdentifier();
        if (!is_int($userId) && !is_string($userId)) {
            return;
        }

        $password = $this->getRequest()->getData('password');
        if (!is_string($password) || $password === '') {
            return;
        }

        $usersTable = $this->usersTable();
        $user = $usersTable->get($userId);
        $user->set('password', $password);
        $usersTable->save($user);
    }

    /**
     * Show a login error based on account status when enumeration is enabled.
     *
     * The account status is only revealed when the submitted password is
     * correct, so the message cannot be used to probe which emails exist.
     *
     * @return void
     */
    private function showLoginError(): void
    {
        $fields = $this->authConfig('Authentication.fields', []);
        $usernameField = is_array($fields) && is_string($fields['username'] ?? null) ? $fields['username'] : 'email';
        $passwordField = is_array($fields) && is_string($fields['password'] ?? null) ? $fields['password'] : 'password';

        $username = $this->getRequest()->getData($usernameField);
        $password = $this->getRequest()->getData($passwordField);
        $messages = $this->authConfig('Messages', []);
        if (!is_array($messages)) {
            $messages = [];
        }

        $enumerateAccounts = $messages['enumerateAccounts'] ?? true;

        if (
            $enumerateAccounts
            && is_string($username) && $username !== ''
            && is_string($password) && $password !== ''
        ) {
            $user = $this->usersTable()->find()
                ->where([$usernameField => $username])
                ->first();

            if (!$user instanceof EntityInterface || !$this->passwordMatches($user, $passwordField, $password)) {
                $user = null;
            }

            if (is_object($user) && $this->hasAccountStatus($user, 'isBlocked')) {
                $blocked = $messages['blocked'] ?? 'Your account has been blocked';
                $blockedMessage = is_string($blocked) ? $blocked : 'Your account has been blocked';
                $this->Flash->error(__d('brammo/admin', $blockedMessage));

                return;
            }

            if (is_object($user) && $this->hasAccountStatus($user, 'isStatusNew')) {
                $notActivated = $messages['notActivated'] ?? 'Your account is not yet activated';
                $this->Flash->error(__d(
                    'brammo/admin',
                    is_string($notActivated) ? $notActivated : 'Your account is not yet activated',
                ));

                return;
            }
        }

        $invalid = $messages['invalidCredentials'] ?? 'Invalid email or password';
        $this->Flash->error(__d(
            'brammo/admin',
            is_string($invalid) ? $invalid : 'Invalid email or password',
        ));
    }

    /**
     * Check a plain password against the user's stored hash.
     *
     * @param \Cake\Datasource\EntityInterface $user User entity.
     * @param string $passwordField Password column.
     * @param string $password Submitted plain password.
     * @return bool
     */
    private function passwordMatches(EntityInterface $user, string $passwordField, string $password): bool
    {
        $hash = $user->get($passwordField);
        if (!is_string($hash) || $hash === '') {
            return false;
        }

        $hasher = $this->authConfig('Authentication.passwordHasher', [
            'className' => 'Authentication.Default',
        ]);
        if (!is_array($hasher)) {
            $hasher = ['className' => 'Authentication.Default'];
        }

        return PasswordHasherFactory::build($hasher)->check($password, $hash);
    }

    /**
     * Users table configured for authentication.
     *
     * @return \Cake\ORM\Table
     */
    private function usersTable(): Table
    {
        $table = $this->authConfig('Users.table', 'Brammo/Admin.Users');
        if (!is_string($table) || $table === '') {
            $table = 'Brammo/Admin.Users';
        }

        return $this->fetchTable($table);
    }

    /**
     * Read an auth config value from Admin.Auth, then Auth.
     *
     * @param string $path Dot path under the Auth config.
     * @param mixed $default Value when neither key is set.
     * @return mixed
     */
    private function authConfig(string $path, mixed $default = null): mixed
    {
        $value = Configure::read('Admin.Auth.' . $path);
        if ($value !== null) {
            return $value;
        }

        $legacy = Configure::read('Auth.' . $path);

        return $legacy ?? $default;
    }

    /**
     * Call a boolean status helper when the user entity provides it.
     *
     * Host applications can use their own user entity as long as it
     * implements isBlocked() and/or isStatusNew().
     *
     * @param object $user User entity.
     * @param string $method Method name.
     * @return bool
     */
    private function hasAccountStatus(object $user, string $method): bool
    {
        if (!method_exists($user, $method)) {
            return false;
        }

        $result = $user->{$method}();

        return $result === true;
    }
}
