<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Controller;

use Brammo\Admin\Controller\UserController;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * Brammo\Admin\Controller\UserController Test Case
 *
 * Note: These are basic structural tests. Full integration tests would require
 * a complete application setup with database and authentication.
 */
class UserControllerTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Brammo\Admin\Controller\UserController
     */
    protected UserController $UserController;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $request = new ServerRequest([
            'url' => '/admin/user/profile',
        ]);
        $this->UserController = new UserController($request);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->UserController);
        parent::tearDown();
    }

    /**
     * Test that UserController extends AppController
     *
     * @return void
     */
    public function testExtendsAppController(): void
    {
        $this->assertInstanceOf(
            'Brammo\Admin\Controller\AppController',
            $this->UserController,
        );
    }

    /**
     * Test that profile action exists
     *
     * @return void
     */
    public function testProfileActionExists(): void
    {
        $this->assertTrue(
            method_exists($this->UserController, 'profile'),
            'UserController should have a profile action',
        );
    }
}
