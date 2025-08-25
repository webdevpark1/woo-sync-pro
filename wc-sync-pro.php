<?php
/**
 * Plugin Name: Enhanced WooCommerce Sync Pro
 * Plugin URI: https://dtechcreative.com
 * Description: Advanced WooCommerce sync plugin with enhanced duplicate handling, product edit features, and professional UI
 * Version: 2.1.0
 * Author: Didar
 * Author URI: https://abdidar.info
 * License: GPL v2 or later
 * Text Domain: enhanced-wc-sync-pro
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EWCS_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EWCS_PRO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EWCS_PRO_VERSION', '2.1.0');
define('EWCS_PRO_BATCH_SIZE', 20);

// Include required files
require_once EWCS_PRO_PLUGIN_PATH . 'includes/class-duplicate-handler.php';
require_once EWCS_PRO_PLUGIN_PATH . 'includes/class-product-edit-handler.php';
require_once EWCS_PRO_PLUGIN_PATH . 'includes/class-api-handler.php';
require_once EWCS_PRO_PLUGIN_PATH . 'includes/class-sync-logger.php';

class EnhancedWCSyncPro {
    private $option_name = 'ewcs_pro_settings';
    private $batch_option = 'ewcs_pro_batch_data';
    private $duplicate_handler;
    private $product_edit_handler;
    private $api_handler;
    private $logger;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialize handlers
        $this->duplicate_handler = new EWCS_Duplicate_Handler();
        $this->product_edit_handler = new EWCS_Product_Edit_Handler();
        $this->api_handler = new EWCS_API_Handler();
        $this->logger = new EWCS_Sync_Logger();

        // Declare WooCommerce compatibility
        add_action('before_woocommerce_init', array($this, 'declare_wc_compatibility'));

        // AJAX Handlers
        add_action('wp_ajax_ewcs_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_ewcs_sync_categories', array($this, 'ajax_sync_categories'));
        add_action('wp_ajax_ewcs_get_categories', array($this, 'ajax_get_categories'));
        add_action('wp_ajax_ewcs_start_batch_import', array($this, 'ajax_start_batch_import'));
        add_action('wp_ajax_ewcs_process_batch', array($this, 'ajax_process_batch'));
        add_action('wp_ajax_ewcs_sync_stock_prices', array($this, 'ajax_sync_stock_prices'));
        add_action('wp_ajax_ewcs_get_import_stats', array($this, 'ajax_get_import_stats'));
        add_action('wp_ajax_ewcs_cancel_import', array($this, 'ajax_cancel_import'));
        add_action('wp_ajax_ewcs_manual_sync_product', array($this, 'ajax_manual_sync_product'));
        add_action('wp_ajax_ewcs_update_remote_id', array($this, 'ajax_update_remote_id'));
        add_action('wp_ajax_ewcs_check_duplicates', array($this, 'ajax_check_duplicates'));
        add_action('wp_ajax_ewcs_resolve_duplicate', array($this, 'ajax_resolve_duplicate'));
        add_action('wp_ajax_ewcs_load_more_logs', array($this, 'ajax_load_more_logs'));
        add_action('wp_ajax_ewcs_pro_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_ewcs_export_logs', array($this, 'ajax_export_logs'));
        add_action('wp_ajax_ewcs_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_ewcs_auto_resolve_duplicates', array($this, 'ajax_auto_resolve_duplicates'));
        add_action('wp_ajax_ewcs_cleanup_duplicates', array($this, 'ajax_cleanup_duplicates'));
        add_action('wp_ajax_ewcs_get_product_logs', array($this, 'ajax_get_product_logs'));

        // Scheduled sync
        add_action('ewcs_pro_sync_hook', array($this, 'scheduled_sync'));

        // Product edit enhancements
        add_action('add_meta_boxes', array($this->product_edit_handler, 'add_sync_meta_box'));
        add_action('save_post', array($this->product_edit_handler, 'save_sync_meta'));

        // Product columns
        add_filter('manage_product_posts_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_product_columns'), 10, 2);

        // Bulk actions
        add_filter('bulk_actions-edit-product', array($this, 'add_bulk_sync_action'));
        add_filter('handle_bulk_actions-edit-product', array($this, 'handle_bulk_sync_action'), 10, 3);

        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Add settings link
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load text domain
        load_plugin_textdomain('enhanced-wc-sync-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function declare_wc_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }

    public function activate() {
        // Create necessary database tables
        $this->create_tables();
        
        // Schedule cron if needed
        $settings = get_option($this->option_name, array());
        if (!empty($settings['sync_interval']) && $settings['sync_interval'] !== 'manual') {
            wp_schedule_event(time(), $settings['sync_interval'], 'ewcs_pro_sync_hook');
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('ewcs_pro_sync_hook');
        delete_option($this->batch_option);
    }

    private function create_tables() {
        global $wpdb;
        
        // Sync log table
        $log_table = $wpdb->prefix . 'ewcs_pro_sync_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $log_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            action varchar(100) NOT NULL,
            status varchar(20) NOT NULL,
            message text NOT NULL,
            product_id int(11),
            remote_id int(11),
            meta longtext,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY action (action),
            KEY status (status),
            KEY product_id (product_id)
        ) $charset_collate;";

        // Duplicate tracking table
        $duplicate_table = $wpdb->prefix . 'ewcs_pro_duplicates';
        $sql2 = "CREATE TABLE $duplicate_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            remote_id int(11) NOT NULL,
            sku varchar(100),
            name varchar(255),
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY product_remote (product_id, remote_id),
            KEY status (status),
            KEY sku (sku)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
    }

    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Enhanced WooCommerce Sync Pro requires WooCommerce to be installed and active.', 'enhanced-wc-sync-pro'); ?></p>
        </div>
        <?php
    }

    public function add_admin_menu() {
        add_menu_page(
            __('WC Sync Pro+', 'enhanced-wc-sync-pro'),
            __('WC Sync Pro+', 'enhanced-wc-sync-pro'),
            'manage_options',
            'ewcs-pro',
            array($this, 'dashboard_page'),
            'dashicons-cloud-upload',
            56
        );

        add_submenu_page(
            'ewcs-pro',
            __('Dashboard', 'enhanced-wc-sync-pro'),
            __('Dashboard', 'enhanced-wc-sync-pro'),
            'manage_options',
            'ewcs-pro',
            array($this, 'dashboard_page')
        );

        add_submenu_page(
            'ewcs-pro',
            __('Import Products', 'enhanced-wc-sync-pro'),
            __('Import Products', 'enhanced-wc-sync-pro'),
            'manage_options',
            'ewcs-pro-import',
            array($this, 'import_page')
        );

        add_submenu_page(
            'ewcs-pro',
            __('Duplicates Manager', 'enhanced-wc-sync-pro'),
            __('Duplicates Manager', 'enhanced-wc-sync-pro'),
            'manage_options',
            'ewcs-pro-duplicates',
            array($this, 'duplicates_page')
        );

        add_submenu_page(
            'ewcs-pro',
            __('Settings', 'enhanced-wc-sync-pro'),
            __('Settings', 'enhanced-wc-sync-pro'),
            'manage_options',
            'ewcs-pro-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'ewcs-pro',
            __('Logs', 'enhanced-wc-sync-pro'),
            __('Logs', 'enhanced-wc-sync-pro'),
            'manage_options',
            'ewcs-pro-logs',
            array($this, 'logs_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ewcs-pro') !== false) {
            // Enqueue CSS and JS
            wp_enqueue_style('ewcs-pro-admin', EWCS_PRO_PLUGIN_URL . 'assets/css/admin.css', array(), EWCS_PRO_VERSION);
            wp_enqueue_script('ewcs-pro-admin', EWCS_PRO_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), EWCS_PRO_VERSION, true);

            // Localize script
            wp_localize_script('ewcs-pro-admin', 'ewcs_pro_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ewcs_pro_nonce'),
                'batch_size' => EWCS_PRO_BATCH_SIZE,
                'strings' => array(
                    'processing' => __('Processing...', 'enhanced-wc-sync-pro'),
                    'complete' => __('Complete!', 'enhanced-wc-sync-pro'),
                    'error' => __('Error occurred', 'enhanced-wc-sync-pro'),
                    'cancel_confirm' => __('Are you sure you want to cancel the import?', 'enhanced-wc-sync-pro'),
                    'select_categories' => __('Please select at least one category', 'enhanced-wc-sync-pro'),
                    'duplicate_resolve_confirm' => __('This will merge/replace the products. Are you sure?', 'enhanced-wc-sync-pro')
                )
            ));
        }

        // Add product edit scripts
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            global $post_type;
            if ($post_type === 'product') {
                wp_enqueue_script('ewcs-pro-product-edit', EWCS_PRO_PLUGIN_URL . 'assets/js/product-edit.js', array('jquery'), EWCS_PRO_VERSION, true);
                wp_localize_script('ewcs-pro-product-edit', 'ewcs_pro_product', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('ewcs_pro_nonce'),
                    'strings' => array(
                        'sync_confirm' => __('This will sync the product with remote data. Continue?', 'enhanced-wc-sync-pro'),
                        'syncing' => __('Syncing product...', 'enhanced-wc-sync-pro'),
                        'sync_success' => __('Product synced successfully!', 'enhanced-wc-sync-pro'),
                        'sync_error' => __('Sync failed. Check logs for details.', 'enhanced-wc-sync-pro')
                    )
                ));
            }
        }
    }

    public function dashboard_page() {
        $settings = get_option($this->option_name, array());
        $stats = $this->get_sync_stats();
        $duplicates_count = $this->duplicate_handler->get_pending_duplicates_count();
        
        include EWCS_PRO_PLUGIN_PATH . 'templates/dashboard.php';
    }

    public function import_page() {
        include EWCS_PRO_PLUGIN_PATH . 'templates/import.php';
    }

    public function duplicates_page() {
        $duplicates = $this->duplicate_handler->get_duplicates();
        include EWCS_PRO_PLUGIN_PATH . 'templates/duplicates.php';
    }

    public function settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) || (isset($_POST['action']) && $_POST['action'] === 'save_settings')) {
            $this->save_settings();
        }
        
        $settings = get_option($this->option_name, array());
        include EWCS_PRO_PLUGIN_PATH . 'templates/settings.php';
    }

    public function logs_page() {
        $logs = $this->logger->get_recent_logs(100);
        include EWCS_PRO_PLUGIN_PATH . 'templates/logs.php';
    }

    // AJAX Handlers
    public function ajax_test_connection() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $result = $this->api_handler->test_connection();
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Connection successful!', 'enhanced-wc-sync-pro'));
    }

    public function ajax_sync_categories() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        try {
            $categories = $this->sync_categories_from_remote();
            $formatted_categories = $this->get_formatted_categories();
            
            wp_send_json_success(array(
                'categories' => $formatted_categories,
                'message' => sprintf(__('Successfully synced %d categories', 'enhanced-wc-sync-pro'), count($categories))
            ));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_get_categories() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $formatted_categories = $this->get_formatted_categories();
        wp_send_json_success($formatted_categories);
    }

    public function ajax_start_batch_import() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $category_ids = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : array();
        $import_mode = sanitize_text_field($_POST['import_mode'] ?? 'skip_duplicates');
        
        if (empty($category_ids)) {
            wp_send_json_error(__('Please select at least one category', 'enhanced-wc-sync-pro'));
        }

        try {
            $batch_data = $this->prepare_batch_import($category_ids, $import_mode);
            update_option($this->batch_option, $batch_data);
            
            wp_send_json_success(array(
                'total_products' => $batch_data['total_products'],
                'total_batches' => $batch_data['total_batches'],
                'batch_size' => $batch_data['batch_size'],
                'import_mode' => $import_mode
            ));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_process_batch() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $batch_data = get_option($this->batch_option, array());
        if (empty($batch_data) || $batch_data['current_batch'] >= $batch_data['total_batches']) {
            wp_send_json_error(__('No batch data available or import completed', 'enhanced-wc-sync-pro'));
        }

        try {
            $result = $this->process_current_batch($batch_data);
            
            // Update batch data
            $batch_data['current_batch']++;
            $batch_data['imported_count'] += $result['imported'];
            $batch_data['updated_count'] += $result['updated'];
            $batch_data['skipped_count'] += $result['skipped'];
            $batch_data['error_count'] += $result['errors'];
            
            update_option($this->batch_option, $batch_data);
            
            $is_complete = $batch_data['current_batch'] >= $batch_data['total_batches'];
            if ($is_complete) {
                delete_option($this->batch_option);
                $this->logger->log('Batch Import', 'success', 
                    sprintf(__('Import completed. %d imported, %d updated, %d skipped, %d errors', 'enhanced-wc-sync-pro'),
                        $batch_data['imported_count'], $batch_data['updated_count'], 
                        $batch_data['skipped_count'], $batch_data['error_count']));
            }

            wp_send_json_success(array(
                'current_batch' => $batch_data['current_batch'],
                'imported_count' => $batch_data['imported_count'],
                'updated_count' => $batch_data['updated_count'],
                'skipped_count' => $batch_data['skipped_count'],
                'error_count' => $batch_data['error_count'],
                'is_complete' => $is_complete,
                'batch_result' => $result
            ));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_manual_sync_product() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id'] ?? 0);
        if (!$product_id) {
            wp_send_json_error(__('Invalid product ID', 'enhanced-wc-sync-pro'));
        }

        try {
            $result = $this->product_edit_handler->sync_single_product($product_id);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_update_remote_id() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id'] ?? 0);
        $remote_id = intval($_POST['remote_id'] ?? 0);
        
        if (!$product_id || !$remote_id) {
            wp_send_json_error(__('Invalid product or remote ID', 'enhanced-wc-sync-pro'));
        }

        update_post_meta($product_id, '_ewcs_pro_remote_id', $remote_id);
        update_post_meta($product_id, '_ewcs_pro_last_sync', current_time('timestamp'));
        
        wp_send_json_success(__('Remote ID updated successfully', 'enhanced-wc-sync-pro'));
    }

    public function ajax_check_duplicates() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $duplicates = $this->duplicate_handler->scan_for_duplicates();
        wp_send_json_success(array(
            'found' => count($duplicates),
            'duplicates' => $duplicates
        ));
    }

    public function ajax_resolve_duplicate() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $duplicate_id = intval($_POST['duplicate_id'] ?? 0);
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        
        if (!$duplicate_id || !in_array($action, ['merge', 'replace', 'skip'])) {
            wp_send_json_error(__('Invalid parameters', 'enhanced-wc-sync-pro'));
        }

        try {
            $result = $this->duplicate_handler->resolve_duplicate($duplicate_id, $action);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_load_more_logs() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $offset = intval($_POST['offset'] ?? 0);
        $limit = 50;
        
        $logs = $this->logger->get_recent_logs($limit, array(), $offset);
        
        wp_send_json_success(array(
            'logs' => $logs
        ));
    }

    public function ajax_clear_logs() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $cleared = $this->logger->clear_logs();
        
        if ($cleared !== false) {
            wp_send_json_success(__('Logs cleared successfully', 'enhanced-wc-sync-pro'));
        } else {
            wp_send_json_error(__('Failed to clear logs', 'enhanced-wc-sync-pro'));
        }
    }

    public function ajax_export_logs() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $filters = array();
        if (!empty($_GET['action_filter'])) {
            $filters['action'] = sanitize_text_field($_GET['action_filter']);
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = sanitize_text_field($_GET['status']);
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_GET['date_to']);
        }
        
        $this->logger->export_logs_csv($filters);
    }

    public function ajax_export_settings() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $settings = get_option($this->option_name, array());
        
        // Remove sensitive data
        unset($settings['consumer_secret']);
        
        // Add export metadata
        $export_data = array(
            'plugin' => 'Enhanced WooCommerce Sync Pro',
            'version' => EWCS_PRO_VERSION,
            'exported_at' => current_time('mysql'),
            'settings' => $settings
        );
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="ewcs-pro-settings-' . date('Y-m-d-H-i-s') . '.json"');
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    public function ajax_auto_resolve_duplicates() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $resolved = $this->duplicate_handler->auto_resolve_duplicates();
        
        wp_send_json_success(array(
            'resolved' => $resolved
        ));
    }

    public function ajax_cleanup_duplicates() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $cleaned = $this->duplicate_handler->cleanup_old_duplicates();
        
        wp_send_json_success(array(
            'cleaned' => $cleaned
        ));
    }

    public function ajax_get_product_logs() {
        check_ajax_referer('ewcs_pro_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id'] ?? 0);
        if (!$product_id) {
            wp_send_json_error(__('Invalid product ID', 'enhanced-wc-sync-pro'));
        }
        
        $logs = $this->logger->get_product_logs($product_id);
        
        wp_send_json_success($logs);
    }

    // Product columns
    public function add_product_columns($columns) {
        $columns['ewcs_pro_sync'] = __('Sync Status', 'enhanced-wc-sync-pro');
        $columns['ewcs_pro_duplicate'] = __('Duplicates', 'enhanced-wc-sync-pro');
        return $columns;
    }

    public function populate_product_columns($column, $post_id) {
        switch ($column) {
            case 'ewcs_pro_sync':
                $remote_id = get_post_meta($post_id, '_ewcs_pro_remote_id', true);
                $last_sync = get_post_meta($post_id, '_ewcs_pro_last_sync', true);
                
                if ($remote_id) {
                    echo '<span style="color: #46b450;">✓ ' . __('Synced', 'enhanced-wc-sync-pro') . '</span>';
                    echo '<br><small>ID: ' . $remote_id . '</small>';
                    if ($last_sync) {
                        echo '<br><small style="color: #666;">' . date('M j, H:i', $last_sync) . '</small>';
                    }
                } else {
                    echo '<span style="color: #999;">— ' . __('Not Synced', 'enhanced-wc-sync-pro') . '</span>';
                }
                break;
                
            case 'ewcs_pro_duplicate':
                $has_duplicates = $this->duplicate_handler->product_has_duplicates($post_id);
                if ($has_duplicates) {
                    echo '<span style="color: #dc3232;">⚠ ' . __('Has Duplicates', 'enhanced-wc-sync-pro') . '</span>';
                } else {
                    echo '<span style="color: #46b450;">✓ ' . __('Clean', 'enhanced-wc-sync-pro') . '</span>';
                }
                break;
        }
    }

    // Bulk actions
    public function add_bulk_sync_action($bulk_actions) {
        $bulk_actions['ewcs_sync'] = __('Sync with Remote', 'enhanced-wc-sync-pro');
        return $bulk_actions;
    }

    public function handle_bulk_sync_action($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'ewcs_sync') {
            return $redirect_to;
        }

        $synced = 0;
        foreach ($post_ids as $post_id) {
            try {
                $this->product_edit_handler->sync_single_product($post_id);
                $synced++;
            } catch (Exception $e) {
                // Log error but continue
                $this->logger->log('Bulk Sync', 'error', $e->getMessage(), $post_id);
            }
        }

        $redirect_to = add_query_arg('bulk_sync_result', $synced, $redirect_to);
        return $redirect_to;
    }

    // Core sync methods (abbreviated - full implementation would be similar to original)
    private function sync_categories_from_remote() {
        // Implementation similar to original but with enhanced error handling
        return $this->api_handler->sync_categories();
    }

    private function get_formatted_categories() {
        // Implementation similar to original
        return $this->api_handler->get_formatted_categories();
    }

    private function prepare_batch_import($category_ids, $import_mode = 'skip_duplicates') {
        // Enhanced batch preparation with duplicate handling
        $settings = get_option($this->option_name, array());
        $batch_size = $settings['batch_size'] ?? EWCS_PRO_BATCH_SIZE;
        
        // Get products from selected categories
        $all_products = $this->api_handler->get_products_by_categories($category_ids);
        
        // Filter based on import mode
        if ($import_mode === 'skip_duplicates') {
            $all_products = $this->duplicate_handler->filter_existing_products($all_products);
        }
        
        $total_products = count($all_products);
        $total_batches = ceil($total_products / $batch_size);
        
        return array(
            'product_ids' => $all_products,
            'category_ids' => $category_ids,
            'import_mode' => $import_mode,
            'total_products' => $total_products,
            'total_batches' => $total_batches,
            'batch_size' => $batch_size,
            'current_batch' => 0,
            'imported_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'start_time' => time()
        );
    }

    private function process_current_batch($batch_data) {
        $current_batch = $batch_data['current_batch'];
        $batch_size = $batch_data['batch_size'];
        $start_index = $current_batch * $batch_size;
        $batch_product_ids = array_slice($batch_data['product_ids'], $start_index, $batch_size);
        
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($batch_product_ids as $product_id) {
            try {
                $result = $this->import_product($product_id, $batch_data['import_mode']);
                
                switch ($result['action']) {
                    case 'imported':
                        $imported++;
                        break;
                    case 'updated':
                        $updated++;
                        break;
                    case 'skipped':
                        $skipped++;
                        break;
                }
            } catch (Exception $e) {
                $errors++;
                $this->logger->log('Product Import', 'error', $e->getMessage(), null, $product_id);
            }
        }
        
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }

    private function import_product($remote_product_id, $import_mode = 'skip_duplicates') {
        // Get product data from remote
        $product_data = $this->api_handler->get_product($remote_product_id);
        
        // Check for existing product
        $existing_id = $this->duplicate_handler->find_existing_product($product_data);
        
        if ($existing_id) {
            switch ($import_mode) {
                case 'skip_duplicates':
                    return array('action' => 'skipped', 'product_id' => $existing_id);
                    
                case 'update_existing':
                    $this->update_existing_product($existing_id, $product_data);
                    return array('action' => 'updated', 'product_id' => $existing_id);
                    
                case 'create_duplicates':
                    // Mark as potential duplicate but create new
                    $new_id = $this->create_new_product($product_data);
                    $this->duplicate_handler->mark_as_duplicate($new_id, $existing_id, $remote_product_id);
                    return array('action' => 'imported', 'product_id' => $new_id);
            }
        } else {
            $new_id = $this->create_new_product($product_data);
            return array('action' => 'imported', 'product_id' => $new_id);
        }
    }

    private function create_new_product($product_data) {
        // Enhanced product creation with better error handling
        $settings = get_option($this->option_name, array());
        
        // Determine product type
        $product_type = $product_data['type'] ?? 'simple';
        switch ($product_type) {
            case 'variable':
                $product = new WC_Product_Variable();
                break;
            case 'grouped':
                $product = new WC_Product_Grouped();
                break;
            case 'external':
                $product = new WC_Product_External();
                break;
            default:
                $product = new WC_Product_Simple();
        }
        
        // Set basic product data
        $product->set_name($product_data['name']);
        $product->set_slug($this->generate_unique_slug($product_data['slug']));
        $product->set_description($product_data['description']);
        $product->set_short_description($product_data['short_description']);
        $product->set_status($settings['import_status'] ?? 'publish');

        // Handle SKU with duplicate prevention
        if (!empty($product_data['sku'])) {
            $unique_sku = $this->duplicate_handler->generate_unique_sku($product_data['sku']);
            $product->set_sku($unique_sku);
        }

        // Set prices
        if ($settings['sync_prices'] ?? true) {
            if (!empty($product_data['regular_price'])) {
                $product->set_regular_price($product_data['regular_price']);
            }
            if (!empty($product_data['sale_price'])) {
                $product->set_sale_price($product_data['sale_price']);
            }
        }

        // Set stock
        if ($settings['sync_stock'] ?? true) {
            $product->set_manage_stock($product_data['manage_stock'] ?? false);
            if ($product_data['manage_stock']) {
                $product->set_stock_quantity($product_data['stock_quantity']);
            }
            $product->set_stock_status($product_data['stock_status'] ?? 'instock');
        }

        // Set dimensions and weight
        if (!empty($product_data['weight'])) {
            $product->set_weight($product_data['weight']);
        }
        if (!empty($product_data['dimensions'])) {
            $product->set_length($product_data['dimensions']['length'] ?? '');
            $product->set_width($product_data['dimensions']['width'] ?? '');
            $product->set_height($product_data['dimensions']['height'] ?? '');
        }

        $product_id = $product->save();
        if (!$product_id) {
            throw new Exception(__('Failed to save product', 'enhanced-wc-sync-pro'));
        }

        // Store sync metadata
        update_post_meta($product_id, '_ewcs_pro_remote_id', $product_data['id']);
        update_post_meta($product_id, '_ewcs_pro_last_sync', current_time('timestamp'));
        update_post_meta($product_id, '_ewcs_pro_import_mode', 'api');

        // Handle categories, images, attributes
        $this->sync_product_categories($product_id, $product_data['categories'] ?? array());
        if ($settings['sync_images'] ?? false) {
            $this->sync_product_images($product_id, $product_data['images'] ?? array());
        }
        if ($settings['sync_attributes'] ?? false) {
            $this->sync_product_attributes($product_id, $product_data['attributes'] ?? array());
        }

        return $product_id;
    }

    private function update_existing_product($product_id, $product_data) {
        $settings = get_option($this->option_name, array());
        $product = wc_get_product($product_id);
        
        if (!$product) {
            throw new Exception(__('Product not found', 'enhanced-wc-sync-pro'));
        }

        // Update basic data
        $product->set_name($product_data['name']);
        $product->set_description($product_data['description']);
        $product->set_short_description($product_data['short_description']);

        // Update prices if enabled
        if ($settings['sync_prices'] ?? true) {
            if (!empty($product_data['regular_price'])) {
                $product->set_regular_price($product_data['regular_price']);
            }
            if (!empty($product_data['sale_price'])) {
                $product->set_sale_price($product_data['sale_price']);
            } else {
                $product->set_sale_price('');
            }
        }

        // Update stock if enabled
        if ($settings['sync_stock'] ?? true) {
            $product->set_manage_stock($product_data['manage_stock'] ?? false);
            if ($product_data['manage_stock']) {
                $product->set_stock_quantity($product_data['stock_quantity']);
            }
            $product->set_stock_status($product_data['stock_status'] ?? 'instock');
        }

        $product->save();
        
        // Update sync metadata
        update_post_meta($product_id, '_ewcs_pro_remote_id', $product_data['id']);
        update_post_meta($product_id, '_ewcs_pro_last_sync', current_time('timestamp'));
        
        return $product_id;
    }

    private function generate_unique_slug($base_slug) {
        $slug = sanitize_title($base_slug);
        $original_slug = $slug;
        $counter = 1;
        
        while (get_page_by_path($slug, OBJECT, 'product')) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function sync_product_categories($product_id, $categories) {
        if (empty($categories)) return;
        
        $category_ids = array();
        foreach ($categories as $category_data) {
            $terms = get_terms(array(
                'taxonomy' => 'product_cat',
                'meta_key' => '_ewcs_pro_remote_id',
                'meta_value' => $category_data['id'],
                'hide_empty' => false
            ));
            
            if (!empty($terms)) {
                $category_ids[] = $terms[0]->term_id;
            }
        }
        
        if (!empty($category_ids)) {
            wp_set_object_terms($product_id, $category_ids, 'product_cat');
        }
    }

    private function sync_product_images($product_id, $images) {
        if (empty($images)) return;
        
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $image_ids = array();
        foreach ($images as $image_data) {
            if (empty($image_data['src'])) continue;
            
            try {
                $image_id = $this->import_image($image_data['src'], $product_id, $image_data['alt'] ?? '');
                if ($image_id) {
                    $image_ids[] = $image_id;
                }
            } catch (Exception $e) {
                $this->logger->log('Image Import', 'error', 
                    sprintf(__('Failed to import image: %s', 'enhanced-wc-sync-pro'), $e->getMessage()), 
                    $product_id);
            }
        }
        
        if (!empty($image_ids)) {
            set_post_thumbnail($product_id, $image_ids[0]);
            if (count($image_ids) > 1) {
                update_post_meta($product_id, '_product_image_gallery', implode(',', array_slice($image_ids, 1)));
            }
        }
    }

    private function import_image($image_url, $product_id, $alt_text = '') {
        // Check if image already exists
        $existing_id = $this->get_attachment_by_url($image_url);
        if ($existing_id) {
            return $existing_id;
        }

        $tmp = download_url($image_url, 300);
        if (is_wp_error($tmp)) {
            throw new Exception($tmp->get_error_message());
        }

        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, $product_id, $alt_text);
        if (is_wp_error($id)) {
            @unlink($tmp);
            throw new Exception($id->get_error_message());
        }

        update_post_meta($id, '_ewcs_pro_original_url', $image_url);
        return $id;
    }

    private function get_attachment_by_url($url) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ewcs_pro_original_url' AND meta_value = %s LIMIT 1",
            $url
        ));
    }

    private function sync_product_attributes($product_id, $attributes) {
        // Implementation for syncing product attributes
        if (empty($attributes)) return;
        
        $product_attributes = array();
        foreach ($attributes as $attribute_data) {
            // Create or get attribute
            $attribute_name = wc_sanitize_taxonomy_name($attribute_data['name']);
            $taxonomy = wc_attribute_taxonomy_name($attribute_name);
            
            if (!empty($attribute_data['options'])) {
                $term_ids = array();
                foreach ($attribute_data['options'] as $option) {
                    $term = get_term_by('name', $option, $taxonomy);
                    if (!$term) {
                        $term_result = wp_insert_term($option, $taxonomy);
                        if (!is_wp_error($term_result)) {
                            $term_ids[] = $term_result['term_id'];
                        }
                    } else {
                        $term_ids[] = $term->term_id;
                    }
                }
                wp_set_object_terms($product_id, $term_ids, $taxonomy);
            }
            
            $product_attributes[$taxonomy] = array(
                'name' => $taxonomy,
                'value' => '',
                'position' => $attribute_data['position'] ?? 0,
                'is_visible' => $attribute_data['visible'] ?? true,
                'is_variation' => $attribute_data['variation'] ?? false,
                'is_taxonomy' => 1
            );
        }
        
        update_post_meta($product_id, '_product_attributes', $product_attributes);
    }

    private function get_sync_stats() {
        global $wpdb;
        
        $total_synced = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ewcs_pro_remote_id'"
        );
        
        $batch_data = get_option($this->batch_option, array());
        $last_batch_size = $batch_data['imported_count'] ?? 0;
        
        $table_name = $wpdb->prefix . 'ewcs_pro_sync_log';
        $errors = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
            $errors = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = 'error' AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
        }
        
        $last_sync = $wpdb->get_var(
            "SELECT MAX(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_ewcs_pro_last_sync'"
        );
        
        return array(
            'total_synced' => intval($total_synced),
            'last_batch_size' => intval($last_batch_size),
            'errors' => intval($errors),
            'last_sync' => $last_sync ? intval($last_sync) : null
        );
    }

    private function save_settings() {
        // Debug: Check if we're getting the POST data
        error_log('EWCS Debug: save_settings called');
        error_log('EWCS Debug: POST data: ' . print_r($_POST, true));
        
        if (!isset($_POST['ewcs_pro_nonce']) || !wp_verify_nonce($_POST['ewcs_pro_nonce'], 'ewcs_pro_settings')) {
            error_log('EWCS Debug: Nonce verification failed');
            wp_die(__('Security check failed', 'enhanced-wc-sync-pro'));
        }

        $settings = array(
            'remote_url' => sanitize_url($_POST['remote_url'] ?? ''),
            'consumer_key' => sanitize_text_field($_POST['consumer_key'] ?? ''),
            'consumer_secret' => sanitize_text_field($_POST['consumer_secret'] ?? ''),
            'sync_direction' => sanitize_text_field($_POST['sync_direction'] ?? 'pull'),
            'sync_interval' => sanitize_text_field($_POST['sync_interval'] ?? 'manual'),
            'sync_products' => isset($_POST['sync_products']),
            'sync_stock' => isset($_POST['sync_stock']),
            'sync_prices' => isset($_POST['sync_prices']),
            'sync_images' => isset($_POST['sync_images']),
            'sync_categories' => isset($_POST['sync_categories']),
            'sync_attributes' => isset($_POST['sync_attributes']),
            'sync_variations' => isset($_POST['sync_variations']),
            'sync_reviews' => isset($_POST['sync_reviews']),
            'import_status' => sanitize_text_field($_POST['import_status'] ?? 'publish'),
            'batch_size' => max(5, min(100, intval($_POST['batch_size'] ?? EWCS_PRO_BATCH_SIZE))),
            'import_timeout' => max(60, min(3600, intval($_POST['import_timeout'] ?? 300))),
            'duplicate_handling' => sanitize_text_field($_POST['duplicate_handling'] ?? 'skip'),
            'duplicate_check_sku' => isset($_POST['duplicate_check_sku']),
            'duplicate_check_name' => isset($_POST['duplicate_check_name']),
            'duplicate_check_slug' => isset($_POST['duplicate_check_slug']),
            'auto_resolve_duplicates' => isset($_POST['auto_resolve_duplicates']),
            'log_level' => sanitize_text_field($_POST['log_level'] ?? 'all'),
            'log_retention' => max(1, min(365, intval($_POST['log_retention'] ?? 30))),
            'enable_performance_monitoring' => isset($_POST['enable_performance_monitoring']),
            'enable_rate_limiting' => isset($_POST['enable_rate_limiting']),
            'enable_debug_mode' => isset($_POST['enable_debug_mode']),
            'webhook_url' => sanitize_url($_POST['webhook_url'] ?? ''),
            'custom_fields' => sanitize_textarea_field($_POST['custom_fields'] ?? ''),
            'verify_ssl' => isset($_POST['verify_ssl']),
            'require_authentication' => isset($_POST['require_authentication']),
            'enable_ip_whitelist' => isset($_POST['enable_ip_whitelist'])
        );

        error_log('EWCS Debug: Settings to save: ' . print_r($settings, true));
        
        $updated = update_option($this->option_name, $settings);
        
        error_log('EWCS Debug: Update result: ' . ($updated ? 'true' : 'false'));
        error_log('EWCS Debug: Current option value: ' . print_r(get_option($this->option_name), true));

        // Update cron schedule
        wp_clear_scheduled_hook('ewcs_pro_sync_hook');
        if ($settings['sync_interval'] !== 'manual') {
            wp_schedule_event(time(), $settings['sync_interval'], 'ewcs_pro_sync_hook');
        }

        // Add success message
        if ($updated) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'enhanced-wc-sync-pro') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info is-dismissible"><p>' . __('No changes were made to the settings.', 'enhanced-wc-sync-pro') . '</p></div>';
            });
        }
    }

    public function scheduled_sync() {
        if (wp_doing_cron()) {
            try {
                $this->sync_stock_and_prices();
            } catch (Exception $e) {
                $this->logger->log('Scheduled Sync', 'error', $e->getMessage());
            }
        }
    }

    private function sync_stock_and_prices() {
        $settings = get_option($this->option_name, array());
        if (!$settings['sync_stock'] && !$settings['sync_prices']) {
            throw new Exception(__('Stock and price sync is disabled', 'enhanced-wc-sync-pro'));
        }

        global $wpdb;
        $synced_products = $wpdb->get_results(
            "SELECT post_id, meta_value as remote_id FROM {$wpdb->postmeta} WHERE meta_key = '_ewcs_pro_remote_id'"
        );

        $updated_count = 0;
        $error_count = 0;

        foreach ($synced_products as $synced_product) {
            try {
                $remote_product = $this->api_handler->get_product($synced_product->remote_id);
                $product = wc_get_product($synced_product->post_id);
                
                if (!$product) continue;

                if ($settings['sync_stock']) {
                    $product->set_manage_stock($remote_product['manage_stock'] ?? false);
                    if ($remote_product['manage_stock']) {
                        $product->set_stock_quantity($remote_product['stock_quantity']);
                    }
                    $product->set_stock_status($remote_product['stock_status'] ?? 'instock');
                }

                if ($settings['sync_prices']) {
                    if (!empty($remote_product['regular_price'])) {
                        $product->set_regular_price($remote_product['regular_price']);
                    }
                    if (!empty($remote_product['sale_price'])) {
                        $product->set_sale_price($remote_product['sale_price']);
                    } else {
                        $product->set_sale_price('');
                    }
                }

                $product->save();
                update_post_meta($synced_product->post_id, '_ewcs_pro_last_sync', current_time('timestamp'));
                $updated_count++;

            } catch (Exception $e) {
                $error_count++;
                $this->logger->log('Stock/Price Sync', 'error', $e->getMessage(), $synced_product->post_id);
            }
        }

        $message = sprintf(__('Updated %d products with %d errors', 'enhanced-wc-sync-pro'), $updated_count, $error_count);
        $this->logger->log('Stock/Price Sync', 'success', $message);
        
        return array(
            'success' => true,
            'message' => $message,
            'updated' => $updated_count,
            'errors' => $error_count
        );
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=ewcs-pro-settings') . '">' . __('Settings', 'enhanced-wc-sync-pro') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
new EnhancedWCSyncPro();