<?php

return [

    'supplier' => 'citilux',
    'name'     => 'CITILUX',

    'fields' => [

        'external_id' => 'id',

        'sku' => [
            'source' => 'vendorCode',
            'type'   => 'string',
        ],

        'barcode' => [
            'source' => 'barcode',
            'type'   => 'string',
        ],

        'name' => 'name',

        'brand' => 'vendor',

        'description' => 'description',

        'source_url' => 'url',

        'price' => [
            'source' => 'price',
            'type'   => 'number',
        ],

        'stock' => [
            'source' => 'count',
            'type'   => 'number',
        ],

        'available' => [
            'source' => 'available',
            'type'   => 'boolean',
        ],

        'currency' => 'currencyId',
    ],


    /*
     * -------------------------------------------------
     * КАТЕГОРИИ
     * -------------------------------------------------
     */

    'categories' => [
        'source' => '_category',
    ],


    /*
     * -------------------------------------------------
     * МЕДИА
     * -------------------------------------------------
     */

    'media' => [

        'images' => [
            'fields' => [
                'picture',
            ],
        ],

        'videos' => [
            'fields' => [],
        ],

        'documents' => [],
    ],


    /*
     * -------------------------------------------------
     * АТРИБУТЫ
     * -------------------------------------------------
     */

    'attributes' => [

        // Размеры
        'length' => [
            'source' => 'param.Длина, мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'width' => [
            'source' => 'param.Ширина, см',
            'type'   => 'number',
            'unit'   => 'см',
        ],

        'height' => [
            'source' => 'param.Высота, см',
            'type'   => 'number',
            'unit'   => 'см',
        ],

        'depth' => [
            'source' => 'param.Глубина врезки, мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],


        // Электрика
        'ip' => [
            'source' => 'param.Степень защиты IP',
            'type'   => 'string',
        ],

        'power' => [
            'source' => 'param.Мощность общая, Вт',
            'type'   => 'number',
            'unit'   => 'Вт',
        ],

        'voltage' => [
            'source' => 'param.Напряжение, В',
            'type'   => 'string',
        ],


        // Свет
        'color_temperature' => [
            'source' => 'param.Цветовая температура, К',
            'type'   => 'number',
            'unit'   => 'К',
            'ignore_zero' => true,
        ],

        'light_flux' => [
            'source' => 'param.Световой поток, Lm',
            'type'   => 'number',
            'unit'   => 'лм',
        ],

        'led' => [
            'source' => 'param.Встроенные светодиоды',
            'type'   => 'boolean',
        ],

        'lamp_type' => [
            'source' => 'param.Виды ламп',
            'type'   => 'string',
        ],


        // Материалы и цвета
        'armature_material' => [
            'source' => 'param.Арматура - Материал',
            'type'   => 'string',
        ],

        'armature_color' => [
            'source' => 'param.Арматура - Цвет',
            'type'   => 'string',
        ],

        'armature_surface' => [
            'source' => 'param.Арматура - Поверхность',
            'type'   => 'string',
        ],

        'shade_material' => [
            'source' => 'param.Материал плафонов',
            'type'   => 'string',
        ],

        'shade_color' => [
            'source' => 'param.Цвет плафонов',
            'type'   => 'string',
        ],

        'shade_surface' => [
            'source' => 'param.Поверхность плафонов',
            'type'   => 'string',
        ],


        // Коллекция
        'collection' => [
            'source' => 'param.Коллекция',
            'type'   => 'string',
        ],
    ],


    /*
     * -------------------------------------------------
     * META
     * -------------------------------------------------
     */

    'meta' => [

        'manufacturer_warranty' => [
            'source' => 'manufacturer_warranty',
            'type'   => 'boolean',
        ],

        'weight' => [
            'source' => 'weight',
            'type'   => 'number',
            'unit'   => 'кг',
        ],

        'dimensions' => [
            'source' => 'dimensions',
            'type'   => 'string',
        ],

        'count' => [
            'source' => 'count',
            'type'   => 'number',
        ],

        'tn_ved' => [
            'source' => 'param.000 Код ТН ВЭД',
            'type'   => 'string',
        ],

        'supplier_barcode' => [
            'source' => 'param.ШтрихКод',
            'type'   => 'string',
        ],

        'package_height' => [
            'source' => 'param.Высота упаковки',
            'type'   => 'number',
            'unit'   => 'см',
        ],

        'package_length' => [
            'source' => 'param.Длина упаковки',
            'type'   => 'number',
            'unit'   => 'см',
        ],

        'package_width' => [
            'source' => 'param.Ширина упаковки',
            'type'   => 'number',
            'unit'   => 'см',
        ],

        'package_weight' => [
            'source' => 'param.Вес коробки',
            'type'   => 'number',
            'unit'   => 'г',
        ],
    ],
];
