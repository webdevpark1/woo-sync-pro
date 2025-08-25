<?php
/**
 * Enhanced WooCommerce Sync Pro - Sync Logger
 * 
 * Handles logging of all sync activities and operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWCS_Sync_Logger {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ewcs_pro_sync_log';
    }
    
    /**
     * Log an action
     */
    public function log($action, $status, $message, $product_id = null, $remote_id = null, $meta = null) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'action' => sanitize_text_field($action),
                'status' => sanitize_text_field($status),
                'message' => sanitize_textarea_field($message),
                'product_id' => $product_id ? intval($product_id) : null,
                'remote_id' => $remote_id ? intval($remote_id) : null,
                'meta' => $meta ? json_encode($meta) : null,
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );
        
        // Clean up old logs periodically
        if (rand(1, 100) === 1) {
            $this->cleanup_old_logs();
        }
    }
    
    /**
     * Get recent logs
     */
    public function get_recent_logs($limit = 50, $filters = array(), $offset = 0) {
        global $wpdb;
        
        $where_clauses = array();
        $where_values = array();
        
        // Add filters
        if (!empty($filters['action'])) {
            $where_clauses[] = 'action = %s';
            $where_values[] = $filters['action'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['product_id'])) {
            $where_clauses[] = 'product_id = %d';
            $where_values[] = intval($filters['product_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'timestamp >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'timestamp <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT * FROM {$this->table_name} {$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $where_values[] = intval($limit);
        $where_values[] = intval($offset);
        
        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_results($wpdb->prepare($sql, $limit, $offset));
        }
    }
    
    /**
     * Get logs for specific product
     */
    public function get_product_logs($product_id, $limit = 20) {
        return $this->get_recent_logs($limit, array('product_id' => $product_id));
    }
    
    /**
     * Get log statistics
     */
    public function get_log_stats($period = '24 HOUR') {
        global $wpdb;
        
        // Use direct string concatenation for INTERVAL since it doesn't work well with prepared statements
        $stats = $wpdb->get_results("
            SELECT 
                status,
                COUNT(*) as count,
                action
            FROM {$this->table_name} 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL {$period})
            GROUP BY status, action
            ORDER BY count DESC
        ");
        
        $formatted_stats = array(
            'total' => 0,
            'success' => 0,
            'error' => 0,
            'warning' => 0,
            'info' => 0,
            'by_action' => array()
        );
        
        foreach ($stats as $stat) {
            $formatted_stats['total'] += $stat->count;
            $formatted_stats[$stat->status] = ($formatted_stats[$stat->status] ?? 0) + $stat->count;
            
            if (!isset($formatted_stats['by_action'][$stat->action])) {
                $formatted_stats['by_action'][$stat->action] = array(
                    'total' => 0,
                    'success' => 0,
                    'error' => 0,
                    'warning' => 0,
                    'info' => 0
                );
            }
            
            $formatted_stats['by_action'][$stat->action]['total'] += $stat->count;
            $formatted_stats['by_action'][$stat->action][$stat->status] += $stat->count;
        }
        
        return $formatted_stats;
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        global $wpdb;
        return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
    
    /**
     * Clear logs older than specified days
     */
    public function clear_old_logs($days = 30) {
        global $wpdb;
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
    
    /**
     * Export logs as CSV
     */
    public function export_logs_csv($filters = array(), $filename = null) {
        if (!$filename) {
            $filename = 'ewcs-sync-logs-' . date('Y-m-d-H-i-s') . '.csv';
        }
        
        $logs = $this->get_recent_logs(10000, $filters); // Get up to 10k logs
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'ID',
            'Timestamp',
            'Action',
            'Status',
            'Message',
            'Product ID',
            'Remote ID',
            'Meta'
        ));
        
        // CSV data
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log->id,
                $log->timestamp,
                $log->action,
                $log->status,
                $log->message,
                $log->product_id,
                $log->remote_id,
                $log->meta
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get error logs for debugging
     */
    public function get_error_logs($limit = 100) {
        return $this->get_recent_logs($limit, array('status' => 'error'));
    }
    
    /**
     * Get success rate for a specific action
     */
    public function get_success_rate($action, $period = '24 HOUR') {
        global $wpdb;
        
        $total = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$this->table_name} 
            WHERE action = %s 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL %s)
        ", $action, $period));
        
        if ($total == 0) {
            return 0;
        }
        
        $success = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$this->table_name} 
            WHERE action = %s 
            AND status = 'success' 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL %s)
        ", $action, $period));
        
        return round(($success / $total) * 100, 2);
    }
    
    /**
     * Log batch operation
     */
    public function log_batch($action, $total_items, $processed_items, $success_count, $error_count, $meta = null) {
        $message = sprintf(
            __('Batch %s: Processed %d/%d items (%d success, %d errors)', 'enhanced-wc-sync-pro'),
            $action,
            $processed_items,
            $total_items,
            $success_count,
            $error_count
        );
        
        $status = $error_count > 0 ? 'warning' : 'success';
        
        $batch_meta = array_merge(array(
            'total_items' => $total_items,
            'processed_items' => $processed_items,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'success_rate' => $total_items > 0 ? round(($success_count / $total_items) * 100, 2) : 0
        ), $meta ?: array());
        
        $this->log('Batch ' . $action, $status, $message, null, null, $batch_meta);
    }
    
    /**
     * Log API rate limit information
     */
    public function log_rate_limit($limit, $remaining, $reset_time) {
        $message = sprintf(
            __('API Rate Limit: %d/%d requests used, reset at %s', 'enhanced-wc-sync-pro'),
            $limit - $remaining,
            $limit,
            date('H:i:s', $reset_time)
        );
        
        $status = 'info';
        if ($remaining < ($limit * 0.1)) { // Less than 10% remaining
            $status = 'warning';
        }
        
        $this->log('Rate Limit', $status, $message, null, null, array(
            'limit' => $limit,
            'remaining' => $remaining,
            'reset_time' => $reset_time,
            'usage_percent' => round((($limit - $remaining) / $limit) * 100, 2)
        ));
    }
    
    /**
     * Create performance log entry
     */
    public function log_performance($operation, $duration, $memory_usage, $additional_data = array()) {
        $message = sprintf(
            __('Performance: %s completed in %s seconds, memory: %s MB', 'enhanced-wc-sync-pro'),
            $operation,
            number_format($duration, 3),
            number_format($memory_usage / 1024 / 1024, 2)
        );
        
        $meta = array_merge(array(
            'duration_seconds' => $duration,
            'memory_bytes' => $memory_usage,
            'memory_mb' => round($memory_usage / 1024 / 1024, 2)
        ), $additional_data);
        
        $this->log('Performance', 'info', $message, null, null, $meta);
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics($period = '24 HOUR') {
        global $wpdb;
        
        // Use direct string concatenation for INTERVAL
        $logs = $wpdb->get_results("
            SELECT meta 
            FROM {$this->table_name} 
            WHERE action = 'Performance' 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL {$period})
            AND meta IS NOT NULL
        ");
        
        $metrics = array(
            'avg_duration' => 0,
            'max_duration' => 0,
            'min_duration' => PHP_FLOAT_MAX,
            'avg_memory' => 0,
            'max_memory' => 0,
            'total_operations' => 0
        );
        
        $total_duration = 0;
        $total_memory = 0;
        
        foreach ($logs as $log) {
            $meta = json_decode($log->meta, true);
            if (!$meta || !isset($meta['duration_seconds'], $meta['memory_bytes'])) {
                continue;
            }
            
            $duration = $meta['duration_seconds'];
            $memory = $meta['memory_bytes'];
            
            $total_duration += $duration;
            $total_memory += $memory;
            $metrics['total_operations']++;
            
            $metrics['max_duration'] = max($metrics['max_duration'], $duration);
            $metrics['min_duration'] = min($metrics['min_duration'], $duration);
            $metrics['max_memory'] = max($metrics['max_memory'], $memory);
        }
        
        if ($metrics['total_operations'] > 0) {
            $metrics['avg_duration'] = $total_duration / $metrics['total_operations'];
            $metrics['avg_memory'] = $total_memory / $metrics['total_operations'];
        }
        
        if ($metrics['min_duration'] === PHP_FLOAT_MAX) {
            $metrics['min_duration'] = 0;
        }
        
        return $metrics;
    }
    
    /**
     * Cleanup old logs (called periodically)
     */
    private function cleanup_old_logs($days = 90) {
        global $wpdb;
        
        // Keep logs for 90 days by default
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        // Also limit total number of logs (keep last 50,000)
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        if ($count > 50000) {
            $wpdb->query("
                DELETE FROM {$this->table_name} 
                WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM {$this->table_name} 
                        ORDER BY timestamp DESC 
                        LIMIT 50000
                    ) AS temp
                )
            ");
        }
    }
}