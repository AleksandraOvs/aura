<?php
$partners_title = get_field('partners_title');
$partners_list  = get_field('partners_list');
?>

<?php if ($partners_title || $partners_list): ?>

    <section class="partners">

        <div class="container">

            <?php if ($partners_title): ?>
                <h2 class="small-heading">
                    <?= esc_html($partners_title); ?>
                </h2>
            <?php endif; ?>


            <?php if ($partners_list): ?>

                <div class="swiper brands-slider">

                    <div class="swiper-wrapper">

                        <?php foreach ($partners_list as $partner): ?>

                            <?php
                            $partner_pic  = $partner['partners_list_pic'] ?? null;
                            $partner_text = $partner['partners_list_text'] ?? '';
                            ?>

                            <?php if ($partner_pic): ?>

                                <div class="swiper-slide">

                                    <a href="#" class="brand-card">

                                        <?php if (is_array($partner_pic)): ?>

                                            <img
                                                src="<?= esc_url($partner_pic['url']); ?>"
                                                alt="<?= esc_attr(
                                                            $partner_pic['alt']
                                                                ?: $partner_text
                                                        ); ?>">

                                        <?php elseif (is_numeric($partner_pic)): ?>

                                            <?= wp_get_attachment_image(
                                                (int) $partner_pic,
                                                'full',
                                                false,
                                                [
                                                    'alt' => $partner_text,
                                                ]
                                            ); ?>

                                        <?php endif; ?>


                                        <?php if ($partner_text): ?>

                                            <span>
                                                <?= esc_html($partner_text); ?>
                                            </span>

                                        <?php endif; ?>

                                    </a>

                                </div>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                    <div class="swiper-pagination"></div>

                </div>

            <?php endif; ?>

        </div>

    </section>

<?php endif; ?>