<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

//use WC_Product;

class Product_Finder
{
    /**
     * Мета-поле поставщика.
     */
    private const SUPPLIER_META = '_supplier';

    /**
     * Мета-поле ID товара у поставщика.
     */
    private const SUPPLIER_ID_META = '_supplier_id';

    /**
     * Мета-поле штрихкода.
     */
    private const BARCODE_META = '_barcode';

    /**
     * Найти товар по SKU.
     *
     * @param string $sku
     *
     * @return WC_Product|null
     */
    public function find_by_sku($sku)
    {
        $sku = trim((string) $sku);

        if ($sku === '') {
            return null;
        }

        $product_id = wc_get_product_id_by_sku($sku);

        if (!$product_id) {
            return null;
        }

        $product = wc_get_product($product_id);

        if (!$product instanceof WC_Product) {
            return null;
        }

        return $product;
    }

    /**
     * Найти товар по поставщику и ID товара у поставщика.
     *
     * @param string $supplier
     * @param string $supplier_id
     *
     * @return WC_Product|null
     */
    public function find_by_supplier($supplier, $supplier_id)
    {
        $supplier = trim((string) $supplier);
        $supplier_id = trim((string) $supplier_id);

        if ($supplier === '' || $supplier_id === '') {
            return null;
        }

        $query = new \WP_Query([
            'post_type'      => [
                'product',
                'product_variation',
            ],
            'post_status'    => [
                'publish',
                'draft',
                'pending',
                'private',
            ],
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'no_found_rows'  => true,

            'meta_query'     => [
                'relation' => 'AND',

                [
                    'key'     => self::SUPPLIER_META,
                    'value'   => $supplier,
                    'compare' => '=',
                ],

                [
                    'key'     => self::SUPPLIER_ID_META,
                    'value'   => $supplier_id,
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($query->posts)) {
            return null;
        }

        if (count($query->posts) > 1) {
            throw new \RuntimeException(
                sprintf(
                    'Найдено несколько товаров поставщика "%s" '
                        . 'с ID "%s". Импорт остановлен во избежание '
                        . 'обновления неоднозначного товара.',
                    $supplier,
                    $supplier_id
                )
            );
        }

        $product_id = (int) $query->posts[0];

        $product = wc_get_product($product_id);

        if (!$product instanceof WC_Product) {
            return null;
        }

        return $product;
    }


    /**
     * Найти товар по штрихкоду.
     *
     * @param string $barcode
     *
     * @return WC_Product|null
     *
     * @throws \RuntimeException Если штрихкод принадлежит нескольким товарам.
     */
    public function find_by_barcode($barcode)
    {
        $barcode = trim((string) $barcode);

        if ($barcode === '') {
            return null;
        }

        $query = new \WP_Query([
            'post_type'      => [
                'product',
                'product_variation',
            ],
            'post_status'    => [
                'publish',
                'draft',
                'pending',
                'private',
            ],
            'posts_per_page' => 2,
            'fields'         => 'ids',
            'no_found_rows'  => true,

            'meta_query'     => [
                [
                    'key'     => self::BARCODE_META,
                    'value'   => $barcode,
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($query->posts)) {
            return null;
        }

        if (count($query->posts) > 1) {
            throw new \RuntimeException(
                sprintf(
                    'Штрихкод "%s" указан у нескольких товаров. '
                        . 'Импорт остановлен во избежание обновления '
                        . 'неоднозначного товара.',
                    $barcode
                )
            );
        }

        $product_id = (int) $query->posts[0];

        $product = wc_get_product($product_id);

        if (!$product instanceof WC_Product) {
            return null;
        }

        return $product;
    }


    /**
     * Проверить, принадлежит ли товар поставщику
     * и совпадает ли ID товара у поставщика.
     *
     * Если у существующего товара нет supplier_id,
     * автоматическое обновление запрещается.
     *
     * @param WC_Product $product
     * @param string     $supplier
     * @param string     $supplier_id
     *
     * @return bool
     */
    private function belongs_to_supplier(
        WC_Product $product,
        $supplier,
        $supplier_id
    ) {
        $product_supplier = (string) get_post_meta(
            $product->get_id(),
            self::SUPPLIER_META,
            true
        );

        $existing_supplier_id = trim(
            (string) get_post_meta(
                $product->get_id(),
                self::SUPPLIER_ID_META,
                true
            )
        );

        $supplier = trim((string) $supplier);
        $supplier_id = trim((string) $supplier_id);

        if (
            $supplier === ''
            || $product_supplier !== $supplier
        ) {
            return false;
        }

        if (
            $supplier_id === ''
            || $existing_supplier_id === ''
        ) {
            return false;
        }

        return $existing_supplier_id === $supplier_id;
    }

    /**
     * Найти товар по данным поставщика.
     *
     * Сначала ищем по уникальному ID поставщика.
     * Затем по SKU и штрихкоду с проверкой принадлежности товара.
     *
     * @param Product_Data $product_data
     *
     * @return WC_Product|null
     *
     * @throws \RuntimeException Если идентификатор принадлежит
     *                            другому поставщику или владелец неизвестен.
     */
    public function find(Product_Data $product_data)
    {
        $supplier    = trim((string) $product_data->get_supplier());
        $supplier_id = trim((string) $product_data->get_supplier_id());
        $sku         = trim((string) $product_data->get_sku());
        $barcode     = trim((string) $product_data->get('barcode'));

        \Supplier_Importer\Core\Logger::info(
            'FINDER: START supplier=' . $supplier
                . ', supplier_id=' . $supplier_id
                . ', SKU=' . $sku
        );

        /*
     * 1. Основной поиск:
     * поставщик + ID товара у поставщика.
     */
        if ($supplier !== '' && $supplier_id !== '') {
            $product = $this->find_by_supplier(
                $supplier,
                $supplier_id
            );

            if ($product) {
                if ($sku !== '') {
                    $sku_product = $this->find_by_sku($sku);

                    if (
                        $sku_product
                        && $sku_product->get_id() !== $product->get_id()
                    ) {
                        throw new \RuntimeException(
                            sprintf(
                                'Нельзя обновить товар ID %d: SKU "%s" '
                                    . 'уже принадлежит другому товару ID %d.',
                                $product->get_id(),
                                $sku,
                                $sku_product->get_id()
                            )
                        );
                    }
                }

                \Supplier_Importer\Core\Logger::info(
                    'FINDER: FOUND BY SUPPLIER ID, product_id='
                        . $product->get_id()
                );

                return $product;
            }
        }

        /*
     * 2. Поиск по SKU.
     *
     * Совпадение SKU само по себе недостаточно:
     * проверяем, что товар принадлежит текущему поставщику.
     */
        if ($sku !== '') {
            $product = $this->find_by_sku($sku);

            if ($product) {
                if (
                    $this->belongs_to_supplier(
                        $product,
                        $supplier,
                        $supplier_id
                    )
                ) {
                    \Supplier_Importer\Core\Logger::info(
                        'FINDER: FOUND BY SKU, product_id='
                            . $product->get_id()
                    );

                    return $product;
                }

                $product_supplier = get_post_meta(
                    $product->get_id(),
                    self::SUPPLIER_META,
                    true
                );

                \Supplier_Importer\Core\Logger::info(
                    'FINDER: SKU COLLISION'
                        . ', SKU=' . $sku
                        . ', current_supplier=' . $supplier
                        . ', existing_supplier='
                        . ($product_supplier !== ''
                            ? $product_supplier
                            : 'unknown')
                        . ', existing_product_id=' . $product->get_id()
                );

                throw new \RuntimeException(
                    sprintf(
                        'Нельзя импортировать товар поставщика "%s": '
                            . 'SKU "%s" уже используется товаром ID %d. '
                            . 'Товар не обновлён, так как его принадлежность '
                            . 'текущему поставщику не подтверждена.',
                        $supplier,
                        $sku,
                        $product->get_id()
                    )
                );
            }
        }

        /*
     * 3. Поиск по штрихкоду.
     *
     * Аналогично SKU, совпадение штрихкода не даёт права
     * обновлять товар другого или неизвестного поставщика.
     */
        if ($barcode !== '') {
            $product = $this->find_by_barcode($barcode);

            if ($product) {
                if (
                    $this->belongs_to_supplier(
                        $product,
                        $supplier,
                        $supplier_id
                    )
                ) {
                    \Supplier_Importer\Core\Logger::info(
                        'FINDER: FOUND BY BARCODE, product_id='
                            . $product->get_id()
                    );

                    return $product;
                }

                $product_supplier = get_post_meta(
                    $product->get_id(),
                    self::SUPPLIER_META,
                    true
                );

                \Supplier_Importer\Core\Logger::info(
                    'FINDER: BARCODE COLLISION'
                        . ', barcode=' . $barcode
                        . ', current_supplier=' . $supplier
                        . ', existing_supplier='
                        . ($product_supplier !== ''
                            ? $product_supplier
                            : 'unknown')
                        . ', existing_product_id=' . $product->get_id()
                );

                throw new \RuntimeException(
                    sprintf(
                        'Нельзя импортировать товар поставщика "%s": '
                            . 'штрихкод "%s" уже используется товаром ID %d. '
                            . 'Товар не обновлён, так как его принадлежность '
                            . 'текущему поставщику не подтверждена.',
                        $supplier,
                        $barcode,
                        $product->get_id()
                    )
                );
            }
        }

        \Supplier_Importer\Core\Logger::info(
            'FINDER: NOT FOUND, supplier=' . $supplier
                . ', supplier_id=' . $supplier_id
                . ', SKU=' . $sku
        );

        return null;
    }

    /**
     * Проверить существование товара по SKU.
     *
     * @param string $sku
     *
     * @return bool
     */
    public function exists_by_sku($sku)
    {
        return $this->find_by_sku($sku) !== null;
    }

    /**
     * Проверить существование товара по поставщику.
     *
     * @param string $supplier
     * @param string $supplier_id
     *
     * @return bool
     */
    public function exists_by_supplier($supplier, $supplier_id)
    {
        return $this->find_by_supplier(
            $supplier,
            $supplier_id
        ) !== null;
    }

    /**
     * Проверить существование товара по штрихкоду.
     *
     * @param string $barcode
     *
     * @return bool
     */
    public function exists_by_barcode($barcode)
    {
        return $this->find_by_barcode($barcode) !== null;
    }
}
