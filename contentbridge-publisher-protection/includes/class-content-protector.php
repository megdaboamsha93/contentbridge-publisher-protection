<?php
namespace ContentBridge;

class ContentProtector {
    private $token_validator;
    private $api_client;
    
    public function __construct() {
        $this->token_validator = new TokenValidator();
        $this->api_client = new APIClient();
        
        // Add filters for content protection
        add_filter('the_content', [$this, 'protect_content'], 999);
        add_filter('the_excerpt', [$this, 'protect_excerpt'], 999);
        add_action('template_redirect', [$this, 'protect_feed']);
        add_action('rest_api_init', [$this, 'protect_rest_api']);
        
        // Add headers to prevent AI crawling
        add_action('send_headers', [$this, 'add_protection_headers']);
    }
    
    /**
     * Protect post content based on settings and token validation
     */
    public function protect_content($content) {
        if (!$this->should_protect_content()) {
            return $content;
        }
        
        if ($this->validate_access()) {
            return $content;
        }
        
        return $this->get_protected_content_message();
    }
    
    /**
     * Protect excerpt with a shorter version of the protection
     */
    public function protect_excerpt($excerpt) {
        if (!$this->should_protect_content()) {
            return $excerpt;
        }
        
        if ($this->validate_access()) {
            return $excerpt;
        }
        
        return $this->get_protected_excerpt_message();
    }
    
    /**
     * Protect RSS/Atom feeds
     */
    public function protect_feed() {
        if (is_feed() && get_option('contentbridge_protect_feeds', true)) {
            die($this->get_protected_feed_message());
        }
    }
    
    /**
     * Protect REST API endpoints
     */
    public function protect_rest_api() {
        if (!get_option('contentbridge_protect_api', true)) {
            return;
        }
        
        register_rest_field('post', 'content', [
            'get_callback' => function($post) {
                if ($this->validate_access()) {
                    return $post['content']['rendered'];
                }
                return $this->get_protected_content_message();
            }
        ]);
    }
    
    /**
     * Add protection headers to prevent AI crawling
     */
    public function add_protection_headers() {
        if (!get_option('contentbridge_add_headers', true)) {
            return;
        }
        
        header('X-Robots-Tag: noai, noimageai');
        header('X-ContentBridge-Protected: true');
        header('Permission-Policy: browsing-topics=()');
    }
    
    /**
     * Check if current content should be protected
     */
    private function should_protect_content() {
        if (!is_singular()) {
            return false;
        }
        
        $post_type = get_post_type();
        $protected_types = get_option('contentbridge_protected_types', ['post']);
        
        if (!in_array($post_type, $protected_types)) {
            return false;
        }
        
        // Check for category and tag protection rules
        $protected_categories = get_option('contentbridge_protected_categories', []);
        $protected_tags = get_option('contentbridge_protected_tags', []);
        
        if (!empty($protected_categories)) {
            if (has_category($protected_categories)) {
                return true;
            }
        }
        
        if (!empty($protected_tags)) {
            if (has_tag($protected_tags)) {
                return true;
            }
        }
        
        return true;
    }
    
    /**
     * Validate access token and permissions
     */
    private function validate_access() {
        $token = $this->get_access_token();
        
        if (!$token) {
            return false;
        }
        
        return $this->token_validator->validate($token);
    }
    
    /**
     * Get access token from various sources
     */
    private function get_access_token() {
        // Check URL parameter
        $token = filter_input(INPUT_GET, 'cb_token');
        if ($token) {
            return $token;
        }
        
        // Check cookie
        if (isset($_COOKIE['cb_access_token'])) {
            return sanitize_text_field($_COOKIE['cb_access_token']);
        }
        
        // Check Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Get protected content message
     */
    private function get_protected_content_message() {
        ob_start();
        include CONTENTBRIDGE_PLUGIN_DIR . 'templates/access-denied.php';
        return ob_get_clean();
    }
    
    /**
     * Get protected excerpt message
     */
    private function get_protected_excerpt_message() {
        return sprintf(
            __('This content is protected. %sGet access%s through ContentBridge marketplace.', 'contentbridge'),
            '<a href="' . esc_url($this->get_marketplace_url()) . '">',
            '</a>'
        );
    }
    
    /**
     * Get protected feed message
     */
    private function get_protected_feed_message() {
        return __('This feed is protected by ContentBridge Publisher Protection. Please access content through our website.', 'contentbridge');
    }
    
    /**
     * Get marketplace URL for the current content
     */
    private function get_marketplace_url() {
        $base_url = $this->api_client->get_marketplace_url();
        return add_query_arg([
            'content_id' => get_the_ID(),
            'site' => get_site_url()
        ], $base_url);
    }
} 