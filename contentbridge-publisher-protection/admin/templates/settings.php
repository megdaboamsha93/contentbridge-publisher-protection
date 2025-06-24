<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_client = new ContentBridge\APIClient();
?>

<div class="wrap contentbridge-settings">
    <h1><?php _e('ContentBridge Settings', 'contentbridge'); ?></h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php" class="contentbridge-settings-form">
        <?php settings_fields('contentbridge_settings'); ?>
        
        <div class="settings-grid">
            <!-- API Settings -->
            <div class="settings-section">
                <h2><?php _e('API Settings', 'contentbridge'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="contentbridge_api_key"><?php _e('API Key', 'contentbridge'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="contentbridge_api_key" 
                                   name="contentbridge_api_key" 
                                   value="<?php echo esc_attr(get_option('contentbridge_api_key')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Enter your ContentBridge API key. You can find this in your ContentBridge account settings.', 'contentbridge'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contentbridge_api_url"><?php _e('API URL', 'contentbridge'); ?></label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="contentbridge_api_url" 
                                   name="contentbridge_api_url" 
                                   value="<?php echo esc_attr(get_option('contentbridge_api_url', 'https://api.contentbridge.com/v1')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contentbridge_marketplace_url"><?php _e('Marketplace URL', 'contentbridge'); ?></label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="contentbridge_marketplace_url" 
                                   name="contentbridge_marketplace_url" 
                                   value="<?php echo esc_attr(get_option('contentbridge_marketplace_url', 'https://market.contentbridge.com')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Protection Settings -->
            <div class="settings-section">
                <h2><?php _e('Protection Settings', 'contentbridge'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="contentbridge_protected_types"><?php _e('Protected Post Types', 'contentbridge'); ?></label>
                        </th>
                        <td>
                            <?php
                            $post_types = get_post_types(['public' => true], 'objects');
                            $protected_types = get_option('contentbridge_protected_types', ['post']);
                            foreach ($post_types as $post_type): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="contentbridge_protected_types[]" 
                                           value="<?php echo esc_attr($post_type->name); ?>"
                                           <?php checked(in_array($post_type->name, $protected_types)); ?>>
                                    <?php echo esc_html($post_type->label); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contentbridge_protected_categories"><?php _e('Protected Categories', 'contentbridge'); ?></label>
                        </th>
                        <td>
                            <?php
                            $categories = get_categories(['hide_empty' => false]);
                            $protected_categories = get_option('contentbridge_protected_categories', []);
                            foreach ($categories as $category): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="contentbridge_protected_categories[]" 
                                           value="<?php echo esc_attr($category->term_id); ?>"
                                           <?php checked(in_array($category->term_id, $protected_categories)); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contentbridge_protected_tags"><?php _e('Protected Tags', 'contentbridge'); ?></label>
                        </th>
                        <td>
                            <?php
                            $tags = get_tags(['hide_empty' => false]);
                            $protected_tags = get_option('contentbridge_protected_tags', []);
                            foreach ($tags as $tag): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" 
                                           name="contentbridge_protected_tags[]" 
                                           value="<?php echo esc_attr($tag->term_id); ?>"
                                           <?php checked(in_array($tag->term_id, $protected_tags)); ?>>
                                    <?php echo esc_html($tag->name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Advanced Settings -->
            <div class="settings-section">
                <h2><?php _e('Advanced Settings', 'contentbridge'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="contentbridge_token_cache_expiration"><?php _e('Token Cache Expiration', 'contentbridge'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="contentbridge_token_cache_expiration" 
                                   name="contentbridge_token_cache_expiration" 
                                   value="<?php echo esc_attr(get_option('contentbridge_token_cache_expiration', 3600)); ?>" 
                                   class="small-text">
                            <p class="description">
                                <?php _e('Time in seconds to cache token validation results (default: 3600)', 'contentbridge'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Protection Features', 'contentbridge'); ?></th>
                        <td>
                            <label class="checkbox-label">
                                <input type="checkbox" 
                                       name="contentbridge_protect_feeds" 
                                       value="1"
                                       <?php checked(get_option('contentbridge_protect_feeds', true)); ?>>
                                <?php _e('Protect RSS/Atom Feeds', 'contentbridge'); ?>
                            </label><br>
                            <label class="checkbox-label">
                                <input type="checkbox" 
                                       name="contentbridge_protect_api" 
                                       value="1"
                                       <?php checked(get_option('contentbridge_protect_api', true)); ?>>
                                <?php _e('Protect REST API Endpoints', 'contentbridge'); ?>
                            </label><br>
                            <label class="checkbox-label">
                                <input type="checkbox" 
                                       name="contentbridge_add_headers" 
                                       value="1"
                                       <?php checked(get_option('contentbridge_add_headers', true)); ?>>
                                <?php _e('Add Protection Headers', 'contentbridge'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(); ?>
        
        <div class="settings-actions">
            <button type="button" class="button" id="test-api-connection">
                <?php _e('Test API Connection', 'contentbridge'); ?>
            </button>
            <button type="button" class="button" id="clear-cache">
                <?php _e('Clear Token Cache', 'contentbridge'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.contentbridge-settings {
    margin: 20px;
}

.settings-grid {
    display: grid;
    gap: 30px;
    margin: 20px 0;
}

.settings-section {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.checkbox-label {
    display: inline-block;
    margin: 5px 20px 5px 0;
}

.settings-actions {
    margin-top: 20px;
}

.settings-actions .button {
    margin-right: 10px;
}

@media screen and (min-width: 783px) {
    .settings-grid {
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test API connection
    $('#test-api-connection').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        button.addClass('disabled').text('<?php _e('Testing...', 'contentbridge'); ?>');
        
        $.post(contentbridgeAdmin.apiUrl + '/test-connection', {
            _wpnonce: contentbridgeAdmin.apiNonce
        }, function(response) {
            if (response.success) {
                alert('<?php _e('API connection successful!', 'contentbridge'); ?>');
            } else {
                alert('<?php _e('API connection failed. Please check your settings.', 'contentbridge'); ?>');
            }
        }).always(function() {
            button.removeClass('disabled').text('<?php _e('Test API Connection', 'contentbridge'); ?>');
        });
    });
    
    // Clear token cache
    $('#clear-cache').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php _e('Are you sure you want to clear the token cache?', 'contentbridge'); ?>')) {
            return;
        }
        
        const button = $(this);
        button.addClass('disabled').text('<?php _e('Clearing...', 'contentbridge'); ?>');
        
        $.post(contentbridgeAdmin.apiUrl + '/clear-cache', {
            _wpnonce: contentbridgeAdmin.apiNonce
        }, function(response) {
            if (response.success) {
                alert('<?php _e('Token cache cleared successfully!', 'contentbridge'); ?>');
            } else {
                alert('<?php _e('Failed to clear token cache.', 'contentbridge'); ?>');
            }
        }).always(function() {
            button.removeClass('disabled').text('<?php _e('Clear Token Cache', 'contentbridge'); ?>');
        });
    });
});</script> 