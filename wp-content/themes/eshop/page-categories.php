<?php

/**
 * Template Name: Категории товаров
 */

defined('ABSPATH') || exit;

get_header();

?>

<main class="categories-page">

    <section class="page-title-block">

        <div class="fixed-container">

            <?php site_breadcrumbs(); ?>

            <h1 class="page-title" data-scroll-animation="fade-down">
                <?= esc_html(get_the_title()); ?>
            </h1>

        </div>

    </section>

    <section class="page-content">
        <div class="container">
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
                                class="categories-tree__link parent-cat"
                                href="<?php echo esc_url(get_term_link($category)); ?>">
                                <?php echo esc_html($category->name); ?>
                            </a>

                            <?php

                            $children = get_terms([
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => false,
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
    </section>

</main>

<style>
    /* =========================================================
   Categories page
   ========================================================= */

    .categories-page {
        padding: 60px 0 80px;
    }

    .categories-page__title {
        margin: 0 0 40px;
    }


    /* Tree */

    .categories-tree {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .categories-tree {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2em;
    }

    .categories-tree .categories-tree {
        margin-top: 10px;
        margin-left: 20px;
        padding-left: 24px;
        border-left: 1px solid #d9d9d9;
    }


    /* Item */

    .categories-tree__item {
        position: relative;
        margin: 0;
        padding: 7px 0;
    }


    /* Horizontal line to child */

    .categories-tree .categories-tree .categories-tree__item::before {
        content: '';
        position: absolute;
        top: 22px;
        left: -24px;
        width: 18px;
        height: 1px;
        background: #d9d9d9;
    }


    /* Links */

    .categories-tree__link.parent-cat {
        font-size: 18px;
        font-weight: 700;
        color: #a69469;
    }

    .categories-tree__link {
        display: inline-flex;
        align-items: center;
        gap: 8px;

        color: #222;
        font-size: 14px;
        line-height: 1.4;
        text-decoration: none;

        transition:
            color 0.2s ease,
            transform 0.2s ease;
    }

    .categories-tree__link:hover {
        color: #614881;
        transform: translateX(3px);
    }


    /* Top-level categories */

    .categories-page__tree>.categories-tree>.categories-tree__item {
        padding: 14px 0;
    }

    .categories-page__tree>.categories-tree>.categories-tree__item>.categories-tree__link {
        font-size: 22px;
        font-weight: 600;
    }


    /* Nested categories */

    .categories-tree .categories-tree .categories-tree__link {
        font-size: 17px;
    }


    /* Mobile */

    @media (max-width: 767px) {

        .categories-tree {
            grid-template-columns: repeat(2, 1fr);
        }


        .categories-page {
            padding: 40px 0 60px;
        }

        .categories-page__title {
            margin-bottom: 30px;
        }

        .categories-tree .categories-tree {
            margin-left: 10px;
            padding-left: 18px;
        }

        .categories-tree .categories-tree .categories-tree__item::before {
            left: -18px;
            width: 12px;
        }

        .categories-page__tree>.categories-tree>.categories-tree__item>.categories-tree__link {
            font-size: 19px;
        }

        .categories-tree .categories-tree .categories-tree__link {
            font-size: 16px;
        }

    }
</style>
<?php

get_footer();
