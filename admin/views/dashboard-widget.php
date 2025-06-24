<?php
if (!defined('WPINC')) {
    die;
}
?>
<div class="contentbridge-widget">
    <?php if (empty(get_option('contentbridge_api_key'))): ?>
        <div class="notice notice-warning inline">
            <p>
                <?php 
                printf(
                    __('Please <a href="%s">configure your ContentBridge API key</a> to start protecting and monetizing your content.', 'contentbridge'),
                    admin_url('admin.php?page=contentbridge-settings')
                );
                ?>
            </p>
        </div>
    <?php else: ?>
        <div class="widget-stats">
            <div class="stat-item">
                <span class="stat-label"><?php esc_html_e('Monthly Revenue', 'contentbridge'); ?></span>
                <span class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></span>
            </div>

            <div class="stat-item">
                <span class="stat-label"><?php esc_html_e('Total Requests', 'contentbridge'); ?></span>
                <span class="stat-value"><?php echo number_format($stats['total_requests']); ?></span>
            </div>

            <div class="stat-item">
                <span class="stat-label"><?php esc_html_e('Avg. Revenue/Request', 'contentbridge'); ?></span>
                <span class="stat-value">
                    $<?php 
                    echo $stats['total_requests'] 
                        ? number_format($stats['total_revenue'] / $stats['total_requests'], 3) 
                        : '0.000'; 
                    ?>
                </span>
            </div>
        </div>

        <?php if (!empty($stats['top_content'])): ?>
            <div class="widget-table">
                <h4><?php esc_html_e('Top Performing Content', 'contentbridge'); ?></h4>
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Content', 'contentbridge'); ?></th>
                            <th><?php esc_html_e('Revenue', 'contentbridge'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $top_3 = array_slice($stats['top_content'], 0, 3);
                        foreach ($top_3 as $content): 
                        ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($content['post_id']); ?>">
                                        <?php echo esc_html($content['post_title']); ?>
                                    </a>
                                </td>
                                <td>$<?php echo number_format($content['revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="widget-footer">
            <a href="<?php echo admin_url('admin.php?page=contentbridge'); ?>" class="button button-secondary">
                <?php esc_html_e('View Full Dashboard', 'contentbridge'); ?>
            </a>
        </p>
    <?php endif; ?>
</div>

<style>
.contentbridge-widget {
    padding: 12px;
}

.contentbridge-widget .notice {
    margin: 0 0 12px;
}

.widget-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 15px;
}

.stat-item {
    text-align: center;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat-label {
    display: block;
    font-size: 11px;
    color: #646970;
    margin-bottom: 4px;
}

.stat-value {
    display: block;
    font-size: 16px;
    font-weight: 600;
    color: #2271b1;
}

.widget-table {
    margin: 15px 0;
}

.widget-table h4 {
    margin: 0 0 8px;
    color: #23282d;
}

.widget-table table {
    width: 100%;
    border-collapse: collapse;
}

.widget-table th,
.widget-table td {
    padding: 6px;
    text-align: left;
    border-bottom: 1px solid #f0f0f1;
}

.widget-table th {
    font-weight: 600;
    color: #646970;
}

.widget-footer {
    margin: 12px 0 0;
    text-align: center;
}
</style> 