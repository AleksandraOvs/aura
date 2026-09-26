<?php

/**
 * Template Name: Категории товаров
 */

defined('ABSPATH') || exit;

get_header();

?>

<main class="categories-page">

    <div class="container">

        <h1 class="categories-page__title">
            <?php the_title(); ?>
        </h1>

        <?php

        $categories = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'menu_order',
            'order'      => 'ASC',
        ]);

        if (! empty($categories) && ! is_wp_error($categories)) :

        ?>

            <ul class="categories-tree">

                <?php foreach ($categories as $category) : ?>

                    <li class="categories-tree__item">

                        <a
                            class="categories-tree__link"
                            href="<?php echo esc_url(get_term_link($category)); ?>">
                            <?php echo esc_html($category->name); ?>
                        </a>

                        <?php

                        $children = get_terms([
                            'taxonomy'   => 'product_cat',
                            'hide_empty' => true,
                            'parent'     => $category->term_id,
                            'orderby'    => 'menu_order',
                            'order'      => 'ASC',
                        ]);

                        if (! empty($children) && ! is_wp_error($children)) :

                        ?>

                            <ul class="categories-tree__children">

                                <?php foreach ($children as $child) : ?>

                                    <li class="categories-tree__item">

                                        <a
                                            class="categories-tree__link"
                                            href="<?php echo esc_url(get_term_link($child)); ?>">
                                            <?php echo esc_html($child->name); ?>
                                        </a>

                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        <?php endif; ?>

                    </li>

                <?php endforeach; ?>

            </ul>

        <?php else : ?>

            <p>Категории товаров не найдены.</p>

        <?php endif; ?>

    </div>

</main>

<?php

get_footer();
