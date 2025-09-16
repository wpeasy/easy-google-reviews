<?php
namespace WP_Easy\EasyGoogleReviews\Shortcodes;

defined('ABSPATH') || exit;

use WP_Easy\EasyGoogleReviews\API\GoogleAPI;

final class ReviewsShortcode
{
    public static function init(): void
    {
        add_shortcode('egr_reviews', [self::class, 'render']);
    }

    public static function render(array $atts): string
    {
        $atts = shortcode_atts([
            'count' => 5,
            'show_all' => 'false',
            'rows_per_page' => get_option('egr_default_rows_per_page', 10),
            'fields' => '', // comma-separated list
            'rating_filter' => '', // e.g., '5' for 5-star only
            'order' => 'desc', // desc or asc
            'class' => ''
        ], $atts, 'egr_reviews');

        // Sanitize attributes
        $count = absint($atts['count']);
        $show_all = filter_var($atts['show_all'], FILTER_VALIDATE_BOOLEAN);
        $rows_per_page = absint($atts['rows_per_page']);
        $rating_filter = sanitize_text_field($atts['rating_filter']);
        $order = in_array($atts['order'], ['asc', 'desc']) ? $atts['order'] : 'desc';
        $class = sanitize_html_class($atts['class']);

        // Parse fields
        $fields = self::parse_fields($atts['fields']);

        // Get reviews data
        $reviews = GoogleAPI::get_cached_reviews();

        if (empty($reviews)) {
            return self::render_no_reviews();
        }

        // Filter reviews by rating if specified
        if (!empty($rating_filter)) {
            $reviews = self::filter_by_rating($reviews, $rating_filter);
        }

        // Sort reviews
        $reviews = self::sort_reviews($reviews, $order);

        // Limit reviews if not showing all
        if (!$show_all && $count > 0) {
            $reviews = array_slice($reviews, 0, $count);
        }

        // Generate unique ID for this instance
        $instance_id = 'egr-reviews-' . wp_rand(1000, 9999);

        if ($show_all && count($reviews) > $rows_per_page) {
            return self::render_paginated_reviews($reviews, $fields, $rows_per_page, $instance_id, $class);
        }

        return self::render_reviews_grid($reviews, $fields, $instance_id, $class);
    }

    private static function parse_fields(string $fields_string): array
    {
        if (empty($fields_string)) {
            return get_option('egr_default_fields', ['author_name', 'text', 'rating', 'time']);
        }

        $fields = array_map('trim', explode(',', $fields_string));
        $allowed_fields = ['author_name', 'text', 'rating', 'time', 'reply'];

        return array_intersect($fields, $allowed_fields);
    }

    private static function filter_by_rating(array $reviews, string $rating): array
    {
        $rating_map = [
            '1' => 'ONE',
            '2' => 'TWO',
            '3' => 'THREE',
            '4' => 'FOUR',
            '5' => 'FIVE'
        ];

        $target_rating = $rating_map[$rating] ?? null;

        if (!$target_rating) {
            return $reviews;
        }

        return array_filter($reviews, function($review) use ($target_rating) {
            return isset($review['starRating']) && $review['starRating'] === $target_rating;
        });
    }

    private static function sort_reviews(array $reviews, string $order): array
    {
        usort($reviews, function($a, $b) use ($order) {
            $time_a = isset($a['createTime']) ? strtotime($a['createTime']) : 0;
            $time_b = isset($b['createTime']) ? strtotime($b['createTime']) : 0;

            if ($order === 'asc') {
                return $time_a - $time_b;
            }

            return $time_b - $time_a;
        });

        return $reviews;
    }

    private static function render_no_reviews(): string
    {
        return '<div class="egr__no-reviews">' . __('No reviews found.', 'egr') . '</div>';
    }

    private static function render_reviews_grid(array $reviews, array $fields, string $instance_id, string $class): string
    {
        $css_class = 'egr__reviews-grid';
        if (!empty($class)) {
            $css_class .= ' ' . $class;
        }

        $output = '<div id="' . esc_attr($instance_id) . '" class="' . esc_attr($css_class) . '">';

        foreach ($reviews as $review) {
            $output .= self::render_single_review($review, $fields);
        }

        $output .= '</div>';

        return $output;
    }

