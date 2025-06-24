<?php
namespace ContentBridge\Tests;

require_once dirname(dirname(__FILE__)) . '/includes/class-installer.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-content-protector.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-api-client.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-token-validator.php';
require_once dirname(dirname(__FILE__)) . '/includes/class-analytics.php';

use ContentBridge\Installer;
use ContentBridge\Content_Protector;
use ContentBridge\API_Client;
use ContentBridge\Token_Validator;
use ContentBridge\Analytics;

class TestCase extends \WP_UnitTestCase {
    protected $content_protector;
    protected $api_client;
    protected $token_validator;
    protected $analytics;
    protected $installer;
    protected $test_post_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Create tables first
        $this->installer = new Installer();
        $this->installer->install();
        
        // Initialize plugin components
        $this->api_client = $this->createMock(API_Client::class);
        $this->token_validator = new Token_Validator();
        $this->analytics = new Analytics();
        $this->content_protector = new Content_Protector();
        
        // Set up test data
        $this->setupTestData();
    }
    
    public function tearDown(): void {
        // Clean up test data
        $this->cleanupTestData();
        parent::tearDown();
    }
    
    protected function setupTestData() {
        // Create a test post
        $this->test_post_id = $this->factory->post->create(array(
            'post_title' => 'Test Post',
            'post_content' => 'Test content that should be protected.',
            'post_status' => 'publish'
        ));
        
        // Set up test settings
        update_option('contentbridge_settings', array(
            'api_key' => 'test_api_key',
            'protection_level' => 'standard',
            'cache_duration' => 3600,
            'protected_post_types' => array('post', 'page')
        ));
        
        // Mock API client responses
        $this->api_client->method('validate_token')
            ->will($this->returnCallback(function($token) {
                if (strpos($token, 'valid_') === 0) {
                    return array(
                        'valid' => true,
                        'message' => '',
                        'data' => array(
                            'user_id' => 1,
                            'expires' => time() + 3600
                        )
                    );
                }
                return array(
                    'valid' => false,
                    'message' => 'Invalid token',
                    'data' => array()
                );
            }));
    }
    
    protected function cleanupTestData() {
        global $wpdb;
        
        // Clean up posts
        wp_delete_post($this->test_post_id, true);
        
        // Clean up plugin tables
        $tables = array(
            'contentbridge_analytics',
            'contentbridge_daily_stats',
            'contentbridge_tokens'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}$table");
        }
        
        // Clean up options
        delete_option('contentbridge_settings');
    }
    
    protected function createTestPost() {
        return $this->factory->post->create(array(
            'post_title' => 'Test Post',
            'post_content' => 'Test content that should be protected.',
            'post_status' => 'publish'
        ));
    }
    
    protected function simulateRequest($post_id) {
        global $post;
        $post = get_post($post_id);
        setup_postdata($post);
        return $post;
    }
    
    protected function mockToken($token, $is_valid = true, $expires = null) {
        if (!$expires) {
            $expires = time() + 3600;
        }
        
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'contentbridge_tokens',
            array(
                'token_hash' => md5($token),
                'validation_data' => json_encode(array(
                    'valid' => $is_valid,
                    'expires' => $expires
                )),
                'expiration' => date('Y-m-d H:i:s', $expires)
            )
        );
    }
    
    public function testApiClientValidateToken() {
        $api_client = new \ContentBridge\API_Client();
        // Use a known invalid token for a predictable response
        $result = $api_client->validate_token('cb_invalid_test_token');
        $this->assertIsArray($result, 'API response should be an array');
        $this->assertArrayHasKey('valid', $result, 'API response should have a valid key');
        $this->assertFalse($result['valid'], 'Invalid token should return valid=false');
    }
} 