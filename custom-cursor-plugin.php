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
    
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Handle image upload
        add_action('admin_post_custom_cursor_upload', array($this, 'handle_image_upload'));
        
        // Register shortcode
        add_shortcode('custom_cursor', array($this, 'custom_cursor_shortcode'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
     * Handle image upload and resize
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
            
            // Resize image to icon size (32x32 is optimal for cursors)
            $this->resize_cursor_image($attachment_id);
            
            // Save attachment ID
            update_option('custom_cursor_image_id', $attachment_id);
            update_option('custom_cursor_image_url', wp_get_attachment_url($attachment_id));
            
            wp_redirect(add_query_arg('upload', 'success', admin_url('admin.php?page=custom-cursor')));
            exit;
        }
        
        wp_redirect(admin_url('admin.php?page=custom-cursor'));
        exit;
    }
    
    /**
     * Resize image to optimal cursor size
     */
    private function resize_cursor_image($attachment_id) {
        $image_path = get_attached_file($attachment_id);
        
        // Load image editor
        $image_editor = wp_get_image_editor($image_path);
        
        if (is_wp_error($image_editor)) {
            return false;
        }
        
        // Get current size
        $size = $image_editor->get_size();
        
        // Only resize if image is larger than 32x32
        if ($size['width'] > 32 || $size['height'] > 32) {
            // Resize to 32x32 maintaining aspect ratio
            $image_editor->resize(32, 32, false);
            
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
        $enabled = get_option('custom_cursor_enabled', false);
        $image_url = get_option('custom_cursor_image_url', '');
        $image_id = get_option('custom_cursor_image_id', '');
        
        ?>
        <div class="wrap">
            <h1>Custom Cursor Settings</h1>
            
            <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Image uploaded successfully!</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['upload']) && $_GET['upload'] === 'error'): ?>
                <div class="notice notice-error is-dismissible">
                    <p>Error uploading image. Please try again.</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="custom_cursor_upload">
                <?php wp_nonce_field('custom_cursor_upload', 'custom_cursor_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label>Enable Custom Cursor</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="custom_cursor_enabled" value="1" 
                                    <?php checked($enabled, '1'); ?>>
                                Enable custom cursor functionality
                            </label>
                            <span id="enable-status" style="margin-left: 10px; display: none;">
                                <span class="spinner" style="float: none; visibility: visible;"></span>
                                <span class="status-text">Saving...</span>
                            </span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cursor_image">Cursor Image</label>
                        </th>
                        <td>
                            <input type="file" name="cursor_image" id="cursor_image" accept="image/*">
                            <p class="description">Upload an image for your custom cursor. Large images will be automatically resized to 32x32 pixels.</p>
                            
                            <?php if ($image_url): ?>
                                <div style="margin-top: 15px;">
                                    <strong>Current cursor image:</strong><br>
                                    <img src="<?php echo esc_url($image_url); ?>" style="max-width: 64px; border: 1px solid #ddd; padding: 5px; margin-top: 10px;">
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Upload Image">
                    <span id="upload-status" style="margin-left: 10px; display: none;">
                        <span class="spinner" style="float: none; visibility: visible;"></span>
                        <span class="status-text">Uploading...</span>
                    </span>
                </p>
            </form>
            
            <hr>
            
            <?php if ($enabled && $image_url): ?>
                <div class="custom-cursor-shortcode" style="background: #f0f0f1; padding: 20px; border-left: 4px solid #2271b1;">
                    <h2>Shortcode</h2>
                    <p>Copy and paste this shortcode into any page or post to enable the custom cursor:</p>
                    <code style="display: block; background: white; padding: 10px; font-size: 14px; margin: 10px 0;">[custom_cursor]</code>
                    <button type="button" class="button" onclick="navigator.clipboard.writeText('[custom_cursor]')">Copy Shortcode</button>
                </div>
            <?php elseif ($enabled && !$image_url): ?>
                <div class="notice notice-warning">
                    <p>Please upload a cursor image to use the shortcode.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p>Enable the custom cursor feature and upload an image to get the shortcode.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Auto-submit form when enable checkbox is changed
            $('input[name="custom_cursor_enabled"]').on('change', function() {
                var checkbox = $(this);
                var statusDiv = $('#enable-status');
                
                // Disable checkbox and show saving status
                checkbox.prop('disabled', true);
                statusDiv.show();
                statusDiv.find('.status-text').text('Saving...');
                statusDiv.removeClass('saved');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'custom_cursor_save_settings',
                        custom_cursor_enabled: checkbox.is(':checked') ? '1' : '0',
                        _wpnonce: '<?php echo wp_create_nonce('custom_cursor_settings'); ?>'
                    },
                    success: function() {
                        // Show success message
                        statusDiv.find('.spinner').hide();
                        statusDiv.find('.status-text').html('<span style="color: #46b450;">✓ Saved!</span>');
                        statusDiv.addClass('saved');
                        
                        // Reload after brief delay
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    },
                    error: function() {
                        // Show error and re-enable
                        statusDiv.find('.spinner').hide();
                        statusDiv.find('.status-text').html('<span style="color: #dc3232;">✗ Error saving</span>');
                        checkbox.prop('disabled', false);
                        
                        // Hide error after 3 seconds
                        setTimeout(function() {
                            statusDiv.fadeOut();
                        }, 3000);
                    }
                });
            });
            
            // Show upload feedback when form is submitted
            $('form[action*="admin-post.php"]').on('submit', function() {
                var fileInput = $('#cursor_image');
                
                // Only show uploading if a file was selected
                if (fileInput.val()) {
                    $('#submit').prop('disabled', true);
                    $('#upload-status').show();
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
        $enabled = get_option('custom_cursor_enabled', false);
        $image_url = get_option('custom_cursor_image_url', '');
        
        if (!$enabled || !$image_url) {
            return '';
        }
        
        // Output inline CSS to change cursor
        $output = '<style>';
        $output .= '.custom-cursor-area { cursor: url(' . esc_url($image_url) . '), auto !important; }';
        $output .= '.custom-cursor-area * { cursor: url(' . esc_url($image_url) . '), auto !important; }';
        $output .= '</style>';
        
        // Output JavaScript to apply cursor to body
        $output .= '<script>';
        $output .= 'document.addEventListener("DOMContentLoaded", function() {';
        $output .= '  document.body.classList.add("custom-cursor-area");';
        $output .= '});';
        $output .= '</script>';
        
        return $output;
    }
}

// Initialize plugin
new Custom_Cursor_Plugin();

// Handle AJAX save settings
add_action('wp_ajax_custom_cursor_save_settings', 'custom_cursor_save_settings');
function custom_cursor_save_settings() {
    check_ajax_referer('custom_cursor_settings');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $enabled = isset($_POST['custom_cursor_enabled']) ? '1' : '0';
    update_option('custom_cursor_enabled', $enabled);
    
    wp_send_json_success();
}

