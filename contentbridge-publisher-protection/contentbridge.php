/**
 * Plugin Name: ContentBridge Publisher Protection
 * Plugin URI: https://contentbridge.com
 * Description: Protect and monetize your content from unauthorized AI crawling while enabling paid access through ContentBridge's marketplace.
 * Version: 1.0.0
 * Author: ContentBridge
 * Author URI: https://contentbridge.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: contentbridge
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('CONTENTBRIDGE_VERSION', '1.0.0');
define('CONTENTBRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBRIDGE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONTENTBRIDGE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'ContentBridge\\';
    $base_dir = CONTENTBRIDGE_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
function contentbridge_init() {
    // Load text domain for internationalization
    load_plugin_textdomain('contentbridge', false, dirname(CONTENTBRIDGE_PLUGIN_BASENAME) . '/languages');
    
    // Initialize plugin components
    if (is_admin()) {
        new ContentBridge\Admin();
    }
    
    new ContentBridge\ContentProtector();
    new ContentBridge\APIClient();
    new ContentBridge\TokenValidator();
    new ContentBridge\Analytics();
}
add_action('plugins_loaded', 'contentbridge_init');

// Register activation hook
register_activation_hook(__FILE__, function() {
    require_once CONTENTBRIDGE_PLUGIN_DIR . 'includes/class-installer.php';
    $installer = new ContentBridge\Installer();
    $installer->activate();
});

// Register deactivation hook
register_deactivation_hook(__FILE__, function() {
    require_once CONTENTBRIDGE_PLUGIN_DIR . 'includes/class-installer.php';
    $installer = new ContentBridge\Installer();
    $installer->deactivate();
}); 