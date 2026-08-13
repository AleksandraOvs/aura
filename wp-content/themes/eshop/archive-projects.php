<?php get_header() ?>

<section class="page-title-block">
    <div class="fixed-container">
        <?php site_breadcrumbs() ?>

        <h1 class="page-title" data-scroll-animation="fade-down">
            <?= esc_html(post_type_archive_title('', false)); ?>
        </h1>
        <div class="page-title__description">
            <p>
                Мы в "Аура света" заботимся о том, чтобы вы получили свою
                покупку в наилучшем виде и в удобное для вас время. После того,
                как ваш заказ будет полностью оплачен и тщательно проверен, мы
                передаем его в надежные руки транспортных компаний. Мы
                используем проверенные службы доставки, чтобы товар довезли в
                целости и вы могли отслеживать его на каждом этапе пути.
            </p>
        </div>
    </div>
</section>

<section class="projects">
    <div class="container">
        <div class="projects-tags">
            <a href="/" class="project-tag active">HoReCa</a>
            <a href="/" class="project-tag">Жилой комплекс</a>
            <a href="/" class="project-tag">Загородный дом</a>
            <a href="/" class="project-tag">Загородный дом</a>
            <a href="/" class="project-tag">Квартира</a>
            <a href="/" class="project-tag">Коммерческое помещение</a>
            <a href="/" class="project-tag">Культурное наследие</a>
            <a href="/" class="project-tag">Ландшафт</a>
            <a href="/" class="project-tag">Офис</a>
            <a href="/" class="project-tag">Частный дом</a>
        </div>

        <div class="projects-list">

            <?php if (have_posts()): ?>

                <?php
                $animation_delay = 0.1;
                ?>

                <?php while (have_posts()): the_post(); ?>

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

                    <?php
                    $animation_delay += 0.1;

                    // После 1 секунды начинаем задержку заново
                    if ($animation_delay > 1) {
                        $animation_delay = 0.1;
                    }
                    ?>

                <?php endwhile; ?>

            <?php endif; ?>

        </div>
    </div>
</section>

<?php get_footer() ?>