<?php
$quiz_title       = get_field('quiz_title');
$quiz_desc        = get_field('quiz_desc');
$quiz_bg          = get_field('quiz_bg');
$quiz_button      = get_field('quiz_button');
$quiz_button_link = get_field('quiz_button_link');
?>

<?php if ($quiz_title || $quiz_desc || $quiz_button): ?>

    <section class="quiz-banner">

        <div class="quiz-banner__inner">

            <?php if ($quiz_bg): ?>

                <?php
                /*
                 * ACF Image может возвращать:
                 * - массив
                 * - ID
                 * - URL
                 */

                $quiz_bg_url = '';
                $quiz_bg_alt = '';

                if (is_array($quiz_bg)) {

                    $quiz_bg_url = $quiz_bg['url'] ?? '';
                    $quiz_bg_alt = $quiz_bg['alt'] ?? '';
                } elseif (is_numeric($quiz_bg)) {

                    $quiz_bg_url = wp_get_attachment_image_url(
                        $quiz_bg,
                        'full'
                    );

                    $quiz_bg_alt = get_post_meta(
                        $quiz_bg,
                        '_wp_attachment_image_alt',
                        true
                    );
                } else {

                    $quiz_bg_url = $quiz_bg;
                }
                ?>

                <?php if ($quiz_bg_url): ?>

                    <img
                        src="<?= esc_url($quiz_bg_url); ?>"
                        alt="<?= esc_attr($quiz_bg_alt); ?>"
                        class="quiz-banner__background">

                <?php endif; ?>

            <?php endif; ?>


            <div class="quiz-banner__inner__content">

                <?php if ($quiz_title): ?>

                    <h2
                        class="quiz-banner__inner__content__title"
                        data-scroll-animation="fade-left">
                        <?= esc_html($quiz_title); ?>
                    </h2>

                <?php endif; ?>


                <?php if ($quiz_desc): ?>

                    <div class="quiz-banner__inner__content__desc">
                        <p>
                            <?= wp_kses_post($quiz_desc); ?>
                        </p>
                    </div>

                <?php endif; ?>


                <?php if ($quiz_button): ?>

                    <a
                        href="<?= esc_url($quiz_button_link ?: '#'); ?>"
                        class="button button-white">
                        <?= esc_html($quiz_button); ?>
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </section>

<?php endif; ?>