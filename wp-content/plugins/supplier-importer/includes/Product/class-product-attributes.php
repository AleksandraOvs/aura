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
            $value = trim((string) $value);

            if ($taxonomy === '' || $value === '') {
                continue;
            }

            $taxonomy_name = $this->get_taxonomy_name($taxonomy);

            // Создаём глобальный атрибут, если он был удалён.
            if (!$this->ensure_attribute_taxonomy($taxonomy_name)) {
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

        if (empty($product_attributes)) {
            return;
        }

        $product->set_attributes($product_attributes);
    }

    /**
     * Создаёт глобальный атрибут WooCommerce при необходимости.
     */
    private function ensure_attribute_taxonomy($taxonomy)
    {
        if (taxonomy_exists($taxonomy)) {
            return true;
        }

        if (
            !function_exists('wc_create_attribute')
            || !function_exists('wc_register_product_attributes')
        ) {
            return false;
        }

        // Убираем префикс pa_ для работы с глобальным атрибутом.
        $slug = preg_replace('/^pa_/', '', $taxonomy);
        $slug = wc_sanitize_taxonomy_name($slug);

        if ($slug === '' || strlen($slug) > 28) {
            return false;
        }

        // Возможно, атрибут существует в базе,
        // но его таксономия ещё не зарегистрирована.
        $attribute_id = wc_attribute_taxonomy_id_by_name($taxonomy);

        if (!$attribute_id) {
            $name = ucwords(
                str_replace(['-', '_'], ' ', $slug)
            );

            $attribute_id = wc_create_attribute([
                'name'         => $name,
                'slug'         => $slug,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);

            if (is_wp_error($attribute_id) || !$attribute_id) {
                return false;
            }
        }

        // Регистрируем таксономию в текущем запросе,
        // чтобы можно было создавать её термины.
        wc_register_product_attributes();

        return taxonomy_exists($taxonomy);
    }

    private function build_attribute($taxonomy, $value)
    {
        $term_ids = $this->get_or_create_terms(
            $taxonomy,
            $value
        );

        if (empty($term_ids)) {
            return null;
        }

        $attribute = new WC_Product_Attribute();

        $attribute->set_id(
            wc_attribute_taxonomy_id_by_name($taxonomy)
        );

        $attribute->set_name($taxonomy);
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
                static function ($value) {
                    return $value !== '';
                }
            )
        );

        $term_ids = [];

        foreach ($values as $term_name) {
            $term = term_exists($term_name, $taxonomy);

            if (!$term) {
                $term = wp_insert_term(
                    $term_name,
                    $taxonomy
                );
            }

            if (is_wp_error($term) || !$term) {
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
