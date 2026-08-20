<?php

/**
 * Template name: Wishlist
 */
get_header() ?>

<section class="page-title-block">
    <div class="fixed-container">
        <?php site_breadcrumbs() ?>

        <h1 class="page-title" data-scroll-animation="fade-down">
            <?= the_title() ?>
        </h1>
    </div>
</section>

<section class="page-content _wishlist-page">
    <div class="fixed-container">
        <?php the_content(); ?>
    </div>

</section>

<?php get_template_part('template-parts/section-contacts') ?>


<?php get_footer() ?>