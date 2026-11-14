<?php
/**
 * User login template
 * 
 * @var \Brammo\Admin\View\AppView $this
 * @var string|null $usernameField
 * @var string|null $passwordField
 * @var string|null $rememberMeField
 */

use Cake\Core\Configure;

// Field names follow Admin.Auth.Authentication (fields, rememberMeField)
$usernameField = $usernameField ?? 'email';
$passwordField = $passwordField ?? 'password';
$rememberMeField = $rememberMeField ?? 'remember_me';

// Set the layout for the login page
$this->setLayout('Brammo/Admin.login');

$this->assign('title', Configure::read('Admin.Brand.name'));
?>
<div class="d-flex vh-100 align-items-center justify-content-center">
    <div style="max-width:20rem">
        <?= $this->Flash->render() ?>
        <div class="text-center mb-3">
            <?= Configure::read('Admin.Brand.html') ?>
        </div>
        <?= $this->Flash->render() ?>
        <div class="card">
            <div class="card-body p-4"><?php
                echo $this->Form->create();
                echo $this->Form->control($usernameField, [
                    'label' => __d('brammo/admin', 'Email'),
                    'type' => $usernameField === 'email' ? 'email' : 'text',
                ]);
                echo $this->Form->control($passwordField, [
                    'label' => __d('brammo/admin', 'Password'),
                    'type' => 'password',
                ]);
                echo $this->Form->control($rememberMeField, [
                    'type' => 'checkbox', 
                    'label' => __d('brammo/admin', 'Remember me')
                ]);
                echo $this->Form->button(__d('brammo/admin', 'Sign in'), [
                    'class' => 'btn-primary w-100'
                ]); 
                echo $this->Form->end();
            ?></div>
        </div>
    </div>
</div>
