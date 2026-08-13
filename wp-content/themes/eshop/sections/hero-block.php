<?php
$hero_img         = get_field('hero_img');
$title_h1         = get_field('title_h1');
$hero_description = get_field('hero_description');
$button_text      = get_field('button_text');
$button_link      = get_field('button_link');
?>

<section class="hero">

    <?php if ($hero_img): ?>
        <div class="hero__image">

            <?php
            // Если ACF возвращает массив
            if (is_array($hero_img)) {
                $hero_img_url = $hero_img['url'] ?? '';
                $hero_img_alt = $hero_img['alt'] ?? '';
            } else {
                // Если ACF возвращает URL
                $hero_img_url = $hero_img;
                $hero_img_alt = '';
            }
            ?>

            <?php if ($hero_img_url): ?>
                <img
                    src="<?= esc_url($hero_img_url); ?>"
                    alt="<?= esc_attr($hero_img_alt); ?>" />
            <?php endif; ?>

        </div>
    <?php endif; ?>


    <div class="fixed-container hero-content">

        <?php if ($title_h1): ?>
            <h1 data-scroll-animation="fade-up">
                <?= wp_kses_post($title_h1); ?>
            </h1>
        <?php endif; ?>


        <?php if ($hero_description): ?>
            <div
                class="hero-content__description"
                data-scroll-animation="fade-down">
                <?= wp_kses_post($hero_description); ?>
            </div>
        <?php endif; ?>


        <?php if ($button_text && $button_link): ?>
            <a
                href="<?= esc_url($button_link); ?>"
                class="button">
                <?= esc_html($button_text); ?>
            </a>
        <?php endif; ?>

    </div>

</section>