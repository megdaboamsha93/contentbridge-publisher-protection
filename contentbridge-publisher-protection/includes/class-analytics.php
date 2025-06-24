<?php
namespace ContentBridge;

class Analytics {
    private $api_client;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        
        $this->api_client = new APIClient();
        $this->table_name = $wpdb->prefix . 'contentbridge_analytics';
        
        add_action('wp', [$this, 'track_content_view']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Track content view
     */
    public function track_content_view() {
        if (!is_singular()) {
            return;
        }
        
        $post_id = get_the_ID();
        $token = $this->get_current_token();
        
        if ($token) {
            $this->record_view($post_id, $token);
        }
    }
    
    /**
     * Record a content view
     */
    private function record_view($post_id, $token) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            [
                'post_id' => $post_id,
                'token_hash' => hash('sha256', $token),
                'view_date' => current_time('mysql'),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR']),
                'referer' => sanitize_text_field($_SERVER['HTTP_REFERER'] ?? '')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        // Report to ContentBridge platform
        $this->api_client->report_access($post_id, $token);
    }
    
    /**
     * Get current access token
     */
    private function get_current_token() {
        $token = filter_input(INPUT_GET, 'cb_token');
        if ($token) {
            return $token;
        }
        
        if (isset($_COOKIE['cb_access_token'])) {
            return sanitize_text_field($_COOKIE['cb_access_token']);
        }
        
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('contentbridge/v1', '/analytics', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_analytics_data'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);
        
        register_rest_route('contentbridge/v1', '/analytics/export', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'export_analytics_data'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);
    }
    
    /**
     * Check if user has admin permission
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Get analytics data for dashboard
     */
    public function get_analytics_data($request) {
        $start_date = $request->get_param('start_date') ?: date('Y-m-d', strtotime('-30 days'));
        $end_date = $request->get_param('end_date') ?: date('Y-m-d');
        
        // Get local analytics
        $local_stats = $this->get_local_stats($start_date, $end_date);
        
        // Get platform analytics
        $platform_stats = $this->api_client->get_analytics($start_date, $end_date);
        
        if (!$platform_stats) {
            return new \WP_Error('api_error', 'Failed to fetch platform analytics', ['status' => 500]);
        }
        
        return [
            'local' => $local_stats,
            'platform' => $platform_stats
        ];
    }
    
    /**
     * Get local analytics statistics
     */
    private function get_local_stats($start_date, $end_date) {
        global $wpdb;
        
        $views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE DATE(view_date) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        $unique_visitors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ip_hash) FROM {$this->table_name} 
            WHERE DATE(view_date) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        $popular_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, COUNT(*) as views 
            FROM {$this->table_name} 
            WHERE DATE(view_date) BETWEEN %s AND %s 
            GROUP BY post_id 
            ORDER BY views DESC 
            LIMIT 10",
            $start_date,
            $end_date
        ));
        
        return [
            'total_views' => (int) $views,
            'unique_visitors' => (int) $unique_visitors,
            'popular_posts' => array_map(function($post) {
                return [
                    'id' => $post->post_id,
                    'title' => get_the_title($post->post_id),
                    'views' => (int) $post->views
                ];
            }, $popular_posts)
        ];
    }
    
    /**
     * Export analytics data as CSV
     */
    public function export_analytics_data($request) {
        global $wpdb;
        
        $start_date = $request->get_param('start_date') ?: date('Y-m-d', strtotime('-30 days'));
        $end_date = $request->get_param('end_date') ?: date('Y-m-d');
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, view_date, user_agent, referer 
            FROM {$this->table_name} 
            WHERE DATE(view_date) BETWEEN %s AND %s 
            ORDER BY view_date DESC",
            $start_date,
            $end_date
        ));
        
        $filename = 'contentbridge-analytics-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Post ID', 'Post Title', 'View Date', 'User Agent', 'Referrer']);
        
        foreach ($results as $row) {
            fputcsv($output, [
                $row->post_id,
                get_the_title($row->post_id),
                $row->view_date,
                $row->user_agent,
                $row->referer
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get earnings data
     */
    public function get_earnings_data($period = 'month') {
        return $this->api_client->get_earnings($period);
    }
    
    /**
     * Get content performance metrics
     */
    public function get_content_metrics($post_id) {
        return $this->api_client->get_content_metrics($post_id);
    }
} 