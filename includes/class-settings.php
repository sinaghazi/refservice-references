<?php
/**
 * Settings class for RefService References plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RefService_Settings
{
    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Enqueue admin scripts on the plugin settings page only
     */
    public function enqueue_admin_assets($hook_suffix)
    {
        if ('settings_page_refservice-references' !== $hook_suffix) {
            return;
        }

        wp_enqueue_script(
            'refservice-admin',
            REFSERVICE_PLUGIN_URL . 'assets/js/admin.js',
            array(),
            REFSERVICE_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_options_page(
            __('Referenssipalvelu References Settings', 'refservice-references'),
            __('Referenssipalvelu References', 'refservice-references'),
            'manage_options',
            'refservice-references',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('refservice_settings', 'refservice_api_key', array(
            'sanitize_callback' => array($this, 'sanitize_api_key'),
        ));
        register_setting('refservice_settings', 'refservice_api_endpoint', array(
            'sanitize_callback' => 'esc_url_raw',
        ));
        register_setting('refservice_settings', 'refservice_cache_duration', array(
            'sanitize_callback' => array($this, 'sanitize_cache_duration'),
        ));
        register_setting('refservice_settings', 'refservice_default_limit', array(
            'sanitize_callback' => array($this, 'sanitize_optional_int'),
        ));
        register_setting('refservice_settings', 'refservice_default_layout', array(
            'sanitize_callback' => array($this, 'sanitize_layout'),
        ));
        register_setting('refservice_settings', 'refservice_display_mode', array(
            'sanitize_callback' => array($this, 'sanitize_display_mode'),
        ));
        register_setting('refservice_settings', 'refservice_custom_css', array(
            'sanitize_callback' => array($this, 'sanitize_custom_css'),
        ));
        register_setting('refservice_settings', 'refservice_detail_url_pattern', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));

        add_settings_section(
            'refservice_main_section',
            __('API Configuration', 'refservice-references'),
            array($this, 'render_section_description'),
            'refservice_settings'
        );

        add_settings_field(
            'refservice_api_key',
            __('API Key', 'refservice-references'),
            array($this, 'render_api_key_field'),
            'refservice_settings',
            'refservice_main_section'
        );

        add_settings_field(
            'refservice_api_endpoint',
            __('API Endpoint URL', 'refservice-references'),
            array($this, 'render_api_endpoint_field'),
            'refservice_settings',
            'refservice_main_section'
        );

        add_settings_field(
            'refservice_cache_duration',
            __('Cache Duration (minutes)', 'refservice-references'),
            array($this, 'render_cache_duration_field'),
            'refservice_settings',
            'refservice_main_section'
        );

        add_settings_field(
            'refservice_default_limit',
            __('Default References Limit', 'refservice-references'),
            array($this, 'render_default_limit_field'),
            'refservice_settings',
            'refservice_main_section'
        );

        add_settings_field(
            'refservice_default_layout',
            __('Default Layout', 'refservice-references'),
            array($this, 'render_default_layout_field'),
            'refservice_settings',
            'refservice_main_section'
        );

        // Display Settings Section
        add_settings_section(
            'refservice_display_section',
            __('Display Settings', 'refservice-references'),
            array($this, 'render_display_section_description'),
            'refservice_settings'
        );

        add_settings_field(
            'refservice_display_mode',
            __('Display Mode', 'refservice-references'),
            array($this, 'render_display_mode_field'),
            'refservice_settings',
            'refservice_display_section'
        );

        add_settings_field(
            'refservice_custom_css',
            __('Custom CSS', 'refservice-references'),
            array($this, 'render_custom_css_field'),
            'refservice_settings',
            'refservice_display_section'
        );

        add_settings_field(
            'refservice_detail_url_pattern',
            __('Reference Detail URL Pattern', 'refservice-references'),
            array($this, 'render_detail_url_pattern_field'),
            'refservice_settings',
            'refservice_display_section'
        );
    }

    /**
     * Sanitize the API key. An empty submission keeps the existing stored
     * key (the field is rendered blank so the real key is never echoed
     * into the page source).
     */
    public function sanitize_api_key($value)
    {
        $value = trim((string) $value);
        if ('' === $value) {
            // Explicit removal via the checkbox rendered next to the field.
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- options.php verifies the settings nonce before sanitize callbacks run.
            if (!empty($_POST['refservice_api_key_remove'])) {
                return '';
            }
            return get_option('refservice_api_key', '');
        }
        return sanitize_text_field($value);
    }

    /**
     * Sanitize the cache duration. Clamped to a minimum of 1 minute so a
     * bad value can never become 0 (set_transient with 0 = never expires).
     */
    public function sanitize_cache_duration($value)
    {
        return max(1, absint($value));
    }

    /**
     * Sanitize an optional integer field (empty string allowed = "no limit").
     */
    public function sanitize_optional_int($value)
    {
        return ('' === $value || null === $value) ? '' : absint($value);
    }

    /**
     * Sanitize the layout option to a known value.
     */
    public function sanitize_layout($value)
    {
        $allowed = array('grid', 'list', 'carousel');
        return in_array($value, $allowed, true) ? $value : 'grid';
    }

    /**
     * Sanitize the display mode option to a known value.
     */
    public function sanitize_display_mode($value)
    {
        $allowed = array('themed', 'no-themed');
        return in_array($value, $allowed, true) ? $value : 'themed';
    }

    /**
     * Sanitize the custom CSS textarea. Strips any tags so a stray
     * "</style><script>" cannot break out into executable markup.
     */
    public function sanitize_custom_css($value)
    {
        return wp_strip_all_tags((string) $value);
    }

    /**
     * Render section description
     */
    public function render_section_description()
    {
        echo '<p>' . esc_html__('Configure your Referenssipalvelu API connection below.', 'refservice-references') . '</p>';
    }

    /**
     * Render API key field
     */
    public function render_api_key_field()
    {
        $stored = get_option('refservice_api_key', '');
        $placeholder = ('' !== $stored)
            ? __('Leave empty to keep the current key', 'refservice-references')
            : __('Enter your API key', 'refservice-references');
        ?>
        <input type="password"
               name="refservice_api_key"
               value=""
               class="regular-text"
               autocomplete="off"
               placeholder="<?php echo esc_attr($placeholder); ?>">
        <?php if ('' !== $stored) : ?>
            <p class="description">
                <?php
                printf(
                    /* translators: %s: masked API key showing only the last four characters. */
                    esc_html__('A key is configured. Current key: %s. Leave the field empty to keep the existing key.', 'refservice-references'),
                    '<code>' . esc_html('····' . substr($stored, -4)) . '</code>'
                );
                ?>
            </p>
            <label style="display:inline-block; margin-top:4px;">
                <input type="checkbox" name="refservice_api_key_remove" value="1">
                <?php esc_html_e('Remove the stored API key on save (disconnects this site from the API)', 'refservice-references'); ?>
            </label>
        <?php endif; ?>
        <p class="description">
            <?php esc_html_e('Based on your package, you can request activation of API Service and obtain an API key from your Referenssipalvelu account manager.', 'refservice-references'); ?>
        </p>
        <?php
    }

    /**
     * Render API endpoint field
     *
     * The endpoint is pre-filled with the hosted Referenssipalvelu API and
     * locked by default, since almost all sites use the hosted service. An
     * "Edit" link unlocks it for connecting to a different or local install.
     */
    public function render_api_endpoint_field()
    {
        $value = get_option('refservice_api_endpoint', 'https://referenssipalvelu.fi/api/v1');
        ?>
        <input type="url"
               id="refservice_api_endpoint"
               name="refservice_api_endpoint"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text code"
               readonly>
        <a href="#" id="refservice_api_endpoint_edit" class="button-link" style="margin-left:8px;">
            <?php esc_html_e('Edit', 'refservice-references'); ?>
        </a>
        <p class="description">
            <?php esc_html_e('The hosted Referenssipalvelu API endpoint. This is pre-configured and normally should not be changed. Click "Edit" only if you are connecting to a different or local installation.', 'refservice-references'); ?>
        </p>
        <?php
    }

    /**
     * Render cache duration field
     */
    public function render_cache_duration_field()
    {
        $value = get_option('refservice_cache_duration', 30);
        ?>
        <input type="number" 
               name="refservice_cache_duration" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="1440" 
               class="small-text">
        <p class="description">
            <?php esc_html_e('How long to cache API responses in minutes. Recommended: 15-30 minutes.', 'refservice-references'); ?>
        </p>
        <?php
    }

    /**
     * Render default limit field
     */
    public function render_default_limit_field()
    {
        $value = get_option('refservice_default_limit', '');
        ?>
        <input type="number" 
               name="refservice_default_limit" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               class="small-text">
        <p class="description">
            <?php esc_html_e('Default number of references to display. Leave empty for all references.', 'refservice-references'); ?>
        </p>
        <?php
    }

    /**
     * Render default layout field
     */
    public function render_default_layout_field()
    {
        $value = get_option('refservice_default_layout', 'grid');
        ?>
        <select name="refservice_default_layout">
            <option value="grid" <?php selected($value, 'grid'); ?>><?php esc_html_e('Grid', 'refservice-references'); ?></option>
            <option value="list" <?php selected($value, 'list'); ?>><?php esc_html_e('List', 'refservice-references'); ?></option>
            <option value="carousel" <?php selected($value, 'carousel'); ?>><?php esc_html_e('Carousel', 'refservice-references'); ?></option>
        </select>
        <p class="description">
            <?php esc_html_e('Default layout for displaying references. Can be overridden per shortcode/block.', 'refservice-references'); ?>
        </p>
        <?php
    }

    /**
     * Render display section description
     */
    public function render_display_section_description()
    {
        echo '<p>' . esc_html__('Configure how references are displayed on your site.', 'refservice-references') . '</p>';
    }

    /**
     * Render display mode field
     */
    public function render_display_mode_field()
    {
        $value = get_option('refservice_display_mode', 'themed');
        ?>
        <select name="refservice_display_mode">
            <option value="themed" <?php selected($value, 'themed'); ?>><?php esc_html_e('Themed (Plugin Styles)', 'refservice-references'); ?></option>
            <option value="no-themed" <?php selected($value, 'no-themed'); ?>><?php esc_html_e('No-Themed (Theme Controls)', 'refservice-references'); ?></option>
        </select>
        <p class="description">
            <?php esc_html_e('Themed: loads the full plugin stylesheet (cards, shadows, colors, hover effects) — works out of the box on any theme.', 'refservice-references'); ?>
            <br>
            <?php esc_html_e('No-Themed: loads only minimal structural CSS (grid, image ratios, carousel mechanics) so your theme fully controls the visual styling.', 'refservice-references'); ?>
            <br>
            <?php esc_html_e('Tip: both modes expose the same CSS variables and class names, so the Custom CSS below works in either mode.', 'refservice-references'); ?>
        </p>
        <?php
    }

    /**
     * Render custom CSS field
     */
    public function render_custom_css_field()
    {
        $value = get_option('refservice_custom_css', '');

        // Ready-made snippets a user can click to append to the textarea.
        // Selectors/variables match assets/css/style.css and style-minimal.css.
        $presets = array(
            'accent' => array(
                'label' => __('Brand accent color', 'refservice-references'),
                'css'   => ".refservice-references,\n.refservice-carousel-wrapper {\n    --refservice-color: #0a7c3a;      /* links, category, arrows */\n    --refservice-color-text: #ffffff; /* text on accent background */\n}",
            ),
            'rounded' => array(
                'label' => __('Rounded cards + soft shadow', 'refservice-references'),
                'css'   => ".refservice-references {\n    --refservice-border-radius: 16px;\n    --refservice-shadow: 0 8px 24px rgba(0, 0, 0, 0.10);\n    --refservice-shadow-hover: 0 12px 32px rgba(0, 0, 0, 0.16);\n}",
            ),
            'compact' => array(
                'label' => __('Compact grid spacing', 'refservice-references'),
                'css'   => ".refservice-references {\n    --refservice-spacing: 1.25rem;\n}\n.refservice-layout-grid {\n    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));\n}",
            ),
        );
        ?>
        <p style="margin-bottom:6px;">
            <?php esc_html_e('Quick presets (click to add to the CSS box):', 'refservice-references'); ?>
            <?php foreach ($presets as $key => $preset) : ?>
                <button type="button"
                        class="button button-small refservice-css-preset"
                        data-css="<?php echo esc_attr($preset['css']); ?>"
                        style="margin:2px;">
                    <?php echo esc_html($preset['label']); ?>
                </button>
            <?php endforeach; ?>
        </p>
        <textarea name="refservice_custom_css"
                  id="refservice_custom_css"
                  rows="12"
                  cols="50"
                  class="large-text code"
                  placeholder="<?php esc_attr_e('Add custom CSS to override plugin styles...', 'refservice-references'); ?>"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php esc_html_e('Add custom CSS to override plugin styles. This CSS is loaded after the plugin styles, in both Themed and No-Themed modes.', 'refservice-references'); ?>
        </p>
        <?php
    }

    /**
     * Render detail URL pattern field
     */
    public function render_detail_url_pattern_field()
    {
        $value = get_option('refservice_detail_url_pattern', '');
        ?>
        <input type="text"
               name="refservice_detail_url_pattern"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text code"
               placeholder="https://referenssipalvelu.fi/{company}/reference/{id}">
        <p class="description">
            <?php esc_html_e('Controls where a reference card\'s "Read More" link points. Two placeholders are available:', 'refservice-references'); ?>
        </p>
        <ul class="description" style="list-style:disc; margin-left:1.5em;">
            <li><code>{id}</code> — <?php esc_html_e('the reference ID', 'refservice-references'); ?></li>
            <li><code>{company}</code> — <?php esc_html_e('your company subdomain (from the API)', 'refservice-references'); ?></li>
        </ul>
        <p class="description">
            <strong><?php esc_html_e('Choose one of these approaches:', 'refservice-references'); ?></strong>
        </p>
        <ol class="description" style="margin-left:1.5em;">
            <li>
                <strong><?php esc_html_e('Leave empty (default)', 'refservice-references'); ?></strong> —
                <?php esc_html_e('links use this site\'s built-in detail page:', 'refservice-references'); ?>
                <code><?php echo esc_html(home_url('/references/{id}/')); ?></code>.
                <?php esc_html_e('Requires "pretty" permalinks (Settings > Permalinks, anything other than "Plain").', 'refservice-references'); ?>
            </li>
            <li>
                <strong><?php esc_html_e('External Referenssipalvelu page', 'refservice-references'); ?></strong> —
                <?php esc_html_e('opens the reference on the hosted service in a new tab:', 'refservice-references'); ?>
                <br>
                <code>https://referenssipalvelu.fi/{company}/reference/{id}</code>
            </li>
            <li>
                <strong><?php esc_html_e('Another WordPress page', 'refservice-references'); ?></strong> —
                <?php esc_html_e('point to a page that contains the detail shortcode:', 'refservice-references'); ?>
                <br>
                <code>/reference-details/?ref={id}</code>
                <?php esc_html_e('(then place', 'refservice-references'); ?> <code>[refservice_detail id="10"]</code> <?php esc_html_e('on that page).', 'refservice-references'); ?>
            </li>
        </ol>
        <p class="description">
            <em><?php esc_html_e('Note: any pattern beginning with http/https opens in a new browser tab.', 'refservice-references'); ?></em>
        </p>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Clear cache if requested
        if (isset($_POST['clear_cache']) && check_admin_referer('refservice_clear_cache')) {
            RefService_Api_Client::clear_cache();
            echo '<div class="notice notice-success"><p>' . esc_html__('Cache cleared successfully.', 'refservice-references') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('refservice_settings');
                do_settings_sections('refservice_settings');
                submit_button(__('Save Settings', 'refservice-references'));
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Clear Cache', 'refservice-references'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('refservice_clear_cache'); ?>
                <p><?php esc_html_e('Clear all cached API responses.', 'refservice-references'); ?></p>
                <input type="submit" 
                       name="clear_cache" 
                       class="button" 
                       value="<?php esc_attr_e('Clear Cache', 'refservice-references'); ?>">
            </form>

            <hr>

            <h2><?php esc_html_e('Usage', 'refservice-references'); ?></h2>
            <p><?php esc_html_e('You can display references with the Gutenberg block ("Referenssipalvelu References") or with shortcodes on any page, post, or widget.', 'refservice-references'); ?></p>

            <h3><?php esc_html_e('Reference list', 'refservice-references'); ?></h3>
            <table class="widefat striped" style="max-width:820px;">
                <thead>
                    <tr>
                        <th style="width:45%;"><?php esc_html_e('Shortcode', 'refservice-references'); ?></th>
                        <th><?php esc_html_e('What it does', 'refservice-references'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[refservice]</code></td>
                        <td><?php esc_html_e('All references, using your default layout.', 'refservice-references'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[refservice limit="6" columns="3"]</code></td>
                        <td><?php esc_html_e('First 6 references in a 3-column grid.', 'refservice-references'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[refservice layout="list"]</code></td>
                        <td><?php esc_html_e('List layout (image beside text).', 'refservice-references'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[refservice layout="carousel" columns="3"]</code></td>
                        <td><?php esc_html_e('Sliding carousel showing 3 cards at a time.', 'refservice-references'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[refservice products="1,2" services="3"]</code></td>
                        <td><?php esc_html_e('Only references tagged with those product/service IDs.', 'refservice-references'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[refservice custom_category_1="4" custom_category_2="7"]</code></td>
                        <td><?php esc_html_e('Filter by your two custom category dimensions.', 'refservice-references'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[refservice language="fi"]</code></td>
                        <td><?php esc_html_e('Force Finnish (otherwise the language is auto-detected).', 'refservice-references'); ?></td>
                    </tr>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e('Attributes can be combined, e.g.', 'refservice-references'); ?> <code>[refservice layout="carousel" columns="3" products="1,2" limit="9" language="en"]</code></p>

            <h3><?php esc_html_e('Single reference detail', 'refservice-references'); ?></h3>
            <p><?php esc_html_e('Show one full reference (hero image, gallery, contact, delivery date) on its own page:', 'refservice-references'); ?></p>
            <code>[refservice_detail id="10"]</code>
            <p class="description"><?php esc_html_e('An alias', 'refservice-references'); ?> <code>[refservice_reference id="10"]</code> <?php esc_html_e('works too. You can also link cards to the built-in detail route', 'refservice-references'); ?> <code><?php echo esc_html(home_url('/references/10/')); ?></code> <?php esc_html_e('(see Reference Detail URL Pattern above).', 'refservice-references'); ?></p>

            <hr>

            <h2><?php esc_html_e('Language Support', 'refservice-references'); ?></h2>
            <?php
            $language_service = RefService_Language::get_instance();
            $company_languages = $language_service->get_company_languages();
            $wp_locale = $language_service->get_wordpress_locale();
            $detected_language = $language_service->detect_language();
            ?>
            <p><?php esc_html_e('The plugin automatically detects and uses the appropriate language based on:', 'refservice-references'); ?></p>
            <ul>
                <li><?php esc_html_e('WordPress site language', 'refservice-references'); ?>: <strong><?php echo esc_html($wp_locale); ?></strong></li>
                <li><?php esc_html_e('Company enabled languages', 'refservice-references'); ?>: 
                    <?php if (!empty($company_languages)) : ?>
                        <strong><?php echo esc_html(implode(', ', $company_languages)); ?></strong>
                    <?php else : ?>
                        <em><?php esc_html_e('Not available (check API connection)', 'refservice-references'); ?></em>
                    <?php endif; ?>
                </li>
                <li><?php esc_html_e('Currently detected language', 'refservice-references'); ?>: <strong><?php echo esc_html($detected_language); ?></strong></li>
            </ul>
            <p class="description">
                <?php esc_html_e('You can override the language by adding the language parameter to shortcodes: [refservice language="fi"]', 'refservice-references'); ?>
            </p>
        </div>
        <?php
    }
}

