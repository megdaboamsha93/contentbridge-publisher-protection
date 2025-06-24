<?php
/**
 * PHPUnit bootstrap file
 */

$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
    exit(1);
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

// Define plugin constants if not already defined
if (!defined('CONTENTBRIDGE_VERSION')) {
    define('CONTENTBRIDGE_VERSION', '1.0.0');
}
if (!defined('CONTENTBRIDGE_PLUGIN_DIR')) {
    define('CONTENTBRIDGE_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/');
}
if (!defined('CONTENTBRIDGE_PLUGIN_URL')) {
    define('CONTENTBRIDGE_PLUGIN_URL', 'http://example.org/wp-content/plugins/contentbridge/');
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require dirname(dirname(__FILE__)) . '/contentbridge.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Create tables after WordPress is loaded
global $wpdb;
require_once CONTENTBRIDGE_PLUGIN_DIR . 'includes/class-installer.php';
$installer = new ContentBridge\Installer();
$installer->install();

// Load test helpers
require_once dirname(__FILE__) . '/TestCase.php'; 