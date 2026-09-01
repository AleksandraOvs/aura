<?php

/**
 * Template Name: checkout
 */

defined('ABSPATH') || exit;

get_header();

?>

<section class="page-title-block">

    <div class="fixed-container">

        <?php site_breadcrumbs(); ?>

        <?php get_template_part('template-parts/checkout-steps'); ?>

        <h1 class="page-title" data-scroll-animation="fade-down">
            <?= esc_html(get_the_title()); ?>
        </h1>

    </div>

</section>



<section class="page-content">

    <div class="fixed-container">

        <?= do_shortcode('[woocommerce_checkout]'); ?>

    </div>

</section>


<?php get_template_part('sections/contacts'); ?>

<?php get_footer(); ?>