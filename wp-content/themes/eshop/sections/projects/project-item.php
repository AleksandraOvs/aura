<?php
$animation_delay = $args['animation_delay'] ?? 0.1;
?>

<div
    class="project-item"
    data-scroll-animation="fade"
    style="--animation-delay: <?= esc_attr($animation_delay); ?>s">
    <a
        class="project-item__link"
        href="<?= esc_url(get_permalink()); ?>">

        <?php if (has_post_thumbnail()): ?>

            <?php the_post_thumbnail(
                'large',
                [
                    'class' => 'project-item__image',
                    'alt'   => get_the_title(),
                ]
            ); ?>

        <?php else: ?>

            <img
                class="project-item__image wp-post-image"
                src="<?= esc_url(
                            get_stylesheet_directory_uri() . '/imgs/svg/placeholder.svg'
                        ); ?>"
                alt="<?= esc_attr(get_the_title()); ?>">

        <?php endif; ?>

        <div class="project-item__content">

            <h3 class="project-item__title">
                <?= esc_html(get_the_title()); ?>
            </h3>

            <?php if (has_excerpt()): ?>

                <div class="project-item__desc">
                    <?= esc_html(get_the_excerpt()); ?>
                </div>

            <?php endif; ?>

        </div>

    </a>
</div>