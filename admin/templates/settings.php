<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings = get_option('contentbridge_settings', array());
$api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
$protection_level = isset($settings['protection_level']) ? $settings['protection_level'] : 'standard';
$cache_duration = isset($settings['cache_duration']) ? $settings['cache_duration'] : 3600;
$protected_post_types = isset($settings['protected_post_types']) ? $settings['protected_post_types'] : array('post', 'page');

// Get all public post types
$post_types = get_post_types(array('public' => true), 'objects');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php">
        <?php settings_fields('contentbridge_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="contentbridge_api_key"><?php _e('API Key', 'contentbridge'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="contentbridge_api_key" 
                           name="contentbridge_settings[api_key]" 
                           value="<?php echo esc_attr($api_key); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php _e('Enter your ContentBridge API key. You can find this in your ContentBridge dashboard.', 'contentbridge'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="contentbridge_protection_level"><?php _e('Protection Level', 'contentbridge'); ?></label>
                </th>
                <td>
                    <select id="contentbridge_protection_level" 
                            name="contentbridge_settings[protection_level]">
                        <option value="standard" <?php selected($protection_level, 'standard'); ?>>
                            <?php _e('Standard', 'contentbridge'); ?>
                        </option>
                        <option value="aggressive" <?php selected($protection_level, 'aggressive'); ?>>
                            <?php _e('Aggressive', 'contentbridge'); ?>
                        </option>
                        <option value="custom" <?php selected($protection_level, 'custom'); ?>>
                            <?php _e('Custom', 'contentbridge'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Choose how strictly you want to protect your content.', 'contentbridge'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="contentbridge_cache_duration"><?php _e('Cache Duration', 'contentbridge'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="contentbridge_cache_duration" 
                           name="contentbridge_settings[cache_duration]" 
                           value="<?php echo esc_attr($cache_duration); ?>" 
                           min="300" 
                           step="300">
                    <p class="description">
                        <?php _e('Duration in seconds to cache token validation results. Minimum 300 seconds.', 'contentbridge'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Protected Post Types', 'contentbridge'); ?></th>
                <td>
                    <?php foreach ($post_types as $post_type): ?>
                        <label>
                            <input type="checkbox" 
                                   name="contentbridge_settings[protected_post_types][]" 
                                   value="<?php echo esc_attr($post_type->name); ?>"
                                   <?php checked(in_array($post_type->name, $protected_post_types)); ?>>
                            <?php echo esc_html($post_type->labels->name); ?>
                        </label><br>
                    <?php endforeach; ?>
                    <p class="description">
                        <?php _e('Select which content types should be protected.', 'contentbridge'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div> 