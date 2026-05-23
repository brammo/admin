<?php
/**
 * Editor element
 * Uses TinyMCE for rich text editing
 * 
 * @var \Brammo\Admin\View\AppView $this
 * @var int $height
 */

use Cake\Core\Configure;
use Cake\Routing\Asset;
use Cake\Routing\Router;

$settings = Configure::read('Admin.Editor');

$apiKey = $settings['apiKey'] ?? '';
if (empty($apiKey)) {
    throw new RuntimeException('TinyMCE API key is not configured. Please set Admin.Editor.apiKey in your configuration.');
}

$imagesUrl = Router::url([
    'plugin' => 'Brammo/Admin',
    'controller' => 'FileManager',
    'action' => 'images',
]);

$height = $height ?? $settings['height'] ?? 500;

$this->Html->script("https://cdn.tiny.cloud/1/$apiKey/tinymce/8/tinymce.min.js", ['block' => true]);

$this->append('script');
?>
<script>
    tinymce.init({
        selector: 'textarea.editor',
        height: <?= $height ?>,
        plugins: 'autolink link image lists fullscreen table code',
        toolbar: 'styles | bold italic removeformat | alignleft aligncenter alignright alignjustify | bullist numlist | link image table | code fullscreen',
        menubar: false,
        branding: false,
        relative_urls: false,
        paste_as_text: true,
        remove_script_host: true,
        convert_urls: true,
        file_picker_types: 'image',
        content_css: '<?= Asset::cssUrl('Brammo/Admin.editor.css') ?>',
        extended_valid_elements: 'i[*]',
        file_picker_callback: function(callback, value, meta) {
            const instanceApi = tinyMCE.activeEditor.windowManager.openUrl({
                url: '<?= $imagesUrl ?>',
                title: '<?= __d('brammo/admin', 'Select Image') ?>',
                onMessage: function(dialogApi, details) {
                    callback(details.data.img);
                    instanceApi.close();
                }
            });
        }
    });
</script>
<?php $this->end() ?>