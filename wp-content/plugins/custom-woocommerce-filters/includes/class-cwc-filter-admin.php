<?php

/**
 * Административные настройки типов отображения атрибутов фильтра.
 *
 * Подключается из основного файла плагина Custom WooCommerce Filters.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWC_Filter_Admin_Display_Settings')) {
    class CWC_Filter_Admin_Display_Settings
    {
        const OPTION_NAME = 'cwc_filter_display_settings';
        const ORDER_OPTION_NAME = 'cwc_filter_display_order';
        const PAGE_SLUG   = 'cwc-filter-display-settings';

        /** Типы, доступные для выбора в настройках. */
        public static function get_types()
        {
            return [
                'legacy'    => 'Как сейчас (обычный список)',
                'checkbox'  => 'Чекбоксы',
                'radio'     => 'Радиокнопки',
                'range'     => 'Числовой диапазон',
                'intervals' => 'Предустановленные интервалы',
                'color'     => 'Цветные элементы',
            ];
        }

        public static function get_filter_order()
        {
            $order = get_option(self::ORDER_OPTION_NAME, []);

            return is_array($order) ? $order : [];
        }

        public static function sanitize_filter_order($input)
        {
            $attributes = self::get_attributes();
            $input = is_array($input) ? $input : [];
            $order = [];

            // Сохраняем только существующие таксономии, без дубликатов.
            foreach ($input as $taxonomy) {
                $taxonomy = sanitize_key($taxonomy);

                if (
                    isset($attributes[$taxonomy]) &&
                    !in_array($taxonomy, $order, true)
                ) {
                    $order[] = $taxonomy;
                }
            }

            // Новые атрибуты автоматически добавляются в конец списка.
            foreach (array_keys($attributes) as $taxonomy) {
                if (!in_array($taxonomy, $order, true)) {
                    $order[] = $taxonomy;
                }
            }

            return $order;
        }

        /** Получить актуальный список глобальных атрибутов WooCommerce. */
        public static function get_attributes()
        {
            if (!function_exists('wc_get_attribute_taxonomies')) {
                return [];
            }

            $attributes = wc_get_attribute_taxonomies();
            $result = [];

            foreach ((array) $attributes as $attribute) {
                if (empty($attribute->attribute_name)) {
                    continue;
                }

                $taxonomy = function_exists('wc_attribute_taxonomy_name')
                    ? wc_attribute_taxonomy_name($attribute->attribute_name)
                    : 'pa_' . $attribute->attribute_name;

                if (!taxonomy_exists($taxonomy)) {
                    continue;
                }

                $result[$taxonomy] = [
                    'taxonomy' => $taxonomy,
                    'label'    => !empty($attribute->attribute_label)
                        ? $attribute->attribute_label
                        : $attribute->attribute_name,
                ];
            }

            return $result;
        }

        public static function get_settings()
        {
            $settings = get_option(self::OPTION_NAME, []);
            return is_array($settings) ? $settings : [];
        }

        public static function init()
        {
            add_action('admin_menu', [__CLASS__, 'register_submenu'], 20);
            add_action('admin_init', [__CLASS__, 'register_setting']);
            add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
            register_setting(
                'cwc_filter_display_settings_group',
                self::ORDER_OPTION_NAME,
                [
                    'type'              => 'array',
                    'sanitize_callback' => [__CLASS__, 'sanitize_filter_order'],
                    'default'           => [],
                ]
            );
        }

        public static function register_submenu()
        {
            // Родительская страница создаётся существующим admin-settings.php.
            add_submenu_page(
                'cwc-filter-attributes',
                'Типы отображения фильтров',
                'Типы отображения',
                'manage_woocommerce',
                self::PAGE_SLUG,
                [__CLASS__, 'render_page']
            );
        }

        public static function register_setting()
        {
            register_setting(
                'cwc_filter_display_settings_group',
                self::OPTION_NAME,
                [
                    'type'              => 'array',
                    'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
                    'default'           => [],
                ]
            );
        }

        /**
         * Валидируем только актуальные атрибуты и поддерживаемые типы.
         * Если тип не сохранён, фронтенд должен использовать старый вывод.
         */
        public static function sanitize_settings($input)
        {
            $attributes = self::get_attributes();
            $types = self::get_types();
            $input = is_array($input) ? $input : [];
            $clean = [];

            foreach ($attributes as $taxonomy => $attribute) {
                $row = isset($input[$taxonomy]) && is_array($input[$taxonomy])
                    ? $input[$taxonomy]
                    : [];

                $type = isset($row['type']) ? sanitize_key($row['type']) : 'legacy';
                if (!isset($types[$type])) {
                    $type = 'legacy';
                }

                $clean[$taxonomy] = [
                    'type'      => $type,
                    'intervals' => [],
                    'colors'    => [],
                    'images'    => [],
                    'modes'     => [],
                ];

                if ($type === 'intervals' && !empty($row['intervals']) && is_array($row['intervals'])) {
                    foreach ($row['intervals'] as $interval) {
                        if (!is_array($interval)) {
                            continue;
                        }

                        $label = isset($interval['label'])
                            ? sanitize_text_field($interval['label'])
                            : '';
                        $min = isset($interval['min'])
                            ? str_replace(',', '.', trim(sanitize_text_field((string) $interval['min'])))
                            : '';
                        $max = isset($interval['max'])
                            ? str_replace(',', '.', trim(sanitize_text_field((string) $interval['max'])))
                            : '';

                        if ($label === '' || $min === '' || $max === '' || !is_numeric($min) || !is_numeric($max)) {
                            continue;
                        }

                        if ((float) $min > (float) $max) {
                            continue;
                        }

                        $clean[$taxonomy]['intervals'][] = [
                            'label' => $label,
                            'min'   => (string) (0 + $min),
                            'max'   => (string) (0 + $max),
                        ];
                    }
                }

                if ($type === 'color') {
                    // Сохраняем обычные цвета.
                    if (!empty($row['colors']) && is_array($row['colors'])) {
                        foreach ($row['colors'] as $term_id => $color) {
                            $term_id = absint($term_id);
                            $color = sanitize_hex_color($color);

                            if (!$term_id || !$color) {
                                continue;
                            }

                            $term = get_term($term_id, $taxonomy);

                            if ($term && !is_wp_error($term)) {
                                $clean[$taxonomy]['colors'][$term_id] = $color;
                            }
                        }
                    }

                    // Сохраняем изображения из медиатеки WordPress.
                    if (!empty($row['images']) && is_array($row['images'])) {
                        foreach ($row['images'] as $term_id => $image_id) {
                            $term_id = absint($term_id);
                            $image_id = absint($image_id);

                            if (!$term_id || !$image_id) {
                                continue;
                            }

                            $term = get_term($term_id, $taxonomy);

                            if (!$term || is_wp_error($term)) {
                                continue;
                            }

                            $attachment = get_post($image_id);

                            if (
                                !$attachment ||
                                $attachment->post_type !== 'attachment'
                            ) {
                                continue;
                            }

                            $mime_type = get_post_mime_type($image_id);

                            // SVG допускается, если он уже загружен в медиатеку.
                            $allowed_mimes = [
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                                'image/svg+xml',
                            ];

                            if (!in_array($mime_type, $allowed_mimes, true)) {
                                continue;
                            }

                            $clean[$taxonomy]['images'][$term_id] = $image_id;
                        }
                    }

                    // Режим отображения каждого значения: цвет или изображение.
                    if (!empty($row['modes']) && is_array($row['modes'])) {
                        foreach ($row['modes'] as $term_id => $mode) {
                            $term_id = absint($term_id);

                            if (!$term_id) {
                                continue;
                            }

                            $term = get_term($term_id, $taxonomy);

                            if (!$term || is_wp_error($term)) {
                                continue;
                            }

                            $mode = sanitize_key($mode);

                            if (in_array($mode, ['color', 'image'], true)) {
                                $clean[$taxonomy]['modes'][$term_id] = $mode;
                            }
                        }
                    }
                }
            }

            return $clean;
        }

        public static function enqueue_assets($hook)
        {
            $page_slug = self::PAGE_SLUG;

            error_log('CWC display settings: hook = ' . $hook);

            // Проверяем, что открыта страница настроек типов отображения.
            if (strpos($hook, $page_slug) === false) {
                return;
            }

            wp_enqueue_script('jquery-ui-sortable');

            wp_enqueue_media();

            $base_dir = dirname(plugin_dir_path(__FILE__)) . '/';
            $base_url = trailingslashit(dirname(plugin_dir_url(__FILE__)));

            $css_path = $base_dir . 'css/admin-display-settings.css';
            $js_path  = $base_dir . 'js/admin-display-settings.js';

            error_log('CWC display settings: JS exists = ' . (file_exists($js_path) ? 'yes' : 'no'));
            error_log('CWC display settings: JS URL = ' . $base_url . 'js/admin-display-settings.js');

            wp_enqueue_style(
                'cwc-admin-display-settings',
                $base_url . 'css/admin-display-settings.css',
                [],
                file_exists($css_path) ? (string) filemtime($css_path) : '1.0.0'
            );

            wp_enqueue_script(
                'cwc-admin-display-settings',
                $base_url . 'js/admin-display-settings.js',
                [],
                file_exists($js_path) ? (string) filemtime($js_path) : '1.0.0',
                true
            );
        }

        public static function render_page()
        {
            if (!current_user_can('manage_woocommerce')) {
                return;
            }

            if (!function_exists('wc_get_attribute_taxonomies')) {
                echo '<div class="wrap"><h1>Типы отображения фильтров</h1><div class="notice notice-error"><p>Для этой страницы нужен активный WooCommerce.</p></div></div>';
                return;
            }

            $attributes = self::get_attributes();
            $settings = self::get_settings();
            $types = self::get_types();
            $saved_order = self::get_filter_order();

            if (!empty($saved_order)) {
                $position = array_flip($saved_order);

                uksort($attributes, static function ($a, $b) use ($position) {
                    $pos_a = $position[$a] ?? PHP_INT_MAX;
                    $pos_b = $position[$b] ?? PHP_INT_MAX;

                    return $pos_a <=> $pos_b;
                });
            }
?>
            <div class="wrap cwc-display-settings">
                <h1>Типы отображения фильтров</h1>
                <p class="description cwc-display-intro">
                    Здесь задаётся способ отображения каждого глобального атрибута WooCommerce.
                    Новые атрибуты появятся в этом списке автоматически. Если настройка не задана,
                    фильтр продолжает выводиться прежним способом — обычным списком значений.
                    Настройки видимости атрибутов по категориям остаются на прежней странице.
                </p>

                <?php if (empty($attributes)) : ?>
                    <div class="notice notice-info">
                        <p>Глобальные атрибуты WooCommerce пока не созданы.</p>
                    </div>
                <?php else : ?>
                    <form method="post" action="options.php">
                        <?php settings_fields('cwc_filter_display_settings_group'); ?>
                        <?php foreach (array_keys($attributes) as $taxonomy) : ?>
                            <input
                                type="hidden"
                                class="cwc-filter-order-input"
                                name="<?php echo esc_attr(self::ORDER_OPTION_NAME); ?>[]"
                                value="<?php echo esc_attr($taxonomy); ?>">
                        <?php endforeach; ?>
                        <table class="widefat striped cwc-display-table">
                            <thead>
                                <tr>
                                    <th scope="col" class="cwc-order-column">Порядок</th>
                                    <th scope="col">Атрибут</th>
                                    <th scope="col">Таксономия</th>
                                    <th scope="col">Тип отображения</th>
                                    <th scope="col">Дополнительные настройки</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attributes as $taxonomy => $attribute) :
                                    $saved = isset($settings[$taxonomy]) && is_array($settings[$taxonomy])
                                        ? $settings[$taxonomy]
                                        : [];
                                    $type = isset($saved['type']) && isset($types[$saved['type']])
                                        ? $saved['type']
                                        : 'legacy';
                                    $intervals = isset($saved['intervals']) && is_array($saved['intervals'])
                                        ? $saved['intervals']
                                        : [];
                                    $colors = isset($saved['colors']) && is_array($saved['colors'])
                                        ? $saved['colors']
                                        : [];

                                    $images = isset($saved['images']) && is_array($saved['images'])
                                        ? $saved['images']
                                        : [];

                                    $modes = isset($saved['modes']) && is_array($saved['modes'])
                                        ? $saved['modes']
                                        : [];
                                    $show_color_terms = $type === 'color';
                                ?>
                                    <tr class="cwc-attribute-row" data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                                        <td class="cwc-order-column">
                                            <span
                                                class="cwc-filter-order-handle"
                                                title="Перетащите, чтобы изменить порядок"
                                                aria-label="Перетащить фильтр">☷</span>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($attribute['label']); ?></strong>
                                        </td>
                                        <td><code><?php echo esc_html($taxonomy); ?></code></td>
                                        <td>
                                            <select class="cwc-display-type" name="<?php echo esc_attr(self::OPTION_NAME . '[' . $taxonomy . '][type]'); ?>">
                                                <?php foreach ($types as $type_value => $type_label) : ?>
                                                    <option value="<?php echo esc_attr($type_value); ?>" <?php selected($type, $type_value); ?>><?php echo esc_html($type_label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="cwc-config-cell">
                                            <div class="cwc-type-help cwc-help-range" <?php echo $type === 'range' ? '' : 'hidden'; ?>>
                                                Числовой диапазон будет настроен на следующем этапе. Сейчас выбор типа сохраняется.
                                            </div>

                                            <div class="cwc-type-config cwc-interval-config" <?php echo $type === 'intervals' ? '' : 'hidden'; ?>>
                                                <p class="description">Добавьте интервалы для этого атрибута. Границы включительные.</p>
                                                <table class="widefat cwc-interval-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Название</th>
                                                            <th>От</th>
                                                            <th>До</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="cwc-interval-rows">
                                                        <?php foreach ($intervals as $index => $interval) : ?>
                                                            <?php self::render_interval_row($taxonomy, $index, $interval); ?>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                                <button type="button" class="button cwc-add-interval">Добавить интервал</button>
                                                <template class="cwc-interval-template">
                                                    <?php self::render_interval_row($taxonomy, '__INDEX__', []); ?>
                                                </template>
                                            </div>


                                            <div class="cwc-type-config cwc-color-config" <?php echo $show_color_terms ? '' : 'hidden'; ?>>
                                                <?php
                                                if ($show_color_terms) :
                                                    $terms = get_terms([
                                                        'taxonomy'   => $taxonomy,
                                                        'hide_empty' => false,
                                                        'number'     => 0,
                                                        'orderby'    => 'name',
                                                        'order'      => 'ASC',
                                                    ]);

                                                    if (!is_wp_error($terms) && !empty($terms)) :
                                                ?>
                                                        <p class="description">
                                                            Для каждого значения выберите отображение: сплошной цвет или изображение.
                                                            Изображения выбираются из медиатеки WordPress.
                                                        </p>

                                                        <div class="cwc-color-terms">
                                                            <?php foreach ($terms as $term) :
                                                                $term_id = (int) $term->term_id;

                                                                $term_color = isset($colors[$term_id])
                                                                    ? sanitize_hex_color($colors[$term_id])
                                                                    : '#cccccc';

                                                                $image_id = isset($images[$term_id])
                                                                    ? absint($images[$term_id])
                                                                    : 0;

                                                                $image_url = $image_id
                                                                    ? wp_get_attachment_url($image_id)
                                                                    : '';

                                                                $mode = isset($modes[$term_id])
                                                                    ? $modes[$term_id]
                                                                    : 'color';

                                                                if (!in_array($mode, ['color', 'image'], true)) {
                                                                    $mode = 'color';
                                                                }

                                                                $field_base = self::OPTION_NAME . '[' . $taxonomy . ']';
                                                            ?>
                                                                <div class="cwc-color-term" data-term-id="<?php echo esc_attr($term_id); ?>">
                                                                    <strong class="cwc-color-term__name">
                                                                        <?php echo esc_html($term->name); ?>
                                                                    </strong>

                                                                    <label class="cwc-color-term__mode-label">
                                                                        <span>Отображение</span>
                                                                        <select
                                                                            class="cwc-color-display-mode"
                                                                            name="<?php echo esc_attr($field_base . '[modes][' . $term_id . ']'); ?>">
                                                                            <option value="color" <?php selected($mode, 'color'); ?>>
                                                                                Сплошной цвет
                                                                            </option>
                                                                            <option value="image" <?php selected($mode, 'image'); ?>>
                                                                                Изображение
                                                                            </option>
                                                                        </select>
                                                                    </label>

                                                                    <div class="cwc-color-editor cwc-color-editor--color" <?php echo $mode === 'color' ? '' : 'hidden'; ?>>
                                                                        <label>
                                                                            <span>Цвет</span>
                                                                            <input
                                                                                type="color"
                                                                                name="<?php echo esc_attr($field_base . '[colors][' . $term_id . ']'); ?>"
                                                                                value="<?php echo esc_attr($term_color ?: '#cccccc'); ?>">
                                                                        </label>
                                                                    </div>

                                                                    <div class="cwc-color-editor cwc-color-editor--image" <?php echo $mode === 'image' ? '' : 'hidden'; ?>>
                                                                        <input
                                                                            type="hidden"
                                                                            class="cwc-color-image-id"
                                                                            name="<?php echo esc_attr($field_base . '[images][' . $term_id . ']'); ?>"
                                                                            value="<?php echo esc_attr($image_id); ?>">

                                                                        <div class="cwc-color-image-preview">
                                                                            <?php if ($image_url) : ?>
                                                                                <img
                                                                                    src="<?php echo esc_url($image_url); ?>"
                                                                                    alt="">
                                                                            <?php else : ?>
                                                                                <span>Изображение не выбрано</span>
                                                                            <?php endif; ?>
                                                                        </div>

                                                                        <div class="cwc-color-image-actions">
                                                                            <button type="button" class="button cwc-select-color-image">
                                                                                Загрузить
                                                                            </button>

                                                                            <button
                                                                                type="button"
                                                                                class="button cwc-remove-color-image"
                                                                                <?php echo $image_id ? '' : 'hidden'; ?>>
                                                                                Удалить
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php
                                                    else :
                                                    ?>
                                                        <p class="description">У этого атрибута пока нет значений.</p>
                                                    <?php
                                                    endif;
                                                else :
                                                    ?>
                                                    <p class="description">
                                                        Выберите тип «Цветные элементы» и сохраните настройки.
                                                        После перезагрузки появятся значения атрибута.
                                                    </p>
                                                <?php endif; ?>
                                            </div>


                                            <?php if ($type === 'legacy' || $type === 'checkbox' || $type === 'radio') : ?>
                                                <span class="cwc-no-extra-config">Дополнительные настройки не требуются.</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php submit_button('Сохранить настройки типов'); ?>
                    </form>
                <?php endif; ?>
            </div>
        <?php
        }

        private static function render_interval_row($taxonomy, $index, $interval)
        {
            $base = self::OPTION_NAME . '[' . $taxonomy . '][intervals][' . $index . ']';
            $label = isset($interval['label']) ? $interval['label'] : '';
            $min = isset($interval['min']) ? $interval['min'] : '';
            $max = isset($interval['max']) ? $interval['max'] : '';
        ?>
            <tr class="cwc-interval-row">
                <td><input type="text" class="regular-text" name="<?php echo esc_attr($base . '[label]'); ?>" value="<?php echo esc_attr($label); ?>" placeholder="Например, 3000–4000 K" /></td>
                <td><input type="number" step="any" name="<?php echo esc_attr($base . '[min]'); ?>" value="<?php echo esc_attr($min); ?>" /></td>
                <td><input type="number" step="any" name="<?php echo esc_attr($base . '[max]'); ?>" value="<?php echo esc_attr($max); ?>" /></td>
                <td><button type="button" class="button-link-delete cwc-remove-interval">
                        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.4904 8.5L17 16.0205L16.0205 17L8.5 9.4904L0.979513 17L0 16.0205L7.5096 8.5L0 0.979513L0.979513 0L8.5 7.5096L16.0205 0L17 0.979513L9.4904 8.5Z" fill="#D12A2A" />
                        </svg>

                    </button></td>
            </tr>
<?php
        }
    }

    CWC_Filter_Admin_Display_Settings::init();
}
