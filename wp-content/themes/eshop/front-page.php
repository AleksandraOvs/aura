<?php get_header() ?>
<?php
$hero_enabled = get_field('hero_enabled');
$list_enabled = get_field('list_enabled');
$offer_enabled = get_field('offer_enabled');
$quiz_enabled = get_field('quiz_enabled');
$about_enabled = get_field('about_enabled');
$partners_enabled = get_field('partners_enabled');
$sertificates_enabled = get_field('sertificates_enabled');
$reviews_enabled = get_field('reviews_enabled');

?>
<?php if ($hero_enabled) { ?>
<?php get_template_part('sections/hero-block') ?>
<?php } ?>
<?php if ($list_enabled) { ?>
<?php get_template_part('sections/list-block') ?>
<?php } ?>

<?php get_template_part('sections/categories-section') ?>

<?php if ($offer_enabled) { ?>
<?php get_template_part('sections/offer') ?>
<?php } ?>

<?php if ($quiz_enabled) { ?>
<?php get_template_part('sections/help-block') ?>
<?php } ?>

<?php if ($about_enabled) { ?>
<?php get_template_part('sections/about-us') ?>
<?php } ?>

<?php if ($partners_enabled) { ?>
<?php get_template_part('sections/partners') ?>
<?php } ?>

<?php if ($sertificates_enabled) { ?>
<?php get_template_part('sections/sertificates') ?>
<?php } ?>

<?php if ($reviews_enabled) { ?>
<?php get_template_part('sections/reviews') ?>
<?php } ?>

<?php get_template_part('sections/contacts') ?>

<?php get_footer(); ?>