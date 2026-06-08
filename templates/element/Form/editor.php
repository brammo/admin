<?php
/**
 * Editor element
 * Lightweight HTML editor for rich text fields
 *
 * @var \Brammo\Admin\View\AppView $this
 * @var int $height
 */

use Cake\Core\Configure;
use Cake\Routing\Router;

$settings = Configure::read('Admin.Editor', []);

$imagesUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'browseImages',
]);

$filesUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'browseFiles',
]);

$height = $height ?? $settings['height'] ?? 500;

$labels = [
    'blockFormat' => __d('brammo/admin', 'Block format'),
    'paragraph' => __d('brammo/admin', 'Paragraph'),
    'heading1' => __d('brammo/admin', 'Heading 1'),
    'heading2' => __d('brammo/admin', 'Heading 2'),
    'heading3' => __d('brammo/admin', 'Heading 3'),
    'heading4' => __d('brammo/admin', 'Heading 4'),
    'heading5' => __d('brammo/admin', 'Heading 5'),
    'heading6' => __d('brammo/admin', 'Heading 6'),
    'div' => __d('brammo/admin', 'Div'),
    'blockquote' => __d('brammo/admin', 'Blockquote'),
    'pre' => __d('brammo/admin', 'Preformatted'),
    'bold' => __d('brammo/admin', 'Bold'),
    'italic' => __d('brammo/admin', 'Italic'),
    'underline' => __d('brammo/admin', 'Underline'),
    'strikethrough' => __d('brammo/admin', 'Strikethrough'),
    'subscript' => __d('brammo/admin', 'Subscript'),
    'superscript' => __d('brammo/admin', 'Superscript'),
    'code' => __d('brammo/admin', 'Code'),
    'alignLeft' => __d('brammo/admin', 'Align left'),
    'alignCenter' => __d('brammo/admin', 'Align center'),
    'alignRight' => __d('brammo/admin', 'Align right'),
    'alignJustify' => __d('brammo/admin', 'Justify'),
    'unorderedList' => __d('brammo/admin', 'Bulleted list'),
    'orderedList' => __d('brammo/admin', 'Numbered list'),
    'link' => __d('brammo/admin', 'Insert link'),
    'linkDialogTitle' => __d('brammo/admin', 'Insert link'),
    'linkEditTitle' => __d('brammo/admin', 'Edit link'),
    'linkUrl' => __d('brammo/admin', 'Link URL'),
    'linkText' => __d('brammo/admin', 'Link text'),
    'linkTitle' => __d('brammo/admin', 'Title'),
    'linkTarget' => __d('brammo/admin', 'Target'),
    'linkTargetDefault' => __d('brammo/admin', 'Same window'),
    'linkTargetBlank' => __d('brammo/admin', 'New window'),
    'linkTargetSelf' => __d('brammo/admin', 'Same frame (_self)'),
    'linkTargetParent' => __d('brammo/admin', 'Parent frame (_parent)'),
    'linkTargetTop' => __d('brammo/admin', 'Top frame (_top)'),
    'linkSelect' => __d('brammo/admin', 'Select'),
    'linkBrowseTitle' => __d('brammo/admin', 'Select file'),
    'linkBack' => __d('brammo/admin', 'Back'),
    'linkInsert' => __d('brammo/admin', 'Insert'),
    'linkSave' => __d('brammo/admin', 'Save'),
    'imageBrowse' => __d('brammo/admin', 'Insert image'),
    'imageDialogTitle' => __d('brammo/admin', 'Insert image'),
    'imageSrc' => __d('brammo/admin', 'Image URL'),
    'imageAlt' => __d('brammo/admin', 'Alt text'),
    'imageWidth' => __d('brammo/admin', 'Width'),
    'imageHeight' => __d('brammo/admin', 'Height'),
    'imageStyles' => __d('brammo/admin', 'Styles'),
    'imageSelect' => __d('brammo/admin', 'Select'),
    'imageBrowseTitle' => __d('brammo/admin', 'Select Image'),
    'imageBack' => __d('brammo/admin', 'Back'),
    'imageInsert' => __d('brammo/admin', 'Insert'),
    'imageEditTitle' => __d('brammo/admin', 'Edit image'),
    'imageSave' => __d('brammo/admin', 'Save'),
    'cancel' => __d('brammo/admin', 'Cancel'),
    'source' => __d('brammo/admin', 'Edit HTML'),
    'undo' => __d('brammo/admin', 'Undo'),
    'redo' => __d('brammo/admin', 'Redo'),
];

$this->Html->css('Brammo/Admin.editor', ['block' => true]);
$this->Html->script('Brammo/Admin.file-browser', ['block' => true]);
$this->Html->script('Brammo/Admin.editor', ['block' => true]);

$this->append('script');
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const browseUrl = <?= json_encode($imagesUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const filesBrowseUrl = <?= json_encode($filesUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const height = <?= (int)$height ?>;
        const labels = <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const modalTitle = <?= json_encode(__d('brammo/admin', 'Select Image'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        window.BrammoEditor = window.BrammoEditor || { instances: {} };

        const fileBrowser = new FileBrowser('#editor-file-browser-modal', modalTitle);

        document.querySelectorAll('textarea.editor').forEach(function(textarea) {
            new HtmlEditor(textarea, {
                browseUrl: browseUrl,
                filesBrowseUrl: filesBrowseUrl,
                height: height,
                labels: labels,
                fileBrowser: fileBrowser,
                folder: 'images',
                linkFolder: 'files',
            });
        });
    });
</script>
<?php $this->end() ?>
