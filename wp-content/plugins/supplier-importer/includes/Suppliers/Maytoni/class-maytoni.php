<?php

namespace Supplier_Importer\Suppliers\Maytoni;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Supplier;

class Maytoni implements Supplier
{
    /**
     * ID поставщика.
     *
     * @return string
     */
    public function get_id()
    {
        return 'maytoni';
    }

    /**
     * Название поставщика.
     *
     * @return string
     */
    public function get_name()
    {
        return 'Maytoni';
    }

    /**
     * Проверяет, подходит ли CSV Maytoni.
     *
     * Определение выполняется по набору характерных
     * фрагментов заголовков.
     *
     * @param array $headers
     *
     * @return bool
     */
    public function supports($headers)
    {
        if (!is_array($headers) || empty($headers)) {
            return false;
        }

        $fragments = [
            'brand_name',
            'style_name',
            'collection_name',
            'series_name',
            'КодТНВЭД',
            'СтатусRu_Ru',
            'Фото1',
            'Фото2',
            'Фото3',
            'IES',
            '3D-модель',
        ];

        $matched = 0;

        foreach ($fragments as $fragment) {
            foreach ($headers as $header) {
                $header = trim((string) $header);

                if (
                    $header !== ''
                    && mb_stripos(
                        $header,
                        $fragment
                    ) !== false
                ) {
                    $matched++;

                    break;
                }
            }
        }

        return $matched >= 3;
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
                'brand_name',
                'style_name',
                'collection_name',
                'series_name',
                'Гарантия',
                'Типы помещении',
                'Напряжение',
                'КодТНВЭД',
                'КлассЭлектрозащиты',
                'ingress_protection_rating',
                'ЦветАрматуры',
                'МатериалАрматуры',
                'ЦветХрусталя',
                'Диаметр',
                'Длина',
                'Ширина',
                'Высота',
                'МинимальнаяВысота',
                'ФормаМонтажногоОтверстияВстраиваемогоСветильника',
                'ТипМонтажаВстраиваемогоСветильника',
                'ДиаметрМонтажногоОтверстияВстраиваемогоСветильника',
                'ГлубинаМонтажногоОтверстияВстраиваемогоСветильника',
                'ШиринаМонтажногоОтверстияВстраиваемогоСветильника',
                'ДлинаМонтажногоОтверстияВстраиваемогоСветильника',
                'Цепь',
                'ДлинаЦепи',
                'Чаша крепления',
                'ФормаЧашиКрепления',
                'ШиринаЧашиКрепления',
                'ДлинаЧашиКрепления',
                'ДиаметрЧашиКрепления',
                'ВысотаЧашиКрепления',
                'ФормаАбажура',
                'ТипКрепленияАбажура',
                'МатериалАбажура',
                'ЦветАбажура',
                'ВысотаАбажура',
                'ШиринаАбажура',
                'ДлинаАбажура',
                'lamp_is_led',
                'ЛампыВКомплекте',
                'Цоколь',
                'КоличествоЛамп',
                'Мощность',
                'СветовойПоток',
                'УголРассеивания',
                'ИндексЦветопередачи',
                'ЦветоваяТемпература',
                'Страна происхождения',
                'Типоразмер',
                'ЦветовойКод',
                'Стиль',
                'Коллекция',
                'Серия',
            ],
        ];
    }

    /**
     * Создаёт mapper поставщика.
     *
     * @return Maytoni_Mapper
     */
    public function get_mapper()
    {
        return new Maytoni_Mapper();
    }
}
