<?php
namespace ContentBridge;

class Admin {
    private $api_client;
    private $analytics;
    
    public function __construct() {
        $this->api_client = new APIClient();
        $this->analytics = new Analytics();
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_post_meta']);
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            __('ContentBridge', 'contentbridge'),
            __('ContentBridge', 'contentbridge'),
            'manage_options',
            'contentbridge',
            [$this, 'render_dashboard_page'],
            'dashicons-shield',
            30
        );
        
        add_submenu_page(
            'contentbridge',
            __('Dashboard', 'contentbridge'),
            __('Dashboard', 'contentbridge'),
            'manage_options',
            'contentbridge',
            [$this, 'render_dashboard_page']
        );
        
        add_submenu_page(
            'contentbridge',
            __('Settings', 'contentbridge'),
            __('Settings', 'contentbridge'),
            'manage_options',
            'contentbridge-settings',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'contentbridge',
            __('Analytics', 'contentbridge'),
            __('Analytics', 'contentbridge'),
            'manage_options',
            'contentbridge-analytics',
            [$this, 'render_analytics_page']
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('contentbridge_settings', 'contentbridge_api_key');
        register_setting('contentbridge_settings', 'contentbridge_api_url');
        register_setting('contentbridge_settings', 'contentbridge_marketplace_url');
        register_setting('contentbridge_settings', 'contentbridge_token_cache_expiration', [
            'type' => 'integer',
            'default' => 3600
        ]);
        register_setting('contentbridge_settings', 'contentbridge_protected_types', [
            'type' => 'array',
            'default' => ['post']
        ]);
        register_setting('contentbridge_settings', 'contentbridge_protected_categories', [
            'type' => 'array',
            'default' => []
        ]);
        register_setting('contentbridge_settings', 'contentbridge_protected_tags', [
            'type' => 'array',
            'default' => []
        ]);
        register_setting('contentbridge_settings', 'contentbridge_protect_feeds', [
            'type' => 'boolean',
            'default' => true
        ]);
        register_setting('contentbridge_settings', 'contentbridge_protect_api', [
            'type' => 'boolean',
            'default' => true
        ]);
        register_setting('contentbridge_settings', 'contentbridge_add_headers', [
            'type' => 'boolean',
            'default' => true
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'contentbridge') === false) {
            return;
        }
        
        wp_enqueue_style(
            'contentbridge-admin',
            CONTENTBRIDGE_PLUGIN_URL . 'admin/css/admin.css',
            [],
            CONTENTBRIDGE_VERSION
        );
        
        wp_enqueue_script(
            'contentbridge-admin',
            CONTENTBRIDGE_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery', 'wp-api'],
            CONTENTBRIDGE_VERSION,
            true
        );
        
        wp_localize_script('contentbridge-admin', 'contentbridgeAdmin', [
            'apiNonce' => wp_create_nonce('wp_rest'),
            'apiUrl' => rest_url('contentbridge/v1'),
            'strings' => [
                'saveSuccess' => __('Settings saved successfully.', 'contentbridge'),
                'saveError' => __('Error saving settings.', 'contentbridge'),
                'confirmReset' => __('Are you sure you want to reset all settings?', 'contentbridge')
            ]
        ]);
    }
    
    /**
     * Add meta boxes to post editor
     */
    public function add_meta_boxes() {
        $post_types = get_option('contentbridge_protected_types', ['post']);
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'contentbridge-protection',
                __('ContentBridge Protection', 'contentbridge'),
                [$this, 'render_protection_meta_box'],
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Save post meta
     */
    public function save_post_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['contentbridge_disable_protection'])) {
            update_post_meta(
                $post_id,
                '_contentbridge_disable_protection',
                sanitize_text_field($_POST['contentbridge_disable_protection'])
            );
        }
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include CONTENTBRIDGE_PLUGIN_DIR . 'admin/templates/dashboard.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include CONTENTBRIDGE_PLUGIN_DIR . 'admin/templates/settings.php';
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include CONTENTBRIDGE_PLUGIN_DIR . 'admin/templates/analytics.php';
    }
    
    /**
     * Render protection meta box
     */
    public function render_protection_meta_box($post) {
        $disabled = get_post_meta($post->ID, '_contentbridge_disable_protection', true);
        
        wp_nonce_field('contentbridge_meta_box', 'contentbridge_meta_box_nonce');
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="contentbridge_disable_protection" value="1" <?php checked($disabled, '1'); ?>>
                <?php _e('Disable protection for this content', 'contentbridge'); ?>
            </label>
        </p>
        <p class="description">
            <?php _e('Check this box to disable ContentBridge protection for this specific content.', 'contentbridge'); ?>
        </p>
        <?php
    }
    
    /**
     * Get protection status for posts list
     */
    public function get_protection_status($post_id) {
        $disabled = get_post_meta($post_id, '_contentbridge_disable_protection', true);
        
        if ($disabled) {
            return [
                'protected' => false,
                'reason' => __('Protection manually disabled', 'contentbridge')
            ];
        }
        
        $post_type = get_post_type($post_id);
        $protected_types = get_option('contentbridge_protected_types', ['post']);
        
        if (!in_array($post_type, $protected_types)) {
            return [
                'protected' => false,
                'reason' => __('Post type not protected', 'contentbridge')
            ];
        }
        
        $protected_categories = get_option('contentbridge_protected_categories', []);
        if (!empty($protected_categories) && has_category($protected_categories, $post_id)) {
            return [
                'protected' => true,
                'reason' => __('Category protection rule', 'contentbridge')
            ];
        }
        
        $protected_tags = get_option('contentbridge_protected_tags', []);
        if (!empty($protected_tags) && has_tag($protected_tags, $post_id)) {
            return [
                'protected' => true,
                'reason' => __('Tag protection rule', 'contentbridge')
            ];
        }
        
        return [
            'protected' => true,
            'reason' => __('Default protection', 'contentbridge')
        ];
    }
} 