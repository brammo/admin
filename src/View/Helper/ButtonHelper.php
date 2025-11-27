<?php
declare(strict_types=1);

namespace Brammo\Admin\View\Helper;

use Cake\View\Helper;
use function Cake\Core\h;
use function Cake\I18n\__d;

/**
 * Button Helper
 *
 * @package Brammo\Admin\View\Helper
 * @property \BootstrapUI\View\Helper\HtmlHelper $Html
 * @property \BootstrapUI\View\Helper\FormHelper $Form
 */
class ButtonHelper extends Helper
{
    /**
     * List of helpers used by this helper
     *
     * @var array<array-key, mixed>
     */
    protected array $helpers = [
        'Html' => ['className' => 'BootstrapUI.Html'],
        'Form' => ['className' => 'BootstrapUI.Form'],
    ];

    /**
     * Renders a button
     *
     * ### Options
     * - `method`: Link method 'get' or 'post' (default: 'get')
     * - `variant`: Button variant (default: 'secondary')
     * - `icon`: Icon name
     * - `size`: Button size (default: '')
     * - `style`: 'compact' or 'noraml' (default: 'noraml')
     * - `confirm`: confirmation message for post links
     *
     * @param string $title Title
     * @param array<string, mixed>|string $url URL
     * @param array<string, mixed> $options Options
     * @return string HTML code
     */
    public function render(string $title, array|string $url, array $options = []): string
    {
        $defaults = [
            'method' => 'get',
            'variant' => 'secondary',
            'icon' => '',
            'size' => '',
            'style' => 'normal',
            'confirm' => '',
        ];
        $options += $defaults;

        $method = $options['method'];
        $variant = $options['variant'];
        $icon = $options['icon'];
        $size = $options['size'];
        $style = $options['style'];
        $confirm = $options['confirm'];

        // TODO: check options

        $htmlOptions = [
            'class' => 'btn btn-' . $variant,
        ];

        if ($style == 'compact') {
            $htmlOptions['title'] = $title;
            $title = '';
        }

        if ($icon) {
            $title = $this->Html->icon($icon) . ' ' . h($title);
            $htmlOptions['escape'] = false;
        }

        if ($size) {
            $htmlOptions['class'] .= ' btn-' . $size;
        }

        if ($confirm) {
            $htmlOptions['confirm'] = $confirm;
        }

        if ($method == 'post') {
            return $this->Form->postLink($title, $url, $htmlOptions);
        } else {
            return $this->Html->link($title, $url, $htmlOptions);
        }
    }

    /**
     * Renders a button link
     *
     * @param string $title Title
     * @param array<string, mixed>|string $url URL
     * @param array<string, mixed> $options Options
     * @return string HTML code
     */
    public function link(string $title, array|string $url, array $options = []): string
    {
        return $this->render($title, $url, ['method' => 'get'] + $options);
    }

    /**
     * Renders a button post link
     *
     * @param string $title Title
     * @param array<string, mixed>|string $url URL
     * @param array<string, mixed> $options Options
     * @return string HTML code
     */
    public function postLink(string $title, array|string $url, array $options = []): string
    {
        return $this->render($title, $url, ['method' => 'post'] + $options);
    }

    /**
     * Renders a create button
     *
     * @param array<string, mixed>|string $url URL
     * @param array<string, mixed> $options Options
     * @return string HTML code
     */
    public function create(array|string $url, array $options = []): string
    {
        return $this->render(__d('brammo/admin', 'Create'), $url, $options + [
            'variant' => 'success',
            'icon' => 'plus-circle',
        ]);
    }

    /**
     * Renders an edit button
     *
     * @param array<string, mixed>|string $url URL
     * @param array<string, mixed> $options Options
     * @return string HTML code
     */
    public function edit(array|string $url, array $options = []): string
    {
        return $this->render(__d('brammo/admin', 'Edit'), $url, $options + [
            'variant' => 'primary',
            'icon' => 'pencil',
        ]);
    }

    /**
     * Renders a delete button
     *
     * @param array<string, mixed>|string $url URL
     * @param array<string, mixed> $options Options
     * @return string HTML code
     */
    public function delete(array|string $url, array $options = []): string
    {
        return $this->render(__d('brammo/admin', 'Delete'), $url, $options + [
            'method' => 'post',
            'variant' => 'danger',
            'icon' => 'trash',
            'confirm' => __d('brammo/admin', 'Are you sure you want to delete?'),
        ]);
    }
}
