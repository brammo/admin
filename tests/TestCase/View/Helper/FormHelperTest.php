<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Helper;

use BootstrapUI\View\Helper\FormHelper as BootstrapFormHelper;
use Brammo\Admin\View\Helper\FormHelper;
use Cake\ORM\Entity;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use ReflectionClass;

/**
 * Brammo\Admin\View\Helper\FormHelper Test Case
 */
class FormHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Brammo\Admin\View\Helper\FormHelper
     */
    protected FormHelper $Form;

    /**
     * View instance
     *
     * @var \Cake\View\View
     */
    protected View $View;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up routes for testing
        Router::reload();
        $routes = Router::createRouteBuilder('/');
        $routes->plugin(
            'Brammo/Admin',
            ['path' => '/admin'],
            function (RouteBuilder $routeBuilder) {
                $routeBuilder->fallbacks();
            },
        );

        $this->View = new View();
        // Load BootstrapUI Html helper which provides the icon() method
        $this->View->loadHelper('Html', ['className' => 'BootstrapUI.Html']);
        $this->Form = new FormHelper($this->View);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Form, $this->View);
        Router::reload();
        parent::tearDown();
    }

    /**
     * Test that FormHelper extends BootstrapUI FormHelper
     *
     * @return void
     */
    public function testExtendsBootstrapFormHelper(): void
    {
        $this->assertInstanceOf(
            BootstrapFormHelper::class,
            $this->Form,
        );
    }

    /**
     * Test control method with non-image type delegates to parent
     *
     * @return void
     */
    public function testControlWithTextType(): void
    {
        $this->Form->create(null);

        $result = $this->Form->control('name', ['type' => 'text']);

        $this->assertStringContainsString('input', $result);
        $this->assertStringContainsString('name="name"', $result);
        $this->assertStringContainsString('type="text"', $result);
    }

    /**
     * Test control method with no type delegates to parent
     *
     * @return void
     */
    public function testControlWithNoType(): void
    {
        $this->Form->create(null);

        $result = $this->Form->control('title');

        $this->assertStringContainsString('input', $result);
        $this->assertStringContainsString('name="title"', $result);
    }

    /**
     * Test control method with image type calls imageControl
     *
     * @return void
     */
    public function testControlWithImageType(): void
    {
        $entity = new Entity(['image' => '/images/test.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->control('image', ['type' => 'image']);

        // Should contain the image control structure
        $this->assertStringContainsString('form-image', $result);
        $this->assertStringContainsString('input-image', $result);
    }

    /**
     * Test imageControl method with default options
     *
     * @return void
     */
    public function testImageControlDefaults(): void
    {
        $entity = new Entity(['image' => '/images/test.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image');

        $this->assertStringContainsString('form-image', $result);
        $this->assertStringContainsString('id="form-image-image"', $result);
        $this->assertStringContainsString('id="input-image"', $result);
    }

    /**
     * Test imageControl method with custom folder option
     *
     * @return void
     */
    public function testImageControlWithFolder(): void
    {
        $entity = new Entity(['thumbnail' => '/uploads/thumb.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('thumbnail', [
            'folder' => 'uploads',
        ]);

        $this->assertStringContainsString('form-image', $result);
        $this->assertStringContainsString('id="form-image-thumbnail"', $result);
    }

    /**
     * Test imageControl method with custom label
     *
     * @return void
     */
    public function testImageControlWithLabel(): void
    {
        $entity = new Entity(['photo' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('photo', [
            'label' => 'Profile Photo',
        ]);

        $this->assertStringContainsString('Profile Photo', $result);
    }

    /**
     * Test imageControl method with allowEmpty false
     *
     * @return void
     */
    public function testImageControlRequired(): void
    {
        $entity = new Entity(['image' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image', [
            'allowEmpty' => false,
        ]);

        $this->assertStringContainsString('required', $result);
    }

    /**
     * Test imageControl method without allowEmpty (should not have required class)
     *
     * @return void
     */
    public function testImageControlNotRequired(): void
    {
        $entity = new Entity(['image' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image', [
            'allowEmpty' => true,
        ]);

        $this->assertStringNotContainsString('required', $result);
    }

    /**
     * Test imageControl with empty entity value
     *
     * @return void
     */
    public function testImageControlEmptyValue(): void
    {
        $entity = new Entity(['image' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image');

        $this->assertStringContainsString('form-image', $result);
        $this->assertStringContainsString('name="image"', $result);
    }

    /**
     * Test imageControl generates auto label from field name
     *
     * @return void
     */
    public function testImageControlAutoLabel(): void
    {
        $entity = new Entity(['featured_image' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('featured_image');

        // Label should be generated from field name
        $this->assertStringContainsString('Featured image', $result);
    }

    /**
     * Test getEntityFromContext with null context
     *
     * @return void
     */
    public function testGetEntityFromContextNull(): void
    {
        // Use reflection to test protected method
        $reflection = new ReflectionClass($this->Form);
        $method = $reflection->getMethod('getEntityFromContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->Form, null);

        $this->assertNull($result);
    }
}
