<?php

namespace Supplier_Importer\Suppliers\Maytoni;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Repository;
use InvalidArgumentException;
use Supplier_Importer\Import\Mapping_Resolver;

class Maytoni_Mapper
{
    private $mapping_repository;

    private $mapping_resolver;

    private $use_global_mappings = false;

    public function __construct($use_global_mappings = false)
    {
        $this->mapping_repository = new Mapping_Repository();
        $this->mapping_resolver = new Mapping_Resolver(
            $this->mapping_repository
        );

        $this->use_global_mappings = (bool) $use_global_mappings;
    }

    public function map($row)
    {
        if (!is_array($row)) {
            throw new InvalidArgumentException(
                'Maytoni Mapper ожидает массив данных товара.'
            );
        }

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

        $value = $row[$key];

        if (!is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
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

        if ($this->use_global_mappings) {
            $mapping = $this->mapping_resolver->resolve_category(
                'maytoni',
                $category
            );
        } else {
            $mapping = $this->mapping_repository->find(
                'maytoni',
                'category',
                $category,
                ''
            );
        }

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

        return $price === ''
            ? null
            : $price;
    }

    private function get_stock($row)
    {
        $stock = $this->get_value(
            $row,
            'Stock'
        );

        return $stock === ''
            ? null
            : $stock;
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
        if ($this->use_global_mappings) {
            return $this->get_yml_attributes($row);
        }
        $mapping = $this->get_attribute_mapping();

        $attributes = [];

        foreach ($mapping as $source => $taxonomy) {
            if (!isset($row[$source])) {
                continue;
            }

            $value = trim((string) $row[$source]);

            if ($value === '') {
                continue;
            }

            $value = $this->get_mapped_attribute_value(
                $source,
                $value,
                'pa_' . $taxonomy
            );

            $attributes[$taxonomy] = [
                'label' => $source,
                'value' => $value,
            ];
        }

        return $attributes;
    }

    /**
     * Собрать атрибуты YML через систему сопоставлений.
     */
    private function get_yml_attributes($row)
    {
        $source_attributes = $row['_supplier_attributes'] ?? [];

        if (!is_array($source_attributes)) {
            return [];
        }

        $attributes = [];

        foreach ($source_attributes as $source => $value) {
            $source = trim((string) $source);
            $value = trim((string) $value);

            if ($source === '' || $value === '') {
                continue;
            }

            // Ищем сопоставление исходного названия атрибута.
            $mapping = $this->mapping_resolver->resolve_attribute(
                'maytoni',
                $source
            );

            $attribute = null;

            if (
                is_array($mapping)
                && !empty($mapping['target_id'])
                && function_exists('wc_get_attribute')
            ) {
                $attribute = wc_get_attribute(
                    (int) $mapping['target_id']
                );
            }

            if (
                $attribute
                && !empty($attribute->slug)
            ) {
                $taxonomy = $attribute->slug;

                $label = !empty($attribute->name)
                    ? $attribute->name
                    : $source;
            } else {
                // Если сопоставления нет, используем исходное название.
                // Product_Attributes сможет создать атрибут автоматически.
                $slug = wc_sanitize_taxonomy_name($source);

                if ($slug === '') {
                    continue;
                }

                $taxonomy = wc_attribute_taxonomy_name($slug);
                $label = $source;
            }

            // Обрабатываем значения по отдельности, чтобы каждое
            // из них могло иметь собственное сопоставление с термином.
            $mapped_values = [];

            $values = preg_split('/[,;]/', $value);

            foreach ($values as $source_value) {
                $source_value = trim($source_value);

                if ($source_value === '') {
                    continue;
                }

                $value_mapping = $this->mapping_resolver
                    ->resolve_attribute_value(
                        'maytoni',
                        $source_value,
                        $source
                    );

                if (
                    is_array($value_mapping)
                    && !empty($value_mapping['target_id'])
                ) {
                    $term = get_term(
                        (int) $value_mapping['target_id'],
                        $taxonomy
                    );

                    if ($term && !is_wp_error($term)) {
                        $source_value = $term->name;
                    }
                }

                if (!in_array($source_value, $mapped_values, true)) {
                    $mapped_values[] = $source_value;
                }
            }

            if (empty($mapped_values)) {
                continue;
            }

            $mapped_value = implode(', ', $mapped_values);

            // Несколько исходных параметров могут быть направлены
            // в один атрибут WooCommerce.
            if (!isset($attributes[$taxonomy])) {
                $attributes[$taxonomy] = [
                    'label' => $label,
                    'value' => $mapped_value,
                ];

                continue;
            }

            $attributes[$taxonomy]['value'] = $this->merge_values(
                $attributes[$taxonomy]['value'],
                $mapped_value
            );
        }

        return $attributes;
    }

    /**
     * Объединить значения без повторов.
     */
    private function merge_values($first, $second)
    {
        $values = [];

        foreach ([$first, $second] as $source) {
            foreach (preg_split('/[,;]/', (string) $source) as $value) {
                $value = trim($value);

                if (
                    $value !== ''
                    && !in_array($value, $values, true)
                ) {
                    $values[] = $value;
                }
            }
        }

        return implode(', ', $values);
    }

    private function get_mapped_attribute_value(
        $source,
        $value,
        $taxonomy
    ) {
        $mapping = $this->mapping_repository->find(
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

    public function get_attribute_mapping()
    {
        return [
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
