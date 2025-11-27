<?php
declare(strict_types=1);

namespace Brammo\Admin\View;

use Cake\View\View;

/**
 * Application View
 *
 * @property \Authentication\View\Helper\IdentityHelper $Identity
 * @property \BootstrapUI\View\Helper\BreadcrumbsHelper $Breadcrumbs
 * @property \BootstrapUI\View\Helper\HtmlHelper $Html
 * @property \BootstrapUI\View\Helper\FormHelper $Form
 * @property \BootstrapUI\View\Helper\FlashHelper $Flash
 * @property \BootstrapUI\View\Helper\PaginatorHelper $Paginator
 */
class AppView extends View
{
    /**
     * Initialization
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // Load BootstrapUI helpers
        $this->addHelper('Html', ['className' => 'BootstrapUI.Html']);
        $this->addHelper('Form', ['className' => 'BootstrapUI.Form']);
        $this->addHelper('Flash', ['className' => 'BootstrapUI.Flash']);
        $this->addHelper('Paginator', ['className' => 'BootstrapUI.Paginator']);
        $this->addHelper('Breadcrumbs', ['className' => 'BootstrapUI.Breadcrumbs']);

        // Load Authentication Identity helper
        $this->addHelper('Authentication.Identity');

        // Load custom Admin helpers
        $this->addHelper('Button', ['className' => 'Brammo/Admin.Button']);
        $this->addHelper('Table', ['className' => 'Brammo/Admin.Table']);
        $this->addHelper('Description', ['className' => 'Brammo/Admin.Description']);
        $this->addHelper('Card', ['className' => 'Brammo/Admin.Card']);
    }
}
