<?php
namespace ContentBridge;

/**
 * Class Content_Protector
 * Handles the core content protection logic
 */
class Content_Protector {
    /**
     * @var Token_Validator
     */
    private $token_validator;

    /**
     * @var API_Client
     */
    private $api_client;

    /**
     * @var Analytics
     */
    private $analytics;

    /**
     * Constructor
     *
     * @param API_Client|null $api_client Optional API client for testing
     */
    public function __construct($api_client = null) {
        $this->api_client = $api_client ?: new API_Client();
        $this->token_validator = new Token_Validator($this->api_client);
        $this->analytics = new Analytics();
    }

    /**
     * Initialize the content protector
     */
    public function init() {
        add_filter('the_content', array($this, 'protect_content'), 999);
        add_filter('the_excerpt', array($this, 'protect_excerpt'), 999);
        add_action('template_redirect', array($this, 'protect_feed'));
        add_action('rest_api_init', array($this, 'protect_rest_api'));
    }

    /**
     * Protect post content
     *
     * @param string $content The post content
     * @return string Modified content
     */
    public function protect_content($content) {
        if (!$this->should_protect_content()) {
            return $content;
        }

        $token = $this->get_access_token();
        if (!$token) {
            return $this->get_access_denied_content();
        }

        $validation = $this->token_validator->validate_token($token);
        if (!$validation['valid']) {
            return $this->get_access_denied_content($validation['message']);
        }

        // Track successful access
        $this->track_content_access($token, $validation['data']);

        return $content;
    }

    /**
     * Protect post excerpt
     *
     * @param string $excerpt The post excerpt
     * @return string Modified excerpt
     */
    public function protect_excerpt($excerpt) {
        if (!$this->should_protect_content()) {
            return $excerpt;
        }

        $token = $this->get_access_token();
        if (!$token || !$this->token_validator->validate_token($token)['valid']) {
            return $this->get_excerpt_preview();
        }

        return $excerpt;
    }

    /**
     * Protect RSS/Atom feeds
     */
    public function protect_feed() {
        if (is_feed() && $this->should_protect_content()) {
            $token = $this->get_access_token();
            if (!$token || !$this->token_validator->validate_token($token)['valid']) {
                die($this->get_access_denied_content());
            }
        }
    }

    /**
     * Protect REST API endpoints
     */
    public function protect_rest_api() {
        register_rest_field(
            $this->get_protected_post_types(),
            'content',
            array(
                'get_callback' => array($this, 'filter_rest_content'),
                'schema' => null,
            )
        );
    }

    /**
     * Filter content in REST API responses
     *
     * @param array $object The object data
     * @return string Modified content
     */
    public function filter_rest_content($object) {
        if (!$this->should_protect_content($object['id'])) {
            return $object['content']['rendered'];
        }

        $token = $this->get_access_token();
        if (!$token || !$this->token_validator->validate_token($token)['valid']) {
            return $this->get_access_denied_content();
        }

        return $object['content']['rendered'];
    }

    /**
     * Check if content should be protected
     *
     * @param int|null $post_id Optional post ID
     * @return bool
     */
    private function should_protect_content($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return false;
        }

        $settings = get_option('contentbridge_settings', array());
        $protected_post_types = isset($settings['protected_post_types']) 
            ? $settings['protected_post_types'] 
            : array('post', 'page');

        // Check if post type is protected
        if (!in_array(get_post_type($post_id), $protected_post_types)) {
            return false;
        }

        // Allow custom protection rules through filter
        return apply_filters('contentbridge_should_protect_content', true, $post_id);
    }

    /**
     * Get access token from request
     *
     * @return string|null
     */
    private function get_access_token() {
        $token = null;

        // Check Authorization header
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $token = $matches[1];
            }
        }

        // Check query parameters
        if (!$token && isset($_GET['access_token'])) {
            $token = $_GET['access_token'];
        }

        return $token;
    }

    /**
     * Get access denied content
     *
     * @param string $message Optional custom message
     * @return string
     */
    private function get_access_denied_content($message = '') {
        $default_message = __('This content is protected. Please provide a valid ContentBridge access token to view it.', 'contentbridge');
        $message = $message ?: $default_message;

        ob_start();
        include(CONTENTBRIDGE_PLUGIN_DIR . 'templates/access-denied.php');
        return ob_get_clean();
    }

    /**
     * Get preview excerpt
     *
     * @return string
     */
    private function get_excerpt_preview() {
        return sprintf(
            '%s... %s',
            wp_trim_words(get_the_excerpt(), 20),
            __('(Protected Content)', 'contentbridge')
        );
    }

    /**
     * Track successful content access
     *
     * @param string $token Access token
     * @param array $token_data Token validation data
     */
    private function track_content_access($token, $token_data) {
        $post_id = get_the_ID();
        
        $this->analytics->track_access(array(
            'post_id' => $post_id,
            'token' => $token,
            'token_data' => $token_data,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'ip_address' => $this->get_client_ip()
        ));

        do_action('contentbridge_content_accessed', $post_id, $token_data);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header]);
                return trim($ip[0]);
            }
        }

        return '';
    }

    /**
     * Get protected post types
     *
     * @return array
     */
    private function get_protected_post_types() {
        $settings = get_option('contentbridge_settings', array());
        return isset($settings['protected_post_types']) 
            ? $settings['protected_post_types'] 
            : array('post', 'page');
    }
} 