<?php
$info_text_left  = get_field('info_text_left');
$info_title      = get_field('info_title');
$info_text_right = get_field('info_text_right');
?>

<section class="section information">
    <div class="container">
        <div class="informaition__inner">

            <?php if ($info_text_left): ?>
                <div class="information__inner__left">
                    <?= wp_kses_post($info_text_left); ?>
                </div>
            <?php endif; ?>

            <?php if ($info_title || $info_text_right): ?>
                <div class="information__inner__right">

                    <?php if ($info_title): ?>
                        <h3>
                            <?= wp_kses_post($info_title); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ($info_text_right): ?>
                        <?= wp_kses_post($info_text_right); ?>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        </div>
    </div>
</section>