<?php

/**
 * Настройки атрибутов фильтра:
 * - общая страница магазина;
 * - родительские категории товаров.
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Настройки фильтров',
        'Фильтры',
        'manage_woocommerce',
        'cwc-filter-attributes',
        'cwc_render_filter_attributes_settings',
        'dashicons-filter',
        56
    );
});

add_action('admin_init', function () {

    register_setting(
        'cwc_filter_attributes_group',
        'cwc_filter_attributes_shop',
        [
            'type'              => 'array',
            'sanitize_callback' => 'cwc_sanitize_filter_attributes_shop',
        ]
    );

    register_setting(
        'cwc_filter_attributes_group',
        'cwc_filter_attributes_by_category',
        [
            'type'              => 'array',
            'sanitize_callback' => 'cwc_sanitize_filter_attributes_settings',
            'default'           => [],
        ]
    );

    register_setting(
        'cwc_filter_attributes_group',
        'cwc_filter_attributes_common',
        [
            'type'              => 'array',
            'sanitize_callback' => 'cwc_sanitize_filter_attributes_common',
            'default'           => [],
        ]
    );
});

add_action('admin_enqueue_scripts', function ($hook_suffix) {

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
 * Очищаем список выбранных атрибутов.
 */
function cwc_sanitize_filter_attribute_list($selected)
{
    if (!is_array($selected)) {
        return [];
    }

    $attributes = cwc_get_filter_attribute_options();

    $selected = array_filter($selected, 'is_scalar');
    $selected = array_map('strval', $selected);

    return array_values(
        array_intersect(
            $selected,
            array_keys($attributes)
        )
    );
}

/**
 * Сохраняем атрибуты общей страницы магазина.
 */
function cwc_sanitize_filter_attributes_shop($input)
{
    return cwc_sanitize_filter_attribute_list($input);
}

/**
 * Сохраняем общие фильтры для всех категорий.
 */
function cwc_sanitize_filter_attributes_common($input)
{
    return cwc_sanitize_filter_attribute_list($input);
}

/**
 * Сохраняем настройки атрибутов категорий.
 */
function cwc_sanitize_filter_attributes_settings($input)
{
    if (!is_array($input)) {
        return [];
    }

    $categories = cwc_get_filter_parent_categories();
    $clean = [];

    foreach ($categories as $category) {
        $category_id = (string) $category->term_id;
        $selected = $input[$category_id] ?? [];

        $clean[$category_id] = cwc_sanitize_filter_attribute_list(
            $selected
        );
    }

    return $clean;
}

/**
 * Вывод административной страницы.
 */
function cwc_render_filter_attributes_settings()
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $categories = cwc_get_filter_parent_categories();
    $attributes = cwc_get_filter_attribute_options();

    $common_saved = get_option('cwc_filter_attributes_common', []);
    $common_selected = is_array($common_saved)
        ? $common_saved
        : [];

    // Настройки общей страницы магазина.
    $shop_saved = get_option('cwc_filter_attributes_shop', null);
    $shop_is_saved = $shop_saved !== null;

    $shop_selected = $shop_is_saved
        ? (array) $shop_saved
        : array_keys($attributes);

    // Настройки категорий.
    $saved = get_option('cwc_filter_attributes_by_category', []);
    $saved = is_array($saved) ? $saved : [];

