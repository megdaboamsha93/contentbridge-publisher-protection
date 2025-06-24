<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_client = new ContentBridge\APIClient();
$analytics = new ContentBridge\Analytics();

// Get date range from request or use default
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

// Get analytics data
$analytics_data = $analytics->get_analytics_data(new WP_REST_Request('GET', '/contentbridge/v1/analytics'));

// Get earnings data
$earnings = $api_client->get_earnings('month');
?>

<div class="wrap contentbridge-analytics">
    <h1><?php _e('ContentBridge Analytics', 'contentbridge'); ?></h1>
    
    <!-- Date Range Filter -->
    <div class="date-range-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="contentbridge-analytics">
            <label for="start_date"><?php _e('Start Date:', 'contentbridge'); ?></label>
            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            
            <label for="end_date"><?php _e('End Date:', 'contentbridge'); ?></label>
            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            
            <button type="submit" class="button"><?php _e('Filter', 'contentbridge'); ?></button>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'contentbridge-analytics'])); ?>" class="button">
                <?php _e('Reset', 'contentbridge'); ?>
            </a>
        </form>
    </div>
    
    <!-- Overview Cards -->
    <div class="analytics-overview">
        <div class="card">
            <h3><?php _e('Total Views', 'contentbridge'); ?></h3>
            <div class="card-content">
                <span class="number"><?php echo number_format($analytics_data['local']['total_views'] ?? 0); ?></span>
                <?php
                $prev_views = $analytics_data['platform']['previous_period']['views'] ?? 0;
                $curr_views = $analytics_data['platform']['current_period']['views'] ?? 0;
                $views_change = $prev_views ? (($curr_views - $prev_views) / $prev_views) * 100 : 0;
                ?>
                <span class="trend <?php echo $views_change >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $views_change >= 0 ? '↑' : '↓'; ?> <?php echo abs(round($views_change, 1)); ?>%
                </span>
            </div>
        </div>
        
        <div class="card">
            <h3><?php _e('Total Earnings', 'contentbridge'); ?></h3>
            <div class="card-content">
                <span class="number">$<?php echo number_format($earnings['total'] ?? 0, 2); ?></span>
                <?php
                $prev_earnings = $analytics_data['platform']['previous_period']['earnings'] ?? 0;
                $curr_earnings = $analytics_data['platform']['current_period']['earnings'] ?? 0;
                $earnings_change = $prev_earnings ? (($curr_earnings - $prev_earnings) / $prev_earnings) * 100 : 0;
                ?>
                <span class="trend <?php echo $earnings_change >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $earnings_change >= 0 ? '↑' : '↓'; ?> <?php echo abs(round($earnings_change, 1)); ?>%
                </span>
            </div>
        </div>
        
        <div class="card">
            <h3><?php _e('Unique Visitors', 'contentbridge'); ?></h3>
            <div class="card-content">
                <span class="number"><?php echo number_format($analytics_data['local']['unique_visitors'] ?? 0); ?></span>
                <?php
                $prev_visitors = $analytics_data['platform']['previous_period']['visitors'] ?? 0;
                $curr_visitors = $analytics_data['platform']['current_period']['visitors'] ?? 0;
                $visitors_change = $prev_visitors ? (($curr_visitors - $prev_visitors) / $prev_visitors) * 100 : 0;
                ?>
                <span class="trend <?php echo $visitors_change >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $visitors_change >= 0 ? '↑' : '↓'; ?> <?php echo abs(round($visitors_change, 1)); ?>%
                </span>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="analytics-charts">
        <div class="chart-container">
            <h2><?php _e('Views & Earnings Over Time', 'contentbridge'); ?></h2>
            <canvas id="performanceChart"></canvas>
        </div>
        
        <div class="chart-container">
            <h2><?php _e('Top Performing Content', 'contentbridge'); ?></h2>
            <canvas id="contentChart"></canvas>
        </div>
    </div>
    
    <!-- Detailed Stats Table -->
    <div class="analytics-table">
        <h2><?php _e('Content Performance', 'contentbridge'); ?></h2>
        <div class="tablenav top">
            <div class="alignleft actions">
                <a href="<?php echo esc_url(add_query_arg([
                    'action' => 'export',
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    '_wpnonce' => wp_create_nonce('contentbridge_export')
                ])); ?>" class="button">
                    <?php _e('Export Data', 'contentbridge'); ?>
                </a>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Title', 'contentbridge'); ?></th>
                    <th><?php _e('Views', 'contentbridge'); ?></th>
                    <th><?php _e('Unique Visitors', 'contentbridge'); ?></th>
                    <th><?php _e('Earnings', 'contentbridge'); ?></th>
                    <th><?php _e('Avg. Time on Page', 'contentbridge'); ?></th>
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
                            <td><?php echo number_format($post['views']); ?></td>
                            <td><?php echo number_format($post['unique_visitors'] ?? 0); ?></td>
                            <td>$<?php echo number_format($post['earnings'] ?? 0, 2); ?></td>
                            <td><?php echo round($post['avg_time'] ?? 0); ?>s</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5"><?php _e('No data available for the selected period.', 'contentbridge'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.contentbridge-analytics {
    margin: 20px;
}

.date-range-filter {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.date-range-filter form {
    display: flex;
    align-items: center;
    gap: 15px;
}

.date-range-filter label {
    font-weight: 500;
}

.analytics-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
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

.trend {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.trend.positive {
    background: #e6f4ea;
    color: #137333;
}

.trend.negative {
    background: #fce8e6;
    color: #c5221f;
}

.analytics-charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
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

.analytics-table {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.analytics-table h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

@media screen and (max-width: 782px) {
    .date-range-filter form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .analytics-charts {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    if (typeof Chart !== 'undefined') {
        // Performance Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($day) {
                    return date('M j', strtotime($day));
                }, array_keys($analytics_data['platform']['daily_views'] ?? []))); ?>,
                datasets: [
                    {
                        label: '<?php _e('Views', 'contentbridge'); ?>',
                        data: <?php echo json_encode(array_values($analytics_data['platform']['daily_views'] ?? [])); ?>,
                        borderColor: '#2271b1',
                        yAxisID: 'y-views'
                    },
                    {
                        label: '<?php _e('Earnings ($)', 'contentbridge'); ?>',
                        data: <?php echo json_encode(array_values($analytics_data['platform']['daily_earnings'] ?? [])); ?>,
                        borderColor: '#00a32a',
                        yAxisID: 'y-earnings'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    'y-views': {
                        type: 'linear',
                        position: 'left'
                    },
                    'y-earnings': {
                        type: 'linear',
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        
        // Content Performance Chart
        const contentCtx = document.getElementById('contentChart').getContext('2d');
        new Chart(contentCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($post) {
                    return wp_trim_words($post['title'], 3);
                }, array_slice($analytics_data['local']['popular_posts'] ?? [], 0, 10))); ?>,
                datasets: [
                    {
                        label: '<?php _e('Views', 'contentbridge'); ?>',
                        data: <?php echo json_encode(array_map(function($post) {
                            return $post['views'];
                        }, array_slice($analytics_data['local']['popular_posts'] ?? [], 0, 10))); ?>,
                        backgroundColor: '#2271b1'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});</script> 