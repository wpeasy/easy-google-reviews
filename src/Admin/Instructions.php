<?php
namespace WP_Easy\EasyGoogleReviews\Admin;

defined('ABSPATH') || exit;

final class Instructions
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
    }

    public static function add_admin_menu(): void
    {
        add_submenu_page(
            'easy-google-reviews',
            __('Instructions', 'egr'),
            __('Instructions', 'egr'),
            'manage_options',
            'easy-google-reviews-instructions',
            [self::class, 'render_instructions_page']
        );
    }

    public static function enqueue_scripts(string $hook): void
    {
        if (strpos($hook, 'easy-google-reviews-instructions') === false) {
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
            'egr-instructions',
            EGR_PLUGIN_URL . 'assets/css/instructions.css',
            ['wpe-framework-admin'],
            EGR_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'egr-instructions',
            EGR_PLUGIN_URL . 'assets/js/instructions.js',
            ['jquery'],
            EGR_PLUGIN_VERSION,
            true
        );
    }

    public static function render_instructions_page(): void
    {
        ?>
        <div class="wrap egr-instructions">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="egr-tabs">
                <nav class="egr-tabs__nav">
                    <button type="button" class="egr-tabs__button egr-tabs__button--active" data-tab="google">
                        <?php _e('Google Step by Step', 'egr'); ?>
                    </button>
                    <button type="button" class="egr-tabs__button" data-tab="settings">
                        <?php _e('Settings', 'egr'); ?>
                    </button>
                    <button type="button" class="egr-tabs__button" data-tab="shortcodes">
                        <?php _e('Shortcodes', 'egr'); ?>
                    </button>
                    <button type="button" class="egr-tabs__button" data-tab="rest-endpoints">
                        <?php _e('REST Endpoints', 'egr'); ?>
                    </button>
                </nav>

                <div class="egr-tabs__content">
                    <!-- Google Step by Step Tab -->
                    <div class="egr-tabs__panel egr-tabs__panel--active" data-panel="google">
                        <?php self::render_google_tab(); ?>
                    </div>

                    <!-- Settings Tab -->
                    <div class="egr-tabs__panel" data-panel="settings">
                        <?php self::render_settings_tab(); ?>
                    </div>

                    <!-- Shortcodes Tab -->
                    <div class="egr-tabs__panel" data-panel="shortcodes">
                        <?php self::render_shortcodes_tab(); ?>
                    </div>

                    <!-- REST Endpoints Tab -->
                    <div class="egr-tabs__panel" data-panel="rest-endpoints">
                        <?php self::render_rest_endpoints_tab(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_google_tab(): void
    {
        ?>
        <div class="egr-instructions__content">
            <h2><?php _e('Google Business Profile API Setup', 'egr'); ?></h2>

            <div class="egr-step">
                <h3><?php _e('Step 1: Create Google Cloud Project', 'egr'); ?></h3>
                <ol>
                    <li><?php _e('Go to the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>', 'egr'); ?></li>
                    <li><?php _e('Click "Select a project" dropdown at the top of the page', 'egr'); ?></li>
                    <li><?php _e('Click "New Project" to create a new project', 'egr'); ?></li>
                    <li><?php _e('Enter a project name (e.g., "My Website Reviews")', 'egr'); ?></li>
                    <li><?php _e('Click "Create" and wait for the project to be created', 'egr'); ?></li>
                </ol>
                <div class="egr-note">
                    <strong><?php _e('Note:', 'egr'); ?></strong>
                    <?php _e('You can also use an existing Google Cloud project if you have one.', 'egr'); ?>
                </div>
            </div>

            <div class="egr-step">
                <h3><?php _e('Step 2: Enable Google My Business API', 'egr'); ?></h3>
                <ol>
                    <li><?php _e('Make sure your new project is selected in the project dropdown', 'egr'); ?></li>
                    <li><?php _e('Go to the <a href="https://console.cloud.google.com/apis/library" target="_blank">API Library</a>', 'egr'); ?></li>
                    <li><?php _e('Search for "Google My Business API" or "Business Profile API"', 'egr'); ?></li>
                    <li><?php _e('Click on the API from the search results', 'egr'); ?></li>
                    <li><?php _e('Click the "Enable" button', 'egr'); ?></li>
                </ol>
                <div class="egr-warning">
                    <strong><?php _e('Important:', 'egr'); ?></strong>
                    <?php _e('The API may take a few minutes to become fully active after enabling.', 'egr'); ?>
                </div>
            </div>

            <div class="egr-step">
                <h3><?php _e('Step 3: Create OAuth 2.0 Credentials', 'egr'); ?></h3>
                <ol>
                    <li><?php _e('Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank">API & Services > Credentials</a>', 'egr'); ?></li>
                    <li><?php _e('Click "Create Credentials" and select "OAuth client ID"', 'egr'); ?></li>
                    <li><?php _e('If prompted, configure the OAuth consent screen first:', 'egr'); ?>
                        <ul>
                            <li><?php _e('Choose "External" user type', 'egr'); ?></li>
                            <li><?php _e('Fill in the required app information', 'egr'); ?></li>
                            <li><?php _e('Add your email as a test user', 'egr'); ?></li>
                        </ul>
                    </li>
                    <li><?php _e('For Application type, select "Web application"', 'egr'); ?></li>
                    <li><?php _e('Enter a name for your OAuth client (e.g., "Website Reviews")', 'egr'); ?></li>
                    <li><?php _e('Add authorized redirect URIs:', 'egr'); ?>
                        <div class="egr-code-block">
                            <code><?php echo esc_url(admin_url('admin.php?page=easy-google-reviews')); ?></code>
                            <button type="button" class="egr-copy-btn" data-copy="<?php echo esc_attr(admin_url('admin.php?page=easy-google-reviews')); ?>">
                                <?php _e('Copy', 'egr'); ?>
                            </button>
                        </div>
                    </li>
                    <li><?php _e('Click "Create"', 'egr'); ?></li>
                </ol>
            </div>

            <div class="egr-step">
                <h3><?php _e('Step 4: Get Your Credentials', 'egr'); ?></h3>
                <ol>
                    <li><?php _e('After creating the OAuth client, a dialog will show your credentials', 'egr'); ?></li>
                    <li><?php _e('Copy the "Client ID" and "Client Secret"', 'egr'); ?></li>
                    <li><?php _e('Go to your WordPress admin > Google Reviews > Settings', 'egr'); ?></li>
                    <li><?php _e('Paste the Client ID and Client Secret into the respective fields', 'egr'); ?></li>
                    <li><?php _e('Save the settings', 'egr'); ?></li>
                </ol>
            </div>

            <div class="egr-step">
                <h3><?php _e('Step 5: Connect Your Google Account', 'egr'); ?></h3>
                <ol>
                    <li><?php _e('After saving your credentials, click "Connect to Google"', 'egr'); ?></li>
                    <li><?php _e('You\'ll be redirected to Google to authorize the connection', 'egr'); ?></li>
                    <li><?php _e('Sign in with the Google account that manages your business profile', 'egr'); ?></li>
                    <li><?php _e('Grant the necessary permissions', 'egr'); ?></li>
                    <li><?php _e('You\'ll be redirected back to your WordPress admin', 'egr'); ?></li>
                </ol>
            </div>

            <div class="egr-step">
                <h3><?php _e('Step 6: Find Your Business Location ID', 'egr'); ?></h3>
                <ol>
                    <li><?php _e('After connecting, scroll down to the "Admin Actions" section in the settings', 'egr'); ?></li>
                    <li><?php _e('Click the "Test Connection" button', 'egr'); ?></li>
                    <li><?php _e('The response will appear in a text box directly below the button', 'egr'); ?></li>
                    <li><?php _e('Look for your business name in the JSON response', 'egr'); ?></li>
                    <li><?php _e('Copy the complete location ID (it will look like "accounts/123456789/locations/987654321")', 'egr'); ?></li>
                    <li><?php _e('Scroll back up and paste the Business Location ID in the settings field', 'egr'); ?></li>
                    <li><?php _e('Save the settings', 'egr'); ?></li>
                </ol>
                <div class="egr-note">
                    <strong><?php _e('Tip:', 'egr'); ?></strong>
                    <?php _e('The response appears as formatted JSON text in a box below the Test Connection button. You don\'t need to open browser developer tools - it\'s visible directly on the page.', 'egr'); ?>
                </div>
            </div>

            <div class="egr-step">
                <h3><?php _e('Step 7: Sync Your Reviews', 'egr'); ?></h3>
                <ol>
                    <li><?php _e('Click "Sync Reviews Now" to fetch your reviews', 'egr'); ?></li>
                    <li><?php _e('Check the Sync Status page to monitor the process', 'egr'); ?></li>
                    <li><?php _e('Once synced, you can use the shortcodes to display reviews', 'egr'); ?></li>
                </ol>
            </div>

            <div class="egr-troubleshooting">
                <h3><?php _e('Troubleshooting Common Issues', 'egr'); ?></h3>

                <div class="egr-trouble-item">
                    <h4><?php _e('Error: "Client ID or Secret not configured"', 'egr'); ?></h4>
                    <p><?php _e('Make sure you\'ve entered both the Client ID and Client Secret correctly in the settings.', 'egr'); ?></p>
                </div>

                <div class="egr-trouble-item">
                    <h4><?php _e('Error: "redirect_uri_mismatch"', 'egr'); ?></h4>
                    <p><?php _e('The redirect URI in your Google Console must exactly match your WordPress admin URL. Check for http vs https and trailing slashes.', 'egr'); ?></p>
                </div>

                <div class="egr-trouble-item">
                    <h4><?php _e('Error: "API not enabled"', 'egr'); ?></h4>
                    <p><?php _e('Make sure you\'ve enabled the Google My Business API in your Google Cloud Console.', 'egr'); ?></p>
                </div>

                <div class="egr-trouble-item">
                    <h4><?php _e('No reviews found', 'egr'); ?></h4>
                    <p><?php _e('Verify that your Business Location ID is correct and that your business has public reviews visible.', 'egr'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_settings_tab(): void
    {
        ?>
        <div class="egr-instructions__content">
            <h2><?php _e('Plugin Settings Guide', 'egr'); ?></h2>

            <div class="egr-setting-group">
                <h3><?php _e('Google API Configuration', 'egr'); ?></h3>

                <div class="egr-setting-item">
                    <h4><?php _e('Google Client ID', 'egr'); ?></h4>
                    <p><?php _e('Your OAuth 2.0 Client ID from Google Cloud Console. This is a long string that looks like:', 'egr'); ?></p>
                    <code>123456789-abcdefghijklmnop.apps.googleusercontent.com</code>
                </div>

                <div class="egr-setting-item">
                    <h4><?php _e('Google Client Secret', 'egr'); ?></h4>
                    <p><?php _e('Your OAuth 2.0 Client Secret from Google Cloud Console. This is a shorter string that looks like:', 'egr'); ?></p>
                    <code>GOCSPX-abcdefghijklmnopqrstuvwxyz</code>
                </div>

                <div class="egr-setting-item">
                    <h4><?php _e('Business Location ID', 'egr'); ?></h4>
                    <p><?php _e('Your Google Business Profile location ID. Use the "Test Connection" button to find this. Enter the complete string exactly as shown:', 'egr'); ?></p>
                    <div class="egr-code-block">
                        <code>accounts/123456789/locations/987654321</code>
                        <button type="button" class="egr-copy-btn" data-copy="accounts/123456789/locations/987654321"><?php _e('Copy Example', 'egr'); ?></button>
                    </div>
                    <div class="egr-note">
                        <strong><?php _e('Important:', 'egr'); ?></strong>
                        <?php _e('You must enter the entire Location ID string including "accounts/" and "locations/" parts. Do not enter just the numbers.', 'egr'); ?>
                    </div>
                </div>
            </div>

            <div class="egr-setting-group">
                <h3><?php _e('Display Settings', 'egr'); ?></h3>

                <div class="egr-setting-item">
                    <h4><?php _e('Default Rows Per Page', 'egr'); ?></h4>
                    <p><?php _e('How many reviews to show per page when using pagination. Recommended: 10-20', 'egr'); ?></p>
                    <p><strong><?php _e('Default:', 'egr'); ?></strong> 10</p>
                </div>

                <div class="egr-setting-item">
                    <h4><?php _e('Default Fields to Display', 'egr'); ?></h4>
                    <p><?php _e('Which review information to show by default in shortcodes:', 'egr'); ?></p>
                    <ul>
                        <li><strong><?php _e('Author Name:', 'egr'); ?></strong> <?php _e('The reviewer\'s display name', 'egr'); ?></li>
                        <li><strong><?php _e('Review Text:', 'egr'); ?></strong> <?php _e('The review content/comment', 'egr'); ?></li>
                        <li><strong><?php _e('Star Rating:', 'egr'); ?></strong> <?php _e('Visual star rating (1-5 stars)', 'egr'); ?></li>
                        <li><strong><?php _e('Review Date:', 'egr'); ?></strong> <?php _e('When the review was posted', 'egr'); ?></li>
                        <li><strong><?php _e('Business Reply:', 'egr'); ?></strong> <?php _e('Your response to the review (if any)', 'egr'); ?></li>
                    </ul>
                </div>

                <div class="egr-setting-item">
                    <h4><?php _e('Cache Timeout', 'egr'); ?></h4>
                    <p><?php _e('How long to store review data before fetching fresh data from Google:', 'egr'); ?></p>
                    <ul>
                        <li><strong>1-2 hours:</strong> <?php _e('For frequently updated businesses', 'egr'); ?></li>
                        <li><strong>6 hours:</strong> <?php _e('Recommended for most businesses (default)', 'egr'); ?></li>
                        <li><strong>24 hours:</strong> <?php _e('For businesses with infrequent reviews', 'egr'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="egr-setting-group">
                <h3><?php _e('Admin Actions', 'egr'); ?></h3>

                <div class="egr-setting-item">
                    <h4><?php _e('Clear Cache', 'egr'); ?></h4>
                    <p><?php _e('Removes all cached review data, forcing a fresh fetch on next display.', 'egr'); ?></p>
                </div>

                <div class="egr-setting-item">
                    <h4><?php _e('Test Connection', 'egr'); ?></h4>
                    <p><?php _e('Verifies your Google API connection and shows available business locations.', 'egr'); ?></p>
                </div>

                <div class="egr-setting-item">
                    <h4><?php _e('Sync Reviews Now', 'egr'); ?></h4>
                    <p><?php _e('Manually triggers a review sync instead of waiting for the scheduled sync.', 'egr'); ?></p>
                </div>

                <div class="egr-setting-item">
                    <h4><?php _e('Disconnect Google Account', 'egr'); ?></h4>
                    <p><?php _e('Removes all authentication tokens and disconnects from Google. You\'ll need to reconnect to use the plugin.', 'egr'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_shortcodes_tab(): void
    {
        ?>
        <div class="egr-instructions__content">
            <h2><?php _e('Shortcode Documentation', 'egr'); ?></h2>

            <div class="egr-shortcode-section">
                <h3><?php _e('Reviews Grid Shortcode', 'egr'); ?></h3>
                <div class="egr-shortcode-basic">
                    <h4><?php _e('Basic Usage', 'egr'); ?></h4>
                    <div class="egr-code-block">
                        <code>[egr_reviews]</code>
                        <button type="button" class="egr-copy-btn" data-copy="[egr_reviews]"><?php _e('Copy', 'egr'); ?></button>
                    </div>
                    <p><?php _e('Displays 5 reviews using your default settings.', 'egr'); ?></p>
                </div>

                <div class="egr-shortcode-attributes">
                    <h4><?php _e('Available Attributes', 'egr'); ?></h4>

                    <div class="egr-attribute">
                        <h5><code>count</code></h5>
                        <p><?php _e('Number of reviews to display (ignored if show_all="true")', 'egr'); ?></p>
                        <div class="egr-code-block">
                            <code>[egr_reviews count="10"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews count="10"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-attribute">
                        <h5><code>show_all</code></h5>
                        <p><?php _e('Show all reviews with pagination (true/false)', 'egr'); ?></p>
                        <div class="egr-code-block">
                            <code>[egr_reviews show_all="true"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews show_all="true"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-attribute">
                        <h5><code>rows_per_page</code></h5>
                        <p><?php _e('Reviews per page when using pagination', 'egr'); ?></p>
                        <div class="egr-code-block">
                            <code>[egr_reviews show_all="true" rows_per_page="20"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews show_all="true" rows_per_page="20"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-attribute">
                        <h5><code>fields</code></h5>
                        <p><?php _e('Comma-separated list of fields to display:', 'egr'); ?></p>
                        <ul>
                            <li><code>author_name</code> - <?php _e('Reviewer name', 'egr'); ?></li>
                            <li><code>text</code> - <?php _e('Review content', 'egr'); ?></li>
                            <li><code>rating</code> - <?php _e('Star rating', 'egr'); ?></li>
                            <li><code>time</code> - <?php _e('Review date', 'egr'); ?></li>
                            <li><code>reply</code> - <?php _e('Business reply', 'egr'); ?></li>
                        </ul>
                        <div class="egr-code-block">
                            <code>[egr_reviews fields="author_name,rating,text"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews fields="author_name,rating,text"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-attribute">
                        <h5><code>rating_filter</code></h5>
                        <p><?php _e('Show only reviews with specific star rating (1-5)', 'egr'); ?></p>
                        <div class="egr-code-block">
                            <code>[egr_reviews rating_filter="5"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews rating_filter="5"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-attribute">
                        <h5><code>order</code></h5>
                        <p><?php _e('Sort order: "desc" (newest first) or "asc" (oldest first)', 'egr'); ?></p>
                        <div class="egr-code-block">
                            <code>[egr_reviews order="asc"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews order="asc"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-attribute">
                        <h5><code>class</code></h5>
                        <p><?php _e('Custom CSS class for styling', 'egr'); ?></p>
                        <div class="egr-code-block">
                            <code>[egr_reviews class="my-custom-reviews"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews class="my-custom-reviews"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="egr-shortcode-examples">
                    <h4><?php _e('Common Examples', 'egr'); ?></h4>

                    <div class="egr-example">
                        <h5><?php _e('Show only 5-star reviews', 'egr'); ?></h5>
                        <div class="egr-code-block">
                            <code>[egr_reviews rating_filter="5" count="10"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews rating_filter="5" count="10"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-example">
                        <h5><?php _e('All reviews with pagination', 'egr'); ?></h5>
                        <div class="egr-code-block">
                            <code>[egr_reviews show_all="true" rows_per_page="15"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews show_all="true" rows_per_page="15"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-example">
                        <h5><?php _e('Simple review display', 'egr'); ?></h5>
                        <div class="egr-code-block">
                            <code>[egr_reviews fields="author_name,rating" count="8"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_reviews fields="author_name,rating" count="8"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="egr-shortcode-section">
                <h3><?php _e('Five Star Count Shortcode', 'egr'); ?></h3>
                <div class="egr-shortcode-basic">
                    <h4><?php _e('Basic Usage', 'egr'); ?></h4>
                    <div class="egr-code-block">
                        <code>[egr_5star_count]</code>
                        <button type="button" class="egr-copy-btn" data-copy="[egr_5star_count]"><?php _e('Copy', 'egr'); ?></button>
                    </div>
                    <p><?php _e('Displays the number of 5-star reviews as a simple number.', 'egr'); ?></p>
                </div>

                <div class="egr-shortcode-attributes">
                    <h4><?php _e('Available Attributes', 'egr'); ?></h4>

                    <div class="egr-attribute">
                        <h5><code>format</code></h5>
                        <p><?php _e('Output format:', 'egr'); ?></p>
                        <ul>
                            <li><code>number</code> - <?php _e('Just the number (e.g., "47")', 'egr'); ?></li>
                            <li><code>formatted</code> - <?php _e('Number with commas (e.g., "1,247")', 'egr'); ?></li>
                            <li><code>text</code> - <?php _e('Full text (e.g., "47 five-star reviews")', 'egr'); ?></li>
                        </ul>
                        <div class="egr-code-block">
                            <code>[egr_5star_count format="text"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_5star_count format="text"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-attribute">
                        <h5><code>prefix</code></h5>
                        <p><?php _e('Text to display before the count', 'egr'); ?></p>
                        <div class="egr-code-block">
                            <code>[egr_5star_count prefix="We have "]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_5star_count prefix="We have "]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-attribute">
                        <h5><code>suffix</code></h5>
                        <p><?php _e('Text to display after the count', 'egr'); ?></p>
                        <div class="egr-code-block">
                            <code>[egr_5star_count suffix=" happy customers!"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_5star_count suffix=" happy customers!"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-attribute">
                        <h5><code>class</code></h5>
                        <p><?php _e('Custom CSS class for styling', 'egr'); ?></p>
                        <div class="egr-code-block">
                            <code>[egr_5star_count class="highlight-number"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_5star_count class="highlight-number"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="egr-shortcode-examples">
                    <h4><?php _e('Examples', 'egr'); ?></h4>

                    <div class="egr-example">
                        <h5><?php _e('Marketing message', 'egr'); ?></h5>
                        <div class="egr-code-block">
                            <code>[egr_5star_count prefix="Join our " suffix=" satisfied customers!"]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_5star_count prefix="Join our " suffix=" satisfied customers!"]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                        <p><?php _e('Output: "Join our 47 satisfied customers!"', 'egr'); ?></p>
                    </div>

                    <div class="egr-example">
                        <h5><?php _e('Formatted with text', 'egr'); ?></h5>
                        <div class="egr-code-block">
                            <code>[egr_5star_count format="formatted" prefix="Over "]</code>
                            <button type="button" class="egr-copy-btn" data-copy='[egr_5star_count format="formatted" prefix="Over "]'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                        <p><?php _e('Output: "Over 1,247" (for large numbers)', 'egr'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_rest_endpoints_tab(): void
    {
        $site_url = get_site_url();
        ?>
        <div class="egr-instructions__content">
            <h2><?php _e('REST Endpoints', 'egr'); ?></h2>
            <p><?php _e('The plugin provides REST API endpoints for frontend integration. All endpoints require same-origin requests for security.', 'egr'); ?></p>

            <div class="egr-note">
                <strong><?php _e('Security:', 'egr'); ?></strong>
                <?php _e('These endpoints only accept requests from the same domain to prevent unauthorized access. Perfect for frontend JavaScript integration.', 'egr'); ?>
            </div>

            <div class="egr-setting-group">
                <h3><?php _e('Base URL', 'egr'); ?></h3>
                <div class="egr-setting-item">
                    <div class="egr-code-block">
                        <code><?php echo esc_html($site_url); ?>/wp-json/egr/v1/</code>
                        <button type="button" class="egr-copy-btn" data-copy="<?php echo esc_attr($site_url); ?>/wp-json/egr/v1/"><?php _e('Copy', 'egr'); ?></button>
                    </div>
                </div>
            </div>

            <!-- 5-Star Count Endpoint -->
            <div class="egr-shortcode-section">
                <h3><?php _e('GET /5star-count', 'egr'); ?></h3>

                <div class="egr-shortcode-basic">
                    <h4><?php _e('Description', 'egr'); ?></h4>
                    <p><?php _e('Returns the total count of 5-star reviews.', 'egr'); ?></p>
                </div>

                <div class="egr-shortcode-examples">
                    <h4><?php _e('Request URL', 'egr'); ?></h4>
                    <div class="egr-example">
                        <div class="egr-code-block">
                            <code><?php echo esc_html($site_url); ?>/wp-json/egr/v1/5star-count</code>
                            <button type="button" class="egr-copy-btn" data-copy="<?php echo esc_attr($site_url); ?>/wp-json/egr/v1/5star-count"><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <h4><?php _e('Response Example', 'egr'); ?></h4>
                    <div class="egr-example">
                        <div class="egr-code-block">
                            <code>{
  "success": true,
  "data": {
    "five_star_count": 42
  }
}</code>
                            <button type="button" class="egr-copy-btn" data-copy='{"success": true, "data": {"five_star_count": 42}}'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews Endpoint -->
            <div class="egr-shortcode-section">
                <h3><?php _e('GET /reviews', 'egr'); ?></h3>

                <div class="egr-shortcode-basic">
                    <h4><?php _e('Description', 'egr'); ?></h4>
                    <p><?php _e('Returns paginated reviews with complete pagination metadata.', 'egr'); ?></p>
                </div>

                <div class="egr-shortcode-attributes">
                    <h4><?php _e('Parameters', 'egr'); ?></h4>

                    <div class="egr-attribute">
                        <h5>page_number</h5>
                        <p><strong><?php _e('Type:', 'egr'); ?></strong> integer</p>
                        <p><strong><?php _e('Default:', 'egr'); ?></strong> 1</p>
                        <p><strong><?php _e('Description:', 'egr'); ?></strong> <?php _e('The page number to retrieve (1-based).', 'egr'); ?></p>
                    </div>

                    <div class="egr-attribute">
                        <h5>row_count</h5>
                        <p><strong><?php _e('Type:', 'egr'); ?></strong> integer</p>
                        <p><strong><?php _e('Default:', 'egr'); ?></strong> 10</p>
                        <p><strong><?php _e('Max:', 'egr'); ?></strong> 100</p>
                        <p><strong><?php _e('Description:', 'egr'); ?></strong> <?php _e('Number of reviews per page.', 'egr'); ?></p>
                    </div>
                </div>

                <div class="egr-shortcode-examples">
                    <h4><?php _e('Request Examples', 'egr'); ?></h4>

                    <div class="egr-example">
                        <h5><?php _e('Basic request', 'egr'); ?></h5>
                        <div class="egr-code-block">
                            <code><?php echo esc_html($site_url); ?>/wp-json/egr/v1/reviews</code>
                            <button type="button" class="egr-copy-btn" data-copy="<?php echo esc_attr($site_url); ?>/wp-json/egr/v1/reviews"><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-example">
                        <h5><?php _e('With pagination', 'egr'); ?></h5>
                        <div class="egr-code-block">
                            <code><?php echo esc_html($site_url); ?>/wp-json/egr/v1/reviews?page_number=2&row_count=5</code>
                            <button type="button" class="egr-copy-btn" data-copy="<?php echo esc_attr($site_url); ?>/wp-json/egr/v1/reviews?page_number=2&row_count=5"><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <h4><?php _e('Response Example', 'egr'); ?></h4>
                    <div class="egr-example">
                        <div class="egr-code-block">
                            <code>{
  "success": true,
  "data": {
    "reviews": [
      {
        "name": "John Doe",
        "rating": 5,
        "text": "Great service!",
        "date": {
          "timestamp": 1234567890,
          "formatted": "January 1, 2024",
          "relative": "2 days ago"
        },
        "reply": {
          "text": "Thank you!",
          "date": {
            "timestamp": 1234567890,
            "formatted": "January 2, 2024",
            "relative": "1 day ago"
          }
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total_items": 50,
      "total_pages": 5,
      "has_next_page": true,
      "has_prev_page": false
    }
  }
}</code>
                            <button type="button" class="egr-copy-btn" data-copy='{"success": true, "data": {"reviews": [{"name": "John Doe", "rating": 5, "text": "Great service!", "date": {"timestamp": 1234567890, "formatted": "January 1, 2024", "relative": "2 days ago"}, "reply": {"text": "Thank you!", "date": {"timestamp": 1234567890, "formatted": "January 2, 2024", "relative": "1 day ago"}}}], "pagination": {"current_page": 1, "per_page": 10, "total_items": 50, "total_pages": 5, "has_next_page": true, "has_prev_page": false}}}'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Endpoint -->
            <div class="egr-shortcode-section">
                <h3><?php _e('GET /stats', 'egr'); ?></h3>

                <div class="egr-shortcode-basic">
                    <h4><?php _e('Description', 'egr'); ?></h4>
                    <p><?php _e('Returns connection status, sync information, and review totals.', 'egr'); ?></p>
                </div>

                <div class="egr-shortcode-examples">
                    <h4><?php _e('Request URL', 'egr'); ?></h4>
                    <div class="egr-example">
                        <div class="egr-code-block">
                            <code><?php echo esc_html($site_url); ?>/wp-json/egr/v1/stats</code>
                            <button type="button" class="egr-copy-btn" data-copy="<?php echo esc_attr($site_url); ?>/wp-json/egr/v1/stats"><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <h4><?php _e('Response Example', 'egr'); ?></h4>
                    <div class="egr-example">
                        <div class="egr-code-block">
                            <code>{
  "success": true,
  "data": {
    "connection_status": "connected",
    "last_sync": {
      "timestamp": 1234567890,
      "formatted": "January 1, 2024 10:30 AM",
      "relative": "2 hours ago"
    },
    "totals": {
      "total_reviews": 150,
      "five_star_count": 42
    },
    "has_error": false,
    "error_message": null
  }
}</code>
                            <button type="button" class="egr-copy-btn" data-copy='{"success": true, "data": {"connection_status": "connected", "last_sync": {"timestamp": 1234567890, "formatted": "January 1, 2024 10:30 AM", "relative": "2 hours ago"}, "totals": {"total_reviews": 150, "five_star_count": 42}, "has_error": false, "error_message": null}}'><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- JavaScript Example -->
            <div class="egr-shortcode-section">
                <h3><?php _e('JavaScript Integration Example', 'egr'); ?></h3>

                <div class="egr-shortcode-basic">
                    <h4><?php _e('Fetch API Example', 'egr'); ?></h4>
                    <p><?php _e('Here\'s how to use these endpoints with JavaScript:', 'egr'); ?></p>
                </div>

                <div class="egr-shortcode-examples">
                    <div class="egr-example">
                        <h5><?php _e('Fetch 5-star count', 'egr'); ?></h5>
                        <div class="egr-code-block">
                            <code>fetch('/wp-json/egr/v1/5star-count')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('5-star count:', data.data.five_star_count);
    }
  });</code>
                            <button type="button" class="egr-copy-btn" data-copy="fetch('/wp-json/egr/v1/5star-count').then(response => response.json()).then(data => { if (data.success) { console.log('5-star count:', data.data.five_star_count); } });"><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>

                    <div class="egr-example">
                        <h5><?php _e('Fetch reviews with pagination', 'egr'); ?></h5>
                        <div class="egr-code-block">
                            <code>fetch('/wp-json/egr/v1/reviews?page_number=1&row_count=5')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      data.data.reviews.forEach(review => {
        console.log(`${review.name}: ${review.rating} stars`);
      });
    }
  });</code>
                            <button type="button" class="egr-copy-btn" data-copy="fetch('/wp-json/egr/v1/reviews?page_number=1&row_count=5').then(response => response.json()).then(data => { if (data.success) { data.data.reviews.forEach(review => { console.log(`${review.name}: ${review.rating} stars`); }); } });"><?php _e('Copy', 'egr'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="egr-troubleshooting">
                <h3><?php _e('Troubleshooting', 'egr'); ?></h3>

                <div class="egr-trouble-item">
                    <h4><?php _e('403 Forbidden Error', 'egr'); ?></h4>
                    <p><?php _e('Make sure you\'re making requests from the same domain. Cross-origin requests are blocked for security.', 'egr'); ?></p>
                </div>

                <div class="egr-trouble-item">
                    <h4><?php _e('Empty Reviews Response', 'egr'); ?></h4>
                    <p><?php _e('Ensure you have configured Google API credentials and successfully synced reviews in the Settings page.', 'egr'); ?></p>
                </div>

                <div class="egr-trouble-item">
                    <h4><?php _e('Connection Status "not_connected"', 'egr'); ?></h4>
                    <p><?php _e('Check that your Google API token is valid and hasn\'t expired. Try refreshing the connection in Settings.', 'egr'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}