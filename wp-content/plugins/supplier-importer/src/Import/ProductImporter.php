<?php

namespace SupplierImporter\Import;

if (!defined('ABSPATH')) {
    exit;
}

class ProductImporter
{
    private AttributeMapper $attribute_mapper;


    public function __construct()
    {
        $this->attribute_mapper =
            new AttributeMapper();
    }

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
         * Импортируем изображения.
         */
        $image_result = [
            'imported' => 0,
            'skipped'  => 0,
            'failed'   => 0,
        ];

        if (
            !empty($product['images']) &&
            is_array($product['images'])
        ) {

            $image_importer = new ImageImporter();

            $image_result = $image_importer->import(
                $saved_product_id,
                $product['images']
            );
        }

        if (
            !empty($product['attributes']) &&
            is_array($product['attributes'])
        ) {

            $this->importAttributes(
                $wc_product,
                $product['attributes']
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
            'image_import' => $image_result,
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
        \WC_Product $product,
        array $attributes
    ): void {

        if (empty($attributes)) {
            return;
        }

        /*
     * Получаем уже существующие атрибуты товара.
     *
     * Мы НЕ будем их удалять.
     */
        $existing_attributes = $product->get_attributes();

        /*
     * Индексируем существующие атрибуты по taxonomy.
     */
        $merged_attributes = [];

        foreach ($existing_attributes as $existing_attribute) {

            $name = $existing_attribute->get_name();

            if (!$name) {
                continue;
            }

            $merged_attributes[$name] = $existing_attribute;
        }


        /*
     * Обрабатываем атрибуты поставщика.
     */
        foreach ($attributes as $attribute_name => $attribute_data) {

            /*
         * Значение + единица измерения
         */
            $unit = '';

            if (
                is_array($attribute_data) &&
                array_key_exists('value', $attribute_data)
            ) {

                $value = $attribute_data['value'];

                if (
                    isset($attribute_data['unit']) &&
                    $attribute_data['unit'] !== null
                ) {
                    $unit = (string) $attribute_data['unit'];
                }
            } else {

                $value = $attribute_data;
            }


            /*
         * Пустое значение от поставщика
         * не должно уничтожать существующий атрибут.
         */
            if ($value === null || $value === '') {
                continue;
            }


            /*
         * Получаем существующий атрибут
         * или создаём новый.
         */
            $attribute = $this->getOrCreateAttribute(
                $attribute_name
            );

            if (!$attribute) {
                continue;
            }

            $taxonomy = $attribute['taxonomy'];


            /*
         * Значение может быть одиночным:
         *
         * "LED"
         *
         * или массивом:
         *
         * ["ГЛН", "LED"]
         */
            $values = is_array($value)
                ? $value
                : [$value];


            $term_ids = [];


            foreach ($values as $term_value) {

                if (
                    $term_value === null ||
                    $term_value === ''
                ) {
                    continue;
                }

                $term_value = trim(
                    (string) $term_value
                );

                if ($term_value === '') {
                    continue;
                }


                /*
             * Ищем существующее значение.
             */
                $term = term_exists(
                    $term_value,
                    $taxonomy
                );


                /*
             * Если значения нет — создаём.
             */
                if (!$term) {

                    $term = wp_insert_term(
                        $term_value,
                        $taxonomy
                    );
                }


                if (is_wp_error($term)) {

                    throw new \RuntimeException(
                        'Не удалось создать значение "' .
                            $term_value .
                            '" для атрибута "' .
                            $attribute['name'] .
                            '": ' .
                            $term->get_error_message()
                    );
                }


                if (is_array($term)) {

                    $term_ids[] = (int) $term['term_id'];
                } else {

                    $term_ids[] = (int) $term;
                }
            }


            /*
         * Если ни одного значения не получилось,
         * существующий атрибут оставляем как есть.
         */
            if (empty($term_ids)) {
                continue;
            }


            /*
         * Привязываем новые значения к товару.
         *
         * false = полностью заменяем значения
         * ИМЕННО ЭТОГО атрибута.
         *
         * Другие атрибуты товара не затрагиваются.
         */
            wp_set_object_terms(
                $product->get_id(),
                $term_ids,
                $taxonomy,
                false
            );


            /*
         * Создаём объект атрибута товара.
         */
            $wc_attribute = new \WC_Product_Attribute();

            $wc_attribute->set_id(
                $attribute['id']
            );

            $wc_attribute->set_name(
                $taxonomy
            );

            $wc_attribute->set_options(
                $term_ids
            );

            /*
         * Если такой атрибут уже существовал,
         * сохраняем его позицию.
         */
            if (isset($merged_attributes[$taxonomy])) {

                $position =
                    $merged_attributes[$taxonomy]
                    ->get_position();
            } else {

                $position = count($merged_attributes);
            }

            $wc_attribute->set_position(
                $position
            );

            $wc_attribute->set_visible(true);

            $wc_attribute->set_variation(false);


            /*
         * Обновляем только этот атрибут
         * в общем массиве.
         */
            $merged_attributes[$taxonomy] =
                $wc_attribute;


            /*
         * Единицу сохраняем отдельно.
         */
            if ($unit !== '') {

                update_post_meta(
                    $product->get_id(),
                    '_supplier_attribute_' .
                        sanitize_key($attribute_name) .
                        '_unit',
                    $unit
                );
            }
        }


        /*
     * Сохраняем объединённый набор атрибутов.
     */
        if (!empty($merged_attributes)) {

            $product->set_attributes(
                array_values($merged_attributes)
            );

            $product->save();
        }
    }

    private function getOrCreateAttribute(
        string $normalized_name
    ): ?array {

        $attribute = $this->attribute_mapper->get(
            $normalized_name
        );

        if (!$attribute) {
            return null;
        }

        $slug = $attribute['slug'];
        $name = $attribute['name'];

        $taxonomy = 'pa_' . $slug;


        /*
     * 1. Ищем существующий атрибут WooCommerce
     */
        $attribute_id = wc_attribute_taxonomy_id_by_name(
            $slug
        );


        /*
     * Атрибут уже существует
     */
        if ($attribute_id) {

            /*
         * На всякий случай проверяем,
         * зарегистрирована ли taxonomy.
         */
            if (!taxonomy_exists($taxonomy)) {

                $this->registerAttributeTaxonomy(
                    $taxonomy
                );
            }

            return [
                'id'       => (int) $attribute_id,
                'slug'     => $slug,
                'taxonomy' => $taxonomy,
                'name'     => $name,
            ];
        }


        /*
     * 2. Атрибута нет — создаём
     */
        $attribute_id = wc_create_attribute([
            'name'         => $name,
            'slug'         => $slug,
            'type'         => 'select',
            'order_by'     => 'menu_order',
            'has_archives' => false,
        ]);


        if (is_wp_error($attribute_id)) {

            throw new \RuntimeException(
                'Не удалось создать атрибут "' .
                    $name .
                    '": ' .
                    $attribute_id->get_error_message()
            );
        }


        /*
     * 3. Сбрасываем кэш атрибутов WooCommerce
     */
        delete_transient(
            'wc_attribute_taxonomies'
        );


        /*
     * 4. Регистрируем taxonomy
     * прямо в текущем запросе.
     */
        if (!taxonomy_exists($taxonomy)) {

            $this->registerAttributeTaxonomy(
                $taxonomy
            );
        }


        return [
            'id'       => (int) $attribute_id,
            'slug'     => $slug,
            'taxonomy' => $taxonomy,
            'name'     => $name,
        ];
    }

    /**
     * Регистрирует taxonomy глобального атрибута
     * в текущем запросе WordPress.
     */
    private function registerAttributeTaxonomy(
        string $taxonomy
    ): void {

        if (taxonomy_exists($taxonomy)) {
            return;
        }

        register_taxonomy(
            $taxonomy,
            ['product'],
            [
                'hierarchical'      => false,
                'show_ui'           => false,
                'show_admin_column' => false,
                'query_var'         => true,
                'rewrite'           => false,
                'public'            => false,
                'show_in_nav_menus' => false,
            ]
        );
    }
}
