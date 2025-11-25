<?php 
/**
 * Bootstrap plugin
 */

use Cake\Core\Configure;

/**
 * Load plugin configuration
*/
Configure::load('Brammo/Admin.admin');

/**
 * Load auth configuration
 */
Configure::load('Brammo/Admin.auth');
