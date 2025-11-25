<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller;

use Brammo\Admin\View\AppView;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Core\Configure;
use Cake\I18n\I18n;
/**
 * Admin Application Controller
 */
class AppController extends Controller
{
    /**
     * Initialization
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // Load components
        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');

        // Set view class
        $this->viewBuilder()->setClassName(AppView::class);

        // Set default layout
        $this->viewBuilder()->setLayout('Brammo/Admin.default');
    }

    /**
     * Called before the controller action
     * 
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // Set default language
        $defaultLang = Configure::read('Admin.I18n.default');
        if ($defaultLang) {
            I18n::setLocale($defaultLang);
        }
    }
}
