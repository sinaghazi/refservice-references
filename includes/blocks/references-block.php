<?php
/**
 * Gutenberg Block for RefService References
 */

if (!defined('ABSPATH')) {
    exit;
}

class RefService_References_Block
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
     *
     * This class is instantiated during the 'init' hook (from the main
     * plugin's init_components()), so we register the block directly here.
     * Adding another 'init' callback at this point would never fire, because
     * WordPress does not execute callbacks added to the currently-running
     * priority of the currently-running hook.
     */
    private function __construct()
    {
        $this->register_block();
    }

    /**
     * Register block
     */
    public function register_block()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        // Register the editor assets referenced by block.json. block.json
        // takes care of enqueueing them in the block editor.
        //
        // Note: the API key/endpoint are intentionally NOT localized to the
        // editor script. The block renders server-side via ServerSideRender,
        // so no credentials are needed client-side. Exposing the key here would
        // leak it to anyone able to edit a post.
        wp_register_script(
            'refservice-references-block-editor',
            REFSERVICE_PLUGIN_URL . 'assets/js/block-editor.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-server-side-render', 'wp-data'),
            REFSERVICE_PLUGIN_VERSION,
            true
        );

        wp_register_style(
            'refservice-references-block-editor',
            REFSERVICE_PLUGIN_URL . 'assets/css/block-editor.css',
            array('wp-edit-blocks'),
            REFSERVICE_PLUGIN_VERSION
        );

        register_block_type(__DIR__ . '/block.json', array(
            'render_callback' => array($this, 'render_block'),
        ));
    }

    /**
     * Render block
     */
    public function render_block($attributes)
    {
        // Ensure we have valid defaults
        $default_layout = get_option('refservice_default_layout', 'grid') ?: 'grid';
        $default_columns = 3;

        // Convert block attributes to shortcode format. 'limit' has no
        // default in block.json; when unset, all references are shown.
        $atts = array(
            'limit' => isset($attributes['limit']) && $attributes['limit'] !== '' ? intval($attributes['limit']) : '',
            'layout' => !empty($attributes['layout']) ? $attributes['layout'] : $default_layout,
            'columns' => !empty($attributes['columns']) ? intval($attributes['columns']) : $default_columns,
            'products' => !empty($attributes['products']) ? $attributes['products'] : '',
            'services' => !empty($attributes['services']) ? $attributes['services'] : '',
            'custom_category_1' => !empty($attributes['customCategory1']) ? $attributes['customCategory1'] : '',
            'custom_category_2' => !empty($attributes['customCategory2']) ? $attributes['customCategory2'] : '',
            'language' => !empty($attributes['language']) ? $attributes['language'] : '',
        );

        // Use existing display class. render_references() always returns
        // markup (errors are rendered as markup too), so no empty check is
        // needed here.
        $display = RefService_Display::get_instance();

        return $display->render_references($atts);
    }
}
