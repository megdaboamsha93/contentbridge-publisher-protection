jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize date pickers
    if ($.fn.datepicker) {
        $('.date-picker').datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }
    
    // Handle API connection test
    $('#test-api-connection').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        button.addClass('disabled').text(contentbridgeAdmin.strings.testing);
        
        $.ajax({
            url: contentbridgeAdmin.apiUrl + '/test-connection',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', contentbridgeAdmin.apiNonce);
            },
            success: function(response) {
                if (response.success) {
                    alert(contentbridgeAdmin.strings.connectionSuccess);
                } else {
                    alert(contentbridgeAdmin.strings.connectionError);
                }
            },
            error: function() {
                alert(contentbridgeAdmin.strings.connectionError);
            },
            complete: function() {
                button.removeClass('disabled').text(contentbridgeAdmin.strings.testConnection);
            }
        });
    });
    
    // Handle cache clearing
    $('#clear-cache').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(contentbridgeAdmin.strings.confirmClearCache)) {
            return;
        }
        
        const button = $(this);
        button.addClass('disabled').text(contentbridgeAdmin.strings.clearing);
        
        $.ajax({
            url: contentbridgeAdmin.apiUrl + '/clear-cache',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', contentbridgeAdmin.apiNonce);
            },
            success: function(response) {
                if (response.success) {
                    alert(contentbridgeAdmin.strings.cacheCleared);
                } else {
                    alert(contentbridgeAdmin.strings.cacheClearError);
                }
            },
            error: function() {
                alert(contentbridgeAdmin.strings.cacheClearError);
            },
            complete: function() {
                button.removeClass('disabled').text(contentbridgeAdmin.strings.clearCache);
            }
        });
    });
    
    // Handle settings form submission
    $('.contentbridge-settings-form').on('submit', function(e) {
        const form = $(this);
        const submitButton = form.find(':submit');
        
        submitButton.addClass('disabled');
        
        // Store original button text
        if (!submitButton.data('original-text')) {
            submitButton.data('original-text', submitButton.text());
        }
        
        submitButton.text(contentbridgeAdmin.strings.saving);
    });
    
    // Handle metrics modal
    $('.view-metrics').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const postId = button.data('post-id');
        
        button.addClass('disabled').text(contentbridgeAdmin.strings.loading);
        
        $.ajax({
            url: contentbridgeAdmin.apiUrl + '/content/' + postId + '/metrics',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', contentbridgeAdmin.apiNonce);
            },
            success: function(response) {
                showMetricsModal(response);
            },
            error: function() {
                alert(contentbridgeAdmin.strings.metricsError);
            },
            complete: function() {
                button.removeClass('disabled').text(contentbridgeAdmin.strings.viewMetrics);
            }
        });
    });
    
    // Function to show metrics modal
    function showMetricsModal(data) {
        const modal = $('<div class="contentbridge-modal"></div>').appendTo('body');
        
        modal.html(`
            <div class="contentbridge-modal-content">
                <h2>${contentbridgeAdmin.strings.contentMetrics}</h2>
                <div class="metrics-grid">
                    <div class="metric">
                        <span class="label">${contentbridgeAdmin.strings.totalViews}</span>
                        <span class="value">${data.total_views}</span>
                    </div>
                    <div class="metric">
                        <span class="label">${contentbridgeAdmin.strings.totalEarnings}</span>
                        <span class="value">$${data.total_earnings.toFixed(2)}</span>
                    </div>
                    <div class="metric">
                        <span class="label">${contentbridgeAdmin.strings.avgTimeOnPage}</span>
                        <span class="value">${data.avg_time_on_page}s</span>
                    </div>
                </div>
                <button class="button close-modal">${contentbridgeAdmin.strings.close}</button>
            </div>
        `);
        
        // Handle modal close
        modal.find('.close-modal').on('click', function() {
            modal.remove();
        });
        
        // Close modal on escape key
        $(document).on('keyup.contentbridge-modal', function(e) {
            if (e.key === 'Escape') {
                modal.remove();
                $(document).off('keyup.contentbridge-modal');
            }
        });
        
        // Close modal on background click
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.remove();
                $(document).off('keyup.contentbridge-modal');
            }
        });
    }
    
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        // Performance chart
        const performanceChart = document.getElementById('performanceChart');
        if (performanceChart) {
            initializePerformanceChart(performanceChart);
        }
        
        // Content chart
        const contentChart = document.getElementById('contentChart');
        if (contentChart) {
            initializeContentChart(contentChart);
        }
    }
    
    // Function to initialize performance chart
    function initializePerformanceChart(canvas) {
        const ctx = canvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: contentbridgeAdmin.chartData.dates,
                datasets: [
                    {
                        label: contentbridgeAdmin.strings.views,
                        data: contentbridgeAdmin.chartData.views,
                        borderColor: '#2271b1',
                        yAxisID: 'y-views'
                    },
                    {
                        label: contentbridgeAdmin.strings.earnings,
                        data: contentbridgeAdmin.chartData.earnings,
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
    }
    
    // Function to initialize content chart
    function initializeContentChart(canvas) {
        const ctx = canvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: contentbridgeAdmin.chartData.contentTitles,
                datasets: [{
                    label: contentbridgeAdmin.strings.views,
                    data: contentbridgeAdmin.chartData.contentViews,
                    backgroundColor: '#2271b1'
                }]
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
    
    // Handle protection settings changes
    $('.protection-toggle').on('change', function() {
        const toggle = $(this);
        const postId = toggle.data('post-id');
        const isProtected = toggle.prop('checked');
        
        $.ajax({
            url: contentbridgeAdmin.apiUrl + '/content/' + postId + '/protection',
            method: 'POST',
            data: {
                protected: isProtected
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', contentbridgeAdmin.apiNonce);
            },
            success: function(response) {
                if (response.success) {
                    const status = toggle.closest('tr').find('.protection-status');
                    status.text(isProtected ? contentbridgeAdmin.strings.protected : contentbridgeAdmin.strings.notProtected)
                          .toggleClass('protected', isProtected)
                          .toggleClass('not-protected', !isProtected);
                } else {
                    toggle.prop('checked', !isProtected);
                    alert(contentbridgeAdmin.strings.updateError);
                }
            },
            error: function() {
                toggle.prop('checked', !isProtected);
                alert(contentbridgeAdmin.strings.updateError);
            }
        });
    });
}); 