<?php

/**
 * Template name: Cart
 */
get_header() ?>

<section class="page-title-block">
    <div class="fixed-container">
        <?php site_breadcrumbs() ?>

        <?php get_template_part('template-parts/checkout-steps') ?>


    </div>
</section>

<section class="page-content">
    <div class="container">
        <h1 class="small-heading" data-scroll-animation="fade-left">
            <?= the_title() ?>
        </h1>
        <?php the_content(); ?>
    </div>

</section>


<?php get_template_part('sections/contacts') ?>


<?php get_footer() ?>