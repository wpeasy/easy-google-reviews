<?php
namespace WP_Easy\EasyGoogleReviews;

defined('ABSPATH') || exit;

use WP_Easy\EasyGoogleReviews\Admin\Settings;
use WP_Easy\EasyGoogleReviews\Admin\Instructions;
use WP_Easy\EasyGoogleReviews\API\GoogleAPI;
use WP_Easy\EasyGoogleReviews\API\RestEndpoints;
use WP_Easy\EasyGoogleReviews\Core\Cron;
use WP_Easy\EasyGoogleReviews\Shortcodes\ReviewsShortcode;
use WP_Easy\EasyGoogleReviews\Shortcodes\FiveStarCountShortcode;

final class Plugin
{
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        // Load text domain for translations
        add_action('init', [self::class, 'load_textdomain']);

        // Initialize components
        add_action('init', [self::class, 'init_components']);

        // Enqueue assets
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    public static function activate(): void
    {
        // Create necessary database tables or options
        self::create_default_options();

        // Schedule cron events
        Cron::schedule_events();

        // Flush rewrite rules if needed
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        // Clear scheduled cron events
        Cron::clear_events();

        // Clear any cached data
        self::clear_transients();
    }

    public static function uninstall(): void
    {
        // Remove all plugin data
        self::remove_plugin_data();
    }

    public static function load_textdomain(): void
    {
        load_plugin_textdomain(
            'egr',
            false,
            dirname(EGR_PLUGIN_BASENAME) . '/languages'
        );
    }

    public static function init_components(): void
    {
        // Initialize admin components
        if (is_admin()) {
            Settings::init();
            Instructions::init();
        }

        // Initialize API handler
        GoogleAPI::init();

        // Initialize REST endpoints
        RestEndpoints::init();

        // Initialize cron handler
        Cron::init();

        // Initialize shortcodes
        ReviewsShortcode::init();
        FiveStarCountShortcode::init();
    }

    public static function enqueue_frontend_assets(): void
    {
        // Only enqueue on pages that have our shortcodes
        global $post;

        if (!$post || (!has_shortcode($post->post_content, 'egr_reviews') && !has_shortcode($post->post_content, 'egr_5star_count'))) {
            return;
        }

        // Load framework CSS first
        wp_enqueue_style(
            'wpe-framework-frontend',
            EGR_PLUGIN_URL . 'assets/css/framework-frontend.css',
            [],
            EGR_PLUGIN_VERSION
        );

        wp_enqueue_style(
            'egr-frontend',
            EGR_PLUGIN_URL . 'assets/css/frontend.css',
            ['wpe-framework-frontend'],
            EGR_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'egr-frontend',
            EGR_PLUGIN_URL . 'assets/js/frontend.js',
            [],
            EGR_PLUGIN_VERSION,
            true
        );

        // Enqueue Alpine.js if available
        if (file_exists(EGR_PLUGIN_PATH . 'assets/vendor/alpine.min.js')) {
            wp_enqueue_script(
                'alpine-js',
                EGR_PLUGIN_URL . 'assets/vendor/alpine.min.js',
                [],
                EGR_PLUGIN_VERSION,
                true
            );
        }

        // Localize script with AJAX data
        wp_localize_script('egr-frontend', 'egrAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('egr_nonce'),
            'loading_text' => __('Loading...', 'egr'),
            'error_text' => __('Error loading reviews', 'egr')
        ]);
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        // Only enqueue on our admin pages
        if (strpos($hook, 'easy-google-reviews') === false) {
            return;
        }

        // Load framework CSS first
        wp_enqueue_style(
            'wpe-framework-admin',
            EGR_PLUGIN_URL . 'assets/css/framework-admin.css',
            [],
            EGR_PLUGIN_VERSION
        );

        wp_enqueue_style(
            'egr-admin',
            EGR_PLUGIN_URL . 'assets/css/admin.css',
            ['wpe-framework-admin'],
            EGR_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'egr-admin',
            EGR_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            EGR_PLUGIN_VERSION,
            true
        );

        // Localize admin script
        wp_localize_script('egr-admin', 'egrAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('egr_admin_nonce'),
            'strings' => [
                'confirm_reset' => __('Are you sure you want to reset all settings?', 'egr'),
                'confirm_clear_cache' => __('Are you sure you want to clear the cache?', 'egr'),
            ]
        ]);
    }

    private static function create_default_options(): void
    {
        $default_options = [
            'egr_google_client_id' => '',
            'egr_google_client_secret' => '',
            'egr_google_access_token' => '',
            'egr_google_refresh_token' => '',
            'egr_google_token_expires' => 0,
            'egr_business_id' => '',
            'egr_default_rows_per_page' => 10,
            'egr_default_fields' => ['author_name', 'text', 'rating', 'time'],
            'egr_cache_timeout' => 6 * HOUR_IN_SECONDS, // 6 hours
            'egr_last_sync' => 0,
            'egr_total_reviews' => 0,
            'egr_five_star_count' => 0,
        ];

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }

    private static function clear_transients(): void
    {
        global $wpdb;

        // Clear all transients starting with 'egr_'
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_egr_%',
                '_transient_timeout_egr_%'
            )
        );
    }

    private static function remove_plugin_data(): void
    {
        global $wpdb;

        // Remove all plugin options
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                'egr_%'
            )
        );

        // Clear transients
        self::clear_transients();

        // Clear any scheduled events
        wp_clear_scheduled_hook('egr_sync_reviews');
        wp_clear_scheduled_hook('egr_refresh_token');
    }
}