<?php
/**
 * Enhanced WooCommerce Sync Pro - Product Edit Handler
 * 
 * Handles single product edit page enhancements and sync functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWCS_Product_Edit_Handler {
    
    public function __construct() {
        // Constructor can be used for any initialization
    }
    
    /**
     * Add sync meta box to product edit page
     */
    public function add_sync_meta_box() {
        add_meta_box(
            'ewcs-pro-sync-info',
            __('WC Sync Pro Information', 'enhanced-wc-sync-pro'),
            array($this, 'render_sync_meta_box'),
            'product',
            'side',
            'default'
        );
    }
    
    /**
     * Render sync meta box content
     */
    public function render_sync_meta_box($post) {
        wp_nonce_field('ewcs_pro_product_meta', 'ewcs_pro_product_nonce');
        
        $remote_id = get_post_meta($post->ID, '_ewcs_pro_remote_id', true);
        $last_sync = get_post_meta($post->ID, '_ewcs_pro_last_sync', true);
        $import_mode = get_post_meta($post->ID, '_ewcs_pro_import_mode', true);
        $sync_enabled = get_post_meta($post->ID, '_ewcs_pro_sync_enabled', true);
        $sync_fields = get_post_meta($post->ID, '_ewcs_pro_sync_fields', true);
        
        if (!$sync_fields) {
            $sync_fields = array('price', 'stock', 'description');
        }
        
        ?>
        <div class="ewcs-pro-sync-meta">
            <style>
                .ewcs-pro-sync-meta { font-size: 13px; }
                .ewcs-sync-field { margin-bottom: 15px; }
                .ewcs-sync-field label { display: block; font-weight: 600; margin-bottom: 5px; }
                .ewcs-sync-field input, .ewcs-sync-field select { width: 100%; }
                .ewcs-sync-status { padding: 8px; border-radius: 4px; margin-bottom: 15px; }
                .ewcs-sync-status.synced { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
                .ewcs-sync-status.not-synced { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
                .ewcs-sync-actions { margin-top: 15px; }
                .ewcs-sync-btn { background: #0073aa; color: white; border: none; padding: 8px 15px; border-radius: 3px; cursor: pointer; margin-right: 5px; }
                .ewcs-sync-btn:hover { background: #005a87; }
                .ewcs-sync-btn.secondary { background: #6c757d; }
                .ewcs-sync-btn.danger { background: #dc3545; }
                .ewcs-sync-checkboxes { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-top: 5px; }
                .ewcs-sync-checkboxes label { font-weight: normal; display: flex; align-items: center; gap: 5px; }
                .ewcs-sync-info { background: #e7f3ff; padding: 10px; border-radius: 4px; margin: 10px 0; }
                .ewcs-last-sync { color: #666; font-size: 12px; }
            </style>
            
            <!-- Sync Status -->
            <div class="ewcs-sync-status <?php echo $remote_id ? 'synced' : 'not-synced'; ?>">
                <?php if ($remote_id): ?>
                    <strong>✓ <?php _e('Synced Product', 'enhanced-wc-sync-pro'); ?></strong><br>
                    <?php _e('Remote ID:', 'enhanced-wc-sync-pro'); ?> <code><?php echo esc_html($remote_id); ?></code>
                    <?php if ($last_sync): ?>
                        <div class="ewcs-last-sync">
                            <?php _e('Last sync:', 'enhanced-wc-sync-pro'); ?> <?php echo date('M j, Y H:i', $last_sync); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <strong>⚠ <?php _e('Not Synced', 'enhanced-wc-sync-pro'); ?></strong><br>
                    <?php _e('This product is not linked to a remote product.', 'enhanced-wc-sync-pro'); ?>
                <?php endif; ?>
            </div>
            
            <!-- Remote ID Field -->
            <div class="ewcs-sync-field">
                <label for="ewcs_remote_id"><?php _e('Remote Product ID', 'enhanced-wc-sync-pro'); ?></label>
                <input type="number" 
                       id="ewcs_remote_id" 
                       name="ewcs_remote_id" 
                       value="<?php echo esc_attr($remote_id); ?>" 
                       placeholder="<?php _e('Enter remote product ID', 'enhanced-wc-sync-pro'); ?>">
                <small><?php _e('ID of the product on the remote site', 'enhanced-wc-sync-pro'); ?></small>
            </div>
            
            <!-- Sync Enabled -->
            <div class="ewcs-sync-field">
                <label>
                    <input type="checkbox" 
                           name="ewcs_sync_enabled" 
                           value="1" 
                           <?php checked($sync_enabled, '1'); ?>>
                    <?php _e('Enable Auto Sync', 'enhanced-wc-sync-pro'); ?>
                </label>
                <small><?php _e('Automatically sync this product during scheduled syncs', 'enhanced-wc-sync-pro'); ?></small>
            </div>
            
            <!-- Sync Fields -->
            <div class="ewcs-sync-field">
                <label><?php _e('Fields to Sync', 'enhanced-wc-sync-pro'); ?></label>
                <div class="ewcs-sync-checkboxes">
                    <label>
                        <input type="checkbox" 
                               name="ewcs_sync_fields[]" 
                               value="price" 
                               <?php checked(in_array('price', $sync_fields)); ?>>
                        <?php _e('Price', 'enhanced-wc-sync-pro'); ?>
                    </label>
                    <label>
                        <input type="checkbox" 
                               name="ewcs_sync_fields[]" 
                               value="stock" 
                               <?php checked(in_array('stock', $sync_fields)); ?>>
                        <?php _e('Stock', 'enhanced-wc-sync-pro'); ?>
                    </label>
                    <label>
                        <input type="checkbox" 
                               name="ewcs_sync_fields[]" 
                               value="description" 
                               <?php checked(in_array('description', $sync_fields)); ?>>
                        <?php _e('Description', 'enhanced-wc-sync-pro'); ?>
                    </label>
                    <label>
                        <input type="checkbox" 
                               name="ewcs_sync_fields[]" 
                               value="images" 
                               <?php checked(in_array('images', $sync_fields)); ?>>
                        <?php _e('Images', 'enhanced-wc-sync-pro'); ?>
                    </label>
                    <label>
                        <input type="checkbox" 
                               name="ewcs_sync_fields[]" 
                               value="categories" 
                               <?php checked(in_array('categories', $sync_fields)); ?>>
                        <?php _e('Categories', 'enhanced-wc-sync-pro'); ?>
                    </label>
                    <label>
                        <input type="checkbox" 
                               name="ewcs_sync_fields[]" 
                               value="attributes" 
                               <?php checked(in_array('attributes', $sync_fields)); ?>>
                        <?php _e('Attributes', 'enhanced-wc-sync-pro'); ?>
                    </label>
                </div>
            </div>
            
            <!-- Import Mode -->
            <div class="ewcs-sync-field">
                <label for="ewcs_import_mode"><?php _e('Import Mode', 'enhanced-wc-sync-pro'); ?></label>
                <select name="ewcs_import_mode" id="ewcs_import_mode">
                    <option value="api" <?php selected($import_mode, 'api'); ?>><?php _e('API Import', 'enhanced-wc-sync-pro'); ?></option>
                    <option value="manual" <?php selected($import_mode, 'manual'); ?>><?php _e('Manual Entry', 'enhanced-wc-sync-pro'); ?></option>
                    <option value="csv" <?php selected($import_mode, 'csv'); ?>><?php _e('CSV Import', 'enhanced-wc-sync-pro'); ?></option>
                </select>
            </div>
            
            <?php if ($remote_id): ?>
            <!-- Sync Information -->
            <div class="ewcs-sync-info">
                <h4 style="margin: 0 0 10px 0;"><?php _e('Sync Information', 'enhanced-wc-sync-pro'); ?></h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px;">
                    <div>
                        <strong><?php _e('Created:', 'enhanced-wc-sync-pro'); ?></strong><br>
                        <?php echo get_the_date('M j, Y H:i', $post->ID); ?>
                    </div>
                    <div>
                        <strong><?php _e('Modified:', 'enhanced-wc-sync-pro'); ?></strong><br>
                        <?php echo get_the_modified_date('M j, Y H:i', $post->ID); ?>
                    </div>
                </div>
                
                <?php 
                $sync_history = get_post_meta($post->ID, '_ewcs_pro_sync_history', true);
                if ($sync_history && is_array($sync_history)): 
                ?>
                <div style="margin-top: 10px;">
                    <strong><?php _e('Recent Syncs:', 'enhanced-wc-sync-pro'); ?></strong>
                    <div style="max-height: 100px; overflow-y: auto; margin-top: 5px;">
                        <?php foreach (array_slice($sync_history, -5) as $sync): ?>
                        <div style="font-size: 11px; color: #666; margin-bottom: 2px;">
                            <?php echo date('M j H:i', $sync['timestamp']); ?> - 
                            <span style="color: <?php echo $sync['status'] === 'success' ? '#28a745' : '#dc3545'; ?>">
                                <?php echo esc_html($sync['status']); ?>
                            </span>
                            <?php if (!empty($sync['message'])): ?>
                            <br><span style="margin-left: 10px;"><?php echo esc_html($sync['message']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="ewcs-sync-actions">
                <?php if ($remote_id): ?>
                <button type="button" 
                        class="ewcs-sync-btn" 
                        id="ewcs-manual-sync"
                        data-product-id="<?php echo $post->ID; ?>">
                    <?php _e('Sync Now', 'enhanced-wc-sync-pro'); ?>
                </button>
                
                <button type="button" 
                        class="ewcs-sync-btn secondary" 
                        id="ewcs-check-remote">
                    <?php _e('Check Remote', 'enhanced-wc-sync-pro'); ?>
                </button>
                
                <button type="button" 
                        class="ewcs-sync-btn danger" 
                        id="ewcs-unlink-product">
                    <?php _e('Unlink', 'enhanced-wc-sync-pro'); ?>
                </button>
                <?php else: ?>
                <button type="button" 
                        class="ewcs-sync-btn" 
                        id="ewcs-link-product">
                    <?php _e('Link Product', 'enhanced-wc-sync-pro'); ?>
                </button>
                <?php endif; ?>
                
                <button type="button" 
                        class="ewcs-sync-btn secondary" 
                        id="ewcs-view-logs">
                    <?php _e('View Logs', 'enhanced-wc-sync-pro'); ?>
                </button>
            </div>
            
            <!-- Sync Log Container (hidden by default) -->
            <div id="ewcs-sync-logs" style="display: none; margin-top: 15px; max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0;"><?php _e('Sync Logs', 'enhanced-wc-sync-pro'); ?></h4>
                <div id="ewcs-logs-content">
                    <em><?php _e('Loading logs...', 'enhanced-wc-sync-pro'); ?></em>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Manual sync
            $('#ewcs-manual-sync').on('click', function() {
                const button = $(this);
                const productId = button.data('product-id');
                
                if (!confirm(ewcs_pro_product.strings.sync_confirm)) {
                    return;
                }
                
                button.prop('disabled', true).text(ewcs_pro_product.strings.syncing);
                
                $.ajax({
                    url: ewcs_pro_product.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ewcs_manual_sync_product',
                        product_id: productId,
                        nonce: ewcs_pro_product.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(ewcs_pro_product.strings.sync_success);
                            location.reload();
                        } else {
                            alert(ewcs_pro_product.strings.sync_error + ': ' + response.data);
                        }
                    },
                    error: function() {
                        alert(ewcs_pro_product.strings.sync_error);
                    },
                    complete: function() {
                        button.prop('disabled', false).text('<?php echo esc_js(__('Sync Now', 'enhanced-wc-sync-pro')); ?>');
                    }
                });
            });
            
            // Update remote ID
            $('#ewcs_remote_id').on('change', function() {
                const remoteId = $(this).val();
                const productId = $('#ewcs-manual-sync').data('product-id');
                
                if (remoteId && productId) {
                    $.ajax({
                        url: ewcs_pro_product.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'ewcs_update_remote_id',
                            product_id: productId,
                            remote_id: remoteId,
                            nonce: ewcs_pro_product.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Show success indicator
                                $('#ewcs_remote_id').css('border-color', '#28a745');
                                setTimeout(function() {
                                    $('#ewcs_remote_id').css('border-color', '');
                                }, 2000);
                            }
                        }
                    });
                }
            });
            
            // View logs
            $('#ewcs-view-logs').on('click', function() {
                const logsContainer = $('#ewcs-sync-logs');
                const logsContent = $('#ewcs-logs-content');
                const productId = $('#ewcs-manual-sync').data('product-id');
                
                if (logsContainer.is(':visible')) {
                    logsContainer.hide();
                    return;
                }
                
                logsContainer.show();
                logsContent.html('<em><?php echo esc_js(__('Loading logs...', 'enhanced-wc-sync-pro')); ?></em>');
                
                $.ajax({
                    url: ewcs_pro_product.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ewcs_get_product_logs',
                        product_id: productId,
                        nonce: ewcs_pro_product.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            let logsHtml = '';
                            response.data.forEach(function(log) {
                                logsHtml += '<div style="font-size: 11px; margin-bottom: 5px; padding: 5px; background: white; border-radius: 3px;">';
                                logsHtml += '<strong>[' + log.timestamp + ']</strong> ';
                                logsHtml += '<span style="color: ' + (log.status === 'success' ? '#28a745' : log.status === 'error' ? '#dc3545' : '#6c757d') + '">';
                                logsHtml += log.status.toUpperCase() + '</span><br>';
                                logsHtml += log.message;
                                logsHtml += '</div>';
                            });
                            logsContent.html(logsHtml);
                        } else {
                            logsContent.html('<em><?php echo esc_js(__('No logs found for this product.', 'enhanced-wc-sync-pro')); ?></em>');
                        }
                    },
                    error: function() {
                        logsContent.html('<em style="color: #dc3545;"><?php echo esc_js(__('Failed to load logs.', 'enhanced-wc-sync-pro')); ?></em>');
                    }
                });
            });
            
            // Unlink product
            $('#ewcs-unlink-product').on('click', function() {
                if (confirm('<?php echo esc_js(__('This will remove the connection to the remote product. Are you sure?', 'enhanced-wc-sync-pro')); ?>')) {
                    $('#ewcs_remote_id').val('');
                    $('input[name="ewcs_sync_enabled"]').prop('checked', false);
                    alert('<?php echo esc_js(__('Product will be unlinked when you save.', 'enhanced-wc-sync-pro')); ?>');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save sync meta data
     */
    public function save_sync_meta($post_id) {
        // Verify nonce
        if (!isset($_POST['ewcs_pro_product_nonce']) || 
            !wp_verify_nonce($_POST['ewcs_pro_product_nonce'], 'ewcs_pro_product_meta')) {
            return;
        }
        
        // Check if user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save remote ID
        if (isset($_POST['ewcs_remote_id'])) {
            $remote_id = intval($_POST['ewcs_remote_id']);
            if ($remote_id > 0) {
                update_post_meta($post_id, '_ewcs_pro_remote_id', $remote_id);
            } else {
                delete_post_meta($post_id, '_ewcs_pro_remote_id');
            }
        }
        
        // Save sync enabled
        $sync_enabled = isset($_POST['ewcs_sync_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_ewcs_pro_sync_enabled', $sync_enabled);
        
        // Save sync fields
        $sync_fields = isset($_POST['ewcs_sync_fields']) ? $_POST['ewcs_sync_fields'] : array();
        update_post_meta($post_id, '_ewcs_pro_sync_fields', $sync_fields);
        
        // Save import mode
        if (isset($_POST['ewcs_import_mode'])) {
            $import_mode = sanitize_text_field($_POST['ewcs_import_mode']);
            update_post_meta($post_id, '_ewcs_pro_import_mode', $import_mode);
        }
    }
    
    /**
     * Sync single product with remote
     */
    public function sync_single_product($product_id) {
        $remote_id = get_post_meta($product_id, '_ewcs_pro_remote_id', true);
        
        if (!$remote_id) {
            throw new Exception(__('Product is not linked to a remote product', 'enhanced-wc-sync-pro'));
        }
        
        // Get API handler
        $api_handler = new EWCS_API_Handler();
        
        // Get remote product data
        $remote_product = $api_handler->get_product($remote_id);
        
        if (!$remote_product) {
            throw new Exception(__('Remote product not found', 'enhanced-wc-sync-pro'));
        }
        
        // Get local product
        $product = wc_get_product($product_id);
        if (!$product) {
            throw new Exception(__('Local product not found', 'enhanced-wc-sync-pro'));
        }
        
        // Get sync fields
        $sync_fields = get_post_meta($product_id, '_ewcs_pro_sync_fields', true);
        if (!$sync_fields) {
            $sync_fields = array('price', 'stock', 'description');
        }
        
        $updated_fields = array();
        
        // Sync prices
        if (in_array('price', $sync_fields)) {
            if (!empty($remote_product['regular_price'])) {
                $product->set_regular_price($remote_product['regular_price']);
                $updated_fields[] = 'regular_price';
            }
            if (!empty($remote_product['sale_price'])) {
                $product->set_sale_price($remote_product['sale_price']);
                $updated_fields[] = 'sale_price';
            }
        }
        
        // Sync stock
        if (in_array('stock', $sync_fields)) {
            $product->set_manage_stock($remote_product['manage_stock'] ?? false);
            if ($remote_product['manage_stock']) {
                $product->set_stock_quantity($remote_product['stock_quantity']);
                $updated_fields[] = 'stock_quantity';
            }
            $product->set_stock_status($remote_product['stock_status'] ?? 'instock');
            $updated_fields[] = 'stock_status';
        }
        
        // Sync description
        if (in_array('description', $sync_fields)) {
            $product->set_description($remote_product['description'] ?? '');
            $product->set_short_description($remote_product['short_description'] ?? '');
            $updated_fields[] = 'description';
        }
        
        // Save product
        $product->save();
        
        // Update sync timestamp
        update_post_meta($product_id, '_ewcs_pro_last_sync', current_time('timestamp'));
        
        // Add to sync history
        $this->add_sync_history($product_id, 'success', 'Manual sync completed', $updated_fields);
        
        // Log the sync
        $logger = new EWCS_Sync_Logger();
        $logger->log('Manual Sync', 'success', 
            sprintf(__('Product %d synced successfully', 'enhanced-wc-sync-pro'), $product_id), 
            $product_id, $remote_id);
        
        return array(
            'success' => true,
            'message' => sprintf(__('Product synced successfully. Updated: %s', 'enhanced-wc-sync-pro'), 
                                implode(', ', $updated_fields)),
            'updated_fields' => $updated_fields
        );
    }
    
    /**
     * Add entry to sync history
     */
    private function add_sync_history($product_id, $status, $message, $updated_fields = array()) {
        $history = get_post_meta($product_id, '_ewcs_pro_sync_history', true);
        if (!is_array($history)) {
            $history = array();
        }
        
        $history[] = array(
            'timestamp' => current_time('timestamp'),
            'status' => $status,
            'message' => $message,
            'updated_fields' => $updated_fields
        );
        
        // Keep only last 20 entries
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }
        
        update_post_meta($product_id, '_ewcs_pro_sync_history', $history);
    }
}