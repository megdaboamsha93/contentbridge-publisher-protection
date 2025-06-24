<?php
namespace ContentBridge;

class Installer {
    private $token_cache_table;
    private $analytics_table;
    private $db_version = '1.0.0';
    
    public function __construct() {
        global $wpdb;
        
        $this->token_cache_table = $wpdb->prefix . 'contentbridge_token_cache';
        $this->analytics_table = $wpdb->prefix . 'contentbridge_analytics';
    }
    
    /**
     * Activate the plugin
     */
    public function activate() {
        $this->create_tables();
        $this->create_default_options();
        
        // Store the current database version
        update_option('contentbridge_db_version', $this->db_version);
        
        // Clear any existing rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Deactivate the plugin
     */
    public function deactivate() {
        // Clear any plugin-specific rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Token cache table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->token_cache_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            token_hash varchar(64) NOT NULL,
            is_valid tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token_hash (token_hash),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Analytics table
        $sql .= "CREATE TABLE IF NOT EXISTS {$this->analytics_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            token_hash varchar(64) NOT NULL,
            view_date datetime NOT NULL,
            user_agent varchar(255),
            ip_hash varchar(64) NOT NULL,
            referer varchar(255),
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY token_hash (token_hash),
            KEY view_date (view_date),
            KEY ip_hash (ip_hash)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create default options
     */
    private function create_default_options() {
        // API settings
        add_option('contentbridge_api_url', 'https://api.contentbridge.com/v1');
        add_option('contentbridge_marketplace_url', 'https://market.contentbridge.com');
        add_option('contentbridge_token_cache_expiration', 3600);
        
        // Protection settings
        add_option('contentbridge_protected_types', ['post']);
        add_option('contentbridge_protected_categories', []);
        add_option('contentbridge_protected_tags', []);
        add_option('contentbridge_protect_feeds', true);
        add_option('contentbridge_protect_api', true);
        add_option('contentbridge_add_headers', true);
    }
    
    /**
     * Update database schema if needed
     */
    public function update_if_needed() {
        $current_version = get_option('contentbridge_db_version', '0');
        
        if (version_compare($current_version, $this->db_version, '<')) {
            $this->create_tables();
            update_option('contentbridge_db_version', $this->db_version);
        }
    }
    
    /**
     * Remove plugin data on uninstall
     */
    public static function uninstall() {
        global $wpdb;
        
        // Drop tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}contentbridge_token_cache");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}contentbridge_analytics");
        
        // Remove options
        $options = [
            'contentbridge_api_key',
            'contentbridge_api_url',
            'contentbridge_marketplace_url',
            'contentbridge_token_cache_expiration',
            'contentbridge_protected_types',
            'contentbridge_protected_categories',
            'contentbridge_protected_tags',
            'contentbridge_protect_feeds',
            'contentbridge_protect_api',
            'contentbridge_add_headers',
            'contentbridge_db_version'
        ];
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove post meta
        $wpdb->delete($wpdb->postmeta, ['meta_key' => '_contentbridge_disable_protection']);
    }
} 