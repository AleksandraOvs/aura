<?php get_header() ?>
<section class="page-content">
    <?php get_template_part('template-parts/page-header'); ?>
    <div class="content">
        <div class="container">
            <?php the_content(); ?>
        </div>
    </div>
</section>

<?php //get_template_part('template-parts/section-contacts') 
?>


<?php get_footer() ?>