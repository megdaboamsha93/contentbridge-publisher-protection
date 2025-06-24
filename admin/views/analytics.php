<?php
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap contentbridge-analytics">
    <h1><?php esc_html_e('ContentBridge Analytics', 'contentbridge'); ?></h1>

    <div class="analytics-period-selector">
        <form method="get">
            <input type="hidden" name="page" value="contentbridge-analytics">
            <select name="period" onchange="this.form.submit()">
                <option value="today" <?php selected($period, 'today'); ?>>
                    <?php esc_html_e('Today', 'contentbridge'); ?>
                </option>
                <option value="week" <?php selected($period, 'week'); ?>>
                    <?php esc_html_e('Last 7 Days', 'contentbridge'); ?>
                </option>
                <option value="month" <?php selected($period, 'month'); ?>>
                    <?php esc_html_e('Last 30 Days', 'contentbridge'); ?>
                </option>
                <option value="year" <?php selected($period, 'year'); ?>>
                    <?php esc_html_e('Last 12 Months', 'contentbridge'); ?>
                </option>
            </select>
        </form>
    </div>

    <div class="analytics-grid">
        <div class="analytics-main">
            <div class="analytics-chart-container">
                <h2><?php esc_html_e('Revenue Over Time', 'contentbridge'); ?></h2>
                <canvas id="revenueChart"></canvas>
            </div>

            <div class="analytics-tables">
                <div class="table-section">
                    <h2><?php esc_html_e('Top Performing Content', 'contentbridge'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Content', 'contentbridge'); ?></th>
                                <th><?php esc_html_e('Requests', 'contentbridge'); ?></th>
                                <th><?php esc_html_e('Revenue', 'contentbridge'); ?></th>
                                <th><?php esc_html_e('Avg. Revenue/Request', 'contentbridge'); ?></th>
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
                                    <td>
                                        $<?php echo number_format($content['revenue'] / $content['request_count'], 3); ?>
                                    </td>
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
                                <th><?php esc_html_e('Avg. Revenue/Request', 'contentbridge'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['revenue_by_company'] as $company): ?>
                                <tr>
                                    <td><?php echo esc_html($company['ai_company_id']); ?></td>
                                    <td><?php echo number_format($company['request_count']); ?></td>
                                    <td>$<?php echo number_format($company['revenue'], 2); ?></td>
                                    <td>
                                        $<?php echo number_format($company['revenue'] / $company['request_count'], 3); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="analytics-sidebar">
            <div class="analytics-summary">
                <h2><?php esc_html_e('Summary', 'contentbridge'); ?></h2>
                <div class="summary-stats">
                    <div class="stat-card">
                        <h3><?php esc_html_e('Total Revenue', 'contentbridge'); ?></h3>
                        <div class="stat-value">
                            $<?php echo number_format($stats['total_revenue'], 2); ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <h3><?php esc_html_e('Total Requests', 'contentbridge'); ?></h3>
                        <div class="stat-value">
                            <?php echo number_format($stats['total_requests']); ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <h3><?php esc_html_e('Average Revenue', 'contentbridge'); ?></h3>
                        <div class="stat-value">
                            $<?php 
                            echo $stats['total_requests'] 
                                ? number_format($stats['total_revenue'] / $stats['total_requests'], 3) 
                                : '0.000'; 
                            ?>
                            <span class="stat-label"><?php esc_html_e('per request', 'contentbridge'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="analytics-export">
                <h2><?php esc_html_e('Export Data', 'contentbridge'); ?></h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="contentbridge_export_analytics">
                    <input type="hidden" name="period" value="<?php echo esc_attr($period); ?>">
                    <?php wp_nonce_field('contentbridge_export_analytics'); ?>
                    
                    <p>
                        <label>
                            <input type="checkbox" name="include_content" value="1" checked>
                            <?php esc_html_e('Include content details', 'contentbridge'); ?>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="include_companies" value="1" checked>
                            <?php esc_html_e('Include company details', 'contentbridge'); ?>
                        </label>
                    </p>
                    
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Export CSV', 'contentbridge'); ?>
                    </button>
                </form>
            </div>
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
            datasets: [
                {
                    label: '<?php esc_html_e('Revenue ($)', 'contentbridge'); ?>',
                    data: chartData.map(function(item) { return item.revenue; }),
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: '<?php esc_html_e('Requests', 'contentbridge'); ?>',
                    data: chartData.map(function(item) { return item.requests; }),
                    borderColor: '#46b450',
                    backgroundColor: 'rgba(70, 180, 80, 0.1)',
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.yAxisID === 'y') {
                                return '$' + context.raw.toFixed(2);
                            }
                            return context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>

<style>
.contentbridge-analytics {
    max-width: 1200px;
    margin: 20px auto;
}

.analytics-period-selector {
    margin: 20px 0;
    text-align: right;
}

.analytics-grid {
    display: grid;
    grid-template-columns: 3fr 1fr;
    gap: 20px;
}

.analytics-main {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.analytics-chart-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: 400px;
}

.analytics-tables {
    display: flex;
    flex-direction: column;
    gap: 20px;
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

.analytics-sidebar > div {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.summary-stats {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.stat-card {
    padding: 15px;
    border-radius: 6px;
    background: #f8f9fa;
}

.stat-card h3 {
    margin: 0 0 10px;
    font-size: 14px;
    color: #646970;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.stat-label {
    font-size: 12px;
    color: #646970;
    font-weight: normal;
    margin-left: 5px;
}

.analytics-export {
    margin-top: 20px;
}

.analytics-export form {
    margin-top: 15px;
}

.analytics-export label {
    display: block;
    margin-bottom: 10px;
}

@media screen and (max-width: 782px) {
    .analytics-grid {
        grid-template-columns: 1fr;
    }
}
</style> 