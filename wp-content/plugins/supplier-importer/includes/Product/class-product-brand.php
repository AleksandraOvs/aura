<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use WC_Product;

class Product_Brand
{
    /**
     * Назначить товару бренд из таксономии product_brand.
     *
     * Пустое значение не изменяет существующий бренд.
     *
     * @param WC_Product $product
     * @param string     $brand_name
     *
     * @return void
     */
    public function assign(
        WC_Product $product,
        $brand_name
    ) {
        $brand_name = trim((string) $brand_name);

        if ($brand_name === '') {
            return;
        }

        if (!$product->get_id()) {
            throw new InvalidArgumentException(
                'Нельзя назначить бренд товару без ID.'
            );
        }

        if (!taxonomy_exists('product_brand')) {
            throw new InvalidArgumentException(
                'Таксономия product_brand не зарегистрирована.'
            );
        }

        $term = term_exists(
            $brand_name,
            'product_brand'
        );

        if (!$term) {
            $term = wp_insert_term(
                $brand_name,
                'product_brand'
            );
        }

        if (is_wp_error($term)) {
            throw new InvalidArgumentException(
                'Не удалось сохранить бренд «'
                    . $brand_name
                    . '»: '
                    . $term->get_error_message()
            );
        }

        $term_id = is_array($term)
            ? (int) $term['term_id']
            : (int) $term;

        $result = wp_set_object_terms(
            $product->get_id(),
            [$term_id],
            'product_brand',
            false
        );

        if (is_wp_error($result)) {
            throw new InvalidArgumentException(
                'Не удалось назначить бренду товар '
                    . $product->get_id()
                    . ': '
                    . $result->get_error_message()
            );
        }
    }
}
