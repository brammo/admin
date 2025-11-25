<?php
/**
 * User profile template
 * 
 * @var \Brammo\Admin\View\AppView $this
 * @var \Brammo\Auth\Model\Entity\User $user
 */

$this->assign('title', __d('brammo/admin', 'User Profile'));
?>
<div class="card">
    <div class="card-body">
        <?= $this->Form->create($user) ?>
        <fieldset>
            <legend><?= __d('brammo/admin', 'Edit Profile') ?></legend>
            <?php
                echo $this->Form->control('name', [
                    'label' => __d('brammo/admin', 'Name'),
                    'required' => true
                ]);
                echo $this->Form->control('email', [
                    'label' => __d('brammo/admin', 'Email'),
                    'type' => 'email',
                    'required' => true
                ]);
                echo $this->Form->control('password', [
                    'label' => __d('brammo/admin', 'Password'),
                    'type' => 'password',
                    'value' => '',
                    'help' => __d('brammo/admin', 'Leave blank to keep current password'),
                    'required' => false
                ]);
            ?>
        </fieldset>
        <div class="mt-3">
            <?= $this->Form->button(__d('brammo/admin', 'Save'), ['class' => 'btn btn-primary']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
