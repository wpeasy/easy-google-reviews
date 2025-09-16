<?php
defined('ABSPATH') || exit;

/**
 * Plugin Name: Easy Google Reviews
 * Description: Display Google Reviews and statistics using Google Business Profile API
 * Version: 1.0.0-beta
 * Author: Alan Blair <alan@wpeasy.au>
 * Text Domain: egr
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: true
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EGR_PLUGIN_FILE', __FILE__);
define('EGR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EGR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EGR_PLUGIN_VERSION', '1.0.0-beta');
define('EGR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if autoloader exists
if (file_exists(EGR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once EGR_PLUGIN_PATH . 'vendor/autoload.php';
} else {
    // Fallback autoloader
    spl_autoload_register(function ($class) {
        $prefix = 'WP_Easy\\EasyGoogleReviews\\';
        $base_dir = EGR_PLUGIN_PATH . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}

use WP_Easy\EasyGoogleReviews\Plugin;

// Initialize the plugin
add_action('plugins_loaded', function() {
    if (class_exists('WP_Easy\EasyGoogleReviews\Plugin')) {
        Plugin::init();
    }
});

// Activation hook
register_activation_hook(__FILE__, function() {
    if (class_exists('WP_Easy\EasyGoogleReviews\Plugin')) {
        Plugin::activate();
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    if (class_exists('WP_Easy\EasyGoogleReviews\Plugin')) {
        Plugin::deactivate();
    }
});

// Uninstall hook
register_uninstall_hook(__FILE__, ['WP_Easy\EasyGoogleReviews\Plugin', 'uninstall']);