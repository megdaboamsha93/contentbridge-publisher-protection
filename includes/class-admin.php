<?php
namespace ContentBridge;

/**
 * Class Admin
 * Handles admin interface and settings
 */
class Admin {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
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
            array($this, 'render_dashboard_page'),
            'dashicons-shield',
            30
        );

        add_submenu_page(
            'contentbridge',
            __('Settings', 'contentbridge'),
            __('Settings', 'contentbridge'),
            'manage_options',
            'contentbridge-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('contentbridge_settings', 'contentbridge_settings', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));

        add_settings_section(
            'contentbridge_general',
            __('General Settings', 'contentbridge'),
            array($this, 'render_general_section'),
            'contentbridge-settings'
        );

        add_settings_field(
            'api_key',
            __('API Key', 'contentbridge'),
            array($this, 'render_api_key_field'),
            'contentbridge-settings',
            'contentbridge_general'
        );

        add_settings_field(
            'protected_post_types',
            __('Protected Post Types', 'contentbridge'),
            array($this, 'render_post_types_field'),
            'contentbridge-settings',
            'contentbridge_general'
        );

        add_settings_field(
            'protection_level',
            __('Protection Level', 'contentbridge'),
            array($this, 'render_protection_level_field'),
            'contentbridge-settings',
            'contentbridge_general'
        );

        add_settings_field(
            'cache_duration',
            __('Cache Duration (seconds)', 'contentbridge'),
            array($this, 'render_cache_duration_field'),
            'contentbridge-settings',
            'contentbridge_general'
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input Settings input
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }

        if (isset($input['protected_post_types']) && is_array($input['protected_post_types'])) {
            $sanitized['protected_post_types'] = array_map('sanitize_text_field', $input['protected_post_types']);
        }

        if (isset($input['protection_level'])) {
            $sanitized['protection_level'] = sanitize_text_field($input['protection_level']);
        }

        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = absint($input['cache_duration']);
        }

        return $sanitized;
    }

    /**
     * Render general section description
     */
    public function render_general_section() {
        echo '<p>' . esc_html__('Configure your ContentBridge Publisher Protection settings below.', 'contentbridge') . '</p>';
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $settings = get_option('contentbridge_settings', array());
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        ?>
        <input type="text" 
               name="contentbridge_settings[api_key]" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text"
               autocomplete="off">
        <p class="description">
            <?php echo wp_kses(
                __('Get your API key from your <a href="https://contentbridge.com/dashboard" target="_blank">ContentBridge dashboard</a>.', 'contentbridge'),
                array('a' => array('href' => array(), 'target' => array()))
            ); ?>
        </p>
        <?php
    }

    /**
     * Render post types field
     */
    public function render_post_types_field() {
        $settings = get_option('contentbridge_settings', array());
        $protected_types = isset($settings['protected_post_types']) ? $settings['protected_post_types'] : array('post', 'page');
        $post_types = get_post_types(array('public' => true), 'objects');

        foreach ($post_types as $type) {
            ?>
            <label>
                <input type="checkbox" 
                       name="contentbridge_settings[protected_post_types][]" 
                       value="<?php echo esc_attr($type->name); ?>"
                       <?php checked(in_array($type->name, $protected_types)); ?>>
                <?php echo esc_html($type->label); ?>
            </label><br>
            <?php
        }
    }

    /**
     * Render protection level field
     */
    public function render_protection_level_field() {
        $settings = get_option('contentbridge_settings', array());
        $level = isset($settings['protection_level']) ? $settings['protection_level'] : 'standard';
        ?>
        <select name="contentbridge_settings[protection_level]">
            <option value="standard" <?php selected($level, 'standard'); ?>>
                <?php esc_html_e('Standard', 'contentbridge'); ?>
            </option>
            <option value="strict" <?php selected($level, 'strict'); ?>>
                <?php esc_html_e('Strict', 'contentbridge'); ?>
            </option>
            <option value="custom" <?php selected($level, 'custom'); ?>>
                <?php esc_html_e('Custom', 'contentbridge'); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render cache duration field
     */
    public function render_cache_duration_field() {
        $settings = get_option('contentbridge_settings', array());
        $duration = isset($settings['cache_duration']) ? absint($settings['cache_duration']) : 3600;
        ?>
        <input type="number" 
               name="contentbridge_settings[cache_duration]" 
               value="<?php echo esc_attr($duration); ?>" 
               min="0" 
               step="1">
        <p class="description">
            <?php esc_html_e('Time in seconds to cache token validation results. Set to 0 to disable caching.', 'contentbridge'); ?>
        </p>
        <?php
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $settings = get_option('contentbridge_settings', array());
        $post_types = isset($settings['protected_post_types']) 
            ? $settings['protected_post_types'] 
            : array('post', 'page');

        foreach ($post_types as $type) {
            add_meta_box(
                'contentbridge-protection',
                __('ContentBridge Protection', 'contentbridge'),
                array($this, 'render_meta_box'),
                $type,
                'side',
                'high'
            );
        }
    }

    /**
     * Render meta box
     *
     * @param \WP_Post $post Post object
     */
    public function render_meta_box($post) {
        wp_nonce_field('contentbridge_meta_box', 'contentbridge_meta_box_nonce');

        $is_protected = get_post_meta($post->ID, '_contentbridge_protected', true);
        $custom_price = get_post_meta($post->ID, '_contentbridge_price', true);
        ?>
        <p>
            <label>
                <input type="checkbox" 
                       name="contentbridge_protected" 
                       value="1" 
                       <?php checked($is_protected, '1'); ?>>
                <?php esc_html_e('Protect this content', 'contentbridge'); ?>
            </label>
        </p>
        <p>
            <label>
                <?php esc_html_e('Custom price per request:', 'contentbridge'); ?><br>
                <input type="number" 
                       name="contentbridge_price" 
                       value="<?php echo esc_attr($custom_price); ?>" 
                       step="0.01" 
                       min="0">
            </label>
        </p>
        <?php
    }

    /**
     * Save meta box data
     *
     * @param int $post_id Post ID
     */
    public function save_meta_box_data($post_id) {
        if (!isset($_POST['contentbridge_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['contentbridge_meta_box_nonce'], 'contentbridge_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $is_protected = isset($_POST['contentbridge_protected']) ? '1' : '0';
        update_post_meta($post_id, '_contentbridge_protected', $is_protected);

        if (isset($_POST['contentbridge_price'])) {
            $price = (float) $_POST['contentbridge_price'];
            update_post_meta($post_id, '_contentbridge_price', $price);
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook_suffix The current admin page
     */
    public function enqueue_admin_assets($hook_suffix) {
        if (!in_array($hook_suffix, array('toplevel_page_contentbridge', 'contentbridge_page_contentbridge-analytics'))) {
            return;
        }

        wp_enqueue_style(
            'contentbridge-admin',
            CONTENTBRIDGE_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            CONTENTBRIDGE_VERSION
        );

        wp_enqueue_script(
            'contentbridge-admin',
            CONTENTBRIDGE_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            CONTENTBRIDGE_VERSION,
            true
        );

        wp_localize_script('contentbridge-admin', 'contentbridgeAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentbridge_admin'),
            'i18n' => array(
                'error' => __('Error', 'contentbridge'),
                'success' => __('Success', 'contentbridge')
            )
        ));
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'contentbridge_dashboard_widget',
            __('ContentBridge Overview', 'contentbridge'),
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        include(CONTENTBRIDGE_PLUGIN_DIR . 'admin/templates/dashboard-widget.php');
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        include(CONTENTBRIDGE_PLUGIN_DIR . 'admin/templates/dashboard.php');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('ContentBridge Settings', 'contentbridge'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('contentbridge_settings');
                do_settings_sections('contentbridge-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
} 