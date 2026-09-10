<?php

namespace SupplierImporter\Import;

if (!defined('ABSPATH')) {
    exit;
}

class ProductImporter
{
    /**
     * Импорт одного нормализованного товара.
     *
     * Пока реализуем:
     * - поиск существующего товара по supplier + external_id;
     * - создание / обновление;
     * - название;
     * - описание;
     * - SKU;
     * - цена;
     * - старая цена;
     * - остаток;
     * - наличие;
     * - meta;
     *
     * Категории, изображения и глобальные атрибуты
     * подключим следующим этапом.
     */
    public function import(array $product): array
    {
        if (empty($product['supplier'])) {
            throw new \RuntimeException(
                'У товара отсутствует supplier.'
            );
        }

        if (empty($product['external_id'])) {
            throw new \RuntimeException(
                'У товара отсутствует external_id.'
            );
        }

        if (empty($product['name'])) {
            throw new \RuntimeException(
                'У товара отсутствует name.'
            );
        }

        $supplier    = sanitize_key($product['supplier']);
        $external_id = (string) $product['external_id'];

        /**
         * Ищем существующий товар.
         */
        $product_id = $this->findExistingProduct(
            $supplier,
            $external_id
        );

        $is_new = !$product_id;

        /**
         * Создаём объект WooCommerce.
         */
        if ($is_new) {

            $wc_product = new \WC_Product_Simple();
        } else {

            $wc_product = wc_get_product($product_id);

            if (!$wc_product) {
                throw new \RuntimeException(
                    'Не удалось загрузить товар WooCommerce #' .
                        $product_id
                );
            }
        }

        /**
         * Название.
         */
        $wc_product->set_name(
            (string) $product['name']
        );

        /**
         * Описание.
         */
        if (
            isset($product['description']) &&
            $product['description'] !== null
        ) {

            $wc_product->set_description(
                (string) $product['description']
            );
        }

        /**
         * SKU.
         *
         * Пока устанавливаем SKU напрямую.
         * На следующем этапе добавим защиту
         * от конфликтов SKU между поставщиками.
         */
        if (!empty($product['sku'])) {

            $sku = (string) $product['sku'];

            $existing_by_sku = wc_get_product_id_by_sku($sku);

            if (
                $existing_by_sku &&
                (int) $existing_by_sku !== (int) $product_id
            ) {

                throw new \RuntimeException(
                    'SKU "' . $sku .
                        '" уже используется товаром #' .
                        $existing_by_sku
                );
            }

            $wc_product->set_sku($sku);
        }

        /**
         * Цена.
         */
        if (
            isset($product['price']) &&
            $product['price'] !== null
        ) {

            $wc_product->set_regular_price(
                (string) $product['price']
            );
        }

        /**
         * Старая цена.
         *
         * Если old_price есть, используем её
         * как sale_price / regular_price позже.
         *
         * Пока не меняем логику ценовой политики.
         */
        if (
            isset($product['old_price']) &&
            $product['old_price'] !== null
        ) {

            update_post_meta(
                $wc_product->get_id(),
                '_supplier_old_price',
                $product['old_price']
            );
        }

        /**
         * Остаток.
         *
         * ВАЖНО:
         * null означает "поставщик не передал остаток".
         * В таком случае ничего не меняем.
         */
        if (
            isset($product['stock']) &&
            $product['stock'] !== null
        ) {

            $stock = (int) $product['stock'];

            $wc_product->set_manage_stock(true);
            $wc_product->set_stock_quantity($stock);

            /**
             * Пока наличие определяем по stock.
             * Позже вынесем эту логику в отдельный
             * StockMapper, потому что у поставщиков
             * available и stock могут противоречить друг другу.
             */
            $wc_product->set_stock_status(
                $stock > 0
                    ? 'instock'
                    : 'outofstock'
            );
        }

        /**
         * Сохраняем товар.
         */
        $saved_product_id = $wc_product->save();

        if (!$saved_product_id) {
            throw new \RuntimeException(
                'Не удалось сохранить товар.'
            );
        }

        /**
         * Категории.
         */
        $this->importCategories(
            $saved_product_id,
            $product['categories'] ?? []
        );

        /**
         * Атрибуты.
         */
        $this->importAttributes(
            $saved_product_id,
            $product['attributes'] ?? []
        );

        /**
         * Сохраняем связь с поставщиком.
         *
         * Пока через post meta.
         * Позже заменим это на отдельную
         * таблицу wp_supplier_products.
         */
        update_post_meta(
            $saved_product_id,
            '_supplier',
            $supplier
        );

        update_post_meta(
            $saved_product_id,
            '_supplier_external_id',
            $external_id
        );

        /**
         * Сохраняем barcode.
         */
        if (
            isset($product['barcode']) &&
            $product['barcode'] !== null
        ) {

            update_post_meta(
                $saved_product_id,
                '_supplier_barcode',
                (string) $product['barcode']
            );
        }

        /**
         * Сохраняем source URL.
         */
        if (
            isset($product['source_url']) &&
            $product['source_url'] !== null
        ) {

            update_post_meta(
                $saved_product_id,
                '_supplier_source_url',
                (string) $product['source_url']
            );
        }

        /**
         * Brand.
         */
        if (
            isset($product['brand']) &&
            $product['brand'] !== null
        ) {

            update_post_meta(
                $saved_product_id,
                '_supplier_brand',
                (string) $product['brand']
            );
        }

        /**
         * Сохраняем все meta из normalized product.
         */
        if (
            !empty($product['meta']) &&
            is_array($product['meta'])
        ) {

            foreach ($product['meta'] as $key => $value) {

                if ($value === null) {
                    continue;
                }

                /**
                 * Если meta имеет структуру:
                 *
                 * [
                 *     'value' => 12,
                 *     'unit'  => 'кг'
                 * ]
                 *
                 * сохраняем значение отдельно,
                 * единицу — отдельно.
                 */
                if (
                    is_array($value) &&
                    array_key_exists('value', $value)
                ) {

                    update_post_meta(
                        $saved_product_id,
                        '_supplier_' . sanitize_key($key),
                        $value['value']
                    );

                    if (
                        isset($value['unit']) &&
                        $value['unit'] !== ''
                    ) {

                        update_post_meta(
                            $saved_product_id,
                            '_supplier_' .
                                sanitize_key($key) .
                                '_unit',
                            $value['unit']
                        );
                    }

                    continue;
                }

                update_post_meta(
                    $saved_product_id,
                    '_supplier_' . sanitize_key($key),
                    $value
                );
            }
        }

        return [
            'success'      => true,
            'action'       => $is_new ? 'created' : 'updated',
            'product_id'   => $saved_product_id,
            'supplier'     => $supplier,
            'external_id'  => $external_id,
            'sku'          => $product['sku'] ?? '',
            'name'         => $product['name'],
        ];
    }


