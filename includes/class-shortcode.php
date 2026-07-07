<?php
/**
 * Shortcode class for RefService References plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RefService_Shortcode
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
        add_shortcode('refservice', array($this, 'render_shortcode'));
        add_shortcode('refservice_references', array($this, 'render_shortcode'));
        add_shortcode('refservice_detail', array($this, 'render_detail_shortcode'));
        add_shortcode('refservice_reference', array($this, 'render_detail_shortcode'));
    }

    /**
     * Render shortcode
     */
    public function render_shortcode($atts)
    {
        // Parse attributes
        $atts = shortcode_atts(array(
            'limit' => get_option('refservice_default_limit', ''),
            'layout' => get_option('refservice_default_layout', 'grid'),
            'products' => '',
            'services' => '',
            'custom_category_1' => '',
            'custom_category_2' => '',
            'columns' => '3',
            'language' => '',
        ), $atts, 'refservice');

        // Get display instance
        $display = RefService_Display::get_instance();

        // Render references
        return $display->render_references($atts);
    }

    /**
     * Render detail shortcode
     */
    public function render_detail_shortcode($atts)
    {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => '',
            'language' => '',
        ), $atts, 'refservice_detail');

        if (empty($atts['id'])) {
            return '<p class="refservice-error">' . esc_html__('Reference ID is required. Use: [refservice_detail id="1"]', 'refservice-references') . '</p>';
        }

        // Get detail instance
        $detail = RefService_Reference_Detail::get_instance();

        // Render detail
        return $detail->render_detail($atts['id'], $atts['language']);
    }
}

