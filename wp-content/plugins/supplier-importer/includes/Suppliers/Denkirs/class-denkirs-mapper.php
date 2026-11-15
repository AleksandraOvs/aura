<?php

namespace Supplier_Importer\Suppliers\Denkirs;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Repository;
use Supplier_Importer\Import\Mapping_Resolver;
use Supplier_Importer\Import\Feed_Attribute_Mapper;

class Denkirs_Mapper
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
            throw new InvalidArgumentException(
                'Denkirs Mapper ожидает массив данных товара.'
            );
        }

        return Product_Data::from_array([
            'supplier' => 'denkirs',

            'supplier_id' => $this->get_value(
                $row,
                'Внутренний идентификатор'
            ),

            'sku' => $this->get_value(
                $row,
                'Артикул'
            ),

            'name' => $this->get_value(
                $row,
                'Название товара'
            ),

            'type' => $this->get_value(
                $row,
                'Тип товара'
            ),

            'brand' => $this->get_value(
                $row,
                'Бренд'
            ),

            'barcode' => $this->get_value(
                $row,
                'Штрихкод'
            ),

            'category' => $this->get_category(
                $row
            ),

            'url' => $this->get_value(
                $row,
                'Ссылка'
            ),

            'description' => '',

            'price' => null,

            'stock' => null,

            'images' => $this->get_images($row),

            'attributes' => $this->get_attributes($row),

            'meta' => $this->get_meta($row),
        ]);
    }

    /**
     * Получает значение поля.
     */
    private function get_value($row, $key)
    {
        if (!isset($row[$key])) {
            return '';
        }

        return trim((string) $row[$key]);
    }

    /**
     * Получает категорию с учётом индивидуальных и глобальных mappings.
     */
    private function get_category($row)
    {
        $category = $this->get_value(
            $row,
            'Категория'
        );

        if ($category === '') {
            return '';
        }

        $mapping = $this->mapping_resolver->resolve_category(
            'denkirs',
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
     * Получает полный путь категории WooCommerce по её ID.
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

    /**
     * Собирает изображения.
     */
    private function get_images($row)
    {
        $images = [];

        foreach ($row as $key => $value) {
            if (
                strpos(
                    (string) $key,
                    'Картинка'
                ) !== 0
            ) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                continue;
            }

            $images[] = $value;
        }

        return array_values(
            array_unique($images)
        );
    }

    /**
     * Собирает характеристики товара через общую систему сопоставлений.
     */
    private function get_attributes($row)
    {
        $params = [];

        foreach ($this->get_attribute_fields() as $source) {
            if (!isset($row[$source])) {
                continue;
            }

            $value = trim((string) $row[$source]);

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
            'denkirs'
        );
    }

    /**
     * Список характеристик из CSV Denkirs.
     */
    private function get_attribute_fields()
    {
        return [
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
            'площадь освещения',
            'световой поток',
        ];
    }



    /**
     * Дополнительные данные поставщика.
     */
    private function get_meta($row)
    {
        return [
            'full_name' => $this->get_value(
                $row,
                'Полное наименование Denkirs'
            ),

            'instruction' => $this->get_value(
                $row,
                'Инструкция'
            ),

            'model_3d' => $this->get_value(
                $row,
                '3D модель'
            ),

            'video' => $this->get_value(
                $row,
                'Видео'
            ),
        ];
    }
}
