<?php
declare(strict_types=1);

namespace Brammo\Admin\FileManager;

use Cake\Core\Configure;
use Exception;
use Imagick;
use ImagickException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use function Cake\I18n\__d;

/**
 * File system operations for the admin file manager.
 */
class FileManagerService
{
    public const string TYPE_FOLDER = '[folder]';

    /**
     * @param string $basePath Base filesystem path for stored files
     * @param list<string> $topFolders Allowed top-level folder names
     * @param list<string> $fileTypes Allowed document extensions
     * @param list<string> $imageTypes Allowed image extensions
     */
    public function __construct(
        private readonly string $basePath,
        private readonly array $topFolders,
        private readonly array $fileTypes,
        private readonly array $imageTypes,
    ) {
    }

    /**
     * Build service from Admin.FileManager configuration.
     *
     * @throws \RuntimeException When required configuration is missing
     */
    public static function fromConfigure(): self
    {
        $config = Configure::read('Admin.FileManager');

        $basePath = $config['basePath'] ?? '';
        if ($basePath === '') {
            throw new RuntimeException('File Manager base path is not configured');
        }

        $topFolders = $config['topFolders'] ?? [];
        if ($topFolders === []) {
            throw new RuntimeException('File Manager top folders are not configured');
        }

        $fileTypes = $config['fileTypes']['files'] ?? [];
        if ($fileTypes === []) {
            throw new RuntimeException('File Manager file types are not configured');
        }

        $imageTypes = $config['fileTypes']['images'] ?? [];
        if ($imageTypes === []) {
            throw new RuntimeException('File Manager image types are not configured');
        }

        return new self($basePath, $topFolders, $fileTypes, $imageTypes);
    }

    /**
     * @return list<string>
     */
    public function getTopFolders(): array
    {
        return $this->topFolders;
    }

    /**
     * @return list<string>
     */
    public function getImageTypes(): array
    {
        return $this->imageTypes;
    }

    /**
     * Resolve a relative folder path to an absolute path under basePath.
     */
    public function getFullPath(string $path): string
    {
        $fullPath = rtrim($this->basePath, DS);
        if ($path !== '') {
            $fullPath .= DS . $path;
        }

        return $fullPath;
    }

    /**
     * Validate path — allowed top folder and no traversal sequences.
     */
    public function isValidFolder(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return false;
        }

        $parts = explode(DS, $path);

        return in_array($parts[0], $this->topFolders, true);
    }

    /**
     * Validate that a resolved path is within the base path.
     */
    public function isPathWithinBase(string $fullPath): bool
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
     */
    public function sanitizeFilename(string $name): string
    {
        $name = str_replace(["\0", '/', '\\', '..'], '', $name);

        return basename($name);
    }

    /**
     * Return a unique, sanitized filename within the target directory.
     */
    public function getUniqueFilename(string $fullPath, string $filename): string
    {
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
     * Get lowercase file extension from a path or filename.
     */
    public function getFileType(string $filePath): string
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }

    /**
     * @return array<string, mixed>
     */
    public function processUploadedFile(string $folder, UploadedFileInterface $uploadedFile): array
    {
        $filename = $uploadedFile->getClientFilename();
        if ($filename === null || $filename === '') {
            return ['error' => __d('brammo/admin', 'Invalid filename')];
        }
        $error = $uploadedFile->getError();

        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_NO_FILE) {
                return ['error' => $filename . ': ' . __d('brammo/admin', 'File not uploaded')];
            }
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                return ['error' => $filename . ': ' . __d('brammo/admin', 'Exceeded file size limit')];
            }

            return ['error' => $filename . ': ' . __d('brammo/admin', 'Unknown error')];
        }

        $fullPath = $this->getFullPath($folder);
        $type = $this->getFileType($filename);

        $isFileType = in_array($type, $this->fileTypes, true);
        $isImageType = in_array($type, $this->imageTypes, true);

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
     * Resize an image to configured max dimensions when it exceeds limits.
     */
    public function fixImageSize(string $path): bool
    {
        $config = Configure::read('Admin.FileManager.Images');

        $maxWidth = $config['maxWidth'] ?? 2048;
        $maxHeight = $config['maxHeight'] ?? 2048;

        $size = getimagesize($path);
        if ($size === false) {
            return false;
        }

        $width = $size[0];
        $height = $size[1];

        if ($width === 0 || $height === 0) {
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
     * @return list<array<string, mixed>>
     */
    public function readFolder(string $path, string $folder, string $filter = ''): array
    {
        $items = [];

        if ($path === '' || !file_exists($path)) {
            return $items;
        }

        $dirHandle = opendir($path);
        if ($dirHandle) {
            while (($filename = readdir($dirHandle)) !== false) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }

                $filePath = $path . DS . $filename;
                $mtime = filemtime($filePath);
                $dateStr = $mtime !== false ? date('Y-m-d H:i:s', $mtime) : '';

                if (is_dir($filePath)) {
                    if (
                        ($folder === '' && !in_array($filename, $this->topFolders, true)) ||
                        ($filter !== '' && stripos($filename, $filter) === false)
                    ) {
                        continue;
                    }

                    $items[] = [
                        'filename' => $filename,
                        'type' => self::TYPE_FOLDER,
                        'date' => $dateStr,
                    ];
                } else {
                    if ($folder === '' || ($filter !== '' && stripos($filename, $filter) === false)) {
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
     * Count items in a folder (excluding . and ..).
     */
    public function folderCount(string $path): int
    {
        $count = 0;

        $dirHandle = opendir($path);
        if ($dirHandle) {
            while (($filename = readdir($dirHandle)) !== false) {
                if ($filename === '.' || $filename === '..') {
                    continue;
                }
                $count++;
            }

            closedir($dirHandle);
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $file1
     * @param array<string, mixed> $file2
     */
    public function sortFiles(
        array $file1,
        array $file2,
        string $sortField,
        string $sortDirection,
    ): int {
        if ($file1['type'] === self::TYPE_FOLDER && $file2['type'] !== self::TYPE_FOLDER) {
            return -1;
        }

        if ($file1['type'] !== self::TYPE_FOLDER && $file2['type'] === self::TYPE_FOLDER) {
            return 1;
        }

        if ($file1[$sortField] < $file2[$sortField]) {
            $res = -1;
        } elseif ($file1[$sortField] > $file2[$sortField]) {
            $res = 1;
        } else {
            $res = 0;
        }

        return $sortDirection === 'asc' ? $res : -$res;
    }
}
