<?php
$about_title       = get_field('about_title');
$about_text        = get_field('about_text');
$about_pic         = get_field('about_pic');
$about_button_text = get_field('about_button_text');
$about_button_link = get_field('about_button_link');
?>

<?php if ($about_title || $about_text || $about_pic): ?>

    <section class="about-company">

        <div class="fixed-container">

            <div class="about-company__inner">

                <div class="about-company__inner__text">

                    <?php if ($about_title): ?>

                        <h2 class="small-heading">
                            <?= esc_html($about_title); ?>
                        </h2>

                    <?php endif; ?>


                    <?php if ($about_text): ?>

                        <div class="about-company__text">
                            <?= wp_kses_post($about_text); ?>
                        </div>

                    <?php endif; ?>


                    <?php if ($about_button_text): ?>

                        <a
                            href="<?= esc_url($about_button_link ?: '#'); ?>"
                            class="button">
                            <?= esc_html($about_button_text); ?>
                        </a>

                    <?php endif; ?>

                </div>


                <?php if ($about_pic): ?>

                    <?php
                    /*
                     * ACF Image может возвращать:
                     * массив / ID / URL.
                     */

                    $about_pic_url = '';
                    $about_pic_alt = '';

                    if (is_array($about_pic)) {

                        $about_pic_url = $about_pic['url'] ?? '';
                        $about_pic_alt = $about_pic['alt'] ?? '';
                    } elseif (is_numeric($about_pic)) {

                        $about_pic_url = wp_get_attachment_image_url(
                            $about_pic,
                            'full'
                        );

                        $about_pic_alt = get_post_meta(
                            $about_pic,
                            '_wp_attachment_image_alt',
                            true
                        );
                    } else {

                        $about_pic_url = $about_pic;
                    }
                    ?>

                    <?php if ($about_pic_url): ?>

                        <div class="about-company__inner__img">

                            <img
                                src="<?= esc_url($about_pic_url); ?>"
                                alt="<?= esc_attr($about_pic_alt ?: $about_title); ?>">

                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

        </div>

    </section>

<?php endif; ?>