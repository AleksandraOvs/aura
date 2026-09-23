<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

use WC_Product;
use InvalidArgumentException;

class Product_Categories
{
    public function assign(
        WC_Product $product,
        $category_path
    ) {
        if (!$product instanceof WC_Product) {
            throw new InvalidArgumentException(
                'Product_Categories ожидает объект WC_Product.'
            );
        }

        $category_path = trim((string) $category_path);

        if ($category_path === '') {
            return [];
        }

        $term_ids = $this->get_or_create_path(
            $category_path
        );

        if (empty($term_ids)) {
            return [];
        }

        $product->set_category_ids(
            $term_ids
        );

        return $term_ids;
    }

    private function get_or_create_path($category_path)
    {
        $parts = preg_split(
            '/\s*\/\s*/',
            $category_path
        );

        $parts = array_values(
            array_filter(
                array_map('trim', $parts)
            )
        );

        if (empty($parts)) {
            return [];
        }

        $parent_id = 0;
        $term_ids = [];

        foreach ($parts as $part) {
            $term = $this->get_or_create_category(
                $part,
                $parent_id
            );

            if (!$term) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Не удалось создать категорию "%s".',
                        $part
                    )
                );
            }

            $parent_id = (int) $term->term_id;

            $term_ids[] = $parent_id;
        }

        return $term_ids;
    }

    private function get_or_create_category(
        $name,
        $parent_id = 0
    ) {
        $term = get_term_by(
            'name',
            $name,
            'product_cat'
        );

        if (
            $term
            && (int) $term->parent === (int) $parent_id
        ) {
            return $term;
        }

        if ($term) {
            $children = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'name'       => $name,
                'parent'     => $parent_id,
                'number'     => 1,
            ]);

            if (!is_wp_error($children) && !empty($children)) {
                return $children[0];
            }
        }

        $result = wp_insert_term(
            $name,
            'product_cat',
            [
                'parent' => $parent_id,
            ]
        );

        if (is_wp_error($result)) {
            if (
                $result->get_error_code()
                === 'term_exists'
            ) {
                $term_id = (int) $result->get_error_data(
                    'term_exists'
                );

                if ($term_id) {
                    return get_term(
                        $term_id,
                        'product_cat'
                    );
                }
            }

            throw new InvalidArgumentException(
                $result->get_error_message()
            );
        }

        return get_term(
            $result['term_id'],
            'product_cat'
        );
    }
}
