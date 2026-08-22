<?php

/**
 * -----------------------------------------------------
 * Блок «Каталог»
 * -----------------------------------------------------
 *
 * Категории и подкатегории выбираются
 * в админке → Каталог.
 *
 * Данные хранятся в:
 * aura_catalog
 * -----------------------------------------------------
 */

$catalog = get_option('aura_catalog', []);


/*
 * Если каталог пуст — ничего не выводим.
 */
if (!empty($catalog) && is_array($catalog)):

    /*
     * Сортируем родительские категории
     * по сохранённому порядку.
     */
    uasort($catalog, function ($a, $b) {

        return (
            (int) ($a['order'] ?? 0)
            <=>
            (int) ($b['order'] ?? 0)
        );
    });


    /*
     * Флаг первого списка.
     *
     * Только первый UL получит
     * дополнительный класс _grid-template.
     */
    $is_first_list = true;

?>

    <section class="products-categories <?php echo is_shop() ? 'pt-0' : ''; ?>">

        <div class="fixed-container">

            <?php foreach ($catalog as $category_id => $category_data): ?>

                <?php

                $category_id = absint($category_id);

                if (!$category_id) {
                    continue;
                }


                /*
             * Получаем родительскую категорию WooCommerce.
             */
                $category = get_term(
                    $category_id,
                    'product_cat'
                );


                if (
                    !$category ||
                    is_wp_error($category)
                ) {
                    continue;
                }


                /*
             * Получаем подкатегории,
             * сохранённые в админке.
             */
                $children = $category_data['children'] ?? [];


                if (!is_array($children)) {
                    $children = [];
                }


                /*
             * Оставляем только включённые
             * подкатегории.
             */
                $selected_subcategories = [];


                foreach ($children as $subcategory_id => $subcategory_data) {

                    if (
                        empty($subcategory_data['enabled'])
                    ) {
                        continue;
                    }


                    $subcategory_id = absint(
                        $subcategory_id
                    );


                    if (!$subcategory_id) {
                        continue;
                    }


                    /*
                 * Получаем термин.
                 */
                    $subcategory = get_term(
                        $subcategory_id,
                        'product_cat'
                    );


                    if (
                        !$subcategory ||
                        is_wp_error($subcategory)
                    ) {
                        continue;
                    }


                    /*
                 * Защита от ошибочной структуры:
                 *
                 * убеждаемся, что это действительно
                 * подкатегория текущей категории.
                 */
                    if (
                        (int) $subcategory->parent !==
                        (int) $category_id
                    ) {
                        continue;
                    }


                    $selected_subcategories[] = [
                        'term'  => $subcategory,
                        'order' => (int) (
                            $subcategory_data['order'] ?? 0
                        ),
                    ];
                }


                /*
             * Сортируем подкатегории
             * по порядку из админки.
             */
                usort(
                    $selected_subcategories,
                    function ($a, $b) {

                        return $a['order']
                            <=>
                            $b['order'];
                    }
                );


                /*
             * ВАЖНО:
             *
             * Родительская категория выводится,
             * если:
             *
             * 1. сама категория включена
             * ИЛИ
             *
             * 2. у неё есть включённые
             *    подкатегории.
             */
                $category_enabled = !empty($category_data['enabled']);


                if (
                    !$category_enabled &&
                    empty($selected_subcategories)
                ) {
                    continue;
                }

                ?>


                <!-- Заголовок родительской категории -->

                <h2 class="small-heading">
                    <?= esc_html($category->name); ?>
                </h2>


                <?php if (!empty($selected_subcategories)): ?>

                    <ul class="products-categories__list<?= $is_first_list ? ' _grid-template' : ''; ?>">

                        <?php foreach (
                            $selected_subcategories
                            as $index => $item
                        ): ?>

                            <?php

                            $subcategory =
                                $item['term'];


                            /*
                         * Ссылка на страницу
                         * категории WooCommerce.
                         */
                            $subcategory_link =
                                get_term_link(
                                    $subcategory,
                                    'product_cat'
                                );


                            if (is_wp_error(
                                $subcategory_link
                            )) {
                                $subcategory_link = '#';
                            }


                            /*
                         * Задержка анимации.
                         */
                            $delay = $index * 0.15;


                            /*
                         * Получаем изображение
                         * категории WooCommerce.
                         */
                            $thumbnail_id =
                                get_term_meta(
                                    $subcategory->term_id,
                                    'thumbnail_id',
                                    true
                                );

                            ?>


                            <li
                                class="products-categories__list__item"
                                data-scroll-animation="fade-up"
                                style="--animation-delay: <?= esc_attr($delay); ?>s;">

                                <a
                                    href="<?= esc_url($subcategory_link); ?>"
                                    class="products-categories__list__link">

                                    <?php if ($thumbnail_id): ?>

                                        <?= wp_get_attachment_image(
                                            $thumbnail_id,
                                            'large',
                                            false,
                                            [
                                                'alt' =>
                                                $subcategory->name,
                                            ]
                                        ); ?>

                                    <?php endif; ?>


                                    <h3 class="products-categories__list__item__title">

                                        <?= esc_html(
                                            $subcategory->name
                                        ); ?>

                                    </h3>

                                </a>

                            </li>


                        <?php endforeach; ?>

                    </ul>

                <?php endif; ?>


                <?php

                /*
             * После первого выведенного списка
             * следующие UL уже не получают
             * _grid-template.
             */
                $is_first_list = false;

                ?>


            <?php endforeach; ?>

        </div>

    </section>

<?php endif; ?>