<?php
namespace WP_Easy\EasyGoogleReviews\Core;

defined('ABSPATH') || exit;

use WP_Easy\EasyGoogleReviews\API\GoogleAPI;

final class Cron
{
    private const SYNC_HOOK = 'egr_sync_reviews';
    private const TOKEN_REFRESH_HOOK = 'egr_refresh_token';

    public static function init(): void
    {
        add_action(self::SYNC_HOOK, [self::class, 'sync_reviews']);
        add_action(self::TOKEN_REFRESH_HOOK, [self::class, 'refresh_token']);

        // Hook into admin actions for manual sync
        add_action('wp_ajax_egr_manual_sync', [self::class, 'handle_manual_sync']);
    }

    public static function schedule_events(): void
    {
        // Schedule review sync (daily)
        if (!wp_next_scheduled(self::SYNC_HOOK)) {
            wp_schedule_event(time(), 'daily', self::SYNC_HOOK);
        }

        // Schedule token refresh (hourly check)
        if (!wp_next_scheduled(self::TOKEN_REFRESH_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::TOKEN_REFRESH_HOOK);
        }
    }

    public static function clear_events(): void
    {
        wp_clear_scheduled_hook(self::SYNC_HOOK);
        wp_clear_scheduled_hook(self::TOKEN_REFRESH_HOOK);
    }

    public static function sync_reviews(): void
    {
        $business_id = get_option('egr_business_id');

        if (empty($business_id)) {
            error_log('EGR: Cannot sync reviews - Business ID not configured');
            return;
        }

        if (!GoogleAPI::ensure_valid_token()) {
            error_log('EGR: Cannot sync reviews - Invalid token');
            return;
        }

        $all_reviews = [];
        $page_token = '';
        $max_pages = 50; // Prevent infinite loops
        $current_page = 0;

        do {
            $current_page++;

            if ($current_page > $max_pages) {
                error_log('EGR: Reached maximum page limit during sync');
                break;
            }

            $result = GoogleAPI::fetch_reviews($business_id, $page_token);

            if (isset($result['error'])) {
                error_log('EGR: Error fetching reviews: ' . $result['error']);
                break;
            }

            if (isset($result['reviews']) && is_array($result['reviews'])) {
                $all_reviews = array_merge($all_reviews, $result['reviews']);
            }

            $page_token = $result['nextPageToken'] ?? '';

            // Add a small delay to avoid rate limiting
            if (!empty($page_token)) {
                sleep(1);
            }

        } while (!empty($page_token));

        if (!empty($all_reviews)) {
            // Cache the reviews
            GoogleAPI::cache_reviews($all_reviews);

            // Update statistics
            $total_count = count($all_reviews);
            $five_star_count = GoogleAPI::count_five_star_reviews($all_reviews);

            update_option('egr_total_reviews', $total_count);
            update_option('egr_five_star_count', $five_star_count);
            update_option('egr_last_sync', time());

            error_log("EGR: Synced {$total_count} reviews ({$five_star_count} five-star)");
        } else {
            error_log('EGR: No reviews found during sync');
        }
    }

    public static function refresh_token(): void
    {
        $refresh_token = get_option('egr_google_refresh_token');

        if (empty($refresh_token)) {
            return;
        }

        $expires = get_option('egr_google_token_expires', 0);

        // Refresh if token expires in the next hour
        if ($expires > time() + HOUR_IN_SECONDS) {
            return;
        }

        $result = GoogleAPI::refresh_access_token();

        if (isset($result['error'])) {
            error_log('EGR: Failed to refresh token: ' . $result['error']);
        } else {
            error_log('EGR: Token refreshed successfully');
        }
    }

    public static function force_sync(): array
    {
        $business_id = get_option('egr_business_id');

        if (empty($business_id)) {
            return ['error' => __('Business ID not configured', 'egr')];
        }

        if (!GoogleAPI::ensure_valid_token()) {
            return ['error' => __('Authentication required', 'egr')];
        }

        // Set a flag to indicate sync is in progress
        set_transient('egr_sync_in_progress', true, 300); // 5 minutes

        // Run the sync
        self::sync_reviews();

        // Clear the progress flag
        delete_transient('egr_sync_in_progress');

        $total_reviews = get_option('egr_total_reviews', 0);
        $five_star_count = get_option('egr_five_star_count', 0);
        $last_sync = get_option('egr_last_sync', 0);

        return [
            'success' => true,
            'total_reviews' => $total_reviews,
            'five_star_count' => $five_star_count,
            'last_sync' => $last_sync ? date('Y-m-d H:i:s', $last_sync) : __('Never', 'egr')
        ];
    }

    public static function is_sync_in_progress(): bool
    {
        return get_transient('egr_sync_in_progress') !== false;
    }

    public static function handle_manual_sync(): void
    {
        check_ajax_referer('egr_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'egr'));
        }

        if (self::is_sync_in_progress()) {
            wp_send_json_error(__('Sync already in progress', 'egr'));
        }

        $result = self::force_sync();

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }

    public static function get_next_scheduled_sync(): string
    {
        $timestamp = wp_next_scheduled(self::SYNC_HOOK);

        if (!$timestamp) {
            return __('Not scheduled', 'egr');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    public static function get_sync_status(): array
    {
        $last_sync = get_option('egr_last_sync', 0);
        $total_reviews = get_option('egr_total_reviews', 0);
        $five_star_count = get_option('egr_five_star_count', 0);
        $next_sync = self::get_next_scheduled_sync();
        $in_progress = self::is_sync_in_progress();

        return [
            'last_sync' => $last_sync ? date('Y-m-d H:i:s', $last_sync) : __('Never', 'egr'),
            'total_reviews' => $total_reviews,
            'five_star_count' => $five_star_count,
            'next_sync' => $next_sync,
            'in_progress' => $in_progress
        ];
    }
}