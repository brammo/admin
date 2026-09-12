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
    'clearFormat' => __d('brammo/admin', 'Clear formatting'),
    'clearFormatConfirm' => __d('brammo/admin', 'Clear formatting from the entire document?'),
    'source' => __d('brammo/admin', 'Edit HTML'),
    'formatSource' => __d('brammo/admin', 'Format HTML'),
    'undo' => __d('brammo/admin', 'Undo'),
    'redo' => __d('brammo/admin', 'Redo'),
    'elementPath' => __d('brammo/admin', 'Element path'),
    'table' => __d('brammo/admin', 'Table'),
    'tableDialogTitle' => __d('brammo/admin', 'Insert table'),
    'tableEditTitle' => __d('brammo/admin', 'Edit table'),
    'tableInsert' => __d('brammo/admin', 'Insert'),
    'tableSave' => __d('brammo/admin', 'Save'),
    'tableProperties' => __d('brammo/admin', 'Table properties'),
    'tableRows' => __d('brammo/admin', 'Rows'),
    'tableColumns' => __d('brammo/admin', 'Columns'),
    'tableHeaderRow' => __d('brammo/admin', 'Header row'),
    'tableHeaderColumn' => __d('brammo/admin', 'Header column'),
    'tableCaption' => __d('brammo/admin', 'Caption'),
    'tableWidth' => __d('brammo/admin', 'Width'),
    'tableAlign' => __d('brammo/admin', 'Alignment'),
    'tableAlignDefault' => __d('brammo/admin', 'Default'),
    'tableClass' => __d('brammo/admin', 'CSS class'),
    'tableStyles' => __d('brammo/admin', 'Styles'),
    'insertRowAbove' => __d('brammo/admin', 'Insert row above'),
    'insertRowBelow' => __d('brammo/admin', 'Insert row below'),
    'insertColumnLeft' => __d('brammo/admin', 'Insert column left'),
    'insertColumnRight' => __d('brammo/admin', 'Insert column right'),
    'deleteRow' => __d('brammo/admin', 'Delete row'),
    'deleteColumn' => __d('brammo/admin', 'Delete column'),
    'deleteTable' => __d('brammo/admin', 'Delete table'),
    'mergeCells' => __d('brammo/admin', 'Merge cells'),
    'splitCell' => __d('brammo/admin', 'Split cell'),
    'cellProperties' => __d('brammo/admin', 'Cell properties'),
    'cellWidth' => __d('brammo/admin', 'Width'),
    'cellHeight' => __d('brammo/admin', 'Height'),
    'cellAlign' => __d('brammo/admin', 'Text align'),
    'cellValign' => __d('brammo/admin', 'Vertical align'),
    'cellValignTop' => __d('brammo/admin', 'Top'),
    'cellValignMiddle' => __d('brammo/admin', 'Middle'),
    'cellValignBottom' => __d('brammo/admin', 'Bottom'),
    'cellBackground' => __d('brammo/admin', 'Background'),
    'cellType' => __d('brammo/admin', 'Cell type'),
    'cellTypeData' => __d('brammo/admin', 'Data cell'),
    'cellTypeHeader' => __d('brammo/admin', 'Header cell'),
    'cellSave' => __d('brammo/admin', 'Save'),
];

$cleanOnPaste = $settings['cleanOnPaste'] ?? true;
$statusBar = $settings['statusBar'] ?? true;
$tableClass = $settings['tableClass'] ?? '';

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
        const cleanOnPaste = <?= $cleanOnPaste ? 'true' : 'false' ?>;
        const statusBar = <?= $statusBar ? 'true' : 'false' ?>;
        const tableClass = <?= json_encode($tableClass, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const labels = <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const modalTitle = <?= json_encode(__d('brammo/admin', 'Select Image'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        window.BrammoEditor = window.BrammoEditor || { instances: {} };

        const fileBrowser = new FileBrowser('#editor-file-browser-modal', modalTitle);

        document.querySelectorAll('textarea.editor').forEach(function(textarea) {
            new HtmlEditor(textarea, {
                browseUrl: browseUrl,
                filesBrowseUrl: filesBrowseUrl,
                height: height,
                cleanOnPaste: cleanOnPaste,
                statusBar: statusBar,
                tableClass: tableClass,
                labels: labels,
                fileBrowser: fileBrowser,
                folder: 'images',
                linkFolder: 'files',
            });
        });
    });
</script>
<?php $this->end() ?>
