<?php

namespace Supplier_Importer\Suppliers\Maytoni;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Repository;

class Maytoni_Mapper
{
    public function map($row)
    {
        return Product_Data::from_array([
            'supplier' => 'maytoni',

            'supplier_id' => $this->get_value(
                $row,
                'id'
            ),

            'sku' => $this->get_value(
                $row,
                'vendorCode'
            ),

            'name' => $this->get_value(
                $row,
                'name'
            ),

            'type' => $this->get_value(
                $row,
                'ВидНоменклатуры'
            ),

            'brand' => $this->get_value(
                $row,
                'brand_name'
            ),

            'barcode' => $this->get_value(
                $row,
                'barcode'
            ),

            'category' => $this->get_category(
                $row
            ),

            'url' => $this->get_value(
                $row,
                'url'
            ),

            'description' => $this->get_value(
                $row,
                'description'
            ),

            'price' => $this->get_price(
                $row
            ),

            'stock' => $this->get_stock(
                $row
            ),

            'images' => $this->get_images(
                $row
            ),

            'attributes' => $this->get_attributes(
                $row
            ),

            'meta' => $this->get_meta(
                $row
            ),
        ]);
    }

    private function get_value(
        $row,
        $key
    ) {
        if (!isset($row[$key])) {
            return '';
        }

        return trim(
            (string) $row[$key]
        );
    }

    private function get_category($row)
    {
        $category = $this->get_value(
            $row,
            'Категория'
        );

        if ($category === '') {
            return '';
        }

        $repository = new Mapping_Repository();

        $mapping = $repository->find(
            'maytoni',
            'category',
            $category,
            ''
        );

        if (
            is_array($mapping)
            && !empty($mapping['target_id'])
        ) {
            $mapped_category = $this->get_category_path(
                (int) $mapping['target_id']
            );

            if ($mapped_category !== '') {
                return $mapped_category;
            }
        }

        return $category;
    }

    private function get_category_path($term_id)
    {
        $term = get_term(
            $term_id,
            'product_cat'
        );

        if (!$term || is_wp_error($term)) {
            return '';
        }

        $parts = [];

        while ($term) {
            $parts[] = $term->name;

            if ((int) $term->parent === 0) {
                break;
            }

            $term = get_term(
                (int) $term->parent,
                'product_cat'
            );

            if (!$term || is_wp_error($term)) {
                break;
            }
        }

        if (empty($parts)) {
            return '';
        }

        return implode(
            ' / ',
            array_reverse($parts)
        );
    }

    private function get_price($row)
    {
        $price = $this->get_value(
            $row,
            'price'
        );

        if ($price === '') {
            return null;
        }

        $price = str_replace(
            ',',
            '.',
            $price
        );

        return is_numeric($price)
            ? (float) $price
            : null;
    }

    private function get_stock($row)
    {
        $stock = $this->get_value(
            $row,
            'Stock'
        );

        if ($stock === '') {
            return null;
        }

        return is_numeric($stock)
            ? (int) $stock
            : null;
    }

    private function get_images($row)
    {
        $images = [];

        foreach ($row as $key => $value) {
            if (
                strpos(
                    (string) $key,
                    'Фото'
                ) !== 0
            ) {
                continue;
            }

            $value = trim(
                (string) $value
            );

            if ($value === '') {
                continue;
            }

            if (
                !filter_var(
                    $value,
                    FILTER_VALIDATE_URL
                )
            ) {
                continue;
            }

            $images[] = $value;
        }

        return array_values(
            array_unique($images)
        );
    }

    private function get_attributes($row)
    {
        $mapping = $this->get_attribute_mapping();

        $attributes = [];

        foreach ($mapping as $source => $taxonomy) {
            if (!isset($row[$source])) {
                continue;
            }

            $value = trim(
                (string) $row[$source]
            );

            if ($value === '') {
                continue;
            }

            $value = $this->get_mapped_attribute_value(
                $source,
                $value,
                'pa_' . $taxonomy
            );

            $attributes[$taxonomy] = $value;
        }

        return $attributes;
    }

