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
        <div class="projects-tags" data-projects-filter>

            <button
                type="button"
                class="project-tag active"
                data-project-tag="all">
                Все проекты
            </button>

            <?php
            $project_tags = get_terms([
                'taxonomy'   => 'project_tag',
                'hide_empty' => true,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (!is_wp_error($project_tags) && $project_tags):
                foreach ($project_tags as $tag):
            ?>

                    <button
                        type="button"
                        class="project-tag"
                        data-project-tag="<?= esc_attr($tag->term_id); ?>">
                        <?= esc_html($tag->name); ?>
                    </button>

            <?php
                endforeach;
            endif;
            ?>

        </div>

        <div class="projects-list" data-projects-list>

            <?php if (have_posts()): ?>

                <?php while (have_posts()): the_post(); ?>

                    <?php
                    get_template_part(
                        'sections/projects/project-item'
                    );
                    ?>

                <?php endwhile; ?>

            <?php endif; ?>

        </div>

        <?php get_template_part('sections/projects/projects-cta') ?>

    </div>

</section>
<?php get_template_part('sections/contacts') ?>
<?php get_footer() ?>