<?php
/**
 * Template for displaying access denied message
 *
 * @package ContentBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('contentbridge_settings', array());
$marketplace_url = 'https://contentbridge.com/marketplace';
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('Access Denied', 'contentbridge'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        .contentbridge-access-denied {
            max-width: 800px;
            margin: 100px auto;
            padding: 40px 20px;
            text-align: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        .contentbridge-access-denied h1 {
            font-size: 2.5em;
            color: #1d2327;
            margin-bottom: 20px;
        }
        
        .contentbridge-access-denied p {
            font-size: 1.2em;
            line-height: 1.6;
            color: #50575e;
            margin-bottom: 30px;
        }
        
        .contentbridge-access-denied .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2271b1;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 1.1em;
            transition: background-color 0.2s ease;
        }
        
        .contentbridge-access-denied .button:hover {
            background-color: #135e96;
        }
        
        .contentbridge-access-denied .icon {
            font-size: 4em;
            color: #d63638;
            margin-bottom: 20px;
        }
        
        @media (max-width: 600px) {
            .contentbridge-access-denied {
                margin: 50px auto;
                padding: 20px;
            }
            
            .contentbridge-access-denied h1 {
                font-size: 2em;
            }
            
            .contentbridge-access-denied p {
                font-size: 1.1em;
            }
        }
    </style>
</head>
<body <?php body_class(); ?>>
    <div class="contentbridge-access-denied">
        <div class="icon">🔒</div>
        <h1><?php _e('Protected Content', 'contentbridge'); ?></h1>
        <p>
            <?php _e('This content is protected and requires proper authorization to access.', 'contentbridge'); ?>
        </p>
        <p>
            <?php _e('To access this content, please visit our marketplace to purchase the appropriate license.', 'contentbridge'); ?>
        </p>
        <p>
            <a href="<?php echo esc_url($marketplace_url); ?>" class="button">
                <?php _e('Visit Marketplace', 'contentbridge'); ?>
            </a>
        </p>
    </div>
    <?php wp_footer(); ?>
</body>
</html>

<style>
.contentbridge-access-denied {
    max-width: 600px;
    margin: 2em auto;
    padding: 2em;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.contentbridge-access-denied__inner {
    max-width: 400px;
    margin: 0 auto;
}

.contentbridge-access-denied__icon {
    color: #e74c3c;
    margin-bottom: 1.5em;
}

.contentbridge-access-denied__title {
    font-size: 1.75em;
    color: #2c3e50;
    margin: 0 0 0.5em;
}

.contentbridge-access-denied__message {
    color: #e74c3c;
    font-size: 1.1em;
    margin-bottom: 1.5em;
}

.contentbridge-access-denied__info {
    color: #7f8c8d;
    margin-bottom: 2em;
}

.contentbridge-access-denied__info p {
    margin: 0.5em 0;
}

.contentbridge-access-denied__actions {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1em;
}

.contentbridge-access-denied__button {
    display: inline-block;
    padding: 0.8em 2em;
    background-color: #3498db;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.contentbridge-access-denied__button:hover {
    background-color: #2980b9;
    color: #fff;
    text-decoration: none;
}

.contentbridge-access-denied__link {
    color: #7f8c8d;
    text-decoration: none;
    transition: color 0.2s;
}

.contentbridge-access-denied__link:hover {
    color: #2c3e50;
    text-decoration: underline;
}

@media (max-width: 480px) {
    .contentbridge-access-denied {
        padding: 1.5em;
        margin: 1em;
    }

    .contentbridge-access-denied__title {
        font-size: 1.5em;
    }

    .contentbridge-access-denied__message {
        font-size: 1em;
    }
}
</style>

<?php
// Add schema.org markup for better SEO
$schema = array(
    '@context' => 'http://schema.org',
    '@type' => 'WebPage',
    'name' => __('Protected Content', 'contentbridge'),
    'description' => __('This content is protected by ContentBridge Publisher Protection.', 'contentbridge')
);
?>
<script type="application/ld+json"><?php echo wp_json_encode($schema); ?></script> 