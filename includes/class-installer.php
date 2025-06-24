<?php
namespace ContentBridge;

/**
 * Class Installer
 * Handles plugin installation and database setup
 */
class Installer {
    /**
     * Run the installer
     */
    public function install() {
        $this->create_tables();
        $this->set_default_options();
        $this->schedule_tasks();
        $this->create_directories();
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Analytics table
        $table_name = $wpdb->prefix . 'contentbridge_analytics';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            token_hash varchar(32) NOT NULL,
            access_time datetime NOT NULL,
            token_data text NOT NULL,
            user_agent text,
            ip_address varchar(45),
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY token_hash (token_hash),
            KEY access_time (access_time)
        ) $charset_collate;";

        // Daily stats table
        $table_name = $wpdb->prefix . 'contentbridge_daily_stats';
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            total_requests int(11) NOT NULL DEFAULT 0,
            total_revenue decimal(10,2) NOT NULL DEFAULT 0.00,
            unique_tokens int(11) NOT NULL DEFAULT 0,
            stats_data longtext NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY date (date)
        ) $charset_collate;";

        // Token cache table
        $table_name = $wpdb->prefix . 'contentbridge_tokens';
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name (
            token_hash varchar(32) NOT NULL,
            validation_data text NOT NULL,
            expiration datetime NOT NULL,
            PRIMARY KEY  (token_hash),
            KEY expiration (expiration)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $default_settings = array(
            'api_key' => '',
            'protection_level' => 'standard',
            'cache_duration' => 3600,
            'protected_post_types' => array('post', 'page')
        );

        if (!get_option('contentbridge_settings')) {
            add_option('contentbridge_settings', $default_settings);
        }

        // Set plugin version
        if (!get_option('contentbridge_version')) {
            add_option('contentbridge_version', CONTENTBRIDGE_VERSION);
        }

        // Set installation timestamp
        if (!get_option('contentbridge_installed')) {
            add_option('contentbridge_installed', time());
        }
    }

    /**
     * Schedule recurring tasks
     */
    private function schedule_tasks() {
        // Schedule daily analytics processing
        if (!wp_next_scheduled('contentbridge_daily_analytics')) {
            wp_schedule_event(time(), 'daily', 'contentbridge_daily_analytics');
        }

        // Schedule token cache cleanup
        if (!wp_next_scheduled('contentbridge_cleanup_token_cache')) {
            wp_schedule_event(time(), 'daily', 'contentbridge_cleanup_token_cache');
        }
    }

    /**
     * Create necessary directories
     */
    private function create_directories() {
        $upload_dir = wp_upload_dir();
        $contentbridge_dir = $upload_dir['basedir'] . '/contentbridge';

        // Create main directory
        if (!file_exists($contentbridge_dir)) {
            wp_mkdir_p($contentbridge_dir);
        }

        // Create .htaccess to protect the directory
        $htaccess_file = $contentbridge_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Create index.php to prevent directory listing
        $index_file = $contentbridge_dir . '/index.php';
        if (!file_exists($index_file)) {
            $index_content = "<?php\n// Silence is golden.";
            file_put_contents($index_file, $index_content);
        }
    }

    /**
     * Run plugin updates
     */
    public function update() {
        $current_version = get_option('contentbridge_version', '0.0.0');
        
        if (version_compare($current_version, CONTENTBRIDGE_VERSION, '<')) {
            // Run version-specific updates
            $this->run_updates($current_version);
            
            // Update version number
            update_option('contentbridge_version', CONTENTBRIDGE_VERSION);
        }
    }

    /**
     * Run version-specific updates
     *
     * @param string $current_version Current plugin version
     */
    private function run_updates($current_version) {
        global $wpdb;

        // Version 1.0.0 updates
        if (version_compare($current_version, '1.0.0', '<')) {
            // Add any necessary database columns or tables
            $this->create_tables();

            // Update any existing settings
            $settings = get_option('contentbridge_settings', array());
            if (!isset($settings['protection_level'])) {
                $settings['protection_level'] = 'standard';
                update_option('contentbridge_settings', $settings);
            }
        }

        // Future version updates can be added here
        // if (version_compare($current_version, '1.1.0', '<')) {
        //     // Run 1.1.0 updates
        // }
    }

    /**
     * Clean up plugin data
     */
    public static function uninstall() {
        global $wpdb;

        // Remove database tables
        $tables = array(
            'contentbridge_analytics',
            'contentbridge_daily_stats',
            'contentbridge_tokens'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
        }

        // Remove options
        delete_option('contentbridge_settings');
        delete_option('contentbridge_version');
        delete_option('contentbridge_installed');

        // Remove scheduled tasks
        wp_clear_scheduled_hook('contentbridge_daily_analytics');
        wp_clear_scheduled_hook('contentbridge_cleanup_token_cache');

        // Remove post meta
        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} 
            WHERE meta_key IN ('_contentbridge_protected', '_contentbridge_price')"
        );

        // Remove upload directory
        $upload_dir = wp_upload_dir();
        $contentbridge_dir = $upload_dir['basedir'] . '/contentbridge';
        
        if (file_exists($contentbridge_dir)) {
            $this->remove_directory($contentbridge_dir);
        }
    }

    /**
     * Recursively remove a directory
     *
     * @param string $dir Directory path
     */
    private static function remove_directory($dir) {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                self::remove_directory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
} 