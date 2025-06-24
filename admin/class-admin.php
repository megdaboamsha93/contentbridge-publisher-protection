<?php
/**
 * Admin interface and settings
 */
class ContentBridge_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_filter('plugin_action_links_contentbridge/contentbridge.php', array($this, 'add_settings_link'));
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
            'dashicons-shield-alt',
            30
        );

        add_submenu_page(
            'contentbridge',
            __('Dashboard', 'contentbridge'),
            __('Dashboard', 'contentbridge'),
            'manage_options',
            'contentbridge',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'contentbridge',
            __('Settings', 'contentbridge'),
            __('Settings', 'contentbridge'),
            'manage_options',
            'contentbridge-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'contentbridge',
            __('Analytics', 'contentbridge'),
            __('Analytics', 'contentbridge'),
            'manage_options',
            'contentbridge-analytics',
            array($this, 'render_analytics_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('contentbridge_settings', 'contentbridge_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));

        register_setting('contentbridge_settings', 'contentbridge_protection_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));

        register_setting('contentbridge_settings', 'contentbridge_protected_post_types', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_post_types'),
            'default' => array('post', 'page')
        ));

        register_setting('contentbridge_settings', 'contentbridge_default_pricing', array(
            'type' => 'number',
            'sanitize_callback' => 'floatval',
            'default' => 0.01
        ));

        register_setting('contentbridge_settings', 'contentbridge_protection_rules', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_protection_rules'),
            'default' => array()
        ));

        // Add settings sections
        add_settings_section(
            'contentbridge_general_settings',
            __('General Settings', 'contentbridge'),
            array($this, 'render_general_settings_section'),
            'contentbridge_settings'
        );

        add_settings_section(
            'contentbridge_protection_settings',
            __('Content Protection Settings', 'contentbridge'),
            array($this, 'render_protection_settings_section'),
            'contentbridge_settings'
        );

        // Add settings fields
        add_settings_field(
            'contentbridge_api_key',
            __('API Key', 'contentbridge'),
            array($this, 'render_api_key_field'),
            'contentbridge_settings',
            'contentbridge_general_settings'
        );

        add_settings_field(
            'contentbridge_protection_enabled',
            __('Enable Protection', 'contentbridge'),
            array($this, 'render_protection_enabled_field'),
            'contentbridge_settings',
            'contentbridge_protection_settings'
        );

        add_settings_field(
            'contentbridge_protected_post_types',
            __('Protected Content Types', 'contentbridge'),
            array($this, 'render_protected_post_types_field'),
            'contentbridge_settings',
            'contentbridge_protection_settings'
        );

        add_settings_field(
            'contentbridge_default_pricing',
            __('Default Price per Request', 'contentbridge'),
            array($this, 'render_default_pricing_field'),
            'contentbridge_settings',
            'contentbridge_protection_settings'
        );
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
            array(),
            CONTENTBRIDGE_VERSION
        );

        wp_enqueue_script(
            'contentbridge-admin',
            CONTENTBRIDGE_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'wp-api'),
            CONTENTBRIDGE_VERSION,
            true
        );

        wp_localize_script('contentbridge-admin', 'contentbridgeAdmin', array(
            'apiNonce' => wp_create_nonce('wp_rest'),
            'apiUrl' => rest_url('contentbridge/v1'),
            'strings' => array(
                'saveSuccess' => __('Settings saved successfully.', 'contentbridge'),
                'saveError' => __('Error saving settings.', 'contentbridge'),
                'confirmReset' => __('Are you sure you want to reset all settings?', 'contentbridge')
            )
        ));
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'contentbridge_revenue_widget',
            __('ContentBridge Revenue', 'contentbridge'),
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $analytics = new ContentBridge_Analytics();
        $stats = $analytics->get_revenue_stats('month');

        include CONTENTBRIDGE_PLUGIN_DIR . 'admin/views/dashboard-widget.php';
    }

    /**
     * Render main dashboard page
     */
    public function render_dashboard_page() {
        $analytics = new ContentBridge_Analytics();
        $stats = $analytics->get_revenue_stats('month');
        $chart_data = $analytics->get_revenue_chart_data('30days');

        include CONTENTBRIDGE_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include CONTENTBRIDGE_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        $analytics = new ContentBridge_Analytics();
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'month';
        $stats = $analytics->get_revenue_stats($period);
        $chart_data = $analytics->get_revenue_chart_data($period);

        include CONTENTBRIDGE_PLUGIN_DIR . 'admin/views/analytics.php';
    }

    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=contentbridge-settings'),
            __('Settings', 'contentbridge')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Render general settings section
     */
    public function render_general_settings_section() {
        echo '<p>' . esc_html__('Configure your ContentBridge integration settings.', 'contentbridge') . '</p>';
    }

    /**
     * Render protection settings section
     */
    public function render_protection_settings_section() {
        echo '<p>' . esc_html__('Configure how ContentBridge protects your content.', 'contentbridge') . '</p>';
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $api_key = get_option('contentbridge_api_key');
        ?>
        <input type="password" 
               name="contentbridge_api_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text"
               autocomplete="off">
        <p class="description">
            <?php esc_html_e('Enter your ContentBridge API key. You can find this in your ContentBridge dashboard.', 'contentbridge'); ?>
        </p>
        <?php
    }

    /**
     * Render protection enabled field
     */
    public function render_protection_enabled_field() {
        $enabled = get_option('contentbridge_protection_enabled', true);
        ?>
        <label>
            <input type="checkbox" 
                   name="contentbridge_protection_enabled" 
                   value="1" 
                   <?php checked($enabled); ?>>
            <?php esc_html_e('Enable content protection', 'contentbridge'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, protected content will require a valid ContentBridge token to access.', 'contentbridge'); ?>
        </p>
        <?php
    }

    /**
     * Render protected post types field
     */
    public function render_protected_post_types_field() {
        $protected_types = get_option('contentbridge_protected_post_types', array('post', 'page'));
        $post_types = get_post_types(array('public' => true), 'objects');

        foreach ($post_types as $type) {
            ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" 
                       name="contentbridge_protected_post_types[]" 
                       value="<?php echo esc_attr($type->name); ?>"
                       <?php checked(in_array($type->name, $protected_types)); ?>>
                <?php echo esc_html($type->label); ?>
            </label>
            <?php
        }
        ?>
        <p class="description">
            <?php esc_html_e('Select which content types should be protected by ContentBridge.', 'contentbridge'); ?>
        </p>
        <?php
    }

    /**
     * Render default pricing field
     */
    public function render_default_pricing_field() {
        $price = get_option('contentbridge_default_pricing', 0.01);
        ?>
        <input type="number" 
               name="contentbridge_default_pricing" 
               value="<?php echo esc_attr($price); ?>" 
               class="small-text"
               step="0.01"
               min="0">
        <p class="description">
            <?php esc_html_e('Default price per request in USD. This can be overridden for specific content.', 'contentbridge'); ?>
        </p>
        <?php
    }

    /**
     * Sanitize post types array
     */
    public function sanitize_post_types($types) {
        if (!is_array($types)) {
            return array('post', 'page');
        }

        return array_filter($types, function($type) {
            return post_type_exists($type);
        });
    }

    /**
     * Sanitize protection rules array
     */
    public function sanitize_protection_rules($rules) {
        if (!is_array($rules)) {
            return array();
        }

        return array_filter($rules, function($rule) {
            return isset($rule['type']) && 
                   isset($rule['value']) && 
                   in_array($rule['type'], array('category', 'tag', 'url_pattern'));
        });
    }
} 