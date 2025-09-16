<?php
namespace WP_Easy\EasyGoogleReviews\Admin;

defined('ABSPATH') || exit;

use WP_Easy\EasyGoogleReviews\API\GoogleAPI;
use WP_Easy\EasyGoogleReviews\Core\Cron;

final class Settings
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('admin_init', [self::class, 'init_settings']);
        add_action('admin_notices', [self::class, 'admin_notices']);

        // Handle auth callback
        add_action('admin_init', [self::class, 'handle_auth_callback']);

        // AJAX handlers
        add_action('wp_ajax_egr_clear_cache', [self::class, 'handle_clear_cache']);
        add_action('wp_ajax_egr_disconnect_google', [self::class, 'handle_disconnect_google']);
    }

    public static function add_admin_menu(): void
    {
        add_menu_page(
            __('Easy Google Reviews', 'egr'),
            __('Google Reviews', 'egr'),
            'manage_options',
            'easy-google-reviews',
            [self::class, 'render_settings_page'],
            'dashicons-star-filled',
            30
        );

        add_submenu_page(
            'easy-google-reviews',
            __('Settings', 'egr'),
            __('Settings', 'egr'),
            'manage_options',
            'easy-google-reviews',
            [self::class, 'render_settings_page']
        );

        add_submenu_page(
            'easy-google-reviews',
            __('Sync Status', 'egr'),
            __('Sync Status', 'egr'),
            'manage_options',
            'easy-google-reviews-sync',
            [self::class, 'render_sync_page']
        );
    }

    public static function init_settings(): void
    {
        // Register settings
        register_setting('egr_settings', 'egr_google_client_id', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);

        register_setting('egr_settings', 'egr_google_client_secret', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);

        register_setting('egr_settings', 'egr_business_id', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);

        register_setting('egr_settings', 'egr_default_rows_per_page', [
            'sanitize_callback' => 'absint',
            'default' => 10
        ]);

        register_setting('egr_settings', 'egr_default_fields', [
            'sanitize_callback' => [self::class, 'sanitize_fields_array']
        ]);

        register_setting('egr_settings', 'egr_cache_timeout', [
            'sanitize_callback' => 'absint',
            'default' => 6 * HOUR_IN_SECONDS
        ]);

        // Add settings sections
        add_settings_section(
            'egr_google_section',
            __('Google API Configuration', 'egr'),
            [self::class, 'render_google_section'],
            'egr_settings'
        );

        add_settings_section(
            'egr_display_section',
            __('Display Settings', 'egr'),
            [self::class, 'render_display_section'],
            'egr_settings'
        );

        // Add settings fields
        add_settings_field(
            'egr_google_client_id',
            __('Google Client ID', 'egr'),
            [self::class, 'render_client_id_field'],
            'egr_settings',
            'egr_google_section'
        );

        add_settings_field(
            'egr_google_client_secret',
            __('Google Client Secret', 'egr'),
            [self::class, 'render_client_secret_field'],
            'egr_settings',
            'egr_google_section'
        );

        add_settings_field(
            'egr_google_auth',
            __('Google Authentication', 'egr'),
            [self::class, 'render_auth_field'],
            'egr_settings',
            'egr_google_section'
        );

        add_settings_field(
            'egr_business_id',
            __('Business Location ID', 'egr'),
            [self::class, 'render_business_id_field'],
            'egr_settings',
            'egr_google_section'
        );

        add_settings_field(
            'egr_default_rows_per_page',
            __('Default Rows Per Page', 'egr'),
            [self::class, 'render_rows_per_page_field'],
            'egr_settings',
            'egr_display_section'
        );

        add_settings_field(
            'egr_default_fields',
            __('Default Fields to Display', 'egr'),
            [self::class, 'render_default_fields_field'],
            'egr_settings',
            'egr_display_section'
        );

        add_settings_field(
            'egr_cache_timeout',
            __('Cache Timeout (hours)', 'egr'),
            [self::class, 'render_cache_timeout_field'],
            'egr_settings',
            'egr_display_section'
        );
    }

    public static function render_settings_page(): void
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('egr_settings');
                do_settings_sections('egr_settings');
                submit_button();
                ?>
            </form>

            <div class="egr-admin-actions">
                <h3><?php _e('Actions', 'egr'); ?></h3>
                <p>
                    <button type="button" class="button" id="egr-clear-cache">
                        <?php _e('Clear Cache', 'egr'); ?>
                    </button>
                    <button type="button" class="button" id="egr-test-connection">
                        <?php _e('Test Connection', 'egr'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="egr-manual-sync">
                        <?php _e('Sync Reviews Now', 'egr'); ?>
                    </button>
                </p>
                <p>
                    <button type="button" class="button button-secondary" id="egr-disconnect-google">
                        <?php _e('Disconnect Google Account', 'egr'); ?>
                    </button>
                </p>
            </div>
        </div>
        <?php
    }

    public static function render_sync_page(): void
    {
        $sync_status = Cron::get_sync_status();
        ?>
        <div class="wrap">
            <h1><?php _e('Sync Status', 'egr'); ?></h1>

            <div class="egr-sync-status">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Last Sync', 'egr'); ?></th>
                        <td><?php echo esc_html($sync_status['last_sync']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Next Scheduled Sync', 'egr'); ?></th>
                        <td><?php echo esc_html($sync_status['next_sync']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Total Reviews', 'egr'); ?></th>
                        <td><?php echo esc_html($sync_status['total_reviews']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Five Star Reviews', 'egr'); ?></th>
                        <td><?php echo esc_html($sync_status['five_star_count']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Sync Status', 'egr'); ?></th>
                        <td>
                            <?php if ($sync_status['in_progress']): ?>
                                <span class="egr-status egr-status-progress"><?php _e('In Progress', 'egr'); ?></span>
                            <?php else: ?>
                                <span class="egr-status egr-status-idle"><?php _e('Idle', 'egr'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="button" class="button button-primary" id="egr-manual-sync" <?php disabled($sync_status['in_progress']); ?>>
                        <?php _e('Sync Reviews Now', 'egr'); ?>
                    </button>
                </p>
            </div>
        </div>
        <?php
    }

    public static function render_google_section(): void
    {
        ?>
        <p><?php _e('Configure your Google API credentials to connect to Google Business Profile.', 'egr'); ?></p>
        <?php
    }

    public static function render_display_section(): void
    {
        ?>
        <p><?php _e('Configure default display settings for your reviews.', 'egr'); ?></p>
        <?php
    }

    public static function render_client_id_field(): void
    {
        $value = get_option('egr_google_client_id');
        ?>
        <input type="text" name="egr_google_client_id" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('Your Google OAuth 2.0 Client ID', 'egr'); ?></p>
        <?php
    }

    public static function render_client_secret_field(): void
    {
        $value = get_option('egr_google_client_secret');
        ?>
        <input type="password" name="egr_google_client_secret" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('Your Google OAuth 2.0 Client Secret', 'egr'); ?></p>
        <?php
    }

    public static function render_auth_field(): void
    {
        $access_token = get_option('egr_google_access_token');
        $client_id = get_option('egr_google_client_id');
        $client_secret = get_option('egr_google_client_secret');

        if (empty($client_id) || empty($client_secret)) {
            ?>
            <p class="description"><?php _e('Please enter your Client ID and Secret first.', 'egr'); ?></p>
            <?php
            return;
        }

        if (!empty($access_token) && GoogleAPI::is_token_valid()) {
            $expires = get_option('egr_google_token_expires', 0);
            ?>
            <p class="egr-auth-status egr-auth-connected">
                <?php _e('Connected to Google', 'egr'); ?>
                <br>
                <small><?php printf(__('Token expires: %s', 'egr'), date('Y-m-d H:i:s', $expires)); ?></small>
            </p>
            <?php
        } else {
            $auth_url = GoogleAPI::get_auth_url();
            ?>
            <a href="<?php echo esc_url($auth_url); ?>" class="button button-primary">
                <?php _e('Connect to Google', 'egr'); ?>
            </a>
            <p class="description"><?php _e('Click to authorize this plugin to access your Google Business Profile.', 'egr'); ?></p>
            <?php
        }
    }

    public static function render_business_id_field(): void
    {
        $value = get_option('egr_business_id');
        ?>
        <input type="text" name="egr_business_id" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('Your Google Business Location ID', 'egr'); ?></p>
        <?php
    }

    public static function render_rows_per_page_field(): void
    {
        $value = get_option('egr_default_rows_per_page', 10);
        ?>
        <input type="number" name="egr_default_rows_per_page" value="<?php echo esc_attr($value); ?>" min="1" max="100" />
        <p class="description"><?php _e('Default number of reviews to show per page', 'egr'); ?></p>
        <?php
    }

    public static function render_default_fields_field(): void
    {
        $available_fields = [
            'author_name' => __('Author Name', 'egr'),
            'text' => __('Review Text', 'egr'),
            'rating' => __('Star Rating', 'egr'),
            'time' => __('Review Date', 'egr'),
            'reply' => __('Business Reply', 'egr')
        ];

        $selected_fields = get_option('egr_default_fields', ['author_name', 'text', 'rating', 'time']);

        foreach ($available_fields as $field => $label) {
            $checked = in_array($field, $selected_fields);
            ?>
            <label>
                <input type="checkbox" name="egr_default_fields[]" value="<?php echo esc_attr($field); ?>" <?php checked($checked); ?> />
                <?php echo esc_html($label); ?>
            </label><br>
            <?php
        }
        ?>
        <p class="description"><?php _e('Select which fields to display by default in shortcodes', 'egr'); ?></p>
        <?php
    }

    public static function render_cache_timeout_field(): void
    {
        $value = get_option('egr_cache_timeout', 6 * HOUR_IN_SECONDS);
        $hours = $value / HOUR_IN_SECONDS;
        ?>
        <input type="number" name="egr_cache_timeout" value="<?php echo esc_attr($hours); ?>" min="1" max="72" />
        <p class="description"><?php _e('How long to cache review data (in hours)', 'egr'); ?></p>
        <?php
    }

    public static function sanitize_fields_array($input): array
    {
        if (!is_array($input)) {
            return ['author_name', 'text', 'rating', 'time'];
        }

        $allowed_fields = ['author_name', 'text', 'rating', 'time', 'reply'];
        return array_intersect($input, $allowed_fields);
    }

    public static function handle_auth_callback(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $code = sanitize_text_field($_GET['code'] ?? '');
        $state = sanitize_text_field($_GET['state'] ?? '');

        if (empty($code) || !wp_verify_nonce($state, 'egr_google_auth')) {
            return;
        }

        $result = GoogleAPI::exchange_code_for_token($code);

        if (isset($result['error'])) {
            add_settings_error('egr_settings', 'auth_error', $result['error'], 'error');
        } else {
            add_settings_error('egr_settings', 'auth_success', __('Successfully connected to Google!', 'egr'), 'success');
        }
    }

    public static function admin_notices(): void
    {
        $screen = get_current_screen();

        if ($screen && strpos($screen->id, 'easy-google-reviews') === false) {
            return;
        }

        // Check if authentication is configured
        $client_id = get_option('egr_google_client_id');
        $client_secret = get_option('egr_google_client_secret');
        $access_token = get_option('egr_google_access_token');

        if (empty($client_id) || empty($client_secret)) {
            ?>
            <div class="notice notice-warning">
                <p><?php _e('Please configure your Google API credentials to start syncing reviews.', 'egr'); ?></p>
            </div>
            <?php
        } elseif (empty($access_token) || !GoogleAPI::is_token_valid()) {
            ?>
            <div class="notice notice-warning">
                <p><?php _e('Please connect to your Google account to start syncing reviews.', 'egr'); ?></p>
            </div>
            <?php
        }
    }

    public static function handle_clear_cache(): void
    {
        check_ajax_referer('egr_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'egr'));
        }

        // Clear review cache
        delete_transient('egr_reviews_data');

        wp_send_json_success(__('Cache cleared successfully', 'egr'));
    }

    public static function handle_disconnect_google(): void
    {
        check_ajax_referer('egr_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'egr'));
        }

        // Clear all Google-related options
        delete_option('egr_google_access_token');
        delete_option('egr_google_refresh_token');
        delete_option('egr_google_token_expires');

        // Clear cached data
        delete_transient('egr_reviews_data');

        wp_send_json_success(__('Google account disconnected successfully', 'egr'));
    }
}