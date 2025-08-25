<?php
/**
 * WooCommerce Sync Pro - Duplicates Manager Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$duplicate_stats = $this->duplicate_handler->get_duplicate_stats();
?>

<div class="ewcs-pro-dashboard">
    <div class="ewcs-pro-container">
        <div class="ewcs-pro-header">
            <h1><?php _e('Duplicates Manager', 'enhanced-wc-sync-pro'); ?></h1>
            <p><?php _e('Manage and resolve duplicate products found during synchronization', 'enhanced-wc-sync-pro'); ?></p>
        </div>
        
        <!-- Statistics -->
        <div class="ewcs-pro-card">
            <h2><?php _e('Duplicate Statistics', 'enhanced-wc-sync-pro'); ?></h2>
            <div class="ewcs-pro-stats">
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value" style="color: #ed8936;"><?php echo $duplicate_stats['pending']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Pending', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value" style="color: #48bb78;"><?php echo $duplicate_stats['merged']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Merged', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value" style="color: #4299e1;"><?php echo $duplicate_stats['replaced']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Replaced', 'enhanced-wc-sync-pro'); ?></span>
                </div>
                <div class="ewcs-pro-stat">
                    <span class="ewcs-pro-stat-value" style="color: #718096;"><?php echo $duplicate_stats['skipped']; ?></span>
                    <span class="ewcs-pro-stat-label"><?php _e('Skipped', 'enhanced-wc-sync-pro'); ?></span>
                </div>
            </div>
            
            <div class="ewcs-pro-actions" style="margin-top: 20px;">
                <button id="scan-duplicates" class="ewcs-pro-btn ewcs-pro-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Scan for Duplicates', 'enhanced-wc-sync-pro'); ?>
                </button>
                
                <button id="auto-resolve" class="ewcs-pro-btn ewcs-pro-btn-secondary"
                        data-tooltip="<?php _e('Automatically resolve duplicates based on settings', 'enhanced-wc-sync-pro'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('Auto Resolve', 'enhanced-wc-sync-pro'); ?>
                </button>
                
                <button id="cleanup-resolved" class="ewcs-pro-btn ewcs-pro-btn-warning"
                        data-tooltip="<?php _e('Clean up resolved duplicates older than 30 days', 'enhanced-wc-sync-pro'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Cleanup Old', 'enhanced-wc-sync-pro'); ?>
                </button>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="ewcs-pro-card">
            <h2><?php _e('Filter Duplicates', 'enhanced-wc-sync-pro'); ?></h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label for="status-filter" class="ewcs-pro-form-label"><?php _e('Status', 'enhanced-wc-sync-pro'); ?></label>
                    <select id="status-filter" class="ewcs-pro-form-input">
                        <option value=""><?php _e('All Statuses', 'enhanced-wc-sync-pro'); ?></option>
                        <option value="pending"><?php _e('Pending', 'enhanced-wc-sync-pro'); ?></option>
                        <option value="merged"><?php _e('Merged', 'enhanced-wc-sync-pro'); ?></option>
                        <option value="replaced"><?php _e('Replaced', 'enhanced-wc-sync-pro'); ?></option>
                        <option value="skipped"><?php _e('Skipped', 'enhanced-wc-sync-pro'); ?></option>
                    </select>
                </div>
                
                <div>
                    <label for="search-duplicates" class="ewcs-pro-form-label"><?php _e('Search', 'enhanced-wc-sync-pro'); ?></label>
                    <input type="text" id="search-duplicates" class="ewcs-pro-form-input" 
                           placeholder="<?php _e('Search by product name or SKU...', 'enhanced-wc-sync-pro'); ?>">
                </div>
                
                <div style="display: flex; align-items: end;">
                    <button id="apply-filters" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                        <span class="dashicons dashicons-filter"></span>
                        <?php _e('Apply Filters', 'enhanced-wc-sync-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Duplicates Table -->
        <div class="ewcs-pro-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;"><?php _e('Duplicate Products', 'enhanced-wc-sync-pro'); ?></h2>
                <div>
                    <span id="duplicates-count"><?php echo count($duplicates); ?></span> <?php _e('duplicates found', 'enhanced-wc-sync-pro'); ?>
                </div>
            </div>
            
            <?php if (empty($duplicates)): ?>
            <div class="ewcs-pro-alert ewcs-pro-alert-success">
                <p><?php _e('No duplicate products found! Your product catalog is clean.', 'enhanced-wc-sync-pro'); ?></p>
                <p><?php _e('Use the "Scan for Duplicates" button to check for new duplicates.', 'enhanced-wc-sync-pro'); ?></p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="ewcs-duplicates-table">
                    <thead>
                        <tr>
                            <th><?php _e('Product', 'enhanced-wc-sync-pro'); ?></th>
                            <th><?php _e('SKU', 'enhanced-wc-sync-pro'); ?></th>
                            <th><?php _e('Remote ID', 'enhanced-wc-sync-pro'); ?></th>
                            <th><?php _e('Status', 'enhanced-wc-sync-pro'); ?></th>
                            <th><?php _e('Created', 'enhanced-wc-sync-pro'); ?></th>
                            <th><?php _e('Actions', 'enhanced-wc-sync-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="duplicates-table-body">
                        <?php foreach ($duplicates as $duplicate): 
                            $product = wc_get_product($duplicate->product_id);
                            $product_name = $product ? $product->get_name() : __('Product not found', 'enhanced-wc-sync-pro');
                        ?>
                        <tr data-duplicate-id="<?php echo $duplicate->id; ?>" data-status="<?php echo esc_attr($duplicate->status); ?>">
                            <td>
                                <strong><?php echo esc_html($product_name); ?></strong>
                                <?php if ($product): ?>
                                <br><small><a href="<?php echo get_edit_post_link($duplicate->product_id); ?>" target="_blank">
                                    <?php _e('Edit Product', 'enhanced-wc-sync-pro'); ?> #<?php echo $duplicate->product_id; ?>
                                </a></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html($duplicate->sku ?: '—'); ?>
                            </td>
                            <td>
                                <?php echo esc_html($duplicate->remote_id ?: '—'); ?>
                            </td>
                            <td>
                                <span class="ewcs-duplicate-status <?php echo esc_attr($duplicate->status); ?>">
                                    <?php echo esc_html(ucfirst($duplicate->status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y H:i', strtotime($duplicate->created_at)); ?>
                            </td>
                            <td>
                                <?php if ($duplicate->status === 'pending'): ?>
                                <div class="ewcs-duplicate-actions">
                                    <button class="ewcs-pro-btn ewcs-pro-btn-success resolve-duplicate" 
                                            data-duplicate-id="<?php echo $duplicate->id; ?>" 
                                            data-action="merge"
                                            data-tooltip="<?php _e('Merge with existing product', 'enhanced-wc-sync-pro'); ?>">
                                        <?php _e('Merge', 'enhanced-wc-sync-pro'); ?>
                                    </button>
                                    
                                    <button class="ewcs-pro-btn ewcs-pro-btn-warning resolve-duplicate" 
                                            data-duplicate-id="<?php echo $duplicate->id; ?>" 
                                            data-action="replace"
                                            data-tooltip="<?php _e('Replace existing with this product', 'enhanced-wc-sync-pro'); ?>">
                                        <?php _e('Replace', 'enhanced-wc-sync-pro'); ?>
                                    </button>
                                    
                                    <button class="ewcs-pro-btn ewcs-pro-btn-secondary resolve-duplicate" 
                                            data-duplicate-id="<?php echo $duplicate->id; ?>" 
                                            data-action="skip"
                                            data-tooltip="<?php _e('Keep both products', 'enhanced-wc-sync-pro'); ?>">
                                        <?php _e('Skip', 'enhanced-wc-sync-pro'); ?>
                                    </button>
                                </div>
                                <?php else: ?>
                                <em style="color: #666;">
                                    <?php 
                                    printf(__('Resolved: %s', 'enhanced-wc-sync-pro'), 
                                           $duplicate->resolved_at ? date('M j, Y', strtotime($duplicate->resolved_at)) : __('Unknown', 'enhanced-wc-sync-pro')); 
                                    ?>
                                </em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count(array_filter($duplicates, function($d) { return $d->status === 'pending'; })) > 1): ?>
            <div class="ewcs-pro-actions" style="margin-top: 20px;">
                <button id="bulk-resolve-merge" class="ewcs-pro-btn ewcs-pro-btn-success">
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php _e('Bulk Merge Pending', 'enhanced-wc-sync-pro'); ?>
                </button>
                
                <button id="bulk-resolve-skip" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Bulk Skip Pending', 'enhanced-wc-sync-pro'); ?>
                </button>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Help Information -->
        <div class="ewcs-pro-card">
            <h2><?php _e('Duplicate Resolution Guide', 'enhanced-wc-sync-pro'); ?></h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="color: #48bb78; margin-bottom: 10px;">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('Merge', 'enhanced-wc-sync-pro'); ?>
                    </h4>
                    <p><?php _e('Combines data from both products into one. The duplicate product is removed and its data is merged with the original product.', 'enhanced-wc-sync-pro'); ?></p>
                    <small style="color: #666;"><?php _e('Best for: Products with different information that should be combined', 'enhanced-wc-sync-pro'); ?></small>
                </div>
                
                <div>
                    <h4 style="color: #ed8936; margin-bottom: 10px;">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Replace', 'enhanced-wc-sync-pro'); ?>
                    </h4>
                    <p><?php _e('Replaces the original product with the duplicate. The original product is removed and the duplicate becomes the main product.', 'enhanced-wc-sync-pro'); ?></p>
                    <small style="color: #666;"><?php _e('Best for: When the duplicate has more complete or accurate information', 'enhanced-wc-sync-pro'); ?></small>
                </div>
                
                <div>
                    <h4 style="color: #718096; margin-bottom: 10px;">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Skip', 'enhanced-wc-sync-pro'); ?>
                    </h4>
                    <p><?php _e('Keeps both products as separate items. This marks the duplicate as resolved without removing either product.', 'enhanced-wc-sync-pro'); ?></p>
                    <small style="color: #666;"><?php _e('Best for: When products are actually different despite appearing as duplicates', 'enhanced-wc-sync-pro'); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Filter functionality
    $('#apply-filters').on('click', function() {
        const statusFilter = $('#status-filter').val();
        const searchTerm = $('#search-duplicates').val().toLowerCase();
        
        let visibleCount = 0;
        $('#duplicates-table-body tr').each(function() {
            const row = $(this);
            const status = row.data('status');
            const productName = row.find('td:first').text().toLowerCase();
            const sku = row.find('td:nth-child(2)').text().toLowerCase();
            
            let showRow = true;
            
            // Status filter
            if (statusFilter && status !== statusFilter) {
                showRow = false;
            }
            
            // Search filter
            if (searchTerm && !productName.includes(searchTerm) && !sku.includes(searchTerm)) {
                showRow = false;
            }
            
            if (showRow) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });
        
        $('#duplicates-count').text(visibleCount);
    });
    
    // Real-time search
    $('#search-duplicates').on('input', function() {
        $('#apply-filters').click();
    });
    
    $('#status-filter').on('change', function() {
        $('#apply-filters').click();
    });
    
    // Scan for duplicates
    $('#scan-duplicates').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).html('<span class="ewcs-spinner"></span> Scanning...');
        
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
                    if (found > 0) {
                        alert(`Found ${found} potential duplicates. Refreshing page...`);
                        location.reload();
                    } else {
                        alert('No new duplicates found!');
                    }
                } else {
                    alert('Scan failed: ' + response.data);
                }
            },
            error: function() {
                alert('Scan failed. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Scan for Duplicates');
            }
        });
    });
    
    // Auto resolve duplicates
    $('#auto-resolve').on('click', function() {
        if (!confirm('This will automatically resolve all pending duplicates based on your plugin settings. Continue?')) {
            return;
        }
        
        const button = $(this);
        button.prop('disabled', true).html('<span class="ewcs-spinner"></span> Resolving...');
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_auto_resolve_duplicates',
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(`Resolved ${response.data.resolved} duplicates automatically.`);
                    location.reload();
                } else {
                    alert('Auto-resolve failed: ' + response.data);
                }
            },
            error: function() {
                alert('Auto-resolve failed. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).html('<span class="dashicons dashicons-admin-generic"></span> Auto Resolve');
            }
        });
    });
    
    // Cleanup resolved duplicates
    $('#cleanup-resolved').on('click', function() {
        if (!confirm('This will permanently remove resolved duplicates older than 30 days. Continue?')) {
            return;
        }
        
        const button = $(this);
        button.prop('disabled', true).html('<span class="ewcs-spinner"></span> Cleaning...');
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_cleanup_duplicates',
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(`Cleaned up ${response.data.cleaned} old duplicate records.`);
                    location.reload();
                } else {
                    alert('Cleanup failed: ' + response.data);
                }
            },
            error: function() {
                alert('Cleanup failed. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Cleanup Old');
            }
        });
    });
    
    // Bulk resolve functions
    $('#bulk-resolve-merge').on('click', function() {
        const pendingRows = $('#duplicates-table-body tr[data-status="pending"]');
        if (pendingRows.length === 0) {
            alert('No pending duplicates to resolve.');
            return;
        }
        
        if (!confirm(`This will merge all ${pendingRows.length} pending duplicates. Continue?`)) {
            return;
        }
        
        bulkResolve('merge');
    });
    
    $('#bulk-resolve-skip').on('click', function() {
        const pendingRows = $('#duplicates-table-body tr[data-status="pending"]');
        if (pendingRows.length === 0) {
            alert('No pending duplicates to resolve.');
            return;
        }
        
        if (!confirm(`This will skip all ${pendingRows.length} pending duplicates. Continue?`)) {
            return;
        }
        
        bulkResolve('skip');
    });
    
    function bulkResolve(action) {
        const pendingRows = $('#duplicates-table-body tr[data-status="pending"]');
        const duplicateIds = [];
        
        pendingRows.each(function() {
            duplicateIds.push($(this).data('duplicate-id'));
        });
        
        if (duplicateIds.length === 0) {
            return;
        }
        
        const button = $(`#bulk-resolve-${action}`);
        button.prop('disabled', true).html('<span class="ewcs-spinner"></span> Processing...');
        
        // Process duplicates one by one
        let processed = 0;
        let errors = 0;
        
        function processNext() {
            if (processed >= duplicateIds.length) {
                // All done
                button.prop('disabled', false).html(button.data('original-text'));
                alert(`Bulk ${action} completed: ${processed - errors} successful, ${errors} errors.`);
                location.reload();
                return;
            }
            
            const duplicateId = duplicateIds[processed];
            $.ajax({
                url: ewcs_pro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ewcs_resolve_duplicate',
                    duplicate_id: duplicateId,
                    action_type: action,
                    nonce: ewcs_pro_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $(`tr[data-duplicate-id="${duplicateId}"]`).fadeOut();
                    } else {
                        errors++;
                        console.error('Failed to resolve duplicate:', duplicateId, response.data);
                    }
                },
                error: function() {
                    errors++;
                    console.error('Ajax error resolving duplicate:', duplicateId);
                },
                complete: function() {
                    processed++;
                    // Update button text with progress
                    button.html(`<span class="ewcs-spinner"></span> Processing... (${processed}/${duplicateIds.length})`);
                    processNext();
                }
            });
        }
        
        // Store original button text
        button.data('original-text', button.html());
        processNext();
    }
});
</script>