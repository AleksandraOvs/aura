<?php
$partners_cta_title       = get_field('partners_cta_title');
$partners_cta_description = get_field('partners_cta_description');

$button_1_text = get_field('button_1_text');
$button_1_link = get_field('button_1_link');

$button_2_text = get_field('button_2_text');
$button_2_link = get_field('button_2_link');
?>

<?php if ($partners_cta_title || $partners_cta_description || $button_1_text || $button_2_text): ?>
    <section class="partners-cta">
        <div class="fixed-container">

            <?php if ($partners_cta_title): ?>
                <h2 class="small-heading">
                    <?= wp_kses_post($partners_cta_title); ?>
                </h2>
            <?php endif; ?>

            <?php if ($partners_cta_description): ?>
                <div class="partners-cta__desc">
                    <?= wp_kses_post($partners_cta_description); ?>
                </div>
            <?php endif; ?>

            <?php if ($button_1_text || $button_2_text): ?>
                <div class="patners-cta__buttons">

                    <?php if ($button_1_text && $button_1_link): ?>
                        <a
                            href="<?= esc_url($button_1_link); ?>"
                            class="button button-black">
                            <?= esc_html($button_1_text); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($button_2_text && $button_2_link): ?>
                        <a
                            href="<?= esc_url($button_2_link); ?>"
                            class="button button-white">
                            <?= esc_html($button_2_text); ?>
                        </a>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        </div>
    </section>
<?php endif; ?>