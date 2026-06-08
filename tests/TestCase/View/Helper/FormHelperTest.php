<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Helper;

use BootstrapUI\View\Helper\FormHelper as BootstrapFormHelper;
use Brammo\Admin\View\Helper\FormHelper;
use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;

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

        Configure::write('Admin.Editor', ['height' => 500]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Form, $this->View);
        Configure::delete('Admin.Editor');
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

        $result = $this->Form->image('image');

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

        $result = $this->Form->image('thumbnail', [
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

        $result = $this->Form->image('photo', [
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

        $result = $this->Form->image('image', [
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

        $result = $this->Form->image('image', [
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

        $result = $this->Form->image('image');

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

        $result = $this->Form->image('featured_image');

        // Label should be generated from field name
        $this->assertStringContainsString('Featured image', $result);
    }

    /**
     * Test imageControl without form context uses null value
     *
     * @return void
     */
    public function testImageControlWithoutFormContext(): void
    {
        $this->Form->create(null);

        $result = $this->Form->image('image');

        $this->assertStringContainsString('form-image', $result);
        $this->assertStringContainsString('name="image"', $result);
        $this->assertStringContainsString('value=""', $result);
    }

    /**
     * Test imageControl with entity missing field still renders
     *
     * @return void
     */
    public function testImageControlWithEntityMissingField(): void
    {
        $this->Form->create(null, [
            'context' => [
                'data' => ['title' => 'Test'],
            ],
        ]);

        $result = $this->Form->image('image');

        $this->assertStringContainsString('form-image', $result);
        $this->assertStringContainsString('name="image"', $result);
        $this->assertStringContainsString('value=""', $result);
    }

    /**
     * Test imageControl sets correct folder from image path
     *
     * @return void
     */
    public function testImageFolderFromImagePath(): void
    {
        $entity = new Entity(['image' => '/images/gallery/test.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->image('image');

        // Should contain form-image with data-folder attribute
        $this->assertStringContainsString('form-image', $result);
        $this->assertStringContainsString('data-folder="images/gallery"', $result);
    }

    /**
     * Test imageControl shows image preview
     *
     * @return void
     */
    public function testImageShowsPreview(): void
    {
        $entity = new Entity(['image' => '/images/photo.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->image('image');

        // Should contain image preview
        $this->assertStringContainsString('image-preview', $result);
        $this->assertStringContainsString('src="/images/photo.jpg"', $result);
    }

    /**
     * Test imageControl contains select and upload buttons
     *
     * @return void
     */
    public function testImageContainsButtons(): void
    {
        $entity = new Entity(['image' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->image('image');

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
    public function testImageHidesDeleteWhenEmpty(): void
    {
        $entity = new Entity(['image' => '']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->image('image');

        // Delete button should be hidden when no image
        $this->assertMatchesRegularExpression('/delete"[^>]*style="display:none"/', $result);
    }

    /**
     * Test imageControl shows delete button when image exists
     *
     * @return void
     */
    public function testImageShowsDeleteWhenImageExists(): void
    {
        $entity = new Entity(['image' => '/images/test.jpg']);
        $entity->setSource('Articles');
        $this->Form->create($entity, ['context' => ['table' => 'Articles']]);

        $result = $this->Form->image('image');

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

        $result = $this->Form->image('image');

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

        $result = $this->Form->image('image');

        // Filename should be visible (without display:none)
        $this->assertStringContainsString('/images/test.jpg</div>', $result);
    }

    /**
     * Test control method with html type delegates to htmlControl
     *
     * @return void
     */
    public function testControlWithHtmlType(): void
    {
        $this->Form->create(null);

        $result = $this->Form->control('body', ['type' => 'html', 'label' => 'Body']);

        $this->assertStringContainsString('textarea', $result);
        $this->assertStringContainsString('name="body"', $result);
        $this->assertStringContainsString('class="editor', $result);
    }

    /**
     * Test htmlControl loads editor assets once
     *
     * @return void
     */
    public function testHtmlControlLoadsEditorOnce(): void
    {
        $this->Form->create(null);

        $this->Form->html('intro');
        $this->Form->html('body');

        $this->assertTrue((bool)$this->View->get('_editorLoaded'));
    }

    /**
     * Test htmlControl merges custom CSS class with editor class
     *
     * @return void
     */
    public function testHtmlControlMergesCustomClass(): void
    {
        $this->Form->create(null);

        $result = $this->Form->html('content', ['class' => 'form-control']);

        $this->assertStringContainsString('class="form-control editor"', $result);
    }

    /**
     * Test control method with dateRange type renders from/to date inputs
     *
     * @return void
     */
    public function testControlWithDateRangeType(): void
    {
        $this->Form->create(null);

        $result = $this->Form->control('period', [
            'type' => 'dateRange',
            'label' => 'Period',
            'from' => ['value' => '2024-01-01'],
            'to' => ['value' => '2024-12-31'],
        ]);

        $this->assertStringContainsString('input-group', $result);
        $this->assertStringContainsString('name="period_from"', $result);
        $this->assertStringContainsString('name="period_to"', $result);
        $this->assertStringContainsString('type="date"', $result);
        $this->assertStringContainsString('Period', $result);
        $this->assertStringContainsString('value="2024-01-01"', $result);
        $this->assertStringContainsString('value="2024-12-31"', $result);
    }

    /**
     * Test dateRange returns only the input group without label or container
     *
     * @return void
     */
    public function testDateRangeReturnsInputGroupOnly(): void
    {
        $this->Form->create(null);

        $result = $this->Form->dateRange('period', [
            'from' => ['value' => '2024-01-01'],
            'to' => ['value' => '2024-12-31'],
        ]);

        $this->assertStringStartsWith('<div class="input-group">', $result);
        $this->assertStringEndsWith('</div>', $result);
        $this->assertStringNotContainsString('<label', $result);
        $this->assertStringNotContainsString('mb-3', $result);
    }

    /**
     * Test dateRange with custom suffixes
     *
     * @return void
     */
    public function testDateRangeWithCustomSuffixes(): void
    {
        $this->Form->create(null);

        $result = $this->Form->control('period', [
            'type' => 'dateRange',
            'suffixes' => ['start', 'end'],
            'from' => ['value' => '2024-01-01'],
            'to' => ['value' => '2024-12-31'],
        ]);

        $this->assertStringContainsString('name="period_start"', $result);
        $this->assertStringContainsString('name="period_end"', $result);
        $this->assertStringNotContainsString('name="period_from"', $result);
        $this->assertStringNotContainsString('name="period_to"', $result);
    }

    /**
     * Test dateRange with value as list
     *
     * @return void
     */
    public function testDateRangeWithValueList(): void
    {
        $this->Form->create(null);

        $result = $this->Form->dateRange('period', [
            'value' => ['2024-01-01', '2024-12-31'],
        ]);

        $this->assertStringContainsString('value="2024-01-01"', $result);
        $this->assertStringContainsString('value="2024-12-31"', $result);
    }

    /**
     * Test dateRange with value as associative array
     *
     * @return void
     */
    public function testDateRangeWithValueAssociative(): void
    {
        $this->Form->create(null);

        $result = $this->Form->dateRange('period', [
            'value' => ['from' => '2024-02-01', 'to' => '2024-11-30'],
        ]);

        $this->assertStringContainsString('value="2024-02-01"', $result);
        $this->assertStringContainsString('value="2024-11-30"', $result);
    }

    /**
     * Test dateRange with valueFrom and valueTo
     *
     * @return void
     */
    public function testDateRangeWithValueFromAndValueTo(): void
    {
        $this->Form->create(null);

        $result = $this->Form->control('period', [
            'type' => 'dateRange',
            'valueFrom' => '2024-03-01',
            'valueTo' => '2024-10-31',
        ]);

        $this->assertStringContainsString('value="2024-03-01"', $result);
        $this->assertStringContainsString('value="2024-10-31"', $result);
    }

    /**
     * Test per-field from/to options override range value
     *
     * @return void
     */
    public function testDateRangeFromToOptionsOverrideValue(): void
    {
        $this->Form->create(null);

        $result = $this->Form->dateRange('period', [
            'value' => ['2024-01-01', '2024-12-31'],
            'from' => ['value' => '2024-06-01'],
        ]);

        $this->assertStringContainsString('value="2024-06-01"', $result);
        $this->assertStringContainsString('value="2024-12-31"', $result);
        $this->assertStringNotContainsString('value="2024-01-01"', $result);
    }

    /**
     * Test control with dateRange wraps input group with label and container
     *
     * @return void
     */
    public function testControlWithDateRangeWrapsInputGroup(): void
    {
        $this->Form->create(null);

        $result = $this->Form->control('date_range', ['type' => 'dateRange']);

        $this->assertStringContainsString('input-group', $result);
        $this->assertStringContainsString('<label', $result);
        $this->assertStringContainsString('mb-3', $result);
    }
}
