<?php

namespace Supplier_Importer\Suppliers\Denkirs;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Repository;

class Denkirs_Mapper
{
    private $mapping_repository;

    public function __construct()
    {
        $this->mapping_repository = new Mapping_Repository();
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
     * Получает категорию с учётом mapping.
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

        $mapping = $this->mapping_repository->find(
            'denkirs',
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
     * Собирает характеристики товара.
     */
    private function get_attributes($row)
    {
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

            $attributes[$taxonomy] = $value;
        }

        return $attributes;
    }

    /**
     * Преобразует значение характеристики через mapping.
     */
    private function get_mapped_attribute_value(
        $source,
        $value,
        $taxonomy
    ) {
        $mapping = $this->mapping_repository->find(
            'denkirs',
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

    /**
     * Соответствие полей поставщика таксономиям сайта.
     */
    private function get_attribute_mapping()
    {
        return [
            'стиль' => 'style',
            'форма' => 'forma',
            'цвет арматуры' => 'armature-color',
            'материал арматуры' => 'armature-material',
            'цвет плафона' => 'shade-color',
            'материал плафона' => 'shade-material',
            'ширина/диаметр' => 'shirina-diametr',
            'длина' => 'dlina-mm',
            'высота' => 'vysota-mm',
            'тип лампы' => 'tip-lampochki',
            'мощность общая' => 'obshhaya-moshhnost',
            'напряжение' => 'napryazhenie',
            'тип цоколя' => 'base',
            'степень защиты ip' => 'ip',
            'цвет свечения' => 'color-temperature',
            'место установки' => 'tip-krepleniya',
            'страна происхождения' => 'strana-proishozhdenia',
            'коллекция' => 'collection',
            'Назначение помещения' => 'room-purpose',
            'форма плафона' => 'shade-shape',

            // «Лампы в комплекте» не является характеристикой LED.
            // Добавить сюда после создания отдельной таксономии.

            'площадь освещения' => 'light-area',
            'световой поток' => 'light-flux',
        ];
    }
}
