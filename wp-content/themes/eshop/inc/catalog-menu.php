<?php

/**
 * -----------------------------------------------------
 * Админка: раздел «Каталог»
 * -----------------------------------------------------
 */

add_action('admin_menu', 'aura_register_catalog_menu');

function aura_register_catalog_menu()
{

    add_menu_page(
        'Каталог',                  // Заголовок страницы
        'Каталог',                  // Название в меню
        'manage_options',           // Кто может видеть
        'aura-catalog',             // slug страницы
        'aura_catalog_page',        // Callback
        'dashicons-products',       // Иконка
        25                          // Позиция в меню
    );
}


/**
 * -----------------------------------------------------
 * Страница «Каталог»
 * -----------------------------------------------------
 */

/**
 * -----------------------------------------------------
 * Страница «Каталог»
 * -----------------------------------------------------
 */

function aura_catalog_page()
{

    /*
     * Получаем сохранённые настройки.
     */
    $catalog = get_option('aura_catalog', []);


    /*
     * Сохраняем изменения.
     */
    if (
        isset($_POST['aura_catalog_save'])
        && check_admin_referer('aura_catalog_save')
    ) {

        $posted_catalog = isset($_POST['catalog'])
            ? $_POST['catalog']
            : [];

        $new_catalog = [];

        /*
         * Обрабатываем категории.
         */
        foreach ($posted_catalog as $category_id => $category_data) {

            $category_id = absint($category_id);

            if (!$category_id) {
                continue;
            }

            /*
             * Категория включена?
             */
            $enabled = !empty($category_data['enabled'])
                ? 1
                : 0;


            /*
             * Подкатегории.
             */
            $children = [];

            if (!empty($category_data['children'])) {

                foreach (
                    $category_data['children']
                    as $subcategory_id => $subcategory_data
                ) {

                    $subcategory_id = absint($subcategory_id);

                    if (!$subcategory_id) {
                        continue;
                    }

                    $children[$subcategory_id] = [
                        'enabled' => !empty($subcategory_data['enabled']) ? 1 : 0,

                        'order' => isset(
                            $subcategory_data['order']
                        )
                            ? absint(
                                $subcategory_data['order']
                            )
                            : 0,
                    ];
                }
            }


            /*
             * Категория.
             */
            $new_catalog[$category_id] = [
                'enabled'  => $enabled,

                'order'    => isset($category_data['order'])
                    ? absint($category_data['order'])
                    : 0,

                'children' => $children,
            ];
        }


        /*
         * Сохраняем.
         */
        update_option(
            'aura_catalog',
            $new_catalog
        );


        /*
         * Обновляем переменную,
         * чтобы сразу показать актуальные данные.
         */
        $catalog = $new_catalog;


        /*
         * Сообщение об успехе.
         */
        add_settings_error(
            'aura_catalog',
            'aura_catalog_saved',
            'Настройки каталога сохранены.',
            'updated'
        );
    }


    /*
     * Получаем категории WooCommerce.
     */
    $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'parent'     => 0,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

?>

    <div class="wrap">

        <h1>Каталог</h1>

        <p>
            Здесь вы можете выбрать категории и подкатегории товаров, которые <strong>будут отображаться в блоке «Каталог» на главной странице и на странице «Каталог»</strong>.
            Отметьте нужные категории и подкатегории. Если выбрана только подкатегория, её родительская категория автоматически будет использоваться в качестве заголовка блока.
            Порядок выбранных категорий и подкатегорий можно изменить.
        </p>


        <?php settings_errors('aura_catalog'); ?>


        <form method="post">

            <?php
            wp_nonce_field('aura_catalog_save');
            ?>


            <div class="aura-catalog">

                <?php if (
                    $categories
                    && !is_wp_error($categories)
                ): ?>


                    <ul class="aura-catalog__categories">


                        <?php foreach (
                            $categories as $category
                        ): ?>


                            <?php

                            $category_id = $category->term_id;

                            $category_data =
                                isset(
                                    $catalog[$category_id]
                                )
                                ? $catalog[$category_id]
                                : [];

                            $category_enabled =
                                !empty($category_data['enabled']);


                            /*
                             * Подкатегории.
                             */
                            $subcategories = get_terms([
                                'taxonomy'   => 'product_cat',
                                'parent'     => $category_id,
                                'hide_empty' => false,
                                'orderby'    => 'name',
                                'order'      => 'ASC',
                            ]);

                            ?>


                            <li
                                class="aura-catalog__category"
                                data-category-id="<?= esc_attr(
                                                        $category_id
                                                    ); ?>">

                                <div class="aura-catalog__category-row">

                                    <span class="aura-catalog__handle">
                                        ☰
                                    </span>


                                    <label>

                                        <input
                                            type="checkbox"

                                            name="catalog[<?= esc_attr(
                                                                $category_id
                                                            ); ?>][enabled]"

                                            value="1"

                                            <?= checked(
                                                $category_enabled,
                                                true,
                                                false
                                            ); ?>>

                                        <strong>
                                            <?= esc_html(
                                                $category->name
                                            ); ?>
                                        </strong>

                                    </label>


                                    <input
                                        type="hidden"

                                        class="aura-catalog__order"

                                        name="catalog[<?= esc_attr(
                                                            $category_id
                                                        ); ?>][order]"

                                        value="<?= esc_attr(
                                                    $category_data['order']
                                                        ?? 0
                                                ); ?>">

                                </div>


                                <?php if (
                                    $subcategories
                                    && !is_wp_error(
                                        $subcategories
                                    )
                                ): ?>


                                    <ul class="aura-catalog__subcategories">


                                        <?php foreach (
                                            $subcategories as $subcategory
                                        ): ?>


                                            <?php

                                            $subcategory_id =
                                                $subcategory->term_id;


                                            $subcategory_data =
                                                $category_data['children'][$subcategory_id]
                                                ?? [];


                                            $subcategory_enabled =
                                                !empty($subcategory_data['enabled']);

                                            ?>


                                            <li
                                                class="aura-catalog__subcategory"
                                                data-subcategory-id="<?= esc_attr(
                                                                            $subcategory_id
                                                                        ); ?>">

                                                <span class="aura-catalog__handle">
                                                    ☰
                                                </span>


                                                <label>

                                                    <input
                                                        type="checkbox"

                                                        name="catalog[<?= esc_attr(
                                                                            $category_id
                                                                        ); ?>][children][<?= esc_attr(
                                                                                                $subcategory_id
                                                                                            ); ?>][enabled]"

                                                        value="1"

                                                        <?= checked(
                                                            $subcategory_enabled,
                                                            true,
                                                            false
                                                        ); ?>>

                                                    <?= esc_html(
                                                        $subcategory->name
                                                    ); ?>

                                                </label>


                                                <input
                                                    type="hidden"

                                                    class="aura-catalog__order"

                                                    name="catalog[<?= esc_attr(
                                                                        $category_id
                                                                    ); ?>][children][<?= esc_attr(
                                                                                            $subcategory_id
                                                                                        ); ?>][order]"

                                                    value="<?= esc_attr(
                                                                $subcategory_data['order'] ?? 0
                                                            ); ?>">

                                            </li>


                                        <?php endforeach; ?>


                                    </ul>


                                <?php endif; ?>


                            </li>


                        <?php endforeach; ?>


                    </ul>


                <?php else: ?>

                    <p>
                        Категории WooCommerce не найдены.
                    </p>

                <?php endif; ?>


            </div>


            <p class="submit">

                <button
                    type="submit"
                    name="aura_catalog_save"
                    class="button button-primary">
                    Сохранить изменения
                </button>

            </p>


        </form>

    </div>

