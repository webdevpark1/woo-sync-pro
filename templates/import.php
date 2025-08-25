<?php
/**
 * Enhanced WooCommerce Sync Pro - Import Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ewcs-pro-dashboard">
    <div class="ewcs-pro-container">
        <div class="ewcs-pro-header">
            <h1><?php _e('Import Products', 'enhanced-wc-sync-pro'); ?></h1>
            <p><?php _e('Select categories and import products with intelligent batch processing and duplicate handling', 'enhanced-wc-sync-pro'); ?></p>
        </div>
        
        <!-- Step 1: Load Categories -->
        <div class="ewcs-pro-card">
            <h2><?php _e('Step 1: Load Categories', 'enhanced-wc-sync-pro'); ?></h2>
            <p><?php _e('First, sync categories from the remote site to see available product categories.', 'enhanced-wc-sync-pro'); ?></p>
            <div class="ewcs-pro-actions">
                <button id="sync-categories" class="ewcs-pro-btn ewcs-pro-btn-primary">
                    <span class="dashicons dashicons-cloud-download"></span>
                    <?php _e('Load Categories', 'enhanced-wc-sync-pro'); ?>
                </button>
                <button id="refresh-categories" class="ewcs-pro-btn ewcs-pro-btn-secondary" style="display: none;">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh Categories', 'enhanced-wc-sync-pro'); ?>
                </button>
            </div>
        </div>
        
        <!-- Step 2: Select Import Mode -->
        <div id="import-mode-section" class="ewcs-pro-card" style="display: none;">
            <h2><?php _e('Step 2: Choose Import Mode', 'enhanced-wc-sync-pro'); ?></h2>
            <p><?php _e('Select how you want to handle existing products during import.', 'enhanced-wc-sync-pro'); ?></p>
            
            <div class="ewcs-import-modes">
                <div class="ewcs-import-mode">
                    <label>
                        <input type="radio" name="import_mode" value="skip_duplicates" checked>
                        <div class="ewcs-import-mode-title"><?php _e('Skip Duplicates', 'enhanced-wc-sync-pro'); ?></div>
                        <div class="ewcs-import-mode-desc"><?php _e('Skip products that already exist (recommended)', 'enhanced-wc-sync-pro'); ?></div>
                    </label>
                </div>
                
                <div class="ewcs-import-mode">
                    <label>
                        <input type="radio" name="import_mode" value="update_existing">
                        <div class="ewcs-import-mode-title"><?php _e('Update Existing', 'enhanced-wc-sync-pro'); ?></div>
                        <div class="ewcs-import-mode-desc"><?php _e('Update existing products with remote data', 'enhanced-wc-sync-pro'); ?></div>
                    </label>
                </div>
                
                <div class="ewcs-import-mode">
                    <label>
                        <input type="radio" name="import_mode" value="create_duplicates">
                        <div class="ewcs-import-mode-title"><?php _e('Allow Duplicates', 'enhanced-wc-sync-pro'); ?></div>
                        <div class="ewcs-import-mode-desc"><?php _e('Create new products even if duplicates exist', 'enhanced-wc-sync-pro'); ?></div>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Select Categories -->
        <div id="categories-section" class="ewcs-pro-card" style="display: none;">
            <h2><?php _e('Step 3: Select Categories', 'enhanced-wc-sync-pro'); ?></h2>
            <p><?php _e('Choose which categories to import products from. You can select multiple categories.', 'enhanced-wc-sync-pro'); ?></p>
            
            <div style="margin: 15px 0; display: flex; gap: 10px; align-items: center;">
                <button id="select-all-categories" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e('Select All', 'enhanced-wc-sync-pro'); ?>
                </button>
                <button id="deselect-all-categories" class="ewcs-pro-btn ewcs-pro-btn-secondary">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Deselect All', 'enhanced-wc-sync-pro'); ?>
                </button>
                
                <div style="margin-left: auto;">
                    <label for="category-filter" style="margin-right: 5px;"><?php _e('Filter:', 'enhanced-wc-sync-pro'); ?></label>
                    <input type="text" id="category-filter" placeholder="<?php _e('Search categories...', 'enhanced-wc-sync-pro'); ?>" 
                           style="padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div id="categories-grid" class="ewcs-pro-category-grid">
                <!-- Categories will be loaded here -->
            </div>
            
            <div class="ewcs-pro-actions" style="margin-top: 20px;">
                <button id="start-import" class="ewcs-pro-btn ewcs-pro-btn-success">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Start Import', 'enhanced-wc-sync-pro'); ?>
                </button>
                <button id="cancel-import" class="ewcs-pro-btn ewcs-pro-btn-danger" style="display: none;">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Cancel Import', 'enhanced-wc-sync-pro'); ?>
                </button>
            </div>
        </div>
        
        <!-- Import Progress -->
        <div id="import-progress" class="ewcs-pro-progress">
            <h3><?php _e('Import Progress', 'enhanced-wc-sync-pro'); ?></h3>
            <div class="ewcs-pro-progress-bar">
                <div class="ewcs-pro-progress-fill" style="width: 0%"></div>
            </div>
            <div class="ewcs-pro-progress-text">
                <span id="progress-status"><?php _e('Starting import...', 'enhanced-wc-sync-pro'); ?></span>
                <span id="progress-percentage">0%</span>
            </div>
            
            <div style="margin-top: 15px; font-size: 14px; color: #718096; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong><?php _e('Current batch:', 'enhanced-wc-sync-pro'); ?></strong>
                    <span id="current-batch">0</span> / <span id="total-batches">0</span>
                </div>
                <div>
                    <strong><?php _e('Products imported:', 'enhanced-wc-sync-pro'); ?></strong>
                    <span id="imported-count" style="color: #48bb78;">0</span>
                </div>
                <div>
                    <strong><?php _e('Products updated:', 'enhanced-wc-sync-pro'); ?></strong>
                    <span id="updated-count" style="color: #4299e1;">0</span>
                </div>
                <div>
                    <strong><?php _e('Products skipped:', 'enhanced-wc-sync-pro'); ?></strong>
                    <span id="skipped-count" style="color: #ed8936;">0</span>
                </div>
                <div>
                    <strong><?php _e('Total products:', 'enhanced-wc-sync-pro'); ?></strong>
                    <span id="total-products">0</span>
                </div>
                <div>
                    <strong><?php _e('Time remaining:', 'enhanced-wc-sync-pro'); ?></strong>
                    <span id="time-remaining">-</span>
                </div>
            </div>
            
            <div style="margin-top: 10px;">
                <div class="ewcs-pro-alert ewcs-pro-alert-info">
                    <strong><?php _e('Import Tips:', 'enhanced-wc-sync-pro'); ?></strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li><?php _e('Keep this page open during import', 'enhanced-wc-sync-pro'); ?></li>
                        <li><?php _e('Import will resume if interrupted', 'enhanced-wc-sync-pro'); ?></li>
                        <li><?php _e('Check logs for detailed information', 'enhanced-wc-sync-pro'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Import Log -->
        <div id="import-log" class="ewcs-pro-log" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin: 0;"><?php _e('Import Log', 'enhanced-wc-sync-pro'); ?></h3>
                <div>
                    <input type="text" id="log-search" placeholder="<?php _e('Search logs...', 'enhanced-wc-sync-pro'); ?>" 
                           style="padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px;">
                    <button id="clear-log-display" class="ewcs-pro-btn ewcs-pro-btn-secondary" style="padding: 5px 10px;">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Clear Display', 'enhanced-wc-sync-pro'); ?>
                    </button>
                </div>
            </div>
            <!-- Import log will be displayed here -->
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Show import mode section when categories are loaded
    $(document).on('categories-loaded', function() {
        $('#import-mode-section').show().addClass('ewcs-fade-in');
    });
    
    // Category filter functionality
    $('#category-filter').on('input', function() {
        const filter = $(this).val().toLowerCase();
        $('.ewcs-pro-category-item').each(function() {
            const categoryName = $(this).find('.ewcs-pro-category-name').text().toLowerCase();
            if (categoryName.includes(filter)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Clear log display
    $('#clear-log-display').on('click', function() {
        $('#import-log .ewcs-pro-log-item').remove();
        $('#import-log').hide();
    });
    
    // Auto-show log when import starts
    $(document).on('import-started', function() {
        $('#import-log').show().addClass('ewcs-fade-in');
    });
    
    // Enhanced category display function
    window.displayCategories = function(categories) {
        const grid = $('#categories-grid');
        grid.empty();
        
        if (!categories || categories.length === 0) {
            grid.append('<div class="ewcs-pro-alert ewcs-pro-alert-info">No categories found. Please sync categories first.</div>');
            return;
        }
        
        // Sort categories by name
        categories.sort((a, b) => a.name.localeCompare(b.name));
        
        categories.forEach(function(category, index) {
            const hasParent = category.parent && category.parent !== 0;
            const categoryItem = $(`
                <div class="ewcs-pro-category-item ewcs-fade-in" style="animation-delay: ${index * 0.05}s">
                    <input type="checkbox" id="cat-${category.id}" value="${category.id}" name="categories[]">
                    <div class="ewcs-pro-category-info">
                        <div class="ewcs-pro-category-name">
                            ${hasParent ? '— ' : ''}${category.name}
                        </div>
                        <div class="ewcs-pro-category-count">
                            ${category.count || 0} products
                            ${category.remote_id ? `• Remote ID: ${category.remote_id}` : ''}
                        </div>
                    </div>
                </div>
            `);
            grid.append(categoryItem);
        });
        
        // Trigger categories loaded event
        $(document).trigger('categories-loaded');
    };
});
</script>