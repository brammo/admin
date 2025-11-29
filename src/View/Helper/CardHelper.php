<?php
declare(strict_types=1);

namespace Brammo\Admin\View\Helper;

use Cake\View\Helper;
use Cake\View\StringTemplateTrait;

/**
 * Card Helper
 * 
 * @property \BootstrapUI\View\Helper\HtmlHelper $Html
 */
class CardHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * Default config for the helper.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'templates' => [
            'card' => '<div{{attrs}}>{{content}}</div>',
            'header' => '<div{{attrs}}>{{content}}</div>',
            'body' => '<div{{attrs}}>{{content}}</div>',
            'footer' => '<div{{attrs}}>{{content}}</div>',
        ],
    ];

    /**
     * Default attributes for the templates
     *
     * @var array<string, array<string, string>>
     */
    protected array $_defaultAttributes = [
        'card' => [
            'class' => 'card',
        ],
        'header' => [
            'class' => 'card-header',
        ],
        'body' => [
            'class' => 'card-body',
        ],
        'footer' => [
            'class' => 'card-footer',
        ],
    ];

    /**
     * Render a card
     * 
     * Options:
     * - `header`: Optional header content for the card
     * - `footer`: Optional footer content for the card
     * - `headerAttrs`: HTML attributes for the header element
     * - `bodyAttrs`: HTML attributes for the body element
     * - `footerAttrs`: HTML attributes for the footer element
     * - Any other options are used as HTML attributes for the card element
     * 
     * @param string $body The card body content
     * @param array<string, mixed> $options Options for rendering the card
     * @return string
     */
    public function render(string $body, array $options = []): string
    {
        $templater = $this->templater();

        $header = $options['header'] ?? null;
        $footer = $options['footer'] ?? null;
        unset($options['header'], $options['footer']);

        $content = '';

        if ($header !== null) {
            $headerAttrs = $this->mergeAttributes('header', $options['headerAttrs'] ?? []);
            unset($options['headerAttrs']);
            $content .= $templater->format('header', [
                'attrs' => $templater->formatAttributes($headerAttrs),
                'content' => $header,
            ]);
        }

        $bodyAttrs = $this->mergeAttributes('body', $options['bodyAttrs'] ?? []);
        unset($options['bodyAttrs']);
        $content .= $templater->format('body', [
            'attrs' => $templater->formatAttributes($bodyAttrs),
            'content' => $body,
        ]);

        if ($footer !== null) {
            $footerAttrs = $this->mergeAttributes('footer', $options['footerAttrs'] ?? []);
            unset($options['footerAttrs']);
            $content .= $templater->format('footer', [
                'attrs' => $templater->formatAttributes($footerAttrs),
                'content' => $footer,
            ]);
        }

        $cardAttrs = $this->mergeAttributes('card', $options);

        return $templater->format('card', [
            'attrs' => $templater->formatAttributes($cardAttrs),
            'content' => $content,
        ]);
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
