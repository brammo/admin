<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Element\FileManager;

use Brammo\Admin\View\AppView;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * FileManager pagination element test case
 */
class PaginationElementTest extends TestCase
{
    /**
     * View instance
     *
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
        $routes->plugin(
            'Brammo/Admin',
            ['path' => '/admin'],
            function (RouteBuilder $routeBuilder): void {
                $routeBuilder->connect('/filemanager', [
                    'controller' => 'FileManager',
                    'action' => 'index',
                ]);
                $routeBuilder->connect('/filemanager/{action}/*', [
                    'controller' => 'FileManager',
                ]);
            },
        );

        $request = new ServerRequest([
            'url' => '/admin/filemanager',
            'params' => [
                'plugin' => 'Brammo/Admin',
                'controller' => 'FileManager',
                'action' => 'index',
            ],
        ]);
        Router::setRequest($request);

        $this->View = new AppView($request);
        $this->View->set([
            'folder' => 'images/news',
            'filter' => '',
            'page' => 1,
            'pages' => 3,
        ]);
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
     * Index includes the element without action/target; those must default from the request.
     *
     * @return void
     */
    public function testRendersWithoutActionAndTargetWithoutWarnings(): void
    {
        $warnings = [];
        set_error_handler(function (int $errno, string $errstr) use (&$warnings): bool {
            if ($errno === E_WARNING) {
                $warnings[] = $errstr;
            }

            return true;
        });

        try {
            $result = $this->View->element('Brammo/Admin.FileManager/pagination');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
        $this->assertStringContainsString('class="pagination"', $result);
        $this->assertStringContainsString('folder=images%2Fnews', $result);
    }

    /**
     * Passed action and target are kept on pagination URLs.
     *
     * @return void
     */
    public function testUsesPassedActionAndTarget(): void
    {
        $result = $this->View->element('Brammo/Admin.FileManager/pagination', [
            'folder' => 'images/news',
            'target' => 'form-image-1',
            'filter' => '',
            'action' => 'browseImages',
        ]);

        $this->assertStringContainsString('browseImages', $result);
        $this->assertStringContainsString('target=form-image-1', $result);
    }

    /**
     * Single-page listings skip pagination markup.
     *
     * @return void
     */
    public function testSkipsMarkupWhenOnlyOnePage(): void
    {
        $this->View->set('pages', 1);

        $result = $this->View->element('Brammo/Admin.FileManager/pagination');

        $this->assertSame('', $result);
    }
}
