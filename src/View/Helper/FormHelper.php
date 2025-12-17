<?php
declare(strict_types=1);

namespace Brammo\Admin\View\Helper;

use BootstrapUI\View\Helper\FormHelper as BootstrapFormHelper;
use Cake\View\Form\ContextInterface;
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

        // Get the entity from the form context
        $context = $this->context();
        $entity = $this->getEntityFromContext($context);

        // Get label from field name if not provided
        if ($options['label'] === null) {
            $options['label'] = __(ucfirst(str_replace('_', ' ', $fieldName)));
        }

        return $this->_View->element('Brammo/Admin.Form/image', [
            'entity' => $entity,
            'field' => $fieldName,
            'folder' => $options['folder'],
            'label' => $options['label'],
            'allowEmpty' => $options['allowEmpty'],
        ]);
    }

    /**
     * Get entity from form context if available.
     *
     * @param \Cake\View\Form\ContextInterface|null $context The form context.
     * @return mixed The entity or null.
     */
    protected function getEntityFromContext(?ContextInterface $context): mixed
    {
        if ($context === null) {
            return null;
        }

        if (method_exists($context, 'entity')) {
            return $context->entity();
        }

        return null;
    }
}
