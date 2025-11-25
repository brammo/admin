<?php
/**
 * Sidebar brand element
 * 
 * @var \Admin\View\AppView $this
 */
use Cake\Core\Configure;

echo $this->Html->link(
    Configure::read('Admin.Brand.html'), 
    '/admin',
    [
        'escape' => false, 
        'class' => 'sidebar-brand'
    ]
);
