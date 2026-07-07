/**
 * Admin scripts for the RefService References settings page.
 *
 * Plain ES5, no dependencies. Interfaces with the PHP-rendered markup via
 * the element IDs (#refservice_api_endpoint, #refservice_api_endpoint_edit,
 * #refservice_custom_css) and the data-css attribute on the preset buttons.
 */
(function () {
    'use strict';

    /**
     * The API endpoint input is readonly by default; the "Edit" link
     * unlocks it for connecting to a different or local installation.
     */
    function setup_endpoint_edit_link() {
        var link = document.getElementById('refservice_api_endpoint_edit');
        var input = document.getElementById('refservice_api_endpoint');
        if (!link || !input) {
            return;
        }
        link.addEventListener('click', function (e) {
            e.preventDefault();
            input.removeAttribute('readonly');
            input.focus();
            link.style.display = 'none';
        });
    }

    /**
     * The CSS preset buttons append their data-css snippet to the
     * Custom CSS textarea.
     */
    function setup_css_preset_buttons() {
        var textarea = document.getElementById('refservice_custom_css');
        var buttons = document.querySelectorAll('.refservice-css-preset');
        if (!textarea || !buttons.length) {
            return;
        }
        for (var i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener('click', function () {
                var snippet = this.getAttribute('data-css');
                var current = textarea.value.replace(/\s+$/, '');
                textarea.value = current ? current + '\n\n' + snippet : snippet;
                textarea.focus();
            });
        }
    }

    function init() {
        setup_endpoint_edit_link();
        setup_css_preset_buttons();
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
