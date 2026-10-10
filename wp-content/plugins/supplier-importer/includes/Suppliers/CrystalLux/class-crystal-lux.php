<?php

namespace Supplier_Importer\Suppliers\CrystalLux;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Supplier;

class Crystal_Lux implements Supplier
{
    public function get_id()
    {
        return 'crystal_lux';
    }

    public function get_name()
    {
        return 'Crystal Lux';
    }

    public function supports($headers)
    {
        if (!is_array($headers) || empty($headers)) {
            return false;
        }

        $required_fragments = [
            'ARTICUL',
            'BARCODE',
            'MORE_PHOTO',
            'DISCONTINUED',
            'TN_VED',
        ];

        $matched = 0;

        foreach ($required_fragments as $fragment) {
            foreach ($headers as $header) {
                if (
                    $header !== '' &&
                    mb_stripos((string) $header, $fragment) !== false
                ) {
                    $matched++;
                    break;
                }
            }
        }

        return $matched >= 3;
    }

    public function get_csv_analysis_config()
    {
        return [
            'category_fields' => [
                'Путь из названий разделов',
            ],

            'attribute_fields' => [
                'Брэнд [brand]',
                'Стиль [STYLE]',
                'Коллекция [COLLECTION]',
                'Тип [TYPE]',
                'Новинка [NEW]',
                'Высота изделия, мм []',
                'Высота цепи/троса, мм []',
                'Высота с цепью/тросом, мм []',
                'Диаметр, мм []',
                'Ширина, мм []',
                'Отступ от стены, мм []',
                'Цоколь []',
                'Количество лампочек: []',
                'Мощность лампочек, Вт []',
                'Длина, мм []',
                'Световой поток []',
                'Цветовая температура []',
                'В комплекте []',
                'IP []',
                'Вес []',
                'Объём []',
                'Материал арматуры []',
                'Цвет арматуры []',
                'Покрытие арматуры []',
                'Материал абажура/плафона []',
                'Цвет абажура/плафона []',
                'Материал подвески []',
                'Цвет подвески []',
                'Декоративный элемент []',
                'Пульт []',
                'Страна производитель []',
                'Напряжение [voltage]',
                'Особенности модели [osobennosti_modeli]',
            ],
        ];
    }

    public function get_mapper()
    {
        return new Crystal_Lux_Mapper();
    }
}
