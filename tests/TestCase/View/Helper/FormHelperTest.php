<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Helper;

use BootstrapUI\View\Helper\FormHelper as BootstrapFormHelper;
use Brammo\Admin\View\Helper\FormHelper;
use Cake\ORM\Entity;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Form\EntityContext;
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
        $this->assertStringContainsString('input type="hidden" name="image"', $result);
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
        // ID is dynamically generated with uniqid()
        $this->assertMatchesRegularExpression('/id="form-image-[a-z0-9]+"/', $result);
        $this->assertStringContainsString('input type="hidden" name="image"', $result);
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
        // ID is dynamically generated with uniqid()
        $this->assertMatchesRegularExpression('/id="form-image-[a-z0-9]+"/', $result);
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

    /**
     * Test getEntityFromContext with EntityContext
     *
     * @return void
     */
    public function testGetEntityFromContextWithEntityContext(): void
    {
        $entity = new Entity(['image' => '/images/test.jpg']);
        $entity->setSource('Articles');

        $context = new EntityContext([
            'entity' => $entity,
        ]);

        $reflection = new ReflectionClass($this->Form);
        $method = $reflection->getMethod('getEntityFromContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->Form, $context);

        $this->assertSame($entity, $result);
    }

    /**
     * Test imageControl throws exception when entity is null
     *
     * @return void
     */
    public function testImageControlThrowsExceptionWithoutEntity(): void
    {
        $this->Form->create(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity variable is required');

        $this->Form->imageControl('image');
    }

    /**
     * Test imageControl throws exception when entity doesn't have field
     *
     * @return void
     */
    public function testImageControlThrowsExceptionWhenFieldMissing(): void
    {
        $entity = new Entity(['title' => 'Test']); // no 'image' field
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity does not have the field "image"');

        $this->Form->imageControl('image');
    }

    /**
     * Test imageControl sets correct folder from image path
     *
     * @return void
     */
    public function testImageControlFolderFromImagePath(): void
    {
        $entity = new Entity(['image' => '/images/gallery/test.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image');

        // Should contain form-image with data-folder attribute
        $this->assertStringContainsString('form-image', $result);
        $this->assertStringContainsString('data-folder="images/gallery"', $result);
    }

    /**
     * Test imageControl shows image preview
     *
     * @return void
     */
    public function testImageControlShowsPreview(): void
    {
        $entity = new Entity(['image' => '/images/photo.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image');

        // Should contain image preview
        $this->assertStringContainsString('image-preview', $result);
        $this->assertStringContainsString('src="/images/photo.jpg"', $result);
    }

    /**
     * Test imageControl contains select and upload buttons
     *
     * @return void
     */
    public function testImageControlContainsButtons(): void
    {
        $entity = new Entity(['image' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image');

        // Should contain action buttons
        $this->assertStringContainsString('class="btn btn-info btn-sm select"', $result);
        $this->assertStringContainsString('class="btn btn-primary btn-sm mb-0 upload"', $result);
        $this->assertStringContainsString('class="btn btn-danger btn-sm delete"', $result);
    }

    /**
     * Test imageControl hides delete button when no image
     *
     * @return void
     */
    public function testImageControlHidesDeleteWhenEmpty(): void
    {
        $entity = new Entity(['image' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image');

        // Delete button should be hidden when no image
        $this->assertMatchesRegularExpression('/delete"[^>]*style="display:none"/', $result);
    }

    /**
     * Test imageControl shows delete button when image exists
     *
     * @return void
     */
    public function testImageControlShowsDeleteWhenImageExists(): void
    {
        $entity = new Entity(['image' => '/images/test.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image');

        // Delete button should be visible (no style="display:none" in delete link)
        $this->assertMatchesRegularExpression('/class="btn btn-danger btn-sm delete"\s*>/', $result);
    }

    /**
     * Test control method with other standard types
     *
     * @return void
     */
    public function testControlWithVariousTypes(): void
    {
        $this->Form->create(null);

        // Test textarea
        $result = $this->Form->control('description', ['type' => 'textarea']);
        $this->assertStringContainsString('textarea', $result);
        $this->assertStringContainsString('name="description"', $result);

        // Test select
        $result = $this->Form->control('category', [
            'type' => 'select',
            'options' => ['1' => 'Option 1', '2' => 'Option 2'],
        ]);
        $this->assertStringContainsString('select', $result);
        $this->assertStringContainsString('Option 1', $result);

        // Test checkbox
        $result = $this->Form->control('active', ['type' => 'checkbox']);
        $this->assertStringContainsString('checkbox', $result);
    }

    /**
     * Test imageControl hides filename display when empty
     *
     * @return void
     */
    public function testImageControlHidesFilenameWhenEmpty(): void
    {
        $entity = new Entity(['image' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image');

        // Filename display should be hidden
        $this->assertMatchesRegularExpression('/class="[^"]*filename[^"]*"[^>]*style="display:none"/', $result);
    }

    /**
     * Test imageControl shows filename when image exists
     *
     * @return void
     */
    public function testImageControlShowsFilenameWhenExists(): void
    {
        $entity = new Entity(['image' => '/images/test.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->imageControl('image');

        // Filename should be visible (without display:none)
        $this->assertStringContainsString('/images/test.jpg</div>', $result);
    }
}
