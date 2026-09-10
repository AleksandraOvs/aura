<?php

return [

    'supplier' => 'lightstar',
    'name'     => 'Lightstar',

    'fields' => [

        'external_id' => 'Артикул',

        'sku' => [
            'source' => 'Артикул',
            'type'   => 'string',
        ],

        'barcode' => [
            'source' => 'Штрихкоды',
            'type'   => 'string',
        ],

        'name' => [
            'source' => 'Наименование для интернет-магазина',
            'type'   => 'string',
        ],

        'description' => [
            'source' => 'Название позиции внутреннее',
            'type'   => 'string',
        ],

        'price' => [
            'source' => 'Розничная',
            'type'   => 'number',
        ],

        'brand' => [
            'value' => 'Lightstar',
        ],

        'currency' => [
            'value' => 'RUB',
        ],
    ],

    'categories' => [
        'callback' => function ($row) {

            echo '<pre style="background:#fff;padding:20px;border:2px solid red;">';

            echo "КОЛОНКИ 30-40:\n\n";

            foreach ($row as $key => $value) {

                static $index = 0;

                if ($index >= 30 && $index <= 40) {
                    echo '[' . $index . '] ';
                    var_dump($key);
                    echo ' => ';
                    var_dump($value);
                    echo "\n";
                }

                $index++;
            }

            echo '</pre>';


            $value = null;

            // Ищем колонку независимо от точного написания
            foreach ($row as $key => $rowValue) {

                $normalizedKey = mb_strtolower(trim($key));

                if ($normalizedKey === 'дополнительная категория') {
                    $value = $rowValue;
                    break;
                }
            }

            $value = \SupplierImporter\Normalizer\ValueCleaner::clean($value);

            if ($value === null) {
                return [];
            }

            // Убираем служебное начало пути
            $value = preg_replace(
                '/^…[\/\\\\]*/u',
                '',
                $value
            );

            // Разбиваем путь категории
            $parts = preg_split(
                '/[\/\\\\]+/',
                $value
            );

            $result = [];

            foreach ($parts as $part) {

                $part = \SupplierImporter\Normalizer\ValueCleaner::clean($part);

                if ($part !== null) {
                    $result[] = $part;
                }
            }

            return $result;
        },
    ],

    'media' => [

        'images' => [
            'fields' => [
                'Фото',
            ],
        ],

        'videos' => [
            'fields' => [
                'Видео',
            ],
        ],

        'documents' => [
            'instruction' => 'Инструкция',
            'ies'         => 'iES-файл',
            '3d_model'    => '3D-модель',
            'drawing'     => 'Чертёж',
            '3d_preview'  => '3D preview',
            'scale_scheme' => 'Схема в масштабе',
            'sketchfab'    => '3D sketchfab',
        ],
    ],

    'attributes' => [

        'collection' => [
            'source' => 'Коллекция',
            'type'   => 'string',
        ],

        'style' => [
            'source' => 'Стиль',
            'type'   => 'string',
        ],

        'lamp_type' => [
            'source' => 'Тип лампы',
            'type'   => 'string',
        ],

        'base' => [
            'source' => 'Цоколь ламп',
            'type'   => 'string',
        ],

        'voltage' => [
            'source' => 'Напряжение питания, В',
            'type'   => 'string',
        ],

        'ip' => [
            'source' => 'Пылевлагозащита, IP',
            'type'   => 'string',
        ],

        'armature_material' => [
            'source' => 'Материал арматуры',
            'type'   => 'string',
        ],

        'armature_color' => [
            'source' => 'Цвет арматуры',
            'type'   => 'string',
        ],

        'shade_material' => [
            'source' => 'Материал плафона',
            'type'   => 'string',
        ],

        'shade_color' => [
            'source' => 'Цвет плафона',
            'type'   => 'string',
        ],

        'lamp_count' => [
            'source' => 'Количество ламп, шт',
            'type'   => 'number',
        ],

        'power' => [
            'source' => 'Мощность лампы (Max), Вт',
            'type'   => 'number',
            'unit'   => 'Вт',
        ],

        'led_power' => [
            'source' => 'Суммарная мощность LED, Вт',
            'type'   => 'number',
            'unit'   => 'Вт',
        ],

        'light_flux' => [
            'source' => 'Световой поток, Лм (для лент Лм/м)',
            'type'   => 'number',
            'unit'   => 'лм',
        ],

        'color_temperature' => [
            'source' => 'Цветовая температура, К',
            'type'   => 'number',
            'unit'   => 'К',
        ],

        'cri' => [
            'source' => 'Индекс цветопередачи CRI, Ra',
            'type'   => 'number',
        ],

        'dimmable' => [
            'source' => 'Диммируемость',
            'type'   => 'boolean',
        ],

        'smart_home' => [
            'source' => 'Умный дом',
            'type'   => 'string',
        ],

        'led' => [
            'source' => 'Встроенные светодиоды',
            'type'   => 'boolean',
        ],

        'mounting_type' => [
            'source' => 'Тип монтажа',
            'type'   => 'string',
        ],

        'mounting_method' => [
            'source' => 'Тип крепления',
            'type'   => 'string',
        ],

        'warranty' => [
            'source' => 'Гарантийный срок (в годах)',
            'type'   => 'number',
            'unit'   => 'лет',
        ],

        'width' => [
            'source' => 'Ширина (W), мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'height' => [
            'source' => 'Высота (H), мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'length' => [
            'source' => 'Длина (Глубина) (L), мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'diameter' => [
            'source' => 'Диаметр (D), мм',
            'type'   => 'number',
            'unit'   => 'мм',
        ],
    ],

    'meta' => [

        'tn_ved' => [
            'source' => 'Код ТНВЭД',
            'type'   => 'string',
        ],

        'weight' => [
            'source' => 'Масса, кг',
            'type'   => 'number',
        ],

        'box_weight' => [
            'source' => 'Вес брутто',
            'type'   => 'number',
        ],

        'packaging' => [
            'source' => 'Упаковки Д/Ш/Г в см',
            'type'   => 'string',
        ],

        'quantity_in_package' => [
            'source' => 'Количество в упаковке',
            'type'   => 'number',
        ],

        'service_life' => [
            'source' => 'Срок службы, ч',
            'type'   => 'number',
        ],

        'marketplace_forbidden' => [
            'source' => 'Запрещен для продажи на маркетплейсах',
            'type'   => 'boolean',
        ],
    ],
];
