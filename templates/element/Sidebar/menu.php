<?php
/**
 * Sidebar menu element
 * 
 * @var Brammo\Admin\View\AppView $this
 */

use Cake\Core\Configure;

$menu = Configure::read('Admin.Sidebar.menu');

$iconDefaults = Configure::read('Admin.Sidebar.iconDefaults');

$currentController = $this->request->getParam('controller');
?>
<ul class="sidebar-menu nav nav-pills">
    <?php foreach ($menu as $name => $item): ?>
        <?php
        $title = $item['title'] ?? __d('admin', $name);
        
        $icon = null;
        if (isset($item['icon'])) {
            if (is_array($item['icon'])) {
                $iconOptions = $item['icon'];
                $iconName = $iconOptions['name'];
                unset($iconOptions['name']);
            } else {
                $iconOptions = [];
                $iconName = $item['icon'];
            }
            $iconOptions += $iconDefaults;
            $iconOptions += ['class' => 'icon'];
            $icon = $this->Html->icon($iconName, $iconOptions);
        }
        
        $hasSubmenu = isset($item['submenu']);
        $isSubmenuActive = false;
        if ($hasSubmenu) {
            foreach ($item['submenu'] as $subName => $subItem) {
                $subController = $subItem['url']['controller'] ?? $subName;
                if ($subController == $currentController) {
                    $isSubmenuActive = true;
                    break;
                }
            }
        }
        ?>
        <li class="nav-item<?= $hasSubmenu ? ' has-submenu' : '' ?><?= $isSubmenuActive ? ' submenu-open' : '' ?>">
            <?php if ($hasSubmenu): ?>
                <a href="#" class="nav-link submenu-toggle" data-submenu-target="<?= h($name) ?>">
                    <?= $icon ?><span><?= h($title) ?></span>
                    <?= $this->Html->icon('chevron-down', ['class' => 'submenu-angle']) ?>
                </a>
                <ul class="submenu nav nav-pills" id="submenu-<?= h($name) ?>">
                    <?php foreach ($item['submenu'] as $subName => $subItem): ?>
                        <li class="nav-item">
                            <?php
                            $subIcon = '';
                            if (isset($subItem['icon'])) {
                                if (is_array($subItem['icon'])) {
                                    $subIconOptions = $subItem['icon'];
                                    $subIconName = $subIconOptions['name'];
                                    unset($subIconOptions['name']);
                                } else {
                                    $subIconOptions = [];
                                    $subIconName = $subItem['icon'];
                                }
                                $subIconOptions += $iconDefaults;
                                $subIconOptions += ['class' => 'icon'];
                                $subIcon = $this->Html->icon($subIconName, $subIconOptions);
                            }
                            $subController = $subItem['url']['controller'] ?? $subName;
                            $subOptions = [
                                'escape' => false,
                                'class' => 'nav-link'
                            ];
                            if ($subController == $currentController) {
                                $subOptions['class'] .= ' active';
                            }
                            echo $this->Html->link(
                                $subIcon . '<span>' . h($subItem['title'] ?? __d('admin', $subName)) . '</span>', 
                                $subItem['url'] ?? [
                                    'plugin' => 'Admin',
                                    'controller' => $subController, 
                                    'action' => 'index'
                                ],
                                $subOptions
                            );
                            ?>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php else: ?>
                <?php
                $controller = $item['url']['controller'] ?? $name;
                $options = [
                    'escape' => false,
                    'class' => 'nav-link'
                ];
                if ($controller == $currentController) {
                    $options['class'] .= ' active';
                }
                echo $this->Html->link(
                    $icon . '<span>' . h($title) . '</span>', 
                    $item['url'] ?? [
                        'plugin' => 'Admin',
                        'controller' => $controller, 
                        'action' => 'index'
                    ], 
                    $options
                );
                ?>
            <?php endif ?>
        </li>
    <?php endforeach ?>
</ul>
