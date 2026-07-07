<?php
/**
 * Language detection and handling class for RefService References plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class RefService_Language
{
    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Company languages cache
     */
    private $company_languages = null;

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
    }

    /**
     * Get WordPress locale (e.g., 'en_US' -> 'en', 'fi_FI' -> 'fi')
     */
    public function get_wordpress_locale()
    {
        $locale = get_locale();
        
        // Extract language code from locale (e.g., 'en_US' -> 'en')
        $lang_code = substr($locale, 0, 2);
        
        return strtolower($lang_code);
    }

    /**
     * Get company's enabled languages from API
     */
    public function get_company_languages()
    {
        // Return cached if available
        if ($this->company_languages !== null) {
            return $this->company_languages;
        }

        // Try to fetch from API
        $api_client = RefService_Api_Client::get_instance();
        $company = $api_client->get_company();

        if (is_wp_error($company)) {
            // If API fails, return empty array
            $this->company_languages = array();
            return $this->company_languages;
        }

        // Extract languages from company data
        $languages = isset($company['languages']) && is_array($company['languages']) 
            ? $company['languages'] 
            : array();

        // Cache the result
        $this->company_languages = $languages;

        return $this->company_languages;
    }

    /**
     * Get primary language (first in company's language list)
     */
    public function get_primary_language()
    {
        $languages = $this->get_company_languages();
        return !empty($languages) ? $languages[0] : 'en';
    }

    /**
     * Detect best matching language
     * Priority:
     * 1. Explicitly set language parameter
     * 2. WordPress locale (if matches company languages)
     * 3. Company's primary language
     * 4. Fallback to 'en'
     */
    public function detect_language($explicit_language = '')
    {
        // Priority 1: Explicitly set language
        if (!empty($explicit_language)) {
            $languages = $this->get_company_languages();
            if (in_array($explicit_language, $languages, true)) {
                return $explicit_language;
            }
        }

        // Priority 2: WordPress locale
        $wp_locale = $this->get_wordpress_locale();
        $languages = $this->get_company_languages();
        
        if (!empty($languages) && in_array($wp_locale, $languages, true)) {
            return $wp_locale;
        }

        // Priority 3: Company's primary language
        $primary = $this->get_primary_language();
        if (!empty($primary)) {
            return $primary;
        }

        // Priority 4: Fallback
        return 'en';
    }
}

