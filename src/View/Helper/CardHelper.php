<?php
declare(strict_types=1);

namespace Brammo\Admin\View\Helper;

use Cake\View\Helper;
use Cake\View\StringTemplateTrait;

/**
 * Card Helper
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
        'class' => ['card'],
        'headerClass' => ['card-header'],
        'bodyClass' => ['card-body'],
        'footerClass' => ['card-footer'],
        'element' => 'Brammo/Admin.card'
    ];

    /**
     * @param string $body
     * @param array<string, mixed> $options
     * @return string
     */
    public function render(string $body, array $options = []): string
    {
        $params = $options + $this->_defaultConfig;
        $params['body'] = $body;

        return $this->_View->element($params['element'], $params);
    }
}
