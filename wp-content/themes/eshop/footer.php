<?php
$footer_logo_id = get_theme_mod('footer_logo');
$footer_logo_url = $footer_logo_id ? wp_get_attachment_image_url($footer_logo_id, 'full') : '';

$site_name = get_bloginfo('name');
$site_description = get_bloginfo('description');
?>

<footer id="colophon" class="footer white-content" role="contentinfo">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-inner__col">
                <div class="footer-inner__col__logo">
                    <?php if ($footer_logo_url): ?>
                        <img
                            src="<?= esc_url($footer_logo_url); ?>"
                            alt="<?= esc_attr($site_name); ?>">
                    <?php endif; ?>

                    <div class="_footer-site-info__logo__company">

                        <?php if ($site_name): ?>
                            <p class="site-name">
                                <?= esc_html($site_name); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($site_description): ?>
                            <p class="site-description">
                                <?= esc_html($site_description); ?>
                            </p>
                        <?php endif; ?>

                    </div>

                </div>

                <?php
                wp_nav_menu([
                    'theme_location' => 'docs_menu',
                    'container'      => false,
                    'menu_class'     => 'docs-menu',
                    'menu_id'        => '',
                    'fallback_cb'    => false,
                    'link_before'    => '',
                    'link_after'     => '',
                ]);
                ?>
            </div>

            <?php if (is_active_sidebar('footer-sidebar-1')) : ?>
                <div class="footer-inner__col">
                    <?php dynamic_sidebar('footer-sidebar-1'); ?>
                </div>
            <?php endif; ?>

            <?php if (is_active_sidebar('footer-sidebar-2')) : ?>
                <div class="footer-inner__col">
                    <?php dynamic_sidebar('footer-sidebar-2'); ?>
                </div>
            <?php endif; ?>

            <?php if (is_active_sidebar('footer-sidebar-3')) : ?>
                <div class="footer-inner__col">
                    <?php dynamic_sidebar('footer-sidebar-3'); ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</footer>

<!-- SCROLL TOP -->
<button class="scroll-top" type="button" aria-label="Наверх">
    <svg width="10" height="12" viewBox="0 0 10 12" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M5 0L0 5L0.705 5.705L4.5 1.915V12H5.5V1.915L9.295 5.705L10 5L5 0Z" fill="#000000" />
    </svg>

</button>

</div>
<!-- end main wrapper-->

<?php if (current_user_can('manage_options')) : ?>
    <div class="current-temp"
        style="position: fixed;
  background: rgba(255,255,255,.7);
  color: #404040;
  padding: 5px 10px;
  font-size: 10px;
  bottom: 10px;
  right: 10px;">
        <?php echo get_current_template() ?>
    </div>
<?php endif; ?>

<div id="consultation-popup" class="popup" style="display:none;">
    <?php echo do_shortcode('[contact-form-7 id="4070d6c" title="Бесплатная консультация"]'); ?>
</div>

<div id="partners-popup" class="popup" style="display:none;max-width:600px;">
    <?php echo do_shortcode('[contact-form-7 id="afa0f23" title="Стать партнером"]'); ?>
</div>

<?php if (!is_cart()) : ?>
    <!--== Start Mini Cart Wrapper ==-->
    <div id="minicart-popup">
        <button class="close">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0.75 11.236L5.993 5.993L11.236 11.236M11.236 0.75L5.992 5.993L0.75 0.75" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>


        <?php woocommerce_mini_cart()
        ?>
    </div>
    <!--== End Mini Cart Wrapper ==-->

<?php endif; ?>


<?php wp_footer(); ?>
</body>

</html>