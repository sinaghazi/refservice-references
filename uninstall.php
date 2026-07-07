<?php
/**
 * Uninstall script for RefService References plugin
 *
 * @package RefService_References
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clear all cached API responses first, while the cache-key registry
// option still exists (RefService_Api_Client::clear_cache() reads it)
require_once __DIR__ . '/includes/class-api-client.php';
RefService_Api_Client::clear_cache();

// Delete plugin options
delete_option('refservice_api_key');
delete_option('refservice_api_endpoint');
delete_option('refservice_cache_duration');
delete_option('refservice_default_limit');
delete_option('refservice_default_layout');
delete_option('refservice_display_mode');
delete_option('refservice_custom_css');
delete_option('refservice_detail_url_pattern');
delete_option('refservice_cache_keys');

