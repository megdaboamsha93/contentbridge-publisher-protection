<?php
namespace ContentBridge;

class TokenValidator {
    private $api_client;
    private $cache_expiration;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        
        $this->api_client = new APIClient();
        $this->cache_expiration = get_option('contentbridge_token_cache_expiration', 3600); // 1 hour default
        $this->table_name = $wpdb->prefix . 'contentbridge_token_cache';
    }
    
    /**
     * Validate a token and check permissions
     */
    public function validate($token) {
        // First check cache
        $cached_result = $this->get_cached_validation($token);
        if ($cached_result !== null) {
            return $cached_result;
        }
        
        // If not in cache, validate with API
        $validation_result = $this->validate_with_api($token);
        
        // Cache the result
        $this->cache_validation_result($token, $validation_result);
        
        return $validation_result;
    }
    
    /**
     * Get cached validation result
     */
    private function get_cached_validation($token) {
        global $wpdb;
        
        $hash = $this->hash_token($token);
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT is_valid, created_at FROM {$this->table_name} WHERE token_hash = %s",
            $hash
        ));
        
        if (!$result) {
            return null;
        }
        
        // Check if cache has expired
        $expiration_time = strtotime($result->created_at) + $this->cache_expiration;
        if (time() > $expiration_time) {
            $this->clear_expired_cache();
            return null;
        }
        
        return (bool) $result->is_valid;
    }
    
    /**
     * Validate token with the API
     */
    private function validate_with_api($token) {
        $response = $this->api_client->make_request('POST', '/validate-token', [
            'token' => $token,
            'site_url' => get_site_url()
        ]);
        
        return $response && isset($response['valid']) && $response['valid'] === true;
    }
    
    /**
     * Cache validation result
     */
    private function cache_validation_result($token, $is_valid) {
        global $wpdb;
        
        $hash = $this->hash_token($token);
        
        $wpdb->replace(
            $this->table_name,
            [
                'token_hash' => $hash,
                'is_valid' => $is_valid,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%d', '%s']
        );
    }
    
    /**
     * Clear expired cache entries
     */
    private function clear_expired_cache() {
        global $wpdb;
        
        $expiration_date = date('Y-m-d H:i:s', time() - $this->cache_expiration);
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < %s",
            $expiration_date
        ));
    }
    
    /**
     * Hash token for secure storage
     */
    private function hash_token($token) {
        return hash('sha256', $token);
    }
    
    /**
     * Clear all cached validations
     */
    public function clear_cache() {
        global $wpdb;
        
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
    
    /**
     * Get cache statistics
     */
    public function get_cache_stats() {
        global $wpdb;
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $valid = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_valid = 1");
        
        return [
            'total' => (int) $total,
            'valid' => (int) $valid,
            'invalid' => (int) ($total - $valid)
        ];
    }
} 