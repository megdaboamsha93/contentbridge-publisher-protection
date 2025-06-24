<?php
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap contentbridge-dashboard">
    <h1><?php esc_html_e('ContentBridge Dashboard', 'contentbridge'); ?></h1>

    <?php if (empty(get_option('contentbridge_api_key'))): ?>
        <div class="notice notice-warning">
            <p>
                <?php 
                printf(
                    __('Please <a href="%s">configure your ContentBridge API key</a> to start protecting and monetizing your content.', 'contentbridge'),
                    admin_url('admin.php?page=contentbridge-settings')
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="contentbridge-stats-grid">
        <div class="stats-card">
            <h3><?php esc_html_e('Monthly Revenue', 'contentbridge'); ?></h3>
            <div class="stats-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
            <div class="stats-label"><?php esc_html_e('Last 30 days', 'contentbridge'); ?></div>
        </div>

        <div class="stats-card">
            <h3><?php esc_html_e('Total Requests', 'contentbridge'); ?></h3>
            <div class="stats-value"><?php echo number_format($stats['total_requests']); ?></div>
            <div class="stats-label"><?php esc_html_e('Last 30 days', 'contentbridge'); ?></div>
        </div>

        <div class="stats-card">
            <h3><?php esc_html_e('Average Revenue', 'contentbridge'); ?></h3>
            <div class="stats-value">
                $<?php echo $stats['total_requests'] ? number_format($stats['total_revenue'] / $stats['total_requests'], 3) : '0.000'; ?>
            </div>
            <div class="stats-label"><?php esc_html_e('Per request', 'contentbridge'); ?></div>
        </div>
    </div>

    <div class="contentbridge-chart-container">
        <h2><?php esc_html_e('Revenue Over Time', 'contentbridge'); ?></h2>
        <canvas id="revenueChart"></canvas>
    </div>

    <div class="contentbridge-tables-grid">
        <div class="table-section">
            <h2><?php esc_html_e('Top Performing Content', 'contentbridge'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Content', 'contentbridge'); ?></th>
                        <th><?php esc_html_e('Requests', 'contentbridge'); ?></th>
                        <th><?php esc_html_e('Revenue', 'contentbridge'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['top_content'] as $content): ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($content['post_id']); ?>">
                                    <?php echo esc_html($content['post_title']); ?>
                                </a>
                            </td>
                            <td><?php echo number_format($content['request_count']); ?></td>
                            <td>$<?php echo number_format($content['revenue'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-section">
            <h2><?php esc_html_e('Revenue by AI Company', 'contentbridge'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Company', 'contentbridge'); ?></th>
                        <th><?php esc_html_e('Requests', 'contentbridge'); ?></th>
                        <th><?php esc_html_e('Revenue', 'contentbridge'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['revenue_by_company'] as $company): ?>
                        <tr>
                            <td><?php echo esc_html($company['ai_company_id']); ?></td>
                            <td><?php echo number_format($company['request_count']); ?></td>
                            <td>$<?php echo number_format($company['revenue'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var ctx = document.getElementById('revenueChart').getContext('2d');
    var chartData = <?php echo wp_json_encode($chart_data); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(function(item) { return item.date; }),
            datasets: [{
                label: '<?php esc_html_e('Revenue ($)', 'contentbridge'); ?>',
                data: chartData.map(function(item) { return item.revenue; }),
                borderColor: '#2271b1',
                backgroundColor: 'rgba(34, 113, 177, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.raw.toFixed(2);
                        }
                    }
                }
            }
        }
    });
});
</script>

<style>
.contentbridge-dashboard {
    max-width: 1200px;
    margin: 20px auto;
}

.contentbridge-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stats-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stats-card h3 {
    margin: 0 0 10px;
    color: #23282d;
}

.stats-value {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.stats-label {
    color: #646970;
    font-size: 12px;
    margin-top: 5px;
}

.contentbridge-chart-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 20px 0;
    height: 400px;
}

.contentbridge-tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.table-section {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table-section h2 {
    margin-top: 0;
}

@media screen and (max-width: 782px) {
    .contentbridge-tables-grid {
        grid-template-columns: 1fr;
    }
}
</style> 