    private static function render_paginated_reviews(array $reviews, array $fields, int $rows_per_page, string $instance_id, string $class): string
    {
        $total_reviews = count($reviews);
        $total_pages = ceil($total_reviews / $rows_per_page);

        $css_class = 'egr__reviews-container egr__reviews-paginated';
        if (!empty($class)) {
            $css_class .= ' ' . $class;
        }

        $output = '<div id="' . esc_attr($instance_id) . '" class="' . esc_attr($css_class) . '" data-rows-per-page="' . esc_attr($rows_per_page) . '">';

        // Render pagination controls (top)
        if ($total_pages > 1) {
            $output .= self::render_pagination_controls($total_pages, 'top');
        }

        // Render review pages
        for ($page = 1; $page <= $total_pages; $page++) {
            $start = ($page - 1) * $rows_per_page;
            $page_reviews = array_slice($reviews, $start, $rows_per_page);

            $page_class = 'egr__reviews-page';
            if ($page === 1) {
                $page_class .= ' egr__reviews-page--active';
            }

            $output .= '<div class="' . esc_attr($page_class) . '" data-page="' . esc_attr($page) . '">';
            $output .= '<div class="egr__reviews-grid">';

            foreach ($page_reviews as $review) {
                $output .= self::render_single_review($review, $fields);
            }

            $output .= '</div></div>';
        }

        // Render pagination controls (bottom)
        if ($total_pages > 1) {
            $output .= self::render_pagination_controls($total_pages, 'bottom');
        }

        $output .= '</div>';

        return $output;
    }

    private static function render_pagination_controls(int $total_pages, string $position): string
    {
        $output = '<div class="egr__pagination egr__pagination--' . esc_attr($position) . '">';
        $output .= '<button type="button" class="egr__pagination-btn egr__pagination-btn--prev" disabled>';
        $output .= self::get_prev_icon() . ' ' . __('Previous', 'egr');
        $output .= '</button>';

        $output .= '<span class="egr__pagination-info">';
        $output .= '<span class="egr__pagination-current">1</span> / <span class="egr__pagination-total">' . $total_pages . '</span>';
        $output .= '</span>';

        $output .= '<button type="button" class="egr__pagination-btn egr__pagination-btn--next">';
        $output .= __('Next', 'egr') . ' ' . self::get_next_icon();
        $output .= '</button>';

        $output .= '</div>';

        return $output;
    }

    private static function render_single_review(array $review, array $fields): string
    {
        $output = '<div class="egr__review">';

        // Review header
        $output .= '<div class="egr__review-header">';

        if (in_array('author_name', $fields) && isset($review['reviewer']['displayName'])) {
            $output .= '<div class="egr__review-author">';
            $output .= esc_html($review['reviewer']['displayName']);
            $output .= '</div>';
        }

        if (in_array('rating', $fields) && isset($review['starRating'])) {
            $output .= '<div class="egr__review-rating">';
            $output .= self::render_star_rating($review['starRating']);
            $output .= '</div>';
        }

        if (in_array('time', $fields) && isset($review['createTime'])) {
            $output .= '<div class="egr__review-date">';
            $output .= esc_html(date('F j, Y', strtotime($review['createTime'])));
            $output .= '</div>';
        }

        $output .= '</div>';

        // Review content
        if (in_array('text', $fields) && isset($review['comment'])) {
            $output .= '<div class="egr__review-content">';
            $output .= '<p>' . esc_html($review['comment']) . '</p>';
            $output .= '</div>';
        }

        // Business reply
        if (in_array('reply', $fields) && isset($review['reviewReply']['comment'])) {
            $output .= '<div class="egr__review-reply">';
            $output .= '<div class="egr__review-reply-header">';
            $output .= '<strong>' . __('Response from the business', 'egr') . '</strong>';
            $output .= '</div>';
            $output .= '<div class="egr__review-reply-content">';
            $output .= '<p>' . esc_html($review['reviewReply']['comment']) . '</p>';
            $output .= '</div>';
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    private static function render_star_rating(string $rating): string
    {
        $rating_map = [
            'ONE' => 1,
            'TWO' => 2,
            'THREE' => 3,
            'FOUR' => 4,
            'FIVE' => 5
        ];

        $stars = $rating_map[$rating] ?? 0;

        $output = '<div class="egr__stars" aria-label="' . sprintf(__('%d out of 5 stars', 'egr'), $stars) . '">';

        for ($i = 1; $i <= 5; $i++) {
            $class = 'egr__star';
            if ($i <= $stars) {
                $class .= ' egr__star--filled';
            }

            $output .= '<span class="' . esc_attr($class) . '">' . self::get_star_icon() . '</span>';
        }

        $output .= '</div>';

        return $output;
    }

    private static function get_star_icon(): string
    {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    }

    private static function get_prev_icon(): string
    {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>';
    }

    private static function get_next_icon(): string
    {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>';
    }
}