<?php
/**
 * Reference Detail class for RefService References plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RefService_Reference_Detail
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
     * Whether the last render_detail() call failed to resolve a reference
     * (missing/invalid ID, API error, or reference not found). Lets callers
     * such as the routing layer emit a proper 404 status.
     */
    private $not_found = false;

    /**
     * The reference array resolved by the most recent render_detail() call,
     * or null if none was resolved. Lets callers such as the routing layer
     * access the rendered reference data (e.g. for document title/meta).
     */
    private $last_reference = null;

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
    }

    /**
     * Render reference detail
     */
    public function render_detail($reference_id, $language = '')
    {
        // Reset for this render.
        $this->not_found = false;
        $this->last_reference = null;

        if (empty($reference_id)) {
            $this->not_found = true;
            return '<p class="refservice-error">' . esc_html__('Reference ID is required.', 'refservice-references') . '</p>';
        }

        // Build API params
        $api_params = array();
        
        // Language detection: use explicit language or auto-detect
        $language_service = RefService_Language::get_instance();
        $detected_language = $language_service->detect_language($language);
        $api_params['language'] = $detected_language;

        // Get company info
        $company = $this->api_client->get_company();
        if (is_wp_error($company)) {
            $this->not_found = true;
            return '<p class="refservice-error">' . esc_html($company->get_error_message()) . '</p>';
        }

        // Get reference details
        $response = $this->api_client->get_reference(intval($reference_id), $api_params);
        if (is_wp_error($response)) {
            $this->not_found = true;
            return '<p class="refservice-error">' . esc_html($response->get_error_message()) . '</p>';
        }

        $reference = isset($response['data']) ? $response['data'] : null;
        if (!$reference) {
            $this->not_found = true;
            return '<p class="refservice-error">' . esc_html__('Reference not found.', 'refservice-references') . '</p>';
        }

        $this->last_reference = $reference;

        // Start output buffering
        ob_start();

        // Render reference detail
        $this->render_detail_html($reference, $company);

        return ob_get_clean();
    }

    /**
     * Whether the most recent render_detail() call could not resolve a
     * reference. Callers can use this to send an HTTP 404 status.
     */
    public function is_not_found()
    {
        return $this->not_found;
    }

    /**
     * Get the reference array resolved by the most recent render_detail()
     * call, or null if no reference was resolved.
     *
     * @return array|null
     */
    public function get_last_reference()
    {
        return $this->last_reference;
    }

    /**
     * Render reference detail HTML
     */
    private function render_detail_html($reference, $company)
    {
        $display_mode = get_option('refservice_display_mode', 'themed');
        $company_color = isset($company['color']) ? esc_attr($company['color']) : '#000000';
        $company_color_text = isset($company['color_text']) ? esc_attr($company['color_text']) : '#ffffff';
        $wrapper_class = 'refservice-reference-detail refservice-' . esc_attr($display_mode);

        // Allow themes to override the template:
        // your-theme/refservice/reference-detail.php
        $template = locate_template('refservice/reference-detail.php');
        if (empty($template)) {
            $template = REFSERVICE_PLUGIN_DIR . 'templates/reference-detail.php';
        }

        include $template;
    }
}

