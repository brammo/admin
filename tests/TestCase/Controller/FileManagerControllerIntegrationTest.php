<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Controller;

use Brammo\Admin\Test\TestApp\Application;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Core\PluginCollection;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * FileManagerController tests through the full HTTP stack (layout + templates).
 *
 * @uses \Brammo\Admin\Controller\FileManagerController
 */
class FileManagerControllerIntegrationTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Plugin collection to restore after the application under test replaces it.
     *
     * @var \Cake\Core\PluginCollection
     */
    protected PluginCollection $originalPlugins;

    /**
     * Locale active before the test
     *
     * @var string
     */
    protected string $originalLocale;

    /**
     * Original FileManager config
     *
     * @var array<string, mixed>|null
     */
    protected ?array $originalConfig = null;

    /**
     * Test directory path
     *
     * @var string
     */
    protected string $testDir;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->originalPlugins = Plugin::getCollection();
        $this->originalLocale = I18n::getLocale();

        parent::setUp();

        $this->configApplication(Application::class, [TESTS . DS . 'test_app' . DS . 'config' . DS]);

        $this->originalConfig = Configure::read('Admin.FileManager');

        $this->testDir = TMP . 'filemanager_integration_' . uniqid();
        mkdir($this->testDir . DS . 'images', 0755, true);

        Configure::write('Admin.FileManager', [
            'basePath' => $this->testDir,
            'topFolders' => ['images'],
            'fileTypes' => [
                'files' => ['pdf'],
                'images' => ['jpg'],
            ],
        ]);

        $this->session([
            'AdminAuth' => [
                'id' => 1,
                'email' => 'test@example.com',
                'status' => 'active',
            ],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->originalConfig !== null) {
            Configure::write('Admin.FileManager', $this->originalConfig);
        } else {
            Configure::delete('Admin.FileManager');
        }

        rmdir($this->testDir . DS . 'images');
        rmdir($this->testDir);

        parent::tearDown();

        Plugin::setCollection($this->originalPlugins);
        I18n::setLocale($this->originalLocale);
    }

    /**
     * Test index escapes the folder name used as the page title
     *
     * @return void
     */
    public function testIndexEscapesFolderInTitle(): void
    {
        $this->get('/admin/filemanager?folder=' . urlencode('images/</title><script>alert(1)</script>'));

        $this->assertResponseOk();
        $this->assertResponseNotContains('<script>alert(1)</script>');
        $this->assertResponseContains('&lt;/title&gt;&lt;script&gt;alert(1)&lt;/script&gt;');
    }

    /**
     * Test browse escapes the folder name in the header
     *
     * @return void
     */
    public function testBrowseImagesEscapesFolder(): void
    {
        $this->get('/admin/filemanager/browseImages?folder=' . urlencode('images/<img src=x onerror=alert(1)>'));

        $this->assertResponseOk();
        $this->assertResponseNotContains('<img src=x onerror=alert(1)>');
        $this->assertResponseContains('/images/&lt;img src=x onerror=alert(1)&gt;');
    }
}
