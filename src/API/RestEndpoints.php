<?php
namespace WP_Easy\EasyGoogleReviews\API;

defined('ABSPATH') || exit;

final class RestEndpoints
{
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void
    {
        // Register namespace for our endpoints
        $namespace = 'egr/v1';

        // Get 5 star count endpoint
        register_rest_route($namespace, '/5star-count', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_5star_count'],
            'permission_callback' => [self::class, 'check_same_origin'],
            'args' => []
        ]);

        // Get reviews with pagination endpoint
        register_rest_route($namespace, '/reviews', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_reviews'],
            'permission_callback' => [self::class, 'check_same_origin'],
            'args' => [
                'page_number' => [
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 1;
                    },
                    'sanitize_callback' => 'absint'
                ],
                'row_count' => [
                    'default' => 10,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 1 && $param <= 100;
                    },
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);

        // Get stats endpoint
        register_rest_route($namespace, '/stats', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_stats'],
            'permission_callback' => [self::class, 'check_same_origin'],
            'args' => []
        ]);
    }

    /**
     * Same-origin permission check
     */
    public static function check_same_origin(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        // If no origin header, check referer
        if (empty($origin)) {
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            if (!empty($referer)) {
                $parsed = parse_url($referer);
                $origin = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? '');
            }
        }

        // Get site URL without protocol
        $site_url = get_site_url();
        $site_host = parse_url($site_url, PHP_URL_HOST);

        // Allow same domain/origin requests
        if (!empty($origin)) {
            $origin_host = parse_url($origin, PHP_URL_HOST);
            return $origin_host === $site_host;
        }

        // Fallback: allow if host matches
        return !empty($host) && $host === $site_host;
    }

    /**
     * Get 5 star count endpoint
     */
    public static function get_5star_count(\WP_REST_Request $request): \WP_REST_Response
    {
        $count = get_option('egr_five_star_count', 0);

        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'five_star_count' => (int) $count
            ]
        ], 200);
    }

    /**
     * Get reviews with pagination endpoint
     */
    public static function get_reviews(\WP_REST_Request $request): \WP_REST_Response
    {
        $page_number = $request->get_param('page_number');
        $row_count = $request->get_param('row_count');

        // Get cached reviews
        $all_reviews = get_transient('egr_reviews_cache');

        if ($all_reviews === false) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('No reviews data available', 'egr'),
                'data' => [
                    'reviews' => [],
                    'pagination' => [
                        'current_page' => $page_number,
                        'per_page' => $row_count,
                        'total_items' => 0,
                        'total_pages' => 0
                    ]
                ]
            ], 200);
        }

        // Calculate pagination
        $total_items = count($all_reviews);
        $total_pages = ceil($total_items / $row_count);
        $offset = ($page_number - 1) * $row_count;

        // Get reviews for current page
        $reviews = array_slice($all_reviews, $offset, $row_count);

        // Sanitize review data for frontend
        $sanitized_reviews = array_map([self::class, 'sanitize_review_for_frontend'], $reviews);

        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'reviews' => $sanitized_reviews,
                'pagination' => [
                    'current_page' => $page_number,
                    'per_page' => $row_count,
                    'total_items' => $total_items,
                    'total_pages' => $total_pages,
                    'has_next_page' => $page_number < $total_pages,
                    'has_prev_page' => $page_number > 1
                ]
            ]
        ], 200);
    }

    /**
     * Get stats endpoint
     */
    public static function get_stats(\WP_REST_Request $request): \WP_REST_Response
    {
        $last_sync = get_option('egr_last_sync', 0);
        $total_reviews = get_option('egr_total_reviews', 0);
        $five_star_count = get_option('egr_five_star_count', 0);

        // Check if we have a valid access token and business ID
        $access_token = get_option('egr_google_access_token', '');
        $business_id = get_option('egr_business_id', '');
        $token_expires = get_option('egr_google_token_expires', 0);

        // Determine connection status
        $is_connected = false;
        if (!empty($access_token) && !empty($business_id)) {
            // Check if token is still valid (with 5 minute buffer)
            $is_connected = ($token_expires > (time() + 300));
        }

        // Get last sync error if any
        $last_error = get_transient('egr_last_sync_error');

        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'connection_status' => $is_connected ? 'connected' : 'not_connected',
                'last_sync' => [
                    'timestamp' => (int) $last_sync,
                    'formatted' => $last_sync ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync) : null,
                    'relative' => $last_sync ? human_time_diff($last_sync) . ' ' . __('ago', 'egr') : null
                ],
                'totals' => [
                    'total_reviews' => (int) $total_reviews,
                    'five_star_count' => (int) $five_star_count
                ],
                'has_error' => !empty($last_error),
                'error_message' => $last_error ?: null
            ]
        ], 200);
    }

    /**
     * Sanitize review data for frontend consumption
     */
    private static function sanitize_review_for_frontend(array $review): array
    {
        return [
            'name' => sanitize_text_field($review['authorDisplayName'] ?? ''),
            'rating' => (int) ($review['starRating'] ?? 0),
            'text' => wp_kses_post($review['comment'] ?? ''),
            'date' => [
                'timestamp' => strtotime($review['createTime'] ?? ''),
                'formatted' => date_i18n(get_option('date_format'), strtotime($review['createTime'] ?? '')),
                'relative' => human_time_diff(strtotime($review['createTime'] ?? '')) . ' ' . __('ago', 'egr')
            ],
            'reply' => !empty($review['reviewReply']) ? [
                'text' => wp_kses_post($review['reviewReply']['comment'] ?? ''),
                'date' => [
                    'timestamp' => strtotime($review['reviewReply']['updateTime'] ?? ''),
                    'formatted' => date_i18n(get_option('date_format'), strtotime($review['reviewReply']['updateTime'] ?? '')),
                    'relative' => human_time_diff(strtotime($review['reviewReply']['updateTime'] ?? '')) . ' ' . __('ago', 'egr')
                ]
            ] : null
        ];
    }
}