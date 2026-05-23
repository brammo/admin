<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\FileManager;

use Brammo\Admin\FileManager\FileManagerService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use RuntimeException;

/**
 * FileManagerService Test Case
 */
class FileManagerServiceTest extends TestCase
{
    protected string $testDir;

    protected FileManagerService $service;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $originalConfig = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConfig = Configure::read('Admin.FileManager');

        $this->testDir = TMP . 'filemanager_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
        mkdir($this->testDir . DS . 'uploads', 0755, true);
        mkdir($this->testDir . DS . 'uploads' . DS . 'subfolder', 0755, true);
        mkdir($this->testDir . DS . 'images', 0755, true);

        file_put_contents($this->testDir . DS . 'uploads' . DS . 'test.txt', 'Test content');
        file_put_contents($this->testDir . DS . 'uploads' . DS . 'test.pdf', 'PDF content');

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

        $this->service = FileManagerService::fromConfigure();
    }

    protected function tearDown(): void
    {
        if ($this->originalConfig !== null) {
            Configure::write('Admin.FileManager', $this->originalConfig);
        } else {
            Configure::delete('Admin.FileManager');
        }

        $this->removeDirectory($this->testDir);

        parent::tearDown();
    }

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

    public function testFromConfigureThrowsWithoutBasePath(): void
    {
        Configure::write('Admin.FileManager.basePath', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File Manager base path is not configured');

        FileManagerService::fromConfigure();
    }

    public function testFromConfigureThrowsWithoutTopFolders(): void
    {
        Configure::write('Admin.FileManager.topFolders', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File Manager top folders are not configured');

        FileManagerService::fromConfigure();
    }

    public function testFromConfigureThrowsWithoutFileTypes(): void
    {
        Configure::write('Admin.FileManager.fileTypes.files', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File Manager file types are not configured');

        FileManagerService::fromConfigure();
    }

    public function testFromConfigureThrowsWithoutImageTypes(): void
    {
        Configure::write('Admin.FileManager.fileTypes.images', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File Manager image types are not configured');

        FileManagerService::fromConfigure();
    }

    public function testIsValidFolderWithValidFolder(): void
    {
        $this->assertTrue($this->service->isValidFolder('uploads'));
        $this->assertTrue($this->service->isValidFolder('uploads/subfolder'));
        $this->assertTrue($this->service->isValidFolder('images'));
    }

    public function testIsValidFolderWithInvalidFolder(): void
    {
        $this->assertFalse($this->service->isValidFolder(''));
        $this->assertFalse($this->service->isValidFolder(null));
        $this->assertFalse($this->service->isValidFolder('invalid'));
        $this->assertFalse($this->service->isValidFolder('../etc'));
        $this->assertFalse($this->service->isValidFolder('secret/files'));
    }

    public function testIsValidFolderBlocksPathTraversal(): void
    {
        $this->assertFalse($this->service->isValidFolder('uploads/../etc'));
        $this->assertFalse($this->service->isValidFolder('uploads/..'));
        $this->assertFalse($this->service->isValidFolder('images/../../../etc/passwd'));
        $this->assertFalse($this->service->isValidFolder("uploads/\0evil"));
    }

    public function testIsPathWithinBaseValid(): void
    {
        $this->assertTrue($this->service->isPathWithinBase($this->testDir . DS . 'uploads'));
        $this->assertTrue($this->service->isPathWithinBase($this->testDir . DS . 'uploads' . DS . 'subfolder'));
        $this->assertTrue($this->service->isPathWithinBase($this->testDir . DS . 'images'));
    }

    public function testIsPathWithinBaseRejectsOutsidePaths(): void
    {
        $this->assertFalse($this->service->isPathWithinBase('/etc'));
        $this->assertFalse($this->service->isPathWithinBase('/tmp'));
        $this->assertFalse($this->service->isPathWithinBase('/nonexistent/path'));
    }

    public function testSanitizeFilename(): void
    {
        $this->assertEquals('test.txt', $this->service->sanitizeFilename('test.txt'));
        $this->assertEquals('pathtotest.txt', $this->service->sanitizeFilename('path/to/test.txt'));
        $this->assertEquals('pathtotest.txt', $this->service->sanitizeFilename('path\\to\\test.txt'));
        $this->assertEquals('test.txt', $this->service->sanitizeFilename('../test.txt'));
        $this->assertEquals('test.txt', $this->service->sanitizeFilename('../../test.txt'));
        $this->assertEquals('test.txt', $this->service->sanitizeFilename("test.txt\0"));
    }

    public function testGetUniqueFilenameSanitizesUnsafeChars(): void
    {
        $result = $this->service->getUniqueFilename($this->testDir . DS . 'uploads', 'my@file#name.txt');
        $this->assertEquals('my_file_name.txt', $result);
    }

    public function testGetFullPath(): void
    {
        $this->assertEquals($this->testDir, $this->service->getFullPath(''));
        $this->assertEquals($this->testDir . DS . 'uploads', $this->service->getFullPath('uploads'));
        $this->assertEquals(
            $this->testDir . DS . 'uploads' . DS . 'subfolder',
            $this->service->getFullPath('uploads' . DS . 'subfolder'),
        );
    }

    public function testGetFileType(): void
    {
        $this->assertEquals('txt', $this->service->getFileType('document.txt'));
        $this->assertEquals('pdf', $this->service->getFileType('document.PDF'));
        $this->assertEquals('jpg', $this->service->getFileType('image.JPG'));
        $this->assertEquals('png', $this->service->getFileType('/path/to/image.PNG'));
        $this->assertEquals('', $this->service->getFileType('noextension'));
    }

    public function testGetUniqueFilenameNonExisting(): void
    {
        $result = $this->service->getUniqueFilename($this->testDir . DS . 'uploads', 'newfile.txt');
        $this->assertEquals('newfile.txt', $result);
    }

    public function testGetUniqueFilenameExisting(): void
    {
        $result = $this->service->getUniqueFilename($this->testDir . DS . 'uploads', 'test.txt');
        $this->assertEquals('test(1).txt', $result);
    }

    public function testGetUniqueFilenameReplacesSpaces(): void
    {
        $result = $this->service->getUniqueFilename($this->testDir . DS . 'uploads', 'my file name.txt');
        $this->assertEquals('my_file_name.txt', $result);
    }

    public function testFolderCount(): void
    {
        $this->assertEquals(3, $this->service->folderCount($this->testDir . DS . 'uploads'));
        $this->assertEquals(0, $this->service->folderCount($this->testDir . DS . 'images'));
        $this->assertEquals(0, $this->service->folderCount($this->testDir . DS . 'uploads' . DS . 'subfolder'));
    }

    public function testReadFolder(): void
    {
        $items = $this->service->readFolder($this->testDir . DS . 'uploads', 'uploads', '');

        $this->assertCount(3, $items);

        foreach ($items as $item) {
            $this->assertArrayHasKey('filename', $item);
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('date', $item);
        }

        $folderItem = array_filter($items, fn($item) => $item['filename'] === 'subfolder');
        $this->assertNotEmpty($folderItem);
        $folderItem = array_values($folderItem)[0];
        $this->assertEquals(FileManagerService::TYPE_FOLDER, $folderItem['type']);
    }

    public function testReadFolderWithFilter(): void
    {
        $items = $this->service->readFolder($this->testDir . DS . 'uploads', 'uploads', 'pdf');

        $this->assertCount(1, $items);
        $this->assertEquals('test.pdf', $items[0]['filename']);
    }

    public function testReadFolderNonExisting(): void
    {
        $items = $this->service->readFolder($this->testDir . DS . 'nonexistent', 'uploads', '');

        $this->assertEmpty($items);
    }

    public function testSortFilesFoldersFirst(): void
    {
        $folder = ['filename' => 'folder', 'type' => FileManagerService::TYPE_FOLDER, 'date' => '2024-01-01'];
        $file = ['filename' => 'aaa.txt', 'type' => 'txt', 'date' => '2024-01-01'];

        $this->assertEquals(-1, $this->service->sortFiles($folder, $file, 'filename', 'asc'));
        $this->assertEquals(1, $this->service->sortFiles($file, $folder, 'filename', 'asc'));
    }

    public function testSortFilesByFilename(): void
    {
        $file1 = ['filename' => 'aaa.txt', 'type' => 'txt', 'date' => '2024-01-01'];
        $file2 = ['filename' => 'bbb.txt', 'type' => 'txt', 'date' => '2024-01-01'];

        $this->assertEquals(-1, $this->service->sortFiles($file1, $file2, 'filename', 'asc'));
        $this->assertEquals(1, $this->service->sortFiles($file2, $file1, 'filename', 'asc'));
        $this->assertEquals(0, $this->service->sortFiles($file1, $file1, 'filename', 'asc'));
    }

    public function testSortFilesByDateDescending(): void
    {
        $file1 = ['filename' => 'old.txt', 'type' => 'txt', 'date' => '2024-01-01 10:00:00'];
        $file2 = ['filename' => 'new.txt', 'type' => 'txt', 'date' => '2024-12-31 10:00:00'];

        $this->assertEquals(1, $this->service->sortFiles($file1, $file2, 'date', 'desc'));
        $this->assertEquals(-1, $this->service->sortFiles($file2, $file1, 'date', 'desc'));
        $this->assertEquals(0, $this->service->sortFiles($file1, $file1, 'date', 'desc'));
    }

    public function testSortFilesByDateAscending(): void
    {
        $file1 = ['filename' => 'old.txt', 'type' => 'txt', 'date' => '2024-01-01 10:00:00'];
        $file2 = ['filename' => 'new.txt', 'type' => 'txt', 'date' => '2024-12-31 10:00:00'];

        $this->assertEquals(-1, $this->service->sortFiles($file1, $file2, 'date', 'asc'));
        $this->assertEquals(1, $this->service->sortFiles($file2, $file1, 'date', 'asc'));
    }

    public function testSortFilesFoldersFirstWithDateSort(): void
    {
        $folder = ['filename' => 'folder', 'type' => FileManagerService::TYPE_FOLDER, 'date' => '2020-01-01'];
        $file = ['filename' => 'recent.txt', 'type' => 'txt', 'date' => '2024-12-31'];

        $this->assertEquals(-1, $this->service->sortFiles($folder, $file, 'date', 'desc'));
        $this->assertEquals(1, $this->service->sortFiles($file, $folder, 'date', 'desc'));
    }

    public function testProcessUploadedFileRejectsInvalidType(): void
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

        $result = $this->service->processUploadedFile('images', $uploadedFile);

        $this->assertIsString($result['error']);
        $this->assertStringContainsString('exe', $result['error']);
        $this->assertFileDoesNotExist($this->testDir . DS . 'images' . DS . 'malware.exe');
    }

    public function testProcessUploadedFileSuccess(): void
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

        $result = $this->service->processUploadedFile('images', $uploadedFile);

        $this->assertFalse($result['error']);
        $this->assertEquals('test_image.jpg', $result['filename']);
        $this->assertFileExists($this->testDir . DS . 'images' . DS . 'test_image.jpg');
    }
}
