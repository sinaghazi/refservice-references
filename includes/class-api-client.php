<?php
/**
 * API Client class for RefService References plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RefService_Api_Client
{
    /**
     * Option name for the registry of transient cache keys set by this class.
     */
    const CACHE_KEYS_OPTION = 'refservice_cache_keys';

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * API endpoint
     */
    private $endpoint;

    /**
     * API key
     */
    private $api_key;

    /**
     * Cache duration in minutes
     */
    private $cache_duration;

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
        $this->endpoint = trailingslashit(get_option('refservice_api_endpoint', 'https://referenssipalvelu.fi/api/v1'));
        $this->api_key = get_option('refservice_api_key', '');
        $this->cache_duration = (int) get_option('refservice_cache_duration', 30);
    }

    /**
     * Make API request
     */
    private function request($endpoint, $params = array())
    {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key is not configured.', 'refservice-references'));
        }

        // Build URL
        $url = $this->endpoint . $endpoint;
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }

        // Check cache (key includes the API key so switching keys never
        // serves data cached for a different key/company)
        $cache_key = 'refservice_' . md5($url . '|' . $this->api_key);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            // Negative cache hit: reconstruct the WP_Error without hitting the network
            if (is_array($cached) && !empty($cached['__refservice_error'])) {
                $error_data = isset($cached['status']) ? array('status' => $cached['status']) : '';
                return new WP_Error(
                    isset($cached['code']) ? $cached['code'] : 'api_error',
                    isset($cached['message']) ? $cached['message'] : __('API request failed.', 'refservice-references'),
                    $error_data
                );
            }
            return $cached;
        }

        // Only relax TLS verification and unsafe-URL rejection in local/dev
        // environments (e.g. an internal Docker network with self-signed certs
        // or service-name hosts). In production, certificates must be verified.
        $is_local = function_exists('wp_get_environment_type')
            && in_array(wp_get_environment_type(), array('local', 'development'), true);

        // Make request
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-API-Key' => $this->api_key,
                'Accept' => 'application/json',
            ),
            'timeout' => apply_filters('refservice_api_timeout', 5),
            'sslverify' => !$is_local,
            'reject_unsafe_urls' => !$is_local,
        ));

        // Handle errors
        if (is_wp_error($response)) {
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('RefService wp_remote_get Error: ' . $response->get_error_message());
                error_log('RefService Request URL: ' . $url);
            }
            $this->cache_error($cache_key, $response->get_error_code(), $response->get_error_message());
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RefService API Status: ' . $status_code);
            error_log('RefService API URL: ' . $url);
            error_log('RefService API Response Body (first 300 chars): ' . substr($body, 0, 300));
        }

        if ($status_code !== 200) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['message']) 
                ? $error_data['message'] 
                : __('API request failed.', 'refservice-references');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('RefService API Error Response (first 300 chars): ' . substr($body, 0, 300));
            }
            $this->cache_error($cache_key, 'api_error', $error_message, $status_code);
            return new WP_Error('api_error', $error_message, array('status' => $status_code));
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('RefService JSON Error: ' . json_last_error_msg());
                error_log('RefService Response Body (first 300 chars): ' . substr($body, 0, 300));
            }
            $error_message = __('Invalid JSON response from API.', 'refservice-references');
            $this->cache_error($cache_key, 'json_error', $error_message, $status_code);
            return new WP_Error('json_error', $error_message);
        }

        // Cache successful response
        $this->set_cached($cache_key, $data, $this->cache_duration * MINUTE_IN_SECONDS);

        return $data;
    }

    /**
     * Cache an error marker so failed requests are not retried on every page
     * view (negative caching).
     */
    private function cache_error($cache_key, $code, $message, $status = null)
    {
        $marker = array(
            '__refservice_error' => true,
            'code' => $code,
            'message' => $message,
            'status' => $status,
        );
        $ttl = (int) apply_filters('refservice_error_cache_ttl', 60);
        $this->set_cached($cache_key, $marker, $ttl);
    }

    /**
     * Set a transient and record its key in the registry so clear_cache()
     * works on sites with a persistent object cache.
     */
    private function set_cached($cache_key, $value, $ttl)
    {
        set_transient($cache_key, $value, $ttl);

        $keys = get_option(self::CACHE_KEYS_OPTION, array());
        if (!is_array($keys)) {
            $keys = array();
        }
        if (!in_array($cache_key, $keys, true)) {
            // Opportunistic pruning: every distinct URL/API-key combination
            // adds an entry, so without this the registry grows unbounded on
            // sites with many shortcode/filter variations. Dropping keys whose
            // transient has expired keeps it at the working set.
            if (count($keys) >= 50) {
                $keys = array_values(array_filter($keys, function ($key) {
                    return false !== get_transient($key);
                }));
            }
            $keys[] = $cache_key;
            update_option(self::CACHE_KEYS_OPTION, $keys, false);
        }
    }

    /**
     * Get company information
     */
    public function get_company()
    {
        return $this->request('company');
    }

    /**
     * Get references
     */
    public function get_references($params = array())
    {
        return $this->request('references', $params);
    }

    /**
     * Get single reference
     */
    public function get_reference($id, $params = array())
    {
        return $this->request('references/' . intval($id), $params);
    }

    /**
     * Get products
     */
    public function get_products($params = array())
    {
        return $this->request('products', $params);
    }

    /**
     * Get services
     */
    public function get_services($params = array())
    {
        return $this->request('services', $params);
    }

    /**
     * Clear cache
     *
     * Static so it can be called without an instance (e.g. from uninstall
     * context where only this class file and WordPress are loaded).
     */
    public static function clear_cache()
    {
        // Delete every transient recorded in the key registry. Using
        // delete_transient() also works on sites with a persistent object
        // cache (Redis/Memcached), where raw SQL on wp_options would not.
        $keys = get_option(self::CACHE_KEYS_OPTION, array());
        if (is_array($keys)) {
            foreach ($keys as $cache_key) {
                delete_transient($cache_key);
            }
        }
        delete_option(self::CACHE_KEYS_OPTION);

        // Best-effort second step: remove transients left behind by older
        // plugin versions that did not maintain the key registry. Underscores
        // are escaped because '_' is a single-character wildcard in LIKE.
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '\\_transient\\_refservice\\_%'
            OR option_name LIKE '\\_transient\\_timeout\\_refservice\\_%'"
        );
    }
}

