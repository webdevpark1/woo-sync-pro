<?php
/**
 * WooCommerce Sync Pro - Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ewcs-pro-dashboard">
    <div class="ewcs-pro-container">
        <div class="ewcs-pro-header">
            <h1><?php _e('WooCommerce Sync Pro', 'enhanced-wc-sync-pro'); ?></h1>
            <p><?php _e('Professional product synchronization with advanced duplicate handling features', 'enhanced-wc-sync-pro'); ?></p>
        </div>
        
        <div class="ewcs-pro-grid">
            <!-- Sync Statistics Card -->
            <div class="ewcs-pro-card">
                <h2><?php _e('Sync Statistics', 'enhanced-wc-sync-pro'); ?></h2>
                <div class="ewcs-pro-stats">
                    <div class="ewcs-pro-stat">
                        <span class="ewcs-pro-stat-value"><?php echo $stats['total_synced']; ?></span>
                        <span class="ewcs-pro-stat-label"><?php _e('Total Synced', 'enhanced-wc-sync-pro'); ?></span>
                    </div>
                    <div class="ewcs-pro-stat">
                        <span class="ewcs-pro-stat-value"><?php echo $stats['last_batch_size']; ?></span>
                        <span class="ewcs-pro-stat-label"><?php _e('Last Batch', 'enhanced-wc-sync-pro'); ?></span>
                    </div>
                    <div class="ewcs-pro-stat">
                        <span class="ewcs-pro-stat-value"><?php echo $stats['errors']; ?></span>
                        <span class="ewcs-pro-stat-label"><?php _e('Recent Errors', 'enhanced-wc-sync-pro'); ?></span>
                    </div>
                    <div class="ewcs-pro-stat">
                        <span class="ewcs-pro-stat-value" style="color: <?php echo $duplicates_count > 0 ? '#f56565' : '#48bb78'; ?>">
                            <?php echo $duplicates_count; ?>
                        </span>
                        <span class="ewcs-pro-stat-label"><?php _e('Pending Duplicates', 'enhanced-wc-sync-pro'); ?></span>
                    </div>
                </div>
                <div style="margin-top: 15px; font-size: 14px; color: #718096;">
                    <?php _e('Last sync:', 'enhanced-wc-sync-pro'); ?>
                    <strong><?php echo $stats['last_sync'] ? date('M j, Y H:i', $stats['last_sync']) : __('Never', 'enhanced-wc-sync-pro'); ?></strong>
                </div>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="ewcs-pro-card">
                <h2><?php _e('Quick Actions', 'enhanced-wc-sync-pro'); ?></h2>
                <div class="ewcs-pro-actions">
                    <button id="test-connection" class="ewcs-pro-btn ewcs-pro-btn-secondary" 
                            data-tooltip="<?php _e('Test connection to remote WooCommerce site', 'enhanced-wc-sync-pro'); ?>">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('Test Connection', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    
                    <a href="<?php echo admin_url('admin.php?page=ewcs-pro-import'); ?>" 
                       class="ewcs-pro-btn ewcs-pro-btn-primary"
                       data-tooltip="<?php _e('Import products from remote site', 'enhanced-wc-sync-pro'); ?>">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Import Products', 'enhanced-wc-sync-pro'); ?>
                    </a>
                    
                    <button id="sync-stock-prices" class="ewcs-pro-btn ewcs-pro-btn-success"
                            data-tooltip="<?php _e('Sync stock quantities and prices for existing products', 'enhanced-wc-sync-pro'); ?>">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Sync Stock & Prices', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    
                    <?php if ($duplicates_count > 0): ?>
                    <a href="<?php echo admin_url('admin.php?page=ewcs-pro-duplicates'); ?>" 
                       class="ewcs-pro-btn ewcs-pro-btn-warning"
                       data-tooltip="<?php _e('Manage duplicate products', 'enhanced-wc-sync-pro'); ?>">
                        <span class="dashicons dashicons-warning"></span>
                        <?php printf(__('Manage %d Duplicates', 'enhanced-wc-sync-pro'), $duplicates_count); ?>
                    </a>
                    <?php else: ?>
                    <button id="check-duplicates" class="ewcs-pro-btn ewcs-pro-btn-secondary"
                            data-tooltip="<?php _e('Scan for potential duplicate products', 'enhanced-wc-sync-pro'); ?>">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Check Duplicates', 'enhanced-wc-sync-pro'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="ewcs-pro-grid">
            <!-- System Status Card -->
            <div class="ewcs-pro-card">
                <h2><?php _e('System Status', 'enhanced-wc-sync-pro'); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <strong><?php _e('WooCommerce:', 'enhanced-wc-sync-pro'); ?></strong>
                        <span style="color: #48bb78;">✓ <?php echo WC()->version; ?></span>
                    </div>
                    <div>
                        <strong><?php _e('API Configuration:', 'enhanced-wc-sync-pro'); ?></strong>
                        <span style="color: <?php echo !empty($settings['remote_url']) ? '#48bb78' : '#f56565'; ?>;">
                            <?php echo !empty($settings['remote_url']) ? '✓ ' . __('Configured', 'enhanced-wc-sync-pro') : '✗ ' . __('Not Configured', 'enhanced-wc-sync-pro'); ?>
                        </span>
                    </div>
                    <div>
                        <strong><?php _e('Memory Limit:', 'enhanced-wc-sync-pro'); ?></strong>
                        <span><?php echo ini_get('memory_limit'); ?></span>
                    </div>
                    <div>
                        <strong><?php _e('Max Execution Time:', 'enhanced-wc-sync-pro'); ?></strong>
                        <span><?php echo ini_get('max_execution_time'); ?>s</span>
                    </div>
                    <div>
                        <strong><?php _e('Plugin Version:', 'enhanced-wc-sync-pro'); ?></strong>
                        <span><?php echo EWCS_PRO_VERSION; ?></span>
                    </div>
                    <div>
                        <strong><?php _e('Database Tables:', 'enhanced-wc-sync-pro'); ?></strong>
                        <?php
                        global $wpdb;
                        $tables_exist = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ewcs_pro_sync_log'") && 
                                       $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ewcs_pro_duplicates'");
                        ?>
                        <span style="color: <?php echo $tables_exist ? '#48bb78' : '#f56565'; ?>;">
                            <?php echo $tables_exist ? '✓ ' . __('Ready', 'enhanced-wc-sync-pro') : '✗ ' . __('Missing', 'enhanced-wc-sync-pro'); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Card -->
            <div class="ewcs-pro-card">
                <h2><?php _e('Recent Activity', 'enhanced-wc-sync-pro'); ?></h2>
                <?php 
                $logger = new EWCS_Sync_Logger();
                $recent_logs = $logger->get_recent_logs(5);
                
                if (!empty($recent_logs)): ?>
                <div class="ewcs-pro-log" style="max-height: 200px;">
                    <?php foreach ($recent_logs as $log): ?>
                    <div class="ewcs-pro-log-item ewcs-pro-log-<?php echo esc_attr($log->status); ?>">
                        <strong>[<?php echo esc_html($log->timestamp); ?>]</strong>
                        <span style="text-transform: uppercase; font-weight: bold; margin: 0 5px;">
                            [<?php echo esc_html($log->action); ?>]
                        </span>
                        <?php echo esc_html($log->message); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="ewcs-pro-actions" style="margin-top: 15px;">
                    <a href="<?php echo admin_url('admin.php?page=ewcs-pro-logs'); ?>" 
                       class="ewcs-pro-btn ewcs-pro-btn-secondary">
                        <?php _e('View All Logs', 'enhanced-wc-sync-pro'); ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="ewcs-pro-alert ewcs-pro-alert-info">
                    <?php _e('No recent activity. Start syncing to see logs here.', 'enhanced-wc-sync-pro'); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($settings['remote_url'])): ?>
        <!-- Connection Status Card -->
        <div class="ewcs-pro-card">
            <h2><?php _e('Remote Connection', 'enhanced-wc-sync-pro'); ?></h2>
            <div style="display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: center;">
                <div>
                    <div style="margin-bottom: 10px;">
                        <strong><?php _e('Remote Site:', 'enhanced-wc-sync-pro'); ?></strong>
                        <span><?php echo esc_html($settings['remote_url']); ?></span>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong><?php _e('Sync Direction:', 'enhanced-wc-sync-pro'); ?></strong>
                        <span><?php echo ucfirst($settings['sync_direction'] ?? 'pull'); ?></span>
                    </div>
                    <div>
                        <strong><?php _e('Auto Sync:', 'enhanced-wc-sync-pro'); ?></strong>
                        <span><?php echo ucfirst($settings['sync_interval'] ?? 'manual'); ?></span>
                    </div>
                </div>
                <div>
                    <a href="<?php echo admin_url('admin.php?page=ewcs-pro-settings'); ?>" 
                       class="ewcs-pro-btn ewcs-pro-btn-secondary">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Settings', 'enhanced-wc-sync-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Setup Required Card -->
        <div class="ewcs-pro-card">
            <h2><?php _e('Setup Required', 'enhanced-wc-sync-pro'); ?></h2>
            <div class="ewcs-pro-alert ewcs-pro-alert-warning">
                <p><?php _e('Please configure your API settings to start syncing products.', 'enhanced-wc-sync-pro'); ?></p>
            </div>
            <div class="ewcs-pro-actions">
                <a href="<?php echo admin_url('admin.php?page=ewcs-pro-settings'); ?>" 
                   class="ewcs-pro-btn ewcs-pro-btn-primary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Configure Settings', 'enhanced-wc-sync-pro'); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Live Activity Log -->
        <div id="ewcs-pro-log" class="ewcs-pro-log" style="display: none;">
            <div class="ewcs-pro-log-item ewcs-pro-log-info">
                <?php _e('Ready for operations...', 'enhanced-wc-sync-pro'); ?>
            </div>
        </div>
    </div>
</div>