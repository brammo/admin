<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller;

use Brammo\Admin\FileManager\FileManagerService;
use Cake\Http\Response;
use function Cake\I18n\__d;

/**
 * File Manager Controller
 */
class FileManagerController extends AppController
{
    private string $sortField = 'filename';

    private string $sortDirection = 'asc';

    private FileManagerService $fileManager;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->fileManager = FileManagerService::fromConfigure();
    }

    /**
     * Index method
     *
     * @return void
     */
    public function index(): void
    {
        $this->browse('all', 20);
    }

    /**
     * Browse images
     *
     * @return \Cake\Http\Response|null
     */
    public function browseImages(): ?Response
    {
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout('ajax');
        }

        $this->sortField = 'date';
        $this->sortDirection = 'desc';
        $this->browse('images', 12);

        return $this->render('browse');
    }

    /**
     * Browse files
     *
     * @return \Cake\Http\Response|null
     */
    public function browseFiles(): ?Response
    {
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout('ajax');
        }

        $this->sortField = 'date';
        $this->sortDirection = 'desc';
        $this->browse('files', 10);

        return $this->render('browse');
    }

    /**
     * Upload files
     *
     * @return \Cake\Http\Response|null
     */
    public function upload(): ?Response
    {
        $this->autoRender = false;

        if (!$this->request->is('post')) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid request'));
        }

        $folder = $this->request->getQuery('folder');
        $folderUrl = ['action' => 'index', '?' => ['folder' => $folder]];

        if (empty($folder) || !is_string($folder) || !$this->fileManager->isValidFolder($folder)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder'));
        }

        $fullPath = $this->fileManager->getFullPath($folder);

        if (!is_dir($fullPath) && !mkdir($fullPath, 0755, true)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder') . ' /' . $folder);
        }

        $uploadedFiles = $this->request->getUploadedFiles();

        $filesToProcess = [];
        if (isset($uploadedFiles['files']) && is_array($uploadedFiles['files'])) {
            $filesToProcess = $uploadedFiles['files'];
        } elseif (isset($uploadedFiles['file'])) {
            $filesToProcess = [$uploadedFiles['file']];
        }

        if ($filesToProcess === []) {
            return $this->errorResponse(__d('brammo/admin', 'No files uploaded'), $folderUrl);
        }

        $files = [];
        $errors = [];
        foreach ($filesToProcess as $uploadedFile) {
            $res = $this->fileManager->processUploadedFile($folder, $uploadedFile);
            if ($res['error']) {
                $errors[] = $res['error'];
            } else {
                $files[] = $res['filename'];
            }
        }

        $res = ['error' => false];
        if ($errors) {
            $res['errors'] = $errors;
            $res['error'] = implode("\r\n", $errors);
        }

        $filesCount = count($files);
        if ($filesCount > 0) {
            $res['message'] = $filesCount . ' ' . __d('brammo/admin', 'file(s) uploaded');
            $res['files'] = $files;
        }

        if ($this->request->is('ajax')) {
            return $this->returnJson($res);
        }

        if (!empty($res['error'])) {
            $this->Flash->error($res['error']);
        }
        if (!empty($res['message'])) {
            $this->Flash->success($res['message']);
        }

        return $this->redirect($folderUrl);
    }

    /**
     * Create a folder
     *
     * @return \Cake\Http\Response|null
     */
    public function createFolder(): ?Response
    {
        if ($this->request->is('ajax')) {
            $this->autoRender = false;
        }

        $folder = $this->request->getQuery('folder');
        if (!is_string($folder) || !$this->fileManager->isValidFolder($folder)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder'));
        }

        $returnUrl = ['action' => 'index', '?' => compact('folder')];

        if ($this->request->is('post')) {
            $newFolder = $this->request->getData('folder');
            if (empty($newFolder)) {
                return $this->errorResponse(__d('brammo/admin', 'Missing folder name'), $returnUrl);
            }

            $newFolder = $this->fileManager->sanitizeFilename((string)$newFolder);
            if ($newFolder === '') {
                return $this->errorResponse(__d('brammo/admin', 'Invalid folder name'), $returnUrl);
            }

            $fullPath = $this->fileManager->getFullPath($folder);
            if (!mkdir($fullPath . DS . $newFolder, 0755, true)) {
                return $this->errorResponse(
                    __d('brammo/admin', 'Cannot create folder') . ' ' . $folder . DS . $newFolder,
                    $returnUrl,
                );
            }

            return $this->successResponse(
                __d('brammo/admin', 'Successfully created folder') . ' ' . $folder . DS . $newFolder,
                ['action' => 'index', '?' => ['folder' => $folder . DS . $newFolder]],
            );
        }

        $this->set(compact('folder'));

        return null;
    }

    /**
     * Delete a file
     *
     * @return \Cake\Http\Response|null
     */
    public function delete(): ?Response
    {
        $this->autoRender = false;

        if (!$this->request->is('post')) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid request'));
        }

        $folder = $this->request->getQuery('folder');
        $returnUrl = ['action' => 'index', '?' => compact('folder')];

        if (empty($folder) || !$this->fileManager->isValidFolder($folder)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder'), $returnUrl);
        }

        $deleteFile = $this->request->getQuery('deleteFile');
        $deleteFolder = $this->request->getQuery('deleteFolder');
        $fullPath = $this->fileManager->getFullPath($folder);

        if (!$this->fileManager->isPathWithinBase($fullPath)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder'), $returnUrl);
        }

        if (!empty($deleteFile)) {
            $deleteFile = $this->fileManager->sanitizeFilename((string)$deleteFile);
            $fullPath .= '/' . $deleteFile;
            if (!file_exists($fullPath) || !is_file($fullPath) || !is_writable($fullPath)) {
                return $this->errorResponse(__d('brammo/admin', 'Invalid file'), $returnUrl);
            }
            if (!unlink($fullPath)) {
                return $this->errorResponse(__d('brammo/admin', 'The file could not be deleted'), $returnUrl);
            }
        } elseif (!empty($deleteFolder)) {
            $deleteFolder = $this->fileManager->sanitizeFilename((string)$deleteFolder);
            $fullPath .= '/' . $deleteFolder;
            if (!file_exists($fullPath) || !is_dir($fullPath) || !is_writable($fullPath)) {
                return $this->errorResponse(__d('brammo/admin', 'Invalid folder'), $returnUrl);
            }

            $dirContents = scandir($fullPath);
            $dirContents = $dirContents !== false
                ? array_diff($dirContents, ['.', '..'])
                : [];

            if (count($dirContents) !== 0) {
                return $this->errorResponse(__d('brammo/admin', 'The folder is not empty'), $returnUrl);
            }
            if (!rmdir($fullPath)) {
                return $this->errorResponse(__d('brammo/admin', 'The folder could not be deleted'), $returnUrl);
            }
        }

        return $this->successResponse(__d('brammo/admin', 'Successfully deleted'), $returnUrl);
    }

    /**
     * Fix image size
     *
     * @return \Cake\Http\Response|null
     */
    public function fixImage(): ?Response
    {
        $this->autoRender = false;

        $folderQuery = $this->request->getQuery('folder');
        $fileQuery = $this->request->getQuery('file');
        $folder = is_string($folderQuery) ? urldecode($folderQuery) : '';
        $filename = is_string($fileQuery) ? $this->fileManager->sanitizeFilename(urldecode($fileQuery)) : '';

        if ($folder === '' || !$this->fileManager->isValidFolder($folder)) {
            $this->Flash->error(__d('brammo/admin', 'Invalid folder'));

            return $this->redirect(['action' => 'index']);
        }

        $returnUrl = ['action' => 'index', '?' => compact('folder')];
        $fullPath = $this->fileManager->getFullPath($folder) . DS . $filename;

        if ($filename === '' || !file_exists($fullPath)) {
            $this->Flash->error(__d('brammo/admin', 'Invalid file'));

            return $this->redirect($returnUrl);
        }

        $fileType = $this->fileManager->getFileType($fullPath);
        if (!in_array($fileType, $this->fileManager->getImageTypes(), true)) {
            $this->Flash->error(__d('brammo/admin', 'The specified file is not an image'));

            return $this->redirect($returnUrl);
        }

        if (!$this->fileManager->fixImageSize($fullPath)) {
            return $this->errorResponse(__d('brammo/admin', 'Cannot fix image size'), $returnUrl);
        }

        return $this->successResponse(__d('brammo/admin', 'Image size fixed'), $returnUrl);
    }

    /**
     * @param array<string, mixed> $data Data
     */
    private function returnJson(array $data): Response
    {
        $json = json_encode($data);

        return $this->response
            ->withType('application/json')
            ->withStringBody($json !== false ? $json : '{"error":"JSON encoding failed"}');
    }

    /**
     * Return JSON error response.
     */
    private function returnJsonError(string $message): Response
    {
        return $this->returnJson(['error' => $message]);
    }

    /**
     * @param array<string, mixed>|string $redirectUrl Redirect URL for non-ajax requests
     */
    private function errorResponse(string $message, array|string $redirectUrl = ['action' => 'index']): Response
    {
        if ($this->request->is('ajax')) {
            return $this->returnJsonError($message);
        }

        $this->Flash->error($message);

        /** @var \Cake\Http\Response */
        return $this->redirect($redirectUrl);
    }

    /**
     * @param array<string, mixed>|string $redirectUrl Redirect URL for non-ajax requests
     */
    private function successResponse(string $message, array|string $redirectUrl = ['action' => 'index']): Response
    {
        if ($this->request->is('ajax')) {
            return $this->returnJson(['success' => $message]);
        }

        $this->Flash->success($message);

        /** @var \Cake\Http\Response */
        return $this->redirect($redirectUrl);
    }

    /**
     * Browse folder contents and set view variables.
     *
     * @param string $type Unused browse filter hint (kept for action compatibility)
     */
    private function browse(string $type, int $itemsPerPage): void
    {
        $folder = $this->request->getQuery('folder') ?: '';
        if ($folder !== '' && !$this->fileManager->isValidFolder($folder)) {
            $folder = '';
        }

        $filter = $this->request->getQuery('filter') ?: '';

        $fullPath = $this->fileManager->getFullPath($folder);

        $items = $this->fileManager->readFolder($fullPath, $folder, $filter);

        $sortField = $this->sortField;
        $sortDirection = $this->sortDirection;
        uasort(
            $items,
            fn(array $a, array $b): int => $this->fileManager->sortFiles($a, $b, $sortField, $sortDirection),
        );

        $pages = (int)ceil(count($items) / $itemsPerPage);
        $page = min(max((int)$this->request->getQuery('page'), 1), $pages);
        $first = ($page - 1) * $itemsPerPage;
        $this->set(compact('page', 'pages', 'first'));

        $items = array_slice($items, $first, $itemsPerPage);

        foreach ($items as $i => $item) {
            if ($item['type'] === FileManagerService::TYPE_FOLDER) {
                $items[$i]['size'] = $this->fileManager->folderCount($fullPath . DS . $item['filename']);
            } else {
                $filePath = $fullPath . DS . $item['filename'];
                $fileType = $this->fileManager->getFileType($filePath);
                $fileSize = filesize($filePath);

                $items[$i]['type'] = $fileType;
                $items[$i]['size'] = $fileSize !== false ? $fileSize : 0;

                if (in_array($fileType, $this->fileManager->getImageTypes(), true)) {
                    $imageSize = getimagesize($filePath);
                    $items[$i]['image'] = true;
                    $items[$i]['width'] = $imageSize !== false ? $imageSize[0] : 0;
                    $items[$i]['height'] = $imageSize !== false ? $imageSize[1] : 0;
                }
            }
        }

        $target = $this->request->getQuery('target') ?: '';

        $this->set(compact('folder', 'filter', 'target', 'items'));
        $this->set('sort', $this->sortField);
        $this->set('sortDirection', $this->sortDirection);
    }
}
