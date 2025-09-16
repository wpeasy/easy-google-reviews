<?php
namespace WP_Easy\EasyGoogleReviews\API;

defined('ABSPATH') || exit;

final class GoogleAPI
{
    private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';
    private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GOOGLE_API_BASE_URL = 'https://mybusiness.googleapis.com/v4';

    public static function init(): void
    {
        add_action('wp_ajax_egr_google_auth', [self::class, 'handle_auth_callback']);
        add_action('wp_ajax_egr_refresh_token', [self::class, 'handle_refresh_token']);
        add_action('wp_ajax_egr_test_connection', [self::class, 'handle_test_connection']);
        add_action('wp_ajax_egr_fetch_reviews', [self::class, 'handle_fetch_reviews']);
    }

    public static function get_auth_url(): string
    {
        $client_id = get_option('egr_google_client_id');

        if (empty($client_id)) {
            return '';
        }

        $params = [
            'client_id' => $client_id,
            'redirect_uri' => admin_url('admin.php?page=easy-google-reviews'),
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => wp_create_nonce('egr_google_auth')
        ];

        return self::GOOGLE_AUTH_URL . '?' . http_build_query($params);
    }

    public static function exchange_code_for_token(string $code): array
    {
        $client_id = get_option('egr_google_client_id');
        $client_secret = get_option('egr_google_client_secret');

        if (empty($client_id) || empty($client_secret)) {
            return ['error' => __('Client ID or Secret not configured', 'egr')];
        }

        $params = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => admin_url('admin.php?page=easy-google-reviews')
        ];

        $response = wp_remote_post(self::GOOGLE_TOKEN_URL, [
            'body' => $params,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return ['error' => $data['error_description'] ?? $data['error']];
        }

        if (isset($data['access_token'])) {
            update_option('egr_google_access_token', sanitize_text_field($data['access_token']));

            if (isset($data['refresh_token'])) {
                update_option('egr_google_refresh_token', sanitize_text_field($data['refresh_token']));
            }

            $expires_in = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;
            update_option('egr_google_token_expires', time() + $expires_in);

            return ['success' => true];
        }

        return ['error' => __('Invalid response from Google', 'egr')];
    }

    public static function refresh_access_token(): array
    {
        $client_id = get_option('egr_google_client_id');
        $client_secret = get_option('egr_google_client_secret');
        $refresh_token = get_option('egr_google_refresh_token');

        if (empty($client_id) || empty($client_secret) || empty($refresh_token)) {
            return ['error' => __('Missing authentication credentials', 'egr')];
        }

        $params = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        ];

        $response = wp_remote_post(self::GOOGLE_TOKEN_URL, [
            'body' => $params,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return ['error' => $data['error_description'] ?? $data['error']];
        }

        if (isset($data['access_token'])) {
            update_option('egr_google_access_token', sanitize_text_field($data['access_token']));

            $expires_in = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;
            update_option('egr_google_token_expires', time() + $expires_in);

            return ['success' => true];
        }

        return ['error' => __('Failed to refresh token', 'egr')];
    }

    public static function is_token_valid(): bool
    {
        $token = get_option('egr_google_access_token');
        $expires = get_option('egr_google_token_expires', 0);

        return !empty($token) && $expires > time() + 300; // 5 minute buffer
    }

    public static function ensure_valid_token(): bool
    {
        if (self::is_token_valid()) {
            return true;
        }

        $result = self::refresh_access_token();
        return isset($result['success']) && $result['success'];
    }

    public static function fetch_business_locations(): array
    {
        if (!self::ensure_valid_token()) {
            return ['error' => __('Unable to authenticate with Google', 'egr')];
        }

        $access_token = get_option('egr_google_access_token');

        $response = wp_remote_get(self::GOOGLE_API_BASE_URL . '/accounts', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return ['error' => $data['error']['message'] ?? __('API Error', 'egr')];
        }

        return $data;
    }

    public static function fetch_reviews(string $business_id, string $page_token = ''): array
    {
        if (!self::ensure_valid_token()) {
            return ['error' => __('Unable to authenticate with Google', 'egr')];
        }

        $access_token = get_option('egr_google_access_token');
        $url = self::GOOGLE_API_BASE_URL . "/accounts/{$business_id}/locations/{$business_id}/reviews";

        $params = [];
        if (!empty($page_token)) {
            $params['pageToken'] = $page_token;
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return ['error' => $data['error']['message'] ?? __('API Error', 'egr')];
        }

        return $data;
    }

    public static function get_cached_reviews(): array
    {
        $cached = get_transient('egr_reviews_data');

        if ($cached === false) {
            return [];
        }

        return is_array($cached) ? $cached : [];
    }

    public static function cache_reviews(array $reviews): void
    {
        $timeout = get_option('egr_cache_timeout', 6 * HOUR_IN_SECONDS);
        set_transient('egr_reviews_data', $reviews, $timeout);
    }

    public static function count_five_star_reviews(array $reviews): int
    {
        $count = 0;

        foreach ($reviews as $review) {
            if (isset($review['starRating']) && $review['starRating'] === 'FIVE') {
                $count++;
            }
        }

        return $count;
    }

    public static function handle_auth_callback(): void
    {
        check_ajax_referer('egr_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'egr'));
        }

        $code = sanitize_text_field($_POST['code'] ?? '');

        if (empty($code)) {
            wp_send_json_error(__('Authorization code missing', 'egr'));
        }

        $result = self::exchange_code_for_token($code);

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success(__('Authentication successful', 'egr'));
    }

    public static function handle_refresh_token(): void
    {
        check_ajax_referer('egr_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'egr'));
        }

        $result = self::refresh_access_token();

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success(__('Token refreshed successfully', 'egr'));
    }

    public static function handle_test_connection(): void
    {
        check_ajax_referer('egr_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'egr'));
        }

        $result = self::fetch_business_locations();

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }

    public static function handle_fetch_reviews(): void
    {
        check_ajax_referer('egr_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'egr'));
        }

        $business_id = sanitize_text_field($_POST['business_id'] ?? '');

        if (empty($business_id)) {
            wp_send_json_error(__('Business ID missing', 'egr'));
        }

        $result = self::fetch_reviews($business_id);

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }
}