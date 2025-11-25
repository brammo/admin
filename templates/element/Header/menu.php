<?php
/**
 * Admin Header Menu element
 * 
 * @var \Admin\View\AppView $this
 */

$name = $this->Identity->get('name');
?>
<ul class="navbar-nav ms-auto">
    <li class="nav-item">
        <button id="theme-toggle" class="nav-link" type="button" aria-label="<?= __d('brammo/admin', 'Toggle Theme') ?>">
            <i id="theme-icon-light" class="bi bi-sun-fill"></i>
            <i id="theme-icon-dark" class="bi bi-moon-fill d-none"></i>
        </button>
    </li>
    <li class="nav-item dropdown">
        <button class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <?= $this->Html->icon('person-circle') ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end position-absolute">
            <li>
                <?= $this->Html->link(
                        __d('brammo/admin', 'Profile'), 
                        ['plugin' => 'brammo/admin', 'controller' => 'User', 'action' => 'profile'],
                        ['class' => 'dropdown-item']
                    ) 
                ?>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <?= $this->Html->link(
                        __d('brammo/admin', 'Log out'), 
                        ['plugin' => 'Brammo/Auth', 'controller' => 'User', 'action' => 'logout'],
                        ['class' => 'dropdown-item']
                    ) 
                ?>
            </li>
        </ul>
    </li>
</ul>
