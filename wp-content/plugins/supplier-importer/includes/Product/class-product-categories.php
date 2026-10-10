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
        $category_path,
        $supplier = ''
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
            $category_path,
            $supplier
        );

        if (empty($term_ids)) {
            return [];
        }

        $product->set_category_ids(
            $term_ids
        );

        return $term_ids;
    }
    private function get_or_create_path(
        $category_path,
        $supplier = ''
    ) {
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
                $parent_id,
                $supplier
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
        $parent_id = 0,
        $supplier = ''
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

        $args = [
            'parent' => $parent_id,
        ];

        if ($supplier === 'crystal_lux') {
            $args['slug'] = sanitize_title($name)
                . '-'
                . (int) $parent_id;
        }

        \Supplier_Importer\Core\Logger::info(
            'CATEGORY INSERT DEBUG: ' . wp_json_encode([
                'name'        => $name,
                'parent_id'   => $parent_id,
                'supplier'    => $supplier,
                'args'        => $args,
                'slug_length' => isset($args['slug'])
                    ? strlen($args['slug'])
                    : null,
            ], JSON_UNESCAPED_UNICODE)
        );

        $result = wp_insert_term(
            $name,
            'product_cat',
            $args
        );


        if (is_wp_error($result)) {
            $error_code = $result->get_error_code();
            $error_data = $result->get_error_data($error_code);

            \Supplier_Importer\Core\Logger::info(
                'CATEGORY ERROR: ' . wp_json_encode([
                    'name'       => $name,
                    'parent_id'  => $parent_id,
                    'error_code' => $error_code,
                    'message'    => $result->get_error_message(),
                    'error_data' => $error_data,
                ], JSON_UNESCAPED_UNICODE)
            );

            if ($error_code === 'term_exists') {
                $term_id = (int) $error_data;

                if ($term_id) {
                    $existing_term = get_term(
                        $term_id,
                        'product_cat'
                    );

                    if (
                        $existing_term
                        && !is_wp_error($existing_term)
                        && (int) $existing_term->parent === (int) $parent_id
                    ) {
                        return $existing_term;
                    }
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
