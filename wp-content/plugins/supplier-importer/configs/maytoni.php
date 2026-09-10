<?php

return [

    'supplier' => 'maytoni',
    'name'     => 'Maytoni',

    'fields' => [

        'external_id' => 'id',

        'sku' => 'vendorCode',

        'barcode' => 'barcode',

        'name' => 'name',

        'brand' => 'brand_name',

        'categories' => [
            'source' => 'Категория',
        ],

        'description' => 'description',

        'source_url' => 'url',

        'price' => [
            'source' => 'price',
            'type'   => 'number',
        ],

        'old_price' => [
            'source' => 'Oldprice',
            'type'   => 'number',
        ],

        'stock' => [
            'source' => 'Stock',
            'type'   => 'number',
        ],

        'available' => [
            'source' => 'available',
            'type'   => 'boolean',
        ],

        'currency' => 'currencyId',
    ],

    'media' => [

        'images' => [
            'pattern' => '/^Фото\d+$/u',
        ],

        'videos' => [
            'pattern' => '/^Видео\d+$/u',
        ],

        'documents' => [
            'instruction' => 'Инструкция',
            'ies'         => 'IES',
            '3d_model'    => '3D-модель',
        ],
    ],

    'attributes' => [

        // Основные
        'style' => [
            'source' => 'style_name',
            'type'   => 'string',
        ],

        'collection' => [
            'source' => 'collection_name',
            'type'   => 'string',
        ],

        'series' => [
            'source' => 'series_name',
            'type'   => 'string',
        ],

        'warranty' => [
            'source' => 'Гарантия',
            'type'   => 'number',
            'unit'   => 'лет',
        ],

        'delivery' => [
            'source' => 'delivery',
            'type'   => 'boolean',
        ],

        // Электрика
        'voltage' => [
            'source' => 'Напряжение',
            'type'   => 'string',
        ],

        'electrical_protection_class' => [
            'source' => 'КлассЭлектрозащиты',
            'type'   => 'string',
        ],

        'ip' => [
            'source' => 'ingress_protection_rating',
            'type'   => 'string',
        ],

        'tn_ved' => [
            'source' => 'КодТНВЭД',
            'type'   => 'string',
        ],

        // Материалы и цвета
        'armature_color' => [
            'source' => 'ЦветАрматуры',
            'type'   => 'string',
        ],

        'armature_material' => [
            'source' => 'МатериалАрматуры',
            'type'   => 'string',
        ],

        'crystal_color' => [
            'source' => 'ЦветХрусталя',
            'type'   => 'string',
        ],

        // Размеры
        'diameter' => [
            'source' => 'Диаметр',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'length' => [
            'source' => 'Длина',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'width' => [
            'source' => 'Ширина',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'height' => [
            'source' => 'Высота',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'minimum_height' => [
            'source' => 'МинимальнаяВысота',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        // Встраиваемые светильники
        'mounting_hole_shape' => [
            'source' => 'ФормаМонтажногоОтверстияВстраиваемогоСветильника',
            'type'   => 'string',
        ],

        'recessed_mounting_type' => [
            'source' => 'ТипМонтажаВстраиваемогоСветильника',
            'type'   => 'string',
        ],

        'mounting_hole_diameter' => [
            'source' => 'ДиаметрМонтажногоОтверстияВстраиваемогоСветильника',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'mounting_hole_depth' => [
            'source' => 'ГлубинаМонтажногоОтверстияВстраиваемогоСветильника',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'mounting_hole_width' => [
            'source' => 'ШиринаМонтажногоОтверстияВстраиваемогоСветильника',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'mounting_hole_length' => [
            'source' => 'ДлинаМонтажногоОтверстияВстраиваемогоСветильника',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        // Цепь
        'chain' => [
            'source' => 'Цепь',
            'type'   => 'string',
        ],

        'chain_length' => [
            'source' => 'ДлинаЦепи',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        // Чаша крепления
        'mounting_cup' => [
            'source' => 'Чаша крепления',
            'type'   => 'string',
        ],

        'mounting_cup_shape' => [
            'source' => 'ФормаЧашиКрепления',
            'type'   => 'string',
        ],

        'mounting_cup_width' => [
            'source' => 'ШиринаЧашиКрепления',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'mounting_cup_length' => [
            'source' => 'ДлинаЧашиКрепления',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'mounting_cup_diameter' => [
            'source' => 'ДиаметрЧашиКрепления',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'mounting_cup_height' => [
            'source' => 'ВысотаЧашиКрепления',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        // Абажур
        'shade_shape' => [
            'source' => 'ФормаАбажура',
            'type'   => 'string',
        ],

        'shade_mounting_type' => [
            'source' => 'ТипКрепленияАбажура',
            'type'   => 'string',
        ],

        'shade_material' => [
            'source' => 'МатериалАбажура',
            'type'   => 'string',
        ],

        'shade_color' => [
            'source' => 'ЦветАбажура',
            'type'   => 'string',
        ],

        'shade_height' => [
            'source' => 'ВысотаАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_width' => [
            'source' => 'ШиринаАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_length' => [
            'source' => 'ДлинаАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_top_diameter' => [
            'source' => 'lampshade_top_diameter',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_bottom_diameter' => [
            'source' => 'НижнийДиаметрАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_top_width' => [
            'source' => 'ВерхняяШиринаАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_bottom_width' => [
            'source' => 'НижняяШиринаАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_top_length' => [
            'source' => 'ВерхняяДлинаАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_bottom_length' => [
            'source' => 'НижняяДлинаАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_diameter' => [
            'source' => 'ДиаметрАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'shade_radius' => [
            'source' => 'РадиусАбажура',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        // Лампы
        'led' => [
            'source' => 'lamp_is_led',
            'type'   => 'boolean',
        ],

        'lamps_included' => [
            'source' => 'ЛампыВКомплекте',
            'type'   => 'boolean',
        ],

        'base' => [
            'source' => 'Цоколь',
            'type'   => 'string',
        ],

        'lamp_count' => [
            'source' => 'КоличествоЛамп',
            'type'   => 'number',
        ],

        'power' => [
            'source' => 'Мощность',
            'type'   => 'number',
            'unit'   => 'Вт',
        ],

        'light_flux' => [
            'source' => 'СветовойПоток',
            'type'   => 'number',
            'unit'   => 'лм',
        ],

        'beam_angle' => [
            'source' => 'УголРассеивания',
            'type'   => 'number',
            'unit'   => '°',
        ],

        'cri' => [
            'source' => 'ИндексЦветопередачи',
            'type'   => 'number',
        ],

        'color_temperature' => [
            'source' => 'ЦветоваяТемпература',
            'type'   => 'string',
            'unit'   => 'К',
            'ignore_zero' => true,
        ],

        'incandescent_equivalent' => [
            'source' => 'АналогЛампыНакаливания',
            'type'   => 'string',
        ],

        // Светодиоды
        'led_count_per_meter' => [
            'source' => 'КоличествоСветодиодовНаМетр',
            'type'   => 'number',
            'unit'   => 'шт/м',
        ],

        // Дополнительные характеристики
        'unit' => [
            'source' => 'ЕдиницаИзмерения',
            'type'   => 'string',
        ],

        'compatibility_collections' => [
            'source' => 'СовместимостьКоллекции',
            'type'   => 'string',
        ],

        'cutting_step' => [
            'source' => 'КратностьРезки',
            'type'   => 'number',
        ],

        'operating_temperature' => [
            'source' => 'РабочаяТемпература',
            'type'   => 'string',
        ],

        'led_type' => [
            'source' => 'Тип светодиодов',
            'type'   => 'string',
        ],

        'control_protocol' => [
            'source' => 'Протокол управления',
            'type'   => 'string',
        ],
    ],

    'meta' => [

        'manufacturer_warranty' => [
            'source' => 'manufacturer_warranty',
            'type'   => 'boolean',
        ],

        'ozon_special_price' => [
            'source' => 'outOzon',
            'type'   => 'number',
        ],

        'cartons_dimensions' => [
            'source' => 'cartons_dimensions',
            'type'   => 'string',
        ],

        'ozon_price' => [
            'source' => 'priceOzYM',
            'type'   => 'number',
        ],

        'wb_price' => [
            'source' => 'priceWB',
            'type'   => 'number',
        ],

        'net_weight' => [
            'source' => 'ВесНетто',
            'type'   => 'number',
            'unit'   => 'кг',
        ],

        'gross_weight' => [
            'source' => 'ВесБрутто',
            'type'   => 'number',
            'unit'   => 'кг',
        ],

        'country' => [
            'source' => 'Страна происхождения',
            'type'   => 'string',
        ],

        'type_size' => [
            'source' => 'Типоразмер',
            'type'   => 'string',
        ],

        'color_code' => [
            'source' => 'ЦветовойКод',
            'type'   => 'string',
        ],

        'is_new' => [
            'source' => 'ЭтоНовинка',
            'type'   => 'boolean',
        ],

        'status_ru' => [
            'source' => 'СтатусRu_Ru',
            'type'   => 'string',
        ],

        'marker' => [
            'source' => 'Маркер',
            'type'   => 'string',
        ],

        'catalog_description' => [
            'source' => 'Описание для каталога',
            'type'   => 'string',
        ],

        'full_name' => [
            'source' => 'Полное наименование',
            'type'   => 'string',
        ],

        'cartons' => [
            'source' => 'cartons',
            'type'   => 'number',
        ],

        'color_temperature_min' => [
            'source' => 'ЦветоваяТемператураМин',
            'type'   => 'number',
            'unit'   => 'К',
        ],

        'color_temperature_max' => [
            'source' => 'ЦветоваяТемператураМакс',
            'type'   => 'number',
            'unit'   => 'К',
        ],

        'box_volume' => [
            'source' => 'Объем коробки, м3',
            'type'   => 'number',
            'unit'   => 'м³',
        ],

        'recommended_lamps' => [
            'source' => 'РекомендуемыеЛампы',
            'type'   => 'string',
        ],

        'accessories' => [
            'source' => 'Аксессуары',
            'type'   => 'string',
        ],

        'series_text' => [
            'source' => 'Текст о серии',
            'type'   => 'string',
        ],

        'arrival_date' => [
            'source' => 'ДатаПрихода',
            'type'   => 'string',
        ],

        'item_type' => [
            'source' => 'ВидНоменклатуры',
            'type'   => 'string',
        ],

        'price_per_meter' => [
            'source' => 'Цена за 1м',
            'type'   => 'number',
        ],

        'youtube' => [
            'source' => 'Youtube',
            'type'   => 'string',
        ],

        'youtube_series' => [
            'source' => 'Youtube серия',
            'type'   => 'string',
        ],

        'compatibility_collections' => [
            'source' => 'СовместимостьКоллекции',
            'type'   => 'string',
        ],


    ],
];
