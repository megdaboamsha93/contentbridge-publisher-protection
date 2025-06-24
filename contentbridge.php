<?php
/*
Plugin Name: ContentBridge Publisher Protection
Plugin URI: https://contentbridge.com/
Description: Protects your WordPress content from unauthorized AI crawling and scraping, while allowing paid access via ContentBridge marketplace.
Version: 1.0.0
Author: ContentBridge
Author URI: https://contentbridge.com/
License: MIT
License URI: https://opensource.org/licenses/MIT
Text Domain: contentbridge
Domain Path: /languages
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CONTENTBRIDGE_VERSION', '1.0.0');
define('CONTENTBRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBRIDGE_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('CONTENTBRIDGE_API_URL')) {
    define('CONTENTBRIDGE_API_URL', 'https://your-supabase-project.supabase.co/functions/v1/validate-token');
}
if (!defined('CONTENTBRIDGE_VALIDATION_METHOD')) {
    define('CONTENTBRIDGE_VALIDATION_METHOD', 'POST');
}

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
    load_plugin_textdomain('contentbridge', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize core classes
    if (is_admin()) {
        require_once CONTENTBRIDGE_PLUGIN_DIR . 'includes/class-admin.php';
        new ContentBridge\Admin();
    }
    
    require_once CONTENTBRIDGE_PLUGIN_DIR . 'includes/class-content-protector.php';
    require_once CONTENTBRIDGE_PLUGIN_DIR . 'includes/class-api-client.php';
    require_once CONTENTBRIDGE_PLUGIN_DIR . 'includes/class-token-validator.php';
    require_once CONTENTBRIDGE_PLUGIN_DIR . 'includes/class-analytics.php';
    
    $content_protector = new ContentBridge\Content_Protector();
    $content_protector->init();

    /**
     * ContentBridge Security Headers
     * Adds essential security headers to reach 9/10 security rating
     */
    class ContentBridge_Security_Headers {
        public function __construct() {
            add_action('init', array($this, 'add_security_headers'));
            add_action('admin_init', array($this, 'add_admin_security_headers'));
        }
        /**
         * Add security headers to all responses
         */
        public function add_security_headers() {
            if (!headers_sent()) {
                header('X-Content-Type-Options: nosniff');
                header('X-Frame-Options: SAMEORIGIN');
                header('X-XSS-Protection: 1; mode=block');
                header('Referrer-Policy: strict-origin-when-cross-origin');
            }
        }
        /**
         * Add CSP headers for admin pages
         */
        public function add_admin_security_headers() {
            if (is_admin() && !headers_sent()) {
                // Relaxed CSP for WordPress admin compatibility
                header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");
            }
        }
    }
    // Initialize security headers
    if (class_exists('ContentBridge_Security_Headers')) {
        new ContentBridge_Security_Headers();
    }
}
add_action('plugins_loaded', 'contentbridge_init');

// Activation hook
register_activation_hook(__FILE__, 'contentbridge_activate');
function contentbridge_activate() {
    // Create necessary database tables
    require_once CONTENTBRIDGE_PLUGIN_DIR . 'includes/class-installer.php';
    $installer = new ContentBridge\Installer();
    $installer->install();
    
    // Set default options
    $default_options = array(
        'api_key' => '',
        'protection_level' => 'standard',
        'cache_duration' => 3600,
        'protected_post_types' => array('post', 'page'),
    );
    
    add_option('contentbridge_settings', $default_options);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'contentbridge_deactivate');
function contentbridge_deactivate() {
    // Clean up any plugin-specific temporary data
    wp_clear_scheduled_hook('contentbridge_daily_analytics');
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'contentbridge_uninstall');
function contentbridge_uninstall() {
    // Remove all plugin data if user chooses to uninstall
    delete_option('contentbridge_settings');
    
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}contentbridge_analytics");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}contentbridge_tokens");
} 