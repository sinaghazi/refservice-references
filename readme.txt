=== Referenssipalvelu References ===
Contributors: inmediasystems
Tags: references, testimonials, portfolio, case studies, api
Requires at least: 5.9
Tested up to: 6.8
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display your company references from Referenssipalvelu on your WordPress website. Lightweight plugin that integrates seamlessly with your Referenssipalvelu account.

== Description ==

Referenssipalvelu References is a lightweight WordPress plugin that allows you to display your company references, testimonials, and case studies from your Referenssipalvelu account directly on your WordPress website.

Referenssipalvelu is a modern reference management system built with Laravel 12, featuring multilingual support, real-time components, and a comprehensive admin interface. This plugin connects your WordPress site to that service over its public API.

**Features:**

* Easy integration with the Referenssipalvelu API
* Display references in grid, list, or carousel layout
* Gutenberg block with live preview, plus shortcode support
* Filter by products, services, or custom categories
* Automatic language detection based on your company's enabled languages
* Responsive design that works on all devices
* Customizable styling with company branding colors
* Built-in caching for optimal performance
* No external dependencies

**How it works:**

1. Request activation of the API Service and obtain an API key from your Referenssipalvelu account manager
2. Configure the plugin in WordPress Settings
3. Use the shortcode `[refservice]` to display references
4. Customize with attributes like `limit`, `layout`, `products`, etc.

**Support & inquiries**

Inmedia Systems Oy
Business ID: 1906813-6
Email: karri.naakka@inmedia.fi
Phone: +358 50 386 4372

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/refservice-references` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings > Referenssipalvelu References to configure your API key.
4. Use the `[refservice]` shortcode on any page or post to display your references.

== Frequently Asked Questions ==

= How do I get my API key? =

Based on your package, you can request activation of the API Service and obtain an API key from your Referenssipalvelu account manager.

= What is the API endpoint URL? =

The endpoint is pre-configured to the hosted service (`https://referenssipalvelu.fi/api/v1`) and is locked by default. Use the "Edit" link next to the field only if you are connecting to a different or local installation.

= Can I customize the appearance? =

Yes! The plugin uses CSS custom properties for colors, allowing you to override styles with your theme's CSS. The plugin also respects your company branding colors from Referenssipalvelu. The settings page includes one-click Custom CSS presets to get you started.

= How do I filter references? =

Use shortcode attributes to filter:
* `products="1,2"` - Show only references for specific products
* `services="3"` - Show only references for specific services
* `limit="6"` - Limit the number of references displayed

= Does the plugin cache data? =

Yes, the plugin caches API responses to improve performance. You can configure the cache duration in settings and clear the cache manually when needed.

== Screenshots ==

1. Plugin settings page
2. References displayed in grid layout
3. References displayed in list layout
4. Single reference card with customer logo

== Changelog ==

= 2.0.0 =
* Fixed bugs and improved the plugin 

= 1.0.0 =
* Initial release
* API integration with RefService
* Shortcode support
* Grid and list layouts
* Filtering by products, services, and custom categories
* Responsive design
* Caching support

== Upgrade Notice ==

= 2.0.0 =
Initial release of Public RefService References plugin for WordPress.

