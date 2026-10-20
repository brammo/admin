<?php
declare(strict_types=1);

namespace Brammo\Admin\Model\Entity;

use Authentication\PasswordHasher\PasswordHasherFactory;
use Cake\Core\Configure;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string|null $email
 * @property string|null $password
 * @property string $status
 * @property string|null $name
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class User extends Entity
{
    /**
     * Status constant for active users
     */
    public const STATUS_ACTIVE = 'active';

    /**
     * Status constant for new users (not yet activated)
     */
    public const STATUS_NEW = 'new';

    /**
     * Status constant for blocked users
     */
    public const STATUS_BLOCKED = 'blocked';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'email' => true,
        'password' => true,
        'status' => true,
        'name' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<array-key, string>
     */
    protected array $_hidden = [
        'password',
    ];

    /**
     * Hashes the password before saving the user entity.
     *
     * @param string $password The plain text password.
     * @return string|null The hashed password or null if the input is empty.
     */
    protected function _setPassword(string $password): ?string
    {
        if ($password === '') {
            return null;
        }

        $passwordHasher = Configure::read('Admin.Auth.Authentication.passwordHasher');
        if (!is_array($passwordHasher)) {
            $passwordHasher = Configure::read('Auth.Authentication.passwordHasher', [
                'className' => 'Authentication.Default',
            ]);
        }

        return PasswordHasherFactory::build($passwordHasher)->hash($password);
    }

    /**
     * Check if user status is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user status is blocked
     *
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Check if user status is new (not yet activated)
     *
     * Do not name this method isNew() — it would override Entity::isNew() used by the ORM.
     *
     * @return bool
     */
    public function isStatusNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }
}
