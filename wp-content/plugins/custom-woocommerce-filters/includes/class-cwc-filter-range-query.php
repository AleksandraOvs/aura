<?php
/**
 * Числовая фильтрация атрибутов WooCommerce через термы таксономий.
 * В запрос попадают ID термов, чьи ИМЕНА являются числами в выбранном диапазоне.
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWC_Filter_Range_Query')) {
    class CWC_Filter_Range_Query
    {
        const OPTION_NAME = 'cwc_filter_display_settings';

        /** Преобразует имя терма в число; slug не используется. */
        public static function parse_numeric_name($name)
        {
            $value = trim((string) $name);
            $value = str_replace(',', '.', $value);

            if ($value === '' || !is_numeric($value)) {
                return null;
            }

            return (float) $value;
        }

        /**
         * Добавляет tax_query для переданных числовых диапазонов.
         * Ожидает параметры cwc_range_{taxonomy}_min и cwc_range_{taxonomy}_max.
         */
        public static function apply($tax_query, $request = null)
        {
            if (!is_array($tax_query)) {
                $tax_query = [];
            }

            if (!is_array($request)) {
                $request = $_POST;
            }

            $settings = get_option(self::OPTION_NAME, []);
            if (!is_array($settings)) {
                return $tax_query;
            }

            $ranges = [];
            foreach ($request as $key => $raw_value) {
                if (!is_string($key) || strpos($key, 'cwc_range_') !== 0) {
                    continue;
                }

                if (!preg_match('/^cwc_range_(.+)_(min|max)$/', $key, $matches)) {
                    continue;
                }

                $taxonomy = sanitize_key($matches[1]);
                $side = $matches[2];
                $value = str_replace(',', '.', trim(sanitize_text_field((string) $raw_value)));

                if (!taxonomy_exists($taxonomy) || !is_numeric($value)) {
                    continue;
                }

                $type = isset($settings[$taxonomy]['type'])
                    ? sanitize_key($settings[$taxonomy]['type'])
                    : 'legacy';

                if (!in_array($type, ['range', 'intervals'], true)) {
                    continue;
                }

                if (!isset($ranges[$taxonomy])) {
                    $ranges[$taxonomy] = [];
                }
                $ranges[$taxonomy][$side] = (float) $value;
            }

            foreach ($ranges as $taxonomy => $range) {
                if (!isset($range['min'], $range['max'])) {
                    continue;
                }

                $min = min($range['min'], $range['max']);
                $max = max($range['min'], $range['max']);

                $terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                ]);

                $term_ids = [];
                if (!is_wp_error($terms) && is_array($terms)) {
                    foreach ($terms as $term) {
                        $numeric_value = self::parse_numeric_name($term->name);
                        if ($numeric_value !== null && $numeric_value >= $min && $numeric_value <= $max) {
                            $term_ids[] = (int) $term->term_id;
                        }
                    }
                }

                // Term ID 0 гарантирует отсутствие совпадений, если диапазон пустой.
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_ids ? array_values(array_unique($term_ids)) : [0],
                    'operator' => 'IN',
                ];
            }

            return $tax_query;
        }
    }
}
