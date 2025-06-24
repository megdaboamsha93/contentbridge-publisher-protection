<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get analytics data
$analytics = new ContentBridge\Analytics();
$current_month = date('Y-m');
$monthly_stats = $analytics->get_monthly_stats($current_month);
$top_content = $analytics->get_top_content(10);
$revenue_data = $analytics->get_revenue_data($current_month);
?>

<div class="wrap contentbridge-analytics">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="analytics-header">
        <div class="date-range-selector">
            <select id="analytics-month">
                <?php
                for ($i = 0; $i < 12; $i++) {
                    $date = date('Y-m', strtotime("-$i months"));
                    $selected = ($date === $current_month) ? 'selected' : '';
                    echo sprintf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($date),
                        $selected,
                        esc_html(date('F Y', strtotime($date)))
                    );
                }
                ?>
            </select>
        </div>
        
        <div class="export-button">
            <button class="button button-primary" id="export-analytics">
                <?php _e('Export Data', 'contentbridge'); ?>
            </button>
        </div>
    </div>
    
    <div class="analytics-grid">
        <!-- Summary Cards -->
        <div class="analytics-card">
            <h3><?php _e('Total Views', 'contentbridge'); ?></h3>
            <div class="stat-value"><?php echo number_format($monthly_stats['total_views']); ?></div>
            <div class="stat-change <?php echo $monthly_stats['views_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo sprintf('%+d%%', $monthly_stats['views_change']); ?>
            </div>
        </div>
        
        <div class="analytics-card">
            <h3><?php _e('Total Revenue', 'contentbridge'); ?></h3>
            <div class="stat-value">$<?php echo number_format($monthly_stats['total_revenue'], 2); ?></div>
            <div class="stat-change <?php echo $monthly_stats['revenue_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo sprintf('%+d%%', $monthly_stats['revenue_change']); ?>
            </div>
        </div>
        
        <div class="analytics-card">
            <h3><?php _e('Unique Visitors', 'contentbridge'); ?></h3>
            <div class="stat-value"><?php echo number_format($monthly_stats['unique_visitors']); ?></div>
            <div class="stat-change <?php echo $monthly_stats['visitors_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo sprintf('%+d%%', $monthly_stats['visitors_change']); ?>
            </div>
        </div>
    </div>
    
    <!-- Revenue Chart -->
    <div class="analytics-chart-container">
        <h2><?php _e('Revenue Over Time', 'contentbridge'); ?></h2>
        <canvas id="revenue-chart"></canvas>
    </div>
    
    <!-- Top Content Table -->
    <div class="analytics-table-container">
        <h2><?php _e('Top Performing Content', 'contentbridge'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Title', 'contentbridge'); ?></th>
                    <th><?php _e('Views', 'contentbridge'); ?></th>
                    <th><?php _e('Revenue', 'contentbridge'); ?></th>
                    <th><?php _e('Conversion Rate', 'contentbridge'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_content as $content): ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(get_permalink($content->post_id)); ?>">
                                <?php echo esc_html($content->title); ?>
                            </a>
                        </td>
                        <td><?php echo number_format($content->views); ?></td>
                        <td>$<?php echo number_format($content->revenue, 2); ?></td>
                        <td><?php echo number_format($content->conversion_rate, 1); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize revenue chart
    var ctx = document.getElementById('revenue-chart').getContext('2d');
    var revenueData = <?php echo json_encode($revenue_data); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: revenueData.labels,
            datasets: [{
                label: '<?php _e('Daily Revenue', 'contentbridge'); ?>',
                data: revenueData.values,
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
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });
    
    // Handle month selection
    $('#analytics-month').on('change', function() {
        window.location.href = window.location.pathname + '?page=contentbridge-analytics&month=' + $(this).val();
    });
    
    // Handle data export
    $('#export-analytics').on('click', function() {
        window.location.href = ajaxurl + '?action=contentbridge_export_analytics&month=' + $('#analytics-month').val();
    });
});
</script> 