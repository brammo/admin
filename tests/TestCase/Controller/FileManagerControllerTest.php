<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Controller;

use Brammo\Admin\Controller\FileManagerController;
use Brammo\Admin\FileManager\FileManagerService;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use ReflectionClass;
use RuntimeException;

/**
 * Brammo\Admin\Controller\FileManagerController Test Case
 */
class FileManagerControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test directory path
     *
     * @var string
     */
    protected string $testDir;

    /**
     * Original FileManager config
     *
     * @var array<string, mixed>|null
     */
    protected ?array $originalConfig = null;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Store original config
        $this->originalConfig = Configure::read('Admin.FileManager');

        // Create test directory structure
        $this->testDir = TMP . 'filemanager_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
        mkdir($this->testDir . DS . 'uploads', 0755, true);
        mkdir($this->testDir . DS . 'uploads' . DS . 'subfolder', 0755, true);
        mkdir($this->testDir . DS . 'images', 0755, true);

        // Create test files
        file_put_contents($this->testDir . DS . 'uploads' . DS . 'test.txt', 'Test content');
        file_put_contents($this->testDir . DS . 'uploads' . DS . 'test.pdf', 'PDF content');

        // Configure FileManager for testing
        Configure::write('Admin.FileManager', [
            'basePath' => $this->testDir,
            'topFolders' => ['uploads', 'images'],
            'fileTypes' => [
                'files' => ['txt', 'pdf', 'doc', 'docx'],
                'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            ],
            'Images' => [
                'maxWidth' => 1024,
                'maxHeight' => 1024,
                'resizeOnUpload' => false,
            ],
        ]);

        // Configure session for tests
        $this->configRequest([
            'environment' => [
                'REQUEST_METHOD' => 'GET',
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
        // Restore original config
        if ($this->originalConfig !== null) {
            Configure::write('Admin.FileManager', $this->originalConfig);
        } else {
            Configure::delete('Admin.FileManager');
        }

        // Clean up test directory
        $this->removeDirectory($this->testDir);

        parent::tearDown();
    }

    /**
     * Helper to recursively remove a directory
     *
     * @param string $dir Directory path
     * @return void
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DS . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Test that FileManagerController extends AppController
     *
     * @return void
     */
    public function testExtendsAppController(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager',
        ]);
        $controller = new FileManagerController($request);

        $this->assertInstanceOf(
            'Brammo\Admin\Controller\AppController',
            $controller,
        );
    }

    /**
     * Test initialize throws exception when basePath is not configured
     *
     * @return void
     */
    public function testInitializeThrowsExceptionWithoutBasePath(): void
    {
        Configure::write('Admin.FileManager.basePath', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File Manager base path is not configured');

        $request = new ServerRequest(['url' => '/admin/file-manager']);
        new FileManagerController($request);
    }

    /**
     * Test initialize throws exception when topFolders is not configured
     *
     * @return void
     */
    public function testInitializeThrowsExceptionWithoutTopFolders(): void
    {
        Configure::write('Admin.FileManager.topFolders', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File Manager top folders are not configured');

        $request = new ServerRequest(['url' => '/admin/file-manager']);
        new FileManagerController($request);
    }

    /**
     * Test initialize throws exception when file types is not configured
     *
     * @return void
     */
    public function testInitializeThrowsExceptionWithoutFileTypes(): void
    {
        Configure::write('Admin.FileManager.fileTypes.files', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File Manager file types are not configured');

        $request = new ServerRequest(['url' => '/admin/file-manager']);
        new FileManagerController($request);
    }

    /**
     * Test initialize throws exception when image types is not configured
     *
     * @return void
     */
    public function testInitializeThrowsExceptionWithoutImageTypes(): void
    {
        Configure::write('Admin.FileManager.fileTypes.images', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File Manager image types are not configured');

        $request = new ServerRequest(['url' => '/admin/file-manager']);
        new FileManagerController($request);
    }

    /**
     * Test returnJson method
     *
     * @return void
     */
    public function testReturnJson(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('returnJson');
        $method->setAccessible(true);

        $response = $method->invoke($controller, ['success' => true, 'message' => 'Test']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->getType());

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Test', $data['message']);
    }

    /**
     * Test returnJsonError method
     *
     * @return void
     */
    public function testReturnJsonError(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('returnJsonError');
        $method->setAccessible(true);

        $response = $method->invoke($controller, 'Error message');

        $this->assertInstanceOf(Response::class, $response);

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals('Error message', $data['error']);
    }

    /**
     * Test errorResponse returns JSON error for AJAX requests
     *
     * @return void
     */
    public function testErrorResponseAjax(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $controller = new FileManagerController($request);

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('errorResponse');
        $method->setAccessible(true);

        $response = $method->invoke($controller, 'Something went wrong');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->getType());

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals('Something went wrong', $data['error']);
    }

    /**
     * Test successResponse returns JSON success for AJAX requests
     *
     * @return void
     */
    public function testSuccessResponseAjax(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $controller = new FileManagerController($request);

        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('successResponse');
        $method->setAccessible(true);

        $response = $method->invoke($controller, 'Operation completed');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->getType());

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals('Operation completed', $data['success']);
    }

    /**
     * Test upload requires POST method
     *
     * @return void
     */
    public function testUploadRequiresPost(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'query' => ['folder' => 'uploads'],
        ]);
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test upload requires folder parameter
     *
     * @return void
     */
    public function testUploadRequiresFolder(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'post' => [],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test upload validates folder path
     *
     * @return void
     */
    public function testUploadValidatesFolder(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => '../etc'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test createFolder requires valid folder
     *
     * @return void
     */
    public function testCreateFolderRequiresValidFolder(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/create-folder',
            'query' => ['folder' => 'invalid'],
        ]);
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = new FileManagerController($request);

        $response = $controller->createFolder();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test delete requires POST method
     *
     * @return void
     */
    public function testDeleteRequiresPost(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/delete',
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'query' => ['folder' => 'uploads', 'deleteFile' => 'test.txt'],
        ]);
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = new FileManagerController($request);

        $response = $controller->delete();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test delete validates folder path
     *
     * @return void
     */
    public function testDeleteValidatesFolder(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/delete',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => '../etc', 'deleteFile' => 'passwd'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = new FileManagerController($request);

        $response = $controller->delete();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test delete sanitizes deleteFile to prevent path traversal
     *
     * @return void
     */
    public function testDeleteSanitizesFilename(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/delete',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'uploads', 'deleteFile' => '../../../etc/passwd'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = new FileManagerController($request);

        $response = $controller->delete();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        // Should return an error, not delete a file outside the folder
        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test createFolder sanitizes folder name
     *
     * @return void
     */
    public function testCreateFolderSanitizesFolderName(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/create-folder',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'uploads'],
            'post' => ['folder' => '../../../etc/evil'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withParsedBody(['folder' => '../../../etc/evil']);

        $controller = new FileManagerController($request);

        $response = $controller->createFolder();

        $this->assertInstanceOf(Response::class, $response);

        // After sanitization, the folder name should be just "evil" or harmless
        // It should NOT create a folder outside of uploads
        $this->assertDirectoryDoesNotExist($this->testDir . DS . '..' . DS . 'etc');
    }

    /**
     * Test delete can delete a file
     *
     * @return void
     */
    public function testDeleteFile(): void
    {
        // Verify file exists
        $filePath = $this->testDir . DS . 'uploads' . DS . 'test.txt';
        $this->assertFileExists($filePath);

        $request = new ServerRequest([
            'url' => '/admin/file-manager/delete',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'uploads', 'deleteFile' => 'test.txt'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = new FileManagerController($request);

        $response = $controller->delete();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertFileDoesNotExist($filePath);
    }

    /**
     * Test delete can delete an empty folder
     *
     * @return void
     */
    public function testDeleteEmptyFolder(): void
    {
        // Create empty folder
        $folderPath = $this->testDir . DS . 'uploads' . DS . 'empty_folder';
        mkdir($folderPath, 0755, true);
        $this->assertDirectoryExists($folderPath);

        $request = new ServerRequest([
            'url' => '/admin/file-manager/delete',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'uploads', 'deleteFolder' => 'empty_folder'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = new FileManagerController($request);

        $response = $controller->delete();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertDirectoryDoesNotExist($folderPath);
    }

    /**
     * Test delete cannot delete non-empty folder
     *
     * @return void
     */
    public function testDeleteNonEmptyFolder(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/delete',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'uploads', 'deleteFolder' => 'subfolder'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        // Add a file to subfolder to make it non-empty
        file_put_contents($this->testDir . DS . 'uploads' . DS . 'subfolder' . DS . 'file.txt', 'content');

        $controller = new FileManagerController($request);

        $response = $controller->delete();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Controller stub that skips view rendering (routing not loaded in unit tests).
     *
     * @param \Cake\Http\ServerRequest $request Request
     */
    private function fileManagerControllerWithoutRender(ServerRequest $request): FileManagerController
    {
        return new class ($request) extends FileManagerController {
            public function render(?string $template = null, ?string $layout = null): Response
            {
                return new Response();
            }
        };
    }

    /**
     * Test browseImages uses simple layout for full-page requests (e.g. legacy iframe embed)
     *
     * @return void
     */
    public function testBrowseImagesSetsSimpleLayoutForNonAjax(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/browse-images',
            'query' => ['folder' => 'images'],
        ]);

        $controller = $this->fileManagerControllerWithoutRender($request);
        $controller->browseImages();

        $this->assertSame('simple', $controller->viewBuilder()->getLayout());
    }

    /**
     * Test browseImages uses ajax layout for modal AJAX loads (FormHelper image control)
     *
     * @return void
     */
    public function testBrowseImagesSetsAjaxLayoutForAjax(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/browse-images',
            'query' => ['folder' => 'images', 'target' => 'form-image-1'],
        ]);
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = $this->fileManagerControllerWithoutRender($request);
        $controller->browseImages();

        $this->assertSame('ajax', $controller->viewBuilder()->getLayout());
    }

    /**
     * Test browseFiles uses simple layout for full-page requests
     *
     * @return void
     */
    public function testBrowseFilesSetsSimpleLayoutForNonAjax(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/browse-files',
            'query' => ['folder' => 'uploads'],
        ]);

        $controller = $this->fileManagerControllerWithoutRender($request);
        $controller->browseFiles();

        $this->assertSame('simple', $controller->viewBuilder()->getLayout());
    }

    /**
     * Test browseFiles uses ajax layout for modal AJAX loads
     *
     * @return void
     */
    public function testBrowseFilesSetsAjaxLayoutForAjax(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/browse-files',
            'query' => ['folder' => 'uploads'],
        ]);
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = $this->fileManagerControllerWithoutRender($request);
        $controller->browseFiles();

        $this->assertSame('ajax', $controller->viewBuilder()->getLayout());
    }

    /**
     * Test fixImage requires valid folder (without router - just check response type)
     *
     * @return void
     */
    public function testFixImageRequiresValidFolder(): void
    {
        $service = FileManagerService::fromConfigure();

        $this->assertFalse($service->isValidFolder(''));
        $this->assertFalse($service->isValidFolder('nonexistent'));
        $this->assertTrue($service->isValidFolder('uploads'));
    }

    /**
     * Test createFolder successfully creates a folder
     *
     * @return void
     */
    public function testCreateFolderSuccess(): void
    {
        $newFolderPath = $this->testDir . DS . 'uploads' . DS . 'newfolder';
        $this->assertDirectoryDoesNotExist($newFolderPath);

        $request = new ServerRequest([
            'url' => '/admin/file-manager/create-folder',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'uploads'],
            'post' => ['folder' => 'newfolder'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withParsedBody(['folder' => 'newfolder']);

        $controller = new FileManagerController($request);

        $response = $controller->createFolder();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertDirectoryExists($newFolderPath);
    }

    /**
     * Test createFolder requires folder name in POST
     *
     * @return void
     */
    public function testCreateFolderRequiresFolderName(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/create-folder',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'uploads'],
            'post' => [],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $controller = new FileManagerController($request);

        $response = $controller->createFolder();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Test AJAX upload single file from image element
     *
     * @return void
     */
    public function testAjaxUploadSingleFileFromImageElement(): void
    {
        // Create a mock uploaded file
        $tmpFile = $this->testDir . DS . 'tmp_upload.jpg';
        file_put_contents($tmpFile, 'fake image content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'test_image.jpg',
            'image/jpeg',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withHeader('X-CSRF-Token', 'test-token');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/json', $response->getType());

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertFalse($data['error']);
        $this->assertArrayHasKey('files', $data);
        $this->assertCount(1, $data['files']);
        $this->assertEquals('test_image.jpg', $data['files'][0]);
        $this->assertArrayHasKey('message', $data);

        // Verify file was uploaded
        $this->assertFileExists($this->testDir . DS . 'images' . DS . 'test_image.jpg');
    }

    /**
     * Test AJAX upload returns error for invalid file type
     *
     * @return void
     */
    public function testAjaxUploadRejectsInvalidFileType(): void
    {
        $tmpFile = $this->testDir . DS . 'tmp_upload.exe';
        file_put_contents($tmpFile, 'fake executable content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'malware.exe',
            'application/x-msdownload',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
        // Error message contains file type (exe) - message is localized
        $this->assertStringContainsString('exe', $data['error']);

        // Verify file was not uploaded
        $this->assertFileDoesNotExist($this->testDir . DS . 'images' . DS . 'malware.exe');
    }

    /**
     * Test AJAX upload handles upload errors
     *
     * @return void
     */
    public function testAjaxUploadHandlesUploadError(): void
    {
        $uploadedFile = new UploadedFile(
            '',
            0,
            UPLOAD_ERR_INI_SIZE,
            'large_image.jpg',
            'image/jpeg',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
        // Error message contains filename - message is localized
        $this->assertStringContainsString('large_image.jpg', $data['error']);
    }

    /**
     * Test AJAX upload handles no file error
     *
     * @return void
     */
    public function testAjaxUploadHandlesNoFileError(): void
    {
        $uploadedFile = new UploadedFile(
            '',
            0,
            UPLOAD_ERR_NO_FILE,
            'empty.jpg',
            'image/jpeg',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
        // Error message contains filename - message is localized
        $this->assertStringContainsString('empty.jpg', $data['error']);
    }

    /**
     * Test AJAX upload returns error for missing folder parameter
     *
     * @return void
     */
    public function testAjaxUploadRequiresFolderParameter(): void
    {
        $tmpFile = $this->testDir . DS . 'tmp_upload.jpg';
        file_put_contents($tmpFile, 'fake image content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'test_image.jpg',
            'image/jpeg',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            // No folder parameter
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
        // Error message is localized - just check that error is not empty
        $this->assertNotEmpty($data['error']);
    }

    /**
     * Test AJAX upload returns error for invalid folder
     *
     * @return void
     */
    public function testAjaxUploadRejectsInvalidFolder(): void
    {
        $tmpFile = $this->testDir . DS . 'tmp_upload.jpg';
        file_put_contents($tmpFile, 'fake image content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'test_image.jpg',
            'image/jpeg',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => '../etc'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
        // Error message is localized - just check that error is not empty
        $this->assertNotEmpty($data['error']);
    }

    /**
     * Test AJAX upload returns error when no files are provided
     *
     * @return void
     */
    public function testAjaxUploadReturnsErrorForNoFiles(): void
    {
        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        // No uploaded files

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
        // Error message is localized - just check that error is not empty
        $this->assertNotEmpty($data['error']);
    }

    /**
     * Test AJAX upload generates unique filename for existing file
     *
     * @return void
     */
    public function testAjaxUploadGeneratesUniqueFilename(): void
    {
        // Create an existing file
        file_put_contents($this->testDir . DS . 'images' . DS . 'existing.jpg', 'existing content');

        $tmpFile = $this->testDir . DS . 'tmp_upload.jpg';
        file_put_contents($tmpFile, 'new content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'existing.jpg',
            'image/jpeg',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertFalse($data['error']);
        $this->assertArrayHasKey('files', $data);
        $this->assertCount(1, $data['files']);
        // Should have incremented filename
        $this->assertEquals('existing(1).jpg', $data['files'][0]);

        // Both files should exist
        $this->assertFileExists($this->testDir . DS . 'images' . DS . 'existing.jpg');
        $this->assertFileExists($this->testDir . DS . 'images' . DS . 'existing(1).jpg');
    }

    /**
     * Test AJAX upload replaces spaces in filename
     *
     * @return void
     */
    public function testAjaxUploadReplacesSpacesInFilename(): void
    {
        $tmpFile = $this->testDir . DS . 'tmp_upload.jpg';
        file_put_contents($tmpFile, 'fake image content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'my new image.jpg',
            'image/jpeg',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertFalse($data['error']);
        $this->assertEquals('my_new_image.jpg', $data['files'][0]);
        $this->assertFileExists($this->testDir . DS . 'images' . DS . 'my_new_image.jpg');
    }

    /**
     * Test AJAX upload to subfolder
     *
     * @return void
     */
    public function testAjaxUploadToSubfolder(): void
    {
        $tmpFile = $this->testDir . DS . 'tmp_upload.txt';
        file_put_contents($tmpFile, 'file content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'document.txt',
            'text/plain',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'uploads/subfolder'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertFalse($data['error']);
        $this->assertEquals('document.txt', $data['files'][0]);
        $this->assertFileExists($this->testDir . DS . 'uploads' . DS . 'subfolder' . DS . 'document.txt');
    }

    /**
     * Test AJAX upload creates target folder if it doesn't exist
     *
     * @return void
     */
    public function testAjaxUploadCreatesTargetFolder(): void
    {
        $tmpFile = $this->testDir . DS . 'tmp_upload.jpg';
        file_put_contents($tmpFile, 'fake image content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'test_image.jpg',
            'image/jpeg',
        );

        // New subfolder doesn't exist yet
        $newFolder = 'images/gallery/2024';
        $this->assertDirectoryDoesNotExist($this->testDir . DS . 'images' . DS . 'gallery');

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => $newFolder],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertFalse($data['error']);
        $this->assertDirectoryExists($this->testDir . DS . 'images' . DS . 'gallery' . DS . '2024');
        $this->assertFileExists($this->testDir . DS . 'images' . DS . 'gallery' . DS . '2024' . DS . 'test_image.jpg');
    }

    /**
     * Test AJAX upload returns JSON response with correct content type
     *
     * @return void
     */
    public function testAjaxUploadReturnsJsonContentType(): void
    {
        $tmpFile = $this->testDir . DS . 'tmp_upload.png';
        file_put_contents($tmpFile, 'fake png content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'icon.png',
            'image/png',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $this->assertEquals('application/json', $response->getType());

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertIsArray($data);
        $this->assertFalse($data['error']);
    }

    /**
     * Test AJAX upload handles unknown upload error
     *
     * @return void
     */
    public function testAjaxUploadHandlesUnknownError(): void
    {
        $uploadedFile = new UploadedFile(
            '',
            0,
            UPLOAD_ERR_EXTENSION, // PHP extension stopped the upload
            'image.jpg',
            'image/jpeg',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
        // Error message contains filename - message is localized
        $this->assertStringContainsString('image.jpg', $data['error']);
    }

    /**
     * Test AJAX upload with empty filename returns error
     *
     * @return void
     */
    public function testAjaxUploadWithEmptyFilenameReturnsError(): void
    {
        $tmpFile = $this->testDir . DS . 'tmp_upload.jpg';
        file_put_contents($tmpFile, 'fake image content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            '', // Empty filename
            'image/jpeg',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['file' => $uploadedFile]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertArrayHasKey('error', $data);
        // Error message is localized - just check that error is not empty
        $this->assertNotEmpty($data['error']);
    }

    /**
     * Test AJAX upload multiple files using files[] format
     *
     * @return void
     */
    public function testAjaxUploadMultipleFiles(): void
    {
        // Create mock uploaded files
        $tmpFile1 = $this->testDir . DS . 'tmp_upload1.jpg';
        $tmpFile2 = $this->testDir . DS . 'tmp_upload2.png';
        file_put_contents($tmpFile1, 'fake image content 1');
        file_put_contents($tmpFile2, 'fake image content 2');

        $uploadedFile1 = new UploadedFile(
            $tmpFile1,
            filesize($tmpFile1),
            UPLOAD_ERR_OK,
            'image1.jpg',
            'image/jpeg',
        );

        $uploadedFile2 = new UploadedFile(
            $tmpFile2,
            filesize($tmpFile2),
            UPLOAD_ERR_OK,
            'image2.png',
            'image/png',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['files' => [$uploadedFile1, $uploadedFile2]]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $this->assertInstanceOf(Response::class, $response);
        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertFalse($data['error']);
        $this->assertArrayHasKey('files', $data);
        $this->assertCount(2, $data['files']);
        $this->assertContains('image1.jpg', $data['files']);
        $this->assertContains('image2.png', $data['files']);

        // Verify files were uploaded
        $this->assertFileExists($this->testDir . DS . 'images' . DS . 'image1.jpg');
        $this->assertFileExists($this->testDir . DS . 'images' . DS . 'image2.png');
    }

    /**
     * Test AJAX upload multiple files with partial failure
     *
     * @return void
     */
    public function testAjaxUploadMultipleFilesPartialFailure(): void
    {
        // Create one valid and one invalid file
        $tmpFile1 = $this->testDir . DS . 'tmp_upload_valid.jpg';
        $tmpFile2 = $this->testDir . DS . 'tmp_upload_invalid.exe';
        file_put_contents($tmpFile1, 'fake image content');
        file_put_contents($tmpFile2, 'fake executable content');

        $uploadedFile1 = new UploadedFile(
            $tmpFile1,
            filesize($tmpFile1),
            UPLOAD_ERR_OK,
            'valid_image.jpg',
            'image/jpeg',
        );

        $uploadedFile2 = new UploadedFile(
            $tmpFile2,
            filesize($tmpFile2),
            UPLOAD_ERR_OK,
            'invalid.exe',
            'application/x-msdownload',
        );

        $request = new ServerRequest([
            'url' => '/admin/file-manager/upload',
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'query' => ['folder' => 'images'],
        ]);
        $request = $request->withEnv('REQUEST_METHOD', 'POST');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $request = $request->withUploadedFiles(['files' => [$uploadedFile1, $uploadedFile2]]);

        $controller = new FileManagerController($request);

        $response = $controller->upload();

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        // Should have both files and errors
        $this->assertArrayHasKey('files', $data);
        $this->assertCount(1, $data['files']);
        $this->assertEquals('valid_image.jpg', $data['files'][0]);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('exe', $data['error']);

        // Only valid file should be uploaded
        $this->assertFileExists($this->testDir . DS . 'images' . DS . 'valid_image.jpg');
        $this->assertFileDoesNotExist($this->testDir . DS . 'images' . DS . 'invalid.exe');
    }
}
