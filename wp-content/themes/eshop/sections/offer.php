<?php
$offer_title          = get_field('offer_title');
$offer_bg             = get_field('offer_bg');
$offer_banner_heading = get_field('offer_banner_heading');
$offer_banner_texts   = get_field('offer_banner_texts');
?>

<?php if ($offer_title || $offer_bg || $offer_banner_heading || $offer_banner_texts): ?>

    <section class="offer">

        <div class="fixed-container">

            <?php if ($offer_title): ?>
                <h2 class="small-heading">
                    <?= esc_html($offer_title); ?>
                </h2>
            <?php endif; ?>


            <div class="offer-banner">

                <?php if ($offer_bg): ?>

                    <?php
                    /*
                     * ACF Image:
                     * поддерживаем как ID, так и массив.
                     */
                    $offer_bg_url = '';
                    $offer_bg_alt = '';

                    if (is_array($offer_bg)) {
                        $offer_bg_url = $offer_bg['url'] ?? '';
                        $offer_bg_alt = $offer_bg['alt'] ?? '';
                    } else {
                        $offer_bg_url = wp_get_attachment_image_url(
                            $offer_bg,
                            'full'
                        );

                        $offer_bg_alt = get_post_meta(
                            $offer_bg,
                            '_wp_attachment_image_alt',
                            true
                        );
                    }
                    ?>

                    <?php if ($offer_bg_url): ?>

                        <img
                            src="<?= esc_url($offer_bg_url); ?>"
                            alt="<?= esc_attr(
                                $offer_bg_alt ?: $offer_title
                            ); ?>"
                        >

                    <?php endif; ?>

                <?php endif; ?>


                <div class="offer-banner__content">

                    <?php if ($offer_banner_heading): ?>

                        <h3 class="offer-heading">
                            <?= esc_html($offer_banner_heading); ?>
                        </h3>

                    <?php endif; ?>


                    <?php if ($offer_banner_texts): ?>

                        <div class="offer-banner__content__inner">

                            <?php foreach ($offer_banner_texts as $text_item): ?>

                                <?php
                                $text = $text_item['offer_banner_text_item'] ?? '';
                                ?>

                                <?php if ($text): ?>

                                    <div class="offer-banner__text">

                                        <p>
                                            <?= wp_kses_post($text); ?>
                                        </p>

                                    </div>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>

<?php endif; ?>