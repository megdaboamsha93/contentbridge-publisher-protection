<?php
namespace ContentBridge\Tests;

class ContentProtectorTest extends TestCase {
    public function testContentProtectionEnabled() {
        $post_id = $this->createTestPost();
        $post = $this->simulateRequest($post_id);
        
        // Test through the protect_content method
        $content = 'Test content that should be protected.';
        $protected_content = $this->content_protector->protect_content($content);
        
        $this->assertNotEquals($content, $protected_content, 'Content should be protected by default for posts');
        $this->assertStringContainsString('protected', strtolower($protected_content), 'Protected content should indicate protection');
    }
    
    public function testContentProtectionDisabled() {
        // Update settings to disable protection for posts
        $settings = get_option('contentbridge_settings');
        $settings['protected_post_types'] = array('page');
        update_option('contentbridge_settings', $settings);
        
        $post_id = $this->createTestPost();
        $post = $this->simulateRequest($post_id);
        
        $content = 'Test content that should not be protected.';
        $filtered_content = $this->content_protector->protect_content($content);
        
        $this->assertEquals($content, $filtered_content, 'Content should not be protected when post type is excluded');
    }
    
    public function testValidTokenAccess() {
        $post_id = $this->createTestPost();
        $post = $this->simulateRequest($post_id);
        
        // Mock a valid token
        $token = 'valid_test_token';
        $this->mockToken($token, true);
        
        // Simulate token in Authorization header
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        $content = 'Test content that should be accessible.';
        $filtered_content = $this->content_protector->protect_content($content);
        
        $this->assertEquals($content, $filtered_content, 'Should allow access with valid token');
        
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }
    
    public function testInvalidTokenAccess() {
        $post_id = $this->createTestPost();
        $post = $this->simulateRequest($post_id);
        
        // Mock an invalid token
        $token = 'invalid_test_token';
        $this->mockToken($token, false);
        
        // Simulate token in query parameter
        $_GET['access_token'] = $token;
        
        $content = 'Test content that should be protected.';
        $filtered_content = $this->content_protector->protect_content($content);
        
        $this->assertNotEquals($content, $filtered_content, 'Should deny access with invalid token');
        $this->assertStringContainsString('protected', strtolower($filtered_content), 'Should show access denied message');
        
        unset($_GET['access_token']);
    }
    
    public function testExcerptProtection() {
        $post_id = $this->createTestPost();
        $post = $this->simulateRequest($post_id);
        
        $excerpt = 'This is a test excerpt that should be protected.';
        $filtered_excerpt = $this->content_protector->protect_excerpt($excerpt);
        
        $this->assertNotEquals($excerpt, $filtered_excerpt, 'Excerpt should be protected without valid token');
    }
    
    public function testRestApiProtection() {
        $post_id = $this->createTestPost();
        $post = $this->simulateRequest($post_id);
        
        $object = array(
            'id' => $post_id,
            'content' => array(
                'rendered' => 'Test content for REST API'
            )
        );
        
        $filtered_content = $this->content_protector->filter_rest_content($object);
        
        $this->assertNotEquals($object['content']['rendered'], $filtered_content, 'REST API content should be protected');
        $this->assertStringContainsString('protected', strtolower($filtered_content), 'Should show access denied message');
    }
    
    public function testAnalyticsTracking() {
        $post_id = $this->createTestPost();
        $post = $this->simulateRequest($post_id);
        
        // Mock a valid token
        $token = 'test_token_for_analytics';
        $this->mockToken($token, true);
        
        // Simulate token access
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        $content = 'Test content for analytics tracking.';
        $this->content_protector->protect_content($content);
        
        // Verify analytics entry was created
        global $wpdb;
        $analytics_entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}contentbridge_analytics WHERE post_id = %d",
                $post_id
            )
        );
        
        $this->assertNotNull($analytics_entry, 'Analytics entry should be created for content access');
        
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }
} 