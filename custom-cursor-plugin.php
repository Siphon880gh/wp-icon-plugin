<?php
/**
 * Plugin Name: Custom Cursor Plugin
 * Plugin URI: https://example.com/custom-cursor
 * Description: Upload a custom cursor image and display it using a shortcode
 * Version: 1.0.0
 * Author: Weng Fei Fung
 * Author URI: https://wengindustries.com
 * License: GPL v2 or later
 * Text Domain: custom-cursor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cursor_Plugin {
    
    private $option_name = 'custom_cursor_settings';
    private $gallery_option = 'custom_cursor_gallery';
    private $current_id_option = 'custom_cursor_current_id';
    
    public function __construct() {
        // Initialize gallery if needed
        $this->maybe_initialize_gallery();
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Handle image upload and cursor management
        add_action('admin_post_custom_cursor_upload', array($this, 'handle_image_upload'));
        add_action('admin_post_custom_cursor_navigate', array($this, 'handle_navigate'));
        add_action('admin_post_custom_cursor_new', array($this, 'handle_new_cursor'));
        add_action('admin_post_custom_cursor_delete', array($this, 'handle_delete_cursor'));
        
        // Register shortcode
        add_shortcode('custom_cursor', array($this, 'custom_cursor_shortcode'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Initialize gallery structure if it doesn't exist
     */
    private function maybe_initialize_gallery() {
        $gallery = get_option($this->gallery_option);
        
        if ($gallery === false) {
            // Migrate from old single-cursor system or create first cursor
            $cursors = array();
            
            // Check if old settings exist
            $old_enabled = get_option('custom_cursor_enabled');
            if ($old_enabled !== false) {
                // Migrate existing cursor
                $cursors[] = array(
                    'id' => 1,
                    'name' => 'Cursor 1',
                    'enabled' => get_option('custom_cursor_enabled', '0'),
                    'image_id' => get_option('custom_cursor_image_id', ''),
                    'image_url' => get_option('custom_cursor_image_url', ''),
                    'size' => get_option('custom_cursor_size', '32'),
                    'animation_type' => get_option('custom_cursor_animation_type', 'none'),
                    'animation_loop' => get_option('custom_cursor_animation_loop', '1'),
                    'animation_speed' => get_option('custom_cursor_animation_speed', '1'),
                    'click_animation' => get_option('custom_cursor_click_animation', '0'),
                    'blend_mode' => get_option('custom_cursor_blend_mode', 'normal'),
                    'shadow_enabled' => get_option('custom_cursor_shadow_enabled', '0'),
                    'shadow_color' => get_option('custom_cursor_shadow_color', '#000000')
                );
            } else {
                // Create first empty cursor
                $cursors[] = $this->get_default_cursor(1);
            }
            
            update_option($this->gallery_option, $cursors);
            update_option($this->current_id_option, 1);
        }
    }
    
    /**
     * Get default cursor structure
     */
    private function get_default_cursor($id) {
        return array(
            'id' => $id,
            'name' => 'Cursor ' . $id,
            'enabled' => '0',
            'image_id' => '',
            'image_url' => '',
            'size' => '32',
            'animation_type' => 'none',
            'animation_loop' => '1',
            'animation_speed' => '1',
            'click_animation' => '0',
            'blend_mode' => 'normal',
            'shadow_enabled' => '0',
            'shadow_color' => '#000000'
        );
    }
    
    /**
     * Get all cursors from gallery
     */
    private function get_cursors() {
        return get_option($this->gallery_option, array());
    }
    
    /**
     * Get current cursor ID
     */
    private function get_current_cursor_id() {
        return get_option($this->current_id_option, 1);
    }
    
    /**
     * Get cursor by ID
     */
    private function get_cursor_by_id($id) {
        $cursors = $this->get_cursors();
        foreach ($cursors as $cursor) {
            if ($cursor['id'] == $id) {
                return $cursor;
            }
        }
        return null;
    }
    
    /**
     * Save cursor data
     */
    private function save_cursor($cursor_data) {
        $cursors = $this->get_cursors();
        $found = false;
        
        foreach ($cursors as $index => $cursor) {
            if ($cursor['id'] == $cursor_data['id']) {
                $cursors[$index] = $cursor_data;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $cursors[] = $cursor_data;
        }
        
        update_option($this->gallery_option, $cursors);
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            'Custom Cursor Settings',
            'Custom Cursor',
            'manage_options',
            'custom-cursor',
            array($this, 'settings_page'),
            'dashicons-images-alt2',
            100
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting($this->option_name, 'custom_cursor_enabled');
        register_setting($this->option_name, 'custom_cursor_image_id');
        register_setting($this->option_name, 'custom_cursor_image_url');
        register_setting($this->option_name, 'custom_cursor_size');
        register_setting($this->option_name, 'custom_cursor_animation_type');
        register_setting($this->option_name, 'custom_cursor_animation_loop');
        register_setting($this->option_name, 'custom_cursor_animation_speed');
        register_setting($this->option_name, 'custom_cursor_click_animation');
        register_setting($this->option_name, 'custom_cursor_blend_mode');
        register_setting($this->option_name, 'custom_cursor_shadow_enabled');
        register_setting($this->option_name, 'custom_cursor_shadow_color');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_custom-cursor') {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script(
            'custom-cursor-admin',
            plugins_url('admin.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
    }
    
    /**
     * Handle navigation between cursors
     */
    public function handle_navigate() {
        if (!isset($_POST['custom_cursor_nonce']) || 
            !wp_verify_nonce($_POST['custom_cursor_nonce'], 'custom_cursor_navigate')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        
        $direction = isset($_POST['direction']) ? $_POST['direction'] : 'next';
        $cursors = $this->get_cursors();
        $current_id = $this->get_current_cursor_id();
        
        // Find current index
        $current_index = 0;
        foreach ($cursors as $index => $cursor) {
            if ($cursor['id'] == $current_id) {
                $current_index = $index;
                break;
            }
        }
        
        // Navigate
        if ($direction === 'prev') {
            $new_index = $current_index > 0 ? $current_index - 1 : count($cursors) - 1;
        } else {
            $new_index = $current_index < count($cursors) - 1 ? $current_index + 1 : 0;
        }
        
        update_option($this->current_id_option, $cursors[$new_index]['id']);
        
        wp_redirect(admin_url('admin.php?page=custom-cursor'));
        exit;
    }
    
    /**
     * Handle creating new cursor
     */
    public function handle_new_cursor() {
        if (!isset($_POST['custom_cursor_nonce']) || 
            !wp_verify_nonce($_POST['custom_cursor_nonce'], 'custom_cursor_new')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        
        $cursors = $this->get_cursors();
        
        // Find max ID
        $max_id = 0;
        foreach ($cursors as $cursor) {
            if ($cursor['id'] > $max_id) {
                $max_id = $cursor['id'];
            }
        }
        
        $new_id = $max_id + 1;
        $new_cursor = $this->get_default_cursor($new_id);
        $cursors[] = $new_cursor;
        
        update_option($this->gallery_option, $cursors);
        update_option($this->current_id_option, $new_id);
        
        wp_redirect(admin_url('admin.php?page=custom-cursor'));
        exit;
    }
    
    /**
     * Handle deleting cursor
     */
    public function handle_delete_cursor() {
        if (!isset($_POST['custom_cursor_nonce']) || 
            !wp_verify_nonce($_POST['custom_cursor_nonce'], 'custom_cursor_delete')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        
        $cursors = $this->get_cursors();
        
        // Don't allow deleting if only one cursor
        if (count($cursors) <= 1) {
            wp_redirect(add_query_arg('error', 'cannot_delete_last', admin_url('admin.php?page=custom-cursor')));
            exit;
        }
        
        $current_id = $this->get_current_cursor_id();
        
        // Remove current cursor
        $new_cursors = array();
        foreach ($cursors as $cursor) {
            if ($cursor['id'] != $current_id) {
                $new_cursors[] = $cursor;
            }
        }
        
        update_option($this->gallery_option, $new_cursors);
        
        // Set current to first cursor
        if (!empty($new_cursors)) {
            update_option($this->current_id_option, $new_cursors[0]['id']);
        }
        
        wp_redirect(admin_url('admin.php?page=custom-cursor'));
        exit;
    }
    
    /**
     * Handle image upload and save settings
     */
    public function handle_image_upload() {
        // Check nonce
        if (!isset($_POST['custom_cursor_nonce']) || 
            !wp_verify_nonce($_POST['custom_cursor_nonce'], 'custom_cursor_upload')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        
        $current_id = $this->get_current_cursor_id();
        $cursor = $this->get_cursor_by_id($current_id);
        
        if (!$cursor) {
            wp_die('Cursor not found');
        }
        
        // Handle file upload
        if (!empty($_FILES['cursor_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('cursor_image', 0);
            
            if (is_wp_error($attachment_id)) {
                wp_redirect(add_query_arg('upload', 'error', admin_url('admin.php?page=custom-cursor')));
                exit;
            }
            
            // Resize image based on selected size
            $cursor_size = isset($_POST['custom_cursor_size']) ? intval($_POST['custom_cursor_size']) : 32;
            $this->resize_cursor_image($attachment_id, $cursor_size);
            
            // Update cursor with new image
            $cursor['image_id'] = $attachment_id;
            $cursor['image_url'] = wp_get_attachment_url($attachment_id);
        }
        
        // Update all cursor settings from form
        $cursor['name'] = isset($_POST['cursor_name']) ? sanitize_text_field($_POST['cursor_name']) : $cursor['name'];
        $cursor['enabled'] = isset($_POST['custom_cursor_enabled']) ? '1' : '0';
        $cursor['size'] = isset($_POST['custom_cursor_size']) ? sanitize_text_field($_POST['custom_cursor_size']) : '32';
        $cursor['animation_type'] = isset($_POST['custom_cursor_animation_type']) ? sanitize_text_field($_POST['custom_cursor_animation_type']) : 'none';
        $cursor['animation_loop'] = isset($_POST['custom_cursor_animation_loop']) ? '1' : '0';
        $cursor['animation_speed'] = isset($_POST['custom_cursor_animation_speed']) ? sanitize_text_field($_POST['custom_cursor_animation_speed']) : '1';
        $cursor['click_animation'] = isset($_POST['custom_cursor_click_animation']) ? '1' : '0';
        $cursor['blend_mode'] = isset($_POST['custom_cursor_blend_mode']) ? sanitize_text_field($_POST['custom_cursor_blend_mode']) : 'normal';
        $cursor['shadow_enabled'] = isset($_POST['custom_cursor_shadow_enabled']) ? '1' : '0';
        $cursor['shadow_color'] = isset($_POST['custom_cursor_shadow_color']) ? sanitize_text_field($_POST['custom_cursor_shadow_color']) : '#000000';
        
        // Save cursor
        $this->save_cursor($cursor);
        
        wp_redirect(add_query_arg('upload', 'success', admin_url('admin.php?page=custom-cursor')));
        exit;
    }
    
    /**
     * Resize image to specified cursor size
     */
    private function resize_cursor_image($attachment_id, $target_size = 32) {
        $image_path = get_attached_file($attachment_id);
        
        // Load image editor
        $image_editor = wp_get_image_editor($image_path);
        
        if (is_wp_error($image_editor)) {
            return false;
        }
        
        // Get current size
        $size = $image_editor->get_size();
        
        // Only resize if image is larger than target size
        if ($size['width'] > $target_size || $size['height'] > $target_size) {
            // Resize to target size maintaining aspect ratio
            $image_editor->resize($target_size, $target_size, false);
            
            // Save the resized image
            $saved = $image_editor->save($image_path);
            
            if (!is_wp_error($saved)) {
                // Update metadata
                $metadata = wp_generate_attachment_metadata($attachment_id, $image_path);
                wp_update_attachment_metadata($attachment_id, $metadata);
            }
        }
        
        return true;
    }
    
    /**
     * Render settings page
     */
    public function settings_page() {
        // Get cursor gallery data
        $cursors = $this->get_cursors();
        $current_id = $this->get_current_cursor_id();
        $cursor = $this->get_cursor_by_id($current_id);
        
        if (!$cursor) {
            // Fallback if cursor not found
            $cursor = $this->get_default_cursor(1);
        }
        
        // Extract cursor data
        $cursor_name = $cursor['name'];
        $enabled = $cursor['enabled'];
        $image_url = $cursor['image_url'];
        $image_id = $cursor['image_id'];
        $cursor_size = $cursor['size'];
        $animation_type = $cursor['animation_type'];
        $animation_loop = $cursor['animation_loop'];
        $animation_speed = $cursor['animation_speed'];
        $click_animation = $cursor['click_animation'];
        $blend_mode = $cursor['blend_mode'];
        $shadow_enabled = $cursor['shadow_enabled'];
        $shadow_color = $cursor['shadow_color'];
        
        // Calculate cursor position
        $current_index = 0;
        foreach ($cursors as $index => $c) {
            if ($c['id'] == $current_id) {
                $current_index = $index + 1; // 1-based for display
                break;
            }
        }
        $total_cursors = count($cursors);
        
        ?>
        <div class="wrap">
            <h1>✨ Custom Cursor Gallery</h1>
            
            <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Success!</strong> Cursor saved successfully!</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['upload']) && $_GET['upload'] === 'error'): ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong>Error!</strong> There was a problem saving. Please try again.</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'cannot_delete_last'): ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong>Error!</strong> Cannot delete the last cursor. You must have at least one cursor.</p>
                </div>
            <?php endif; ?>
            
            <!-- Refined Carousel Navigation -->
            <style>
                .cursor-carousel-container {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 35px 30px 25px;
                    border-radius: 12px;
                    margin: 20px 0;
                    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
                    position: relative;
                    overflow: hidden;
                }
                .cursor-carousel-container::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 1px;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
                }
                .cursor-nav-wrapper {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    max-width: 800px;
                    margin: 0 auto;
                    gap: 20px;
                }
                .cursor-nav-btn {
                    font-size: 18px !important;
                    padding: 10px 20px !important;
                    background: rgba(255, 255, 255, 0.25) !important;
                    border: 1px solid rgba(255, 255, 255, 0.3) !important;
                    cursor: pointer !important;
                    border-radius: 8px !important;
                    transition: all 0.2s ease !important;
                    font-weight: 500 !important;
                    color: white !important;
                    min-width: 50px;
                    backdrop-filter: blur(10px);
                }
                .cursor-nav-btn:hover {
                    background: rgba(255, 255, 255, 0.35) !important;
                    border-color: rgba(255, 255, 255, 0.5) !important;
                    transform: translateY(-1px);
                }
                .cursor-nav-btn:active {
                    transform: translateY(0);
                }
                .cursor-info {
                    color: white;
                    text-align: center;
                    flex-grow: 1;
                    padding: 0 30px;
                }
                .cursor-name {
                    font-size: 28px;
                    font-weight: 600;
                    margin-bottom: 6px;
                    text-shadow: 0 2px 8px rgba(0,0,0,0.15);
                    letter-spacing: -0.3px;
                }
                .cursor-counter {
                    font-size: 13px;
                    opacity: 0.85;
                    font-weight: 400;
                    letter-spacing: 0.3px;
                }
                .cursor-actions {
                    display: flex;
                    gap: 8px;
                    justify-content: center;
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid rgba(255, 255, 255, 0.15);
                }
                .cursor-action-btn {
                    padding: 8px 18px !important;
                    font-size: 12px !important;
                    font-weight: 500 !important;
                    border: 1px solid rgba(255, 255, 255, 0.25) !important;
                    border-radius: 6px !important;
                    cursor: pointer !important;
                    transition: all 0.2s ease !important;
                    background: rgba(255, 255, 255, 0.1) !important;
                    color: white !important;
                    letter-spacing: 0.3px;
                }
                .cursor-action-btn:hover {
                    background: rgba(255, 255, 255, 0.2) !important;
                    border-color: rgba(255, 255, 255, 0.4) !important;
                    transform: translateY(-1px);
                }
                .cursor-action-btn:active {
                    transform: translateY(0);
                }
            </style>
            
            <div class="cursor-carousel-container">
                <div class="cursor-nav-wrapper">
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin: 0;">
                        <input type="hidden" name="action" value="custom_cursor_navigate">
                        <input type="hidden" name="direction" value="prev">
                        <?php wp_nonce_field('custom_cursor_navigate', 'custom_cursor_nonce'); ?>
                        <button type="submit" class="cursor-nav-btn" title="Previous Cursor">
                            ◀
                        </button>
                    </form>
                    
                    <div class="cursor-info">
                        <div class="cursor-name">
                            <?php echo esc_html($cursor_name); ?>
                        </div>
                        <div class="cursor-counter">
                            Cursor <?php echo $current_index; ?> of <?php echo $total_cursors; ?>
                        </div>
                    </div>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin: 0;">
                        <input type="hidden" name="action" value="custom_cursor_navigate">
                        <input type="hidden" name="direction" value="next">
                        <?php wp_nonce_field('custom_cursor_navigate', 'custom_cursor_nonce'); ?>
                        <button type="submit" class="cursor-nav-btn" title="Next Cursor">
                            ▶
                        </button>
                    </form>
                </div>
                
                <div class="cursor-actions">
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin: 0;">
                        <input type="hidden" name="action" value="custom_cursor_new">
                        <?php wp_nonce_field('custom_cursor_new', 'custom_cursor_nonce'); ?>
                        <button type="submit" class="cursor-action-btn" title="Create a new cursor">
                            + New Cursor
                        </button>
                    </form>
                    
                    <?php if ($total_cursors > 1): ?>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete &quot;<?php echo esc_js($cursor_name); ?>&quot;?\n\nThis action cannot be undone.');">
                        <input type="hidden" name="action" value="custom_cursor_delete">
                        <?php wp_nonce_field('custom_cursor_delete', 'custom_cursor_nonce'); ?>
                        <button type="submit" class="cursor-action-btn" title="Delete this cursor">
                            Delete
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <style>
                .custom-cursor-card {
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                    margin: 20px 0;
                    padding: 0;
                }
                .custom-cursor-card-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: #fff;
                    padding: 15px 20px;
                    font-size: 16px;
                    font-weight: 600;
                    border-bottom: 1px solid #c3c4c7;
                }
                .custom-cursor-card-body {
                    padding: 20px;
                }
                .custom-cursor-section {
                    margin-bottom: 30px;
                }
                .custom-cursor-section-title {
                    font-size: 15px;
                    font-weight: 600;
                    margin-bottom: 15px;
                    color: #1d2327;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .custom-cursor-toggle {
                    cursor: pointer;
                    user-select: none;
                    padding: 12px 15px;
                    background: #f6f7f7;
                    border: 1px solid #dcdcde;
                    border-radius: 4px;
                    margin-bottom: 10px;
                    transition: all 0.2s;
                }
                .custom-cursor-toggle:hover {
                    background: #fff;
                    border-color: #667eea;
                }
                .custom-cursor-toggle.active {
                    background: #fff;
                    border-color: #667eea;
                }
                .custom-cursor-toggle-arrow {
                    display: inline-block;
                    transition: transform 0.2s;
                    margin-right: 8px;
                }
                .custom-cursor-toggle.active .custom-cursor-toggle-arrow {
                    transform: rotate(90deg);
                }
                .custom-cursor-collapsible {
                    display: none;
                    padding: 15px;
                    border: 1px solid #dcdcde;
                    border-top: none;
                    background: #fff;
                }
                .custom-cursor-collapsible.active {
                    display: block;
                }
                .image-preview {
                    display: inline-block;
                    margin-top: 10px;
                    padding: 10px;
                    background: #f6f7f7;
                    border: 2px dashed #c3c4c7;
                    border-radius: 4px;
                }
                .image-preview img {
                    max-width: 64px;
                    display: block;
                }
                .color-picker-wrapper {
                    display: inline-block;
                }
                .save-button-wrapper {
                    background: #f6f7f7;
                    padding: 20px;
                    border-top: 1px solid #c3c4c7;
                    margin-top: 20px;
                }
            </style>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" id="custom-cursor-form">
                <input type="hidden" name="action" value="custom_cursor_upload">
                <?php wp_nonce_field('custom_cursor_upload', 'custom_cursor_nonce'); ?>
                
                <div class="custom-cursor-card">
                    <div class="custom-cursor-card-header">
                        🎯 Basic Settings
                    </div>
                    <div class="custom-cursor-card-body">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="cursor_name">Cursor Name</label>
                                </th>
                                <td>
                                    <input type="text" name="cursor_name" id="cursor_name" value="<?php echo esc_attr($cursor_name); ?>" class="regular-text" placeholder="My Custom Cursor">
                                    <p class="description">Give this cursor a memorable name (shown in carousel navigation).</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label>Enable Custom Cursor</label>
                                </th>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="custom_cursor_enabled" value="1" <?php checked($enabled, '1'); ?>>
                                        <span style="font-weight: 600; color: #667eea;">Enable custom cursor functionality</span>
                                    </label>
                                    <p class="description">Turn this on to activate custom cursor on pages with the shortcode.</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="cursor_image">Cursor Image</label>
                                </th>
                                <td>
                                    <input type="file" name="cursor_image" id="cursor_image" accept="image/*" style="margin-bottom: 10px;">
                                    <button type="submit" name="upload_image" id="upload_image_btn" class="button button-primary" style="vertical-align: top;">
                                        📤 Upload Image
                                    </button>
                                    <span id="upload-status" style="margin-left: 10px; display: none;">
                                        <span class="spinner" style="float: none; visibility: visible;"></span>
                                        <span class="status-text">Uploading...</span>
                                    </span>
                                    <p class="description">Upload an image for your custom cursor. Will be automatically resized based on size setting.</p>
                                    
                                    <?php if ($image_url): ?>
                                        <div class="image-preview">
                                            <strong>Current cursor:</strong><br>
                                            <img src="<?php echo esc_url($image_url); ?>" alt="Current cursor">
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="custom_cursor_size">Cursor Size</label>
                                </th>
                                <td>
                                    <select name="custom_cursor_size" id="custom_cursor_size" class="regular-text">
                                        <option value="16" <?php selected($cursor_size, '16'); ?>>16×16 pixels (Small)</option>
                                        <option value="24" <?php selected($cursor_size, '24'); ?>>24×24 pixels (Medium-Small)</option>
                                        <option value="32" <?php selected($cursor_size, '32'); ?>>32×32 pixels (Standard) ⭐</option>
                                        <option value="48" <?php selected($cursor_size, '48'); ?>>48×48 pixels (Large)</option>
                                        <option value="64" <?php selected($cursor_size, '64'); ?>>64×64 pixels (Extra Large)</option>
                                    </select>
                                    <p class="description">Select cursor size. Uploaded images will be resized automatically.</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="custom-cursor-card">
                    <div class="custom-cursor-card-header">
                        🎬 Animation Settings
                    </div>
                    <div class="custom-cursor-card-body">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="custom_cursor_animation_type">Animation Type</label>
                                </th>
                                <td>
                                    <select name="custom_cursor_animation_type" id="custom_cursor_animation_type" class="regular-text">
                                        <option value="none" <?php selected($animation_type, 'none'); ?>>⛔ None (Static)</option>
                                        <option value="pulse" <?php selected($animation_type, 'pulse'); ?>>💓 Pulse (Scale up/down)</option>
                                        <option value="spin" <?php selected($animation_type, 'spin'); ?>>🔄 Spin (Rotate)</option>
                                        <option value="bounce" <?php selected($animation_type, 'bounce'); ?>>⬆️ Bounce</option>
                                        <option value="shake" <?php selected($animation_type, 'shake'); ?>>↔️ Shake</option>
                                        <option value="glow" <?php selected($animation_type, 'glow'); ?>>✨ Glow (Opacity pulse)</option>
                                    </select>
                                    <p class="description">Choose an animation effect for your cursor.</p>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="custom-cursor-toggle" id="advanced-animation-toggle">
                            <span class="custom-cursor-toggle-arrow">▶</span>
                            <strong>Advanced Animation Options</strong>
                            <span style="color: #50575e; font-size: 13px; margin-left: 10px;">(Click to expand)</span>
                        </div>
                        <div class="custom-cursor-collapsible" id="advanced-animation-options">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="custom_cursor_animation_speed">Animation Speed</label>
                                    </th>
                                    <td>
                                        <select name="custom_cursor_animation_speed" id="custom_cursor_animation_speed" class="regular-text">
                                            <option value="0.5" <?php selected($animation_speed, '0.5'); ?>>⚡ Fast (0.5s)</option>
                                            <option value="1" <?php selected($animation_speed, '1'); ?>>➡️ Normal (1s)</option>
                                            <option value="1.5" <?php selected($animation_speed, '1.5'); ?>>🐢 Medium (1.5s)</option>
                                            <option value="2" <?php selected($animation_speed, '2'); ?>>🐌 Slow (2s)</option>
                                            <option value="3" <?php selected($animation_speed, '3'); ?>>🦥 Very Slow (3s)</option>
                                        </select>
                                        <p class="description">Duration of one animation cycle.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="custom_cursor_animation_loop">Animation Loop</label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="custom_cursor_animation_loop" id="custom_cursor_animation_loop" value="1" <?php checked($animation_loop, '1'); ?>>
                                            <strong>Loop animation continuously</strong>
                                        </label>
                                        <p class="description">When unchecked, animation plays once on page load.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="custom_cursor_click_animation">Click Animation</label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="custom_cursor_click_animation" id="custom_cursor_click_animation" value="1" <?php checked($click_animation, '1'); ?>>
                                            <strong>Pulse on click</strong>
                                        </label>
                                        <p class="description">Cursor will pulse when clicking anywhere on the page.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="custom-cursor-card">
                    <div class="custom-cursor-card-header">
                        🎨 Visual Effects
                    </div>
                    <div class="custom-cursor-card-body">
                        <div class="custom-cursor-toggle" id="visual-effects-toggle">
                            <span class="custom-cursor-toggle-arrow">▶</span>
                            <strong>Advanced Visual Effects</strong>
                            <span style="color: #50575e; font-size: 13px; margin-left: 10px;">(Click to expand)</span>
                        </div>
                        <div class="custom-cursor-collapsible" id="visual-effects-options">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="custom_cursor_blend_mode">Blend Mode</label>
                                    </th>
                                    <td>
                                        <select name="custom_cursor_blend_mode" id="custom_cursor_blend_mode" class="regular-text">
                                            <option value="normal" <?php selected($blend_mode, 'normal'); ?>>Normal</option>
                                            <option value="multiply" <?php selected($blend_mode, 'multiply'); ?>>Multiply</option>
                                            <option value="screen" <?php selected($blend_mode, 'screen'); ?>>Screen</option>
                                            <option value="overlay" <?php selected($blend_mode, 'overlay'); ?>>Overlay</option>
                                            <option value="difference" <?php selected($blend_mode, 'difference'); ?>>Difference</option>
                                            <option value="exclusion" <?php selected($blend_mode, 'exclusion'); ?>>Exclusion</option>
                                        </select>
                                        <p class="description">How the cursor blends with the page content beneath it.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="custom_cursor_shadow_enabled">Drop Shadow</label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="custom_cursor_shadow_enabled" id="custom_cursor_shadow_enabled" value="1" <?php checked($shadow_enabled, '1'); ?>>
                                            <strong>Enable drop shadow</strong>
                                        </label>
                                        <p class="description">Adds a shadow effect to make cursor stand out.</p>
                                    </td>
                                </tr>
                                
                                <tr id="shadow-color-row" style="display: <?php echo $shadow_enabled ? 'table-row' : 'none'; ?>;">
                                    <th scope="row">
                                        <label for="custom_cursor_shadow_color">Shadow Color</label>
                                    </th>
                                    <td>
                                        <div class="color-picker-wrapper">
                                            <input type="color" name="custom_cursor_shadow_color" id="custom_cursor_shadow_color" value="<?php echo esc_attr($shadow_color); ?>">
                                            <code style="margin-left: 10px;"><?php echo esc_html($shadow_color); ?></code>
                                        </div>
                                        <p class="description">Choose shadow color.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            
            
            <?php if ($enabled && $image_url): ?>
                <div class="custom-cursor-card" style="margin-top: 20px;">
                    <div class="custom-cursor-card-header">
                        📋 Shortcode
                    </div>
                    <div class="custom-cursor-card-body">
                        <p style="margin-top: 0;">Copy and paste this shortcode into any page or post to enable this custom cursor:</p>
                        <div style="background: #f6f7f7; padding: 15px; border-radius: 4px; border: 1px solid #c3c4c7; margin: 15px 0;">
                            <code style="display: block; background: white; padding: 12px; font-size: 16px; border: 2px dashed #667eea; border-radius: 4px; font-family: monospace;">[custom_cursor id="<?php echo $current_id; ?>"]</code>
                        </div>
                        <button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText('[custom_cursor id=&quot;<?php echo $current_id; ?>&quot;]'); this.innerHTML = '✓ Copied!'; setTimeout(() => this.innerHTML = '📋 Copy Shortcode', 2000);">
                            📋 Copy Shortcode
                        </button>
                        <p class="description" style="margin-top: 10px;">
                            💡 <strong>Tip:</strong> Each cursor has a unique ID. This shortcode will always display "<?php echo esc_html($cursor_name); ?>".
                        </p>
                    </div>
                </div>
            <?php elseif ($enabled && !$image_url): ?>
                <div class="notice notice-warning" style="margin-top: 20px;">
                    <p><strong>⚠️ Almost there!</strong> Upload a cursor image above to get your shortcode.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-info" style="margin-top: 20px;">
                    <p><strong>ℹ️ Getting started:</strong> Enable the custom cursor and upload an image to generate your shortcode.</p>
                </div>
            <?php endif; ?>
            
            <div class="save-button-wrapper">
                <button type="submit" name="save_settings" class="button button-primary button-hero" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; text-shadow: none; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.4);">
                    💾 Save All Settings
                </button>
                <span id="save-status" style="margin-left: 15px; display: none;">
                    <span class="spinner" style="float: none; visibility: visible;"></span>
                    <span class="status-text">Saving...</span>
                </span>
                <p class="description" style="margin-top: 10px;">Click to save all settings and upload any new image.</p>
            </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Collapsible sections
            $('.custom-cursor-toggle').on('click', function() {
                var toggle = $(this);
                var targetId = toggle.attr('id').replace('-toggle', '-options');
                var target = $('#' + targetId);
                
                toggle.toggleClass('active');
                target.toggleClass('active');
            });
            
            // Shadow color row toggle
            $('#custom_cursor_shadow_enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#shadow-color-row').show();
                } else {
                    $('#shadow-color-row').hide();
                }
            });
            
            // Update color code display
            $('#custom_cursor_shadow_color').on('input', function() {
                $(this).next('code').text($(this).val());
            });
            
            // Show feedback when form is submitted
            $('form#custom-cursor-form').on('submit', function(e) {
                var fileInput = $('#cursor_image');
                var submitBtn = $(this).find('button[type="submit"]');
                
                // Show appropriate feedback
                if (fileInput.val()) {
                    $('#upload-status').show();
                    submitBtn.prop('disabled', true);
                } else {
                    $('#save-status').show();
                    submitBtn.prop('disabled', true);
                }
            });
            
            // Upload Image button specifically
            $('#upload_image_btn').on('click', function(e) {
                var fileInput = $('#cursor_image');
                if (!fileInput.val()) {
                    e.preventDefault();
                    alert('Please select an image file first.');
                    return false;
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Shortcode handler
     */
    public function custom_cursor_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'id' => '1', // Default to first cursor
        ), $atts);
        
        $cursor_id = intval($atts['id']);
        $cursor = $this->get_cursor_by_id($cursor_id);
        
        // If cursor not found or not enabled, return empty
        if (!$cursor || $cursor['enabled'] !== '1' || empty($cursor['image_url'])) {
            return '';
        }
        
        // Extract cursor settings
        $image_url = $cursor['image_url'];
        $animation_type = $cursor['animation_type'];
        $animation_loop = $cursor['animation_loop'];
        $animation_speed = $cursor['animation_speed'];
        $cursor_size = $cursor['size'];
        $click_animation = $cursor['click_animation'];
        $blend_mode = $cursor['blend_mode'];
        $shadow_enabled = $cursor['shadow_enabled'];
        $shadow_color = $cursor['shadow_color'];
        
        // Start output
        $output = '<style>';
        
        // Add animation keyframes based on type
        if ($animation_type !== 'none') {
            $animation_name = 'custom-cursor-' . $animation_type;
            
            switch ($animation_type) {
                case 'pulse':
                    $output .= '@keyframes ' . $animation_name . ' {';
                    $output .= '  0%, 100% { transform: scale(1); }';
                    $output .= '  50% { transform: scale(1.2); }';
                    $output .= '}';
                    break;
                    
                case 'spin':
                    $output .= '@keyframes ' . $animation_name . ' {';
                    $output .= '  from { transform: rotate(0deg); }';
                    $output .= '  to { transform: rotate(360deg); }';
                    $output .= '}';
                    break;
                    
                case 'bounce':
                    $output .= '@keyframes ' . $animation_name . ' {';
                    $output .= '  0%, 100% { transform: translateY(0); }';
                    $output .= '  50% { transform: translateY(-5px); }';
                    $output .= '}';
                    break;
                    
                case 'shake':
                    $output .= '@keyframes ' . $animation_name . ' {';
                    $output .= '  0%, 100% { transform: translateX(0); }';
                    $output .= '  25% { transform: translateX(-3px); }';
                    $output .= '  75% { transform: translateX(3px); }';
                    $output .= '}';
                    break;
                    
                case 'glow':
                    $output .= '@keyframes ' . $animation_name . ' {';
                    $output .= '  0%, 100% { opacity: 1; filter: drop-shadow(0 0 0px rgba(255,255,255,0)); }';
                    $output .= '  50% { opacity: 0.7; filter: drop-shadow(0 0 8px rgba(255,255,255,0.8)); }';
                    $output .= '}';
                    break;
            }
        }
        
        // Click animation keyframes
        if ($click_animation === '1') {
            $output .= '@keyframes custom-cursor-click {';
            $output .= '  0% { transform: translate(-50%, -50%) scale(1); }';
            $output .= '  50% { transform: translate(-50%, -50%) scale(0.8); }';
            $output .= '  100% { transform: translate(-50%, -50%) scale(1); }';
            $output .= '}';
        }
        
        // CSS for cursor area
        $output .= '.custom-cursor-area { cursor: url(' . esc_url($image_url) . '), auto !important; }';
        $output .= '.custom-cursor-area * { cursor: url(' . esc_url($image_url) . '), auto !important; }';
        
        // CSS for animated cursor element
        if ($animation_type !== 'none') {
            $iteration_count = $animation_loop === '1' ? 'infinite' : '1';
            $output .= '.custom-cursor-animated {';
            $output .= '  position: fixed;';
            $output .= '  pointer-events: none;';
            $output .= '  z-index: 9999;';
            $output .= '  width: ' . intval($cursor_size) . 'px;';
            $output .= '  height: ' . intval($cursor_size) . 'px;';
            $output .= '  background-image: url(' . esc_url($image_url) . ');';
            $output .= '  background-size: contain;';
            $output .= '  background-repeat: no-repeat;';
            $output .= '  animation: custom-cursor-' . $animation_type . ' ' . floatval($animation_speed) . 's ease-in-out ' . $iteration_count . ';';
            $output .= '  transform: translate(-50%, -50%);';
            
            // Add blend mode
            if ($blend_mode !== 'normal') {
                $output .= '  mix-blend-mode: ' . esc_attr($blend_mode) . ';';
            }
            
            // Add shadow
            if ($shadow_enabled === '1') {
                // Convert hex to RGB for shadow
                $hex = ltrim($shadow_color, '#');
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $output .= '  filter: drop-shadow(0 2px 4px rgba(' . $r . ',' . $g . ',' . $b . ', 0.5));';
            }
            
            $output .= '}';
            
            // Click animation class
            if ($click_animation === '1') {
                $output .= '.custom-cursor-animated.clicking {';
                $output .= '  animation: custom-cursor-click 0.3s ease-in-out !important;';
                $output .= '}';
            }
        }
        
        $output .= '</style>';
        
        // Output JavaScript
        $output .= '<script>';
        $output .= 'document.addEventListener("DOMContentLoaded", function() {';
        $output .= '  document.body.classList.add("custom-cursor-area");';
        
        // Add animated cursor element if animation is enabled
        if ($animation_type !== 'none') {
            $output .= '  var cursorDiv = document.createElement("div");';
            $output .= '  cursorDiv.className = "custom-cursor-animated";';
            $output .= '  document.body.appendChild(cursorDiv);';
            $output .= '  document.addEventListener("mousemove", function(e) {';
            $output .= '    cursorDiv.style.left = e.clientX + "px";';
            $output .= '    cursorDiv.style.top = e.clientY + "px";';
            $output .= '  });';
            
            // Add click animation if enabled
            if ($click_animation === '1') {
                $output .= '  document.addEventListener("mousedown", function() {';
                $output .= '    cursorDiv.classList.add("clicking");';
                $output .= '  });';
                $output .= '  document.addEventListener("mouseup", function() {';
                $output .= '    setTimeout(function() {';
                $output .= '      cursorDiv.classList.remove("clicking");';
                $output .= '    }, 300);';
                $output .= '  });';
            }
        }
        
        $output .= '});';
        $output .= '</script>';
        
        return $output;
    }
}

// Initialize plugin
new Custom_Cursor_Plugin();

