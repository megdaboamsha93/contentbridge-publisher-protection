<?php
namespace ContentBridge;

/**
 * Class Token_Validator
 * Handles token validation and caching
 */
class Token_Validator {
    /**
     * @var API_Client
     */
    private $api_client;

    /**
     * @var int Cache duration in seconds
     */
    private $cache_duration;

    /**
     * Constructor
     *
     * @param API_Client|null $api_client Optional API client for testing
     */
    public function __construct($api_client = null) {
        $this->api_client = $api_client ?: new API_Client();
        $settings = get_option('contentbridge_settings', array());
        $this->cache_duration = isset($settings['cache_duration']) ? (int)$settings['cache_duration'] : 3600;
    }

    /**
     * Validate a token
     *
     * @param string $token The access token to validate
     * @return array Validation result with 'valid', 'message', and 'data' keys
     */
    public function validate_token($token) {
        // Check cache first
        $cached_result = $this->get_cached_validation($token);
        if (false !== $cached_result) {
            return $cached_result;
        }

        // Validate with API
        $validation_result = $this->validate_with_api($token);

        // Cache the result
        $this->cache_validation_result($token, $validation_result);

        return $validation_result;
    }

    /**
     * Get cached validation result
     *
     * @param string $token The access token
     * @return array|false Cached validation result or false if not found
     */
    private function get_cached_validation($token) {
        $cache_key = $this->get_cache_key($token);

        if (wp_using_ext_object_cache()) {
            return wp_cache_get($cache_key, 'contentbridge');
        }

        return get_transient($cache_key);
    }

    /**
     * Cache validation result
     *
     * @param string $token The access token
     * @param array $result The validation result to cache
     */
    private function cache_validation_result($token, $result) {
        $cache_key = $this->get_cache_key($token);

        if (wp_using_ext_object_cache()) {
            wp_cache_set($cache_key, $result, 'contentbridge', $this->cache_duration);
            return;
        }

        set_transient($cache_key, $result, $this->cache_duration);
    }

    /**
     * Validate token with API
     *
     * @param string $token The access token to validate
     * @return array Validation result
     */
    private function validate_with_api($token) {
        try {
            $response = $this->api_client->validate_token($token);

            if (!is_array($response) || !isset($response['valid'])) {
                return array(
                    'valid' => false,
                    'message' => __('Invalid API response', 'contentbridge'),
                    'data' => array()
                );
            }

            return array(
                'valid' => $response['valid'],
                'message' => isset($response['message']) ? $response['message'] : '',
                'data' => isset($response['data']) ? $response['data'] : array()
            );

        } catch (\Exception $e) {
            return array(
                'valid' => false,
                'message' => $e->getMessage(),
                'data' => array()
            );
        }
    }

    /**
     * Generate cache key for token
     *
     * @param string $token The access token
     * @return string Cache key
     */
    private function get_cache_key($token) {
        return 'cb_token_' . md5($token);
    }

    /**
     * Clear token validation cache
     *
     * @param string $token Optional specific token to clear
     */
    public function clear_cache($token = null) {
        if ($token) {
            $cache_key = $this->get_cache_key($token);
            
            if (wp_using_ext_object_cache()) {
                wp_cache_delete($cache_key, 'contentbridge');
            } else {
                delete_transient($cache_key);
            }
            
            return;
        }

        // Clear all token caches if no specific token provided
        if (!wp_using_ext_object_cache()) {
            global $wpdb;
            
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_cb_token_%' 
                OR option_name LIKE '_transient_timeout_cb_token_%'"
            );
        }
    }

    /**
     * Schedule cache cleanup
     */
    public function schedule_cache_cleanup() {
        if (!wp_using_ext_object_cache() && !wp_next_scheduled('contentbridge_cleanup_token_cache')) {
            wp_schedule_event(time(), 'daily', 'contentbridge_cleanup_token_cache');
        }
    }

    /**
     * Cleanup expired token caches
     */
    public function cleanup_expired_cache() {
        if (wp_using_ext_object_cache()) {
            return;
        }

        global $wpdb;
        
        // Delete expired transients
        $wpdb->query(
            "DELETE a, b FROM {$wpdb->options} a 
            LEFT JOIN {$wpdb->options} b 
                ON b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12)) 
            WHERE a.option_name LIKE '_transient_cb_token_%' 
            AND b.option_value < " . time()
        );
    }
} 