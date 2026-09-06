<?php
$shop_page_id = wc_get_page_id('shop');

$hero_img   = get_field('shop_page_hero_img', $shop_page_id);
$hero_text1 = get_field('shop_page_hero_text1', $shop_page_id);
$title_h1   = get_field('shop_page_hero_heading', $shop_page_id);
$hero_text2 = get_field('shop_page_hero_text2', $shop_page_id);


// Если картинка не выбрана в ACF — используем изображение по умолчанию
if (!$hero_img) {
    $hero_img_url = get_template_directory_uri() . '/imgs/cooperation-bg.webp';
    $hero_img_alt = '';
} elseif (is_array($hero_img)) {
    $hero_img_url = $hero_img['url'] ?? '';
    $hero_img_alt = $hero_img['alt'] ?? '';
} else {
    $hero_img_url = $hero_img;
    $hero_img_alt = '';
}
?>

<section class="hero">

    <?php if ($hero_img_url): ?>
        <div class="hero__image">
            <img
                src="<?= esc_url($hero_img_url); ?>"
                alt="<?= esc_attr($hero_img_alt); ?>" />
        </div>
    <?php endif; ?>


    <div class="fixed-container hero-content">
        <?php if ($hero_text1): ?>
            <div
                class="hero-content__description --shop-hero"
                data-scroll-animation="fade-down">
                <?= wp_kses_post($hero_text1); ?>
            </div>
        <?php endif; ?>
        <?php if ($title_h1): ?>
            <h1 data-scroll-animation="fade-up">
                <?= wp_kses_post($title_h1); ?>
            </h1>
        <?php endif; ?>
        <?php if ($hero_text2): ?>
            <div
                class="hero-content__description --shop-hero"
                data-scroll-animation="fade-down">
                <?= wp_kses_post($hero_text2); ?>
            </div>
        <?php endif; ?>

    </div>

</section>