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
     * - `html`: Renders a WYSIWYG HTML editor using the Form/editor element
     * - `dateRange`: Renders an input group with configurable `{name}_{suffix}` date fields
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
            return $this->image($fieldName, $options);
        }

        if ($options['type'] === 'html') {
            return $this->html($fieldName, $options);
        }

        if ($options['type'] === 'dateRange') {
            unset($options['type']);
            $input = $this->dateRange($fieldName, $options);

            return parent::control($fieldName, [
                'type' => 'date',
                'input' => $input,
            ] + $options);
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
    public function image(string $fieldName, array $options = []): string
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
     * Generate an HTML editor control element.
     *
     * Options:
     * - `label`: The label for the control
     *
     * @param string $fieldName The field name.
     * @param array<string, mixed> $options The options.
     * @return string Generated HTML.
     */
    public function html(string $fieldName, array $options = []): string
    {
        // Include the editor element (HTML editor initialization)
        if (empty($this->_View->get('_editorLoaded'))) {
            $this->_View->element('Brammo/Admin.Form/editor');
            $this->_View->set('_editorLoaded', true);
        }

        // Remove custom type to prevent infinite loop
        unset($options['type']);

        // Add editor class to enable HTML editor initialization
        $options['class'] = isset($options['class'])
            ? $options['class'] . ' editor'
            : 'editor';

        // Set textarea type
        $options['type'] = 'textarea';

        return parent::control($fieldName, $options);
    }

    /**
     * Generate a date range input group with `_from` and `_to` date fields.
     *
     * Returns only the `input-group` markup. Use `control()` with `type => dateRange`
     * for label and container wrapping.
     *
     * Options:
     * - `suffixes`: Field name suffixes for the range ends. Default `['from', 'to']` (fields
     *   `{name}_from`, `{name}_to`). Pass a list e.g. `['start', 'end']` or associative
     *   `['from' => 'start', 'to' => 'end']`.
     * - `value`: Range values as `[$from, $to]` or `['from' => $from, 'to' => $to]`
     * - `valueFrom`, `valueTo`: Values for the first and second date inputs
     * - `from`: Extra options passed to the first date input
     * - `to`: Extra options passed to the second date input
     *
     * @param string $fieldName The field name (without range suffix).
     * @param array<string, mixed> $options The options.
     * @return string Generated HTML.
     */
    public function dateRange(string $fieldName, array $options = []): string
    {
        unset($options['type'], $options['label'], $options['error']);

        [$fromSuffix, $toSuffix] = $this->parseDateRangeSuffixes($options);
        [$fromValue, $toValue] = $this->parseDateRangeValues($options);

        $fromField = $fieldName . '_' . $fromSuffix;
        $toField = $fieldName . '_' . $toSuffix;

        $fromOptions = is_array($options['from'] ?? null) ? $options['from'] : [];
        $toOptions = is_array($options['to'] ?? null) ? $options['to'] : [];
        unset($options['from'], $options['to']);

        if ($fromValue !== null) {
            $fromOptions += ['value' => $fromValue];
        }
        if ($toValue !== null) {
            $toOptions += ['value' => $toValue];
        }

        $fromInput = $this->date($fromField, array_merge($options, $fromOptions));
        $toInput = $this->date($toField, array_merge($options, $toOptions));

        return $this->Html->tag('div', $fromInput . $toInput, ['class' => 'input-group']);
    }

    /**
     * Resolve date range field suffixes from options.
     *
     * @param array<string, mixed> $options Control options; `suffixes` is removed when set.
     * @return array{0: string, 1: string} First and second field suffix (without leading underscore).
     */
    protected function parseDateRangeSuffixes(array &$options): array
    {
        $suffixes = $options['suffixes'] ?? null;
        unset($options['suffixes']);

        if ($suffixes === null) {
            return ['from', 'to'];
        }

        if (is_array($suffixes) && array_is_list($suffixes)) {
            return [
                is_string($suffixes[0] ?? null) ? $suffixes[0] : 'from',
                is_string($suffixes[1] ?? null) ? $suffixes[1] : 'to',
            ];
        }

        if (is_array($suffixes)) {
            return [
                is_string($suffixes['from'] ?? null) ? $suffixes['from'] : 'from',
                is_string($suffixes['to'] ?? null) ? $suffixes['to'] : 'to',
            ];
        }

        return ['from', 'to'];
    }

    /**
     * Resolve date range values from options.
     *
     * @param array<string, mixed> $options Control options; value keys are removed when set.
     * @return array{0: mixed, 1: mixed} First and second date values, or null when not set.
     */
    protected function parseDateRangeValues(array &$options): array
    {
        $fromValue = null;
        $toValue = null;

        $value = $options['value'] ?? null;
        if ($value !== null) {
            unset($options['value']);
            if (is_array($value)) {
                if (array_is_list($value)) {
                    if (array_key_exists(0, $value)) {
                        $fromValue = $value[0];
                    }
                    if (array_key_exists(1, $value)) {
                        $toValue = $value[1];
                    }
                } else {
                    if (array_key_exists('from', $value)) {
                        $fromValue = $value['from'];
                    }
                    if (array_key_exists('to', $value)) {
                        $toValue = $value['to'];
                    }
                }
            }
        }

        if (array_key_exists('valueFrom', $options)) {
            $fromValue = $options['valueFrom'];
            unset($options['valueFrom']);
        }
        if (array_key_exists('valueTo', $options)) {
            $toValue = $options['valueTo'];
            unset($options['valueTo']);
        }

        return [$fromValue, $toValue];
    }
}