    /**
     * Найти товар по поставщику и external_id.
     */
    private function findExistingProduct(
        string $supplier,
        string $external_id
    ): int {

        $query = new \WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',

            'meta_query' => [
                'relation' => 'AND',

                [
                    'key'   => '_supplier',
                    'value' => $supplier,
                ],

                [
                    'key'   => '_supplier_external_id',
                    'value' => $external_id,
                ],
            ],
        ]);

        if (empty($query->posts)) {
            return 0;
        }

        return (int) $query->posts[0];
    }

    private function importCategories(
        int $product_id,
        array $categories
    ): void {

        if (empty($categories)) {
            return;
        }

        $term_ids = [];

        foreach ($categories as $category) {

            /**
             * Пока поддерживаем два варианта:
             *
             * 1. ['name' => 'Подвесные светильники']
             * 2. 'Подвесные светильники'
             */

            if (is_array($category)) {

                $name = $category['name'] ?? '';
            } else {

                $name = $category;
            }

            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            /**
             * Ищем категорию WooCommerce.
             */
            $term = term_exists(
                $name,
                'product_cat'
            );

            if (!$term) {

                $term = wp_insert_term(
                    $name,
                    'product_cat'
                );
            }

            if (
                is_wp_error($term) ||
                empty($term['term_id'])
            ) {
                continue;
            }

            $term_ids[] = (int) $term['term_id'];
        }

        if (!empty($term_ids)) {

            wp_set_object_terms(
                $product_id,
                $term_ids,
                'product_cat',
                false
            );
        }
    }

    private function importAttributes(
        int $product_id,
        array $attributes
    ): void {

        if (empty($attributes)) {
            return;
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return;
        }

        $wc_attributes = [];

        foreach ($attributes as $attribute_name => $value) {

            if ($value === null || $value === '') {
                continue;
            }

            /**
             * Название taxonomy.
             *
             * Например:
             *
             * collection → pa_collection
             * lamp_type  → pa_lamp_type
             */
            $taxonomy = 'pa_' . sanitize_title(
                $attribute_name
            );

            /**
             * Значение + единица измерения.
             */
            if (
                is_array($value) &&
                array_key_exists('value', $value)
            ) {

                $attribute_value =
                    (string) $value['value'];

                if (
                    isset($value['unit']) &&
                    $value['unit'] !== ''
                ) {

                    $attribute_value .= ' ' .
                        $value['unit'];
                }
            } else {

                /**
                 * Массив значений.
                 *
                 * Например:
                 *
                 * [
                 *     'ГЛН',
                 *     'LED'
                 * ]
                 */
                if (is_array($value)) {

                    $values = [];

                    foreach ($value as $item) {

                        if (
                            is_array($item) &&
                            isset($item['value'])
                        ) {

                            $item_value =
                                (string) $item['value'];

                            if (
                                isset($item['unit']) &&
                                $item['unit'] !== ''
                            ) {

                                $item_value .= ' ' .
                                    $item['unit'];
                            }

                            $values[] = $item_value;
                        } else {

                            $values[] =
                                (string) $item;
                        }
                    }

                    $attribute_value =
                        implode(', ', $values);
                } else {

                    $attribute_value =
                        (string) $value;
                }
            }

            $attribute_value = trim(
                $attribute_value
            );

            if ($attribute_value === '') {
                continue;
            }

            /**
             * Создаём глобальный атрибут,
             * если его ещё нет.
             */
            $attribute_id =
                $this->getOrCreateAttribute(
                    $attribute_name
                );

            if (!$attribute_id) {
                continue;
            }

            /**
             * Проверяем, существует ли taxonomy.
             */
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            /**
             * Создаём term.
             */
            $term = term_exists(
                $attribute_value,
                $taxonomy
            );

            if (!$term) {

                $term = wp_insert_term(
                    $attribute_value,
                    $taxonomy
                );
            }

            if (
                is_wp_error($term) ||
                empty($term['term_id'])
            ) {
                continue;
            }

            $term_id = (int) $term['term_id'];

            /**
             * Привязываем term к товару.
             */
            wp_set_object_terms(
                $product_id,
                [$term_id],
                $taxonomy,
                false
            );

            /**
             * Формируем объект WooCommerce attribute.
             */
            $attribute = new \WC_Product_Attribute();

            $attribute->set_id(
                $attribute_id
            );

            $attribute->set_name(
                $taxonomy
            );

            $attribute->set_options(
                [$term_id]
            );

            /**
             * Видимость на странице товара.
             */
            $attribute->set_visible(true);

            /**
             * Использовать в вариациях.
             *
             * Пока false.
             */
            $attribute->set_variation(false);

            $wc_attributes[] = $attribute;
        }

        if (!empty($wc_attributes)) {

            $product->set_attributes(
                $wc_attributes
            );

            $product->save();
        }
    }

    private function getOrCreateAttribute(
        string $attribute_name
    ): int {

        $taxonomy = 'pa_' . sanitize_title(
            $attribute_name
        );

        /**
         * Атрибут уже существует.
         */
        $attribute_id =
            wc_attribute_taxonomy_id_by_name(
                $attribute_name
            );

        if ($attribute_id) {
            return (int) $attribute_id;
        }

        /**
         * Создаём новый глобальный атрибут.
         */
        $attribute_id =
            wc_create_attribute([
                'name'         => $attribute_name,
                'slug'         => sanitize_title(
                    $attribute_name
                ),
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);

        if (is_wp_error($attribute_id)) {
            return 0;
        }

        /**
         * WooCommerce не всегда сразу
         * регистрирует taxonomy после создания
         * атрибута.
         *
         * Обновляем taxonomies.
         */
        delete_transient(
            'wc_attribute_taxonomies'
        );

        \WC_Cache_Helper::invalidate_cache_group(
            'woocommerce-attributes'
        );

        /**
         * Регистрируем taxonomy вручную,
         * если она ещё не зарегистрирована.
         */
        if (!taxonomy_exists($taxonomy)) {

            register_taxonomy(
                $taxonomy,
                ['product'],
                [
                    'hierarchical' => false,
                    'show_ui'      => false,
                    'query_var'    => true,
                    'rewrite'      => false,
                ]
            );
        }

        return (int) $attribute_id;
    }
}
