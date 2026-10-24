<?php
declare(strict_types=1);

namespace Brammo\Admin\Model\Table;

use Brammo\Admin\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @method \Brammo\Admin\Model\Entity\User newEmptyEntity()
 * @method \Brammo\Admin\Model\Entity\User newEntity(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method list<\Brammo\Admin\Model\Entity\User> newEntities(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method \Brammo\Admin\Model\Entity\User get(mixed $primaryKey, array<string, mixed> $options = [])
 * @method \Brammo\Admin\Model\Entity\User findOrCreate(array<string, mixed> $search, ?callable $callback = null, array<string, mixed> $options = [])
 * @method \Brammo\Admin\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method list<\Brammo\Admin\Model\Entity\User> patchEntities(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method \Brammo\Admin\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method \Brammo\Admin\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method list<\Brammo\Admin\Model\Entity\User> saveMany(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @method list<\Brammo\Admin\Model\Entity\User> saveManyOrFail(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @method list<\Brammo\Admin\Model\Entity\User>|false deleteMany(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @method list<\Brammo\Admin\Model\Entity\User> deleteManyOrFail(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>, \Brammo\Admin\Model\Entity\User>
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(User::class);

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->allowEmptyString('name');

        $validator
            ->email('email')
            ->maxLength('email', 255)
            ->notEmptyString('email')
            ->requirePresence('email', 'create');

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->allowEmptyString('password');

        $validator
            ->scalar('status')
            ->inList('status', [User::STATUS_ACTIVE, User::STATUS_NEW, User::STATUS_BLOCKED])
            ->notEmptyString('status');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

        return $rules;
    }

    /**
     * Find active users
     *
     * Filters query to only return users with active status.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Brammo\Admin\Model\Entity\User> $query The query to modify
     * @return \Cake\ORM\Query\SelectQuery<\Brammo\Admin\Model\Entity\User>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('status') => User::STATUS_ACTIVE]);
    }
}
