<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Element;

use Brammo\Admin\View\AppView;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Sortable element test case
 */
class SortableElementTest extends TestCase
{
    /**
     * @var \Brammo\Admin\View\AppView
     */
    protected AppView $View;

    /**
     * @var mixed
     */
    protected mixed $originalSortableConfig = null;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->View = new AppView();
        $this->originalSortableConfig = Configure::read('Admin.Sortable');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->originalSortableConfig !== null) {
            Configure::write('Admin.Sortable', $this->originalSortableConfig);
        } else {
            Configure::delete('Admin.Sortable');
        }

        unset($this->View, $this->originalSortableConfig);
        parent::tearDown();
    }

    /**
     * Test SortableJS CDN and plugin script are queued.
     *
     * @return void
     */
    public function testLoadsSortableScripts(): void
    {
        $this->View->element('Brammo/Admin.sortable');
        $script = $this->View->fetch('script');

        $this->assertStringContainsString('sortablejs@1.15.6', $script);
        $this->assertStringContainsString('sortable.js', $script);
    }

    /**
     * Test CDN URL can be overridden via Admin.Sortable.script.
     *
     * @return void
     */
    public function testScriptUrlCanBeConfigured(): void
    {
        Configure::write('Admin.Sortable.script', 'https://example.test/Sortable.min.js');

        $this->View->element('Brammo/Admin.sortable');
        $script = $this->View->fetch('script');

        $this->assertStringContainsString('https://example.test/Sortable.min.js', $script);
        $this->assertStringNotContainsString('sortablejs@1.15.6', $script);
    }

    /**
     * Test grip handle markup.
     *
     * @return void
     */
    public function testHandleRendersGrip(): void
    {
        $html = $this->View->element('Brammo/Admin.Sortable/handle');

        $this->assertStringContainsString('data-sortable-handle', $html);
        $this->assertStringContainsString('cursor: grab', $html);
        $this->assertStringContainsString('Drag to reorder', $html);
        $this->assertStringContainsString('grip-vertical', $html);
    }
}
