<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_client = new ContentBridge\APIClient();
$analytics = new ContentBridge\Analytics();

// Get analytics data for the last 30 days
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$analytics_data = $analytics->get_analytics_data(new WP_REST_Request('GET', '/contentbridge/v1/analytics'));

// Get earnings data
$earnings = $api_client->get_earnings('month');
?>

<div class="wrap contentbridge-dashboard">
    <h1><?php _e('ContentBridge Dashboard', 'contentbridge'); ?></h1>
    
    <?php if (!get_option('contentbridge_api_key')): ?>
        <div class="notice notice-warning">
            <p>
                <?php _e('Please configure your ContentBridge API key in the settings to start protecting your content.', 'contentbridge'); ?>
                <a href="<?php echo admin_url('admin.php?page=contentbridge-settings'); ?>" class="button button-primary">
                    <?php _e('Configure Settings', 'contentbridge'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="contentbridge-dashboard-grid">
        <!-- Overview Cards -->
        <div class="overview-cards">
            <div class="card">
                <h3><?php _e('Protected Content', 'contentbridge'); ?></h3>
                <div class="card-content">
                    <span class="number"><?php echo esc_html($analytics_data['local']['total_views'] ?? 0); ?></span>
                    <span class="label"><?php _e('Views This Month', 'contentbridge'); ?></span>
                </div>
            </div>
            
            <div class="card">
                <h3><?php _e('Unique Visitors', 'contentbridge'); ?></h3>
                <div class="card-content">
                    <span class="number"><?php echo esc_html($analytics_data['local']['unique_visitors'] ?? 0); ?></span>
                    <span class="label"><?php _e('This Month', 'contentbridge'); ?></span>
                </div>
            </div>
            
            <div class="card">
                <h3><?php _e('Earnings', 'contentbridge'); ?></h3>
                <div class="card-content">
                    <span class="number">$<?php echo number_format($earnings['total'] ?? 0, 2); ?></span>
                    <span class="label"><?php _e('This Month', 'contentbridge'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Popular Content -->
        <div class="content-table">
            <h2><?php _e('Popular Protected Content', 'contentbridge'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'contentbridge'); ?></th>
                        <th><?php _e('Views', 'contentbridge'); ?></th>
                        <th><?php _e('Earnings', 'contentbridge'); ?></th>
                        <th><?php _e('Actions', 'contentbridge'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($analytics_data['local']['popular_posts'])): ?>
                        <?php foreach ($analytics_data['local']['popular_posts'] as $post): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_permalink($post['id']); ?>" target="_blank">
                                        <?php echo esc_html($post['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($post['views']); ?></td>
                                <td>$<?php echo number_format($post['earnings'] ?? 0, 2); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($post['id']); ?>" class="button button-small">
                                        <?php _e('Edit', 'contentbridge'); ?>
                                    </a>
                                    <a href="#" class="button button-small view-metrics" data-post-id="<?php echo esc_attr($post['id']); ?>">
                                        <?php _e('View Metrics', 'contentbridge'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4"><?php _e('No data available yet.', 'contentbridge'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Charts -->
        <div class="analytics-charts">
            <div class="chart-container">
                <h2><?php _e('Views Over Time', 'contentbridge'); ?></h2>
                <canvas id="viewsChart"></canvas>
            </div>
            
            <div class="chart-container">
                <h2><?php _e('Earnings Over Time', 'contentbridge'); ?></h2>
                <canvas id="earningsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<style>
.contentbridge-dashboard {
    margin: 20px;
}

.contentbridge-dashboard-grid {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
    font-size: 14px;
    font-weight: 600;
}

.card-content {
    text-align: center;
}

.card-content .number {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #2271b1;
    margin-bottom: 5px;
}

.card-content .label {
    color: #646970;
    font-size: 13px;
}

.content-table {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.content-table h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

.analytics-charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.chart-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.chart-container h2 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 16px;
}

@media screen and (max-width: 782px) {
    .analytics-charts {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize charts if Chart.js is loaded
    if (typeof Chart !== 'undefined') {
        // Views chart
        const viewsCtx = document.getElementById('viewsChart').getContext('2d');
        new Chart(viewsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($day) {
                    return date('M j', strtotime($day));
                }, array_keys($analytics_data['platform']['daily_views'] ?? []))); ?>,
                datasets: [{
                    label: '<?php _e('Daily Views', 'contentbridge'); ?>',
                    data: <?php echo json_encode(array_values($analytics_data['platform']['daily_views'] ?? [])); ?>,
                    borderColor: '#2271b1',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Earnings chart
        const earningsCtx = document.getElementById('earningsChart').getContext('2d');
        new Chart(earningsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($day) {
                    return date('M j', strtotime($day));
                }, array_keys($analytics_data['platform']['daily_earnings'] ?? []))); ?>,
                datasets: [{
                    label: '<?php _e('Daily Earnings ($)', 'contentbridge'); ?>',
                    data: <?php echo json_encode(array_values($analytics_data['platform']['daily_earnings'] ?? [])); ?>,
                    borderColor: '#00a32a',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    
    // Handle view metrics button click
    $('.view-metrics').on('click', function(e) {
        e.preventDefault();
        const postId = $(this).data('post-id');
        
        // Show loading state
        $(this).addClass('disabled').text('<?php _e('Loading...', 'contentbridge'); ?>');
        
        // Fetch metrics data
        $.get(contentbridgeAdmin.apiUrl + '/content/' + postId + '/metrics', function(response) {
            // Create and show modal with metrics
            const modal = $('<div class="contentbridge-modal"></div>').appendTo('body');
            modal.html(`
                <div class="contentbridge-modal-content">
                    <h2><?php _e('Content Metrics', 'contentbridge'); ?></h2>
                    <div class="metrics-grid">
                        <div class="metric">
                            <span class="label"><?php _e('Total Views', 'contentbridge'); ?></span>
                            <span class="value">${response.total_views}</span>
                        </div>
                        <div class="metric">
                            <span class="label"><?php _e('Total Earnings', 'contentbridge'); ?></span>
                            <span class="value">$${response.total_earnings.toFixed(2)}</span>
                        </div>
                        <div class="metric">
                            <span class="label"><?php _e('Average Time on Page', 'contentbridge'); ?></span>
                            <span class="value">${response.avg_time_on_page}s</span>
                        </div>
                    </div>
                    <button class="button close-modal"><?php _e('Close', 'contentbridge'); ?></button>
                </div>
            `);
            
            // Handle modal close
            modal.find('.close-modal').on('click', function() {
                modal.remove();
            });
            
            // Reset button state
            $('.view-metrics[data-post-id="' + postId + '"]')
                .removeClass('disabled')
                .text('<?php _e('View Metrics', 'contentbridge'); ?>');
        });
    });
});
</script>

<style>
.contentbridge-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.contentbridge-modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.metric {
    text-align: center;
}

.metric .label {
    display: block;
    color: #646970;
    margin-bottom: 5px;
}

.metric .value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #2271b1;
}

.close-modal {
    display: block;
    margin: 20px auto 0;
}
</style> 