<?php
}


/**
 * -----------------------------------------------------
 * CSS админки каталога
 * -----------------------------------------------------
 */

add_action(
    'admin_enqueue_scripts',
    'aura_catalog_admin_assets'
);

function aura_catalog_admin_assets($hook)
{

    if ($hook !== 'toplevel_page_aura-catalog') {
        return;
    }

    wp_enqueue_script('jquery-ui-sortable');

    wp_add_inline_style(
        'dashicons',
        '
        .aura-catalog {
            max-width: 900px;
            margin-top: 25px;
        }

        .aura-catalog__categories {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  grid-template-columns: repeat(2,1fr);
  gap: .5em;
}

        .aura-catalog__categories,
        .aura-catalog__subcategories {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .aura-catalog__category {
            background: #fff;
            border: 1px solid #dcdcde;
        }

        .aura-catalog__category-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
        }

        .aura-catalog__subcategories {
            padding: 0 15px 10px 45px;
        }

        .aura-catalog__subcategory {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-top: 1px solid #eee;
        }

        .aura-catalog__handle {
            cursor: grab;
            color: #8c8f94;
            font-size: 16px;
        }

        .aura-catalog__handle:active {
            cursor: grabbing;
        }

        .aura-catalog__subcategory label,
        .aura-catalog__category-row label {
            margin: 0;
        }

        .aura-catalog__category-row strong {
            font-size: 14px;
        }

        .aura-catalog__placeholder {
            height: 45px;
            border: 2px dashed #2271b1;
            margin-bottom: 10px;
        }
        '
    );
}


wp_add_inline_script(
    'jquery-ui-sortable',
    '
    jQuery(function($) {

        /*
         * Сортировка основных категорий.
         */
        $(".aura-catalog__categories").sortable({

            handle: ".aura-catalog__category-row .aura-catalog__handle",

            items: "> .aura-catalog__category",

            placeholder: "aura-catalog__placeholder",

            update: function() {

                $(".aura-catalog__categories > .aura-catalog__category")
                    .each(function(index) {

                        $(this)
                            .find("> .aura-catalog__category-row .aura-catalog__order")
                            .val(index);

                    });

            }

        });


        /*
         * Сортировка подкатегорий.
         */
        $(".aura-catalog__subcategories").sortable({

            handle: ".aura-catalog__handle",

            items: "> .aura-catalog__subcategory",

            placeholder: "aura-catalog__placeholder",

            update: function() {

                $(this)
                    .children(".aura-catalog__subcategory")
                    .each(function(index) {

                        $(this)
                            .find(".aura-catalog__order")
                            .val(index);

                    });

            }

        });

    });
    '
);
