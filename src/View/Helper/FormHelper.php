<?php
declare(strict_types=1);

namespace Brammo\Admin\View\Helper;

use BootstrapUI\View\Helper\FormHelper as BootstrapFormHelper;
use function Cake\I18n\__;

/**
 * Form Helper
 *
 * Extends BootstrapUI Form helper with additional control types.
 */
class FormHelper extends BootstrapFormHelper
{
    /**
     * Generate a form control element with support for custom types.
     *
     * Adds support for the following custom types:
     * - `image`: Renders an image picker/uploader using the Form/image element
     * - `html`: Renders a TinyMCE WYSIWYG editor using the Form/editor element
     *
     * @param string $fieldName The field name.
     * @param array<array-key, mixed> $options The options.
     * @return string Generated HTML.
     */
    public function control(string $fieldName, array $options = []): string
    {
        $options += [
            'type' => null,
        ];

        if ($options['type'] === 'image') {
            return $this->imageControl($fieldName, $options);
        }

        if ($options['type'] === 'html') {
            return $this->htmlControl($fieldName, $options);
        }

        return parent::control($fieldName, $options);
    }

    /**
     * Generate an image control element.
     *
     * Options:
     * - `folder`: The folder path for image selection (default: 'images')
     * - `label`: The label for the control
     * - `allowEmpty`: Whether empty values are allowed (default: true)
     *
     * @param string $fieldName The field name.
     * @param array<string, mixed> $options The options.
     * @return string Generated HTML.
     */
    public function imageControl(string $fieldName, array $options = []): string
    {
        $defaults = [
            'folder' => 'images',
            'label' => null,
            'allowEmpty' => true,
        ];

        $options += $defaults;

        // Get current value for the field
        $value = $this->context()->val($fieldName);

        // Get label from field name if not provided
        if ($options['label'] === null) {
            $options['label'] = __(ucfirst(str_replace('_', ' ', $fieldName)));
        }

        return $this->_View->element('Brammo/Admin.Form/image', [
            'field' => $fieldName,
            'folder' => $options['folder'],
            'label' => $options['label'],
            'allowEmpty' => $options['allowEmpty'],
            'value' => $value,
        ]);
    }

    /**
     * Generate an HTML editor control element using TinyMCE.
     *
     * Options:
     * - `label`: The label for the control
     *
     * @param string $fieldName The field name.
     * @param array<string, mixed> $options The options.
     * @return string Generated HTML.
     */
    public function htmlControl(string $fieldName, array $options = []): string
    {
        // Include the editor element (TinyMCE initialization)
        if (empty($this->_View->get('_editorLoaded'))) {
            $this->_View->element('Brammo/Admin.Form/editor');
            $this->_View->set('_editorLoaded', true);
        }

        // Remove custom type to prevent infinite loop
        unset($options['type']);

        // Add editor class to enable TinyMCE initialization
        $options['class'] = isset($options['class'])
            ? $options['class'] . ' editor'
            : 'editor';

        // Set textarea type
        $options['type'] = 'textarea';

        return parent::control($fieldName, $options);
    }
}
