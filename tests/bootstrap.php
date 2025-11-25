<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for Brammo\Admin.
 */

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\Cache\Cache;

// Load the autoloader
require dirname(__DIR__) . '/vendor/autoload.php';

// Load CakePHP core functions if not already loaded
if (!function_exists('h')) {
    require dirname(__DIR__) . '/vendor/cakephp/cakephp/src/Core/functions.php';
}

// Path constants to a few helpful things.
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', dirname(__DIR__) . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('TESTS', ROOT . 'tests');
define('APP', ROOT . 'tests' . DS . 'test_app' . DS);
define('APP_DIR', 'test_app');
define('WEBROOT_DIR', 'webroot');
define('WWW_ROOT', dirname(__DIR__) . DS . 'webroot' . DS);
define('TMP', sys_get_temp_dir() . DS);
define('CONFIG', dirname(__DIR__) . DS . 'config' . DS);
define('CACHE', TMP);
define('LOGS', TMP);

// Configure the timezone
date_default_timezone_set('UTC');

// Minimal application config
Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'Brammo\Admin',
    'encoding' => 'UTF-8',
    'defaultLocale' => 'en_US',
    'defaultTimezone' => 'UTC',
    'paths' => [
        'plugins' => [ROOT . 'plugins' . DS],
        'templates' => [ROOT . 'templates' . DS],
        'locales' => [ROOT . 'resources' . DS . 'locales' . DS],
    ],
]);

// Configure the cache
Cache::setConfig('_cake_translations_', [
    'className' => 'File',
    'prefix' => 'brammo_admin_cake_translations_',
    'path' => CACHE . 'persistent/',
    'serialize' => true,
    'duration' => '+1 years',
]);

Cache::setConfig('_cake_model_', [
    'className' => 'File',
    'prefix' => 'brammo_admin_cake_model_',
    'path' => CACHE . 'models/',
    'serialize' => true,
    'duration' => '+1 years',
]);

Cache::setConfig('default', [
    'className' => 'File',
    'path' => CACHE,
]);

// Configure test database
ConnectionManager::setConfig('test', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\Sqlite',
    'database' => ':memory:',
    'encoding' => 'utf8',
    'cacheMetadata' => true,
    'quoteIdentifiers' => false,
]);

// Load more plugins here if needed
if (!Plugin::isLoaded('Brammo/Admin')) {
    Plugin::getCollection()->add(new \Brammo\Admin\Plugin());
}
