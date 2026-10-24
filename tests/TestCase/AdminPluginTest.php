<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase;

use Authentication\Middleware\AuthenticationMiddleware;
use Brammo\Admin\AdminPlugin;
use Cake\Console\CommandCollection;
use Cake\Core\Container;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\TestSuite\TestCase;

/**
 * Brammo\Admin\AdminPlugin Test Case
 */
class AdminPluginTest extends TestCase
{
    /**
     * Subject under test
     *
     * @var \Brammo\Admin\AdminPlugin
     */
    protected AdminPlugin $plugin;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = new AdminPlugin();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->plugin);
        parent::tearDown();
    }

    /**
     * Test bootstrap method
     *
     * Authentication is part of this plugin, so bootstrap must not load Brammo/Auth.
     *
     * @return void
     */
    public function testBootstrap(): void
    {
        $app = $this->createMock(PluginApplicationInterface::class);
        $app->expects($this->never())->method('addPlugin');

        $this->plugin->bootstrap($app);
    }

    /**
     * Test middleware method
     *
     * @return void
     */
    public function testMiddleware(): void
    {
        $middlewareQueue = new MiddlewareQueue();

        $result = $this->plugin->middleware($middlewareQueue);

        $this->assertInstanceOf(MiddlewareQueue::class, $result);
        $this->assertCount(1, $result);

        // Check that AuthenticationMiddleware was added
        $middleware = iterator_to_array($result);
        $this->assertInstanceOf(AuthenticationMiddleware::class, $middleware[0]);
    }

    /**
     * Test console method
     *
     * @return void
     */
    public function testConsole(): void
    {
        $commands = new CommandCollection();

        $result = $this->plugin->console($commands);

        $this->assertInstanceOf(CommandCollection::class, $result);
    }

    /**
     * Test services method
     *
     * @return void
     */
    public function testServices(): void
    {
        $container = new Container();

        // Should not throw any exceptions
        $this->plugin->services($container);

        $this->assertInstanceOf(Container::class, $container);
    }
}
