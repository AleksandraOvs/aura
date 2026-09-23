<?php

namespace Supplier_Importer\Suppliers\Denkirs;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Supplier;

class Denkirs implements Supplier
{
    /**
     * ID поставщика.
     *
     * @return string
     */
    public function get_id()
    {
        return 'denkirs';
    }

    /**
     * Название поставщика.
     *
     * @return string
     */
    public function get_name()
    {
        return 'Denkirs';
    }

    /**
     * Проверяет CSV на соответствие Denkirs.
     *
     * @param array $headers
     *
     * @return bool
     */
    public function supports($headers)
    {
        $required_headers = [
            'Код товара',
            'Название товара',
            'Бренд',
            'Артикул',
            'Категория',
        ];

        foreach ($required_headers as $required) {
            if (!in_array($required, $headers, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Конфигурация анализа CSV.
     *
     * @return array
     */
    public function get_csv_analysis_config()
    {
        return [
            'category_fields' => [
                'Категория',
            ],

            'attribute_fields' => [
                'стиль',
                'форма',
                'цвет арматуры',
                'материал арматуры',
                'цвет плафона',
                'материал плафона',
                'ширина/диаметр',
                'длина',
                'высота',
                'тип лампы',
                'мощность общая',
                'напряжение',
                'тип цоколя',
                'степень защиты ip',
                'цвет свечения',
                'место установки',
                'страна происхождения',
                'коллекция',
                'Назначение помещения',
                'форма плафона',
                'лампы в комплекте',
                'площадь освещения',
                'световой поток',
            ],
        ];
    }

    /**
     * Mapper поставщика.
     *
     * @return Denkirs_Mapper
     */
    public function get_mapper()
    {
        return new Denkirs_Mapper();
    }
}
