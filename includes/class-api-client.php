<?php
namespace ContentBridge;

/**
 * Class API_Client
 * Handles communication with the ContentBridge API
 */
class API_Client {
    const VALIDATION_URL = 'https://wvnejqwzvebccjcjbfye.supabase.co/functions/v1/validate-token';
    const VALIDATION_METHOD = 'POST';

    /**
     * @var string API base URL
     */
    private $api_base_url = 'https://api.contentbridge.com/v1';

    /**
     * @var string API key
     */
    private $api_key;

    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('contentbridge_settings', array());
        $this->api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
    }

    /**
     * Validate a token with the ContentBridge platform
     *
     * @param string $token The access token
     * @return array Response from the platform
     */
    public function validate_token($token) {
        $url = self::VALIDATION_URL;
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'token' => $token,
                'type' => 'publisher',
            ]),
            'timeout' => 10,
        ];
        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            return [
                'valid' => false,
                'message' => $response->get_error_message(),
                'data' => []
            ];
        }
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
        if (!is_array($json)) {
            return [
                'valid' => false,
                'message' => __('Invalid response from validation server', 'contentbridge'),
                'data' => []
            ];
        }
        // Map platform response to plugin expected format
        return [
            'valid' => !empty($json['valid']),
            'message' => $json['result'] ?? '',
            'data' => $json,
        ];
    }

    /**
     * Track content access
     *
     * @param array $data Access data
     * @return array API response
     * @throws \Exception If API request fails
     */
    public function track_access($data) {
        return $this->make_request('POST', '/analytics/track', $data);
    }

    /**
     * Get revenue report
     *
     * @param string $start_date Start date (YYYY-MM-DD)
     * @param string $end_date End date (YYYY-MM-DD)
     * @return array API response
     * @throws \Exception If API request fails
     */
    public function get_revenue_report($start_date, $end_date) {
        return $this->make_request('GET', '/analytics/revenue', array(
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
    }

    /**
     * Get content performance report
     *
     * @param string $start_date Start date (YYYY-MM-DD)
     * @param string $end_date End date (YYYY-MM-DD)
     * @return array API response
     * @throws \Exception If API request fails
     */
    public function get_content_performance($start_date, $end_date) {
        return $this->make_request('GET', '/analytics/content', array(
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
    }

    /**
     * Get AI company usage report
     *
     * @param string $start_date Start date (YYYY-MM-DD)
     * @param string $end_date End date (YYYY-MM-DD)
     * @return array API response
     * @throws \Exception If API request fails
     */
    public function get_ai_company_usage($start_date, $end_date) {
        return $this->make_request('GET', '/analytics/companies', array(
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
    }

    /**
     * Make an API request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array API response
     * @throws \Exception If API request fails
     */
    private function make_request($method, $endpoint, $data = array()) {
        if (empty($this->api_key)) {
            throw new \Exception(__('API key not configured', 'contentbridge'));
        }

        $url = $this->api_base_url . $endpoint;
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => $this->get_user_agent()
            ),
            'timeout' => 30
        );

        if (!empty($data)) {
            if ($method === 'GET') {
                $url = add_query_arg($data, $url);
            } else {
                $args['body'] = wp_json_encode($data);
            }
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = isset($response_data['message']) 
                ? $response_data['message'] 
                : __('Unknown API error', 'contentbridge');
            
            throw new \Exception($error_message, $response_code);
        }

        if (!is_array($response_data)) {
            throw new \Exception(__('Invalid API response format', 'contentbridge'));
        }

        return $response_data;
    }

    /**
     * Get the current post/page URL
     *
     * @return string
     */
    private function get_current_url() {
        // Try to get the permalink for the current post
        if (function_exists('get_permalink') && get_the_ID()) {
            return get_permalink(get_the_ID());
        }
        // Fallback to home URL
        return home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? ''));
    }

    /**
     * Get user agent string
     *
     * @return string User agent
     */
    private function get_user_agent() {
        global $wp_version;
        return sprintf(
            'ContentBridge WordPress Plugin/%s WordPress/%s PHP/%s',
            CONTENTBRIDGE_VERSION,
            $wp_version,
            phpversion()
        );
    }
} 