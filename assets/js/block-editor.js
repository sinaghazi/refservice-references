(function(blocks, element, components, i18n, blockEditor, serverSideRender) {
    const { registerBlockType } = blocks;
    const { createElement: el } = element;
    const { PanelBody, TextControl, SelectControl, RangeControl } = components;
    const { __ } = i18n;
    const { InspectorControls, useBlockProps } = blockEditor;
    // wp.serverSideRender IS the component itself (or an ES module default).
    const ServerSideRender = serverSideRender && serverSideRender.default ? serverSideRender.default : serverSideRender;

    registerBlockType('refservice/references', {
        title: __('Referenssipalvelu References', 'refservice-references'),
        icon: 'portfolio',
        category: 'widgets',
        description: __('Display your Referenssipalvelu references with filters and customizable layout.', 'refservice-references'),
        attributes: {
            limit: {
                type: 'number',
            },
            layout: {
                type: 'string',
            },
            columns: {
                type: 'number',
                default: 3,
            },
            products: {
                type: 'string',
                default: '',
            },
            services: {
                type: 'string',
                default: '',
            },
            customCategory1: {
                type: 'string',
                default: '',
            },
            customCategory2: {
                type: 'string',
                default: '',
            },
            language: {
                type: 'string',
                default: '',
            },
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { limit, layout, columns, products, services, customCategory1, customCategory2, language } = attributes;

            // apiVersion 2: the edit component must supply the block wrapper
            // props itself (selection, focus, toolbar anchoring).
            return el('div', useBlockProps({ className: 'refservice-block-editor' }),
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Display Settings', 'refservice-references'), initialOpen: true },
                        el(RangeControl, {
                            label: __('Number of References', 'refservice-references'),
                            value: typeof limit === 'number' ? limit : undefined,
                            onChange: (value) => setAttributes({ limit: typeof value === 'number' ? value : undefined }),
                            allowReset: true,
                            min: 1,
                            max: 50,
                            help: __('Leave empty to show all references', 'refservice-references'),
                        }),
                        el(SelectControl, {
                            label: __('Layout', 'refservice-references'),
                            // Unset means the site-wide default layout from
                            // Settings > Referenssipalvelu References applies.
                            value: layout || '',
                            options: [
                                { label: __('Site default', 'refservice-references'), value: '' },
                                { label: __('Grid', 'refservice-references'), value: 'grid' },
                                { label: __('List', 'refservice-references'), value: 'list' },
                                { label: __('Carousel', 'refservice-references'), value: 'carousel' },
                            ],
                            onChange: (value) => setAttributes({ layout: value || undefined }),
                        }),
                        (layout === 'grid' || layout === 'carousel' || !layout) && el(RangeControl, {
                            label: __('Columns', 'refservice-references'),
                            value: columns || 3,
                            onChange: (value) => setAttributes({ columns: value }),
                            min: 1,
                            max: 4,
                        }),
                    ),
                    el(PanelBody, { title: __('Filter Settings', 'refservice-references'), initialOpen: false },
                        el(TextControl, {
                            label: __('Product IDs', 'refservice-references'),
                            value: products || '',
                            onChange: (value) => setAttributes({ products: value }),
                            help: __('Comma-separated product IDs (e.g., 1,2,3)', 'refservice-references'),
                        }),
                        el(TextControl, {
                            label: __('Service IDs', 'refservice-references'),
                            value: services || '',
                            onChange: (value) => setAttributes({ services: value }),
                            help: __('Comma-separated service IDs (e.g., 1,2)', 'refservice-references'),
                        }),
                        el(TextControl, {
                            label: __('Custom Category 1 IDs', 'refservice-references'),
                            value: customCategory1 || '',
                            onChange: (value) => setAttributes({ customCategory1: value }),
                            help: __('Comma-separated category IDs', 'refservice-references'),
                        }),
                        el(TextControl, {
                            label: __('Custom Category 2 IDs', 'refservice-references'),
                            value: customCategory2 || '',
                            onChange: (value) => setAttributes({ customCategory2: value }),
                            help: __('Comma-separated category IDs', 'refservice-references'),
                        }),
                        el(TextControl, {
                            label: __('Language Code', 'refservice-references'),
                            value: language || '',
                            onChange: (value) => setAttributes({ language: value }),
                            help: __('Language code (e.g., en, fi). Leave empty for auto-detection.', 'refservice-references'),
                        }),
                    )
                ),
                el('div', { className: 'refservice-block-preview-wrapper' },
                    el(ServerSideRender, {
                        block: 'refservice/references',
                        attributes: attributes,
                    })
                )
            );
        },
        save: function() {
            // Server-side rendering
            return null;
        },
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.i18n,
    window.wp.blockEditor,
    window.wp.serverSideRender
);

