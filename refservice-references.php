<?php
/**
 * Plugin Name: Referenssipalvelu References
 * Plugin URI: https://referenssipalvelu.fi
 * Description: Display your company references from Referenssipalvelu on your WordPress website. Lightweight plugin that integrates with your Referenssipalvelu account.
 * Version: 2.0.0
 * Author: Inmedia Systems Oy
 * Author URI: https://inmedia.fi
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: refservice-references
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('REFSERVICE_PLUGIN_VERSION', '2.0.0');
define('REFSERVICE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REFSERVICE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REFSERVICE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class RefService_References
{
    /**
     * Single instance of the class
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
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init()
    {
        // Load plugin text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Include required files
        $this->includes();

        // Initialize routing at load time (before `init` fires) so that its
        // rewrite-rule registration is a reliable top-level `init` hook.
        // Instantiating it later from inside init_components() (which itself
        // runs on `init`) means add_action('init', ...) is registered while
        // `init` is already executing, which WordPress does not fire reliably.
        // The symptom was that any live flush (e.g. visiting Settings >
        // Permalinks) dropped the plugin's rewrite rules, 404ing detail pages.
        RefService_Routing::get_instance();

        // Initialize remaining components
        add_action('init', array($this, 'init_components'));
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'refservice-references',
            false,
            dirname(REFSERVICE_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Include required files
     */
    private function includes()
    {
        require_once REFSERVICE_PLUGIN_DIR . 'includes/class-settings.php';
        require_once REFSERVICE_PLUGIN_DIR . 'includes/class-api-client.php';
        require_once REFSERVICE_PLUGIN_DIR . 'includes/class-language.php';
        require_once REFSERVICE_PLUGIN_DIR . 'includes/class-shortcode.php';
        require_once REFSERVICE_PLUGIN_DIR . 'includes/class-display.php';
        require_once REFSERVICE_PLUGIN_DIR . 'includes/class-reference-detail.php';
        require_once REFSERVICE_PLUGIN_DIR . 'includes/class-routing.php';
        
        // Load blocks if Gutenberg is available
        if (function_exists('register_block_type')) {
            require_once REFSERVICE_PLUGIN_DIR . 'includes/blocks/references-block.php';
        }
    }

    /**
     * Initialize plugin components
     */
    public function init_components()
    {
        // Initialize settings
        RefService_Settings::get_instance();

        // Initialize shortcode
        RefService_Shortcode::get_instance();

        // Initialize display
        RefService_Display::get_instance();

        // Initialize blocks
        if (function_exists('register_block_type')) {
            RefService_References_Block::get_instance();
        }
    }
}

/**
 * Initialize plugin
 */
function refservice_references_init()
{
    return RefService_References::get_instance();
}

// Start the plugin
refservice_references_init();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function () {
    // Set default options
    if (!get_option('refservice_api_endpoint')) {
        update_option('refservice_api_endpoint', 'https://referenssipalvelu.fi/api/v1');
    }
    if (!get_option('refservice_cache_duration')) {
        update_option('refservice_cache_duration', 30); // 30 minutes
    }
    if (!get_option('refservice_display_mode')) {
        update_option('refservice_display_mode', 'themed');
    }
    
    // Flush rewrite rules for pretty URLs
    RefService_Routing::flush_rewrite_rules();
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function () {
    // Clear all cached API responses (class is already loaded via includes())
    RefService_Api_Client::clear_cache();
});

