<?php
/**
 * Editor element
 * Uses tinymce for rich text editing
 * 
 * @var \App\View\AppView $this
 */

use Cake\Core\Configure;
use Cake\Routing\Asset;

$apiKey = Configure::read('TinyMCE.apiKey');

$this->Html->script("https://cdn.tiny.cloud/1/$apiKey/tinymce/7/tinymce.min.js", ['block' => true]);

$this->Html->scriptStart(['block' => true]);
?>
    tinymce.init({
        selector: 'textarea.editor',
        plugins: 'lists link code',
        toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image code',
        menubar: false,
        branding: false,
        height: 300,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
        content_css: '<?= Asset::cssUrl('Admin.editor.css') ?>',
        extended_valid_elements: 'i[*]',
    });
<?php
$this->Html->scriptEnd();
