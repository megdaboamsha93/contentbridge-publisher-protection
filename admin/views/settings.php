<?php
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap contentbridge-settings">
    <h1><?php esc_html_e('ContentBridge Settings', 'contentbridge'); ?></h1>

    <?php if (isset($_GET['settings-updated'])): ?>
        <div class="notice notice-success">
            <p><?php esc_html_e('Settings saved successfully.', 'contentbridge'); ?></p>
        </div>
    <?php endif; ?>

    <div class="contentbridge-settings-grid">
        <div class="settings-main">
            <form method="post" action="options.php">
                <?php
                settings_fields('contentbridge_settings');
                do_settings_sections('contentbridge_settings');
                ?>

                <div class="settings-section">
                    <h2><?php esc_html_e('Protection Rules', 'contentbridge'); ?></h2>
                    <div id="protection-rules">
                        <?php
                        $rules = get_option('contentbridge_protection_rules', array());
                        foreach ($rules as $index => $rule):
                        ?>
                            <div class="protection-rule">
                                <select name="contentbridge_protection_rules[<?php echo $index; ?>][type]">
                                    <option value="category" <?php selected($rule['type'], 'category'); ?>>
                                        <?php esc_html_e('Category', 'contentbridge'); ?>
                                    </option>
                                    <option value="tag" <?php selected($rule['type'], 'tag'); ?>>
                                        <?php esc_html_e('Tag', 'contentbridge'); ?>
                                    </option>
                                    <option value="url_pattern" <?php selected($rule['type'], 'url_pattern'); ?>>
                                        <?php esc_html_e('URL Pattern', 'contentbridge'); ?>
                                    </option>
                                </select>
                                <input type="text" 
                                       name="contentbridge_protection_rules[<?php echo $index; ?>][value]"
                                       value="<?php echo esc_attr($rule['value']); ?>"
                                       placeholder="<?php esc_attr_e('Rule value', 'contentbridge'); ?>">
                                <button type="button" class="button remove-rule">
                                    <?php esc_html_e('Remove', 'contentbridge'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button" id="add-rule">
                        <?php esc_html_e('Add Rule', 'contentbridge'); ?>
                    </button>
                </div>

                <div class="settings-section">
                    <h2><?php esc_html_e('Advanced Settings', 'contentbridge'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Cache Duration', 'contentbridge'); ?>
                            </th>
                            <td>
                                <input type="number" 
                                       name="contentbridge_cache_duration" 
                                       value="<?php echo esc_attr(get_option('contentbridge_cache_duration', 300)); ?>"
                                       min="0"
                                       step="1"
                                       class="small-text">
                                <?php esc_html_e('seconds', 'contentbridge'); ?>
                                <p class="description">
                                    <?php esc_html_e('How long to cache token validation results. Set to 0 to disable caching.', 'contentbridge'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Debug Mode', 'contentbridge'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="contentbridge_debug_mode" 
                                           value="1"
                                           <?php checked(get_option('contentbridge_debug_mode')); ?>>
                                    <?php esc_html_e('Enable debug mode', 'contentbridge'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Log detailed debug information for troubleshooting.', 'contentbridge'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <div class="settings-sidebar">
            <div class="settings-box">
                <h3><?php esc_html_e('Quick Start Guide', 'contentbridge'); ?></h3>
                <ol>
                    <li><?php esc_html_e('Enter your ContentBridge API key', 'contentbridge'); ?></li>
                    <li><?php esc_html_e('Select which content types to protect', 'contentbridge'); ?></li>
                    <li><?php esc_html_e('Set your default pricing', 'contentbridge'); ?></li>
                    <li><?php esc_html_e('Add any custom protection rules', 'contentbridge'); ?></li>
                    <li><?php esc_html_e('Save your settings', 'contentbridge'); ?></li>
                </ol>
                <p>
                    <a href="https://docs.contentbridge.eu" target="_blank" class="button button-secondary">
                        <?php esc_html_e('View Documentation', 'contentbridge'); ?>
                    </a>
                </p>
            </div>

            <div class="settings-box">
                <h3><?php esc_html_e('Need Help?', 'contentbridge'); ?></h3>
                <p>
                    <?php esc_html_e('If you need assistance setting up ContentBridge or have any questions, our support team is here to help.', 'contentbridge'); ?>
                </p>
                <p>
                    <a href="https://contentbridge.eu/support" target="_blank" class="button button-secondary">
                        <?php esc_html_e('Contact Support', 'contentbridge'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var ruleTemplate = `
        <div class="protection-rule">
            <select name="contentbridge_protection_rules[{index}][type]">
                <option value="category"><?php esc_html_e('Category', 'contentbridge'); ?></option>
                <option value="tag"><?php esc_html_e('Tag', 'contentbridge'); ?></option>
                <option value="url_pattern"><?php esc_html_e('URL Pattern', 'contentbridge'); ?></option>
            </select>
            <input type="text" 
                   name="contentbridge_protection_rules[{index}][value]"
                   placeholder="<?php esc_attr_e('Rule value', 'contentbridge'); ?>">
            <button type="button" class="button remove-rule">
                <?php esc_html_e('Remove', 'contentbridge'); ?>
            </button>
        </div>
    `;

    $('#add-rule').on('click', function() {
        var index = $('.protection-rule').length;
        var newRule = ruleTemplate.replace(/{index}/g, index);
        $('#protection-rules').append(newRule);
    });

    $(document).on('click', '.remove-rule', function() {
        $(this).closest('.protection-rule').remove();
        // Update indices
        $('.protection-rule').each(function(index) {
            $(this).find('select, input').each(function() {
                var name = $(this).attr('name');
                $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
            });
        });
    });
});
</script>

<style>
.contentbridge-settings {
    max-width: 1200px;
    margin: 20px auto;
}

.contentbridge-settings-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.settings-main {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.settings-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.settings-box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.settings-box h3 {
    margin-top: 0;
    color: #23282d;
}

.settings-section {
    margin-bottom: 30px;
}

.protection-rule {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.protection-rule select {
    width: 150px;
}

.protection-rule input {
    flex: 1;
}

#add-rule {
    margin-top: 10px;
}

@media screen and (max-width: 782px) {
    .contentbridge-settings-grid {
        grid-template-columns: 1fr;
    }

    .protection-rule {
        flex-direction: column;
        gap: 5px;
    }

    .protection-rule select,
    .protection-rule input {
        width: 100%;
    }
}
</style> 