<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="contentbridge-access-denied">
    <style>
        .contentbridge-access-denied {
            max-width: 800px;
            margin: 2em auto;
            padding: 2em;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        .contentbridge-access-denied h2 {
            color: #1a1a1a;
            font-size: 24px;
            margin-bottom: 1em;
            font-weight: 600;
        }
        
        .contentbridge-access-denied p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 1.5em;
        }
        
        .contentbridge-access-denied .icon {
            margin-bottom: 1.5em;
        }
        
        .contentbridge-access-denied .icon svg {
            width: 64px;
            height: 64px;
            fill: #4a90e2;
        }
        
        .contentbridge-access-denied .cta-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4a90e2;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        
        .contentbridge-access-denied .cta-button:hover {
            background-color: #357abd;
            color: #fff;
        }
        
        .contentbridge-access-denied .footer-text {
            margin-top: 2em;
            font-size: 14px;
            color: #999;
        }
    </style>
    
    <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M12 1C8.676 1 6 3.676 6 7v3H4v12h16V10h-2V7c0-3.324-2.676-6-6-6zm0 2c2.276 0 4 1.724 4 4v3H8V7c0-2.276 1.724-4 4-4z"/>
        </svg>
    </div>
    
    <h2><?php _e('Protected Content', 'contentbridge'); ?></h2>
    
    <p>
        <?php _e('This content is protected and available exclusively through the ContentBridge marketplace.', 'contentbridge'); ?>
    </p>
    
    <p>
        <?php _e('To access this content, please purchase it through our marketplace platform.', 'contentbridge'); ?>
    </p>
    
    <p>
        <a href="<?php echo esc_url(add_query_arg([
            'content_id' => get_the_ID(),
            'site' => get_site_url()
        ], get_option('contentbridge_marketplace_url', 'https://market.contentbridge.com'))); ?>" 
           class="cta-button">
            <?php _e('Get Access', 'contentbridge'); ?>
        </a>
    </p>
    
    <p class="footer-text">
        <?php _e('Protected by ContentBridge Publisher Protection', 'contentbridge'); ?>
    </p>
</div> 