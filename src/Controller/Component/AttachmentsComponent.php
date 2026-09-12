<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller\Component;

use Brammo\Admin\FileManager\FileManagerService;
use Cake\Controller\Component;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\ORM\Association;
use Cake\ORM\Table;
use Psr\Http\Message\UploadedFileInterface;
use function Cake\I18n\__d;

/**
 * Bulk-upload files into a File Manager folder and create ORM rows.
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class AttachmentsComponent extends Component
{
    /**
     * Default config.
     *
     * - `filenameField`: Column that stores the public path (default `filename`)
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'filenameField' => 'filename',
    ];

    private ?FileManagerService $fileManager = null;

    /**
     * Upload one or more files and create attachment rows.
     *
     * @param \Cake\ORM\Table|\Cake\ORM\Association $source Child table or association
     * @param string $folder Relative folder under File Manager base (no leading slash)
     * @param array<string, mixed> $defaults Foreign keys and other fields for each new row
     * @return \Cake\Http\Response JSON response with per-file results
     */
    public function upload(
        Table|Association $source,
        string $folder,
        array $defaults = [],
    ): Response {
        $table = $this->resolveTable($source);

        $controller = $this->getController();
        $request = $controller->getRequest();

        if (!$request->is('post')) {
            throw new BadRequestException('Invalid request');
        }

        $folder = trim($folder, '/');
        $fileManager = $this->fileManager();

        if ($folder === '' || !$fileManager->isValidFolder($folder)) {
            return $this->jsonResponse([
                'error' => true,
                'files' => [],
                'message' => __d('brammo/admin', 'Invalid folder'),
            ], 400);
        }

        $fullPath = $fileManager->getFullPath($folder);
        if (!is_dir($fullPath) && !mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
            return $this->jsonResponse([
                'error' => true,
                'files' => [],
                'message' => __d('brammo/admin', 'Cannot create folder'),
            ], 500);
        }

        $filesToProcess = $this->uploadedFiles();
        if ($filesToProcess === []) {
            return $this->jsonResponse([
                'error' => true,
                'files' => [],
                'message' => __d('brammo/admin', 'No files uploaded'),
            ], 400);
        }

        $filenameField = (string)$this->getConfig('filenameField');
        $results = [];
        $hasError = false;
        $savedCount = 0;

        foreach ($filesToProcess as $uploadedFile) {
            $clientName = $uploadedFile->getClientFilename() ?? '';
            $res = $fileManager->processUploadedFile($folder, $uploadedFile);

            if (!empty($res['error'])) {
                $hasError = true;
                $results[] = [
                    'name' => $clientName,
                    'error' => $res['error'],
                ];
                continue;
            }

            $filename = '/' . $folder . '/' . $res['filename'];
            $entity = $table->newEntity(array_merge($defaults, [
                $filenameField => $filename,
            ]));

            if (!$table->save($entity)) {
                $hasError = true;
                $results[] = [
                    'name' => $res['filename'],
                    'error' => __d('brammo/admin', 'Cannot save file record'),
                ];
                continue;
            }

            $savedCount++;
            $results[] = [
                'name' => $res['filename'],
                'filename' => $filename,
                'error' => false,
            ];
        }

        return $this->jsonResponse([
            'error' => $hasError,
            'files' => $results,
            'saved' => $savedCount,
        ]);
    }

    /**
     * @return list<\Psr\Http\Message\UploadedFileInterface>
     */
    private function uploadedFiles(): array
    {
        $uploadedFiles = $this->getController()->getRequest()->getUploadedFiles();
        $filesToProcess = [];

        if (isset($uploadedFiles['files']) && is_array($uploadedFiles['files'])) {
            foreach ($uploadedFiles['files'] as $uploadedFile) {
                if ($uploadedFile instanceof UploadedFileInterface) {
                    $filesToProcess[] = $uploadedFile;
                }
            }
        } elseif (isset($uploadedFiles['files']) && $uploadedFiles['files'] instanceof UploadedFileInterface) {
            $filesToProcess = [$uploadedFiles['files']];
        } elseif (isset($uploadedFiles['file']) && $uploadedFiles['file'] instanceof UploadedFileInterface) {
            $filesToProcess = [$uploadedFiles['file']];
        }

        return $filesToProcess;
    }

    /**
     * @param \Cake\ORM\Table|\Cake\ORM\Association $source Table or association
     * @return \Cake\ORM\Table
     */
    private function resolveTable(Table|Association $source): Table
    {
        if ($source instanceof Association) {
            return $source->getTarget();
        }

        return $source;
    }

    /**
     * @return \Brammo\Admin\FileManager\FileManagerService
     */
    private function fileManager(): FileManagerService
    {
        return $this->fileManager ??= FileManagerService::fromConfigure();
    }

    /**
     * @param array<string, mixed> $payload JSON body
     * @param int $status HTTP status
     * @return \Cake\Http\Response
     */
    private function jsonResponse(array $payload, int $status = 200): Response
    {
        return $this->getController()->getResponse()
            ->withType('application/json')
            ->withStatus($status)
            ->withStringBody(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
