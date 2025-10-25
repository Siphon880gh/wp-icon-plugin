# Custom Cursor Plugin - Context Documentation

> **Note:** Code references in this file use approximate location cues (e.g., "near the top," "middle of file") rather than exact line numbers to reduce maintenance overhead as the codebase evolves.

## Project Overview

**What it does:**  
A WordPress plugin that enables site administrators to upload custom cursor images and display them on any page using a shortcode. The plugin automatically optimizes uploaded images to the standard cursor size (32x32 pixels) and provides a user-friendly admin interface for managing the custom cursor.

**Tech Stack:**
- **Backend:** PHP 7.0+ (WordPress plugin architecture)
- **Frontend:** JavaScript (jQuery), CSS
- **Image Processing:** WordPress Image Editor (GD or ImageMagick)
- **Storage:** WordPress Options API and Media Library

**Architecture:**
- **Single-class architecture:** `Custom_Cursor_Plugin` class handles all functionality
- **WordPress Hooks:** Uses actions and filters for integration
- **Settings Storage:** WordPress Options API for persistence
- **Media Management:** WordPress Media Library for image uploads

## Project Structure

```
/wp-icon-plugin/
├── custom-cursor-plugin.php  (318 lines) - Main plugin file
├── admin.js                   (10 lines)  - Admin scripts placeholder
├── README.md                  (97 lines)  - User documentation
├── INSTALLATION.txt           (31 lines)  - Quick setup guide
└── docs/
    └── settings-page.png                  - Screenshot of admin interface
```

## Core Components

### 1. Main Plugin Class (`custom-cursor-plugin.php`)

**File:** `custom-cursor-plugin.php` (318 lines total)

The plugin is implemented as a single PHP class with the following key components:

#### Class Properties (near top of class)
```php
private $option_name = 'custom_cursor_settings';
```
Stores the option group name for WordPress settings API.

#### Constructor Method (near top of class)
```php
public function __construct() {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_post_custom_cursor_upload', array($this, 'handle_image_upload'));
    add_shortcode('custom_cursor', array($this, 'custom_cursor_shortcode'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
}
```
Hooks into WordPress actions to register menu, settings, upload handler, shortcode, and scripts.

#### Settings Registration (early in class)
```php
public function register_settings() {
    register_setting($this->option_name, 'custom_cursor_enabled');
    register_setting($this->option_name, 'custom_cursor_image_id');
    register_setting($this->option_name, 'custom_cursor_image_url');
}
```
Registers three options: enabled state, image ID, and image URL.

#### Image Upload Handler (middle of class, ~84-122)
```php
public function handle_image_upload() {
    // Verify nonce and permissions
    // Handle file upload using WordPress media functions
    // Resize image to optimal cursor size
    // Save attachment ID and URL
    // Redirect with success/error message
}
```
Processes image uploads with security checks, automatic resizing, and status feedback.

#### Image Resizing Logic (middle of class, ~127-156)
```php
private function resize_cursor_image($attachment_id) {
    // Load WordPress image editor
    // Get current image dimensions
    // Resize to 32x32 if larger
    // Save resized image
    // Update attachment metadata
}
```
Uses WordPress Image Editor to resize uploaded images to optimal cursor size (32x32 pixels).

#### Admin Settings Page (large method, ~161-270)
```php
public function settings_page() {
    // Render HTML form with:
    // - Enable/disable checkbox
    // - Image upload field
    // - Current image preview
    // - Shortcode display (when enabled)
    // - Inline AJAX for toggle
}
```
Generates the admin interface with form, preview, and dynamic shortcode display.

#### Shortcode Handler (near end of class, ~275-297)
```php
public function custom_cursor_shortcode($atts) {
    // Check if enabled and image exists
    // Output CSS to apply cursor
    // Output JavaScript to add CSS class to body
    // Returns empty string if disabled
}
```
Generates inline CSS and JavaScript to apply custom cursor to the page.

#### AJAX Settings Handler (bottom of file, ~304-316)
```php
function custom_cursor_save_settings() {
    // Verify AJAX nonce
    // Check user permissions
    // Update enabled option
    // Return JSON success
}
```
Handles AJAX requests from the enable/disable checkbox for seamless updates.

### 2. Admin JavaScript (`admin.js`)

**File:** `admin.js` (10 lines total)

Currently minimal - serves as a placeholder for future admin functionality. The main JavaScript is inline in the settings page for the enable/disable toggle functionality.

