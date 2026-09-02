<?php

/**
 * Template name: Информация
 */
get_header() ?>

<?php $page_nav = get_page_siblings(); ?>

<section class="page-title-block">
    <div class="fixed-container">
        <?php site_breadcrumbs() ?>

        <?php if ($page_nav): ?>
            <nav class="page-nav">

                <?php foreach ($page_nav as $page): ?>
                    <a
                        class="page-nav__link <?= $page->ID === get_queried_object_id() ? 'active' : ''; ?>"
                        href="<?= esc_url(get_permalink($page->ID)); ?>">
                        <?= esc_html(get_the_title($page->ID)); ?>
                    </a>
                <?php endforeach; ?>

            </nav>
        <?php endif; ?>

        <h1 class="page-title" data-scroll-animation="fade-down">
            <?= the_title() ?>
        </h1>


    </div>
</section>

<section class="page-content">
    <div class="container">
        <?php the_content() ?>
    </div>


</section>


<?php get_template_part('sections/contacts') ?>


<?php get_footer() ?>