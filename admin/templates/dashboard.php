<?php
/**
 * Admin dashboard template
 *
 * @package ContentBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get settings
$settings = get_option('contentbridge_settings', array());
$api_key = isset($settings['api_key']) ? $settings['api_key'] : '';

// Get analytics data
$analytics = new ContentBridge\Analytics();
$data = $analytics->get_analytics(
    date('Y-m-d', strtotime('-30 days')),
    date('Y-m-d')
);

// Check if API key is configured
if (empty($api_key)) {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('ContentBridge Dashboard', 'contentbridge'); ?></h1>
        
        <div class="notice notice-warning">
            <p>
                <?php
                printf(
                    wp_kses(
                        __('Please configure your ContentBridge API key in the <a href="%s">settings page</a> to start protecting and monetizing your content.', 'contentbridge'),
                        array('a' => array('href' => array()))
                    ),
                    esc_url(admin_url('admin.php?page=contentbridge-settings'))
                );
                ?>
            </p>
        </div>
    </div>
    <?php
    return;
}

// Add export nonce and URL
$export_nonce = wp_create_nonce('contentbridge_export_analytics');
$export_url = admin_url('admin-post.php?action=contentbridge_export_analytics&nonce=' . $export_nonce);

if (is_wp_error($data)) {
    echo '<div class="notice notice-error"><p>' . esc_html($data->get_error_message()) . '</p></div>';
    return;
}
?>

<div class="wrap contentbridge-dashboard">
    <h1><?php echo esc_html__('ContentBridge Dashboard', 'contentbridge'); ?></h1>

    <div class="contentbridge-dashboard__header">
        <div class="contentbridge-dashboard__period-selector">
            <select id="contentbridge-period">
                <option value="7"><?php esc_html_e('Last 7 days', 'contentbridge'); ?></option>
                <option value="30" selected><?php esc_html_e('Last 30 days', 'contentbridge'); ?></option>
                <option value="90"><?php esc_html_e('Last 90 days', 'contentbridge'); ?></option>
                <option value="custom"><?php esc_html_e('Custom range', 'contentbridge'); ?></option>
            </select>

            <div id="contentbridge-custom-range" style="display: none;">
                <input type="date" id="contentbridge-start-date" value="<?php echo esc_attr(date('Y-m-d', strtotime('-30 days'))); ?>">
                <input type="date" id="contentbridge-end-date" value="<?php echo esc_attr(date('Y-m-d')); ?>">
            </div>

            <button class="button button-primary" id="contentbridge-update">
                <?php esc_html_e('Update', 'contentbridge'); ?>
            </button>
        </div>

        <div class="contentbridge-dashboard__actions">
            <button type="button" id="contentbridge-export" class="button"
                data-export-url="<?php echo esc_url($export_url); ?>">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export Data', 'contentbridge'); ?>
            </button>
        </div>
    </div>

    <div class="contentbridge-dashboard__grid">
        <!-- Revenue Overview Card -->
        <div class="contentbridge-card">
            <h2><?php esc_html_e('Revenue Overview', 'contentbridge'); ?></h2>
            <div class="contentbridge-card__content">
                <div class="contentbridge-stats-grid">
                    <div class="contentbridge-stat">
                        <span class="contentbridge-stat__label"><?php esc_html_e('Total Revenue', 'contentbridge'); ?></span>
                        <span class="contentbridge-stat__value" id="total-revenue">
                            $<?php echo number_format($data['revenue']['total_revenue'], 2); ?>
                        </span>
                    </div>
                    <div class="contentbridge-stat">
                        <span class="contentbridge-stat__label"><?php esc_html_e('Total Requests', 'contentbridge'); ?></span>
                        <span class="contentbridge-stat__value" id="total-requests">
                            <?php echo number_format($data['revenue']['total_requests']); ?>
                        </span>
                    </div>
                    <div class="contentbridge-stat">
                        <span class="contentbridge-stat__label"><?php esc_html_e('Average Revenue/Request', 'contentbridge'); ?></span>
                        <span class="contentbridge-stat__value" id="avg-revenue">
                            $<?php echo number_format($data['revenue']['total_requests'] ? $data['revenue']['total_revenue'] / $data['revenue']['total_requests'] : 0, 3); ?>
                        </span>
                    </div>
                </div>
                <div id="revenue-chart"></div>
            </div>
        </div>

        <!-- Top Content Card -->
        <div class="contentbridge-card">
            <h2><?php esc_html_e('Top Performing Content', 'contentbridge'); ?></h2>
            <div class="contentbridge-card__content">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Content', 'contentbridge'); ?></th>
                            <th><?php esc_html_e('Requests', 'contentbridge'); ?></th>
                            <th><?php esc_html_e('Revenue', 'contentbridge'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="top-content">
                        <?php foreach ($data['content'] as $item): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_permalink($item['post_id'])); ?>" target="_blank">
                                    <?php echo esc_html($item['title']); ?>
                                </a>
                            </td>
                            <td><?php echo number_format($item['requests']); ?></td>
                            <td>$<?php echo number_format($item['revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- AI Companies Card -->
        <div class="contentbridge-card">
            <h2><?php esc_html_e('AI Company Usage', 'contentbridge'); ?></h2>
            <div class="contentbridge-card__content">
                <div id="companies-chart"></div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Company', 'contentbridge'); ?></th>
                            <th><?php esc_html_e('Requests', 'contentbridge'); ?></th>
                            <th><?php esc_html_e('Revenue', 'contentbridge'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="company-usage">
                        <?php foreach ($data['companies'] as $company): ?>
                        <tr>
                            <td><?php echo esc_html($company['name']); ?></td>
                            <td><?php echo number_format($company['requests']); ?></td>
                            <td>$<?php echo number_format($company['revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.contentbridge-dashboard {
    margin: 20px 0;
}

.contentbridge-dashboard__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.contentbridge-dashboard__period-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.contentbridge-dashboard__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.contentbridge-card {
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.contentbridge-card h2 {
    margin: 0;
    padding: 15px;
    border-bottom: 1px solid #e2e4e7;
    font-size: 14px;
    font-weight: 600;
}

.contentbridge-card__content {
    padding: 15px;
}

.contentbridge-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.contentbridge-stat {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.contentbridge-stat__label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-bottom: 5px;
}

.contentbridge-stat__value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #1e1e1e;
}

#revenue-chart,
#companies-chart {
    height: 300px;
    margin-bottom: 20px;
}

.contentbridge-dashboard .wp-list-table {
    margin-top: 0;
}

@media screen and (max-width: 782px) {
    .contentbridge-dashboard__header {
        flex-direction: column;
        gap: 15px;
    }

    .contentbridge-dashboard__period-selector {
        flex-wrap: wrap;
    }

    .contentbridge-stat__value {
        font-size: 20px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize charts
    function initCharts() {
        // Revenue chart
        const revenueCtx = document.getElementById('revenue-chart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode(array_column($data['revenue']['daily'], 'date')); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Daily Revenue', 'contentbridge'); ?>',
                    data: <?php echo wp_json_encode(array_column($data['revenue']['daily'], 'revenue')); ?>,
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
                            callback: value => '$' + value.toFixed(2)
                        }
                    }
                }
            }
        });

        // Companies chart
        const companiesCtx = document.getElementById('companies-chart').getContext('2d');
        new Chart(companiesCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo wp_json_encode(array_column($data['companies'], 'name')); ?>,
                datasets: [{
                    data: <?php echo wp_json_encode(array_column($data['companies'], 'revenue')); ?>,
                    backgroundColor: [
                        '#2271b1',
                        '#3582c4',
                        '#4f94d4',
                        '#72aee6',
                        '#9ec2e6',
                        '#c6d9f0'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Initialize charts on page load
    initCharts();

    // Handle period selector
    $('#contentbridge-period').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#contentbridge-custom-range').show();
        } else {
            $('#contentbridge-custom-range').hide();
        }
    });

    // Update button functionality (AJAX)
    $('#contentbridge-update').on('click', function() {
        let start = $('#contentbridge-start-date').val();
        let end = $('#contentbridge-end-date').val();
        if (!start || !end) {
            alert('<?php esc_html_e('Please select a valid date range.', 'contentbridge'); ?>');
            return;
        }
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'contentbridge_get_analytics',
                nonce: '<?php echo wp_create_nonce('contentbridge_analytics'); ?>',
                start_date: start,
                end_date: end
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php esc_html_e('Error updating analytics data', 'contentbridge'); ?>');
            }
        });
    });

    // Export functionality
    $('#contentbridge-export').on('click', function() {
        // Get selected date range
        let start = $('#contentbridge-start-date').val();
        let end = $('#contentbridge-end-date').val();
        let url = $(this).data('export-url');
        if (start && end) {
            url += '&start_date=' + encodeURIComponent(start) + '&end_date=' + encodeURIComponent(end);
        }
        window.location.href = url;
    });

    function updateDashboard(data) {
        // Update stats
        $('.stat-value').eq(0).text('$' + (data.revenue.total_revenue ? Number(data.revenue.total_revenue).toFixed(2) : '0.00'));
        $('.stat-value').eq(1).text(data.revenue.total_requests ? Number(data.revenue.total_requests).toLocaleString() : '0');
        $('.stat-value').eq(2).text(data.revenue.unique_tokens ? Number(data.revenue.unique_tokens).toLocaleString() : '0');
        let avg = (data.revenue.total_requests > 0) ? (data.revenue.total_revenue / data.revenue.total_requests) : 0;
        $('.stat-value').eq(3).text('$' + Number(avg).toFixed(3));

        // Update top content table
        let contentHtml = '';
        if (Array.isArray(data.content)) {
            data.content.slice(0, 10).forEach(function(item) {
                let requests = item.requests ? Number(item.requests) : 0;
                let revenue = item.revenue ? Number(item.revenue) : 0;
                let avg_price = requests > 0 ? revenue / requests : 0;
                contentHtml += `<tr>
                    <td><strong>${item.title || 'Unknown'}</strong><br><small>${item.url || ''}</small></td>
                    <td>${requests.toLocaleString()}</td>
                    <td>$${revenue.toFixed(2)}</td>
                    <td>$${avg_price.toFixed(3)}</td>
                </tr>`;
            });
        }
        $('.contentbridge-table-wrapper tbody').first().html(contentHtml);

        // Update company usage table
        let companyHtml = '';
        if (Array.isArray(data.companies)) {
            data.companies.slice(0, 10).forEach(function(company) {
                companyHtml += `<tr>
                    <td><strong>${company.name || 'Unknown Company'}</strong></td>
                    <td>${company.requests ? Number(company.requests).toLocaleString() : 0}</td>
                    <td>$${company.revenue ? Number(company.revenue).toFixed(2) : '0.00'}</td>
                    <td>${company.last_access ? new Date(company.last_access).toLocaleString() : '<?php esc_html_e('Never', 'contentbridge'); ?>'}</td>
                </tr>`;
            });
        }
        $('.contentbridge-table-wrapper tbody').last().html(companyHtml);

        // Reinitialize charts
        initCharts();
    }
});
</script><?php
// Add help tab
get_current_screen()->add_help_tab(array(
    'id' => 'contentbridge-dashboard-help',
    'title' => __('Dashboard Help', 'contentbridge'),
    'content' => '
        <h2>' . __('ContentBridge Dashboard', 'contentbridge') . '</h2>
        <p>' . __('This dashboard provides an overview of your content monetization through ContentBridge:', 'contentbridge') . '</p>
        <ul>
            <li>' . __('Revenue Overview: Shows your total revenue, requests, and average revenue per request.', 'contentbridge') . '</li>
            <li>' . __('Top Performing Content: Lists your most accessed and highest-earning content.', 'contentbridge') . '</li>
            <li>' . __('AI Company Usage: Shows which AI companies are accessing your content.', 'contentbridge') . '</li>
        </ul>
        <p>' . __('Use the period selector to view data for different time ranges, and export your data for detailed analysis.', 'contentbridge') . '</p>
    '
)); 