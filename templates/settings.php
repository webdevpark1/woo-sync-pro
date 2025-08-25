<?php
/**
 * Enhanced WooCommerce Sync Pro - Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ewcs-pro-dashboard">
    <div class="ewcs-pro-container">
        <div class="ewcs-pro-header">
            <h1><?php _e('Settings', 'enhanced-wc-sync-pro'); ?></h1>
            <p><?php _e('Configure your Enhanced WooCommerce sync settings for optimal performance', 'enhanced-wc-sync-pro'); ?></p>
        </div>

        <form method="post" action="" class="ewcs-settings-form">
            <?php wp_nonce_field('ewcs_pro_settings', 'ewcs_pro_nonce'); ?>
            <input type="hidden" name="action" value="save_settings">
            
            <!-- API Configuration -->
            <div class="ewcs-pro-card">
                <h2><?php _e('API Configuration', 'enhanced-wc-sync-pro'); ?></h2>
                <div class="ewcs-pro-form-group">
                    <label class="ewcs-pro-form-label" for="remote_url">
                        <?php _e('Remote Site URL', 'enhanced-wc-sync-pro'); ?>
                        <span style="color: #f56565;">*</span>
                    </label>
                    <input type="url" 
                           id="remote_url" 
                           name="remote_url" 
                           value="<?php echo esc_attr($settings['remote_url'] ?? ''); ?>" 
                           class="ewcs-pro-form-input" 
                           required
                           placeholder="https://example.com">
                    <div class="ewcs-pro-form-help">
                        <?php _e('The URL of the remote WooCommerce site (e.g., https://example.com)', 'enhanced-wc-sync-pro'); ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="consumer_key">
                            <?php _e('Consumer Key', 'enhanced-wc-sync-pro'); ?>
                            <span style="color: #f56565;">*</span>
                        </label>
                        <input type="text" 
                               id="consumer_key" 
                               name="consumer_key" 
                               value="<?php echo esc_attr($settings['consumer_key'] ?? ''); ?>" 
                               class="ewcs-pro-form-input" 
                               required
                               placeholder="ck_...">
                        <div class="ewcs-pro-form-help">
                            <?php _e('WooCommerce REST API Consumer Key', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>

                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="consumer_secret">
                            <?php _e('Consumer Secret', 'enhanced-wc-sync-pro'); ?>
                            <span style="color: #f56565;">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="password" 
                                   id="consumer_secret" 
                                   name="consumer_secret" 
                                   value="<?php echo esc_attr($settings['consumer_secret'] ?? ''); ?>" 
                                   class="ewcs-pro-form-input" 
                                   required
                                   placeholder="cs_...">
                            <button type="button" 
                                    id="toggle-secret" 
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                        <div class="ewcs-pro-form-help">
                            <?php _e('WooCommerce REST API Consumer Secret', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>
                </div>

                <div class="ewcs-pro-actions" style="margin-top: 15px;">
                    <button type="button" id="test-api-connection" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('Test Connection', 'enhanced-wc-sync-pro'); ?>
                    </button>
                </div>
            </div>

            <!-- Sync Configuration -->
            <div class="ewcs-pro-card">
                <h2><?php _e('Sync Configuration', 'enhanced-wc-sync-pro'); ?></h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="sync_direction">
                            <?php _e('Sync Direction', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <select name="sync_direction" id="sync_direction" class="ewcs-pro-form-input">
                            <option value="pull" <?php selected($settings['sync_direction'] ?? '', 'pull'); ?>>
                                <?php _e('Pull from Remote (Import)', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="push" <?php selected($settings['sync_direction'] ?? '', 'push'); ?>>
                                <?php _e('Push to Remote (Export)', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="bidirectional" <?php selected($settings['sync_direction'] ?? '', 'bidirectional'); ?>>
                                <?php _e('Bidirectional', 'enhanced-wc-sync-pro'); ?>
                            </option>
                        </select>
                        <div class="ewcs-pro-form-help">
                            <?php _e('Choose the direction of data synchronization', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>

                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="sync_interval">
                            <?php _e('Auto Sync Interval', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <select name="sync_interval" id="sync_interval" class="ewcs-pro-form-input">
                            <option value="manual" <?php selected($settings['sync_interval'] ?? '', 'manual'); ?>>
                                <?php _e('Manual Only', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="hourly" <?php selected($settings['sync_interval'] ?? '', 'hourly'); ?>>
                                <?php _e('Hourly', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="twicedaily" <?php selected($settings['sync_interval'] ?? '', 'twicedaily'); ?>>
                                <?php _e('Twice Daily', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="daily" <?php selected($settings['sync_interval'] ?? '', 'daily'); ?>>
                                <?php _e('Daily', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="weekly" <?php selected($settings['sync_interval'] ?? '', 'weekly'); ?>>
                                <?php _e('Weekly', 'enhanced-wc-sync-pro'); ?>
                            </option>
                        </select>
                        <div class="ewcs-pro-form-help">
                            <?php _e('How often to automatically sync products', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sync Options -->
            <div class="ewcs-pro-card">
                <h2><?php _e('Sync Options', 'enhanced-wc-sync-pro'); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <h4><?php _e('Content to Sync', 'enhanced-wc-sync-pro'); ?></h4>
                        <div style="display: grid; gap: 10px;">
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="sync_products" <?php checked($settings['sync_products'] ?? true, true); ?>>
                                <?php _e('Sync Products', 'enhanced-wc-sync-pro'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="sync_stock" <?php checked($settings['sync_stock'] ?? true, true); ?>>
                                <?php _e('Sync Stock Quantities', 'enhanced-wc-sync-pro'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="sync_prices" <?php checked($settings['sync_prices'] ?? true, true); ?>>
                                <?php _e('Sync Prices', 'enhanced-wc-sync-pro'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="sync_images" <?php checked($settings['sync_images'] ?? false, true); ?>>
                                <?php _e('Sync Product Images', 'enhanced-wc-sync-pro'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <h4><?php _e('Additional Options', 'enhanced-wc-sync-pro'); ?></h4>
                        <div style="display: grid; gap: 10px;">
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="sync_categories" <?php checked($settings['sync_categories'] ?? true, true); ?>>
                                <?php _e('Sync Categories', 'enhanced-wc-sync-pro'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="sync_attributes" <?php checked($settings['sync_attributes'] ?? false, true); ?>>
                                <?php _e('Sync Attributes', 'enhanced-wc-sync-pro'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="sync_variations" <?php checked($settings['sync_variations'] ?? false, true); ?>>
                                <?php _e('Sync Product Variations', 'enhanced-wc-sync-pro'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="sync_reviews" <?php checked($settings['sync_reviews'] ?? false, true); ?>>
                                <?php _e('Sync Product Reviews', 'enhanced-wc-sync-pro'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Settings -->
            <div class="ewcs-pro-card">
                <h2><?php _e('Import Settings', 'enhanced-wc-sync-pro'); ?></h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="import_status">
                            <?php _e('Product Status for Imports', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <select name="import_status" id="import_status" class="ewcs-pro-form-input">
                            <option value="publish" <?php selected($settings['import_status'] ?? '', 'publish'); ?>>
                                <?php _e('Published', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="draft" <?php selected($settings['import_status'] ?? '', 'draft'); ?>>
                                <?php _e('Draft', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="private" <?php selected($settings['import_status'] ?? '', 'private'); ?>>
                                <?php _e('Private', 'enhanced-wc-sync-pro'); ?>
                            </option>
                        </select>
                    </div>

                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="batch_size">
                            <?php _e('Batch Size', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <input type="number" 
                               id="batch_size" 
                               name="batch_size" 
                               value="<?php echo esc_attr($settings['batch_size'] ?? EWCS_PRO_BATCH_SIZE); ?>" 
                               class="ewcs-pro-form-input" 
                               min="5" 
                               max="100" 
                               step="5">
                        <div class="ewcs-pro-form-help">
                            <?php _e('Products per batch (5-100)', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>

                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="import_timeout">
                            <?php _e('Import Timeout (seconds)', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <input type="number" 
                               id="import_timeout" 
                               name="import_timeout" 
                               value="<?php echo esc_attr($settings['import_timeout'] ?? 300); ?>" 
                               class="ewcs-pro-form-input" 
                               min="60" 
                               max="3600" 
                               step="30">
                        <div class="ewcs-pro-form-help">
                            <?php _e('Maximum time for import operations', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Duplicate Handling -->
            <div class="ewcs-pro-card">
                <h2><?php _e('Duplicate Handling', 'enhanced-wc-sync-pro'); ?></h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="duplicate_handling">
                            <?php _e('Default Duplicate Action', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <select name="duplicate_handling" id="duplicate_handling" class="ewcs-pro-form-input">
                            <option value="skip" <?php selected($settings['duplicate_handling'] ?? '', 'skip'); ?>>
                                <?php _e('Skip Duplicates', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="update" <?php selected($settings['duplicate_handling'] ?? '', 'update'); ?>>
                                <?php _e('Update Existing', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="merge" <?php selected($settings['duplicate_handling'] ?? '', 'merge'); ?>>
                                <?php _e('Merge Products', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="create" <?php selected($settings['duplicate_handling'] ?? '', 'create'); ?>>
                                <?php _e('Create Duplicates', 'enhanced-wc-sync-pro'); ?>
                            </option>
                        </select>
                        <div class="ewcs-pro-form-help">
                            <?php _e('How to handle duplicate products during import', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>

                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label"><?php _e('Duplicate Detection', 'enhanced-wc-sync-pro'); ?></label>
                        <div style="display: grid; gap: 8px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="duplicate_check_sku" <?php checked($settings['duplicate_check_sku'] ?? true, true); ?>>
                                <?php _e('Check by SKU', 'enhanced-wc-sync-pro'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="duplicate_check_name" <?php checked($settings['duplicate_check_name'] ?? true, true); ?>>
                                <?php _e('Check by Name', 'enhanced-wc-sync-pro'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="duplicate_check_slug" <?php checked($settings['duplicate_check_slug'] ?? false, true); ?>>
                                <?php _e('Check by Slug', 'enhanced-wc-sync-pro'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="auto_resolve_duplicates" <?php checked($settings['auto_resolve_duplicates'] ?? false, true); ?>>
                                <?php _e('Auto-resolve duplicates', 'enhanced-wc-sync-pro'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Settings -->
            <div class="ewcs-pro-card">
                <h2><?php _e('Performance & Logging', 'enhanced-wc-sync-pro'); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="log_level">
                            <?php _e('Logging Level', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <select name="log_level" id="log_level" class="ewcs-pro-form-input">
                            <option value="all" <?php selected($settings['log_level'] ?? '', 'all'); ?>>
                                <?php _e('All Activities', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="errors" <?php selected($settings['log_level'] ?? '', 'errors'); ?>>
                                <?php _e('Errors Only', 'enhanced-wc-sync-pro'); ?>
                            </option>
                            <option value="none" <?php selected($settings['log_level'] ?? '', 'none'); ?>>
                                <?php _e('No Logging', 'enhanced-wc-sync-pro'); ?>
                            </option>
                        </select>
                    </div>

                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="log_retention">
                            <?php _e('Log Retention (days)', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <input type="number" 
                               id="log_retention" 
                               name="log_retention" 
                               value="<?php echo esc_attr($settings['log_retention'] ?? 30); ?>" 
                               class="ewcs-pro-form-input" 
                               min="1" 
                               max="365">
                        <div class="ewcs-pro-form-help">
                            <?php _e('How long to keep sync logs', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="enable_performance_monitoring" <?php checked($settings['enable_performance_monitoring'] ?? false, true); ?>>
                            <?php _e('Performance Monitoring', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="enable_rate_limiting" <?php checked($settings['enable_rate_limiting'] ?? true, true); ?>>
                            <?php _e('API Rate Limiting', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="enable_debug_mode" <?php checked($settings['enable_debug_mode'] ?? false, true); ?>>
                            <?php _e('Debug Mode', 'enhanced-wc-sync-pro'); ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="ewcs-pro-card">
                <h2><?php _e('Advanced Settings', 'enhanced-wc-sync-pro'); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="webhook_url">
                            <?php _e('Webhook URL (Optional)', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <input type="url" 
                               id="webhook_url" 
                               name="webhook_url" 
                               value="<?php echo esc_attr($settings['webhook_url'] ?? ''); ?>" 
                               class="ewcs-pro-form-input" 
                               placeholder="https://yoursite.com/webhook">
                        <div class="ewcs-pro-form-help">
                            <?php _e('URL to receive sync notifications', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>

                    <div class="ewcs-pro-form-group">
                        <label class="ewcs-pro-form-label" for="custom_fields">
                            <?php _e('Custom Fields to Sync', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <textarea id="custom_fields" 
                                  name="custom_fields" 
                                  class="ewcs-pro-form-input" 
                                  rows="3" 
                                  placeholder="field1,field2,field3"><?php echo esc_textarea($settings['custom_fields'] ?? ''); ?></textarea>
                        <div class="ewcs-pro-form-help">
                            <?php _e('Comma-separated list of custom field names', 'enhanced-wc-sync-pro'); ?>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <h4><?php _e('API Security', 'enhanced-wc-sync-pro'); ?></h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="verify_ssl" <?php checked($settings['verify_ssl'] ?? true, true); ?>>
                            <?php _e('Verify SSL Certificates', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="require_authentication" <?php checked($settings['require_authentication'] ?? true, true); ?>>
                            <?php _e('Require API Authentication', 'enhanced-wc-sync-pro'); ?>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="enable_ip_whitelist" <?php checked($settings['enable_ip_whitelist'] ?? false, true); ?>>
                            <?php _e('Enable IP Whitelisting', 'enhanced-wc-sync-pro'); ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="ewcs-pro-card">
                <div class="ewcs-pro-actions">
                    <button type="submit" name="submit" class="ewcs-pro-btn ewcs-pro-btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Save Settings', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    
                    <button type="button" id="export-settings" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Settings', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    
                    <button type="button" id="import-settings" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Import Settings', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    
                    <button type="button" id="reset-settings" class="ewcs-pro-btn ewcs-pro-btn-danger">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Reset to Defaults', 'enhanced-wc-sync-pro'); ?>
                    </button>
                </div>
                
                <input type="file" id="settings-file-input" accept=".json" style="display: none;">
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Toggle password visibility
    $('#toggle-secret').on('click', function() {
        const input = $('#consumer_secret');
        const icon = $(this).find('.dashicons');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            input.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // Test API connection
    $('#test-api-connection').on('click', function() {
        const button = $(this);
        const remoteUrl = $('#remote_url').val();
        const consumerKey = $('#consumer_key').val();
        const consumerSecret = $('#consumer_secret').val();
        
        if (!remoteUrl || !consumerKey || !consumerSecret) {
            alert('Please fill in all API credentials first.');
            return;
        }
        
        button.prop('disabled', true).html('<span class="ewcs-spinner"></span> Testing...');
        
        $.ajax({
            url: ewcs_pro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ewcs_test_connection',
                nonce: ewcs_pro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ Connection successful! API credentials are working correctly.');
                } else {
                    alert('❌ Connection failed: ' + response.data);
                }
            },
            error: function() {
                alert('❌ Connection test failed. Please check your credentials and try again.');
            },
            complete: function() {
                button.prop('disabled', false).html('<span class="dashicons dashicons-admin-links"></span> Test Connection');
            }
        });
    });
    
    // Export settings
    $('#export-settings').on('click', function() {
        window.open(ewcs_pro_ajax.ajax_url + '?action=ewcs_export_settings&nonce=' + ewcs_pro_ajax.nonce, '_blank');
    });
    
    // Import settings
    $('#import-settings').on('click', function() {
        $('#settings-file-input').click();
    });
    
    $('#settings-file-input').on('change', function() {
        const file = this.files[0];
        if (!file) return;
        
        if (file.type !== 'application/json') {
            alert('Please select a valid JSON settings file.');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const settings = JSON.parse(e.target.result);
                
                if (!settings.plugin || settings.plugin !== 'Enhanced WooCommerce Sync Pro') {
                    alert('Invalid settings file format.');
                    return;
                }
                
                if (confirm('This will overwrite your current settings. Are you sure?')) {
                    // Populate form fields with imported settings
                    Object.keys(settings.settings).forEach(key => {
                        const field = $(`[name="${key}"]`);
                        if (field.length) {
                            if (field.is(':checkbox')) {
                                field.prop('checked', settings.settings[key]);
                            } else {
                                field.val(settings.settings[key]);
                            }
                        }
                    });
                    
                    alert('Settings imported successfully! Don\'t forget to save.');
                }
            } catch (error) {
                alert('Error reading settings file: ' + error.message);
            }
        };
        reader.readAsText(file);
    });
    
    // Reset settings
    $('#reset-settings').on('click', function() {
        if (confirm('This will reset all settings to their default values. Are you sure?')) {
            // Reset form to defaults
            $('form')[0].reset();
            
            // Set specific defaults
            $('#sync_direction').val('pull');
            $('#sync_interval').val('manual');
            $('#import_status').val('publish');
            $('#batch_size').val('<?php echo EWCS_PRO_BATCH_SIZE; ?>');
            $('#duplicate_handling').val('skip');
            $('#log_level').val('all');
            $('#log_retention').val('30');
            
            // Check default checkboxes
            $('input[name="sync_products"], input[name="sync_stock"], input[name="sync_prices"], input[name="sync_categories"]').prop('checked', true);
            $('input[name="duplicate_check_sku"], input[name="duplicate_check_name"], input[name="verify_ssl"], input[name="require_authentication"], input[name="enable_rate_limiting"]').prop('checked', true);
            
            alert('Settings reset to defaults! Don\'t forget to save.');
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        const remoteUrl = $('#remote_url').val().trim();
        const consumerKey = $('#consumer_key').val().trim();
        const consumerSecret = $('#consumer_secret').val().trim();
        
        if (!remoteUrl || !consumerKey || !consumerSecret) {
            e.preventDefault();
            alert('Please fill in all required API credentials.');
            return false;
        }
        
        // Validate URL format
        try {
            new URL(remoteUrl);
        } catch (error) {
            e.preventDefault();
            alert('Please enter a valid URL for the remote site.');
            return false;
        }
        
        // Show saving indicator
        const submitBtn = $('button[name="submit"]');
        submitBtn.prop('disabled', true).html('<span class="ewcs-spinner"></span> Saving...');
    });
    
    // Sync interval warning
    $('#sync_interval').on('change', function() {
        const interval = $(this).val();
        const warning = $('#sync-interval-warning');
        
        warning.remove(); // Remove existing warning
        
        if (interval === 'hourly') {
            $(this).after('<p id="sync-interval-warning" class="ewcs-pro-form-help" style="color: #ed8936; margin-top: 5px;">⚠️ Hourly sync may impact site performance. Monitor your server resources.</p>');
        }
    });
    
    // Batch size validation
    $('#batch_size').on('input', function() {
        const value = parseInt($(this).val());
        const min = parseInt($(this).attr('min'));
        const max = parseInt($(this).attr('max'));
        
        if (value < min) {
            $(this).val(min);
            alert(`Minimum batch size is ${min}`);
        } else if (value > max) {
            $(this).val(max);
            alert(`Maximum batch size is ${max}`);
        }
    });
    
    // Enable debug mode warning
    $('input[name="enable_debug_mode"]').on('change', function() {
        if ($(this).is(':checked')) {
            alert('Debug mode will create detailed logs and may impact performance. Only enable when troubleshooting.');
        }
    });
    
    // Performance monitoring info
    $('input[name="enable_performance_monitoring"]').on('change', function() {
        if ($(this).is(':checked')) {
            if (!confirm('Performance monitoring will track sync operations and may use additional server resources. Continue?')) {
                $(this).prop('checked', false);
            }
        }
    });
    
    // Auto-save draft functionality
    let autoSaveTimeout;
    $('.ewcs-pro-form-input').on('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            // Auto-save as draft (you can implement this as needed)
            console.log('Auto-saving settings draft...');
        }, 2000);
    });
    
    // Real-time URL validation
    $('#remote_url').on('blur', function() {
        const url = $(this).val().trim();
        if (url) {
            try {
                new URL(url);
                $(this).css('border-color', '#48bb78');
                setTimeout(() => $(this).css('border-color', ''), 2000);
            } catch (error) {
                $(this).css('border-color', '#f56565');
                alert('Please enter a valid URL (e.g., https://example.com)');
            }
        }
    });
    
    // Custom fields validation
    $('#custom_fields').on('blur', function() {
        const fields = $(this).val().trim();
        if (fields) {
            // Validate comma-separated format
            const fieldArray = fields.split(',').map(f => f.trim()).filter(f => f);
            if (fieldArray.length !== fields.split(',').length) {
                alert('Please ensure all custom fields are properly separated by commas.');
                $(this).focus();
            }
        }
    });
    
    // Tooltip initialization
    $('[data-tooltip]').each(function() {
        $(this).attr('title', $(this).data('tooltip'));
    });
    
    // Show/hide advanced sections based on selections
    function toggleAdvancedSections() {
        const syncDirection = $('#sync_direction').val();
        const enableDebug = $('input[name="enable_debug_mode"]').is(':checked');
        
        // Show/hide relevant sections based on sync direction
        if (syncDirection === 'push' || syncDirection === 'bidirectional') {
            // Show additional options for pushing data
            $('.push-options').show();
        } else {
            $('.push-options').hide();
        }
        
        // Show/hide debug options
        if (enableDebug) {
            $('.debug-options').show();
        } else {
            $('.debug-options').hide();
        }
    }
    
    $('#sync_direction, input[name="enable_debug_mode"]').on('change', toggleAdvancedSections);
    toggleAdvancedSections(); // Initialize on page load
});
</script>

<style>
.ewcs-settings-form .ewcs-pro-card {
    margin-bottom: 25px;
}

.ewcs-settings-form h4 {
    margin: 0 0 15px 0;
    color: #2d3748;
    font-size: 16px;
    font-weight: 600;
}

.ewcs-settings-form .required {
    color: #f56565;
}

.ewcs-settings-form .form-section {
    border-left: 4px solid #667eea;
    padding-left: 20px;
    margin-bottom: 30px;
}

.ewcs-settings-form .success-border {
    border-color: #48bb78 !important;
}

.ewcs-settings-form .error-border {
    border-color: #f56565 !important;
}

.ewcs-settings-form .info-box {
    background: #e7f3ff;
    border: 1px solid #4299e1;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}

.ewcs-settings-form .warning-box {
    background: #fef5e7;
    border: 1px solid #ed8936;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}

.ewcs-settings-form .success-box {
    background: #f0fff4;
    border: 1px solid #48bb78;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .ewcs-settings-form div[style*="grid-template-columns"] {
        display: block !important;
    }
    
    .ewcs-settings-form div[style*="grid-template-columns"] > div {
        margin-bottom: 20px;
    }
}
</style>