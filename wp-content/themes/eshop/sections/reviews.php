<?php
$reviews_title       = get_field('reviews_title');
$reviews_bg          = get_field('reviews_bg');
$reviews_description = get_field('reviews_description');
$reviews             = get_field('reviews');
$button_text            = get_field('reviews_button_text');
$button_link         = get_field('reviews_button_link');
?>

<?php if ($reviews): ?>

    <section
        class="reviews"
        <?php if ($reviews_bg): ?>
        style="background-image: url('<?= esc_url($reviews_bg['url']); ?>');"
        <?php endif; ?>>
        <div class="fixed-container">

            <?php if ($reviews_title): ?>
                <h2 data-scroll-animation="scale">
                    <?= esc_html($reviews_title); ?>
                </h2>
            <?php endif; ?>


            <?php if ($reviews_description): ?>
                <div class="reviews_desc">
                    <?= wp_kses_post($reviews_description); ?>
                </div>
            <?php endif; ?>


            <div class="swiper reviews-slider">

                <div class="swiper-wrapper">

                    <?php foreach ($reviews as $review): ?>

                        <?php
                        $review_text = $review['review_text'] ?? '';
                        $review_pics = $review['reviews_pics'] ?? [];
                        ?>

                        <div class="swiper-slide reviews-slider__slide">

                            <?php if ($review_text): ?>

                                <div class="reviews-slider__slide__text">

                                    <p>
                                        <?= wp_kses_post($review_text); ?>
                                    </p>

                                    <button class="reviews-slider__more" type="button">
                                        Читать полностью
                                    </button>

                                </div>

                            <?php endif; ?>


                            <?php if ($review_pics): ?>

                                <div class="reviews-photo">

                                    <?php foreach ($review_pics as $pic): ?>

                                        <?php
                                        $image = $pic['review_pic_item'] ?? null;

                                        if (!$image) {
                                            continue;
                                        }

                                        // Если ACF возвращает ID
                                        if (is_numeric($image)) {
                                            $image_url = wp_get_attachment_image_url(
                                                $image,
                                                'full'
                                            );

                                            $image_alt = get_post_meta(
                                                $image,
                                                '_wp_attachment_image_alt',
                                                true
                                            );

                                            $image_thumb = wp_get_attachment_image_url(
                                                $image,
                                                'medium_large'
                                            );
                                        }

                                        // Если ACF возвращает массив
                                        elseif (is_array($image)) {
                                            $image_url   = $image['url'] ?? '';
                                            $image_alt   = $image['alt'] ?? '';
                                            $image_thumb = $image['sizes']['medium_large']
                                                ?? $image_url;
                                        } else {
                                            $image_url   = $image;
                                            $image_thumb = $image;
                                            $image_alt   = '';
                                        }

                                        if (!$image_url) {
                                            continue;
                                        }
                                        ?>

                                        <a
                                            href="<?= esc_url($image_url); ?>"
                                            data-fancybox="reviews-photo">
                                            <img
                                                src="<?= esc_url($image_thumb); ?>"
                                                alt="<?= esc_attr($image_alt); ?>">
                                        </a>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <?php
            if ($button_link) {
            ?>
                <a href="<?= wp_kses_post($button_link); ?>" class="button reviews-btn">
                    <?php
                    if ($button_text) {
                    ?>
                        <?= wp_kses_post($button_text); ?>
                    <?php } else {
                        echo 'ссылка';
                    } ?>
                </a>
            <?php
            }
            ?>


        </div>
    </section>

<?php endif; ?>