/**
 * Enhanced WooCommerce Sync Pro - Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    let importInProgress = false;
    let importInterval = null;
    let duplicateResolutionInProgress = false;
    
    // Initialize
    init();
    
    function init() {
        bindEvents();
        initTooltips();
        checkImportStatus();
    }
    
    function bindEvents() {
        // Test connection
        $(document).on('click', '#test-connection', handleTestConnection);
        
        // Category sync
        $(document).on('click', '#sync-categories, #refresh-categories', handleSyncCategories);
        
        // Category selection
        $(document).on('click', '#select-all-categories', selectAllCategories);
        $(document).on('click', '#deselect-all-categories', deselectAllCategories);
        
        // Import management
        $(document).on('click', '#start-import', handleStartImport);
        $(document).on('click', '#cancel-import', handleCancelImport);
        
        // Stock and price sync
        $(document).on('click', '#sync-stock-prices', handleSyncStockPrices);
        
        // Duplicate management
        $(document).on('click', '#check-duplicates', handleCheckDuplicates);
        $(document).on('click', '.resolve-duplicate', handleResolveDuplicate);
        
        // Import mode selection
        $(document).on('change', 'input[name="import_mode"]', handleImportModeChange);
        
        // Logs management
        $(document).on('click', '#clear-logs', handleClearLogs);
        $(document).on('click', '#refresh-logs', () => location.reload());
        $(document).on('click', '#export-logs', handleExportLogs);
        
        // Auto-refresh
        if (window.location.href.indexOf('ewcs-pro') !== -1) {
            setInterval(autoRefreshStatus, 30000); // Every 30 seconds
        }
    }
    
    function initTooltips() {
        $('[data-tooltip]').addClass('ewcs-tooltip');
    }
    
    function checkImportStatus() {
        if (window.location.href.indexOf('ewcs-pro-import') !== -1) {
            setTimeout(loadCategoriesGrid, 1000);
            setTimeout(checkForActiveImport, 2000);
        }
    }
    
    // Utility functions
    function addLog(message, type = 'info') {
        const timestamp = new Date().toLocaleString();
        const logClass = `ewcs-pro-log-${type}`;
        const logEntry = `<div class="${logClass} ewcs-pro-log-item">[${timestamp}] ${message}</div>`;
        
        let logContainer = $('#ewcs-pro-log, #import-log');
        if (logContainer.length === 0) {
            logContainer = $('<div id="ewcs-pro-log" class="ewcs-pro-log"></div>').appendTo('.ewcs-pro-container');
        }
        
        logContainer.show().append(logEntry);
        logContainer.scrollTop(logContainer[0].scrollHeight);
    }
    
    function setButtonLoading(button, loading) {
        if (loading) {
            button.addClass('ewcs-pro-loading').prop('disabled', true);
            const spinner = '<span class="ewcs-spinner"></span>';
            button.data('original-html', button.html()).html(spinner + ' ' + ewcs_pro_ajax.strings.processing);
        } else {
            button.removeClass('ewcs-pro-loading').prop('disabled', false);
            if (button.data('original-html')) {
                button.html(button.data('original-html'));
            }
        }
    }
    
    function updateProgress(current, total, status = '') {
        const percentage = Math.round((current / total) * 100);
        $('#import-progress').show().addClass('ewcs-fade-in');
        $('.ewcs-pro-progress-fill').css('width', percentage + '%');
        $('#progress-percentage').text(percentage + '%');
        
        if (status) {
            $('#progress-status').text(status);
        }
    }
    
    function formatTime(seconds) {
        if (seconds < 60) {
            return seconds + 's';
        } else if (seconds < 3600) {
            return Math.round(seconds / 60) + 'm';
        } else {
            return Math.round(seconds / 3600) + 'h';
        }
    }
    
    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="ewcs-pro-alert ewcs-pro-alert-${type} ewcs-fade-in" style="position: fixed; top: 32px; right: 20px; z-index: 9999; max-width: 400px;">
                ${message}
                <button type="button" class="notice-dismiss" style="float: right; margin-left: 10px;">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
        
        // Manual close
        notification.find('.notice-dismiss').on('click', () => {
            notification.fadeOut(() => notification.remove());
        });
    }
    
    // Event handlers
    function handleTestConnection() {
        const button = $(this);
        setButtonLoading(button, true);
        addLog('Testing connection to remote site...');
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_test_connection',
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    addLog(response.data, 'success');
                    showNotification('Connection successful!', 'success');
                } else {
                    addLog('Connection failed: ' + response.data, 'error');
                    showNotification('Connection failed: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                addLog('Ajax request failed: ' + error, 'error');
                showNotification('Connection test failed', 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
            }
        });
    }
    
    function handleSyncCategories() {
        const button = $(this);
        setButtonLoading(button, true);
        addLog('Loading categories from remote site...');
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_sync_categories',
                nonce: ewcs_pro_ajax.nonce
            },
            timeout: 60000,
            success: function(response) {
                if (response.success) {
                    addLog(response.data.message, 'success');
                    
                    if (response.data.categories && response.data.categories.length > 0) {
                        displayCategories(response.data.categories);
                        $('#categories-section').show().addClass('ewcs-fade-in');
                        $('#refresh-categories').show();
                        showNotification(`${response.data.categories.length} categories loaded successfully!`, 'success');
                    } else {
                        addLog('No categories were returned from the remote site', 'warning');
                        loadCategoriesGrid();
                    }
                } else {
                    addLog('Failed to sync categories: ' + response.data, 'error');
                    showNotification('Failed to sync categories: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    addLog('Category sync timed out. Please check your connection and try again.', 'error');
                    showNotification('Category sync timed out', 'error');
                } else {
                    addLog('Ajax request failed: ' + error, 'error');
                    showNotification('Failed to load categories', 'error');
                }
            },
            complete: function() {
                setButtonLoading(button, false);
            }
        });
    }
    
    function loadCategoriesGrid() {
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_get_categories',
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayCategories(response.data);
                    if (response.data.length > 0) {
                        $('#categories-section').show();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error loading categories:', error);
            }
        });
    }
    
    function displayCategories(categories) {
        const grid = $('#categories-grid');
        grid.empty();
        
        if (!categories || categories.length === 0) {
            grid.append('<div class="ewcs-pro-alert ewcs-pro-alert-info">No categories found. Please sync categories first.</div>');
            return;
        }
        
        categories.forEach(function(category, index) {
            const categoryItem = $(`
                <div class="ewcs-pro-category-item ewcs-fade-in" style="animation-delay: ${index * 0.1}s">
                    <input type="checkbox" id="cat-${category.id}" value="${category.id}" name="categories[]">
                    <div class="ewcs-pro-category-info">
                        <div class="ewcs-pro-category-name">${category.name}</div>
                        <div class="ewcs-pro-category-count">${category.count || 0} products</div>
                        ${category.remote_id ? `<small>Remote ID: ${category.remote_id}</small>` : ''}
                    </div>
                </div>
            `);
            grid.append(categoryItem);
        });
    }
    
    function selectAllCategories() {
        $('#categories-grid input[type="checkbox"]').prop('checked', true);
        showNotification('All categories selected', 'info');
    }
    
    function deselectAllCategories() {
        $('#categories-grid input[type="checkbox"]').prop('checked', false);
        showNotification('All categories deselected', 'info');
    }
    
    function handleImportModeChange() {
        const selectedMode = $(this).val();
        const modeContainer = $(this).closest('.ewcs-import-mode');
        
        // Remove selected class from all modes
        $('.ewcs-import-mode').removeClass('selected');
        
        // Add selected class to current mode
        modeContainer.addClass('selected');
        
        // Show/hide mode-specific options
        $('.import-mode-options').hide();
        $(`.import-mode-options[data-mode="${selectedMode}"]`).show().addClass('ewcs-fade-in');
    }
    
    function handleStartImport() {
        const selectedCategories = $('#categories-grid input[type="checkbox"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        const importMode = $('input[name="import_mode"]:checked').val() || 'skip_duplicates';
        
        if (selectedCategories.length === 0) {
            alert(ewcs_pro_ajax.strings.select_categories);
            return;
        }
        
        const button = $(this);
        setButtonLoading(button, true);
        importInProgress = true;
        $('#cancel-import').show();
        
        addLog(`Starting import for ${selectedCategories.length} categories (${importMode} mode)...`);
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_start_batch_import',
                categories: selectedCategories,
                import_mode: importMode,
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    addLog(`Import prepared: ${response.data.total_products} products in ${response.data.total_batches} batches`, 'success');
                    
                    // Update progress display
                    $('#total-products').text(response.data.total_products);
                    $('#total-batches').text(response.data.total_batches);
                    $('#current-batch').text(0);
                    $('#imported-count').text(0);
                    $('#updated-count').text(0);
                    $('#skipped-count').text(0);
                    
                    updateProgress(0, response.data.total_products, 'Starting import...');
                    
                    // Start processing batches
                    startBatchProcessing();
                    
                    showNotification(`Import started: ${response.data.total_products} products to process`, 'success');
                } else {
                    addLog('Failed to start import: ' + response.data, 'error');
                    importInProgress = false;
                    $('#cancel-import').hide();
                    showNotification('Failed to start import: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                addLog('Ajax request failed: ' + error, 'error');
                importInProgress = false;
                $('#cancel-import').hide();
                showNotification('Failed to start import', 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
            }
        });
    }
    
    function startBatchProcessing() {
        processBatch();
        importInterval = setInterval(function() {
            if (importInProgress) {
                processBatch();
            } else {
                clearInterval(importInterval);
            }
        }, 2000);
    }
    
    function processBatch() {
        if (!importInProgress) return;
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_process_batch',
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update progress counters
                    $('#current-batch').text(data.current_batch);
                    $('#imported-count').text(data.imported_count);
                    $('#updated-count').text(data.updated_count || 0);
                    $('#skipped-count').text(data.skipped_count || 0);
                    
                    const totalProducts = parseInt($('#total-products').text());
                    const processedProducts = data.imported_count + (data.updated_count || 0) + (data.skipped_count || 0);
                    
                    updateProgress(processedProducts, totalProducts, 
                        `Processing batch ${data.current_batch}/${$('#total-batches').text()}...`);
                    
                    // Calculate estimated time remaining
                    const totalBatches = parseInt($('#total-batches').text());
                    const remainingBatches = totalBatches - data.current_batch;
                    const estimatedTimeRemaining = Math.round((remainingBatches * 2000) / 1000);
                    
                    if (estimatedTimeRemaining > 0) {
                        $('#time-remaining').text(formatTime(estimatedTimeRemaining));
                    } else {
                        $('#time-remaining').text('-');
                    }
                    
                    // Log batch completion with detailed results
                    if (data.batch_result) {
                        const result = data.batch_result;
                        let logMessage = `Batch ${data.current_batch} completed: `;
                        let details = [];
                        
                        if (result.imported > 0) details.push(`${result.imported} imported`);
                        if (result.updated > 0) details.push(`${result.updated} updated`);
                        if (result.skipped > 0) details.push(`${result.skipped} skipped`);
                        if (result.errors > 0) details.push(`${result.errors} errors`);
                        
                        logMessage += details.join(', ');
                        addLog(logMessage, result.errors > 0 ? 'warning' : 'success');
                    }
                    
                    // Check if import is complete
                    if (data.is_complete) {
                        completeImport(data);
                    }
                } else {
                    addLog('Batch processing failed: ' + response.data, 'error');
                    importInProgress = false;
                    $('#cancel-import').hide();
                    showNotification('Batch processing failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                addLog('Batch processing ajax failed: ' + error, 'error');
                importInProgress = false;
                $('#cancel-import').hide();
                showNotification('Batch processing failed', 'error');
            }
        });
    }
    
    function completeImport(data) {
        importInProgress = false;
        clearInterval(importInterval);
        $('#cancel-import').hide();
        
        const totalProcessed = data.imported_count + (data.updated_count || 0) + (data.skipped_count || 0);
        updateProgress(totalProcessed, totalProcessed, ewcs_pro_ajax.strings.complete);
        
        let completionMessage = `Import completed! `;
        let details = [];
        
        if (data.imported_count > 0) details.push(`${data.imported_count} imported`);
        if (data.updated_count > 0) details.push(`${data.updated_count} updated`);
        if (data.skipped_count > 0) details.push(`${data.skipped_count} skipped`);
        if (data.error_count > 0) details.push(`${data.error_count} errors`);
        
        completionMessage += details.join(', ');
        
        addLog(completionMessage, data.error_count > 0 ? 'warning' : 'success');
        showNotification(completionMessage, data.error_count > 0 ? 'warning' : 'success');
        
        // Refresh page stats after delay
        setTimeout(function() {
            if (window.location.href.indexOf('ewcs-pro-import') === -1) {
                location.reload();
            }
        }, 3000);
    }
    
    function handleCancelImport() {
        if (confirm(ewcs_pro_ajax.strings.cancel_confirm)) {
            const button = $(this);
            setButtonLoading(button, true);
            
            $.ajax({
                url: ewcs_pro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ewcs_cancel_import',
                    nonce: ewcs_pro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        addLog(response.data, 'info');
                        importInProgress = false;
                        clearInterval(importInterval);
                        $('#import-progress').hide();
                        button.hide();
                        showNotification('Import cancelled', 'info');
                    }
                },
                complete: function() {
                    setButtonLoading(button, false);
                }
            });
        }
    }
    
    function handleSyncStockPrices() {
        const button = $(this);
        setButtonLoading(button, true);
        addLog('Starting stock and price sync...');
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_sync_stock_prices',
                nonce: ewcs_pro_ajax.nonce
            },
            timeout: 300000, // 5 minutes
            success: function(response) {
                if (response.success) {
                    addLog(response.data, 'success');
                    showNotification('Stock and prices synced successfully!', 'success');
                } else {
                    addLog('Stock/price sync failed: ' + response.data, 'error');
                    showNotification('Stock/price sync failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    addLog('Sync timed out - check logs page for details', 'error');
                    showNotification('Sync timed out - check logs for details', 'warning');
                } else {
                    addLog('Ajax request failed: ' + error, 'error');
                    showNotification('Sync failed', 'error');
                }
            },
            complete: function() {
                setButtonLoading(button, false);
                // Refresh stats after delay
                setTimeout(function() {
                    if (window.location.href.indexOf('page=ewcs-pro') !== -1 && 
                        window.location.href.indexOf('import') === -1) {
                        location.reload();
                    }
                }, 2000);
            }
        });
    }
    
    function handleCheckDuplicates() {
        const button = $(this);
        setButtonLoading(button, true);
        addLog('Scanning for duplicates...');
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_check_duplicates',
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const found = response.data.found;
                    addLog(`Duplicate scan completed: ${found} potential duplicates found`, 
                           found > 0 ? 'warning' : 'success');
                    
                    if (found > 0) {
                        showNotification(`Found ${found} potential duplicates`, 'warning');
                        // Optionally display duplicates or redirect to duplicates page
                        setTimeout(() => {
                            if (confirm('Duplicates found. Go to Duplicates Manager?')) {
                                window.location.href = 'admin.php?page=ewcs-pro-duplicates';
                            }
                        }, 1000);
                    } else {
                        showNotification('No duplicates found!', 'success');
                    }
                } else {
                    addLog('Duplicate scan failed: ' + response.data, 'error');
                    showNotification('Duplicate scan failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                addLog('Duplicate scan ajax failed: ' + error, 'error');
                showNotification('Duplicate scan failed', 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
            }
        });
    }
    
    function handleResolveDuplicate() {
        if (duplicateResolutionInProgress) return;
        
        const button = $(this);
        const duplicateId = button.data('duplicate-id');
        const actionType = button.data('action');
        
        if (!duplicateId || !actionType) {
            showNotification('Invalid duplicate resolution parameters', 'error');
            return;
        }
        
        if (!confirm(ewcs_pro_ajax.strings.duplicate_resolve_confirm)) {
            return;
        }
        
        duplicateResolutionInProgress = true;
        setButtonLoading(button, true);
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_resolve_duplicate',
                duplicate_id: duplicateId,
                action_type: actionType,
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Duplicate resolved successfully!', 'success');
                    // Remove the resolved duplicate row
                    button.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    showNotification('Failed to resolve duplicate: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Failed to resolve duplicate', 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
                duplicateResolutionInProgress = false;
            }
        });
    }
    
    function handleClearLogs() {
        if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
            const button = $(this);
            setButtonLoading(button, true);
            
            $.ajax({
                url: ewcs_pro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ewcs_pro_clear_logs',
                    nonce: ewcs_pro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Logs cleared successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('Failed to clear logs', 'error');
                    }
                },
                error: function() {
                    showNotification('Failed to clear logs', 'error');
                },
                complete: function() {
                    setButtonLoading(button, false);
                }
            });
        }
    }
    
    function handleExportLogs() {
        const filters = {};
        
        // Get filter values if they exist
        const actionFilter = $('#log-action-filter').val();
        const statusFilter = $('#log-status-filter').val();
        const dateFromFilter = $('#log-date-from').val();
        const dateToFilter = $('#log-date-to').val();
        
        if (actionFilter) filters.action = actionFilter;
        if (statusFilter) filters.status = statusFilter;
        if (dateFromFilter) filters.date_from = dateFromFilter;
        if (dateToFilter) filters.date_to = dateToFilter;
        
        // Build export URL
        let exportUrl = ewcs_pro_ajax.ajax_url + '?action=ewcs_export_logs&nonce=' + ewcs_pro_ajax.nonce;
        
        Object.keys(filters).forEach(key => {
            exportUrl += '&' + key + '=' + encodeURIComponent(filters[key]);
        });
        
        // Trigger download
        window.open(exportUrl, '_blank');
        showNotification('Export started...', 'info');
    }
    
    function checkForActiveImport() {
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_get_import_stats',
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.current_batch !== undefined) {
                    const data = response.data;
                    if (data.current_batch < data.total_batches) {
                        // Resume import display
                        importInProgress = true;
                        $('#cancel-import').show();
                        $('#total-products').text(data.total_products);
                        $('#total-batches').text(data.total_batches);
                        $('#current-batch').text(data.current_batch);
                        $('#imported-count').text(data.imported_count);
                        $('#updated-count').text(data.updated_count || 0);
                        $('#skipped-count').text(data.skipped_count || 0);
                        
                        const processed = data.imported_count + (data.updated_count || 0) + (data.skipped_count || 0);
                        updateProgress(processed, data.total_products, 'Resuming import...');
                        
                        startBatchProcessing();
                        showNotification('Resuming interrupted import...', 'info');
                    }
                }
            }
        });
    }
    
    function autoRefreshStatus() {
        if (!importInProgress) {
            // Refresh stats periodically
            const statsElements = $('.ewcs-pro-stat-value');
            if (statsElements.length > 0) {
                statsElements.addClass('ewcs-pulse');
                setTimeout(() => {
                    statsElements.removeClass('ewcs-pulse');
                }, 2000);
            }
        }
    }
    
    // Prevent leaving page during import
    window.addEventListener('beforeunload', function(e) {
        if (importInProgress) {
            const confirmationMessage = 'Import is in progress. Are you sure you want to leave?';
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });
    
    // Export settings functionality
    $(document).on('click', '#export-settings', function() {
        window.open(ewcs_pro_ajax.ajax_url + '?action=ewcs_export_settings&nonce=' + ewcs_pro_ajax.nonce, '_blank');
        showNotification('Settings export started...', 'info');
    });
    
    // Settings form enhancements
    $(document).on('change', '#sync_interval', function() {
        const interval = $(this).val();
        const warning = $('#sync-interval-warning');
        
        if (interval === 'hourly') {
            if (warning.length === 0) {
                $(this).after('<p id="sync-interval-warning" class="ewcs-pro-form-help" style="color: #ed8936;">⚠️ Hourly sync may impact site performance. Monitor your server resources.</p>');
            }
        } else {
            warning.remove();
        }
    });
    
    // Batch size validation
    $(document).on('input', '#batch_size', function() {
        const value = parseInt($(this).val());
        const min = parseInt($(this).attr('min'));
        const max = parseInt($(this).attr('max'));
        
        if (value < min) {
            $(this).val(min);
            showNotification(`Minimum batch size is ${min}`, 'warning');
        } else if (value > max) {
            $(this).val(max);
            showNotification(`Maximum batch size is ${max}`, 'warning');
        }
    });
    
    // Advanced search/filter functionality for logs
    $(document).on('input', '#log-search', function() {
        const searchTerm = $(this).val().toLowerCase();
        const logItems = $('.ewcs-pro-log-item');
        
        logItems.each(function() {
            const logText = $(this).text().toLowerCase();
            if (logText.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + R for refresh
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 82) {
            if (window.location.href.indexOf('ewcs-pro') !== -1) {
                e.preventDefault();
                location.reload();
            }
        }
        
        // Escape to cancel import
        if (e.keyCode === 27 && importInProgress) {
            $('#cancel-import').click();
        }
    });
    
    // Initialize any existing tooltips on page load
    setTimeout(initTooltips, 500);
});