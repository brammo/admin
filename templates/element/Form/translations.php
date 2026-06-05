<?php
/**
 * Form translations element
 *
 * Renders the same set of form controls once per configured locale, grouped in
 * Bootstrap tabs (via NavHelper). Fields for the default locale keep their
 * original names; other locales are prefixed as `_translations.{locale}.{field}`
 * for CakePHP Translate behavior.
 *
 * Locales are read from `App.locales`, then `I18n.locales`, then
 * `[App.defaultLocale]`. The default locale comes from `App.defaultLocale`
 * (falls back to `en`).
 *
 * Usage (inside an active `Form->create()` context):
 *
 * ```php
 * echo $this->element('Brammo/Admin.Form/translations', [
 *     'controls' => [
 *         'title' => ['label' => 'Title'],
 *         'body' => ['type' => 'html', 'label' => 'Content'],
 *     ],
 * ]);
 * ```
 *
 * @var \Brammo\Admin\View\AppView $this
 * @var array<string, array<string, mixed>> $controls Field names mapped to `Form->control()` options
 * @see docs/HELPERS.md#translations-element
 */

use Cake\Core\Configure;

$defaultLocale = Configure::read('App.defaultLocale');
if (empty($defaultLocale)) {
    $defaultLocale = 'en';
}

$locales = Configure::read('App.locales');
if (empty($locales)) {
    $locales = Configure::read('I18n.locales');
}

if (empty($locales)) {
    $locales = [$defaultLocale => $defaultLocale];
}

foreach ($locales as $locale => $localeName) {
    $this->start('localeContent');
        foreach ($controls as $name => $options) {

            if ($locale != $defaultLocale) {
                $name = '_translations.' . $locale . '.' . $name;
            }

            echo $this->Form->control($name, $options);
        }
    $this->end();

    $flag = $this->Flag->icon($locale == 'en' ? 'gb' : $locale);
    $this->Nav->add(
        $locale,
        $flag . ' ' . $localeName,
        $this->fetch('localeContent'),
    );
}
echo $this->Nav->render();