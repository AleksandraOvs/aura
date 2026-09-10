<?php

return [

    /*
     * ---------------------------------------------------------
     * ПОСТАВЩИК
     * ---------------------------------------------------------
     */

    'supplier' => 'denkirs',

    'name' => 'Denkirs',

    /*
     * ---------------------------------------------------------
     * ОСНОВНЫЕ ПОЛЯ
     * ---------------------------------------------------------
     */

    'fields' => [

        /*
         * Уникальный ID товара у поставщика.
         */

        'external_id' => 'Внутренний идентификатор',

        /*
         * Артикул.
         */

        'sku' => 'Артикул',

        /*
         * Штрихкод.
         */

        'barcode' => 'Штрихкод',

        /*
         * Название.
         */

        'name' => 'Название товара',

        /*
         * Бренд.
         */

        'brand' => 'Бренд',

        /*
         * Категория.
         */

        'categories' => [
            'source'    => 'Категория',
            'separator' => '/',
        ],

        /*
         * URL товара у поставщика.
         */

        'source_url' => 'Ссылка',

        /*
         * У Denkirs сейчас нет цены.
         *
         * Поэтому price здесь НЕ указываем.
         */

        /*
         * У Denkirs сейчас нет остатка.
         *
         * Поэтому stock здесь НЕ указываем.
         */

        /*
         * Нет description.
         *
         * Можно позже добавить:
         *
         * 'description' => 'Описание',
         */

        /*
         * Нет short_description.
         */

    ],

    /*
     * ---------------------------------------------------------
     * MEDIA
     * ---------------------------------------------------------
     */

    'media' => [

        /*
         * Изображения.
         *
         * В CSV:
         *
         * Картинка
         * Картинка_2
         * Картинка_3
         * ...
         * Картинка_16
         */

        'images' => [

            'pattern' => '/^Картинка(?:_\d+)?$/u',

            'separator' => ';',
        ],

        /*
         * Видео.
         */

        'videos' => [

            'fields' => [
                'Видео',
            ],

        ],

        /*
         * Документы.
         */

        'documents' => [

            'instruction' => 'Инструкция',

            '3d_model' => '3D модель',

        ],
    ],

    /*
     * ---------------------------------------------------------
     * АТРИБУТЫ
     * ---------------------------------------------------------
     *
     * Левая часть — наше внутреннее имя.
     *
     * Правая часть — название колонки Denkirs.
     *
     * Позже именно здесь можно будет сопоставить
     * эти поля с WooCommerce pa_атрибутами.
     */

    'attributes' => [

        'style' => [
            'source' => 'стиль',
            'type'   => 'string',
        ],

        'shape' => [
            'source' => 'форма',
            'type'   => 'string',
        ],

        'armature_color' => [
            'source' => 'цвет арматуры',
            'type'   => 'string',
        ],

        'armature_material' => [
            'source' => 'материал арматуры',
            'type'   => 'string',
        ],

        'shade_color' => [
            'source' => 'цвет плафона',
            'type'   => 'string',
        ],

        'shade_material' => [
            'source' => 'материал плафона',
            'type'   => 'string',
        ],

        /*
         * Размеры.
         */

        'width' => [
            'source' => 'ширина/диаметр',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'length' => [
            'source' => 'длина',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        'height' => [
            'source' => 'высота',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        /*
         * Свет.
         */

        'lamp_type' => [
            'source' => 'тип лампы',
            'type'   => 'string',
        ],

        'power' => [
            'source' => 'мощность общая',
            'type'   => 'number',
            'unit'   => 'Вт',
        ],

        'voltage' => [
            'source' => 'напряжение',
            'type'   => 'number',
            'unit'   => 'В',
        ],

        'base' => [
            'source' => 'тип цоколя',
            'type'   => 'string',
        ],

        'ip' => [
            'source' => 'степень защиты ip',
            'type'   => 'number',
        ],

        'light_color' => [
            'source' => 'цвет свечения',
            'type'   => 'string',
        ],

        /*
         * Монтаж.
         */

        'installation_place' => [
            'source' => 'место установки',
            'type'   => 'string',
        ],

        /*
         * Страна.
         */

        'country' => [
            'source' => 'страна происхождения',
            'type'   => 'string',
        ],

        /*
         * Коллекция.
         */

        'collection' => [
            'source' => 'коллекция',
            'type'   => 'string',
        ],

        /*
         * Помещение.
         */

        'room' => [
            'source' => 'Назначение помещения',
            'type'   => 'string',
        ],

        /*
         * Форма плафона.
         */

        'shade_shape' => [
            'source' => 'форма плафона',
            'type'   => 'string',
        ],

        /*
         * Лампы в комплекте.
         */

        'lamps_included' => [
            'source' => 'лампы в комплекте',
            'type'   => 'boolean',
        ],

        /*
         * Площадь освещения.
         */

        'lighting_area' => [
            'source' => 'площадь освещения',
            'type'   => 'number',
            'unit'   => 'м²',
        ],

        /*
         * Световой поток.
         */

        'luminous_flux' => [
            'source' => 'световой поток',
            'type'   => 'number',
            'unit'   => 'лм',
        ],

        /*
         * Дополнительные характеристики,
         * которые уже встречаются в CSV.
         */

        'dimmable' => [
            'source' => 'диммируемость',
            'type'   => 'boolean',
        ],

        'decor_material' => [
            'source' => 'материал декора',
            'type'   => 'string',
        ],

        'beam_angle' => [
            'source' => 'угол рассеивания света',
            'type'   => 'number',
            'unit'   => '°',
        ],

        'color_rendering' => [
            'source' => 'цветопередача',
            'type'   => 'string',
        ],

        'lamp_power' => [
            'source' => 'мощность лампы',
            'type'   => 'number',
            'unit'   => 'Вт',
        ],

        'lamp_count' => [
            'source' => 'количество ламп',
            'type'   => 'number',
        ],

        'phase_count' => [
            'source' => 'количество фаз',
            'type'   => 'number',
        ],

        'mounting_type' => [
            'source' => 'тип монтажа',
            'type'   => 'string',
        ],

        'mounting_method' => [
            'source' => 'способ крепления',
            'type'   => 'string',
        ],

        'switch' => [
            'source' => 'выключатель',
            'type'   => 'string',
        ],

        'control_type' => [
            'source' => 'вид управления',
            'type'   => 'string',
        ],

        'remote_control' => [
            'source' => 'пульт управления',
            'type'   => 'boolean',
        ],

        'motion_sensor' => [
            'source' => 'датчик движения',
            'type'   => 'boolean',
        ],

        'features' => [
            'source' => 'особенности',
            'type'   => 'string',
        ],

        'backlight' => [
            'source' => 'подсветка',
            'type'   => 'boolean',
        ],

        'magnetic' => [
            'source' => 'магнитный',
            'type'   => 'boolean',
        ],

        'height_adjustment' => [
            'source' => 'регулировка по высоте',
            'type'   => 'boolean',
        ],

        'voice_control' => [
            'source' => 'управляется голосовыми помощниками',
            'type'   => 'boolean',
        ],

        'app_control' => [
            'source' => 'управляется приложениями',
            'type'   => 'boolean',
        ],

        'smart_home_control' => [
            'source' => 'управляется экосистемами умного дома',
            'type'   => 'boolean',
        ],

        /*
         * Минимальная высота.
         */

        'height_min' => [
            'source' => 'высота минимальная',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        /*
         * Подвес.
         */

        'suspension_type' => [
            'source' => 'тип подвеса',
            'type'   => 'string',
        ],

        'chain_length' => [
            'source' => 'длина цепи',
            'type'   => 'number',
            'unit'   => 'мм',
        ],

        /*
         * Протоколы связи.
         */

        'communication_protocols' => [
            'source' => 'протоколы связи',
            'type'   => 'string',
        ],

        /*
         * Цвет декора.
         */

        'decor_color' => [
            'source' => 'цвет декора',
            'type'   => 'string',
        ],

        /*
         * Температура эксплуатации.
         */

        'operating_temperature' => [
            'source' => 'диапазон рабочих температур',
            'type'   => 'string',
        ],

        /*
         * Выходная мощность.
         */

        'output_power' => [
            'source' => 'мощность выходная',
            'type'   => 'number',
            'unit'   => 'Вт',
        ],

        /*
         * Датчик освещённости.
         */

        'light_sensor' => [
            'source' => 'датчик освещенности',
            'type'   => 'boolean',
        ],

        /*
         * Filament.
         */

        'filament' => [
            'source' => 'филамент',
            'type'   => 'boolean',
        ],
    ],

    /*
     * ---------------------------------------------------------
     * META
     * ---------------------------------------------------------
     *
     * Это характеристики, которые пока не считаем
     * основными WooCommerce-атрибутами.
     */

    'meta' => [

        'box_weight' => [
            'source' => 'вес коробки',
            'type'   => 'number',
        ],

        'shipping_places' => [
            'source' => 'количество грузовых мест',
            'type'   => 'number',
        ],

        'box_volume' => [
            'source' => 'объем коробки',
            'type'   => 'number',
        ],

        'box_size' => [
            'source' => 'размер коробки (ДхШхВ)',
            'type'   => 'string',
        ],

        'full_supplier_name' => [
            'source' => 'Полное наименование Denkirs',
            'type'   => 'string',
        ],

        /*
         * Запрет для маркетплейсов.
         */

        'marketplace_forbidden' => [
            'source' => 'Запрет для МП',
            'type'   => 'boolean',
        ],

        /*
         * 3D модель и инструкция находятся в documents,
         * поэтому сюда их дублировать не нужно.
         */

    ],

];
