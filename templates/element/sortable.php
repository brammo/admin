<?php
/**
 * SortableJS loader for [data-sortable] grids
 *
 * @var \Brammo\Admin\View\AppView $this
 */

use Cake\Core\Configure;

$script = Configure::read('Admin.Sortable.script');
if (!is_string($script) || $script === '') {
    $script = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js';
}

$this->Html->script($script, ['block' => true]);
$this->Html->script('Brammo/Admin.sortable', ['block' => true]);