## Code Flow

### Installation Flow
1. User uploads plugin files to `/wp-content/plugins/custom-cursor/`
2. User activates plugin in WordPress admin
3. WordPress executes plugin initialization (bottom of `custom-cursor-plugin.php`)
4. Plugin registers menu, settings, and hooks

### Admin Configuration Flow
1. User navigates to "Custom Cursor" menu in WordPress admin
2. `add_admin_menu()` creates menu item linking to `settings_page()`
3. User enables plugin via checkbox → AJAX call to `custom_cursor_save_settings()`
4. User uploads image → `handle_image_upload()` processes file
5. Image automatically resized via `resize_cursor_image()`
6. Settings saved to WordPress options
7. Shortcode displayed on settings page for copying

### Frontend Display Flow
1. User adds `[custom_cursor]` shortcode to a page/post
2. WordPress processes shortcode via `custom_cursor_shortcode()`
3. Method checks if plugin is enabled and image exists
4. Returns inline CSS and JavaScript to apply cursor
5. JavaScript adds `.custom-cursor-area` class to `<body>` on page load
6. CSS applies custom cursor URL to body and all child elements

## Key Features Implementation

### Auto-Resize Images
Located in `resize_cursor_image()` method:
- Uses WordPress Image Editor API (`wp_get_image_editor()`)
- Checks if image dimensions exceed 32x32
- Resizes maintaining aspect ratio
- Updates attachment metadata

### Security Measures
- **Nonce verification:** All forms use `wp_nonce_field()` and `wp_verify_nonce()`
- **Capability checks:** Requires `manage_options` permission
- **Direct access prevention:** File starts with `if (!defined('ABSPATH')) exit;`
- **AJAX security:** Uses `check_ajax_referer()`

### WordPress Integration
- **Settings API:** Uses `register_setting()` and `get_option()`/`update_option()`
- **Media Library:** Uses `media_handle_upload()` for file handling
- **Admin Menu:** Uses `add_menu_page()` with Dashicons icon
- **Shortcode API:** Uses `add_shortcode()` for `[custom_cursor]`

## Database Schema

The plugin uses WordPress Options table with three keys:

| Option Key | Type | Description |
|------------|------|-------------|
| `custom_cursor_enabled` | string | '1' or '0' - feature toggle |
| `custom_cursor_image_id` | int | WordPress attachment ID |
| `custom_cursor_image_url` | string | Full URL to cursor image |

## Browser Compatibility

The cursor CSS approach works in all modern browsers:
- Chrome/Edge, Firefox, Safari, Opera
- Uses CSS `cursor: url(...)` property
- Fallback to `auto` cursor if image fails

## Development Notes

### Extending the Plugin

**Adding new cursor options:**
- Register new settings in `register_settings()`
- Add form fields in `settings_page()`
- Update shortcode logic in `custom_cursor_shortcode()`

**Adding cursor size options:**
- Modify `resize_cursor_image()` to accept size parameter
- Add size selector in settings page
- Update resize logic accordingly

**Adding per-element cursors:**
- Modify shortcode to accept element selector parameter
- Update CSS output to target specific elements
- Add UI for selector configuration

### Known Limitations
- Cursor size fixed at 32x32 pixels (browser optimization)
- Single global cursor image (not per-page customization)
- Requires shortcode on each page (not site-wide option)
- Admin JavaScript mostly inline (could be extracted to `admin.js`)

## Testing Checklist

- [ ] Upload image smaller than 32x32 (should not resize)
- [ ] Upload image larger than 32x32 (should auto-resize)
- [ ] Test enable/disable toggle (should work without page reload)
- [ ] Verify shortcode appears when enabled + image uploaded
- [ ] Test shortcode on page (cursor should change)
- [ ] Test without shortcode (default cursor)
- [ ] Verify security: non-admin cannot access settings
- [ ] Test various image formats (PNG, JPG, GIF, WEBP)

## Common Customization Requests

**Q: How to apply cursor site-wide?**  
A: Modify `custom_cursor_shortcode()` to hook into `wp_head` action instead of using shortcode, or add option to enable site-wide mode.

**Q: How to allow different cursors on different pages?**  
A: Add page-specific meta boxes for cursor selection, store per-post cursor data, modify shortcode to check post meta first.

**Q: How to support animated cursors?**  
A: Add support for `.ani` files or animated GIFs, update file upload restrictions, test browser compatibility.

