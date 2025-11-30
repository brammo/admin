<?php
declare(strict_types=1);

namespace Brammo\Admin\View\Helper;

use Cake\View\Helper;
use Cake\View\StringTemplateTrait;

/**
 * Nav Helper
 *
 * Renders Bootstrap 5 nav tabs or pills with JavaScript tab-switching behavior.
 * Supports both buttons (in-page panels) and links (navigational tabs).
 *
 * @property \BootstrapUI\View\Helper\HtmlHelper $Html
 */
class NavHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * List of helpers used by this helper
     *
     * @var array<string, array<string, string>>
     */
    protected array $helpers = [
        'Html' => ['className' => 'BootstrapUI.Html'],
    ];

    /**
     * Default config for the helper.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'type' => 'tabs', // 'tabs' or 'pills'
        'fade' => true,
        'fill' => false,
        'justified' => false,
        'vertical' => false,
        'templates' => [
            'nav' => '<ul{{attrs}}>{{content}}</ul>',
            'navItem' => '<li{{attrs}}>{{content}}</li>',
            'navButton' => '<button{{attrs}}>{{content}}</button>',
            'navLink' => '<a{{attrs}}>{{content}}</a>',
            'tabContent' => '<div{{attrs}}>{{content}}</div>',
            'tabPane' => '<div{{attrs}}>{{content}}</div>',
        ],
    ];

    /**
     * Default attributes for the templates
     *
     * @var array<string, array<string, string>>
     */
    protected array $_defaultAttributes = [
        'nav' => [
            'class' => 'nav',
            'role' => 'tablist',
        ],
        'navItem' => [
            'class' => 'nav-item',
            'role' => 'presentation',
        ],
        'navButton' => [
            'class' => 'nav-link',
            'type' => 'button',
            'role' => 'tab',
        ],
        'navLink' => [
            'class' => 'nav-link',
        ],
        'tabContent' => [
            'class' => 'tab-content',
        ],
        'tabPane' => [
            'class' => 'tab-pane',
            'role' => 'tabpanel',
            'tabindex' => '0',
        ],
    ];

    /**
     * The tabs collection
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $tabs = [];

    /**
     * The links collection (navigational tabs without panels)
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $links = [];

    /**
     * Add a tab with panel content
     *
     * Options:
     * - `icon`: Icon name to display before the title (uses Html->icon())
     * - `active`: Force this tab to be active (default: first tab is active)
     * - `disabled`: Disable this tab
     * - Any other options are used as HTML attributes for the button element
     *
     * @param string $id Unique identifier for the tab (used for panel ID)
     * @param string $title Tab title text
     * @param string $content Tab panel content
     * @param array<string, mixed> $options Options for the tab
     * @return $this
     */
    public function add(string $id, string $title, string $content, array $options = [])
    {
        $this->tabs[] = [
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Add a navigational link (no panel content)
     *
     * Options:
     * - `icon`: Icon name to display before the title (uses Html->icon())
     * - `active`: Mark this link as active
     * - `disabled`: Disable this link
     * - Any other options are used as HTML attributes for the link element
     *
     * @param string $title Link title text
     * @param string|array<string, mixed> $url Link URL (string or CakePHP URL array)
     * @param array<string, mixed> $options Options for the tab
     * @return $this
     */
    public function addLink(string $title, string|array $url, array $options = [])
    {
        $this->links[] = [
            'title' => $title,
            'url' => $url,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Render the nav and tab content
     *
     * Options:
     * - `type`: 'tabs' or 'pills' (overrides default)
     * - `fade`: Enable fade animation (overrides default)
     * - `fill`: Make nav items fill available width
     * - `justified`: Make nav items equal width
     * - `vertical`: Render nav vertically
     * - `navAttrs`: HTML attributes for the nav element
     * - `contentAttrs`: HTML attributes for the tab-content element
     *
     * @param array<string, mixed> $options Options for rendering
     * @return string
     */
    public function render(array $options = []): string
    {
        /** @var string $type */
        $type = $options['type'] ?? $this->getConfig('type');
        /** @var bool $fade */
        $fade = $options['fade'] ?? $this->getConfig('fade');
        /** @var bool $fill */
        $fill = $options['fill'] ?? $this->getConfig('fill');
        /** @var bool $justified */
        $justified = $options['justified'] ?? $this->getConfig('justified');
        /** @var bool $vertical */
        $vertical = $options['vertical'] ?? $this->getConfig('vertical');
        /** @var array<string, mixed> $navAttrs */
        $navAttrs = $options['navAttrs'] ?? [];
        /** @var array<string, mixed> $contentAttrs */
        $contentAttrs = $options['contentAttrs'] ?? [];

        $templater = $this->templater();

        // Build nav classes
        $navClasses = ['nav'];
        $navClasses[] = $type === 'pills' ? 'nav-pills' : 'nav-tabs';

        if ($fill) {
            $navClasses[] = 'nav-fill';
        }
        if ($justified) {
            $navClasses[] = 'nav-justified';
        }
        if ($vertical) {
            $navClasses[] = 'flex-column';
        }

        // Merge nav attributes
        $navAttributes = $this->mergeAttributes('nav', $navAttrs);
        $extraClass = isset($navAttrs['class']) ? ' ' . $navAttrs['class'] : '';
        $navAttributes['class'] = implode(' ', $navClasses) . $extraClass;

        // Render nav items
        $navItems = $this->renderNavItems($fade);

        // Render nav
        $nav = $templater->format('nav', [
            'attrs' => $templater->formatAttributes($navAttributes),
            'content' => $navItems,
        ]);

        // Render tab content (only if we have tabs with content)
        $tabContent = '';
        if (!empty($this->tabs)) {
            $tabContent = $this->renderTabContent($fade, $contentAttrs);
        }

        // Clear collections
        $this->tabs = [];
        $this->links = [];

        // For vertical layout, wrap in a flex container
        if ($vertical && !empty($tabContent)) {
            return '<div class="d-flex align-items-start">' . $nav . $tabContent . '</div>';
        }

        return $nav . $tabContent;
    }

    /**
     * Render only the nav (without tab content)
     *
     * Useful when you want to render just navigational links.
     *
     * @param array<string, mixed> $options Options for rendering
     * @return string
     */
    public function renderNav(array $options = []): string
    {
        /** @var string $type */
        $type = $options['type'] ?? $this->getConfig('type');
        /** @var bool $fill */
        $fill = $options['fill'] ?? $this->getConfig('fill');
        /** @var bool $justified */
        $justified = $options['justified'] ?? $this->getConfig('justified');
        /** @var bool $vertical */
        $vertical = $options['vertical'] ?? $this->getConfig('vertical');
        /** @var array<string, mixed> $navAttrs */
        $navAttrs = $options['navAttrs'] ?? [];

        $templater = $this->templater();

        // Build nav classes
        $navClasses = ['nav'];
        $navClasses[] = $type === 'pills' ? 'nav-pills' : 'nav-tabs';

        if ($fill) {
            $navClasses[] = 'nav-fill';
        }
        if ($justified) {
            $navClasses[] = 'nav-justified';
        }
        if ($vertical) {
            $navClasses[] = 'flex-column';
        }

        // Merge nav attributes
        $navAttributes = $this->mergeAttributes('nav', $navAttrs);
        $extraClass = isset($navAttrs['class']) ? ' ' . $navAttrs['class'] : '';
        $navAttributes['class'] = implode(' ', $navClasses) . $extraClass;

        // Render nav items (no fade for nav-only rendering)
        $navItems = $this->renderNavItems(false);

        // Clear collections
        $this->tabs = [];
        $this->links = [];

        return $templater->format('nav', [
            'attrs' => $templater->formatAttributes($navAttributes),
            'content' => $navItems,
        ]);
    }

    /**
     * Render nav items (buttons for tabs, links for navigation)
     *
     * @param bool $fade Whether fade is enabled
     * @return string
     */
    protected function renderNavItems(bool $fade): string
    {
        $templater = $this->templater();
        $items = [];
        $isFirstTab = true;

        // Render tab buttons
        foreach ($this->tabs as $tab) {
            /** @var string $id */
            $id = $tab['id'];
            /** @var string $title */
            $title = $tab['title'];
            /** @var array<string, mixed> $options */
            $options = $tab['options'];

            // Extract special options
            /** @var string|null $icon */
            $icon = $options['icon'] ?? null;
            /** @var bool $active */
            $active = $options['active'] ?? ($isFirstTab ? true : false);
            /** @var bool $disabled */
            $disabled = $options['disabled'] ?? false;
            unset($options['icon'], $options['active'], $options['disabled']);

            // Build button attributes
            $buttonAttrs = $this->mergeAttributes('navButton', $options);
            $buttonAttrs['data-bs-toggle'] = 'tab';
            $buttonAttrs['data-bs-target'] = '#' . $id;
            $buttonAttrs['aria-controls'] = $id;
            $buttonAttrs['aria-selected'] = $active ? 'true' : 'false';

            // Add active/disabled classes
            $buttonClasses = ['nav-link'];
            if ($active) {
                $buttonClasses[] = 'active';
            }
            if ($disabled) {
                $buttonClasses[] = 'disabled';
                $buttonAttrs['disabled'] = 'disabled';
                $buttonAttrs['tabindex'] = '-1';
                $buttonAttrs['aria-disabled'] = 'true';
            }
            $buttonAttrs['class'] = implode(' ', $buttonClasses);

            // Build title with icon
            $titleContent = $this->buildTitle($title, $icon);

            // Render button
            $button = $templater->format('navButton', [
                'attrs' => $templater->formatAttributes($buttonAttrs),
                'content' => $titleContent,
            ]);

            // Render nav item
            $itemAttrs = $this->mergeAttributes('navItem', []);
            $items[] = $templater->format('navItem', [
                'attrs' => $templater->formatAttributes($itemAttrs),
                'content' => $button,
            ]);

            $isFirstTab = false;
        }

        // Render navigational links
        foreach ($this->links as $link) {
            /** @var string $title */
            $title = $link['title'];
            /** @var string|array<string, mixed> $url */
            $url = $link['url'];
            /** @var array<string, mixed> $options */
            $options = $link['options'];

            // Extract special options
            /** @var string|null $icon */
            $icon = $options['icon'] ?? null;
            /** @var bool $active */
            $active = $options['active'] ?? false;
            /** @var bool $disabled */
            $disabled = $options['disabled'] ?? false;
            unset($options['icon'], $options['active'], $options['disabled']);

            // Build link attributes
            $linkAttrs = $this->mergeAttributes('navLink', $options);
            $linkAttrs['href'] = is_array($url) ? $this->Html->Url->build($url) : $url;

            // Add active/disabled classes
            $linkClasses = ['nav-link'];
            if ($active) {
                $linkClasses[] = 'active';
                $linkAttrs['aria-current'] = 'page';
            }
            if ($disabled) {
                $linkClasses[] = 'disabled';
                $linkAttrs['tabindex'] = '-1';
                $linkAttrs['aria-disabled'] = 'true';
            }
            $linkAttrs['class'] = implode(' ', $linkClasses);

            // Build title with icon
            $titleContent = $this->buildTitle($title, $icon);

            // Render link
            $linkHtml = $templater->format('navLink', [
                'attrs' => $templater->formatAttributes($linkAttrs),
                'content' => $titleContent,
            ]);

            // Render nav item
            $itemAttrs = $this->mergeAttributes('navItem', []);
            unset($itemAttrs['role']); // No role for link items
            $items[] = $templater->format('navItem', [
                'attrs' => $templater->formatAttributes($itemAttrs),
                'content' => $linkHtml,
            ]);
        }

        return implode("\n", $items);
    }

    /**
     * Render tab content panels
     *
     * @param bool $fade Whether fade is enabled
     * @param array<string, mixed> $contentAttrs Attributes for tab-content wrapper
     * @return string
     */
    protected function renderTabContent(bool $fade, array $contentAttrs = []): string
    {
        $templater = $this->templater();
        $panes = [];
        $isFirstTab = true;

        foreach ($this->tabs as $tab) {
            /** @var string $id */
            $id = $tab['id'];
            /** @var string $content */
            $content = $tab['content'];
            /** @var array<string, mixed> $options */
            $options = $tab['options'];

            // Check if this tab is active
            /** @var bool $active */
            $active = $options['active'] ?? ($isFirstTab ? true : false);

            // Build pane attributes
            $paneAttrs = $this->mergeAttributes('tabPane', []);
            $paneAttrs['id'] = $id;
            $paneAttrs['aria-labelledby'] = $id . '-tab';

            // Build pane classes
            $paneClasses = ['tab-pane'];
            if ($fade) {
                $paneClasses[] = 'fade';
            }
            if ($active) {
                $paneClasses[] = 'show';
                $paneClasses[] = 'active';
            }
            $paneAttrs['class'] = implode(' ', $paneClasses);

            // Render pane
            $panes[] = $templater->format('tabPane', [
                'attrs' => $templater->formatAttributes($paneAttrs),
                'content' => $content,
            ]);

            $isFirstTab = false;
        }

        // Render tab-content wrapper
        $wrapperAttrs = $this->mergeAttributes('tabContent', $contentAttrs);

        return $templater->format('tabContent', [
            'attrs' => $templater->formatAttributes($wrapperAttrs),
            'content' => implode("\n", $panes),
        ]);
    }

    /**
     * Build title with optional icon
     *
     * @param string $title The title text
     * @param string|null $icon Optional icon name
     * @return string
     */
    protected function buildTitle(string $title, ?string $icon): string
    {
        if ($icon === null) {
            return $title;
        }

        return $this->Html->icon($icon) . ' ' . $title;
    }

    /**
     * Merge default attributes with provided attributes
     *
     * @param string $template The template name
     * @param array<string, mixed> $attrs The attributes to merge
     * @return array<string, mixed>
     */
    protected function mergeAttributes(string $template, array $attrs): array
    {
        $defaults = $this->_defaultAttributes[$template] ?? [];

        return $attrs + $defaults;
    }
}
