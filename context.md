# Custom Cursor Plugin - Context Documentation

> **Note:** Code references in this file use approximate location cues (e.g., "near the top," "middle of file") rather than exact line numbers to reduce maintenance overhead as the codebase evolves.

## Project Overview

**What it does:**  
A WordPress plugin that enables site administrators to upload custom cursor images with animations and visual effects through a beautiful modern admin interface. The plugin features a card-based UI with progressive disclosure, automatically optimizes uploaded images to a user-selected size (16px to 64px), supports six animation types (pulse, spin, bounce, shake, glow, click), visual effects (blend modes, drop shadows), and provides an intuitive workflow with organized settings sections and a unified save button.

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
├── custom-cursor-plugin.php  (769 lines) - Main plugin file with full feature set
├── admin.js                   (10 lines)  - Admin scripts placeholder
├── README.md                  (166 lines) - User documentation
├── INSTALLATION.txt           (31 lines)  - Quick setup guide
├── context.md                 (358 lines) - AI/developer technical docs
└── docs/
    └── settings-page.png                  - Screenshot of admin interface (outdated)
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
    register_setting($this->option_name, 'custom_cursor_size');
    register_setting($this->option_name, 'custom_cursor_animation_type');
    register_setting($this->option_name, 'custom_cursor_animation_loop');
    register_setting($this->option_name, 'custom_cursor_animation_speed');
    register_setting($this->option_name, 'custom_cursor_click_animation');
    register_setting($this->option_name, 'custom_cursor_blend_mode');
    register_setting($this->option_name, 'custom_cursor_shadow_enabled');
    register_setting($this->option_name, 'custom_cursor_shadow_color');
}
```
Registers eleven options covering all cursor settings: enabled state, image ID/URL, cursor size (16-64px), animation type (none/pulse/spin/bounce/shake/glow), animation controls (loop, speed, click), and visual effects (blend mode, shadow, shadow color).

#### Image Upload Handler (middle of class, ~84-140)
```php
public function handle_image_upload() {
    // Verify nonce and permissions
    // Handle file upload using WordPress media functions
    // Resize image to user-selected cursor size
    // Save attachment ID and URL
    // Save all settings (size, animation type, loop, speed)
    // Redirect with success/error message
}
```
Processes image uploads with security checks, automatic resizing based on selected size, saves all cursor settings including animation options, and provides status feedback.

#### Image Resizing Logic (middle of class, ~143-161)
```php
private function resize_cursor_image($attachment_id, $target_size = 32) {
    // Load WordPress image editor
    // Get current image dimensions
    // Resize to target_size if larger
    // Save resized image
    // Update attachment metadata
}
```
Uses WordPress Image Editor to resize uploaded images to user-selected cursor size (16px, 24px, 32px, 48px, or 64px).

#### Admin Settings Page (large method, ~179-606)
```php
public function settings_page() {
    // Render modern card-based UI with:
    // - Card 1: Basic Settings
    //   - Enable/disable checkbox
    //   - Image upload field with inline Upload Image button
    //   - Current image preview in styled box
    //   - Cursor size selector (16-64px with emoji indicators)
    // - Card 2: Animation Settings
    //   - Animation type selector (emoji prefixed)
    //   - Collapsible "Advanced Animation Options" section:
    //     - Animation speed selector
    //     - Animation loop checkbox
    //     - Click animation checkbox
    // - Card 3: Visual Effects
    //   - Collapsible "Advanced Visual Effects" section:
    //     - Blend mode selector
    //     - Drop shadow checkbox
    //     - Shadow color picker (conditionally shown)
    // - Shortcode card (when ready)
    // - Unified "Save All Settings" button at bottom
    // - Inline CSS for card styling, gradients, collapsibles
    // - JavaScript for collapsible toggling and color picker updates
}
```
Generates a beautiful modern admin interface with card-based layout, gradient headers, progressive disclosure (collapsible sections), real-time color picker, and organized workflow with single save button.

#### Shortcode Handler (near end of class, ~613-764)
```php
public function custom_cursor_shortcode($atts) {
    // Check if enabled and image exists
    // Get all settings (animation, visual effects)
    // Generate CSS keyframes for animation type
    // Generate click animation keyframes if enabled
    // Output CSS for cursor with:
    //   - Animation (if selected)
    //   - Blend mode (if not normal)
    //   - Drop shadow (if enabled, with custom color)
    // Output JavaScript to:
    //   - Add CSS class to body
    //   - Create animated cursor element (if animation enabled)
    //   - Track mouse movement
    //   - Handle click animation (mousedown/mouseup)
    // Returns empty string if disabled
}
```
Generates inline CSS (including animation keyframes, blend modes, shadows) and JavaScript to apply custom animated cursor with full visual effects. Supports six animation types, click interaction, blend modes, and custom drop shadows.

**Note:** The AJAX settings handler has been removed. All settings now save together via the unified "Save All Settings" button using standard form submission.

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
- Checks if image dimensions exceed selected target size
- Resizes maintaining aspect ratio to one of: 16px, 24px, 32px, 48px, or 64px
- Updates attachment metadata

### Animation System
Located in `custom_cursor_shortcode()` method:
- **Six animation types:**
  - **Pulse:** Scales cursor from 1x to 1.2x smoothly
  - **Spin:** 360-degree continuous rotation
  - **Bounce:** Vertical translation (-5px)
  - **Shake:** Horizontal translation (±3px)
  - **Glow:** Opacity pulse with drop-shadow effect
  - **None:** Static cursor (default)
- **CSS Keyframes:** Dynamically generated based on animation type
- **Customizable speed:** 0.5s (fast) to 3s (very slow)
- **Loop control:** Infinite or single-play options
- **Implementation:** Creates animated div element that follows mouse movement
- **Non-intrusive:** Uses `pointer-events: none` to avoid interfering with page interactions

### Modern UI/UX Features
Located in the settings page with inline CSS and JavaScript:
- **Card-based layout:** Settings organized into themed cards with gradient purple headers
- **Progressive disclosure:** Advanced options hidden in collapsible sections that expand/collapse
- **Collapsible toggling:** Smooth transitions with rotating arrow indicators
- **Visual feedback:** Spinners and success messages for uploads and saves
- **Color picker:** Real-time hex code display that updates as you pick colors
- **Conditional visibility:** Shadow color picker only shows when shadow is enabled
- **Emoji indicators:** Size options and animations labeled with relevant emojis for quick scanning
- **Unified save button:** Large gradient button at bottom saves all settings at once
- **Inline upload:** Upload Image button positioned right next to file input for intuitive flow

### Visual Effects Implementation
New visual effects applied via CSS in `custom_cursor_shortcode()`:
- **Blend modes:** Uses CSS `mix-blend-mode` property for creative color mixing
- **Drop shadow:** Converts hex color to RGBA and applies via `drop-shadow` filter
- **Click animation:** Separate CSS keyframe animation triggered on mousedown/mouseup events
- **Effect stacking:** Multiple effects (animation + blend + shadow) can be combined

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

The plugin uses WordPress Options table with eleven keys:

| Option Key | Type | Description |
|------------|------|-------------|
| `custom_cursor_enabled` | string | '1' or '0' - feature toggle |
| `custom_cursor_image_id` | int | WordPress attachment ID |
| `custom_cursor_image_url` | string | Full URL to cursor image |
| `custom_cursor_size` | string | '16', '24', '32', '48', or '64' - cursor size in pixels |
| `custom_cursor_animation_type` | string | 'none', 'pulse', 'spin', 'bounce', 'shake', or 'glow' |
| `custom_cursor_animation_loop` | string | '1' or '0' - whether animation loops |
| `custom_cursor_animation_speed` | string | '0.5', '1', '1.5', '2', or '3' - animation duration in seconds |
| `custom_cursor_click_animation` | string | '1' or '0' - whether cursor pulses on click |
| `custom_cursor_blend_mode` | string | 'normal', 'multiply', 'screen', 'overlay', 'difference', or 'exclusion' |
| `custom_cursor_shadow_enabled` | string | '1' or '0' - whether drop shadow is enabled |
| `custom_cursor_shadow_color` | string | Hex color code (e.g., '#000000') for drop shadow |

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
- Cursor size limited to 5 preset options (16px, 24px, 32px, 48px, 64px)
- Single global cursor image (not per-page customization)
- Requires shortcode on each page (not site-wide option)
- All JavaScript inline in settings page (not extracted to `admin.js` for easier maintenance)
- Animated cursors create an overlay div (may affect z-index stacking in rare cases)
- Animation applies to entire cursor, not individual parts (for multi-part cursor animations, would need custom implementation)
- Settings page screenshot in docs folder is now outdated (shows old UI)

## Testing Checklist

### Basic Functionality
- [ ] Upload image smaller than selected size (should not resize)
- [ ] Upload image larger than selected size (should auto-resize)
- [ ] Test enable/disable toggle (should work without page reload)
- [ ] Verify shortcode appears when enabled + image uploaded
- [ ] Test shortcode on page (cursor should change)
- [ ] Test without shortcode (default cursor)
- [ ] Verify security: non-admin cannot access settings
- [ ] Test various image formats (PNG, JPG, GIF, WEBP)

### Size Options
- [ ] Test all size options (16px, 24px, 32px, 48px, 64px)
- [ ] Verify cursor displays at correct size on frontend
- [ ] Test changing size after image already uploaded

### Animation Features
- [ ] Test each animation type (pulse, spin, bounce, shake, glow)
- [ ] Verify animation loop on/off functionality
- [ ] Test all speed settings (0.5s, 1s, 1.5s, 2s, 3s)
- [ ] Verify "none" animation shows static cursor
- [ ] Test animation with different cursor sizes
- [ ] Verify animated cursor follows mouse accurately
- [ ] Confirm animated cursor doesn't interfere with clicking/interactions
- [ ] Test click animation (cursor should pulse on mousedown)

### Visual Effects
- [ ] Test each blend mode (multiply, screen, overlay, difference, exclusion)
- [ ] Verify blend modes work with different page backgrounds
- [ ] Test drop shadow enable/disable
- [ ] Test shadow color picker functionality
- [ ] Verify hex code updates in real-time as color is picked
- [ ] Test combining multiple effects (animation + blend + shadow)

### UI/UX Features
- [ ] Verify collapsible sections expand/collapse correctly
- [ ] Test arrow rotation animation on toggle
- [ ] Confirm shadow color row shows/hides based on checkbox
- [ ] Test Upload Image button (should alert if no file selected)
- [ ] Verify Save Settings button saves all options
- [ ] Test shortcode copy button (should show "✓ Copied!" feedback)
- [ ] Confirm card layout displays correctly
- [ ] Verify gradient headers render properly

## Common Customization Requests

**Q: How to apply cursor site-wide?**  
A: Modify `custom_cursor_shortcode()` to hook into `wp_head` action instead of using shortcode, or add option to enable site-wide mode.

**Q: How to allow different cursors on different pages?**  
A: Add page-specific meta boxes for cursor selection, store per-post cursor data, modify shortcode to check post meta first.

**Q: How to add custom animation types?**  
A: Add new cases to the switch statement in `custom_cursor_shortcode()`, define CSS keyframes for the new animation, add option to animation type dropdown in settings page.

**Q: How to add more cursor sizes?**  
A: Add options to the size dropdown in `settings_page()`, ensure validation accepts new values in `handle_image_upload()`.

**Q: How to make animations trigger on hover instead of continuous?**  
A: Modify JavaScript in shortcode to add/remove animation class on mouseover/mouseout events instead of applying animation on load.

**Q: How to support animated GIF cursors?**  
A: Animated GIFs are already supported - just upload a GIF file. The animation type setting adds CSS animations on top of the cursor image (including animated GIFs).

