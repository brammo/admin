<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Helper;

use Brammo\Admin\View\Helper\DescriptionHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * Brammo\Admin\View\Helper\DescriptionHelper Test Case
 */
class DescriptionHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Brammo\Admin\View\Helper\DescriptionHelper
     */
    protected DescriptionHelper $Description;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $view = new View();
        $this->Description = new DescriptionHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Description);
        parent::tearDown();
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
    {
        $result = $this->Description->add('Term', 'Definition');

        // add() should return $this for method chaining
        $this->assertInstanceOf(DescriptionHelper::class, $result);
    }

    /**
     * Test render method with single item
     *
     * @return void
     */
    public function testRenderSingleItem(): void
    {
        $this->Description->add('Name', 'John Doe');
        $result = $this->Description->render();

        $this->assertStringContainsString('<dl', $result);
        $this->assertStringContainsString('<dt', $result);
        $this->assertStringContainsString('<dd', $result);
        $this->assertStringContainsString('Name', $result);
        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString('</dl>', $result);
    }

    /**
     * Test render method with multiple items
     *
     * @return void
     */
    public function testRenderMultipleItems(): void
    {
        $this->Description->add('Name', 'John Doe');
        $this->Description->add('Email', 'john@example.com');
        $this->Description->add('Phone', '+1234567890');

        $result = $this->Description->render();

        $this->assertStringContainsString('Name', $result);
        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString('Email', $result);
        $this->assertStringContainsString('john@example.com', $result);
        $this->assertStringContainsString('Phone', $result);
        $this->assertStringContainsString('+1234567890', $result);
    }

    /**
     * Test method chaining
     *
     * @return void
     */
    public function testMethodChaining(): void
    {
        $result = $this->Description
            ->add('First', 'Value 1')
            ->add('Second', 'Value 2')
            ->add('Third', 'Value 3')
            ->render();

        $this->assertStringContainsString('First', $result);
        $this->assertStringContainsString('Value 1', $result);
        $this->assertStringContainsString('Second', $result);
        $this->assertStringContainsString('Value 2', $result);
        $this->assertStringContainsString('Third', $result);
        $this->assertStringContainsString('Value 3', $result);
    }

    /**
     * Test render with custom options
     *
     * @return void
     */
    public function testRenderWithCustomOptions(): void
    {
        $this->Description->add('Term', 'Definition');
        $result = $this->Description->render([
            'list' => ['class' => 'custom-dl-class'],
        ]);

        $this->assertStringContainsString('class="custom-dl-class"', $result);
        $this->assertStringContainsString('Term', $result);
        $this->assertStringContainsString('Definition', $result);
    }

    /**
     * Test that state is reset after render
     *
     * @return void
     */
    public function testStateResetAfterRender(): void
    {
        $this->Description->add('Name', 'John Doe');

        $result1 = $this->Description->render();
        $this->assertStringContainsString('John Doe', $result1);

        // After render, state should be reset
        $result2 = $this->Description->render();
        $this->assertStringNotContainsString('John Doe', $result2);
    }

    /**
     * Test render empty list
     *
     * @return void
     */
    public function testRenderEmptyList(): void
    {
        $result = $this->Description->render();

        $this->assertStringContainsString('<dl', $result);
        $this->assertStringContainsString('</dl>', $result);
        $this->assertStringNotContainsString('<dt', $result);
        $this->assertStringNotContainsString('<dd', $result);
    }

    /**
     * Test HTML in terms and definitions
     *
     * @return void
     */
    public function testHtmlInContent(): void
    {
        $this->Description->add('<script>alert("XSS")</script>', '<b>Bold Text</b>');
        $result = $this->Description->render();

        // HTML content is rendered as-is (view layer is responsible for escaping)
        $this->assertStringContainsString('<script>', $result);
        $this->assertStringContainsString('<b>', $result);
    }
}
