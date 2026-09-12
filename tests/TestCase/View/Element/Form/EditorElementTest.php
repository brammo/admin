<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Element\Form;

use Brammo\Admin\View\AppView;
use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Form editor element test case
 */
class EditorElementTest extends TestCase
{
    /**
     * View instance
     *
     * @var \Brammo\Admin\View\AppView
     */
    protected AppView $View;

    /**
     * Editor config snapshot for tearDown
     *
     * @var array<string, mixed>|null
     */
    protected ?array $originalEditorConfig = null;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Router::reload();
        $routes = Router::createRouteBuilder('/');
        $routes->plugin(
            'Brammo/Admin',
            ['path' => '/admin'],
            function (RouteBuilder $routeBuilder): void {
                $routeBuilder->fallbacks();
            },
        );

        $this->View = new AppView();
        $this->originalEditorConfig = Configure::read('Admin.Editor');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->originalEditorConfig !== null) {
            Configure::write('Admin.Editor', $this->originalEditorConfig);
        } else {
            Configure::delete('Admin.Editor');
        }

        unset($this->View, $this->originalEditorConfig);
        Router::reload();
        parent::tearDown();
    }

    /**
     * Render the editor element and return the script block contents.
     *
     * @return string
     */
    protected function renderEditorScript(): string
    {
        $this->View->element('Brammo/Admin.Form/editor');

        return $this->View->fetch('script');
    }

    /**
     * Test cleanOnPaste defaults to true in the emitted script
     *
     * @return void
     */
    public function testCleanOnPasteDefaultsToTrue(): void
    {
        Configure::write('Admin.Editor', [
            'height' => 500,
        ]);

        $script = $this->renderEditorScript();

        $this->assertStringContainsString('const cleanOnPaste = true;', $script);
        $this->assertStringContainsString('cleanOnPaste: cleanOnPaste', $script);
        $this->assertStringContainsString('Clear formatting', $script);
    }

    /**
     * Test cleanOnPaste can be disabled via Admin.Editor config
     *
     * @return void
     */
    public function testCleanOnPasteCanBeDisabled(): void
    {
        Configure::write('Admin.Editor', [
            'height' => 500,
            'cleanOnPaste' => false,
        ]);

        $script = $this->renderEditorScript();

        $this->assertStringContainsString('const cleanOnPaste = false;', $script);
    }

    /**
     * Test statusBar defaults to true in the emitted script
     *
     * @return void
     */
    public function testStatusBarDefaultsToTrue(): void
    {
        Configure::write('Admin.Editor', [
            'height' => 500,
        ]);

        $script = $this->renderEditorScript();

        $this->assertStringContainsString('const statusBar = true;', $script);
        $this->assertStringContainsString('statusBar: statusBar', $script);
        $this->assertStringContainsString('Element path', $script);
    }

    /**
     * Test statusBar can be disabled via Admin.Editor config
     *
     * @return void
     */
    public function testStatusBarCanBeDisabled(): void
    {
        Configure::write('Admin.Editor', [
            'height' => 500,
            'statusBar' => false,
        ]);

        $script = $this->renderEditorScript();

        $this->assertStringContainsString('const statusBar = false;', $script);
    }
}
