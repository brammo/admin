<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Controller;

use Brammo\Admin\Controller\AppController;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Brammo\Admin\Controller\AppController Test Case
 */
class AppControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Subject under test
     *
     * @var \Brammo\Admin\Controller\AppController
     */
    protected AppController $AppController;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $request = new \Cake\Http\ServerRequest();
        $this->AppController = new AppController($request);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->AppController);
        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->AppController->initialize();

        // Check that Flash component is loaded
        $this->assertTrue($this->AppController->components()->has('Flash'));

        // Check that Authentication component is loaded
        $this->assertTrue($this->AppController->components()->has('Authentication'));

        // Check view class is set
        $viewClass = $this->AppController->viewBuilder()->getClassName();
        $this->assertEquals('Brammo\Admin\View\AppView', $viewClass);

        // Check layout is set
        $layout = $this->AppController->viewBuilder()->getLayout();
        $this->assertEquals('Brammo/Admin.default', $layout);
    }

    /**
     * Test beforeFilter sets locale when configured
     *
     * @return void
     */
    public function testBeforeFilterSetsLocale(): void
    {
        Configure::write('Admin.I18n.default', 'bg');

        $this->AppController->initialize();
        $this->AppController->beforeFilter(
            new \Cake\Event\Event('Controller.beforeFilter', $this->AppController)
        );

        $this->assertEquals('bg', I18n::getLocale());
    }

    /**
     * Test beforeFilter without configured locale
     *
     * @return void
     */
    public function testBeforeFilterWithoutConfiguredLocale(): void
    {
        Configure::write('Admin.I18n.default', null);
        $originalLocale = I18n::getLocale();

        $this->AppController->initialize();
        $this->AppController->beforeFilter(
            new \Cake\Event\Event('Controller.beforeFilter', $this->AppController)
        );

        // Locale should remain unchanged
        $this->assertEquals($originalLocale, I18n::getLocale());
    }
}
