<?php
/**
 * Pagination element
 * 
 * Uses BootstrapUI pagination with icons.
 * 
 * @var Brammo\Admin\View\AppView $this
 */

echo $this->Paginator->links([
    'first' => $this->Html->icon('chevron-double-left'),
    'prev' => $this->Html->icon('chevron-left'),
    'next' => $this->Html->icon('chevron-right'),
    'last' => $this->Html->icon('chevron-double-right'),
    'escape' => false
]);
