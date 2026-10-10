<?php

/**
 * Настройки атрибутов фильтра по родительским категориям.
 */

add_action('admin_menu', function () {
    add_menu_page(
        'Настройки фильтров',                  // Заголовок страницы
        'Фильтры',                             // Название в основном меню
        'manage_woocommerce',                  // Необходимые права
        'cwc-filter-attributes',               // Уникальный slug
        'cwc_render_filter_attributes_settings', // Функция вывода страницы
        'dashicons-filter',                    // Иконка меню
        56                                     // Позиция в меню
    );
});

add_action('admin_init', function () {
    register_setting(
        'cwc_filter_attributes_group',
        'cwc_filter_attributes_by_category',
        [
            'type'              => 'array',
            'sanitize_callback' => 'cwc_sanitize_filter_attributes_settings',
            'default'           => [],
        ]
    );
});

add_action('admin_enqueue_scripts', function ($hook_suffix) {

    // Загружаем CSS только на странице «Фильтры».
    if ($hook_suffix !== 'toplevel_page_cwc-filter-attributes') {
        return;
    }

    $css_file = plugin_dir_path(__FILE__) . 'css/admin-settings.css';

    wp_enqueue_style(
        'cwc-admin-settings',
        plugin_dir_url(__FILE__) . 'css/admin-settings.css',
        [],
        file_exists($css_file) ? filemtime($css_file) : '1.0.0'
    );
});

/**
 * Получаем доступные атрибуты WooCommerce.
 */
function cwc_get_filter_attribute_options()
{
    $attributes = [];

    foreach (wc_get_attribute_taxonomies() as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        $attributes[$taxonomy] = $attribute->attribute_label;
    }

    return $attributes;
}

/**
 * Получаем родительские категории товаров.
 */
function cwc_get_filter_parent_categories()
{
    $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'parent'     => 0,
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    return is_wp_error($categories) ? [] : $categories;
}

/**
 * Проверяем и очищаем сохранённые настройки.
 */
function cwc_sanitize_filter_attributes_settings($input)
{
    if (!is_array($input)) {
        return [];
    }

    $attributes = cwc_get_filter_attribute_options();
    $categories = cwc_get_filter_parent_categories();
    $clean      = [];

    foreach ($categories as $category) {
        $category_id = (string) $category->term_id;
        $selected    = $input[$category_id] ?? [];

        if (!is_array($selected)) {
            $selected = [];
        }

        $clean[$category_id] = array_values(
            array_intersect(
                array_map('sanitize_key', $selected),
                array_keys($attributes)
            )
        );
    }

    return $clean;
}

/**
 * Страница настроек в админке.
 */
function cwc_render_filter_attributes_settings()
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $categories = cwc_get_filter_parent_categories();
    $attributes = cwc_get_filter_attribute_options();

    $saved = get_option('cwc_filter_attributes_by_category', []);
    $saved = is_array($saved) ? $saved : [];

?>
    <div class="wrap cwc-settings">

        <div class="cwc-settings__header">
            <h1>Фильтры товаров</h1>
            <p>
                Выберите атрибуты, которые будут отображаться
                в фильтрах каждой родительской категории.
                Настройки также применяются ко всем вложенным категориям.
            </p>
        </div>

        <form method="post" action="options.php" class="cwc-settings__form">

            <?php settings_fields('cwc_filter_attributes_group'); ?>

            <?php if (empty($categories)) : ?>

                <div class="cwc-settings__empty">
                    Родительские категории товаров не найдены.
                </div>

            <?php else : ?>

                <div class="cwc-settings__categories">

                    <?php foreach ($categories as $category) : ?>

                        <?php
                        $category_id = (string) $category->term_id;

                        // Если настройки ещё не сохранялись,
                        // считаем включёнными все атрибуты.
                        $selected = array_key_exists($category_id, $saved)
                            ? (array) $saved[$category_id]
                            : array_keys($attributes);
                        ?>

                        <section class="cwc-category-card">

                            <div class="cwc-category-card__header">
                                <h2 class="cwc-category-card__title">
                                    <?php echo esc_html($category->name); ?>
                                </h2>

                                <span class="cwc-category-card__count">
                                    <?php
                                    echo esc_html(
                                        count($selected) . ' из ' . count($attributes)
                                    );
                                    ?>
                                </span>
                            </div>

                            <div class="cwc-category-card__body">

                                <?php if (empty($attributes)) : ?>

                                    <p class="cwc-settings__empty">
                                        Атрибуты WooCommerce не найдены.
                                    </p>

                                <?php else : ?>

                                    <input
                                        type="hidden"
                                        name="cwc_filter_attributes_by_category[<?php echo esc_attr($category_id); ?>][]"
                                        value="">

                                    <div class="cwc-attributes-grid">

                                        <?php foreach ($attributes as $taxonomy => $label) : ?>

                                            <label class="cwc-attribute-option">

                                                <input
                                                    type="checkbox"
                                                    name="cwc_filter_attributes_by_category[<?php echo esc_attr($category_id); ?>][]"
                                                    value="<?php echo esc_attr($taxonomy); ?>"
                                                    <?php checked(in_array($taxonomy, $selected, true)); ?>>

                                                <span class="cwc-attribute-option__content">
                                                    <span class="cwc-attribute-option__label">
                                                        <?php echo esc_html($label); ?>
                                                    </span>

                                                    <code class="cwc-attribute-option__taxonomy">
                                                        <?php echo esc_html($taxonomy); ?>
                                                    </code>
                                                </span>

                                            </label>

                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                            </div>

                        </section>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <div class="cwc-settings__footer">
                <?php submit_button('Сохранить настройки', 'primary', 'submit', false); ?>
            </div>

        </form>

    </div>
<?php
}
