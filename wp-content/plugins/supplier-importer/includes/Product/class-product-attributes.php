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

        if (
            !is_array($attributes)
            || empty($attributes)
        ) {
            return;
        }

        $product_attributes = [];

        foreach ($attributes as $taxonomy => $value) {
            $taxonomy = trim(
                (string) $taxonomy
            );

            $value = trim(
                (string) $value
            );

            if (
                $taxonomy === ''
                || $value === ''
            ) {
                continue;
            }

            if (!taxonomy_exists($this->get_taxonomy_name($taxonomy))) {
                continue;
            }

            $attribute = $this->build_attribute(
                $taxonomy,
                $value
            );

            if (!$attribute) {
                continue;
            }

            $product_attributes[$taxonomy] = $attribute;
        }

        if (empty($product_attributes)) {
            return;
        }

        $product->set_attributes(
            $product_attributes
        );
    }

    private function build_attribute(
        $taxonomy,
        $value
    ) {
        $taxonomy_name = $this->get_taxonomy_name(
            $taxonomy
        );

        $term_ids = $this->get_or_create_terms(
            $taxonomy_name,
            $value
        );

        if (empty($term_ids)) {
            return null;
        }

        $attribute = new WC_Product_Attribute();

        $attribute->set_id(
            wc_attribute_taxonomy_id_by_name(
                $taxonomy_name
            )
        );

        $attribute->set_name(
            $taxonomy_name
        );

        $attribute->set_options(
            $term_ids
        );

        $attribute->set_position(0);
        $attribute->set_visible(true);
        $attribute->set_variation(false);

        return $attribute;
    }

    private function get_taxonomy_name(
        $taxonomy
    ) {
        if (strpos($taxonomy, 'pa_') === 0) {
            return $taxonomy;
        }

        return 'pa_' . $taxonomy;
    }

    private function get_or_create_terms(
        $taxonomy,
        $value
    ) {
        $values = preg_split(
            '/\s*[,;]\s*/',
            $value
        );

        $values = array_values(
            array_filter(
                array_map(
                    'trim',
                    $values
                )
            )
        );

        $term_ids = [];

        foreach ($values as $term_name) {
            $term = term_exists(
                $term_name,
                $taxonomy
            );

            if (!$term) {
                $term = wp_insert_term(
                    $term_name,
                    $taxonomy
                );
            }

            if (is_wp_error($term)) {
                continue;
            }

            if (is_array($term)) {
                $term_ids[] = (int) $term['term_id'];
            } else {
                $term_ids[] = (int) $term;
            }
        }

        return array_values(
            array_unique($term_ids)
        );
    }
}
