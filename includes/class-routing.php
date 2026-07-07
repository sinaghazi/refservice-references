<?php
/**
 * Routing class for RefService References plugin
 * Handles pretty URLs for reference detail pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class RefService_Routing
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
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_reference_requests'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }

    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules()
    {
        // Add rewrite rule for /references/{id} or /reference/{id}
        add_rewrite_rule(
            '^references/([0-9]+)/?$',
            'index.php?refservice_reference_id=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^reference/([0-9]+)/?$',
            'index.php?refservice_reference_id=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'refservice_reference_id';
        return $vars;
    }

    /**
     * Handle reference requests
     */
    public function handle_reference_requests()
    {
        $reference_id = get_query_var('refservice_reference_id');
        
        if (!empty($reference_id)) {
            // Get detail instance
            $detail = RefService_Reference_Detail::get_instance();

            // Render detail
            $content = $detail->render_detail($reference_id);

            // If the reference could not be resolved, respond with a proper
            // 404 status instead of a 200 "not found" page.
            if ($detail->is_not_found()) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
            } else {
                // Register SEO hooks before rendering so wp_head() picks
                // them up in both the classic and block-theme paths.
                $this->register_seo_hooks($detail, $reference_id);
            }

            // Create a simple page wrapper
            $this->render_reference_page($content, $reference_id);
            exit;
        }
    }

    /**
     * Register SEO hooks (document title and canonical URL) for a resolved reference
     */
    private function register_seo_hooks($detail, $reference_id)
    {
        // Document title: "{reference name} – {site name}"
        if (method_exists($detail, 'get_last_reference')) {
            $reference = $detail->get_last_reference();

            if (is_array($reference) && !empty($reference['name'])) {
                $reference_name = wp_strip_all_tags($reference['name']);

                add_filter('pre_get_document_title', function () use ($reference_name) {
                    return $reference_name . ' – ' . get_bloginfo('name');
                });
            }
        }

        // Canonical URL for the reference detail page. Mirror the link
        // building in RefService_Display: the pretty route only resolves
        // when a permalink structure is set; on "Plain" permalinks the
        // canonical must be the query-var URL or it points at a 404.
        if (get_option('permalink_structure')) {
            $canonical = home_url('/references/' . (int) $reference_id . '/');
        } else {
            $canonical = add_query_arg('refservice_reference_id', (int) $reference_id, home_url('/'));
        }

        add_action('wp_head', function () use ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
        });
    }

    /**
     * Render reference page
     */
    private function render_reference_page($content, $reference_id)
    {
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            $this->render_block_theme_page($content);
            return;
        }

        // Classic theme: use current theme's header and footer
        get_header();
        ?>
        <div class="refservice-reference-page-wrapper">
            <?php echo $content; ?>
        </div>
        <?php
        get_footer();
    }

    /**
     * Render reference page for block (FSE) themes
     *
     * get_header()/get_footer() on block themes only outputs legacy
     * theme-compat markup, so build a full block-theme document instead.
     */
    private function render_block_theme_page($content)
    {
        ?><!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>
        <?php
        if (function_exists('wp_body_open')) {
            wp_body_open();
        }

        if (function_exists('block_template_part')) {
            block_template_part('header');
        }
        ?>
        <main class="refservice-reference-page-wrapper">
            <?php echo $content; ?>
        </main>
        <?php
        if (function_exists('block_template_part')) {
            block_template_part('footer');
        }

        wp_footer();
        ?>
        </body>
        </html>
        <?php
    }

    /**
     * Flush rewrite rules (call on activation)
     */
    public static function flush_rewrite_rules()
    {
        $instance = self::get_instance();
        $instance->add_rewrite_rules();
        flush_rewrite_rules();
    }
}