    private function get_mapped_attribute_value(
        $source,
        $value,
        $taxonomy
    ) {
        $repository = new Mapping_Repository();

        $mapping = $repository->find(
            'maytoni',
            'attribute_value',
            $value,
            $source
        );

        if (
            !is_array($mapping)
            || empty($mapping['target_id'])
        ) {
            return $value;
        }

        $term = get_term(
            (int) $mapping['target_id'],
            $taxonomy
        );

        if (!$term || is_wp_error($term)) {
            return $value;
        }

        return $term->name;
    }

    private function get_meta($row)
    {
        return [
            'full_name' => $this->get_value(
                $row,
                'Полное наименование'
            ),

            'instruction' => $this->get_value(
                $row,
                'Инструкция'
            ),

            'model_3d' => $this->get_value(
                $row,
                '3D-модель'
            ),

            'video' => $this->get_first_video(
                $row
            ),
        ];
    }

    private function get_first_video($row)
    {
        foreach (
            [
                'Видео1',
                'Видео2',
            ] as $key
        ) {
            $value = $this->get_value(
                $row,
                $key
            );

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function get_attribute_mapping()
    {
        return [
            'brand_name' => 'proizoditel',
            'style_name' => 'style',
            'collection_name' => 'collection',
            'series_name' => 'collection',

            'Гарантия' => 'warranty',
            'Напряжение' => 'napryazhenie',
            'КлассЭлектрозащиты' => 'base',
            'ingress_protection_rating' => 'ip',

            'ЦветАрматуры' => 'armature-color',
            'МатериалАрматуры' => 'armature-material',

            'ЦветХрусталя' => 'shade-color',

            'Диаметр' => 'shirina-diametr',
            'Длина' => 'dlina-mm',
            'Ширина' => 'shirina-diametr',
            'Высота' => 'vysota-mm',

            'ФормаМонтажногоОтверстияВстраиваемогоСветильника'
            => 'recessed-mounting-type',

            'ТипМонтажаВстраиваемогоСветильника'
            => 'recessed-mounting-type',

            'ДиаметрМонтажногоОтверстияВстраиваемогоСветильника'
            => 'diametr-mont-otv',

            'ГлубинаМонтажногоОтверстияВстраиваемогоСветильника'
            => 'depth',

            'ШиринаМонтажногоОтверстияВстраиваемогоСветильника'
            => 'shirina-mont-otv',

            'ДлинаМонтажногоОтверстияВстраиваемогоСветильника'
            => 'dlina-mont-otv',

            'ДлинаЦепи' => 'chain-length',

            'ФормаЧашиКрепления' => 'base',

            'ШиринаЧашиКрепления' => 'shirina-mont-otv',
            'ДлинаЧашиКрепления' => 'dlina-mont-otv',
            'ДиаметрЧашиКрепления' => 'diametr-mont-otv',

            'ФормаАбажура' => 'shade-shape',
            'ТипКрепленияАбажура' => 'shade-mounting-type',
            'МатериалАбажура' => 'shade-material',
            'ЦветАбажура' => 'shade-color',

            'lamp_is_led' => 'led',
            'ЛампыВКомплекте' => 'lamp-count',
            'Цоколь' => 'base',
            'КоличествоЛамп' => 'lamp-count',
            'Мощность' => 'moshhnost-lampochki-vt',

            'СветовойПоток' => 'light-flux',
            'УголРассеивания' => 'tip-krepleniya',
            'ИндексЦветопередачи' => 'cri',
            'ЦветоваяТемпература' => 'color-temperature',

            'Страна происхождения' => 'strana-proishozhdenia',
            'Типоразмер' => 'tip-lampochki',
            'ЦветовойКод' => 'shade-color',
        ];
    }
}
