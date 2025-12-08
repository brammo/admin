<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller;

use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\UploadedFileInterface;

use function Cake\I18n\__d;

/**
 * File Manager Controller
 */
class FileManagerController extends AppController
{
    /**
     * Folder type constant
     */
    private const TYPE_FOLDER = '[folder]';

    /**
     * Items per page
     *
     * @var int
     */
    private int $itemsPerPage = 10;

    /**
     * Sort field
     * 
     * @var string
     */
    private string $sort = 'filename';

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
            throw new \RuntimeException('File Manager base path is not configured');
        }

        $this->topFolders = $config['topFolders'] ?? [];
        if (empty($this->topFolders)) {
            throw new \RuntimeException('File Manager top folders are not configured');
        }

        $this->fileTypes = $config['fileTypes']['files'] ?? [];
        if (empty($this->fileTypes)) {
            throw new \RuntimeException('File Manager file types are not configured');
        }

        $this->imageTypes = $config['fileTypes']['images'] ?? [];
        if (empty($this->imageTypes)) {
            throw new \RuntimeException('File Manager image types are not configured');
        }
    }

    /**
     * Index method
     * 
     * @return void
     */    
    public function index(): void
    {
        $this->browse();
    }
    
    /**
     * Images method
     * 
     * @return void
     */
    public function images(): void
    {
        $this->itemsPerPage = 12;
        $this->sort = 'date';
        $this->sortDirection = 'desc';

        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout('ajax');
        }
        
        $this->browse();
    }
    
    /**
     * Files method
     * 
     * @return void
     */
    public function files(): void
    {
        $this->itemsPerPage = 10;
        $this->sort = 'date';
        $this->sortDirection = 'desc';
        
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setLayout('ajax');
        }
        
        $this->browse();
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
            return $this->returnJsonError(__d('brammo/admin', 'Invalid request'));
        }

        $folder = $this->request->getQuery('folder');
        if (empty($folder)) {
            if ($this->request->is('ajax')) {
                return $this->returnJsonError(__d('brammo/admin', 'Invalid folder'));
            } else {
                $this->Flash->error(__d('brammo/admin', 'Invalid folder'));
                return $this->redirect(['action' => 'index']);
            }
        }

        if (!is_string($folder) || !$this->isValidFolder($folder)) {
            if ($this->request->is('ajax')) {
                return $this->returnJsonError(__d('brammo/admin', 'Invalid folder'));
            } else {
                $this->Flash->error(__d('brammo/admin', 'Invalid folder'));
                return $this->redirect(['action' => 'index']);
            }
        }
        
        $fullPath = $this->getFullPath($folder);
        
        if (!file_exists($fullPath) && !@mkdir($fullPath, 0755, true)) {
            if ($this->request->is('ajax')) {
                return $this->returnJsonError(__d('brammo/admin', 'Invalid folder') . ' /' . $folder);
            } else {
                $this->Flash->error(__d('brammo/admin', 'Invalid folder') . ' /' . $folder);
                return $this->redirect(['action' => 'index']);
            }
        }
        
        $uploadedFiles = $this->request->getUploadedFiles();
        
        if (empty($uploadedFiles)) {
            if ($this->request->is('ajax')) {
                return $this->returnJsonError(__d('brammo/admin', 'No files uploaded'));
            } else {
                $this->Flash->error(__d('brammo/admin', 'No files uploaded'));
                return $this->redirect(['action' => 'index', '?' => ['folder' => $folder]]);
            }
        }
        
        $files = [];
        $errors = [];
        
        // Handle multiple files (files[])
        if (isset($uploadedFiles['files']) && is_array($uploadedFiles['files'])) {
            foreach ($uploadedFiles['files'] as $uploadedFile) {
                $res = $this->processUploadedFile($folder, $uploadedFile);
                
                if ($res['error']) {
                    $errors[] = $res['error'];
                } else {
                    $files[] = $res['filename'];
                }
            }
        // Handle single file (file)
        } elseif (isset($uploadedFiles['file'])) {
            $res = $this->processUploadedFile($folder, $uploadedFiles['file']);
            
            if ($res['error']) {
                $errors[] = $res['error'];
            } else {
                $files[] = $res['filename'];
            }
        } else {
            if ($this->request->is('ajax')) {
                return $this->returnJsonError(__d('brammo/admin', 'No files uploaded'));
            } else {
                $this->Flash->error(__d('brammo/admin', 'No files uploaded'));
                return $this->redirect(['action' => 'index', '?' => ['folder' => $folder]]);
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
            $res['file'] = implode("\r\n", $files);
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

        return $this->redirect(['action' => 'index', '?' => ['folder' => $folder]]);
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
            if ($this->request->is('ajax')) {
                return $this->returnJsonError(__d('brammo/admin', 'Invalid folder'));
            } else {
                $this->Flash->error(__d('brammo/admin', 'Invalid folder'));
                return $this->redirect(['action' => 'index']);
            }
        }
        
        $returnUrl = ['action' => 'index', '?' => compact('folder')];
        
        if ($this->request->is('post')) {

            $newFolder = $this->request->getData('folder');
            if (empty($newFolder)) {
                if ($this->request->is('ajax')) {
                    return $this->returnJsonError(__d('brammo/admin', 'Missing folder name'));
                } else {
                    $this->Flash->error(__d('brammo/admin', 'Missing folder name'));
                    return $this->redirect($returnUrl);
                }
            }
            
            $fullPath = $this->getFullPath($folder);
        
            if (!@mkdir($fullPath . DS . $newFolder, 0755, true)) {
                if ($this->request->is('ajax')) {
                    return $this->returnJsonError(__d('brammo/admin', 'Cannot create folder') . ' ' . $folder . DS . $newFolder);
                } else {
                    $this->Flash->error(__d('brammo/admin', 'Cannot create folder') . ' ' . $folder . DS . $newFolder);
                    return $this->redirect($returnUrl);
                }
            }

            if ($this->request->is('ajax')) {
                return $this->returnJson(['success' => __d('brammo/admin', 'Successfuly created folder') . ' ' . $folder . DS . $newFolder]);
            } else {
                $this->Flash->success(__d('brammo/admin', 'Successfuly created folder') . ' ' . $folder . DS . $newFolder);
                return $this->redirect(['action' => 'index', '?' => ['folder' => $folder . DS . $newFolder]]);
            }
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
            if ($this->request->is('ajax')) {
                return $this->returnJsonError(__d('brammo/admin', 'Invalid request'));
            } else {
                $this->Flash->error(__d('brammo/admin', 'Invalid request'));
                return $this->redirect(['action' => 'index']);
            }
        }

        $folder = $this->request->getQuery('folder');
        
        $returnUrl = ['action' => 'index', '?' => compact('folder')];

        if (empty($folder) || !$this->isValidFolder($folder)) {
            if ($this->request->is('ajax')) {
                return $this->returnJsonError(__d('brammo/admin', 'Invalid folder'));
            } else {
                $this->Flash->error(__d('brammo/admin', 'Invalid folder'));
                return $this->redirect($returnUrl);
            }
        }

        $deleteFile = $this->request->getQuery('deleteFile');
        $deleteFolder = $this->request->getQuery('deleteFolder');
        $fullPath = $this->getFullPath($folder);

        if (!empty($deleteFile)) {
            
            $fullPath .= '/' . $deleteFile;
            if (!file_exists($fullPath) || !is_file($fullPath) || !is_writable($fullPath)) {
                if ($this->request->is('ajax')) {
                    return $this->returnJsonError(__d('brammo/admin', 'Invalid file'));
                } else {
                    $this->Flash->error(__d('brammo/admin', 'Invalid file'));
                    return $this->redirect($returnUrl);
                }
            }

            if (!unlink($fullPath)) {
                if ($this->request->is('ajax')) {
                    return $this->returnJsonError(__d('brammo/admin', 'The file could not be deleted'));
                } else {
                    $this->Flash->error(__d('brammo/admin', 'The file could not be deleted'));
                    return $this->redirect($returnUrl);
                }
            }
            
        } elseif (!empty($deleteFolder)) {

            $fullPath .= '/' . $deleteFolder;
            if (!file_exists($fullPath) || !is_dir($fullPath) || !is_writable($fullPath)) {
                if ($this->request->is('ajax')) {
                    return $this->returnJsonError(__d('brammo/admin', 'Invalid folder'));
                } else {
                    $this->Flash->error(__d('brammo/admin', 'Invalid folder'));
                    return $this->redirect($returnUrl);
                }
            }

            $dirContents = scandir($fullPath);
            if ($dirContents === false) {
                $dirContents = [];
            }

            $dirContents = array_filter($dirContents, function ($item) {
                return $item !== '.' && $item !== '..';
            });

            if (count($dirContents) !== 0) {
                if ($this->request->is('ajax')) {
                    return $this->returnJsonError(__d('brammo/admin', 'The folder is not empty'));
                } else {
                    $this->Flash->error(__d('brammo/admin', 'The folder is not empty'));
                    return $this->redirect($returnUrl);
                }
            }

            if (!@rmdir($fullPath)) {
                if ($this->request->is('ajax')) {
                    return $this->returnJsonError(__d('brammo/admin', 'The folder could not be deleted'));
                } else {
                    $this->Flash->error(__d('brammo/admin', 'The folder could not be deleted'));
                    return $this->redirect($returnUrl);
                }
            }
        }
        
        if ($this->request->is('ajax')) {
            return $this->returnJson(['success' => __d('brammo/admin', 'Successfuly deleted')]);
        }

        return $this->redirect($returnUrl);
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
        $filename = is_string($fileQuery) ? urldecode($fileQuery) : '';
        
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
            $this->Flash->error(__d('brammo/admin', 'Cannot fix image size'));
            return $this->redirect($returnUrl);
        }
        
        if ($this->request->is('ajax')) {
            return $this->returnJson(['success' => __d('brammo/admin', 'Image size fixed')]);
        } else {
            $this->Flash->success(__d('brammo/admin', 'Image size fixed'));
        } 
        
        return $this->redirect($returnUrl);
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
     * Browse method
     * 
     * @return void
     */
    private function browse(): void
    {
        $folder = $this->request->getQuery('folder') ?: '';
        if (!empty($folder) && !$this->isValidFolder($folder)) {
            $folder = '';
        }

        $filter = $this->request->getQuery('filter') ?: '';
        
        $fullPath = $this->getFullPath($folder);
        
        $items = $this->readFolder($fullPath, $folder, $filter);
        
        uasort($items, [$this, 'sortFiles']);
        
        if ($this->itemsPerPage) {
            
            $pages = (int)ceil(count($items) / $this->itemsPerPage);
            $page = min(max((int)$this->request->getQuery('page'), 1), $pages);
            $first = ($page - 1) * $this->itemsPerPage;

            $this->set(compact('page', 'pages', 'first'));
            
            $items = array_slice($items, $first, $this->itemsPerPage);
        }
        
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
        $this->set('sort', $this->sort);
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

            while (false !== ($filename = readdir($dirHandle))) {

                if ($filename == '.' || $filename == '..') {
                    continue;
                }

                $filePath = $path . DS . $filename;
                $mtime = filemtime($filePath);
                $dateStr = $mtime !== false ? date('Y-m-d H:i:s', $mtime) : '';

                if (is_dir($filePath)) {
                    if ((empty($folder) && !in_array($filename, $this->topFolders, true)) ||
                        (!empty($filter) && stripos($filename, $filter) === false)) {
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
            while (false !== ($filename = readdir($dirHandle))) {
                if ($filename == '.' || $filename == '..') {
                    continue;
                }
                $count ++;
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
        
        if ($file1[$this->sort] < $file2[$this->sort]) {
            $res = -1;
        } elseif ($file1[$this->sort] > $file2[$this->sort]) {
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
     * @param UploadedFileInterface $uploadedFile Uploaded file
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
        } catch (\Exception $e) {
            return ['error' => __d('brammo/admin', 'Cannot save file') . ' ' . $filename];
        }

        if ($isImageType && Configure::read('Admin.FileManager.Images.fixOnUpload')) {
            $this->fixImageSize($fullPath . DS . $filename);
        }
        
        return [
            'error' => false,
            'filename' => $filename
        ];
    }

    /**
     * Validate path - checks if path starts with allowed top folder
     * 
     * @param string|null $path Path
     * @return bool
     */
    private function isValidFolder(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }
        $parts = explode(DS, $path);

        return in_array($parts[0], $this->topFolders, true);
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
        $filename = str_replace(' ', '_', $filename);
        
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
            }
            while (file_exists($fullPath . DS . $filename));
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
            $image = new \Imagick($path);
        } catch (\ImagickException $e) {
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
            $res = $image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_CATROM, 1, true);
        } catch (\ImagickException $e) {
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

        } catch (\ImagickException $e) {
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
