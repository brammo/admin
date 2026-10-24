<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestApp;

use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;

/**
 * Application used by controller integration tests.
 */
class Application extends BaseApplication
{
    /**
     * @inheritDoc
     */
    public function bootstrap(): void
    {
        parent::bootstrap();

        $this->addPlugin('BootstrapUI', ['bootstrap' => false, 'routes' => false]);
        $this->addPlugin('Brammo/BootstrapUI', ['bootstrap' => false, 'routes' => false]);
        $this->addPlugin('Brammo/Content', ['bootstrap' => false, 'routes' => false]);
        $this->addPlugin('Authentication', ['bootstrap' => false, 'routes' => false]);
        $this->addPlugin('Brammo/Admin');
    }

    /**
     * @inheritDoc
     */
    public function pluginBootstrap(): void
    {
        parent::pluginBootstrap();

        // Keep assertion messages in English. Plugin config defaults the locale to bg.
        Configure::write('Admin.defaultLocale', '');
    }

    /**
     * @inheritDoc
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $error = Configure::read('Error');
        if (!is_array($error)) {
            $error = [];
        }

        return $middlewareQueue
            ->add(new ErrorHandlerMiddleware($error, $this))
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware());
    }
}
