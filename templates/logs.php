<?php
/**
 * Enhanced WooCommerce Sync Pro - Logs Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$logger = new EWCS_Sync_Logger();
$log_stats = $logger->get_log_stats('24 HOUR');
?>

<div class="ewcs-pro-dashboard">
    <div class="ewcs-pro-container">
        <div class="ewcs-pro-header">
            <h1><?php _e('Sync Logs', 'enhanced-wc-sync-pro'); ?></h1>
            <p><?php _e('View detailed logs of all sync operations and system activities', 'enhanced-wc-sync-pro'); ?></p>
        </div>

        <!-- Log Statistics -->
        <div class="ewcs-pro-card">
            <h2><?php _e('Last 24 Hours Statistics', 'enhanced-wc-sync-pro'); ?></h2>
            <div class="ewcs-pro-stats">
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value"><?php echo $log_stats['total']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Total Events', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value" style="color: #48bb78;"><?php echo $log_stats['success']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Success', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value" style="color: #f56565;"><?php echo $log_stats['error']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Errors', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value" style="color: #ed8936;"><?php echo $log_stats['warning']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Warnings', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value" style="color: #4299e1;"><?php echo $log_stats['info']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Info', 'enhanced-wc-sync-pro'); ?></span>
                </div>
            </div>
        </div>

        <!-- Log Filters -->
        <div class="ewcs-pro-card">
            <h2><?php _e('Filter Logs', 'enhanced-wc-sync-pro'); ?></h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label for="log-action-filter" class="ewcs-pro-form-label"><?php _e('Action', 'enhanced-wc-sync-pro'); ?></label>
                    <select id="log-action-filter" class="ewcs-pro-form-input">
                        <option value=""><?php _e('All Actions', 'enhanced-wc-sync-pro'); ?></option>
                        <?php foreach ($log_stats['by_action'] as $action => $stats): ?>
                        <option value="<?php echo esc_attr($action); ?>"><?php echo esc_html($action); ?> (<?php echo $stats['total']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="log-status-filter" class="ewcs-pro-form-label"><?php _e('Status', 'enhanced-wc-sync-pro'); ?></label>
                    <select id="log-status-filter" class="ewcs-pro-form-input">
                        <option value=""><?php _e('All Statuses', 'enhanced-wc-sync-pro'); ?></option>
                        <option value="success"><?php _e('Success', 'enhanced-wc-sync-pro'); ?></option>
                        <option value="error"><?php _e('Error', 'enhanced-wc-sync-pro'); ?></option>
                        <option value="warning"><?php _e('Warning', 'enhanced-wc-sync-pro'); ?></option>
                        <option value="info"><?php _e('Info', 'enhanced-wc-sync-pro'); ?></option>
                    </select>
                </div>
                
                <div>
                    <label for="log-date-from" class="ewcs-pro-form-label"><?php _e('From Date', 'enhanced-wc-sync-pro'); ?></label>
                    <input type="date" id="log-date-from" class="ewcs-pro-form-input" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                </div>
                
                <div>
                    <label for="log-date-to" class="ewcs-pro-form-label"><?php _e('To Date', 'enhanced-wc-sync-pro'); ?></label>
                    <input type="date" id="log-date-to" class="ewcs-pro-form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div>
                    <label for="log-search" class="ewcs-pro-form-label"><?php _e('Search', 'enhanced-wc-sync-pro'); ?></label>
                    <input type="text" id="log-search" class="ewcs-pro-form-input" placeholder="<?php _e('Search logs...', 'enhanced-wc-sync-pro'); ?>">
                </div>
                
                <div style="display: flex; align-items: end; gap: 10px;">
                    <button id="apply-log-filters" class="ewcs-pro-btn ewcs-pro-btn-primary">
                        <span class="dashicons dashicons-filter"></span>
                        <?php _e('Filter', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    <button id="reset-log-filters" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Reset', 'enhanced-wc-sync-pro'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Log Actions -->
        <div class="ewcs-pro-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;"><?php _e('Recent Activity', 'enhanced-wc-sync-pro'); ?></h2>
                <div class="ewcs-pro-actions">
                    <button id="refresh-logs" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Refresh', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    
                    <?php if (!empty($logs)): ?>
                    <button id="export-logs" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    
                    <button id="clear-logs" class="ewcs-pro-btn ewcs-pro-btn-danger">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Clear All Logs', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($logs)): ?>
            <div class="ewcs-pro-alert ewcs-pro-alert-info">
                <?php _e('No logs available yet. Start syncing to see activity here.', 'enhanced-wc-sync-pro'); ?>
            </div>
            <?php else: ?>
            
            <!-- Log Summary -->
            <div style="margin-bottom: 20px;">
                <div class="ewcs-pro-alert ewcs-pro-alert-info">
                    <strong><?php _e('Showing:', 'enhanced-wc-sync-pro'); ?></strong>
                    <span id="logs-count"><?php echo count($logs); ?></span> <?php _e('log entries', 'enhanced-wc-sync-pro'); ?>
                    <span style="margin-left: 20px;">
                        <strong><?php _e('Last updated:', 'enhanced-wc-sync-pro'); ?></strong>
                        <span id="logs-last-updated"><?php echo current_time('M j, Y H:i:s'); ?></span>
                    </span>
                </div>
            </div>

            <!-- Log Table -->
            <div class="table-responsive">
                <table class="ewcs-duplicates-table" id="logs-table">
                    <thead>
                        <tr>
                            <th style="width: 140px;"><?php _e('Timestamp', 'enhanced-wc-sync-pro'); ?></th>
                            <th style="width: 100px;"><?php _e('Status', 'enhanced-wc-sync-pro'); ?></th>
                            <th style="width: 120px;"><?php _e('Action', 'enhanced-wc-sync-pro'); ?></th>
                            <th><?php _e('Message', 'enhanced-wc-sync-pro'); ?></th>
                            <th style="width: 80px;"><?php _e('Product', 'enhanced-wc-sync-pro'); ?></th>
                            <th style="width: 80px;"><?php _e('Remote ID', 'enhanced-wc-sync-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                        <?php foreach ($logs as $log): ?>
                        <tr data-status="<?php echo esc_attr($log->status); ?>" 
                            data-action="<?php echo esc_attr($log->action); ?>"
                            data-timestamp="<?php echo esc_attr($log->timestamp); ?>">
                            <td>
                                <div style="font-size: 12px; color: #666;">
                                    <?php echo date('M j, H:i:s', strtotime($log->timestamp)); ?>
                                </div>
                            </td>
                            <td>
                                <span class="ewcs-duplicate-status <?php echo esc_attr($log->status); ?>">
                                    <?php echo esc_html(ucfirst($log->status)); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo esc_html($log->action); ?></strong>
                            </td>
                            <td>
                                <div class="log-message" style="max-width: 400px; word-wrap: break-word;">
                                    <?php echo esc_html($log->message); ?>
                                </div>
                                <?php if ($log->meta): ?>
                                <details style="margin-top: 5px;">
                                    <summary style="cursor: pointer; font-size: 12px; color: #666;">
                                        <?php _e('View Details', 'enhanced-wc-sync-pro'); ?>
                                    </summary>
                                    <pre style="font-size: 11px; background: #f8f9fa; padding: 8px; border-radius: 4px; margin: 5px 0; overflow-x: auto;"><?php echo esc_html($log->meta); ?></pre>
                                </details>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log->product_id): ?>
                                <a href="<?php echo get_edit_post_link($log->product_id); ?>" target="_blank">
                                    #<?php echo $log->product_id; ?>
                                </a>
                                <?php else: ?>
                                <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $log->remote_id ? esc_html($log->remote_id) : '<span style="color: #999;">—</span>'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (if needed) -->
            <div style="margin-top: 20px; text-align: center;">
                <button id="load-more-logs" class="ewcs-pro-btn ewcs-pro-btn-secondary" data-offset="<?php echo count($logs); ?>">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    <?php _e('Load More Logs', 'enhanced-wc-sync-pro'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Performance Metrics (if available) -->
        <?php 
        $performance_metrics = $logger->get_performance_metrics('24 HOUR');
        if ($performance_metrics['total_operations'] > 0): 
        ?>
        <div class="ewcs-pro-card">
            <h2><?php _e('Performance Metrics (24h)', 'enhanced-wc-sync-pro'); ?></h2>
            <div class="ewcs-pro-stats">
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value"><?php echo $performance_metrics['total_operations']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Operations', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value"><?php echo number_format($performance_metrics['avg_duration'], 2); ?>s</span>
                    <span class="ewcs-pro-stat-label"><?php _e('Avg Duration', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value"><?php echo number_format($performance_metrics['max_duration'], 2); ?>s</span>
                    <span class="ewcs-pro-stat-label"><?php _e('Max Duration', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value"><?php echo number_format($performance_metrics['avg_memory'] / 1024 / 1024, 1); ?>MB</span>
                    <span class="ewcs-pro-stat-label"><?php _e('Avg Memory', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value"><?php echo number_format($performance_metrics['max_memory'] / 1024 / 1024, 1); ?>MB</span>
                    <span class="ewcs-pro-stat-label"><?php _e('Peak Memory', 'enhanced-wc-sync-pro'); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let currentOffset = <?php echo count($logs); ?>;
    
    // Apply filters
    $('#apply-log-filters').on('click', function() {
        applyLogFilters();
    });
    
    // Reset filters
    $('#reset-log-filters').on('click', function() {
        $('#log-action-filter').val('');
        $('#log-status-filter').val('');
        $('#log-date-from').val('<?php echo date('Y-m-d', strtotime('-7 days')); ?>');
        $('#log-date-to').val('<?php echo date('Y-m-d'); ?>');
        $('#log-search').val('');
        applyLogFilters();
    });
    
    // Real-time search
    $('#log-search').on('input', function() {
        applyLogFilters();
    });
    
    // Auto-filter on dropdown change
    $('#log-action-filter, #log-status-filter').on('change', function() {
        applyLogFilters();
    });
    
    function applyLogFilters() {
        const actionFilter = $('#log-action-filter').val();
        const statusFilter = $('#log-status-filter').val();
        const searchTerm = $('#log-search').val().toLowerCase();
        const dateFrom = $('#log-date-from').val();
        const dateTo = $('#log-date-to').val();
        
        let visibleCount = 0;
        
        $('#logs-table-body tr').each(function() {
            const row = $(this);
            const status = row.data('status');
            const action = row.data('action');
            const timestamp = row.data('timestamp');
            const message = row.find('.log-message').text().toLowerCase();
            const logDate = timestamp.split(' ')[0]; // Extract date part
            
            let showRow = true;
            
            // Action filter
            if (actionFilter && action !== actionFilter) {
                showRow = false;
            }
            
            // Status filter
            if (statusFilter && status !== statusFilter) {
                showRow = false;
            }
            
            // Date range filter
            if (dateFrom && logDate < dateFrom) {
                showRow = false;
            }
            if (dateTo && logDate > dateTo) {
                showRow = false;
            }
            
            // Search filter
            if (searchTerm && !message.includes(searchTerm) && !action.toLowerCase().includes(searchTerm)) {
                showRow = false;
            }
            
            if (showRow) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });
        
        $('#logs-count').text(visibleCount);
    }
    
    // Load more logs
    $('#load-more-logs').on('click', function() {
        const button = $(this);
        const offset = button.data('offset');
        
        button.prop('disabled', true).html('<span class="ewcs-spinner"></span> Loading...');
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_load_more_logs',
                offset: offset,
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.logs.length > 0) {
                    const logs = response.data.logs;
                    let newRows = '';
                    
                    logs.forEach(function(log) {
                        const productLink = log.product_id ? 
                            `<a href="/wp-admin/post.php?post=${log.product_id}&action=edit" target="_blank">#${log.product_id}</a>` : 
                            '<span style="color: #999;">—</span>';
                        
                        const remoteId = log.remote_id || '<span style="color: #999;">—</span>';
                        
                        const metaDetails = log.meta ? 
                            `<details style="margin-top: 5px;">
                                <summary style="cursor: pointer; font-size: 12px; color: #666;">View Details</summary>
                                <pre style="font-size: 11px; background: #f8f9fa; padding: 8px; border-radius: 4px; margin: 5px 0; overflow-x: auto;">${log.meta}</pre>
                            </details>` : '';
                        
                        newRows += `
                            <tr data-status="${log.status}" data-action="${log.action}" data-timestamp="${log.timestamp}">
                                <td><div style="font-size: 12px; color: #666;">${new Date(log.timestamp).toLocaleDateString('en-US', {month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit'})}</div></td>
                                <td><span class="ewcs-duplicate-status ${log.status}">${log.status.charAt(0).toUpperCase() + log.status.slice(1)}</span></td>
                                <td><strong>${log.action}</strong></td>
                                <td><div class="log-message" style="max-width: 400px; word-wrap: break-word;">${log.message}</div>${metaDetails}</td>
                                <td>${productLink}</td>
                                <td>${remoteId}</td>
                            </tr>
                        `;
                    });
                    
                    $('#logs-table-body').append(newRows);
                    button.data('offset', offset + logs.length);
                    
                    // Update counts
                    const totalVisible = $('#logs-table-body tr:visible').length;
                    $('#logs-count').text(totalVisible);
                    
                    if (logs.length < 50) { // If less than full batch, hide load more button
                        button.hide();
                    }
                } else {
                    button.hide();
                    alert('No more logs to load.');
                }
            },
            error: function() {
                alert('Failed to load more logs.');
            },
            complete: function() {
                button.prop('disabled', false).html('<span class="dashicons dashicons-arrow-down-alt2"></span> Load More Logs');
            }
        });
    });
    
    // Auto-refresh logs every 30 seconds
    setInterval(function() {
        $('#logs-last-updated').text(new Date().toLocaleString());
        
        // Optionally auto-refresh the first few logs
        // You can implement this to check for new logs
    }, 30000);
    
    // Export logs
    $('#export-logs').on('click', function() {
        const filters = {
            action: $('#log-action-filter').val(),
            status: $('#log-status-filter').val(),
            date_from: $('#log-date-from').val(),
            date_to: $('#log-date-to').val()
        };
        
        let exportUrl = ewcs_pro_ajax.ajax_url + '?action=ewcs_export_logs&nonce=' + ewcs_pro_ajax.nonce;
        
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                exportUrl += '&' + key + '=' + encodeURIComponent(filters[key]);
            }
        });
        
        window.open(exportUrl, '_blank');
        
        // Show notification
        $('<div class="ewcs-pro-alert ewcs-pro-alert-success ewcs-fade-in" style="position: fixed; top: 32px; right: 20px; z-index: 9999; max-width: 300px;">' +
            'CSV export started. Check your downloads folder.' +
            '<button type="button" class="notice-dismiss" style="float: right; margin-left: 10px;">&times;</button>' +
        '</div>').appendTo('body').delay(4000).fadeOut(function() {
            $(this).remove();
        });
    });
    
    // Clear all logs
    $('#clear-logs').on('click', function() {
        if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
            return;
        }
        
        const button = $(this);
        button.prop('disabled', true).html('<span class="ewcs-spinner"></span> Clearing...');
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_pro_clear_logs',
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('All logs cleared successfully.');
                    location.reload();
                } else {
                    alert('Failed to clear logs: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to clear logs. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear All Logs');
            }
        });
    });
    
    // Refresh logs
    $('#refresh-logs').on('click', function() {
        location.reload();
    });
    
    // Initialize filters
    applyLogFilters();
    
    // Handle notice dismiss buttons
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.ewcs-pro-alert').fadeOut(function() {
            $(this).remove();
        });
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + F for search
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 70) {
            e.preventDefault();
            $('#log-search').focus();
        }
        
        // Ctrl/Cmd + R for refresh
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 82) {
            if (window.location.href.indexOf('ewcs-pro-logs') !== -1) {
                e.preventDefault();
                location.reload();
            }
        }
    });
    
    // Auto-scroll to new logs if user is at bottom
    function checkAutoScroll() {
        const scrollTop = $(window).scrollTop();
        const windowHeight = $(window).height();
        const documentHeight = $(document).height();
        
        return (scrollTop + windowHeight >= documentHeight - 100);
    }
    
    // Highlight search terms
    function highlightSearchTerms() {
        const searchTerm = $('#log-search').val().toLowerCase();
        if (!searchTerm) return;
        
        $('.log-message').each(function() {
            const text = $(this).text();
            const highlightedText = text.replace(new RegExp(searchTerm, 'gi'), '<mark>        window.open(exportUrl</mark>');
            $(this).html(highlightedText);
        });
    }
    
    // Apply highlighting when search changes
    $('#log-search').on('input', function() {
        // Remove previous highlights
        $('.log-message mark').contents().unwrap();
        
        // Apply new highlights after a short delay
        setTimeout(highlightSearchTerms, 100);
    });
});
</script>

<style>
.table-responsive {
    overflow-x: auto;
    margin: 15px 0;
}

#logs-table {
    width: 100%;
    min-width: 800px;
}

#logs-table th {
    position: sticky;
    top: 0;
    background: #f7fafc;
    z-index: 10;
}

#logs-table td {
    vertical-align: top;
}

.log-message {
    line-height: 1.4;
}

.log-message mark {
    background: #fff3cd;
    padding: 2px 4px;
    border-radius: 2px;
}

details summary {
    outline: none;
}

details[open] summary {
    margin-bottom: 5px;
}

/* Status-specific styling */
.ewcs-duplicate-status.success {
    background: #d4edda;
    color: #155724;
}

.ewcs-duplicate-status.error {
    background: #f8d7da;
    color: #721c24;
}

.ewcs-duplicate-status.warning {
    background: #fff3cd;
    color: #856404;
}

.ewcs-duplicate-status.info {
    background: #d1ecf1;
    color: #0c5460;
}

/* Responsive table */
@media (max-width: 768px) {
    #logs-table {
        font-size: 12px;
    }
    
    #logs-table th,
    #logs-table td {
        padding: 8px 4px;
    }
    
    .log-message {
        max-width: 200px !important;
    }
}

/* Loading animation for new logs */
@keyframes slideInFromTop {
    0% {
        transform: translateY(-20px);
        opacity: 0;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}

.new-log-entry {
    animation: slideInFromTop 0.5s ease-out;
}

/* Sticky filter bar on scroll */
.filters-sticky {
    position: sticky;
    top: 32px; /* WordPress admin bar height */
    z-index: 100;
    background: white;
    padding: 15px 0;
    border-bottom: 1px solid #e2e8f0;
}
</style>