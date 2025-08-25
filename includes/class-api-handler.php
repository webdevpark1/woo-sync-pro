<?php
/**
 * Enhanced WooCommerce Sync Pro - API Handler
 * 
 * Handles all API communications with remote WooCommerce sites
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWCS_API_Handler {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('ewcs_pro_settings', array());
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->settings['remote_url']) || 
            empty($this->settings['consumer_key']) || 
            empty($this->settings['consumer_secret'])) {
            return new WP_Error('missing_credentials', __('API credentials not configured', 'enhanced-wc-sync-pro'));
        }
        
        try {
            $response = $this->make_request('system_status');
            return $response;
        } catch (Exception $e) {
            return new WP_Error('connection_failed', $e->getMessage());
        }
    }
    
    /**
     * Make API request
     */
    public function make_request($endpoint, $params = array(), $method = 'GET', $data = null) {
        if (empty($this->settings['remote_url']) || 
            empty($this->settings['consumer_key']) || 
            empty($this->settings['consumer_secret'])) {
            throw new Exception(__('API credentials not configured', 'enhanced-wc-sync-pro'));
        }
        
        $url = trailingslashit($this->settings['remote_url']) . 'wp-json/wc/v3/' . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->settings['consumer_key'] . ':' . $this->settings['consumer_secret']),
                'Content-Type' => 'application/json',
                'User-Agent' => 'Enhanced WC Sync Pro/' . EWCS_PRO_VERSION
            )
        );
        
        // Add query parameters
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        // Add body data for POST/PUT requests
        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code >= 400) {
            $error_data = json_decode($body, true);
            $error_message = 'HTTP ' . $code;
            
            if (isset($error_data['message'])) {
                $error_message .= ': ' . $error_data['message'];
            } elseif (isset($error_data['data']['message'])) {
                $error_message .= ': ' . $error_data['data']['message'];
            } else {
                $error_message .= ': ' . $body;
            }
            
            throw new Exception($error_message);
        }
        
        return json_decode($body, true);
    }
    
    /**
     * Get single product from remote
     */
    public function get_product($product_id) {
        return $this->make_request('products/' . $product_id);
    }
    
    /**
     * Get products by categories
     */
    public function get_products_by_categories($category_ids) {
        $all_products = array();
        
        // Get remote category IDs
        $remote_category_ids = array();
        foreach ($category_ids as $category_id) {
            $remote_id = get_term_meta($category_id, '_ewcs_pro_remote_id', true);
            if ($remote_id) {
                $remote_category_ids[] = $remote_id;
            }
        }
        
        if (empty($remote_category_ids)) {
            throw new Exception(__('Selected categories are not synced with remote site', 'enhanced-wc-sync-pro'));
        }
        
        // Get products from each category
        foreach ($remote_category_ids as $remote_cat_id) {
            $page = 1;
            do {
                $products = $this->make_request('products', array(
                    'category' => $remote_cat_id,
                    'per_page' => 100,
                    'page' => $page,
                    'status' => 'publish'
                ));
                
                foreach ($products as $product) {
                    $all_products[] = $product['id'];
                }
                
                $page++;
            } while (count($products) == 100);
        }
        
        // Remove duplicates
        return array_unique($all_products);
    }
    
    /**
     * Sync categories from remote
     */
    public function sync_categories() {
        $page = 1;
        $all_categories = array();
        
        do {
            $categories = $this->make_request('products/categories', array(
                'per_page' => 100,
                'page' => $page,
                'hide_empty' => false
            ));
            
            if (empty($categories)) {
                break;
            }
            
            foreach ($categories as $category_data) {
                try {
                    $category_id = $this->import_category($category_data);
                    if ($category_id) {
                        $all_categories[] = array(
                            'id' => $category_id,
                            'name' => $category_data['name'],
                            'remote_id' => $category_data['id'],
                            'count' => $category_data['count'] ?? 0
                        );
                    }
                } catch (Exception $e) {
                    error_log('Failed to import category ' . $category_data['name'] . ': ' . $e->getMessage());
                }
            }
            
            $page++;
        } while (count($categories) == 100);
        
        return $all_categories;
    }
    
    /**
     * Import single category
     */
    private function import_category($category_data) {
        // Check if category already exists by remote ID
        $existing_terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'meta_key' => '_ewcs_pro_remote_id',
            'meta_value' => $category_data['id'],
            'hide_empty' => false
        ));
        
        if (!empty($existing_terms)) {
            return $existing_terms[0]->term_id;
        }
        
        // Check by slug
        $term_by_slug = get_term_by('slug', $category_data['slug'], 'product_cat');
        if ($term_by_slug) {
            update_term_meta($term_by_slug->term_id, '_ewcs_pro_remote_id', $category_data['id']);
            return $term_by_slug->term_id;
        }
        
        // Handle parent category
        $parent_id = 0;
        if (!empty($category_data['parent'])) {
            $parent_terms = get_terms(array(
                'taxonomy' => 'product_cat',
                'meta_key' => '_ewcs_pro_remote_id',
                'meta_value' => $category_data['parent'],
                'hide_empty' => false
            ));
            
            if (!empty($parent_terms)) {
                $parent_id = $parent_terms[0]->term_id;
            }
        }
        
        // Create new category
        $result = wp_insert_term(
            $category_data['name'],
            'product_cat',
            array(
                'slug' => $category_data['slug'],
                'description' => $category_data['description'] ?? '',
                'parent' => $parent_id
            )
        );
        
        if (is_wp_error($result)) {
            throw new Exception('Failed to create category: ' . $result->get_error_message());
        }
        
        // Store remote ID and additional metadata
        update_term_meta($result['term_id'], '_ewcs_pro_remote_id', $category_data['id']);
        update_term_meta($result['term_id'], '_ewcs_pro_last_sync', current_time('timestamp'));
        
        // Handle category image if available
        if (!empty($category_data['image']['src'])) {
            try {
                $image_id = $this->import_category_image($category_data['image']['src'], $result['term_id']);
                if ($image_id) {
                    update_term_meta($result['term_id'], 'thumbnail_id', $image_id);
                }
            } catch (Exception $e) {
                // Log error but don't fail category creation
                error_log('Failed to import category image: ' . $e->getMessage());
            }
        }
        
        return $result['term_id'];
    }
    
    /**
     * Import category image
     */
    private function import_category_image($image_url, $term_id) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url, 300);
        if (is_wp_error($tmp)) {
            throw new Exception($tmp->get_error_message());
        }
        
        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );
        
        $id = media_handle_sideload($file_array, 0, 'Category image for term ' . $term_id);
        if (is_wp_error($id)) {
            @unlink($tmp);
            throw new Exception($id->get_error_message());
        }
        
        return $id;
    }
    
    /**
     * Get formatted categories for display
     */
    public function get_formatted_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_ewcs_pro_remote_id',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        $formatted_categories = array();
        foreach ($categories as $category) {
            $remote_id = get_term_meta($category->term_id, '_ewcs_pro_remote_id', true);
            $product_count = $this->get_remote_category_product_count($remote_id);
            
            $formatted_categories[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $product_count,
                'remote_id' => $remote_id,
                'parent' => $category->parent
            );
        }
        
        return $formatted_categories;
    }
    
    /**
     * Get product count for remote category
     */
    private function get_remote_category_product_count($remote_id) {
        if (!$remote_id) {
            return 0;
        }
        
        try {
            $response = wp_remote_get(
                trailingslashit($this->settings['remote_url']) . 'wp-json/wc/v3/products?' . http_build_query(array(
                    'category' => $remote_id,
                    'per_page' => 1,
                    'status' => 'publish'
                )),
                array(
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode($this->settings['consumer_key'] . ':' . $this->settings['consumer_secret'])
                    ),
                    'timeout' => 30
                )
            );
            
            if (!is_wp_error($response)) {
                $headers = wp_remote_retrieve_headers($response);
                return isset($headers['x-wp-total']) ? intval($headers['x-wp-total']) : 0;
            }
        } catch (Exception $e) {
            // Ignore errors and return 0
        }
        
        return 0;
    }
    
    /**
     * Create product on remote site
     */
    public function create_remote_product($product_data) {
        return $this->make_request('products', array(), 'POST', $product_data);
    }
    
    /**
     * Update product on remote site
     */
    public function update_remote_product($product_id, $product_data) {
        return $this->make_request('products/' . $product_id, array(), 'PUT', $product_data);
    }
    
    /**
     * Delete product on remote site
     */
    public function delete_remote_product($product_id, $force = false) {
        $params = $force ? array('force' => 'true') : array();
        return $this->make_request('products/' . $product_id, $params, 'DELETE');
    }
    
    /**
     * Get batch of products
     */
    public function get_products($params = array()) {
        $defaults = array(
            'per_page' => 100,
            'status' => 'publish'
        );
        
        $params = wp_parse_args($params, $defaults);
        return $this->make_request('products', $params);
    }
    
    /**
     * Get product variations
     */
    public function get_product_variations($product_id, $params = array()) {
        $defaults = array(
            'per_page' => 100
        );
        
        $params = wp_parse_args($params, $defaults);
        return $this->make_request('products/' . $product_id . '/variations', $params);
    }
    
    /**
     * Batch update products
     */
    public function batch_update_products($create = array(), $update = array(), $delete = array()) {
        $data = array();
        
        if (!empty($create)) {
            $data['create'] = $create;
        }
        
        if (!empty($update)) {
            $data['update'] = $update;
        }
        
        if (!empty($delete)) {
            $data['delete'] = $delete;
        }
        
        return $this->make_request('products/batch', array(), 'POST', $data);
    }
    
    /**
     * Get orders from remote site
     */
    public function get_orders($params = array()) {
        $defaults = array(
            'per_page' => 100,
            'status' => 'any'
        );
        
        $params = wp_parse_args($params, $defaults);
        return $this->make_request('orders', $params);
    }
    
    /**
     * Get customers from remote site
     */
    public function get_customers($params = array()) {
        $defaults = array(
            'per_page' => 100
        );
        
        $params = wp_parse_args($params, $defaults);
        return $this->make_request('customers', $params);
    }
    
    /**
     * Validate API credentials
     */
    public function validate_credentials() {
        try {
            $response = $this->make_request('');
            return !is_wp_error($response);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get API rate limit information
     */
    public function get_rate_limit_info() {
        try {
            $response = wp_remote_head(
                trailingslashit($this->settings['remote_url']) . 'wp-json/wc/v3/',
                array(
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode($this->settings['consumer_key'] . ':' . $this->settings['consumer_secret'])
                    )
                )
            );
            
            if (!is_wp_error($response)) {
                $headers = wp_remote_retrieve_headers($response);
                return array(
                    'limit' => $headers['x-wc-rate-limit'] ?? null,
                    'remaining' => $headers['x-wc-rate-limit-remaining'] ?? null,
                    'reset' => $headers['x-wc-rate-limit-reset'] ?? null
                );
            }
        } catch (Exception $e) {
            // Ignore errors
        }
        
        return null;
    }
    
    /**
     * Test webhook endpoint
     */
    public function test_webhook($webhook_url, $test_data = null) {
        if (!$test_data) {
            $test_data = array(
                'test' => true,
                'timestamp' => current_time('timestamp'),
                'message' => 'Test webhook from Enhanced WC Sync Pro'
            );
        }
        
        $response = wp_remote_post($webhook_url, array(
            'body' => json_encode($test_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'Enhanced WC Sync Pro/' . EWCS_PRO_VERSION
            ),
            'timeout' => 30
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200;
    }
}