?>

    <div class="wrap cwc-settings">

        <div class="cwc-settings__header">
            <h1>Фильтры товаров</h1>
            <p>
                Настройте, какие характеристики товаров будут доступны
                посетителям в фильтрах общего каталога и отдельных категорий.
            </p>
        </div>

        <form method="post" action="options.php" class="cwc-settings__form">

            <?php settings_fields('cwc_filter_attributes_group'); ?>

            <!-- Общие фильтры -->

            <section class="cwc-category-card">

                <div class="cwc-category-card__header">
                    <h2 class="cwc-category-card__title">
                        1. Общие фильтры
                    </h2>

                    <span class="cwc-category-card__count">
                        <?php echo esc_html(count($common_selected) . ' из ' . count($attributes)); ?>
                    </span>
                </div>

                <div class="cwc-category-card__body">

                    <div class="cwc-settings__description">
                        <p>
                            Выберите атрибуты, которые будут отображаться
                            в фильтрах товаров во всех категориях по умолчанию.
                        </p>

                        <p class="cwc-settings__hint">
                            Все индивидуальные фильтры категорий по умолчанию выключены.
                            Отметьте дополнительные атрибуты, которые нужны в конкретной категории.
                            Они будут отображаться вместе с общими фильтрами.
                        </p>
                    </div>

                    <?php if (empty($attributes)) : ?>

                        <p class="cwc-settings__empty">
                            Атрибуты WooCommerce не найдены.
                        </p>

                    <?php else : ?>

                        <input
                            type="checkbox"
                            class="cwc-common-attribute"
                            name="cwc_filter_attributes_common[]"
                            value="<?php echo esc_attr($taxonomy); ?>"
                            <?php checked(in_array($taxonomy, $common_selected, true)); ?>>

                        <div class="cwc-settings__bulk-actions">
                            <button type="button" class="button cwc-select-all">
                                Отметить всё
                            </button>

                            <button type="button" class="button cwc-deselect-all">
                                Снять всё
                            </button>
                        </div>

                        <div class="cwc-attributes-grid">

                            <?php foreach ($attributes as $taxonomy => $label) : ?>

                                <?php
                                $is_common_attribute = in_array(
                                    $taxonomy,
                                    $common_selected,
                                    true
                                );
                                ?>

                                <label
                                    class="cwc-attribute-option"
                                    data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                                    <?php echo $is_common_attribute ? 'hidden' : ''; ?>>

                                    <input
                                        type="checkbox"
                                        class="cwc-category-attribute"
                                        name="cwc_filter_attributes_by_category[<?php echo esc_attr($category_id); ?>][]"
                                        value="<?php echo esc_attr($taxonomy); ?>"
                                        <?php checked(in_array($taxonomy, $selected, true)); ?>
                                        <?php disabled($is_common_attribute); ?>>

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

            <!-- Общая страница магазина -->

            <section class="cwc-category-card cwc-settings__shop-card">

                <div class="cwc-category-card__header">
                    <h2 class="cwc-category-card__title">
                        2. Страница магазина (/shop)
                    </h2>

                    <span class="cwc-category-card__count">
                        <?php
                        echo esc_html(
                            count($shop_selected) . ' из ' . count($attributes)
                        );
                        ?>
                    </span>
                </div>

                <div class="cwc-category-card__body">

                    <div class="cwc-settings__description">
                        <p>
                            Выберите атрибуты, по которым посетители смогут
                            фильтровать товары на общей странице магазина
                            <code>/shop/</code>.
                        </p>

                        <p>
                            Эти настройки действуют только на общей странице
                            каталога и не влияют на настройки отдельных категорий.
                        </p>

                        <p class="cwc-settings__hint">
                            Если снять все галочки и сохранить настройки,
                            фильтры по атрибутам на общей странице магазина
                            отображаться не будут.
                        </p>

                        <?php if (!$shop_is_saved) : ?>
                            <p class="cwc-settings__notice">
                                Настройки ещё не сохранялись. Сейчас отображаются
                                все доступные атрибуты.
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($attributes)) : ?>

                        <p class="cwc-settings__empty">
                            Атрибуты WooCommerce не найдены.
                        </p>

                    <?php else : ?>

                        <input
                            type="hidden"
                            name="cwc_filter_attributes_shop[]"
                            value="">

                        <div class="cwc-settings__bulk-actions">
                            <button type="button" class="button cwc-select-all">
                                Отметить всё
                            </button>

                            <button type="button" class="button cwc-deselect-all">
                                Снять всё
                            </button>
                        </div>
                        <div class="cwc-attributes-grid">

                            <?php foreach ($attributes as $taxonomy => $label) : ?>

                                <label class="cwc-attribute-option">

                                    <input
                                        type="checkbox"
                                        name="cwc_filter_attributes_shop[]"
                                        value="<?php echo esc_attr($taxonomy); ?>"
                                        <?php checked(in_array($taxonomy, $shop_selected, true)); ?>>

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

            <!-- Настройки категорий -->

            <section class="cwc-settings__category-section">

                <div class="cwc-settings__section-heading">
                    <h2>2. Фильтры отдельных категорий</h2>

                    <p>
                        Здесь можно настроить свой набор фильтров для каждой
                        корневой категории товаров.
                    </p>

                    <p>
                        Настройки распространяются также на все вложенные
                        подкатегории. Например, если вы настроите фильтры
                        для категории «Освещение», они будут действовать
                        и в её подкатегориях.
                    </p>

                    <p class="cwc-settings__hint">
                        Если настройки категории ещё не сохранялись,
                        для неё будут отображаться все доступные атрибуты.
                        Если снять все галочки и сохранить, фильтры по атрибутам
                        в этой категории и её подкатегориях отображаться не будут.
                    </p>
                </div>

                <?php if (empty($categories)) : ?>

                    <div class="cwc-settings__empty">
                        Родительские категории товаров не найдены.
                    </div>

                <?php else : ?>

                    <div class="cwc-settings__categories">

                        <?php foreach ($categories as $category) : ?>

                            <?php
                            $category_id = (string) $category->term_id;

                            $selected = array_key_exists($category_id, $saved)
                                ? (array) $saved[$category_id]
                                : [];
                            ?>

                            <details class="cwc-category-card cwc-category-card--accordion">

                                <summary class="cwc-category-card__header">

                                    <h3 class="cwc-category-card__title">
                                        <?php echo esc_html($category->name); ?>
                                    </h3>

                                    <span class="cwc-category-card__count">
                                        <?php
                                        echo esc_html(
                                            count(array_diff($selected, $common_selected))
                                                . ' из ' . (count($attributes) - count($common_selected))
                                        );
                                        ?>
                                    </span>

                                    <span class="cwc-category-card__arrow" aria-hidden="true"></span>

                                </summary>

                                <div class="cwc-category-card__body">

                                    <?php if (empty($attributes)) : ?>

                                        <p class="cwc-settings__empty">
                                            Атрибуты WooCommerce не найдены.
                                        </p>

                                    <?php else : ?>

                                        <div class="cwc-settings__bulk-actions">

                                            <button type="button" class="button cwc-select-all">
                                                Отметить всё
                                            </button>

                                            <button type="button" class="button cwc-deselect-all">
                                                Снять всё
                                            </button>

                                        </div>

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

                            </details>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

            <div class="cwc-settings__footer">
                <?php submit_button('Сохранить настройки', 'primary', 'submit', false); ?>
            </div>

        </form>

    </div>

