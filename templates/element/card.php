<?php
/**
 * Card element
 * 
 * @var \Cake\View\View $this
 * @var string $header
 * @var string $body
 * @var string $footer
 */

$content = '';

if (isset($header)) {
    $content = $this->Html->div(join(' ', (array)$headerClass), $header);
}

if (isset($body)) {
    $content = $this->Html->div(join(' ', (array)$bodyClass), $body);
}

if (isset($footer)) {
    $content = $this->Html->div(join(' ', (array)$footerClass), $footer);
}

echo $this->Html->div(join(' ', (array)$class), $content);
