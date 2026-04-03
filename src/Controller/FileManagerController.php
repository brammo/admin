<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller;

use Cake\Core\Configure;
use Cake\Http\Response;
use Exception;
use Imagick;
use ImagickException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use function Cake\I18n\__d;

/**
 * File Manager Controller
 */
class FileManagerController extends AppController
{
    /**
     * Folder type constant
     */
    private const string TYPE_FOLDER = '[folder]';

    /**
     * Sort field
     *
     * @var string
     */
    private string $sortField = 'filename';

    /**
     * Sort direction
     *
     * @var string
     */
    private string $sortDirection = 'asc';

    /**
     * Base path
     *
     * @var string
     */
    private string $basePath = '';

    /**
     * Top level folders
     *
     * @var array<string>
     */
    private array $topFolders = [];

    /**
     * File types
     *
     * @var array<string>
     */
    private array $fileTypes = [];

    /**
     * Image types
     *
     * @var array<string>
     */
    private array $imageTypes = [];

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $config = Configure::read('Admin.FileManager');

        $this->basePath = $config['basePath'] ?? '';
        if (empty($this->basePath)) {
            throw new RuntimeException('File Manager base path is not configured');
        }

        $this->topFolders = $config['topFolders'] ?? [];
        if (empty($this->topFolders)) {
            throw new RuntimeException('File Manager top folders are not configured');
        }

        $this->fileTypes = $config['fileTypes']['files'] ?? [];
        if (empty($this->fileTypes)) {
            throw new RuntimeException('File Manager file types are not configured');
        }

        $this->imageTypes = $config['fileTypes']['images'] ?? [];
        if (empty($this->imageTypes)) {
            throw new RuntimeException('File Manager image types are not configured');
        }
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
     * @return void
     */
    public function browseImages(): void
    {
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout('ajax');
        }

