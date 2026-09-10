<?php

return [

    'supplier' => 'crystal-lux',
    'name'     => 'Crystal Lux',


    /*
     * =====================================================
     * ОСНОВНЫЕ ПОЛЯ
     * =====================================================
     */

    'fields' => [

        /*
         * <offer id="4993">
         */
        'external_id' => 'id',


        /*
         * <param name="Артикул">
         */
        'sku' => [
            'source' => 'param.Артикул',
            'type'   => 'string',
        ],


        /*
         * <param name="Штрихкод">
         */
        'barcode' => [
            'source' => 'param.Штрихкод',
            'type'   => 'string',
        ],


        /*
         * Название товара
         */
        'name' => 'model',


        /*
         * Бренд
         */
        'brand' => 'vendor',


        /*
         * Описание, если оно есть
         */
        'description' => 'description',


        /*
         * Ссылка на товар
         */
        'source_url' => 'url',


        /*
         * Цена
         */
        'price' => [
            'source' => 'price',
            'type'   => 'number',
        ],


        /*
         * Остаток.
         *
         * В этом XML есть два источника:
         *
         * <param name="stock">3</param>
         *
         * и
         *
         * <count>3</count>
         *
         * Используем <count>.
         */
        'stock' => [
            'source' => 'count',
            'type'   => 'number',
        ],


        /*
         * available="true"
         */
        'available' => [
            'source' => 'available',
            'type'   => 'boolean',
        ],


        /*
         * RUR
         */
        'currency' => 'currencyId',
    ],


    /*
     * =====================================================
     * КАТЕГОРИИ
     * =====================================================
     */

    'categories' => [

        /*
         * categoryId находится непосредственно
         * в offer:
         *
         * <categoryId>18</categoryId>
         *
         * Сам YmlParser уже связывает этот ID
         * со справочником категорий.
         */
        'source' => '_category',
    ],


    /*
     * =====================================================
     * ИЗОБРАЖЕНИЯ
     * =====================================================
     */

    'media' => [

        'images' => [

            /*
             * В XML может быть много:
             *
             * <picture>...</picture>
             * <picture>...</picture>
             */
            'fields' => [
                'picture',
            ],

        ],

    ],


    /*
     * =====================================================
     * АТРИБУТЫ
     * =====================================================
     */

    'attributes' => [

        /*
         * Стиль
         */
        'style' => [
            'source' => 'param.Стиль',
            'type'   => 'string',
        ],


        /*
         * Коллекция
         */
        'collection' => [
            'source' => 'param.Коллекция',
            'type'   => 'string',
        ],


        /*
         * Тип
         */
        'type' => [
            'source' => 'param.Тип',
            'type'   => 'string',
        ],


        /*
         * Высота изделия
         */
        'height' => [
            'source' => 'param.Высота изделия, мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],


        /*
         * Диаметр.
         *
         * Для WooCommerce это тоже числовой
         * параметр.
         */
        'diameter' => [
            'source' => 'param.Диаметр, мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],


        /*
         * Высота цепи / троса
         */
        'chain_length' => [
            'source' => 'param.Высота цепи/троса, мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],


        /*
         * Полная высота
         */
        'height_with_chain' => [
            'source' => 'param.Высота с цепью/тросом, мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],


        /*
         * Цоколь
         */
        'base' => [
            'source' => 'param.Цоколь',
            'type'   => 'string',
        ],


        /*
         * Количество лампочек
         */
        'lamp_count' => [
            'source' => 'param.Количество лампочек:',
            'type'   => 'number',
        ],


        /*
         * Мощность одной лампочки
         *
         * В файле:
         *
         * 60W
         *
         * ValueCleaner::number()
         * превратит это в 60.
         */
        'lamp_power' => [
            'source' => 'param.Мощность лампочек, Вт',
            'type'   => 'number',
            'unit'   => 'Вт',
        ],


        /*
         * IP
         *
         * В твоём файле:
         *
         * <param name="IP">20</param>
         */
        'ip' => [
            'source' => 'param.IP',
            'type'   => 'number',
        ],


        /*
         * Материал арматуры
         */
        'armature_material' => [
            'source' => 'param.Материал арматуры',
            'type'   => 'string',
        ],


        /*
         * Покрытие арматуры
         */
        'armature_coating' => [
            'source' => 'param.Покрытие арматуры',
            'type'   => 'string',
        ],


        /*
         * Цвет арматуры
         */
        'armature_color' => [
            'source' => 'param.Цвет арматуры',
            'type'   => 'string',
        ],


        /*
         * Материал плафона
         */
        'shade_material' => [
            'source' => 'param.Материал абажура/плафона',
            'type'   => 'string',
        ],


        /*
         * Цвет плафона
         */
        'shade_color' => [
            'source' => 'param.Цвет абажура/плафона',
            'type'   => 'string',
        ],


        /*
         * Декоративный элемент
         */
        'decor_element' => [
            'source' => 'param.Декоративный элемент',
            'type'   => 'string',
        ],


        /*
         * Пульт
         *
         * "нет" → false
         * "да"  → true
         */
        'remote_control' => [
            'source' => 'param.Пульт',
            'type'   => 'boolean',
        ],


        /*
         * Страна производителя
         */
        'country' => [
            'source' => 'param.Страна производитель',
            'type'   => 'string',
        ],


        /*
         * Напряжение.
         *
         * В файле:
         *
         * 220V
         *
         * Здесь оставляем строкой,
         * поскольку это фактически значение
         * с единицей измерения.
         */
        'voltage' => [
            'source' => 'param.Напряжение',
            'type'   => 'string',
        ],

    ],


    /*
     * =====================================================
     * META
     * =====================================================
     */

    'meta' => [

        /*
         * Внутренний код Crystal Lux
         */
        'supplier_code' => [
            'source' => 'param.Код',
            'type'   => 'string',
        ],


        /*
         * Рекомендации
         */
        'recommendations' => [
            'source' => 'param.Рекомендации',
            'type'   => 'string',
        ],


        /*
         * Вес товара
         */
        'weight' => [
            'source' => 'param.Вес',
            'type'   => 'number',
        ],


        /*
         * Объём товара
         */
        'volume' => [
            'source' => 'param.Объём',
            'type'   => 'number',
        ],


        /*
         * Остаток непосредственно из param.
         *
         * Это дублирует <count>,
         * поэтому оставляем как служебное поле.
         */
        'supplier_stock' => [
            'source' => 'param.stock',
            'type'   => 'number',
        ],


        /*
         * Размеры коробки
         */
        'box_length' => [
            'source' => 'param.Длина коробки 1',
            'type'   => 'number',
            'unit'   => 'см',
        ],

        'box_width' => [
            'source' => 'param.Ширина коробки 1',
            'type'   => 'number',
            'unit'   => 'см',
        ],

        'box_height' => [
            'source' => 'param.Высота коробки 1',
            'type'   => 'number',
            'unit'   => 'см',
        ],


        /*
         * Вес коробки
         */
        'box_weight' => [
            'source' => 'param.Вес коробки 1',
            'type'   => 'number',
        ],


        /*
         * Объём коробки
         */
        'box_volume' => [
            'source' => 'param.Объем коробки 1',
            'type'   => 'number',
        ],


        /*
         * ТН ВЭД
         */
        'tn_ved' => [
            'source' => 'param.ТН ВЭД',
            'type'   => 'string',
        ],

    ],

];