<?php
}

add_action('admin_footer', function () {
    $screen = get_current_screen();

    if (!$screen || $screen->id !== 'toplevel_page_cwc-filter-attributes') {
        return;
    }
?>
    <script>
        // Скрываем общие атрибуты в индивидуальных настройках категорий.
        function updateCommonAttributes() {

            const commonAttributes = new Set();

            document.querySelectorAll('.cwc-common-attribute:checked')
                .forEach(function(checkbox) {
                    commonAttributes.add(checkbox.value);
                });

            document.querySelectorAll('.cwc-category-attribute')
                .forEach(function(checkbox) {

                    const isCommon = commonAttributes.has(checkbox.value);
                    const label = checkbox.closest('.cwc-attribute-option');

                    checkbox.disabled = isCommon;

                    if (label) {
                        label.hidden = isCommon;
                    }
                });
        }

        // Первоначальная проверка при загрузке страницы.
        updateCommonAttributes();

        // Обновление при изменении общих фильтров.
        document.addEventListener('change', function(event) {

            if (event.target.matches('.cwc-common-attribute')) {
                updateCommonAttributes();
            }

        });

        document.addEventListener('click', function(event) {
            const selectButton = event.target.closest('.cwc-select-all');
            const deselectButton = event.target.closest('.cwc-deselect-all');

            if (!selectButton && !deselectButton) {
                return;
            }

            const button = selectButton || deselectButton;
            const card = button.closest('.cwc-category-card');

            if (!card) {
                return;
            }

            const checked = Boolean(selectButton);

            card.querySelectorAll('.cwc-attribute-option input[type="checkbox"]')
                .forEach(function(checkbox) {
                    checkbox.checked = checked;
                });
        });
    </script>
<?php
});
