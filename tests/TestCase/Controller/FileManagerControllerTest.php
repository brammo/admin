<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Controller;

use Brammo\Admin\Controller\FileManagerController;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

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
                'fixOnUpload' => false,
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

        $this->expectException(\RuntimeException::class);
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

        $this->expectException(\RuntimeException::class);
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

        $this->expectException(\RuntimeException::class);
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File Manager image types are not configured');

        $request = new ServerRequest(['url' => '/admin/file-manager']);
        new FileManagerController($request);
    }

    /**
     * Test that required action methods exist
     *
     * @return void
     */
    public function testRequiredActionsExist(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $this->assertTrue(method_exists($controller, 'index'), 'Should have index action');
        $this->assertTrue(method_exists($controller, 'browseImages'), 'Should have browseImages action');
        $this->assertTrue(method_exists($controller, 'browseFiles'), 'Should have browseFiles action');
        $this->assertTrue(method_exists($controller, 'upload'), 'Should have upload action');
        $this->assertTrue(method_exists($controller, 'createFolder'), 'Should have createFolder action');
        $this->assertTrue(method_exists($controller, 'delete'), 'Should have delete action');
        $this->assertTrue(method_exists($controller, 'fixImage'), 'Should have fixImage action');
    }

    /**
     * Test isValidFolder with valid folder
     *
     * @return void
     */
    public function testIsValidFolderWithValidFolder(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isValidFolder');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, 'uploads'));
        $this->assertTrue($method->invoke($controller, 'uploads/subfolder'));
        $this->assertTrue($method->invoke($controller, 'images'));
    }

    /**
     * Test isValidFolder with invalid folder
     *
     * @return void
     */
    public function testIsValidFolderWithInvalidFolder(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isValidFolder');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, ''));
        $this->assertFalse($method->invoke($controller, null));
        $this->assertFalse($method->invoke($controller, 'invalid'));
        $this->assertFalse($method->invoke($controller, '../etc'));
        $this->assertFalse($method->invoke($controller, 'secret/files'));
    }

    /**
     * Test getFullPath method
     *
     * @return void
     */
    public function testGetFullPath(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getFullPath');
        $method->setAccessible(true);

        $this->assertEquals($this->testDir, $method->invoke($controller, ''));
        $this->assertEquals($this->testDir . DS . 'uploads', $method->invoke($controller, 'uploads'));
        $this->assertEquals(
            $this->testDir . DS . 'uploads' . DS . 'subfolder',
            $method->invoke($controller, 'uploads' . DS . 'subfolder')
        );
    }

    /**
     * Test getFileType method
     *
     * @return void
     */
    public function testGetFileType(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getFileType');
        $method->setAccessible(true);

        $this->assertEquals('txt', $method->invoke($controller, 'document.txt'));
        $this->assertEquals('pdf', $method->invoke($controller, 'document.PDF'));
        $this->assertEquals('jpg', $method->invoke($controller, 'image.JPG'));
        $this->assertEquals('png', $method->invoke($controller, '/path/to/image.PNG'));
        $this->assertEquals('', $method->invoke($controller, 'noextension'));
    }

    /**
     * Test getUniqueFilename with non-existing file
     *
     * @return void
     */
    public function testGetUniqueFilenameNonExisting(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getUniqueFilename');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $this->testDir . DS . 'uploads', 'newfile.txt');
        $this->assertEquals('newfile.txt', $result);
    }

    /**
     * Test getUniqueFilename with existing file
     *
     * @return void
     */
    public function testGetUniqueFilenameExisting(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getUniqueFilename');
        $method->setAccessible(true);

        // test.txt already exists in setUp
        $result = $method->invoke($controller, $this->testDir . DS . 'uploads', 'test.txt');
        $this->assertEquals('test(1).txt', $result);
    }

    /**
     * Test getUniqueFilename replaces spaces with underscores
     *
     * @return void
     */
    public function testGetUniqueFilenameReplacesSpaces(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getUniqueFilename');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $this->testDir . DS . 'uploads', 'my file name.txt');
        $this->assertEquals('my_file_name.txt', $result);
    }

    /**
     * Test folderCount method
     *
     * @return void
     */
    public function testFolderCount(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('folderCount');
        $method->setAccessible(true);

        // uploads folder has: subfolder, test.txt, test.pdf = 3 items
        $count = $method->invoke($controller, $this->testDir . DS . 'uploads');
        $this->assertEquals(3, $count);

        // images folder is empty
        $count = $method->invoke($controller, $this->testDir . DS . 'images');
        $this->assertEquals(0, $count);

        // subfolder is empty
        $count = $method->invoke($controller, $this->testDir . DS . 'uploads' . DS . 'subfolder');
        $this->assertEquals(0, $count);
    }

    /**
     * Test readFolder method returns correct structure
     *
     * @return void
     */
    public function testReadFolder(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('readFolder');
        $method->setAccessible(true);

        $items = $method->invoke(
            $controller,
            $this->testDir . DS . 'uploads',
            'uploads',
            ''
        );

        $this->assertIsArray($items);
        $this->assertCount(3, $items); // subfolder, test.txt, test.pdf

        // Check structure of items
        foreach ($items as $item) {
            $this->assertArrayHasKey('filename', $item);
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('date', $item);
        }

        // Check folder detection
        $folderItem = array_filter($items, fn($item) => $item['filename'] === 'subfolder');
        $this->assertNotEmpty($folderItem);
        $folderItem = array_values($folderItem)[0];
        $this->assertEquals('[folder]', $folderItem['type']);
    }

    /**
     * Test readFolder with filter
     *
     * @return void
     */
    public function testReadFolderWithFilter(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('readFolder');
        $method->setAccessible(true);

        $items = $method->invoke(
            $controller,
            $this->testDir . DS . 'uploads',
            'uploads',
            'pdf'
        );

        $this->assertCount(1, $items);
        $this->assertEquals('test.pdf', $items[0]['filename']);
    }

    /**
     * Test readFolder returns empty array for non-existing path
     *
     * @return void
     */
    public function testReadFolderNonExisting(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('readFolder');
        $method->setAccessible(true);

        $items = $method->invoke(
            $controller,
            $this->testDir . DS . 'nonexistent',
            'uploads',
            ''
        );

        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    /**
     * Test sortFiles method - folders come first
     *
     * @return void
     */
    public function testSortFilesFoldersFirst(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sortFiles');
        $method->setAccessible(true);

        $folder = ['filename' => 'folder', 'type' => '[folder]', 'date' => '2024-01-01'];
        $file = ['filename' => 'aaa.txt', 'type' => 'txt', 'date' => '2024-01-01'];

        // Folder should come before file
        $this->assertEquals(-1, $method->invoke($controller, $folder, $file));
        $this->assertEquals(1, $method->invoke($controller, $file, $folder));
    }

    /**
     * Test sortFiles method - sorts by filename
     *
     * @return void
     */
    public function testSortFilesByFilename(): void
    {
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sortFiles');
        $method->setAccessible(true);

        $file1 = ['filename' => 'aaa.txt', 'type' => 'txt', 'date' => '2024-01-01'];
        $file2 = ['filename' => 'bbb.txt', 'type' => 'txt', 'date' => '2024-01-01'];

        // Default sort is by filename ascending
        $this->assertEquals(-1, $method->invoke($controller, $file1, $file2));
        $this->assertEquals(1, $method->invoke($controller, $file2, $file1));
        $this->assertEquals(0, $method->invoke($controller, $file1, $file1));
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

        $reflection = new \ReflectionClass($controller);
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

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('returnJsonError');
        $method->setAccessible(true);

        $response = $method->invoke($controller, 'Error message');

        $this->assertInstanceOf(Response::class, $response);

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals('Error message', $data['error']);
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
     * Test fixImage requires valid folder (without router - just check response type)
     *
     * @return void
     */
    public function testFixImageRequiresValidFolder(): void
    {
        // The fixImage method uses Flash and redirect for invalid folder
        // which requires router to be initialized. We test the path validation
        // through the isValidFolder method directly instead.
        $request = new ServerRequest(['url' => '/admin/file-manager']);
        $controller = new FileManagerController($request);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isValidFolder');
        $method->setAccessible(true);

        // Empty folder should be invalid
        $this->assertFalse($method->invoke($controller, ''));

        // Invalid folder should be invalid
        $this->assertFalse($method->invoke($controller, 'nonexistent'));

        // Valid folder should be valid
        $this->assertTrue($method->invoke($controller, 'uploads'));
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'test_image.jpg',
            'image/jpeg'
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'malware.exe',
            'application/x-msdownload'
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
        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            '',
            0,
            UPLOAD_ERR_INI_SIZE,
            'large_image.jpg',
            'image/jpeg'
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
        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            '',
            0,
            UPLOAD_ERR_NO_FILE,
            'empty.jpg',
            'image/jpeg'
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'test_image.jpg',
            'image/jpeg'
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'test_image.jpg',
            'image/jpeg'
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'existing.jpg',
            'image/jpeg'
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'my new image.jpg',
            'image/jpeg'
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'document.txt',
            'text/plain'
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'test_image.jpg',
            'image/jpeg'
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'icon.png',
            'image/png'
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
        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            '',
            0,
            UPLOAD_ERR_EXTENSION, // PHP extension stopped the upload
            'image.jpg',
            'image/jpeg'
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

        $uploadedFile = new \Laminas\Diactoros\UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            '', // Empty filename
            'image/jpeg'
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
}
