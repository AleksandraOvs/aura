<?php

namespace Supplier_Importer\Suppliers\CrystalLux;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Repository;
use Supplier_Importer\Import\Mapping_Resolver;
use Supplier_Importer\Import\Feed_Attribute_Mapper;

class Crystal_Lux_Mapper
{

    private $mapping_resolver;
    private $attribute_mapper;

    public function __construct()
    {
        $repository = new Mapping_Repository();

        $this->mapping_resolver = new Mapping_Resolver(
            $repository
        );

        $this->attribute_mapper = new Feed_Attribute_Mapper(
            $this->mapping_resolver
        );
    }

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

        $mapping = $this->mapping_resolver->resolve_category(
            'crystal_lux',
            $category
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
     * Получить атрибуты через общий механизм сопоставлений.
     */
    private function get_attributes($row)
    {
        $params = [];

        foreach ($this->get_attribute_fields() as $source) {
            $value = $this->value($row, $source);

            if ($value === '') {
                continue;
            }

            $params[] = [
                'name' => $source,
                'value' => $value,
            ];
        }

        return $this->attribute_mapper->map(
            $params,
            'crystal_lux'
        );
    }

    /**
     * Исходные поля характеристик Crystal Lux.
     */
    private function get_attribute_fields()
    {
        return [
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

        if (!is_numeric($value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Некорректное числовое значение Crystal Lux: %s',
                    $value
                )
            );
        }

        $number = (float) $value;

        if (!is_finite($number)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Числовое значение Crystal Lux выходит за допустимый диапазон: %s',
                    $value
                )
            );
        }

        return $number;
    }
}
