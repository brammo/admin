<?php
declare(strict_types=1);

namespace Brammo\Admin\Test\TestCase\View\Element\Form;

use Brammo\Admin\View\AppView;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Form translations element test case
 */
class TranslationsElementTest extends TestCase
{
    /**
     * View instance
     *
     * @var \Brammo\Admin\View\AppView
     */
    protected AppView $View;

    /**
     * Locale config snapshot for tearDown
     *
     * @var array<string, mixed>|null
     */
    protected ?array $originalAppConfig = null;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->View = new AppView();
        $this->originalAppConfig = Configure::read('App');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->originalAppConfig !== null) {
            Configure::write('App', $this->originalAppConfig);
        }
        Configure::delete('I18n.locales');

        unset($this->View, $this->originalAppConfig);
        parent::tearDown();
    }

    /**
     * Render the translations element inside a form context.
     *
     * @param array<string, array<string, mixed>> $controls Control definitions.
     * @return string
     */
    protected function renderElement(array $controls): string
    {
        $this->View->Form->create(null);

        return $this->View->element('Brammo/Admin.Form/translations', [
            'controls' => $controls,
        ]);
    }

    /**
     * Test rendering tabs for each configured locale
     *
     * @return void
     */
    public function testRendersTabsForEachLocale(): void
    {
        Configure::write('App', array_merge(Configure::read('App'), [
            'defaultLocale' => 'bg',
            'locales' => [
                'bg' => 'Bulgarian',
                'en' => 'English',
            ],
        ]));

        $result = $this->renderElement([
            'title' => ['label' => 'Title', 'type' => 'text'],
        ]);

        $this->assertStringContainsString('class="nav nav-tabs"', $result);
        $this->assertStringContainsString('id="bg-tab"', $result);
        $this->assertStringContainsString('id="en-tab"', $result);
        $this->assertStringContainsString('Bulgarian', $result);
        $this->assertStringContainsString('English', $result);
        $this->assertStringContainsString('id="bg"', $result);
        $this->assertStringContainsString('id="en"', $result);
    }

    /**
     * Test default locale fields keep original names
     *
     * @return void
     */
    public function testDefaultLocaleKeepsFieldNames(): void
    {
        Configure::write('App', array_merge(Configure::read('App'), [
            'defaultLocale' => 'bg',
            'locales' => [
                'bg' => 'Bulgarian',
                'en' => 'English',
            ],
        ]));

        $result = $this->renderElement([
            'title' => ['label' => 'Title', 'type' => 'text'],
            'slug' => ['label' => 'Slug', 'type' => 'text'],
        ]);

        $this->assertStringContainsString('name="title"', $result);
        $this->assertStringContainsString('name="slug"', $result);
        $this->assertStringContainsString('name="_translations[en][title]"', $result);
        $this->assertStringContainsString('name="_translations[en][slug]"', $result);
        $this->assertStringNotContainsString('name="_translations[bg][title]"', $result);
    }

    /**
     * Test English locale tab uses the GB flag icon
     *
     * @return void
     */
    public function testEnglishLocaleUsesGbFlag(): void
    {
        Configure::write('App', array_merge(Configure::read('App'), [
            'defaultLocale' => 'bg',
            'locales' => [
                'bg' => 'Bulgarian',
                'en' => 'English',
            ],
        ]));

        $result = $this->renderElement([
            'title' => ['label' => 'Title', 'type' => 'text'],
        ]);

        $this->assertStringContainsString('flag-icon-gb', $result);
        $this->assertStringContainsString('flag-icon-bg', $result);
    }

    /**
     * Test locales fall back to I18n.locales when App.locales is empty
     *
     * @return void
     */
    public function testFallsBackToI18nLocales(): void
    {
        Configure::write('App', array_merge(Configure::read('App'), [
            'defaultLocale' => 'en',
            'locales' => null,
        ]));
        Configure::write('I18n.locales', [
            'en' => 'English',
            'de' => 'German',
        ]);

        $result = $this->renderElement([
            'title' => ['label' => 'Title', 'type' => 'text'],
        ]);

        $this->assertStringContainsString('id="en-tab"', $result);
        $this->assertStringContainsString('id="de-tab"', $result);
        $this->assertStringContainsString('German', $result);
        $this->assertStringContainsString('name="_translations[de][title]"', $result);
    }

    /**
     * Test locales fall back to App.defaultLocale when no locale list is configured
     *
     * @return void
     */
    public function testFallsBackToDefaultLocaleOnly(): void
    {
        Configure::write('App', [
            'namespace' => 'Brammo\Admin',
            'encoding' => 'UTF-8',
            'defaultLocale' => 'bg',
            'defaultTimezone' => 'UTC',
            'paths' => Configure::read('App.paths'),
        ]);
        Configure::delete('I18n.locales');

        $result = $this->renderElement([
            'title' => ['label' => 'Title', 'type' => 'text'],
        ]);

        $this->assertStringContainsString('class="nav nav-tabs"', $result);
        $this->assertStringContainsString('name="title"', $result);
    }

    /**
     * Test default locale falls back to en when App.defaultLocale is empty
     *
     * @return void
     */
    public function testDefaultLocaleFallsBackToEn(): void
    {
        Configure::write('App', array_merge(Configure::read('App'), [
            'defaultLocale' => null,
            'locales' => [
                'en' => 'English',
                'bg' => 'Bulgarian',
            ],
        ]));

        $result = $this->renderElement([
            'title' => ['label' => 'Title', 'type' => 'text'],
        ]);

        $this->assertStringContainsString('name="title"', $result);
        $this->assertStringContainsString('name="_translations[bg][title]"', $result);
        $this->assertStringNotContainsString('name="_translations[en][title]"', $result);
    }

    /**
     * Test multiple controls are rendered in each locale tab
     *
     * @return void
     */
    public function testRendersMultipleControlsPerLocale(): void
    {
        Configure::write('App', array_merge(Configure::read('App'), [
            'defaultLocale' => 'bg',
            'locales' => [
                'bg' => 'Bulgarian',
                'en' => 'English',
            ],
        ]));

        $result = $this->renderElement([
            'title' => ['label' => 'Title', 'type' => 'text'],
            'slug' => ['label' => 'Slug', 'type' => 'text'],
        ]);

        $this->assertEquals(1, substr_count($result, 'name="title"'));
        $this->assertEquals(1, substr_count($result, 'name="slug"'));
        $this->assertEquals(1, substr_count($result, 'name="_translations[en][title]"'));
        $this->assertEquals(1, substr_count($result, 'name="_translations[en][slug]"'));
    }
}
