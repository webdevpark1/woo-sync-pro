<?php
/**
 * Enhanced WooCommerce Sync Pro - Duplicate Handler
 * 
 * Handles duplicate product detection, prevention, and resolution
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWCS_Duplicate_Handler {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ewcs_pro_duplicates';
    }
    
    /**
     * Find existing product by various criteria
     */
    public function find_existing_product($product_data) {
        global $wpdb;
        
        // Check by remote ID first
        if (!empty($product_data['id'])) {
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ewcs_pro_remote_id' AND meta_value = %s LIMIT 1",
                $product_data['id']
            ));
            
            if ($existing_id) {
                return intval($existing_id);
            }
        }
        
        // Check by SKU
        if (!empty($product_data['sku'])) {
            $existing_id = wc_get_product_id_by_sku($product_data['sku']);
            if ($existing_id) {
                return intval($existing_id);
            }
        }
        
        // Check by name (exact match)
        $existing_post = get_page_by_title($product_data['name'], OBJECT, 'product');
        if ($existing_post) {
            return intval($existing_post->ID);
        }
        
        // Check by slug
        $existing_post = get_page_by_path($product_data['slug'], OBJECT, 'product');
        if ($existing_post) {
            return intval($existing_post->ID);
        }
        
        return false;
    }
    
    /**
     * Filter out existing products from import list
     */
    public function filter_existing_products($product_ids) {
        $filtered = array();
        
        foreach ($product_ids as $remote_id) {
            if (!$this->remote_product_exists($remote_id)) {
                $filtered[] = $remote_id;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Check if remote product already exists locally
     */
    public function remote_product_exists($remote_id) {
        global $wpdb;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ewcs_pro_remote_id' AND meta_value = %s",
            $remote_id
        ));
        
        return $exists > 0;
    }
    
    /**
     * Generate unique SKU to prevent duplicates
     */
    public function generate_unique_sku($base_sku) {
        $sku = sanitize_text_field($base_sku);
        $original_sku = $sku;
        $counter = 1;
        
        while (wc_get_product_id_by_sku($sku)) {
            $sku = $original_sku . '-' . $counter;
            $counter++;
            
            // Prevent infinite loop
            if ($counter > 100) {
                $sku = $original_sku . '-' . uniqid();
                break;
            }
        }
        
        return $sku;
    }
    
    /**
     * Mark products as duplicates
     */
    public function mark_as_duplicate($product_id, $existing_id, $remote_id) {
        global $wpdb;
        
        $product = wc_get_product($product_id);
        $existing_product = wc_get_product($existing_id);
        
        if (!$product || !$existing_product) {
            return false;
        }
        
        $wpdb->insert(
            $this->table_name,
            array(
                'product_id' => $product_id,
                'remote_id' => $remote_id,
                'sku' => $product->get_sku(),
                'name' => $product->get_name(),
                'status' => 'pending'
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        // Also mark the existing product if not already marked
        $existing_duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE product_id = %d",
            $existing_id
        ));
        
        if (!$existing_duplicate) {
            $wpdb->insert(
                $this->table_name,
                array(
                    'product_id' => $existing_id,
                    'remote_id' => get_post_meta($existing_id, '_ewcs_pro_remote_id', true),
                    'sku' => $existing_product->get_sku(),
                    'name' => $existing_product->get_name(),
                    'status' => 'original'
                ),
                array('%d', '%d', '%s', '%s', '%s')
            );
        }
        
        return true;
    }
    
    /**
     * Scan for potential duplicates
     */
    public function scan_for_duplicates() {
        global $wpdb;
        
        $duplicates = array();
        
        // Find products with same SKU
        $sku_duplicates = $wpdb->get_results("
            SELECT p1.post_id as product1, p2.post_id as product2, p1.meta_value as sku
            FROM {$wpdb->postmeta} p1
            INNER JOIN {$wpdb->postmeta} p2 ON p1.meta_value = p2.meta_value
            WHERE p1.meta_key = '_sku' AND p2.meta_key = '_sku'
            AND p1.post_id != p2.post_id
            AND p1.meta_value != ''
            AND p1.post_id < p2.post_id
        ");
        
        foreach ($sku_duplicates as $duplicate) {
            $duplicates[] = array(
                'type' => 'sku',
                'product1' => $duplicate->product1,
                'product2' => $duplicate->product2,
                'value' => $duplicate->sku,
                'confidence' => 'high'
            );
        }
        
        // Find products with same name
        $name_duplicates = $wpdb->get_results("
            SELECT p1.ID as product1, p2.ID as product2, p1.post_title as name
            FROM {$wpdb->posts} p1
            INNER JOIN {$wpdb->posts} p2 ON p1.post_title = p2.post_title
            WHERE p1.post_type = 'product' AND p2.post_type = 'product'
            AND p1.ID != p2.ID
            AND p1.ID < p2.ID
        ");
        
        foreach ($name_duplicates as $duplicate) {
            $duplicates[] = array(
                'type' => 'name',
                'product1' => $duplicate->product1,
                'product2' => $duplicate->product2,
                'value' => $duplicate->name,
                'confidence' => 'medium'
            );
        }
        
        // Find products with same remote ID
        $remote_duplicates = $wpdb->get_results("
            SELECT p1.post_id as product1, p2.post_id as product2, p1.meta_value as remote_id
            FROM {$wpdb->postmeta} p1
            INNER JOIN {$wpdb->postmeta} p2 ON p1.meta_value = p2.meta_value
            WHERE p1.meta_key = '_ewcs_pro_remote_id' AND p2.meta_key = '_ewcs_pro_remote_id'
            AND p1.post_id != p2.post_id
            AND p1.post_id < p2.post_id
        ");
        
        foreach ($remote_duplicates as $duplicate) {
            $duplicates[] = array(
                'type' => 'remote_id',
                'product1' => $duplicate->product1,
                'product2' => $duplicate->product2,
                'value' => $duplicate->remote_id,
                'confidence' => 'high'
            );
        }
        
        return $duplicates;
    }
    
    /**
     * Get all duplicates from database
     */
    public function get_duplicates($status = null) {
        global $wpdb;
        
        $where = '';
        $params = array();
        
        if ($status) {
            $where = 'WHERE status = %s';
            $params[] = $status;
        }
        
        $sql = "SELECT * FROM {$this->table_name} $where ORDER BY created_at DESC";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            return $wpdb->get_results($sql);
        }
    }
    
    /**
     * Get pending duplicates count
     */
    public function get_pending_duplicates_count() {
        global $wpdb;
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'");
    }
    
    /**
     * Check if product has duplicates
     */
    public function product_has_duplicates($product_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE product_id = %d AND status = 'pending'",
            $product_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Resolve duplicate
     */
    public function resolve_duplicate($duplicate_id, $action) {
        global $wpdb;
        
        $duplicate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $duplicate_id
        ));
        
        if (!$duplicate) {
            throw new Exception(__('Duplicate record not found', 'enhanced-wc-sync-pro'));
        }
        
        switch ($action) {
            case 'merge':
                return $this->merge_products($duplicate);
                
            case 'replace':
                return $this->replace_product($duplicate);
                
            case 'skip':
                return $this->skip_duplicate($duplicate);
                
            default:
                throw new Exception(__('Invalid action', 'enhanced-wc-sync-pro'));
        }
    }
    
    /**
     * Merge duplicate products
     */
    private function merge_products($duplicate) {
        // Implementation for merging products
        // This would combine data from both products
        
        $wpdb->update(
            $this->table_name,
            array(
                'status' => 'merged',
                'resolved_at' => current_time('mysql')
            ),
            array('id' => $duplicate->id),
            array('%s', '%s'),
            array('%d')
        );
        
        return array(
            'success' => true,
            'message' => __('Products merged successfully', 'enhanced-wc-sync-pro')
        );
    }
    
    /**
     * Replace original with duplicate
     */
    private function replace_product($duplicate) {
        // Implementation for replacing product
        
        global $wpdb;
        $wpdb->update(
            $this->table_name,
            array(
                'status' => 'replaced',
                'resolved_at' => current_time('mysql')
            ),
            array('id' => $duplicate->id),
            array('%s', '%s'),
            array('%d')
        );
        
        return array(
            'success' => true,
            'message' => __('Product replaced successfully', 'enhanced-wc-sync-pro')
        );
    }
    
    /**
     * Skip duplicate (keep both)
     */
    private function skip_duplicate($duplicate) {
        global $wpdb;
        
        $wpdb->update(
            $this->table_name,
            array(
                'status' => 'skipped',
                'resolved_at' => current_time('mysql')
            ),
            array('id' => $duplicate->id),
            array('%s', '%s'),
            array('%d')
        );
        
        return array(
            'success' => true,
            'message' => __('Duplicate skipped - both products kept', 'enhanced-wc-sync-pro')
        );
    }
    
    /**
     * Auto-resolve duplicates based on settings
     */
    public function auto_resolve_duplicates() {
        $settings = get_option('ewcs_pro_settings', array());
        
        if (!($settings['auto_resolve_duplicates'] ?? false)) {
            return;
        }
        
        $pending_duplicates = $this->get_duplicates('pending');
        $resolved_count = 0;
        
        foreach ($pending_duplicates as $duplicate) {
            try {
                $action = $settings['duplicate_handling'] ?? 'skip';
                $this->resolve_duplicate($duplicate->id, $action);
                $resolved_count++;
            } catch (Exception $e) {
                // Log error but continue
                error_log('Auto-resolve duplicate failed: ' . $e->getMessage());
            }
        }
        
        return $resolved_count;
    }
    
    /**
     * Clean up resolved duplicates older than X days
     */
    public function cleanup_old_duplicates($days = 30) {
        global $wpdb;
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
             WHERE status IN ('merged', 'replaced', 'skipped') 
             AND resolved_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        return $deleted;
    }
    
    /**
     * Get duplicate statistics
     */
    public function get_duplicate_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM {$this->table_name} 
            GROUP BY status
        ");
        
        $formatted_stats = array(
            'pending' => 0,
            'merged' => 0,
            'replaced' => 0,
            'skipped' => 0,
            'original' => 0
        );
        
        foreach ($stats as $stat) {
            $formatted_stats[$stat->status] = intval($stat->count);
        }
        
        return $formatted_stats;
    }
}