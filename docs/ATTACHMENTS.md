# Attachments and multiupload

Bulk-upload files into a File Manager folder and create host ORM rows. The plugin provides:

- Sequential upload UI (`webroot/js/multiupload.js` + `Brammo/Admin.multiupload`)
- `AttachmentsComponent::upload()`
- Optional gallery/list elements (`Brammo/Admin.Attachments/images`, `Attachments/files`)
- Optional `AttachmentFileBehavior` to unlink unused files after delete

Domain tables, folder paths, and add/edit/delete actions stay in the host app. Reorder uses [SortableComponent](SORTABLE.md).

Load the component only on controllers that need attachments (not on `AppController` by default).

## Requirements

- `Admin.FileManager` configured (`basePath`, `topFolders`, `fileTypes`) — see [FILEMANAGER.md](FILEMANAGER.md)
- A child table (or `hasMany` association) with a path column (convention: `filename`)
- A saved owner entity (upload needs an owner id and folder)
- Optional: ADmad Sequence on the child table so new rows get `sort_order`

Typical child fields: `id`, owner FK, `filename`, `title`, `sort_order`.

## Controller

```php
public function initialize(): void
{
    parent::initialize();
    $this->loadComponent('Brammo/Admin.Attachments');
    $this->loadComponent('Brammo/Admin.Sortable');
}

public function uploadFiles(int $articleId): \Cake\Http\Response
{
    $article = $this->NewsArticles->get($articleId);

    return $this->Attachments->upload(
        $this->NewsArticles->Files,
        $article->fileFolder(), // e.g. files/2026 — must pass isValidFolder()
        ['news_article_id' => $article->id],
    );
}

public function sortFiles(int $articleId): \Cake\Http\Response
{
    $this->NewsArticles->get($articleId);

    return $this->Sortable->reorder(
        $this->NewsArticles->Files,
        $this->request->getData('ids'),
        ['news_article_id' => $articleId],
    );
}
```

`upload()`:

- Requires POST
- Validates `$folder` with `FileManagerService::isValidFolder()` (creates the directory if needed)
- Accepts `files[]`, `files`, or `file`
- Writes via `processUploadedFile()` (unique name, optional Imagick resize)
- Inserts a row with `$defaults` plus `filename` as `/{folder}/{name}`
- Returns JSON `{ error, files: [{ name, filename?, error }], saved }`

Override the path column:

```php
$this->loadComponent('Brammo/Admin.Attachments', [
    'filenameField' => 'path',
]);
```

Single-file add/edit/delete remain normal host actions (Form `image` control or File Manager browse).

## Templates

On the owner **view** page (after the entity exists):

```php
echo $this->element('Brammo/Admin.Attachments/images', [
    'items' => $article->images,
    'uploadUrl' => ['action' => 'uploadImages', $article->id],
    'sortUrl' => ['action' => 'sortImages', $article->id],
    'addUrl' => ['action' => 'addImage', $article->id],
    'editAction' => 'editImage',
    'deleteAction' => 'deleteImage',
]);

echo $this->element('Brammo/Admin.Attachments/files', [
    'items' => $article->files,
    'uploadUrl' => ['action' => 'uploadFiles', $article->id],
    'sortUrl' => ['action' => 'sortFiles', $article->id],
    'addUrl' => ['action' => 'addFile', $article->id],
    'editAction' => 'editFile',
    'deleteAction' => 'deleteFile',
]);
```

Optional keys: `title`, `addTitle`, `uploadLabel`, `accept`, `id` (must be unique when both widgets are on one page). Images also accept `lightbox` (default `true`, uses `Brammo/Content.lightgallery`) and `thumbSize` (default `300`).

The multiupload widget can be used alone:

```php
echo $this->element('Brammo/Admin.multiupload', [
    'id' => 'images',
    'url' => ['action' => 'uploadImages', $article->id],
    'label' => __d('brammo/admin', 'Upload images'),
    'accept' => 'image/*',
]);
```

It POSTs one file at a time as `files[]` with CSRF and shows a Bootstrap progress modal. The page reloads after any successful save.

## Disk cleanup

Attach the behavior on child tables so deleting a row unlinks the file when nothing else stores the same path:

```php
$this->addBehavior('Brammo/Admin.AttachmentFile', [
    'field' => 'filename',
    'references' => [
        'NewsImages' => 'filename',
        'NewsArticles' => 'image',
        'Banners' => 'image',
    ],
]);
```

The same table is always checked (other rows with the same path). `references` lists additional aliases. Unlink uses `realpath` + `isPathWithinBase` and is skipped when File Manager is not configured, the folder is invalid, or the path is outside `basePath`.

## Out of scope

- Polymorphic attachments table
- Folder path helpers (`imageFolder()` / `fileFolder()` stay in the host)
- FormHelper gallery control (attachments need a persisted owner)
