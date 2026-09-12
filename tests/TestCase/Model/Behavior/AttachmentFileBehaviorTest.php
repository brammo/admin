<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\Model\Behavior;

use Brammo\Admin\Model\Behavior\AttachmentFileBehavior;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * AttachmentFileBehavior Test Case
 */
class AttachmentFileBehaviorTest extends TestCase
{
    /**
     * @var \Cake\ORM\Table
     */
    protected Table $table;

    /**
     * @var \Cake\ORM\Table
     */
    protected Table $heroes;

    protected string $testDir;

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

        $this->originalConfig = Configure::read('Admin.FileManager');
        $this->testDir = TMP . 'attachment_behavior_' . uniqid();
        mkdir($this->testDir, 0755, true);
        mkdir($this->testDir . DS . 'uploads', 0755, true);

        Configure::write('Admin.FileManager', [
            'basePath' => $this->testDir,
            'topFolders' => ['uploads', 'images'],
            'fileTypes' => [
                'files' => ['txt'],
                'images' => ['jpg'],
            ],
        ]);

        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS attachment_files');
        $connection->execute('DROP TABLE IF EXISTS heroes');
        $connection->execute(
            'CREATE TABLE attachment_files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename VARCHAR(255) NOT NULL
            )',
        );
        $connection->execute(
            'CREATE TABLE heroes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                image VARCHAR(255)
            )',
        );

        $this->table = $this->getTableLocator()->get('AttachmentFiles', [
            'table' => 'attachment_files',
            'connection' => $connection,
        ]);
        $this->table->addBehavior(AttachmentFileBehavior::class, [
            'references' => [
                'Heroes' => 'image',
            ],
        ]);

        $this->heroes = $this->getTableLocator()->get('Heroes', [
            'table' => 'heroes',
            'connection' => $connection,
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
        $this->getTableLocator()->remove('Heroes');
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS attachment_files');
        $connection->execute('DROP TABLE IF EXISTS heroes');

        if ($this->originalConfig !== null) {
            Configure::write('Admin.FileManager', $this->originalConfig);
        } else {
            Configure::delete('Admin.FileManager');
        }

        $this->removeDirectory($this->testDir);
        unset($this->table, $this->heroes);
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
     * Unused files are unlinked after delete.
     *
     * @return void
     */
    public function testUnlinksUnusedFile(): void
    {
        $relative = 'uploads/gone.txt';
        $full = $this->testDir . DS . $relative;
        file_put_contents($full, 'x');

        $entity = $this->table->saveOrFail($this->table->newEntity([
            'filename' => '/' . $relative,
        ]));
        $this->table->deleteOrFail($entity);

        $this->assertFileDoesNotExist($full);
    }

    /**
     * A second row with the same path keeps the file.
     *
     * @return void
     */
    public function testKeepsFileWhenSameTableStillReferencesIt(): void
    {
        $relative = 'uploads/shared.txt';
        $full = $this->testDir . DS . $relative;
        file_put_contents($full, 'x');

        $first = $this->table->saveOrFail($this->table->newEntity([
            'filename' => '/' . $relative,
        ]));
        $this->table->saveOrFail($this->table->newEntity([
            'filename' => '/' . $relative,
        ]));
        $this->table->deleteOrFail($first);

        $this->assertFileExists($full);
    }

    /**
     * Hero image column in references keeps the file.
     *
     * @return void
     */
    public function testKeepsFileWhenReferencedElsewhere(): void
    {
        $relative = 'uploads/hero.txt';
        $full = $this->testDir . DS . $relative;
        file_put_contents($full, 'x');

        $this->heroes->saveOrFail($this->heroes->newEntity([
            'image' => '/' . $relative,
        ]));
        $entity = $this->table->saveOrFail($this->table->newEntity([
            'filename' => '/' . $relative,
        ]));
        $this->table->deleteOrFail($entity);

        $this->assertFileExists($full);
    }

    /**
     * Paths outside the File Manager base are not unlinked.
     *
     * @return void
     */
    public function testDoesNotUnlinkOutsideBase(): void
    {
        $outsideDir = TMP . 'attachment_outside_' . uniqid();
        mkdir($outsideDir, 0755, true);
        $outside = $outsideDir . DS . 'secret.txt';
        file_put_contents($outside, 'secret');

        $entity = $this->table->saveOrFail($this->table->newEntity([
            'filename' => '/etc/secret.txt',
        ]));
        $this->table->deleteOrFail($entity);

        $this->assertFileExists($outside);
        $this->removeDirectory($outsideDir);
    }
}
