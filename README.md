# Custom Cursor Plugin for WordPress

![Last Commit](https://img.shields.io/github/last-commit/Siphon880gh/wp-icon-plugin/main)
<a target="_blank" href="https://github.com/Siphon880gh" rel="nofollow"><img src="https://img.shields.io/badge/GitHub--blue?style=social&logo=GitHub" alt="Github" data-canonical-src="https://img.shields.io/badge/GitHub--blue?style=social&logo=GitHub" style="max-width:8.5ch;"></a>
<a target="_blank" href="https://www.linkedin.com/in/weng-fung/" rel="nofollow"><img src="https://img.shields.io/badge/LinkedIn-blue?style=flat&logo=linkedin&labelColor=blue" alt="Linked-In" data-canonical-src="https://img.shields.io/badge/LinkedIn-blue?style=flat&amp;logo=linkedin&amp;labelColor=blue" style="max-width:10ch;"></a>
<a target="_blank" href="https://www.youtube.com/@WayneTeachesCode/" rel="nofollow"><img src="https://img.shields.io/badge/Youtube-red?style=flat&logo=youtube&labelColor=red" alt="Youtube" data-canonical-src="https://img.shields.io/badge/Youtube-red?style=flat&amp;logo=youtube&amp;labelColor=red" style="max-width:10ch;"></a>

By Weng Fei Fung (Weng). A WordPress plugin that allows you to upload a custom cursor image and display it on any page using a shortcode.

![Settings Screenshot](docs/settings-page.png)

## Features

- Upload custom cursor images through WordPress admin
- Automatic image resizing to optimal cursor size (32x32 pixels)
- Enable/disable functionality with a simple toggle
- Easy-to-use shortcode for displaying custom cursor on any page
- One-click shortcode copying

## Installation

1. Upload the `custom-cursor-plugin.php` file to your WordPress plugins directory:
   - `/wp-content/plugins/custom-cursor/`

2. Activate the plugin through the 'Plugins' menu in WordPress

3. Navigate to 'Custom Cursor' in the WordPress admin menu

## Usage

### Step 1: Upload Your Cursor Image

1. Go to **Custom Cursor** in the WordPress admin menu
2. Upload an image file (any size - it will be automatically optimized)
3. Large images will be automatically resized to 32x32 pixels for optimal cursor performance

### Step 2: Enable the Plugin

1. Check the "Enable custom cursor functionality" checkbox
2. The settings will save automatically

### Step 3: Use the Shortcode

1. Once enabled and an image is uploaded, you'll see a shortcode displayed on the settings page:
   ```
   [custom_cursor]
   ```

2. Copy this shortcode and paste it into any WordPress page or post

3. When visitors view that page, their cursor will change to your custom image

## How It Works

- The shortcode applies the custom cursor to the entire page when activated
- The cursor image is applied using CSS `cursor` property
- All elements on the page will display the custom cursor
- Falls back to default cursor if the custom image fails to load

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- GD or ImageMagick library (usually included with WordPress)

## Browser Compatibility

The custom cursor feature is supported in all modern browsers:
- Chrome/Edge
- Firefox
- Safari
- Opera

## Troubleshooting

**Cursor not changing:**
- Make sure the plugin is enabled in settings
- Verify the shortcode `[custom_cursor]` is on the page
- Check that the image uploaded successfully
- Try a different image format (PNG works best for cursors)

**Image too large:**
- The plugin automatically resizes images, but smaller source images work better
- Recommended: Upload images already close to 32x32 pixels for best quality

## Technical Details

- Images are resized to 32x32 pixels (optimal cursor size for most browsers)
- Supports common image formats: JPG, PNG, GIF, WEBP
- Uses WordPress media library for image management
- Implements WordPress security best practices (nonces, capability checks)

## License

GPL v2 or later

