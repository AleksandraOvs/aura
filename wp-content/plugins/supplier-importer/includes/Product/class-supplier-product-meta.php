<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use WC_Product;

class Supplier_Product_Meta
{
    /**
     * Сохранить дополнительные метаданные поставщика.
     *
     * Пока поддерживается только признак discontinued
     * для Crystal Lux.
     *
     * @param WC_Product  $product
     * @param Product_Data $product_data
     *
     * @return void
     */
    public function update(
        WC_Product $product,
        Product_Data $product_data
    ) {
        if ($product_data->get_supplier() !== 'crystal_lux') {
            return;
        }

        $meta = $product_data->get_meta();

        if (!array_key_exists('discontinued', $meta)) {
            return;
        }

        if (!is_bool($meta['discontinued'])) {
            throw new InvalidArgumentException(
                'Поле discontinued для Crystal Lux должно иметь булево значение.'
            );
        }

        $product_id = $product->get_id();

        if (!$product_id) {
            throw new InvalidArgumentException(
                'Невозможно сохранить метаданные: товар ещё не сохранён.'
            );
        }

        update_post_meta(
            $product->get_id(),
            '_supplier_discontinued',
            $meta['discontinued'] ? 'yes' : 'no'
        );
    }
}
