jQuery(document).ready(function($) {
    'use strict';

    // Protection Level Change Handler
    $('#contentbridge_protection_level').on('change', function() {
        var level = $(this).val();
        var customFields = $('.contentbridge-custom-protection');
        
        if (level === 'custom') {
            customFields.slideDown();
        } else {
            customFields.slideUp();
        }
    });

    // Initialize tooltips
    $('.contentbridge-tooltip').tooltip();

    // Handle bulk actions
    $('.contentbridge-bulk-action').on('click', function(e) {
        e.preventDefault();
        
        var action = $('#bulk-action-selector-top').val();
        var selectedItems = $('.contentbridge-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (!action || selectedItems.length === 0) {
            alert(contentbridgeAdmin.strings.noSelection);
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'contentbridge_bulk_action',
                security: contentbridgeAdmin.nonce,
                bulk_action: action,
                items: selectedItems
            },
            beforeSend: function() {
                $('.contentbridge-loading').show();
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data.message || contentbridgeAdmin.strings.error);
                }
            },
            error: function() {
                alert(contentbridgeAdmin.strings.error);
            },
            complete: function() {
                $('.contentbridge-loading').hide();
            }
        });
    });

    // Analytics Date Range Picker
    if ($('#analytics-daterange').length) {
        $('#analytics-daterange').daterangepicker({
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            startDate: moment().subtract(29, 'days'),
            endDate: moment(),
            locale: {
                format: 'YYYY-MM-DD'
            }
        }, function(start, end) {
            updateAnalytics(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
        });
    }

    // Update analytics data
    function updateAnalytics(startDate, endDate) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'contentbridge_get_analytics',
                security: contentbridgeAdmin.nonce,
                start_date: startDate,
                end_date: endDate
            },
            beforeSend: function() {
                $('.contentbridge-loading').show();
            },
            success: function(response) {
                if (response.success) {
                    updateAnalyticsUI(response.data);
                }
            },
            complete: function() {
                $('.contentbridge-loading').hide();
            }
        });
    }

    // Update analytics UI elements
    function updateAnalyticsUI(data) {
        // Update summary cards
        $('.total-views .stat-value').text(data.total_views.toLocaleString());
        $('.total-revenue .stat-value').text('$' + data.total_revenue.toLocaleString());
        $('.unique-visitors .stat-value').text(data.unique_visitors.toLocaleString());

        // Update chart if it exists
        if (window.revenueChart && data.chart_data) {
            window.revenueChart.data.labels = data.chart_data.labels;
            window.revenueChart.data.datasets[0].data = data.chart_data.values;
            window.revenueChart.update();
        }

        // Update top content table
        if (data.top_content) {
            var tableBody = $('.analytics-table tbody');
            tableBody.empty();
            
            data.top_content.forEach(function(item) {
                tableBody.append(
                    '<tr>' +
                    '<td><a href="' + item.url + '">' + item.title + '</a></td>' +
                    '<td>' + item.views.toLocaleString() + '</td>' +
                    '<td>$' + item.revenue.toLocaleString() + '</td>' +
                    '<td>' + item.conversion_rate.toFixed(1) + '%</td>' +
                    '</tr>'
                );
            });
        }
    }

    // Export analytics data
    $('#export-analytics').on('click', function(e) {
        e.preventDefault();
        
        var dateRange = $('#analytics-daterange').val().split(' - ');
        var startDate = dateRange[0];
        var endDate = dateRange[1];
        
        window.location.href = ajaxurl + 
            '?action=contentbridge_export_analytics' +
            '&security=' + contentbridgeAdmin.nonce +
            '&start_date=' + startDate +
            '&end_date=' + endDate;
    });

    // Test API Connection
    $('#test-api-connection').on('click', function(e) {
        e.preventDefault();
        
        var apiKey = $('#contentbridge_api_key').val();
        
        if (!apiKey) {
            alert(contentbridgeAdmin.strings.noApiKey);
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'contentbridge_test_connection',
                security: contentbridgeAdmin.nonce,
                api_key: apiKey
            },
            beforeSend: function() {
                $(e.target).prop('disabled', true).text(contentbridgeAdmin.strings.testing);
            },
            success: function(response) {
                if (response.success) {
                    alert(contentbridgeAdmin.strings.connectionSuccess);
                } else {
                    alert(response.data.message || contentbridgeAdmin.strings.connectionError);
                }
            },
            error: function() {
                alert(contentbridgeAdmin.strings.connectionError);
            },
            complete: function() {
                $(e.target).prop('disabled', false).text(contentbridgeAdmin.strings.testConnection);
            }
        });
    });
}); 