<?php
$sertificates_title = get_field('sertificates_title');
$sertificates       = get_field('sertificates');
?>

<?php if ($sertificates_title || $sertificates): ?>

    <section class="sertificates">

        <div class="container">

            <?php if ($sertificates_title): ?>
                <h2 class="small-heading">
                    <?= esc_html($sertificates_title); ?>
                </h2>
            <?php endif; ?>


            <?php if ($sertificates): ?>

                <div class="swiper sertificates-slider">

                    <div class="swiper-wrapper">

                        <?php foreach ($sertificates as $certificate): ?>

                            <?php
                            $sert_pic = $certificate['sert_pic'] ?? null;

                            if (!$sert_pic) {
                                continue;
                            }

                            /*
                             * Оригинальное изображение —
                             * именно его будет открывать Fancybox.
                             */
                            $full_image = $sert_pic['url'];

                            /*
                             * Изображение для отображения
                             * внутри слайдера.
                             */
                            $preview_image = $sert_pic['sizes']['large']
                                ?? $sert_pic['url'];

                            $alt = $sert_pic['alt'] ?: 'Сертификат';
                            ?>

                            <div class="swiper-slide sertificates-slider__slide">

                                <a
                                    href="<?= esc_url($full_image); ?>"
                                    class="sertificate"
                                    data-fancybox="sertificates">

                                    <img
                                        src="<?= esc_url($preview_image); ?>"
                                        alt="<?= esc_attr($alt); ?>">

                                </a>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <div class="sertificates-slider-pagination"></div>

                </div>

            <?php endif; ?>

        </div>

    </section>

<?php endif; ?>