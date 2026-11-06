<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use WC_Product;
use WC_Product_Attribute;

class Product_Attributes
{
    public function assign(
        WC_Product $product,
        $attributes
    ) {
        if (!$product instanceof WC_Product) {
            throw new InvalidArgumentException(
                'Product_Attributes ожидает объект WC_Product.'
            );
        }

        if (!is_array($attributes) || empty($attributes)) {
            return;
        }

        $product_attributes = $product->get_attributes();

        foreach ($attributes as $taxonomy => $value) {
            $taxonomy = trim((string) $taxonomy);
            $label = $taxonomy;

            if (is_array($value)) {
                $label = isset($value['label'])
                    ? trim((string) $value['label'])
                    : $taxonomy;

                $value = isset($value['value'])
                    ? trim((string) $value['value'])
                    : '';
            } else {
                $value = trim((string) $value);
            }

            if ($taxonomy === '' || $value === '') {
                continue;
            }

            $taxonomy_name = $this->get_taxonomy_name($taxonomy);

            if ($taxonomy_name === '') {
                continue;
            }

            $attribute_data = $this->ensure_attribute_taxonomy(
                $taxonomy_name,
                $label
            );

            if (!$attribute_data) {
                \Supplier_Importer\Core\Logger::error(
                    'ATTRIBUTE ERROR: не удалось подготовить таксономию '
                        . $taxonomy_name
                );

                continue;
            }

            $attribute = $this->build_attribute(
                $attribute_data['taxonomy_name'],
                $attribute_data['attribute_id'],
                $value
            );

            if (!$attribute) {
                continue;
            }

            $product_attributes[$attribute_data['taxonomy_name']] = $attribute;
        }

        if (!empty($product_attributes)) {
            $product->set_attributes($product_attributes);
        }
    }


    private function ensure_attribute_taxonomy(
        $taxonomy_name,
        $label = ''
    ) {
        if (!function_exists('wc_create_attribute')) {
            return false;
        }

        $original_slug = substr($taxonomy_name, 3);

        if ($original_slug === '') {
            return false;
        }

        $attribute_name = $label !== ''
            ? $label
            : ucwords(str_replace(['-', '_'], ' ', $original_slug));

        // Сначала ищем существующий атрибут по исходному slug.
        $attribute_id = wc_attribute_taxonomy_id_by_name(
            $original_slug
        );

        if ($attribute_id) {
            $slug = $original_slug;
        } else {
            $max_length = wc_get_attribute_slug_max_byte_length();

            // Для коротких slug сохраняем исходное имя.
            if (strlen($original_slug) <= $max_length) {
                $slug = $original_slug;
            } else {
                // Для длинных создаём стабильный короткий slug.
                $slug = $this->generate_short_slug($original_slug);

                $existing_id = wc_attribute_taxonomy_id_by_name($slug);

                // Проверяем, что найденный hash-slug принадлежит
                // атрибуту с ожидаемым названием.
                if ($existing_id) {
                    $existing_label = '';

                    foreach (wc_get_attribute_taxonomies() as $existing_attribute) {
                        if (
                            (int) $existing_attribute->attribute_id
                            === (int) $existing_id
                        ) {
                            $existing_label = $existing_attribute->attribute_label;
                            break;
                        }
                    }

                    if ($existing_label !== $attribute_name) {
                        \Supplier_Importer\Core\Logger::error(
                            'ATTRIBUTE ERROR: сгенерированный slug занят '
                                . $slug . ', исходный slug: ' . $original_slug
                        );

                        return false;
                    }

                    $attribute_id = $existing_id;
                }
            }

            // Общая проверка и создание для коротких и длинных slug.
            if (!$attribute_id) {
                $actual_taxonomy_name = 'pa_' . $slug;

                if (taxonomy_exists($actual_taxonomy_name)) {
                    \Supplier_Importer\Core\Logger::error(
                        'ATTRIBUTE ERROR: таксономия уже существует: '
                            . $actual_taxonomy_name
                    );

                    return false;
                }

                $attribute_id = wc_create_attribute([
                    'name'         => $attribute_name,
                    'slug'         => $slug,
                    'type'         => 'select',
                    'order_by'     => 'menu_order',
                    'has_archives' => false,
                ]);

                if (is_wp_error($attribute_id)) {
                    \Supplier_Importer\Core\Logger::error(
                        'ATTRIBUTE ERROR: создание атрибута '
                            . $slug . ': '
                            . $attribute_id->get_error_message()
                    );

                    return false;
                }

                if (!$attribute_id) {
                    return false;
                }

                delete_transient('wc_attribute_taxonomies');
            }
        }

        $actual_taxonomy_name = 'pa_' . $slug;

        if (!taxonomy_exists($actual_taxonomy_name)) {
            register_taxonomy(
                $actual_taxonomy_name,
                ['product'],
                [
                    'hierarchical' => false,
                    'show_ui'      => false,
                    'query_var'    => true,
                    'rewrite'      => false,
                ]
            );
        }

        if (!taxonomy_exists($actual_taxonomy_name)) {
            return false;
        }

        return [
            'taxonomy_name' => $actual_taxonomy_name,
            'attribute_id'  => (int) $attribute_id,
        ];
    }


    /**
     * Генерирует детерминированный ASCII slug длиной до 29 байт.
     */
    private function generate_short_slug(
        $source,
        $attempt = 0
    ) {
        $hash_source = $attempt === 0
            ? $source
            : $source . ':' . $attempt;

        return 'attr-' . substr(
            hash('sha256', $hash_source),
            0,
            24
        );
    }

    /**
     * Создаёт объект атрибута товара.
     */
    private function build_attribute(
        $taxonomy_name,
        $attribute_id,
        $value
    ) {
        $term_ids = $this->get_or_create_terms(
            $taxonomy_name,
            $value
        );

        if (empty($term_ids)) {
            return null;
        }

        $attribute = new WC_Product_Attribute();

        $attribute->set_id($attribute_id);
        $attribute->set_name($taxonomy_name);
        $attribute->set_options($term_ids);
        $attribute->set_position(0);
        $attribute->set_visible(true);
        $attribute->set_variation(false);

        return $attribute;
    }

    /**
     * Нормализует исходное имя таксономии.
     */
    private function get_taxonomy_name($taxonomy)
    {
        $taxonomy = trim((string) $taxonomy);

        $taxonomy = preg_replace(
            '/^pa_/',
            '',
            $taxonomy
        );

        $taxonomy = wc_sanitize_taxonomy_name($taxonomy);

        return $taxonomy !== ''
            ? 'pa_' . $taxonomy
            : '';
    }

    /**
     * Получает существующие термины или создаёт новые.
     */
    private function get_or_create_terms($taxonomy, $value)
    {
        $values = preg_split('/\s*[,;]\s*/', $value);

        $values = array_values(
            array_filter(
                array_map('trim', $values),
                static function ($item) {
                    return $item !== '';
                }
            )
        );

        $term_ids = [];

        foreach ($values as $term_name) {
            $term = term_exists($term_name, $taxonomy);

            if (!$term) {
                $term = wp_insert_term($term_name, $taxonomy);
            }

            if (is_wp_error($term)) {
                \Supplier_Importer\Core\Logger::info(
                    'ATTRIBUTE ERROR: значение "' . $term_name
                        . '" в ' . $taxonomy . ': '
                        . $term->get_error_message()
                );

                continue;
            }

            if (is_array($term)) {
                $term_ids[] = (int) $term['term_id'];
            } else {
                $term_ids[] = (int) $term;
            }
        }

        return array_values(array_unique($term_ids));
    }
}
