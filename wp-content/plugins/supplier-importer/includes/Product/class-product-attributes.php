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

        $product_attributes = [];

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

            if (!$this->ensure_attribute_taxonomy($taxonomy_name)) {
                \Supplier_Importer\Core\Logger::info(
                    'ATTRIBUTE ERROR: не удалось подготовить таксономию '
                        . $taxonomy_name
                );

                continue;
            }

            $attribute = $this->build_attribute(
                $taxonomy_name,
                $value
            );

            if (!$attribute) {
                continue;
            }

            $product_attributes[$taxonomy_name] = $attribute;
        }

        if (!empty($product_attributes)) {
            $product->set_attributes($product_attributes);
        }
    }

    /**
     * Создать глобальный атрибут при необходимости
     * и зарегистрировать его таксономию в текущем запросе.
     */
    private function ensure_attribute_taxonomy($taxonomy_name)
    {
        if (!function_exists('wc_create_attribute')) {
            return false;
        }

        $slug = preg_replace('/^pa_/', '', $taxonomy_name);
        $slug = wc_sanitize_taxonomy_name($slug);

        if ($slug === '') {
            return false;
        }

        $attribute_id = wc_attribute_taxonomy_id_by_name(
            'pa_' . $slug
        );

        if (!$attribute_id) {
            $attribute_id = wc_create_attribute([
                'name'         => ucwords(
                    str_replace(['-', '_'], ' ', $slug)
                ),
                'slug'         => $slug,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);

            if (is_wp_error($attribute_id)) {
                \Supplier_Importer\Core\Logger::info(
                    'ATTRIBUTE ERROR: создание атрибута '
                        . $slug . ': ' . $attribute_id->get_error_message()
                );

                return false;
            }

            // Обновляем кэш списка глобальных атрибутов.
            delete_transient('wc_attribute_taxonomies');
        }

        // WooCommerce регистрирует существующие атрибуты при запуске.
        // Атрибут, созданный в этом запросе, регистрируем сразу.
        if (!taxonomy_exists($taxonomy_name)) {
            register_taxonomy(
                $taxonomy_name,
                ['product'],
                [
                    'hierarchical' => false,
                    'show_ui'      => false,
                    'query_var'    => true,
                    'rewrite'      => false,
                ]
            );
        }

        return taxonomy_exists($taxonomy_name);
    }

    private function build_attribute($taxonomy_name, $value)
    {
        $term_ids = $this->get_or_create_terms(
            $taxonomy_name,
            $value
        );

        if (empty($term_ids)) {
            return null;
        }

        $attribute = new WC_Product_Attribute();

        $attribute->set_id(
            wc_attribute_taxonomy_id_by_name($taxonomy_name)
        );

        $attribute->set_name($taxonomy_name);
        $attribute->set_options($term_ids);
        $attribute->set_position(0);
        $attribute->set_visible(true);
        $attribute->set_variation(false);

        return $attribute;
    }

    private function get_taxonomy_name($taxonomy)
    {
        if (strpos($taxonomy, 'pa_') === 0) {
            return $taxonomy;
        }

        return 'pa_' . $taxonomy;
    }

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
