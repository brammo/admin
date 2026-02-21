# File Manager

The Brammo Admin plugin includes a built-in File Manager for uploading, browsing, and managing files and images.

## Table of Contents

- [Configuration](#configuration)
- [Routes](#routes)
- [Actions](#actions)
- [Integration](#integration)
- [Image Processing](#image-processing)

---

## Configuration

Configure the File Manager in your application's configuration under `Admin.FileManager`:

```php
'Admin' => [
    'FileManager' => [
        'basePath' => WWW_ROOT,
        'topFolders' => ['images', 'files'],
        'fileTypes' => [
            'files' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        ],
        'fileIcons' => [
            'pdf' => 'file-earmark-pdf',
            'doc' => 'file-earmark-word',
            // ... more mappings
            'default' => 'file-earmark',
        ],
        'Images' => [
            'maxWidth' => 2048,
            'maxHeight' => 2048,
            'jpegQuality' => 90,
            'pngQuality' => 6,
            'webpQuality' => 90,
            'resizeOnUpload' => true,
        ],
    ],
]
```

### Configuration Options

| Option | Type | Required | Description |
|--------|------|----------|-------------|
| `basePath` | string | Yes | Base filesystem path where files are stored (typically `WWW_ROOT`) |
| `topFolders` | array | Yes | Array of top-level folder names accessible in the File Manager |
| `fileTypes.files` | array | Yes | Allowed file extensions for documents |
| `fileTypes.images` | array | Yes | Allowed file extensions for images |
| `fileIcons` | array | No | Map file extensions to Bootstrap Icons names |

### Image Processing Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `maxWidth` | int | `2048` | Maximum image width in pixels |
| `maxHeight` | int | `2048` | Maximum image height in pixels |
| `jpegQuality` | int | `90` | JPEG compression quality (1-100) |
| `pngQuality` | int | `6` | PNG compression level (0-9) |
| `webpQuality` | int | `90` | WebP compression quality (1-100) |
| `resizeOnUpload` | bool | `true` | Automatically resize images exceeding max dimensions on upload |

---

## Routes

The File Manager is available at the following routes:

| Route | Action | Description |
|-------|--------|-------------|
| `/admin/filemanager` | `index` | Main file browser interface |
| `/admin/filemanager/browseImages` | `browseImages` | Browse images only (used by image picker modals) |
| `/admin/filemanager/browseFiles` | `browseFiles` | Browse files only |
| `/admin/filemanager/upload` | `upload` | Handle file uploads |
| `/admin/filemanager/createFolder` | `createFolder` | Create new folders |
| `/admin/filemanager/delete` | `delete` | Delete files or empty folders |
| `/admin/filemanager/fixImage` | `fixImage` | Resize an existing image to fit max dimensions |

---

## Actions

### Index (Main Browser)

Browse all files and folders. Navigate through the folder structure starting from configured top folders.

**Query Parameters:**
- `folder` - Current folder path (e.g., `images/products`)
- `filter` - Filter files by name
- `page` - Pagination page number

### Browse Images

Browse only image files. Used internally by the [FormHelper image control](HELPERS.md#image-control) for AJAX-based image selection.

Supports AJAX requests with a simplified layout for modal display.

### Browse Files

Browse only document files (non-images). Similar to browseImages but filtered for document types.

### Upload

Upload files to a specified folder.

**Request:**
- Method: `POST`
- Query: `folder` - Target folder path
- Body: `file` (single file) or `files[]` (multiple files)

**Response (AJAX):**
```json
{
    "error": false,
    "message": "2 file(s) uploaded",
    "files": ["image1.jpg", "image2.jpg"]
}
```

**Error Response:**
```json
{
    "error": "Invalid file type png"
}
```

### Create Folder

Create a new subfolder within the current folder.

**Request:**
- Method: `POST`
- Query: `folder` - Parent folder path
- Body: `folder` - New folder name

### Delete

Delete a file or empty folder.

**Request:**
- Method: `POST`
- Query:
  - `folder` - Current folder path
  - `deleteFile` - Filename to delete, OR
  - `deleteFolder` - Folder name to delete (must be empty)

### Fix Image

Resize an existing image to fit within the configured max dimensions.

**Request:**
- Query:
  - `folder` - Folder path
  - `file` - Image filename

---

## Integration

### Using with FormHelper

The File Manager integrates with the FormHelper's `image` type for seamless image selection:

```php
echo $this->Form->control('image', [
    'type' => 'image',
    'folder' => 'images/products',
]);
```

See [FormHelper Image Control](HELPERS.md#image-control) for detailed documentation.

### Sidebar Menu Entry

Add the File Manager to your admin sidebar:

```php
'Sidebar' => [
    'menu' => [
        'FileManager' => [
            'title' => __('Files'),
            'icon' => 'folder',
            'url' => [
                'plugin' => 'Brammo/Admin',
                'controller' => 'FileManager',
                'action' => 'index'
            ],
        ],
    ],
]
```

### Direct Links

Link to specific folders or actions:

```php
// Link to images folder
echo $this->Html->link('Manage Images', [
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'index',
    '?' => ['folder' => 'images']
]);

// Link to files folder
echo $this->Html->link('Manage Documents', [
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'index',
    '?' => ['folder' => 'files']
]);
```

---

## Image Processing

The File Manager uses **ImageMagick** (via PHP's Imagick extension) for image processing.

### Automatic Resizing

When `resizeOnUpload` is enabled, uploaded images exceeding the configured max dimensions are automatically resized while maintaining aspect ratio.

### Manual Resize

Use the "Fix Image" action to resize existing images that exceed the configured dimensions.

### Supported Formats

Image processing supports:
- **JPEG** (.jpg, .jpeg) - Configurable quality compression
- **PNG** (.png) - Configurable compression level
- **WebP** (.webp) - Configurable quality compression
- **GIF** (.gif) - Basic support

### Requirements

Ensure the Imagick PHP extension is installed:

```bash
# Ubuntu/Debian
sudo apt-get install php-imagick

# macOS with Homebrew
brew install imagemagick
pecl install imagick
```

---

## Security

The File Manager includes several security measures:

- **Path Validation**: All paths are validated against configured `topFolders` to prevent directory traversal
- **File Type Validation**: Only configured file types in `fileTypes` are allowed for upload
- **Authentication**: Access requires authentication through the admin panel's authentication system
- **Unique Filenames**: Uploaded files with duplicate names are automatically renamed (e.g., `image(1).jpg`)
