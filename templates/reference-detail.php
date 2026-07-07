<?php
/**
 * Reference Detail Template
 * 
 * This template can be overridden by copying it to your theme:
 * your-theme/refservice/reference-detail.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="<?php echo esc_attr($wrapper_class); ?>" style="--refservice-color: <?php echo esc_attr($company_color); ?>; --refservice-color-text: <?php echo esc_attr($company_color_text); ?>;">
    <article class="refservice-detail-article">
        <?php if (!empty($reference['image_main_large'])) : ?>
            <div class="refservice-detail-hero">
                <img src="<?php echo esc_url($reference['image_main_large']); ?>" 
                     alt="<?php echo esc_attr($reference['name'] ?? ''); ?>"
                     class="refservice-detail-main-image">
            </div>
        <?php endif; ?>

        <header class="refservice-detail-header">
            <?php if (!empty($reference['product']['name']) || !empty($reference['service']['name'])) : ?>
                <span class="refservice-detail-category">
                    <?php echo esc_html($reference['product']['name'] ?? $reference['service']['name']); ?>
                </span>
            <?php endif; ?>
            
            <h1 class="refservice-detail-title"><?php echo esc_html($reference['name'] ?? ''); ?></h1>
            
            <?php if (!empty($reference['customer'])) : ?>
                <div class="refservice-detail-customer">
                    <?php if (!empty($reference['customer']['image'])) : ?>
                        <img src="<?php echo esc_url($reference['customer']['image']); ?>" 
                             alt="<?php echo esc_attr($reference['customer']['name']); ?>"
                             class="refservice-detail-customer-logo">
                    <?php endif; ?>
                    <?php if (!empty($reference['customer']['name'])) : ?>
                        <span class="refservice-detail-customer-name"><?php echo esc_html($reference['customer']['name']); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if (!empty($reference['description_short'])) : ?>
            <div class="refservice-detail-description refservice-detail-description-short">
                <?php echo wp_kses_post($reference['description_short']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reference['description'])) : ?>
            <div class="refservice-detail-description">
                <?php echo wp_kses_post($reference['description']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reference['images']) && is_array($reference['images'])) : ?>
            <div class="refservice-detail-gallery">
                <h2><?php esc_html_e('Gallery', 'refservice-references'); ?></h2>
                <div class="refservice-detail-gallery-grid">
                    <?php foreach ($reference['images'] as $image) : ?>
                        <figure class="refservice-detail-gallery-item">
                            <a href="<?php echo esc_url($image['url_large'] ?? $image['url']); ?>" 
                               class="refservice-detail-gallery-link"
                               data-lightbox="refservice-gallery">
                                <img src="<?php echo esc_url($image['url']); ?>" 
                                     alt="<?php echo esc_attr($image['title'] ?? ($reference['name'] ?? '')); ?>"
                                     loading="lazy">
                            </a>
                            <?php if (!empty($image['title'])) : ?>
                                <figcaption><?php echo esc_html($image['title']); ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($reference['seller'])) : ?>
            <div class="refservice-detail-seller">
                <h2><?php esc_html_e('Contact', 'refservice-references'); ?></h2>
                <?php if (!empty($reference['seller']['name'])) : ?>
                    <p><strong><?php echo esc_html($reference['seller']['name']); ?></strong></p>
                <?php endif; ?>
                <?php if (!empty($reference['seller']['email'])) : ?>
                    <p><a href="mailto:<?php echo esc_attr($reference['seller']['email']); ?>"><?php echo esc_html($reference['seller']['email']); ?></a></p>
                <?php endif; ?>
                <?php if (!empty($reference['seller']['phone'])) : ?>
                    <p><a href="tel:<?php echo esc_attr($reference['seller']['phone']); ?>"><?php echo esc_html($reference['seller']['phone']); ?></a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <footer class="refservice-detail-footer">
            <?php if (!empty($reference['delivery_month']) && !empty($reference['delivery_year'])) : ?>
                <time datetime="<?php echo esc_attr($reference['delivery_year'] . '-' . str_pad($reference['delivery_month'], 2, '0', STR_PAD_LEFT)); ?>">
                    <?php
                    echo esc_html(date_i18n('F Y', mktime(0, 0, 0, (int) $reference['delivery_month'], 1, (int) $reference['delivery_year'])));
                    ?>
                </time>
            <?php endif; ?>
        </footer>
    </article>
</div>

