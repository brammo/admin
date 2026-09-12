<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Element;

use Brammo\Admin\View\AppView;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Multiupload and attachments element test case
 */
class AttachmentsElementTest extends TestCase
{
    /**
     * @var \Brammo\Admin\View\AppView
     */
    protected AppView $View;

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
        $routes->setRouteClass(DashedRoute::class);
        $routes->connect('/{controller}/{action}/*');

        $request = new ServerRequest([
            'params' => [
                'controller' => 'News',
                'action' => 'view',
                'plugin' => null,
                'pass' => [],
            ],
        ]);
        Router::setRequest($request);
        $this->View = new AppView($request);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->View);
        Router::reload();
        parent::tearDown();
    }

    /**
     * Test multiupload widget markup and script.
     *
     * @return void
     */
    public function testMultiuploadRendersWidget(): void
    {
        $html = $this->View->element('Brammo/Admin.multiupload', [
            'id' => 'images',
            'url' => ['action' => 'uploadImages', 3],
            'label' => 'Upload images',
            'accept' => 'image/*',
        ]);

        $this->assertStringContainsString('data-multiupload', $html);
        $this->assertStringContainsString('data-modal="#multiupload-modal-images"', $html);
        $this->assertStringContainsString('accept="image/*"', $html);
        $this->assertStringContainsString('Upload images', $html);
        $this->assertStringContainsString('data-multiupload-cancel', $html);
        $this->assertStringContainsString('multiupload.js', $this->View->fetch('script'));
    }

    /**
     * Test files section with sortable rows.
     *
     * @return void
     */
    public function testFilesElementRendersSortableTable(): void
    {
        $html = $this->View->element('Brammo/Admin.Attachments/files', [
            'items' => [
                new Entity(['id' => 1, 'title' => 'Spec', 'filename' => '/uploads/spec.pdf']),
            ],
            'uploadUrl' => ['action' => 'uploadFiles', 9],
            'sortUrl' => ['action' => 'sortFiles', 9],
            'addUrl' => ['action' => 'addFile', 9],
            'editAction' => 'editFile',
            'deleteAction' => 'deleteFile',
        ]);

        $this->assertStringContainsString('data-sortable', $html);
        $this->assertStringContainsString('data-sortable-handle', $html);
        $this->assertStringContainsString('/uploads/spec.pdf', $html);
        $this->assertStringContainsString('Spec', $html);
        $this->assertStringContainsString('data-multiupload', $html);
        $this->assertStringContainsString('sortable.js', $this->View->fetch('script'));
    }

    /**
     * Test images section without items still shows the uploader.
     *
     * @return void
     */
    public function testImagesElementRendersUploaderWithoutItems(): void
    {
        $html = $this->View->element('Brammo/Admin.Attachments/images', [
            'items' => [],
            'uploadUrl' => ['action' => 'uploadImages', 2],
            'addUrl' => ['action' => 'addImage', 2],
        ]);

        $this->assertStringContainsString('data-multiupload', $html);
        $this->assertStringContainsString('Upload images', $html);
        $this->assertStringNotContainsString('data-sortable', $html);
    }

    /**
     * Test images gallery markup with items.
     *
     * @return void
     */
    public function testImagesElementRendersGallery(): void
    {
        $html = $this->View->element('Brammo/Admin.Attachments/images', [
            'items' => [
                new Entity(['id' => 4, 'title' => 'Cover', 'filename' => '/images/cover.jpg']),
            ],
            'uploadUrl' => ['action' => 'uploadImages', 2],
            'sortUrl' => ['action' => 'sortImages', 2],
            'editAction' => 'editImage',
            'deleteAction' => 'deleteImage',
            'lightbox' => false,
        ]);

        $this->assertStringContainsString('data-sortable', $html);
        $this->assertStringContainsString('data-id="4"', $html);
        $this->assertStringContainsString('Cover', $html);
        $this->assertStringContainsString('/images/cover.jpg', $html);
        $this->assertStringContainsString('gallery-image', $html);
    }
}
