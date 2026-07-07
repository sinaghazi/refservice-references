<?php
/**
 * Display class for RefService References plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RefService_Display
{
    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * API client
     */
    private $api_client;

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
        $this->api_client = RefService_Api_Client::get_instance();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Register assets. Nothing is enqueued here: enqueue_styles() runs when
     * a reference list or detail is actually rendered, so pages without
     * plugin output load no plugin CSS. On the shortcode path that happens
     * during the_content, and WordPress then prints the styles in the footer.
     */
    public function enqueue_assets()
    {
        $display_mode = get_option('refservice_display_mode', 'themed');

        // Full CSS in themed mode, structural-only CSS in no-themed mode
        if ($display_mode === 'themed') {
            wp_register_style(
                'refservice-references-style',
                REFSERVICE_PLUGIN_URL . 'assets/css/style.css',
                array(),
                REFSERVICE_PLUGIN_VERSION
            );
        } else {
            wp_register_style(
                'refservice-references-minimal',
                REFSERVICE_PLUGIN_URL . 'assets/css/style-minimal.css',
                array(),
                REFSERVICE_PLUGIN_VERSION
            );
        }

        // Carousel JS (enqueued on demand when a carousel renders)
        wp_register_script(
            'refservice-carousel',
            REFSERVICE_PLUGIN_URL . 'assets/js/carousel.js',
            array(),
            REFSERVICE_PLUGIN_VERSION,
            true
        );

        // Attach custom CSS so it prints whenever the stylesheet does
        $custom_css = get_option('refservice_custom_css', '');
        if (!empty($custom_css)) {
            wp_add_inline_style(
                $display_mode === 'themed' ? 'refservice-references-style' : 'refservice-references-minimal',
                $custom_css
            );
        }
    }

    /**
     * Enqueue the stylesheet for the active display mode. Called at render
     * time by both the list and the detail views. Safe to call before the
     * wp_enqueue_scripts registration has run: the handle is queued and
     * resolved when styles are printed.
     */
    public function enqueue_styles()
    {
        $display_mode = get_option('refservice_display_mode', 'themed');
        wp_enqueue_style(
            $display_mode === 'themed' ? 'refservice-references-style' : 'refservice-references-minimal'
        );
    }

    /**
     * Render references
     */
    public function render_references($atts)
    {
        // Build API params
        $api_params = array();
        if (!empty($atts['limit'])) {
            $api_params['limit'] = intval($atts['limit']);
        }
        if (!empty($atts['products'])) {
            $api_params['products'] = preg_replace('/[^0-9,]/', '', $atts['products']);
        }
        if (!empty($atts['services'])) {
            $api_params['services'] = preg_replace('/[^0-9,]/', '', $atts['services']);
        }
        if (!empty($atts['custom_category_1'])) {
            $api_params['custom_category_1'] = preg_replace('/[^0-9,]/', '', $atts['custom_category_1']);
        }
        if (!empty($atts['custom_category_2'])) {
            $api_params['custom_category_2'] = preg_replace('/[^0-9,]/', '', $atts['custom_category_2']);
        }
        
        // Language detection: use explicit language or auto-detect
        $language_service = RefService_Language::get_instance();
        $detected_language = $language_service->detect_language(
            !empty($atts['language']) ? $atts['language'] : ''
        );
        $api_params['language'] = $detected_language;

        // Get company info
        $company = $this->api_client->get_company();
        if (is_wp_error($company)) {
            return '<p class="refservice-error">' . esc_html($company->get_error_message()) . '</p>';
        }

        // Get references
        $response = $this->api_client->get_references($api_params);
        if (is_wp_error($response)) {
            // Debug: Log error details
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('RefService API Error: ' . $response->get_error_message());
                error_log('RefService API Error Code: ' . $response->get_error_code());
            }
            return '<p class="refservice-error">' . esc_html($response->get_error_message()) . '</p>';
        }

        $references = isset($response['data']) ? $response['data'] : array();
        if (empty($references)) {
            return '<p class="refservice-empty">' . esc_html__('No references found.', 'refservice-references') . '</p>';
        }

        // Get layout
        $layout = sanitize_text_field($atts['layout']);
        $columns = intval($atts['columns']);

        // Start output buffering
        ob_start();

        // Render references
        $this->render_references_html($references, $company, $layout, $columns);

        return ob_get_clean();
    }

    /**
     * Render references HTML
     */
    private function render_references_html($references, $company, $layout, $columns)
    {
        $this->enqueue_styles();

        $display_mode = get_option('refservice_display_mode', 'themed');
        $company_color = !empty($company['color']) ? sanitize_hex_color($company['color']) : '';
        if (empty($company_color)) {
            $company_color = '#000000';
        }
        $company_color_text = !empty($company['color_text']) ? sanitize_hex_color($company['color_text']) : '';
        if (empty($company_color_text)) {
            $company_color_text = '#ffffff';
        }
        $is_carousel = ($layout === 'carousel');
        $wrapper_class = 'refservice-references refservice-layout-' . esc_attr($layout) . ' refservice-' . esc_attr($display_mode);
        $item_class = 'refservice-reference-item refservice-col-' . esc_attr($columns);

        if ($is_carousel) {
            wp_enqueue_script('refservice-carousel');
        }

        ?>
        <?php if ($is_carousel) : ?>
        <div class="refservice-carousel-wrapper refservice-<?php echo esc_attr($display_mode); ?>" style="--refservice-color: <?php echo esc_attr($company_color); ?>; --refservice-color-text: <?php echo esc_attr($company_color_text); ?>; --refservice-carousel-columns: <?php echo esc_attr($columns); ?>;">
            <div class="refservice-carousel-track" data-columns="<?php echo esc_attr($columns); ?>">
                <?php foreach ($references as $reference) : ?>
                    <div class="refservice-carousel-slide">
                        <div class="refservice-reference-card">
                            <?php $this->render_card_inner($reference, $company); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($references) > intval($columns)) : ?>
                <div class="refservice-carousel-nav">
                    <button class="refservice-carousel-btn refservice-carousel-prev" aria-label="<?php esc_attr_e('Previous', 'refservice-references'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button class="refservice-carousel-btn refservice-carousel-next" aria-label="<?php esc_attr_e('Next', 'refservice-references'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php else : ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>" style="--refservice-color: <?php echo esc_attr($company_color); ?>; --refservice-color-text: <?php echo esc_attr($company_color_text); ?>;">
            <?php foreach ($references as $reference) : ?>
                <div class="<?php echo esc_attr($item_class); ?>">
                    <div class="refservice-reference-card">
                        <?php $this->render_card_inner($reference, $company); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render the inner content of a reference card (shared between grid/list and carousel)
     */
    private function render_card_inner($reference, $company)
    {
        ?>
        <?php if (!empty($reference['image_main'])) : ?>
            <figure class="refservice-reference-image">
                <img src="<?php echo esc_url($reference['image_main']); ?>" 
                     alt="<?php echo esc_attr($reference['name'] ?? ''); ?>"
                     loading="lazy">
            </figure>
        <?php endif; ?>
        
        <div class="refservice-reference-content">
            <?php if (!empty($reference['product']['name']) || !empty($reference['service']['name'])) : ?>
                <span class="refservice-reference-category">
                    <?php echo esc_html(!empty($reference['product']['name']) ? $reference['product']['name'] : $reference['service']['name']); ?>
                </span>
            <?php endif; ?>
            
            <h3 class="refservice-reference-title">
                <?php echo esc_html($reference['name'] ?? ''); ?>
            </h3>
            
            <?php if (!empty($reference['description_short'])) : ?>
                <div class="refservice-reference-description">
                    <?php echo wp_kses_post($reference['description_short']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($reference['customer'])) : ?>
                <div class="refservice-reference-customer">
                    <?php if (!empty($reference['customer']['image'])) : ?>
                        <img src="<?php echo esc_url($reference['customer']['image']); ?>" 
                             alt="<?php echo esc_attr($reference['customer']['name']); ?>"
                             class="refservice-customer-logo">
                    <?php endif; ?>
                    <?php if (!empty($reference['customer']['name'])) : ?>
                        <span class="refservice-customer-name"><?php echo esc_html($reference['customer']['name']); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="refservice-reference-footer">
                <?php 
                $url_pattern = get_option('refservice_detail_url_pattern', '');
                $link_url = '';
                $link_target = '';
                $link_rel = '';
                
                if (!empty($url_pattern)) {
                    $company_for_url = $company;
                    $link_url = str_replace(
                        array('{id}', '{company}'),
                        array($reference['id'], $company_for_url['subdomain'] ?? ''),
                        $url_pattern
                    );
                    if (strpos($link_url, 'http') === 0) {
                        $link_target = '_blank';
                        $link_rel = 'noopener noreferrer';
                    }
                } else {
                    if (get_option('permalink_structure')) {
                        $link_url = home_url('/references/' . $reference['id'] . '/');
                    } else {
                        $link_url = add_query_arg('refservice_reference_id', (int) $reference['id'], home_url('/'));
                    }
                }
                ?>
                <?php if (!empty($link_url)) : ?>
                    <a href="<?php echo esc_url($link_url); ?>" 
                       class="refservice-reference-link"
                       <?php if ($link_target) : ?>target="<?php echo esc_attr($link_target); ?>"<?php endif; ?>
                       <?php if ($link_rel) : ?>rel="<?php echo esc_attr($link_rel); ?>"<?php endif; ?>>
                        <?php esc_html_e('Read More', 'refservice-references'); ?>
                        <span class="refservice-arrow">→</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

