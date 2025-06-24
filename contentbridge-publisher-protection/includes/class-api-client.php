<?php
namespace ContentBridge;

class APIClient {
    private $api_base_url;
    private $api_key;
    private $marketplace_url;
    
    public function __construct() {
        $this->api_base_url = get_option('contentbridge_api_url', 'https://api.contentbridge.com/v1');
        $this->marketplace_url = get_option('contentbridge_marketplace_url', 'https://market.contentbridge.com');
        $this->api_key = get_option('contentbridge_api_key');
    }
    
    /**
     * Validate API credentials
     */
    public function validate_credentials() {
        $response = $this->make_request('GET', '/validate');
        return $response && isset($response['valid']) && $response['valid'] === true;
    }
    
    /**
     * Get marketplace URL
     */
    public function get_marketplace_url() {
        return $this->marketplace_url;
    }
    
    /**
     * Report content access for analytics
     */
    public function report_access($post_id, $token) {
        return $this->make_request('POST', '/access', [
            'post_id' => $post_id,
            'token' => $token,
            'site_url' => get_site_url()
        ]);
    }
    
    /**
     * Get analytics data
     */
    public function get_analytics($start_date, $end_date) {
        return $this->make_request('GET', '/analytics', [
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }
    
    /**
     * Make an HTTP request to the ContentBridge API
     */
    private function make_request($method, $endpoint, $data = null) {
        if (!$this->api_key) {
            return false;
        }
        
        $url = $this->api_base_url . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'ContentBridge-WP/' . CONTENTBRIDGE_VERSION
            ],
            'timeout' => 30
        ];
        
        if ($data !== null) {
            if ($method === 'GET') {
                $url = add_query_arg($data, $url);
            } else {
                $args['body'] = wp_json_encode($data);
            }
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            error_log('ContentBridge API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);
        
        if ($status !== 200) {
            error_log('ContentBridge API Error: Unexpected status code ' . $status);
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('ContentBridge API Error: Invalid JSON response');
            return false;
        }
        
        return $data;
    }
    
    /**
     * Get publisher earnings data
     */
    public function get_earnings($period = 'month') {
        return $this->make_request('GET', '/earnings', [
            'period' => $period
        ]);
    }
    
    /**
     * Get content performance metrics
     */
    public function get_content_metrics($post_id) {
        return $this->make_request('GET', '/content/' . $post_id . '/metrics');
    }
    
    /**
     * Update protection settings on the platform
     */
    public function update_protection_settings($settings) {
        return $this->make_request('POST', '/settings', $settings);
    }
    
    /**
     * Sync local content status with the platform
     */
    public function sync_content_status($posts) {
        return $this->make_request('POST', '/sync', [
            'posts' => $posts
        ]);
    }
} 