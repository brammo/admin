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
 * @property \Brammo\Admin\View\Helper\NavHelper $Nav
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

        // Load BootstrapUI additional helpers
        $this->addHelper('Table', ['className' => 'Brammo/BootstrapUI.Table']);
        $this->addHelper('Description', ['className' => 'Brammo/BootstrapUI.Description']);
        $this->addHelper('Card', ['className' => 'Brammo/BootstrapUI.Card']);
        $this->addHelper('Nav', ['className' => 'Brammo/BootstrapUI.Nav']);
        
        // Load custom Admin helpers
        $this->addHelper('Button', ['className' => 'Brammo/Admin.Button']);

        // Load Authentication Identity helper
        $this->addHelper('Authentication.Identity');
    }
}
