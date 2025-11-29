<?php
declare(strict_types=1);

namespace Brammo\Admin\View\Helper;

use Cake\View\Helper;
use Cake\View\StringTemplateTrait;

/**
 * Description Helper
 */
class DescriptionHelper extends Helper
{
    use StringTemplateTrait;

    /**
     * Default config for the helper.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'templates' => [
            'list' => '<dl{{attrs}}>{{content}}</dl>',
            'term' => '<dt{{attrs}}>{{content}}</dt>',
            'definition' => '<dd{{attrs}}>{{content}}</dd>',
        ],
    ];

    /**
     * The list
     *
     * @var array<int, array{0: string, 1: string}>
     */
    protected array $rows = [];

    /**
     * Add a row
     *
     * @param string $term The term/label
     * @param string $definition The definition/value
     * @return $this
     */
    public function add(string $term, string $definition)
    {
        $this->rows[] = [$term, $definition];

        return $this;
    }

    /**
     * Render the description list
     * 
     * Options:
     * - `list`: HTML attributes for the list element
     * 
     * @param array<string, mixed> $options
     * @return string
     */
    public function render(array $options = []): string
    {
        $templater = $this->templater();

        $content = '';
        foreach ($this->rows as [$term, $definition]) {
            $content .= $templater->format('term', ['content' => $term]);
            $content .= $templater->format('definition', ['content' => $definition]);
        }

        /** @var array<string, mixed> $listAttrs */
        $listAttrs = isset($options['list']) && is_array($options['list']) ? $options['list'] : [];
        $content = $templater->format('list', [
            'attrs' => $templater->formatAttributes($listAttrs),
            'content' => $content,
        ]);

        $this->rows = [];

        return $content;
    }
}
