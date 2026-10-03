<?php

/**
 * Template Name: Категории товаров
 */

defined('ABSPATH') || exit;


/**
 * Рекурсивный вывод дерева категорий товаров.
 *
 * @param int $parent_id ID родительской категории.
 */
function aura_product_categories_tree($parent_id = 0)
{
    $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $parent_id,
        'orderby'    => 'menu_order',
        'order'      => 'ASC',
    ]);

    if (empty($categories) || is_wp_error($categories)) {
        return;
    }

?>

    <ul class="<?php echo $parent_id === 0 ? 'categories-tree' : 'categories-tree__children'; ?>">

        <?php foreach ($categories as $category) : ?>

            <li class="categories-tree__item">

                <a
                    class="categories-tree__link<?php echo $parent_id === 0 ? ' parent-cat' : ''; ?>"
                    href="<?php echo esc_url(get_term_link($category)); ?>">
                    <span class="categories-tree__name">
                        <?php echo esc_html($category->name); ?>
                    </span>

                    <span class="categories-tree__count">
                        <?php echo esc_html($category->count); ?>
                    </span>
                </a>

                <?php
                // Рекурсивно выводим дочерние категории.
                aura_product_categories_tree($category->term_id);
                ?>

            </li>

        <?php endforeach; ?>

    </ul>

<?php
}


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

            aura_product_categories_tree();

            ?>

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


    /* =========================================================
       Main tree
       ========================================================= */

    .categories-tree {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 40px 30px;

        margin: 0;
        padding: 0;

        list-style: none;
    }


    /* =========================================================
       Items
       ========================================================= */

    .categories-tree__item {
        position: relative;

        margin: 0;
        padding: 7px 0;
    }


    /* =========================================================
       Nested tree
       ========================================================= */

    .categories-tree__children {
        margin: 10px 0 0 20px;
        padding: 0 0 0 24px;

        list-style: none;

        border-left: 1px solid #d9d9d9;
    }


    /* =========================================================
       Connection line
       ========================================================= */

    .categories-tree__children>.categories-tree__item::before {
        content: '';

        position: absolute;
        top: 22px;
        left: -24px;

        width: 18px;
        height: 1px;

        background: #d9d9d9;
    }


    /* =========================================================
       Links
       ========================================================= */

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


    /* =========================================================
       Top-level categories
       ========================================================= */

    .categories-tree>.categories-tree__item {
        padding: 14px 0;
    }

    .categories-tree>.categories-tree__item>.categories-tree__link {
        color: #a69469;

        font-size: 18px;
        font-weight: 700;
    }


    /* =========================================================
       Nested categories
       ========================================================= */

    .categories-tree__children .categories-tree__link {
        font-size: 14px;
    }


    /* =========================================================
       Product count
       ========================================================= */

    .categories-tree__count {
        color: #888;

        font-size: 13px;
        font-weight: 400;
        line-height: 1;
    }


    /* =========================================================
       Deeper levels
       ========================================================= */

    .categories-tree__children .categories-tree__children {
        margin-top: 5px;
    }


    /* =========================================================
       Mobile
       ========================================================= */

    @media (max-width: 767px) {

        .categories-page {
            padding: 40px 0 60px;
        }

        .categories-tree {
            grid-template-columns: repeat(2, 1fr);
            gap: 30px 20px;
        }

        .categories-tree__children {
            margin-left: 10px;
            padding-left: 18px;
        }

        .categories-tree__children>.categories-tree__item::before {
            left: -18px;
            width: 12px;
        }

        .categories-tree>.categories-tree__item>.categories-tree__link {
            font-size: 17px;
        }

        .categories-tree__children .categories-tree__link {
            font-size: 14px;
        }

    }


    @media (max-width: 480px) {

        .categories-tree {
            grid-template-columns: 1fr;
        }

    }
</style>


<?php

get_footer();
