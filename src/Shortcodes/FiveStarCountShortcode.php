<?php
namespace WP_Easy\EasyGoogleReviews\Shortcodes;

defined('ABSPATH') || exit;

final class FiveStarCountShortcode
{
    public static function init(): void
    {
        add_shortcode('egr_5star_count', [self::class, 'render']);
    }

    public static function render(array $atts): string
    {
        $atts = shortcode_atts([
            'format' => 'number', // 'number', 'formatted', 'text'
            'prefix' => '',
            'suffix' => '',
            'class' => ''
        ], $atts, 'egr_5star_count');

        // Sanitize attributes
        $format = in_array($atts['format'], ['number', 'formatted', 'text']) ? $atts['format'] : 'number';
        $prefix = sanitize_text_field($atts['prefix']);
        $suffix = sanitize_text_field($atts['suffix']);
        $class = sanitize_html_class($atts['class']);

        // Get the count from saved option (updated during sync)
        $count = get_option('egr_five_star_count', 0);

        // Format the output
        $output = self::format_count($count, $format);

        // Add prefix and suffix
        if (!empty($prefix)) {
            $output = $prefix . $output;
        }

        if (!empty($suffix)) {
            $output = $output . $suffix;
        }

        // Wrap in container
        $css_class = 'egr__five-star-count';
        if (!empty($class)) {
            $css_class .= ' ' . $class;
        }

        return '<span class="' . esc_attr($css_class) . '">' . esc_html($output) . '</span>';
    }

    private static function format_count(int $count, string $format): string
    {
        switch ($format) {
            case 'formatted':
                return number_format($count);

            case 'text':
                if ($count === 0) {
                    return __('No five-star reviews', 'egr');
                } elseif ($count === 1) {
                    return __('1 five-star review', 'egr');
                } else {
                    return sprintf(__('%s five-star reviews', 'egr'), number_format($count));
                }

            case 'number':
            default:
                return (string) $count;
        }
    }
}