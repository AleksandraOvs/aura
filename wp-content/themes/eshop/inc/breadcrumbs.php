<?php

/**
 * -----------------------------------------------------
 * Хлебные крошки
 * -----------------------------------------------------
 */
function site_breadcrumbs()
{
    $page_num = get_query_var('paged') ? get_query_var('paged') : 1;

    echo '<div class="site-breadcrumbs">';


    /*
     * Главная страница
     */
    if (is_front_page()) {

        if ($page_num > 1) {

            echo '<a href="' . esc_url(home_url('/')) . '">Главная</a>';
            echo '<span class="separator"></span>';
            echo '<span>' . esc_html($page_num) . '-page</span>';
        } else {

            echo '<span>Главная</span>';
        }

        echo '</div>';

        return;
    }


    /*
     * Главная
     */
    echo '<a href="' . esc_url(home_url('/')) . '">Главная</a>';
    echo '<span class="separator"></span>';


    /*
     * Одиночная запись / страница
     */
    if (is_singular()) {

        $post_type     = get_post_type();
        $post_type_obj = get_post_type_object($post_type);


        /*
         * CPT "Проекты"
         *
         * Главная → Наши проекты → Название проекта
         *
         * Категории проектов здесь НЕ выводятся.
         */
        if ($post_type === 'projects') {

            $archive_link = get_post_type_archive_link('projects');

            if ($archive_link) {

                echo '<a href="' . esc_url($archive_link) . '">'
                    . 'Наши проекты'
                    . '</a>';

                echo '<span class="separator"></span>';
            }

            echo '<span>'
                . esc_html(get_the_title())
                . '</span>';
        }


        /*
         * Обычная запись WordPress
         */ elseif ($post_type === 'post') {

            $primary_cat = null;


            // Основная категория Yoast
            if (class_exists('WPSEO_Primary_Term')) {

                $wpseo_primary_term = new WPSEO_Primary_Term(
                    'category',
                    get_the_ID()
                );

                $primary_cat_id = $wpseo_primary_term->get_primary_term();

                if ($primary_cat_id) {
                    $primary_cat = get_category($primary_cat_id);
                }
            }


            // Если основной категории нет — берём первую
            if (!$primary_cat) {

                $categories = get_the_category();

                if (!empty($categories)) {
                    $primary_cat = $categories[0];
                }
            }


            // Вывод категорий записи
            if ($primary_cat) {

                $parents = get_ancestors(
                    $primary_cat->term_id,
                    'category'
                );

                $parents = array_reverse($parents);

                foreach ($parents as $parent_id) {

                    $parent = get_category($parent_id);

                    if (!$parent) {
                        continue;
                    }

                    echo '<a href="' . esc_url(
                        get_category_link($parent->term_id)
                    ) . '">'
                        . esc_html($parent->name)
                        . '</a>';

                    echo '<span class="separator"></span>';
                }

                echo '<a href="' . esc_url(
                    get_category_link($primary_cat->term_id)
                ) . '">'
                    . esc_html($primary_cat->name)
                    . '</a>';

                echo '<span class="separator"></span>';
            }


            echo '<span>'
                . esc_html(get_the_title())
                . '</span';
        }


        /*
         * WooCommerce товар
         */ elseif ($post_type === 'product') {

            $archive_link = get_post_type_archive_link('product');

            echo '<a href="' . esc_url($archive_link) . '">Каталог</a>';
            echo '<span class="separator"></span>';


            $terms = get_the_terms(
                get_the_ID(),
                'product_cat'
            );

            if ($terms && !is_wp_error($terms)) {

                $term = $terms[0];

                $ancestors = get_ancestors(
                    $term->term_id,
                    'product_cat'
                );

                if ($ancestors) {

                    $ancestors = array_reverse($ancestors);

                    foreach ($ancestors as $ancestor_id) {

                        $ancestor = get_term(
                            $ancestor_id,
                            'product_cat'
                        );

                        if (!$ancestor || is_wp_error($ancestor)) {
                            continue;
                        }

                        echo '<a href="' . esc_url(
                            get_term_link($ancestor)
                        ) . '">'
                            . esc_html($ancestor->name)
                            . '</a>';

                        echo '<span class="separator"></span>';
                    }
                }

                echo '<a href="' . esc_url(
                    get_term_link($term)
                ) . '">'
                    . esc_html($term->name)
                    . '</a>';

                echo '<span class="separator"></span>';
            }


            echo '<span>'
                . esc_html(get_the_title())
                . '</span';
        }


        /*
         * Остальные CPT
         */ elseif (
            $post_type_obj instanceof WP_Post_Type
            && !empty($post_type_obj->has_archive)
        ) {

            $archive_title = $post_type_obj->labels->name;
            $archive_link  = get_post_type_archive_link($post_type);

            if ($archive_link) {

                echo '<a href="' . esc_url($archive_link) . '">'
                    . esc_html($archive_title)
                    . '</a>';

                echo '<span class="separator"></span>';
            }

            echo '<span>'
                . esc_html(get_the_title())
                . '</span>';
        }


        /*
         * Обычная страница
         */ else {

            echo '<span>'
                . esc_html(get_the_title())
                . '</span>';
        }


        /*
     * Архив проектов
     */
    } elseif (is_post_type_archive('projects')) {

        echo '<span>Наши проекты</span>';


        /*
     * Категория WooCommerce
     */
    } elseif (is_product_category()) {

        $term = get_queried_object();

        if ($term) {

            echo '<a href="' . esc_url(
                get_post_type_archive_link('product')
            ) . '">Каталог</a>';

            echo '<span class="separator"></span>';


            $ancestors = get_ancestors(
                $term->term_id,
                'product_cat'
            );

            if ($ancestors) {

                $ancestors = array_reverse($ancestors);

                foreach ($ancestors as $ancestor_id) {

                    $ancestor = get_term(
                        $ancestor_id,
                        'product_cat'
                    );

                    if (!$ancestor || is_wp_error($ancestor)) {
                        continue;
                    }

                    echo '<a href="' . esc_url(
                        get_term_link($ancestor)
                    ) . '">'
                        . esc_html($ancestor->name)
                        . '</a>';

                    echo '<span class="separator"></span>';
                }
            }

            echo '<span>'
                . esc_html($term->name)
                . '</span>';
        }


        /*
     * Остальные архивы CPT
     */
    } elseif (is_post_type_archive()) {

        $post_type = get_query_var('post_type');

        if (is_array($post_type)) {
            $post_type = reset($post_type);
        }

        $post_type_obj = get_post_type_object($post_type);

        if ($post_type_obj) {

            echo '<span>'
                . esc_html($post_type_obj->labels->name)
                . '</span>';
        }


        /*
     * Страница
     */
    } elseif (is_page()) {

        echo '<span>'
            . esc_html(get_the_title())
            . '</span>';


        /*
     * Метка
     */
    } elseif (is_tag()) {

        echo '<span>'
            . esc_html(single_tag_title('', false))
            . '</span>';


        /*
     * 404
     */
    } elseif (is_404()) {

        echo '<span>Ошибка 404</span>';
    }


    /*
     * Пагинация
     */
    if ($page_num > 1) {

        echo '<span class="separator"></span>';

        echo '<span>'
            . esc_html($page_num)
            . '-page'
            . '</span>';
    }


    echo '</div>';
}
