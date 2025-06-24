<?php
namespace ContentBridge;

/**
 * Class Analytics
 * Handles usage tracking and revenue reporting
 */
class Analytics {
    /**
     * @var API_Client
     */
    private $api_client;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_client = new API_Client();
        $this->init();
    }

    /**
     * Initialize analytics
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_analytics_menu'));
        add_action('wp_ajax_contentbridge_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('contentbridge_daily_analytics', array($this, 'process_daily_analytics'));

        // Schedule daily analytics processing if not already scheduled
        if (!wp_next_scheduled('contentbridge_daily_analytics')) {
            wp_schedule_event(time(), 'daily', 'contentbridge_daily_analytics');
        }
    }

    /**
     * Add analytics menu
     */
    public function add_analytics_menu() {
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
     * Track content access
     *
     * @param array $data Access data
     */
    public function track_access($data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'contentbridge_analytics';
        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $data['post_id'],
                'token_hash' => md5($data['token']),
                'access_time' => current_time('mysql'),
                'token_data' => maybe_serialize($data['token_data']),
                'user_agent' => $data['user_agent'],
                'ip_address' => $data['ip_address']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        // Send to ContentBridge API
        try {
            $this->api_client->track_access($data);
        } catch (\Exception $e) {
            error_log('ContentBridge API Error: ' . $e->getMessage());
        }
    }

    /**
     * Get analytics data
     *
     * @param string $start_date Start date (YYYY-MM-DD)
     * @param string $end_date End date (YYYY-MM-DD)
     * @return array Analytics data
     */
    public function get_analytics($start_date, $end_date) {
        try {
            $revenue = $this->api_client->get_revenue_report($start_date, $end_date);
            $content = $this->api_client->get_content_performance($start_date, $end_date);
            $companies = $this->api_client->get_ai_company_usage($start_date, $end_date);

            return array(
                'revenue' => $revenue,
                'content' => $content,
                'companies' => $companies
            );
        } catch (\Exception $e) {
            return new \WP_Error('api_error', $e->getMessage());
        }
    }

    /**
     * Process daily analytics
     */
    public function process_daily_analytics() {
        global $wpdb;

        // Get yesterday's date
        $date = date('Y-m-d', strtotime('-1 day'));

        // Get analytics data
        $analytics = $this->get_analytics($date, $date);
        if (is_wp_error($analytics)) {
            error_log('ContentBridge Analytics Error: ' . $analytics->get_error_message());
            return;
        }

        // Store aggregated data
        $table_name = $wpdb->prefix . 'contentbridge_daily_stats';
        $wpdb->insert(
            $table_name,
            array(
                'date' => $date,
                'total_requests' => $analytics['revenue']['total_requests'],
                'total_revenue' => $analytics['revenue']['total_revenue'],
                'unique_tokens' => $analytics['revenue']['unique_tokens'],
                'stats_data' => maybe_serialize($analytics)
            ),
            array('%s', '%d', '%f', '%d', '%s')
        );
    }

    /**
     * AJAX handler for analytics data
     */
    public function ajax_get_analytics() {
        check_ajax_referer('contentbridge_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');

        $analytics = $this->get_analytics($start_date, $end_date);
        if (is_wp_error($analytics)) {
            wp_send_json_error($analytics->get_error_message());
        }

        wp_send_json_success($analytics);
    }

    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        // Check if template exists
        $template = CONTENTBRIDGE_PLUGIN_DIR . 'admin/templates/analytics.php';
        if (file_exists($template)) {
            include $template;
            return;
        }

        // Fallback HTML if template doesn't exist
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('ContentBridge Analytics', 'contentbridge'); ?></h1>
            
            <div class="contentbridge-analytics-filters">
                <input type="date" id="cb-start-date" value="<?php echo esc_attr(date('Y-m-d', strtotime('-30 days'))); ?>">
                <input type="date" id="cb-end-date" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                <button class="button button-primary" id="cb-update-analytics">
                    <?php echo esc_html__('Update', 'contentbridge'); ?>
                </button>
            </div>

            <div class="contentbridge-analytics-grid">
                <div class="contentbridge-analytics-card">
                    <h3><?php echo esc_html__('Revenue Overview', 'contentbridge'); ?></h3>
                    <div id="cb-revenue-chart"></div>
                </div>

                <div class="contentbridge-analytics-card">
                    <h3><?php echo esc_html__('Top Content', 'contentbridge'); ?></h3>
                    <div id="cb-content-table"></div>
                </div>

                <div class="contentbridge-analytics-card">
                    <h3><?php echo esc_html__('AI Company Usage', 'contentbridge'); ?></h3>
                    <div id="cb-companies-chart"></div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            function loadAnalytics() {
                var startDate = $('#cb-start-date').val();
                var endDate = $('#cb-end-date').val();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'contentbridge_get_analytics',
                        nonce: '<?php echo wp_create_nonce('contentbridge_analytics'); ?>',
                        start_date: startDate,
                        end_date: endDate
                    },
                    success: function(response) {
                        if (response.success) {
                            updateCharts(response.data);
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Error loading analytics', 'contentbridge')); ?>');
                    }
                });
            }

            function updateCharts(data) {
                // Update revenue chart
                if (data.revenue) {
                    // Implement chart update logic
                }

                // Update content table
                if (data.content) {
                    var table = '<table class="wp-list-table widefat fixed striped">';
                    table += '<thead><tr><th>Content</th><th>Requests</th><th>Revenue</th></tr></thead><tbody>';
                    
                    data.content.forEach(function(item) {
                        table += '<tr>';
                        table += '<td>' + item.title + '</td>';
                        table += '<td>' + item.requests + '</td>';
                        table += '<td>$' + item.revenue.toFixed(2) + '</td>';
                        table += '</tr>';
                    });
                    
                    table += '</tbody></table>';
                    $('#cb-content-table').html(table);
                }

                // Update companies chart
                if (data.companies) {
                    // Implement chart update logic
                }
            }

            $('#cb-update-analytics').on('click', loadAnalytics);
            loadAnalytics(); // Initial load
        });
        </script>
        <?php
    }

    /**
     * Create analytics tables
     */
    public static function create_tables() {
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 