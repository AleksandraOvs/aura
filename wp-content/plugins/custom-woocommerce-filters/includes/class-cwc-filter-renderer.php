<?php

/** Вывод фильтров по настройкам типа отображения. */
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWC_Filter_Renderer')) {
    class CWC_Filter_Renderer
    {
        const OPTION_NAME = 'cwc_filter_display_settings';

        public static function get_type($taxonomy)
        {
            $settings = get_option(self::OPTION_NAME, []);
            if (!is_array($settings) || empty($settings[$taxonomy]['type'])) {
                return 'legacy';
            }

            $type = sanitize_key($settings[$taxonomy]['type']);
            return in_array($type, ['legacy', 'checkbox', 'radio', 'range', 'intervals', 'color'], true)
                ? $type
                : 'legacy';
        }

        private static function numeric_values($terms)
        {
            $values = [];
            foreach ($terms as $term) {
                $value = CWC_Filter_Range_Query::parse_numeric_name($term->name);
                if ($value !== null) {
                    $values[] = $value;
                }
            }
            return $values;
        }

        private static function render_shell_start($taxonomy, $title, $type)
        {
            ob_start();
?>
            <div class="filter cwc-filter--<?php echo esc_attr($type); ?>" data-filter-type="<?php echo esc_attr($type); ?>">
                <div class="filter-item__title">
                    <?php echo esc_html(function_exists('cwc_clean_title') ? cwc_clean_title($title) : $title); ?>
                    <div class="filter-item-title__toggle"><span></span><span></span></div>
                </div>
                <div class="filter-item__content">
                    <?php
                    return ob_get_clean();
                }

                public static function render($taxonomy, $title, $category_id = 0)
                {
                    if (!taxonomy_exists($taxonomy)) {
                        return '';
                    }

                    $terms = cwc_get_category_filter_terms($taxonomy, $category_id);
                    if (empty($terms) || is_wp_error($terms)) {
                        return '';
                    }

                    usort($terms, static function ($a, $b) {
                        $a_numeric = CWC_Filter_Range_Query::parse_numeric_name($a->name);
                        $b_numeric = CWC_Filter_Range_Query::parse_numeric_name($b->name);
                        if ($a_numeric !== null && $b_numeric !== null) {
                            return $a_numeric <=> $b_numeric;
                        }
                        if ($a_numeric !== null) {
                            return -1;
                        }
                        if ($b_numeric !== null) {
                            return 1;
                        }
                        return strnatcasecmp($a->name, $b->name);
                    });

                    $type = self::get_type($taxonomy);
                    $settings = get_option(self::OPTION_NAME, []);
                    $saved = isset($settings[$taxonomy]) && is_array($settings[$taxonomy])
                        ? $settings[$taxonomy]
                        : [];

                    // Числовой диапазон: границы вычисляются по числовым ИМЕНАМ термов в текущей категории.
                    if ($type === 'range') {
                        $values = self::numeric_values($terms);
                        if (!$values) {
                            $type = 'legacy';
                        } else {
                            $min = min($values);
                            $max = max($values);
                            $step = (floor($min) == $min && floor($max) == $max) ? '1' : 'any';
                            ob_start();
                            echo self::render_shell_start($taxonomy, $title, 'range');
                    ?>
                            <div class="cwc-numeric-range range-inputs" data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                                <div
                                    class="cwc-range-slider"
                                    data-min="<?php echo esc_attr($min); ?>"
                                    data-max="<?php echo esc_attr($max); ?>"
                                    data-step="<?php echo esc_attr($step === '1' ? '1' : '0.1'); ?>"
                                    aria-label="<?php echo esc_attr(sprintf('Диапазон: %s', $title)); ?>"></div>
                                <label class="cwc-range-field">
                                    <span>От</span>
                                    <input type="number" step="<?php echo esc_attr($step); ?>" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" value="<?php echo esc_attr($min); ?>" name="<?php echo esc_attr('cwc_range_' . $taxonomy . '_min'); ?>" />
                                </label>
                                <label class="cwc-range-field">
                                    <span>До</span>
                                    <input type="number" step="<?php echo esc_attr($step); ?>" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" value="<?php echo esc_attr($max); ?>" name="<?php echo esc_attr('cwc_range_' . $taxonomy . '_max'); ?>" />
                                </label>
                            </div>
                        <?php
                            echo '</div></div>';
                            return ob_get_clean();
                        }
                    }

                    // Интервалы: если настройки не заполнены, сохраняем совместимый обычный список.
                    if ($type === 'intervals') {
                        $intervals = isset($saved['intervals']) && is_array($saved['intervals'])
                            ? $saved['intervals']
                            : [];
                        $valid_intervals = [];
                        foreach ($intervals as $interval) {
                            if (!is_array($interval) || !isset($interval['label'], $interval['min'], $interval['max'])) {
                                continue;
                            }
                            $min = str_replace(',', '.', trim((string) $interval['min']));
                            $max = str_replace(',', '.', trim((string) $interval['max']));
                            $label = sanitize_text_field((string) $interval['label']);
                            if ($label === '' || !is_numeric($min) || !is_numeric($max)) {
                                continue;
                            }
                            $valid_intervals[] = ['label' => $label, 'min' => (float) $min, 'max' => (float) $max];
                        }

                        if ($valid_intervals) {
                            ob_start();
                            echo self::render_shell_start($taxonomy, $title, 'intervals');
                        ?>
                            <ul class="sidebar-list cwc-filter-list cwc-filter-list--intervals" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" data-display-type="intervals">
                                <?php foreach ($valid_intervals as $interval) : ?>
                                    <li>
                                        <a href="#" class="filter-item cwc-filter-option cwc-interval-option" data-range-min="<?php echo esc_attr($interval['min']); ?>" data-range-max="<?php echo esc_attr($interval['max']); ?>">
                                            <?php echo esc_html($interval['label']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                    <?php
                            echo '</div></div>';
                            return ob_get_clean();
                        }
                        $type = 'legacy';
                    }

                    $colors = isset($saved['colors']) && is_array($saved['colors'])
                        ? $saved['colors']
                        : [];

                    $images = isset($saved['images']) && is_array($saved['images'])
                        ? $saved['images']
                        : [];

                    $modes = isset($saved['modes']) && is_array($saved['modes'])
                        ? $saved['modes']
                        : [];
                    ob_start();
                    ?>
                    <div class="filter cwc-filter--<?php echo esc_attr($type); ?>" data-filter-type="<?php echo esc_attr($type); ?>">
                        <div class="filter-item__title">
                            <?php echo esc_html(function_exists('cwc_clean_title') ? cwc_clean_title($title) : $title); ?>
                            <div class="filter-item-title__toggle"><span></span><span></span></div>
                        </div>
                        <div class="filter-item__content">
                            <ul class="sidebar-list cwc-filter-list cwc-filter-list--<?php echo esc_attr($type); ?>" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" data-display-type="<?php echo esc_attr($type); ?>">
                                <?php foreach ($terms as $term) :
                                    $term_id = (int) $term->term_id;

                                    $color = isset($colors[$term_id])
                                        ? sanitize_hex_color($colors[$term_id])
                                        : '';

                                    $image_id = isset($images[$term_id])
                                        ? absint($images[$term_id])
                                        : 0;

                                    $mode = isset($modes[$term_id])
                                        ? sanitize_key($modes[$term_id])
                                        : 'color';

                                    if (!in_array($mode, ['color', 'image'], true)) {
                                        $mode = 'color';
                                    }

                                    $image_url = '';

                                    if ($mode === 'image' && $image_id) {
                                        $mime_type = get_post_mime_type($image_id);

                                        if (
                                            in_array(
                                                $mime_type,
                                                ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
                                                true
                                            )
                                        ) {
                                            // У SVG миниатюра может отсутствовать.
                                            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');

                                            if (!$image_url) {
                                                $image_url = wp_get_attachment_url($image_id);
                                            }
                                        }
                                    }
                                ?>
                                    <li>
                                        <a href="#" class="filter-item cwc-filter-option" data-slug="<?php echo esc_attr($term->slug); ?>">
                                            <?php if ($type === 'checkbox') : ?>
                                                <span class="cwc-option-control cwc-option-control--checkbox" aria-hidden="true"></span>
                                            <?php elseif ($type === 'radio') : ?>
                                                <span class="cwc-option-control cwc-option-control--radio" aria-hidden="true"></span>
                                            <?php elseif ($type === 'color') : ?>
                                                <?php if ($mode === 'image' && $image_url) : ?>
                                                    <span
                                                        class="cwc-color-swatch cwc-color-swatch--image"
                                                        aria-hidden="true"
                                                        style="background-image: url('<?php echo esc_url($image_url); ?>');"></span>
                                                <?php else : ?>
                                                    <span
                                                        class="cwc-color-swatch"
                                                        aria-hidden="true"
                                                        style="background-color: <?php echo esc_attr($color ?: '#cccccc'); ?>"></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <span class="cwc-option-label"><?php echo esc_html($term->name); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
        <?php
                    return ob_get_clean();
                }
            }
        }
