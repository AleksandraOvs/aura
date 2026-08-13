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

        <?php
        $animation_delay = 0.1;
        ?>
        <div class="projects-list">

            <?php if (have_posts()): ?>


                <?php while (have_posts()): the_post(); ?>

                    <?php
                    get_template_part(
                        'sections/projects/project-item',
                        null,
                        [
                            'animation_delay' => $animation_delay,
                        ]
                    );

                    $animation_delay += 0.1;

                    if ($animation_delay > 1) {
                        $animation_delay = 0.1;
                    }
                    ?>



                <?php endwhile; ?>

            <?php endif; ?>

        </div>

        <?php get_template_part('sections/projects/projects-cta') ?>

    </div>

</section>
<?php get_template_part('sections/contacts') ?>
<?php get_footer() ?>