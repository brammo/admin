<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Controller\Component;

use Brammo\Admin\Controller\Component\AttachmentsComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

/**
 * AttachmentsComponent Test Case
 */
class AttachmentsComponentTest extends TestCase
{
    /**
     * @var \Cake\Controller\Controller
     */
    protected Controller $controller;

    /**
     * @var \Brammo\Admin\Controller\Component\AttachmentsComponent
     */
    protected AttachmentsComponent $Attachments;

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $table;

    protected string $testDir;

    protected string $originalLocale;

    /**
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

        $this->originalLocale = I18n::getLocale();
        I18n::setLocale('en_US');

        $this->originalConfig = Configure::read('Admin.FileManager');
        $this->testDir = TMP . 'attachments_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
        mkdir($this->testDir . DS . 'uploads', 0755, true);
        mkdir($this->testDir . DS . 'images', 0755, true);

        Configure::write('Admin.FileManager', [
            'basePath' => $this->testDir,
            'topFolders' => ['uploads', 'images'],
            'fileTypes' => [
                'files' => ['txt', 'pdf'],
                'images' => ['jpg', 'jpeg', 'png'],
            ],
            'Images' => [
                'maxWidth' => 1024,
                'maxHeight' => 1024,
                'resizeOnUpload' => false,
            ],
        ]);

        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS attachment_files');
        $connection->execute(
            'CREATE TABLE attachment_files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255),
                filename VARCHAR(255) NOT NULL,
                owner_id INTEGER,
                sort_order INTEGER NOT NULL DEFAULT 0
            )',
        );

        $this->table = $this->getTableLocator()->get('AttachmentFiles', [
            'table' => 'attachment_files',
            'connection' => $connection,
        ]);
        $this->table->addBehavior('ADmad/Sequence.Sequence', [
            'sequenceField' => 'sort_order',
            'scope' => ['owner_id'],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->getTableLocator()->remove('AttachmentFiles');
        ConnectionManager::get('test')->execute('DROP TABLE IF EXISTS attachment_files');

        if ($this->originalConfig !== null) {
            Configure::write('Admin.FileManager', $this->originalConfig);
        } else {
            Configure::delete('Admin.FileManager');
        }

        I18n::setLocale($this->originalLocale);
        $this->removeDirectory($this->testDir);
        unset($this->Attachments, $this->controller, $this->table);
        parent::tearDown();
    }

    /**
     * @param string $dir Directory
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
     * @param string $method HTTP method
     * @param list<\Psr\Http\Message\UploadedFileInterface> $files Uploaded files
     * @return void
     */
    protected function createAttachments(string $method = 'POST', array $files = []): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'params' => [
                'controller' => 'News',
                'action' => 'uploadFiles',
                'plugin' => null,
                'pass' => [],
            ],
        ]);
        if ($files !== []) {
            $request = $request->withUploadedFiles(['files' => $files]);
        }

        $this->controller = new Controller($request);
        $this->controller->setName('News');
        $registry = new ComponentRegistry($this->controller);
        $this->Attachments = new AttachmentsComponent($registry);
    }

    /**
     * @param string $name Client filename
     * @param string $contents File body
     * @return \Laminas\Diactoros\UploadedFile
     */
    protected function uploadedFile(string $name, string $contents = 'hello'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, $contents);

        return new UploadedFile($tmp, strlen($contents), UPLOAD_ERR_OK, $name, 'text/plain');
    }

    /**
     * @param \Cake\Http\Response $response Response
     * @return array<string, mixed>
     */
    protected function decode(Response $response): array
    {
        $payload = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($payload);

        return $payload;
    }

    /**
     * GET requests are rejected.
     *
     * @return void
     */
    public function testUploadRejectsNonPost(): void
    {
        $this->createAttachments('GET');

        $this->expectException(BadRequestException::class);
        $this->Attachments->upload($this->table, 'uploads', ['owner_id' => 1]);
    }

    /**
     * Folder must be under configured top folders.
     *
     * @return void
     */
    public function testUploadRejectsInvalidFolder(): void
    {
        $this->createAttachments('POST', [$this->uploadedFile('doc.txt')]);
        $response = $this->Attachments->upload($this->table, 'secret', ['owner_id' => 1]);

        $this->assertSame(400, $response->getStatusCode());
        $payload = $this->decode($response);
        $this->assertTrue($payload['error']);
        $this->assertSame('Invalid folder', $payload['message']);
    }

    /**
     * Missing files returns 400.
     *
     * @return void
     */
    public function testUploadRejectsEmptyFiles(): void
    {
        $this->createAttachments('POST');
        $response = $this->Attachments->upload($this->table, 'uploads', ['owner_id' => 1]);

        $this->assertSame(400, $response->getStatusCode());
        $payload = $this->decode($response);
        $this->assertTrue($payload['error']);
        $this->assertSame('No files uploaded', $payload['message']);
    }

    /**
     * Successful upload writes disk + ORM row.
     *
     * @return void
     */
    public function testUploadSavesFileAndRow(): void
    {
        $this->createAttachments('POST', [$this->uploadedFile('note.txt')]);
        $response = $this->Attachments->upload(
            $this->table,
            'uploads/docs',
            ['owner_id' => 5],
        );

        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->decode($response);
        $this->assertFalse($payload['error']);
        $this->assertSame(1, $payload['saved']);
        $this->assertSame('/uploads/docs/note.txt', $payload['files'][0]['filename']);
        $this->assertFileExists($this->testDir . DS . 'uploads' . DS . 'docs' . DS . 'note.txt');

        $row = $this->table->find()->firstOrFail();
        $this->assertSame('/uploads/docs/note.txt', $row->filename);
        $this->assertSame(5, $row->owner_id);
        $this->assertSame(1, $row->sort_order);
    }

    /**
     * Invalid extensions are reported per file.
     *
     * @return void
     */
    public function testUploadRejectsInvalidType(): void
    {
        $this->createAttachments('POST', [$this->uploadedFile('virus.exe')]);
        $response = $this->Attachments->upload($this->table, 'uploads', ['owner_id' => 1]);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->decode($response);
        $this->assertTrue($payload['error']);
        $this->assertSame(0, $payload['saved']);
        $this->assertNotFalse($payload['files'][0]['error']);
        $this->assertSame(0, $this->table->find()->count());
    }

    /**
     * ORM save failure is reported without counting as saved.
     *
     * @return void
     */
    public function testUploadReportsSaveFailure(): void
    {
        $this->table->getValidator()
            ->requirePresence('title')
            ->notEmptyString('title');

        $this->createAttachments('POST', [$this->uploadedFile('note.txt')]);
        $response = $this->Attachments->upload($this->table, 'uploads', ['owner_id' => 1]);

        $payload = $this->decode($response);
        $this->assertTrue($payload['error']);
        $this->assertSame(0, $payload['saved']);
        $this->assertSame('Cannot save file record', $payload['files'][0]['error']);
    }
}
