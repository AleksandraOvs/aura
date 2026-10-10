<?php

namespace Supplier_Importer\Suppliers\CrystalLux;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Repository;

class Crystal_Lux_Mapper
{
    public function map($row)
    {
        if (!is_array($row)) {
            throw new \InvalidArgumentException(
                'Crystal_Lux_Mapper ожидает массив данных CSV.'
            );
        }

        $sku = $this->value($row, 'Артикул [ARTICUL]');

        if ($sku === '') {
            throw new \InvalidArgumentException(
                'В строке Crystal Lux отсутствует артикул.'
            );
        }


        $discontinued_value = mb_strtolower(
            trim(
                $this->value(
                    $row,
                    'Снято с производства [DISCONTINUED]'
                )
            ),
            'UTF-8'
        );

        $discontinued = in_array(
            $discontinued_value,
            [
                'снято с производства',
                'да',
                'yes',
                'true',
                '1',
            ],
            true
        );

        $images = $this->get_images($row);

        $attributes = $this->get_attributes($row);

        $meta = [
            'discontinued' => $discontinued,
            'old_price' => $this->number(
                $this->value($row, 'Старая цена []')
            ),
            'instruction' => $this->value(
                $row,
                'Инструкция [INSTRUKTCIIA]'
            ),
            'video' => $this->value(
                $row,
                'Видео RuTube [ATT_RUTUBE]'
            ),
            'supplier_url' => $this->value(
                $row,
                'Детальная картинка (путь)'
            ),
        ];

        return Product_Data::from_array([
            'supplier' => 'crystal_lux',
            'supplier_id' => $this->value($row, 'Код [CODE]'),
            'sku' => $sku,
            'name' => $this->value($row, 'Наименование элемента'),
            'type' => $this->value($row, 'Тип [TYPE]'),
            'brand' => $this->value($row, 'Брэнд [brand]'),
            'barcode' => $this->value($row, 'Штрихкод [BARCODE]'),
            'category' => $this->get_category($row),
            'url' => '',
            'description' => $this->value(
                $row,
                'Особенности модели [osobennosti_modeli]'
            ),
            'price' => $this->number(
                $this->value($row, 'Цена "Цена"')
            ),
            'stock' => $this->number(
                $this->value($row, 'Доступное количество')
            ),
            'images' => $images,
            'attributes' => $attributes,
            'meta' => $meta,
        ]);
    }


    /**
     * Получить категорию с учётом сохранённого сопоставления.
     */
    private function get_category($row)
    {
        $category = $this->value(
            $row,
            'Путь из названий разделов'
        );

        if ($category === '') {
            return '';
        }

        $repository = new Mapping_Repository();

        $mapping = $repository->find(
            'crystal_lux',
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

        // Если сопоставления нет, сохраняем исходный путь.
        return $category;
    }

    /**
     * Получить полный путь категории WooCommerce.
     */
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


    private function get_images($row)
    {
        $images = [];

        $detail_image = $this->value(
            $row,
            'Детальная картинка (путь)'
        );

        $preview_image = $this->value(
            $row,
            'Картинка для анонса (путь)'
        );

        if ($detail_image !== '') {
            $images[] = $detail_image;
        }

        if ($preview_image !== '') {
            $images[] = $preview_image;
        }

        $more_photos = $this->value(
            $row,
            'Дополнительные фото товара [MORE_PHOTO]'
        );

        if ($more_photos !== '') {
            foreach (explode(';', $more_photos) as $image) {
                $image = trim($image);

                if ($image !== '') {
                    $images[] = $image;
                }
            }
        }

        $images = array_filter(
            $images,
            static function ($url) {
                return (bool) filter_var(
                    $url,
                    FILTER_VALIDATE_URL
                );
            }
        );

        return array_values(array_unique($images));
    }

    /**
     * Получить атрибуты с учётом сопоставлений.
     */
    private function get_attributes($row)
    {
        $mapping = $this->get_attribute_mapping();
        $attributes = [];

        foreach ($mapping as $source => $taxonomy) {
            if (!isset($row[$source])) {
                continue;
            }

            $value = $this->value($row, $source);

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

    /**
     * Получить значение атрибута с учётом mapping.
     */
    private function get_mapped_attribute_value(
        $source,
        $value,
        $taxonomy
    ) {
        $repository = new Mapping_Repository();

        $mapping = $repository->find(
            'crystal_lux',
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

    /**
     * Соответствие полей Crystal Lux таксономиям WooCommerce.
     *
     * Используются таксономии, уже встречающиеся
     * в Maytoni_Mapper. Перед импортом нужно проверить,
     * что они зарегистрированы на сайте.
     */
    private function get_attribute_mapping()
    {
        return [
            'Брэнд [brand]' => 'proizoditel',
            'Стиль [STYLE]' => 'style',
            'Коллекция [COLLECTION]' => 'collection',

            'Цоколь []' => 'base',
            'Количество лампочек: []' => 'lamp-count',
            'Мощность лампочек, Вт []' => 'moshhnost-lampochki-vt',
            'Световой поток []' => 'light-flux',
            'Цветовая температура []' => 'color-temperature',
            'IP []' => 'ip',

            'Высота изделия, мм []' => 'vysota-mm',
            'Длина, мм []' => 'dlina-mm',
            'Диаметр, мм []' => 'shirina-diametr',

            'Материал арматуры []' => 'armature-material',
            'Цвет арматуры []' => 'armature-color',
            'Материал абажура/плафона []' => 'shade-material',
            'Цвет абажура/плафона []' => 'shade-color',

            'Страна производитель []' => 'strana-proishozhdenia',
            'Напряжение [voltage]' => 'napryazhenie',
        ];
    }

    private function value($row, $key)
    {
        if (!array_key_exists($key, $row)) {
            return '';
        }

        if (is_array($row[$key]) || is_object($row[$key])) {
            return '';
        }

        return trim((string) $row[$key]);
    }

    private function number($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(
            ["\xC2\xA0", ' '],
            '',
            $value
        );

        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? $value : null;
    }
}
