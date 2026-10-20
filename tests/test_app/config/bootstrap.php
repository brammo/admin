<?php
declare(strict_types=1);

/**
 * Minimal bootstrap for integration tests.
 */

use Cake\Core\Configure;
use Cake\Utility\Security;

if (!Configure::check('Error')) {
    Configure::write('Error', [
        'errorLevel' => E_ALL,
        'skipLog' => [],
        'log' => false,
        'trace' => true,
    ]);
}

Security::setSalt('brammo-admin-integration-test-salt-value');
