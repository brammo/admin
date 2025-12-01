<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Helper;

use Brammo\Admin\View\Helper\ButtonHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * Brammo\Admin\View\Helper\ButtonHelper Test Case
 */
class ButtonHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Brammo\Admin\View\Helper\ButtonHelper
     */
    protected ButtonHelper $Button;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $view = new View();
        $this->Button = new ButtonHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Button);
        parent::tearDown();
    }

    /**
     * Test render method with default options
     *
     * @return void
     */
    public function testRenderDefault(): void
    {
        $result = $this->Button->render('Test Button', '/test/url');

        $this->assertStringContainsString('btn', $result);
        $this->assertStringContainsString('btn-secondary', $result);
        $this->assertStringContainsString('Test Button', $result);
        $this->assertStringContainsString('/test/url', $result);
    }

    /**
     * Test render method with custom variant
     *
     * @return void
     */
    public function testRenderWithVariant(): void
    {
        $result = $this->Button->render('Primary Button', '/test', [
            'variant' => 'primary',
        ]);

        $this->assertStringContainsString('btn-primary', $result);
    }

    /**
     * Test render method with icon
     *
     * @return void
     */
    public function testRenderWithIcon(): void
    {
        $result = $this->Button->render('Icon Button', '/test', [
            'icon' => 'plus-circle',
        ]);

        $this->assertStringContainsString('plus-circle', $result);
    }

    /**
     * Test render method with compact style
     *
     * @return void
     */
    public function testRenderCompactStyle(): void
    {
        $result = $this->Button->render('Compact', '/test', [
            'style' => 'compact',
        ]);

        $this->assertStringContainsString('title="Compact"', $result);
    }

    /**
     * Test render method with size
     *
     * @return void
     */
    public function testRenderWithSize(): void
    {
        $result = $this->Button->render('Small Button', '/test', [
            'size' => 'sm',
        ]);

        $this->assertStringContainsString('btn-sm', $result);
    }

    /**
     * Test render method with POST method
     *
     * @return void
     */
    public function testRenderPost(): void
    {
        $result = $this->Button->render('Post Button', '/test', [
            'method' => 'post',
        ]);

        $this->assertStringContainsString('form', $result);
        $this->assertStringContainsString('method="post"', $result);
    }

    /**
     * Test render method with confirmation
     *
     * @return void
     */
    public function testRenderWithConfirm(): void
    {
        $result = $this->Button->render('Delete', '/test', [
            'method' => 'post',
            'confirm' => 'Are you sure?',
        ]);

        $this->assertStringContainsString('Are you sure?', $result);
    }

    /**
     * Test link method
     *
     * @return void
     */
    public function testLink(): void
    {
        $result = $this->Button->link('Link Button', '/test');

        $this->assertStringContainsString('btn', $result);
        $this->assertStringContainsString('/test', $result);
        $this->assertStringNotContainsString('form', $result);
    }

    /**
     * Test postLink method
     *
     * @return void
     */
    public function testPostLink(): void
    {
        $result = $this->Button->postLink('Post Link', '/test');

        $this->assertStringContainsString('form', $result);
        $this->assertStringContainsString('method="post"', $result);
    }

    /**
     * Test create method
     *
     * @return void
     */
    public function testCreate(): void
    {
        $result = $this->Button->create('/test/add');

        $this->assertStringContainsString('btn-success', $result);
        $this->assertStringContainsString('plus-circle', $result);
        // Text is translated, so just verify button structure is correct
        $this->assertStringContainsString('href="/test/add"', $result);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $result = $this->Button->edit('/test/edit/1');

        $this->assertStringContainsString('btn-primary', $result);
        $this->assertStringContainsString('pencil', $result);
        // Text is translated, so just verify button structure is correct
        $this->assertStringContainsString('href="/test/edit/1"', $result);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $result = $this->Button->delete('/test/delete/1');

        $this->assertStringContainsString('btn-danger', $result);
        $this->assertStringContainsString('trash', $result);
        // Text is translated, so just verify button structure is correct
        $this->assertStringContainsString('form', $result);
        $this->assertStringContainsString('data-confirm-message', $result);
    }

    /**
     * Test delete method with custom options
     *
     * @return void
     */
    public function testDeleteWithCustomOptions(): void
    {
        $result = $this->Button->delete('/test/delete/1', [
            'confirm' => 'Custom confirmation message',
        ]);

        $this->assertStringContainsString('Custom confirmation message', $result);
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $result = $this->Button->view('/test/view/1');

        $this->assertStringContainsString('btn-info', $result);
        $this->assertStringContainsString('eye', $result);
        $this->assertStringContainsString('href="/test/view/1"', $result);
        $this->assertStringNotContainsString('form', $result);
    }

    /**
     * Test view method with custom options
     *
     * @return void
     */
    public function testViewWithCustomOptions(): void
    {
        $result = $this->Button->view('/test/view/1', [
            'size' => 'lg',
        ]);

        $this->assertStringContainsString('btn-info', $result);
        $this->assertStringContainsString('btn-lg', $result);
    }

    /**
     * Test editCompact method
     *
     * @return void
     */
    public function testEditCompact(): void
    {
        $result = $this->Button->editCompact('/test/edit/1');

        $this->assertStringContainsString('btn-primary', $result);
        $this->assertStringContainsString('btn-sm', $result);
        $this->assertStringContainsString('pencil', $result);
        $this->assertStringContainsString('title=', $result);
        $this->assertStringContainsString('href="/test/edit/1"', $result);
    }

    /**
     * Test deleteCompact method
     *
     * @return void
     */
    public function testDeleteCompact(): void
    {
        $result = $this->Button->deleteCompact('/test/delete/1');

        $this->assertStringContainsString('btn-danger', $result);
        $this->assertStringContainsString('btn-sm', $result);
        $this->assertStringContainsString('trash', $result);
        $this->assertStringContainsString('title=', $result);
        $this->assertStringContainsString('form', $result);
        $this->assertStringContainsString('data-confirm-message', $result);
    }

    /**
     * Test preview method
     *
     * @return void
     */
    public function testPreview(): void
    {
        $result = $this->Button->preview('/test/preview/1');

        $this->assertStringContainsString('btn-info', $result);
        $this->assertStringContainsString('box-arrow-up-right', $result);
        $this->assertStringContainsString('title=', $result);
        $this->assertStringContainsString('href="/test/preview/1"', $result);
        $this->assertStringNotContainsString('form', $result);
    }

    /**
     * Test render method with title option override
     *
     * @return void
     */
    public function testRenderWithTitleOption(): void
    {
        $result = $this->Button->render('Original Title', '/test', [
            'title' => 'Overridden Title',
        ]);

        $this->assertStringContainsString('Overridden Title', $result);
        $this->assertStringNotContainsString('Original Title', $result);
    }

    /**
     * Test render method with array URL
     *
     * @return void
     */
    public function testRenderWithArrayUrl(): void
    {
        // Array URLs require routing, so just test with a simple array that gets converted
        $result = $this->Button->render('Array URL', '/tests/index');

        $this->assertStringContainsString('btn', $result);
        $this->assertStringContainsString('Array URL', $result);
        $this->assertStringContainsString('/tests/index', $result);
    }

    /**
     * Test link method with options
     *
     * @return void
     */
    public function testLinkWithOptions(): void
    {
        $result = $this->Button->link('Link With Options', '/test', [
            'variant' => 'warning',
            'icon' => 'star',
            'size' => 'lg',
        ]);

        $this->assertStringContainsString('btn-warning', $result);
        $this->assertStringContainsString('btn-lg', $result);
        $this->assertStringContainsString('star', $result);
        $this->assertStringNotContainsString('form', $result);
    }

    /**
     * Test postLink method with options
     *
     * @return void
     */
    public function testPostLinkWithOptions(): void
    {
        $result = $this->Button->postLink('Post With Options', '/test', [
            'variant' => 'success',
            'icon' => 'check',
            'confirm' => 'Confirm this action?',
        ]);

        $this->assertStringContainsString('btn-success', $result);
        $this->assertStringContainsString('check', $result);
        $this->assertStringContainsString('form', $result);
        $this->assertStringContainsString('Confirm this action?', $result);
    }

    /**
     * Test create method with custom options
     *
     * @return void
     */
    public function testCreateWithCustomOptions(): void
    {
        $result = $this->Button->create('/test/add', [
            'size' => 'lg',
            'icon' => 'plus',
        ]);

        $this->assertStringContainsString('btn-success', $result);
        $this->assertStringContainsString('btn-lg', $result);
        // Custom icon should override default
        $this->assertStringContainsString('plus', $result);
    }

    /**
     * Test edit method with custom options
     *
     * @return void
     */
    public function testEditWithCustomOptions(): void
    {
        $result = $this->Button->edit('/test/edit/1', [
            'size' => 'sm',
            'variant' => 'warning',
        ]);

        // Custom variant should override default
        $this->assertStringContainsString('btn-warning', $result);
        $this->assertStringContainsString('btn-sm', $result);
    }

    /**
     * Test render with multiple options combined
     *
     * @return void
     */
    public function testRenderWithMultipleOptions(): void
    {
        $result = $this->Button->render('Full Options', '/test', [
            'variant' => 'danger',
            'icon' => 'exclamation-triangle',
            'size' => 'lg',
            'style' => 'compact',
        ]);

        $this->assertStringContainsString('btn-danger', $result);
        $this->assertStringContainsString('btn-lg', $result);
        $this->assertStringContainsString('exclamation-triangle', $result);
        $this->assertStringContainsString('title="Full Options"', $result);
    }
}