        $this->sortField = 'date';
        $this->sortDirection = 'desc';
        $this->browse('images', 12);
        $this->render('browse');
    }

    /**
     * Browse files
     *
     * @return void
     */
    public function browseFiles(): void
    {
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout('ajax');
        }

        $this->sortField = 'date';
        $this->sortDirection = 'desc';
        $this->browse('files', 10);
        $this->render('browse');
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

        if (empty($folder) || !is_string($folder) || !$this->isValidFolder($folder)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder'));
        }

        $fullPath = $this->getFullPath($folder);

        if (!file_exists($fullPath) && !@mkdir($fullPath, 0755, true)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder') . ' /' . $folder);
        }

        $uploadedFiles = $this->request->getUploadedFiles();

        // Collect uploaded files from either files[] or file field
        $filesToProcess = [];
        if (isset($uploadedFiles['files']) && is_array($uploadedFiles['files'])) {
            $filesToProcess = $uploadedFiles['files'];
        } elseif (isset($uploadedFiles['file'])) {
            $filesToProcess = [$uploadedFiles['file']];
        }

        if (empty($filesToProcess)) {
            return $this->errorResponse(__d('brammo/admin', 'No files uploaded'), $folderUrl);
        }

        $files = [];
        $errors = [];
        foreach ($filesToProcess as $uploadedFile) {
            $res = $this->processUploadedFile($folder, $uploadedFile);
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
        if (!is_string($folder) || !$this->isValidFolder($folder)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder'));
        }

        $returnUrl = ['action' => 'index', '?' => compact('folder')];

        if ($this->request->is('post')) {
            $newFolder = $this->request->getData('folder');
            if (empty($newFolder)) {
                return $this->errorResponse(__d('brammo/admin', 'Missing folder name'), $returnUrl);
            }

            $newFolder = $this->sanitizeFilename((string)$newFolder);
            if (empty($newFolder)) {
                return $this->errorResponse(__d('brammo/admin', 'Invalid folder name'), $returnUrl);
            }

            $fullPath = $this->getFullPath($folder);
            if (!@mkdir($fullPath . DS . $newFolder, 0755, true)) {
                return $this->errorResponse(
                    __d('brammo/admin', 'Cannot create folder') . ' ' . $folder . DS . $newFolder,
                    $returnUrl,
                );
            }

            return $this->successResponse(
                __d('brammo/admin', 'Successfuly created folder') . ' ' . $folder . DS . $newFolder,
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

        if (empty($folder) || !$this->isValidFolder($folder)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder'), $returnUrl);
        }

        $deleteFile = $this->request->getQuery('deleteFile');
        $deleteFolder = $this->request->getQuery('deleteFolder');
        $fullPath = $this->getFullPath($folder);

        if (!$this->isPathWithinBase($fullPath)) {
            return $this->errorResponse(__d('brammo/admin', 'Invalid folder'), $returnUrl);
        }

        if (!empty($deleteFile)) {
            $deleteFile = $this->sanitizeFilename((string)$deleteFile);
            $fullPath .= '/' . $deleteFile;
            if (!file_exists($fullPath) || !is_file($fullPath) || !is_writable($fullPath)) {
                return $this->errorResponse(__d('brammo/admin', 'Invalid file'), $returnUrl);
            }
            if (!unlink($fullPath)) {
                return $this->errorResponse(__d('brammo/admin', 'The file could not be deleted'), $returnUrl);
            }
        } elseif (!empty($deleteFolder)) {
            $deleteFolder = $this->sanitizeFilename((string)$deleteFolder);
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
            if (!@rmdir($fullPath)) {
                return $this->errorResponse(__d('brammo/admin', 'The folder could not be deleted'), $returnUrl);
            }
        }

        return $this->successResponse(__d('brammo/admin', 'Successfuly deleted'), $returnUrl);
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
        $filename = is_string($fileQuery) ? $this->sanitizeFilename(urldecode($fileQuery)) : '';

        if (empty($folder) || !$this->isValidFolder($folder)) {
            $this->Flash->error(__d('brammo/admin', 'Invalid folder'));

            return $this->redirect(['action' => 'index']);
        }

        $returnUrl = ['action' => 'index', '?' => compact('folder')];
        $fullPath = $this->getFullPath($folder) . DS . $filename;

        if (empty($filename) || !file_exists($fullPath)) {
            $this->Flash->error(__d('brammo/admin', 'Invalid file'));

            return $this->redirect($returnUrl);
        }

        $fileType = $this->getFileType($fullPath);
        if (!in_array($fileType, $this->imageTypes, true)) {
            $this->Flash->error(__d('brammo/admin', 'The specified file is not an image'));

            return $this->redirect($returnUrl);
        }

        if (!$this->fixImageSize($fullPath)) {
            return $this->errorResponse(__d('brammo/admin', 'Cannot fix image size'), $returnUrl);
        }

        return $this->successResponse(__d('brammo/admin', 'Image size fixed'), $returnUrl);
    }

    /**
     * Get full path
     *
     * @param string $path Path
     * @return string Full path
     */
    private function getFullPath(string $path): string
    {
        $fullPath = rtrim($this->basePath, DS);
        if (!empty($path)) {
            $fullPath .= DS . $path;
        }

        return $fullPath;
    }

    /**
     * Return JSON response
     *
     * @param array<string, mixed> $data Data
     * @return \Cake\Http\Response
     */
    private function returnJson(array $data): Response
    {
        $json = json_encode($data);

        return $this->response
            ->withType('application/json')
            ->withStringBody($json !== false ? $json : '{"error":"JSON encoding failed"}');
    }

    /**
     * Return JSON error response
     *
     * @param string $message Error message
     * @return \Cake\Http\Response
     */
    private function returnJsonError(string $message): Response
    {
        return $this->returnJson(['error' => $message]);
    }

    /**
     * Return an error response, choosing JSON or flash+redirect based on request type.
     *
     * @param string $message Error message
     * @param array<string, mixed>|string $redirectUrl Redirect URL for non-ajax requests
     * @return \Cake\Http\Response
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
     * Return a success response, choosing JSON or flash+redirect based on request type.
     *
     * @param string $message Success message
     * @param array<string, mixed>|string $redirectUrl Redirect URL for non-ajax requests
     * @return \Cake\Http\Response
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
     * Browse method
     *
     * @param string $type File type (images, files, all)
     * @return void
     */
    private function browse(string $type, int $itemsPerPage): void
    {
        $folder = $this->request->getQuery('folder') ?: '';
        if (!empty($folder) && !$this->isValidFolder($folder)) {
            $folder = '';
        }

        $filter = $this->request->getQuery('filter') ?: '';

        $fullPath = $this->getFullPath($folder);

        $items = $this->readFolder($fullPath, $folder, $filter);

        uasort($items, [$this, 'sortFiles']);

        $pages = (int)ceil(count($items) / $itemsPerPage);
        $page = min(max((int)$this->request->getQuery('page'), 1), $pages);
        $first = ($page - 1) * $itemsPerPage;
        $this->set(compact('page', 'pages', 'first'));

        $items = array_slice($items, $first, $itemsPerPage);

        foreach ($items as $i => $item) {
            if ($item['type'] === self::TYPE_FOLDER) {
                $items[$i]['size'] = $this->folderCount($fullPath . DS . $item['filename']);
            } else {
                $filePath = $fullPath . DS . $item['filename'];
                $fileType = $this->getFileType($filePath);
                $fileSize = @filesize($filePath);

                $items[$i]['type'] = $fileType;
                $items[$i]['size'] = $fileSize !== false ? $fileSize : 0;

                if (in_array($fileType, $this->imageTypes, true)) {
                    $imageSize = @getimagesize($filePath);
                    $items[$i]['image'] = true;
                    $items[$i]['width'] = $imageSize[0] ?? 0;
                    $items[$i]['height'] = $imageSize[1] ?? 0;
                }
            }
        }

        $target = $this->request->getQuery('target') ?: '';

        $this->set(compact('folder', 'filter', 'target', 'items'));
        $this->set('sort', $this->sortField);
        $this->set('sortDirection', $this->sortDirection);
    }

    /**
     * Reads a folder
     *
     * @param string $path Path
     * @param string $folder Folder
     * @param string $filter Filter
     * @return array<int, array<string, mixed>> Files and folders
     */
    private function readFolder(string $path, string $folder, string $filter = ''): array
    {
        $items = [];

        if (empty($path) || !file_exists($path)) {
            return $items;
        }

        $dirHandle = opendir($path);
        if ($dirHandle) {
            while (($filename = readdir($dirHandle)) !== false) {
                if ($filename == '.' || $filename == '..') {
                    continue;
                }

                $filePath = $path . DS . $filename;
                $mtime = filemtime($filePath);
                $dateStr = $mtime !== false ? date('Y-m-d H:i:s', $mtime) : '';

                if (is_dir($filePath)) {
                    if (
                        (empty($folder) && !in_array($filename, $this->topFolders, true)) ||
                        (!empty($filter) && stripos($filename, $filter) === false)
                    ) {
                        continue;
                    }

                    $items[] = [
                        'filename' => $filename,
                        'type' => self::TYPE_FOLDER,
                        'date' => $dateStr,
                    ];
                } else {
                    if (empty($folder) || (!empty($filter) && stripos($filename, $filter) === false)) {
                        continue;
                    }

                    $items[] = [
                        'filename' => $filename,
                        'type' => null,
                        'date' => $dateStr,
                    ];
                }
            }

            closedir($dirHandle);
        }

        return $items;
    }

    /**
     * Counts items in a folder
     *
     * @param string $path Path
     * @return int Number of items
     */
    private function folderCount(string $path): int
    {
        $count = 0;

        $dirHandle = opendir($path);
        if ($dirHandle) {
            while (($filename = readdir($dirHandle)) !== false) {
                if ($filename == '.' || $filename == '..') {
                    continue;
                }
                $count++;
            }

            closedir($dirHandle);
        }

        return $count;
    }

    /**
     * Sort files
     *
     * @param array<string, mixed> $file1 File 1
     * @param array<string, mixed> $file2 File 2
     * @return int
     */
    private function sortFiles(array $file1, array $file2): int
    {
        if ($file1['type'] === self::TYPE_FOLDER && $file2['type'] !== self::TYPE_FOLDER) {
            return -1;
        }

        if ($file1['type'] !== self::TYPE_FOLDER && $file2['type'] === self::TYPE_FOLDER) {
            return 1;
        }

        if ($file1[$this->sortField] < $file2[$this->sortField]) {
            $res = -1;
        } elseif ($file1[$this->sortField] > $file2[$this->sortField]) {
            $res = 1;
        } else {
            $res = 0;
        }

        return $this->sortDirection == 'asc' ? $res : -$res;
    }

    /**
     * Process an uploaded file using PSR-7 UploadedFileInterface
     *
     * @param string $folder Target folder
     * @param \Psr\Http\Message\UploadedFileInterface $uploadedFile Uploaded file
     * @return array<string, mixed> Result with error or filename
     */
    private function processUploadedFile(string $folder, UploadedFileInterface $uploadedFile): array
    {
        $filename = $uploadedFile->getClientFilename();
        if ($filename === null || $filename === '') {
            return ['error' => __d('brammo/admin', 'Invalid filename')];
        }
        $error = $uploadedFile->getError();

        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_NO_FILE) {
                return ['error' => $filename . ': ' . __d('brammo/admin', 'File not uploaded')];
            } elseif ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                return ['error' => $filename . ': ' . __d('brammo/admin', 'Exceeded file size limit')];
            }

            return ['error' => $filename . ': ' . __d('brammo/admin', 'Unknown error')];
        }

        $fullPath = $this->getFullPath($folder);
        $type = $this->getFileType($filename);

        $isFileType = in_array($type, $this->fileTypes);
        $isImageType = in_array($type, $this->imageTypes);

        if (!$isFileType && !$isImageType) {
            return ['error' => __d('brammo/admin', 'Invalid file type') . ' ' . $type];
        }

        $filename = $this->getUniqueFilename($fullPath, $filename);

        try {
            $uploadedFile->moveTo($fullPath . DS . $filename);
        } catch (Exception $e) {
            return ['error' => __d('brammo/admin', 'Cannot save file') . ' ' . $filename];
        }

        if ($isImageType && Configure::read('Admin.FileManager.Images.resizeOnUpload')) {
            $this->fixImageSize($fullPath . DS . $filename);
        }

        return [
            'error' => false,
            'filename' => $filename,
        ];
    }

    /**
     * Validate path - checks if path starts with allowed top folder
     * and does not contain path traversal sequences.
     *
     * @param string|null $path Path
     * @return bool
     */
    private function isValidFolder(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        // Block path traversal sequences
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return false;
        }

        $parts = explode(DS, $path);

        return in_array($parts[0], $this->topFolders, true);
    }

    /**
     * Validate that a resolved path is within the base path.
     *
     * @param string $fullPath The resolved path to validate.
     * @return bool
     */
    private function isPathWithinBase(string $fullPath): bool
    {
        $realBase = realpath($this->basePath);
        $realPath = realpath($fullPath);

        if ($realBase === false || $realPath === false) {
            return false;
        }

        return str_starts_with($realPath, $realBase . DS) || $realPath === $realBase;
    }

    /**
     * Sanitize a filename by removing path separators and traversal sequences.
     *
     * @param string $name The filename to sanitize.
     * @return string
     */
    private function sanitizeFilename(string $name): string
    {
        // Remove null bytes, path separators, and traversal sequences
        $name = str_replace(["\0", '/', '\\', '..'], '', $name);

        return basename($name);
    }

    /**
     * Get unique filename
     *
     * @param string $fullPath Full path
     * @param string $filename Filename
     * @return string Fixed filename
     */
    private function getUniqueFilename(string $fullPath, string $filename): string
    {
        // Sanitize: use basename, replace spaces and unsafe chars
        $filename = basename($filename);
        $filename = preg_replace('/[^\w.\-()]/', '_', $filename) ?: $filename;

        if (file_exists($fullPath . DS . $filename)) {
            $match = [];
            if (preg_match('/(.+)(\..{1,4})$/', $filename, $match)) {
                $name = $match[1];
                $ext = $match[2];
            } else {
                $name = $filename;
                $ext = '';
            }

            $i = 0;
            do {
                $filename = $name . '(' . ++$i . ')' . $ext;
            } while (file_exists($fullPath . DS . $filename));
        }

        return $filename;
    }

    /**
     * Fix image size
     *
     * @param string $path Path
     * @return bool
     */
    private function fixImageSize(string $path): bool
    {
        $config = Configure::read('Admin.FileManager.Images');

        $maxWidth = $config['maxWidth'] ?? 2048;
        $maxHeight = $config['maxHeight'] ?? 2048;

        $size = @getimagesize($path);
        if (!$size) {
            return false;
        }

        $width = $size[0];
        $height = $size[1];

        if ($width == 0 || $height == 0) {
            return false;
        }

        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }

        try {
            $image = new Imagick($path);
        } catch (ImagickException $e) {
            return false;
        }

        $newWidth = $width;
        $newHeight = $height;

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)round($height * $maxWidth / $width);
        }

        if ($newHeight > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = (int)round($width * $maxHeight / $height);
        }

        try {
            $res = $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_CATROM, 1, true);
        } catch (ImagickException $e) {
            $image->clear();

            return false;
        }

        if (!$res) {
            $image->clear();

            return false;
        }

        try {
            $fileType = $this->getFileType($path);
            switch ($fileType) {
                case 'png':
                    $image->setImageCompressionQuality(($config['pngQuality'] ?? 6) * 10);
                    break;
                case 'webp':
                    $image->setImageCompressionQuality($config['webpQuality'] ?? 90);
                    break;
                case 'jpg':
                    $image->setImageCompressionQuality($config['jpegQuality'] ?? 90);
                    break;
            }
            $image->writeImage($path);
        } catch (ImagickException $e) {
            $image->clear();

            return false;
        }

        $image->clear();

        return true;
    }

    /**
     * Get file type
     *
     * @param string $file_path File path
     * @return string File type
     */
    private function getFileType(string $file_path): string
    {
        return strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    }
}
