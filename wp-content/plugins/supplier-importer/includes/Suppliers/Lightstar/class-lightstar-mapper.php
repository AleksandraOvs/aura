<?php

namespace Supplier_Importer\Suppliers\Lightstar;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Feed_Mapper;
use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Repository;
use Supplier_Importer\Import\Mapping_Resolver;

class Lightstar_Mapper implements Feed_Mapper
{
    private $categories = [];
    private $mapping_repository;
    private $mapping_resolver;
    private $use_global_mappings = false;

    public function __construct(
        array $categories = [],
        $use_global_mappings = false
    ) {
        $this->categories = $categories;

        $this->mapping_repository = new Mapping_Repository();

        $this->mapping_resolver = new Mapping_Resolver(
            $this->mapping_repository
        );

        $this->use_global_mappings = (bool) $use_global_mappings;
    }

    public function get_format()
    {
        return 'yml';
    }

    public function map(array $record)
    {
        $offer_attributes = $record['@attributes'] ?? [];

        $sku = $this->scalar($record['vendorCode'] ?? '');

        if ($sku === '') {
            $sku = $this->get_param_value(
                $record['param'] ?? [],
                'Артикул'
            );
        }

        $supplier_id = $this->scalar(
            $offer_attributes['id'] ?? ''
        );

        if ($supplier_id === '') {
            $supplier_id = $sku;
        }

        $brand = $this->scalar($record['vendor'] ?? '');

        if ($brand === '') {
            $brand = 'Lightstar';
        }

        $attributes = $this->extract_params(
            $record['param'] ?? []
        );

        // Вес находится в отдельном XML-элементе.
        $weight = $this->scalar($record['weight'] ?? '');

        if ($weight !== '') {
            $attributes[] = [
                'name'  => 'Вес, кг',
                'value' => $weight,
            ];
        }

        // Страна производства находится вне param.
        $country = $this->scalar(
            $record['country_of_origin'] ?? ''
        );

        if ($country !== '') {
            $attributes[] = [
                'name'  => 'Страна производства',
                'value' => $country,
            ];
        }

        $data = [
            'supplier'    => 'lightstar',
            'supplier_id' => $supplier_id,
            'sku'         => $sku,
            'name'        => $this->scalar($record['name'] ?? ''),
            'brand'       => $brand,
            'barcode'     => $this->scalar($record['barcode'] ?? ''),
            'category'    => $this->get_category($record['categoryId'] ?? ''),
            'url'         => $this->scalar($record['url'] ?? ''),
            'description' => $this->scalar($record['description'] ?? ''),
            'price'       => $this->scalar($record['price'] ?? null),
            'stock'       => $this->get_stock($offer_attributes, $record),
            'images'      => $this->extract_pictures($record['picture'] ?? []),
            'attributes'  => $attributes,
        ];

        return Product_Data::from_array($data);
    }

    /**
     * Извлечь характеристики из param.
     */
    private function extract_params($source)
    {
        $params = [];

        foreach ($this->as_list($source) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $item_attributes = $item['@attributes'] ?? [];

            $name = $this->scalar(
                $item_attributes['name'] ?? ''
            );

            $value = $this->scalar($item);

            if ($name === '' || $value === '') {
                continue;
            }

            $normalized_name = $this->normalize_param_name($name);

            // Артикул и штрихкод уже передаются отдельными полями.
            if (in_array($normalized_name, ['артикул', 'штрихкод'], true)) {
                continue;
            }

            $params[] = [
                'name'  => $name,
                'value' => $value,
            ];
        }

        return $params;
    }

    /**
     * Найти значение характеристики по названию.
     */
    private function get_param_value($source, $param_name)
    {
        foreach ($this->as_list($source) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $item_attributes = $item['@attributes'] ?? [];

            $name = $this->scalar(
                $item_attributes['name'] ?? ''
            );

            if (
                $this->normalize_param_name($name)
                !== $this->normalize_param_name($param_name)
            ) {
                continue;
            }

            return $this->scalar($item);
        }

        return '';
    }

    /**
     * Извлечь URL изображений.
     */
    private function extract_pictures($source)
    {
        $pictures = [];

        foreach ($this->as_list($source) as $picture) {
            $url = $this->scalar($picture);

            if (
                $url === ''
                || !filter_var($url, FILTER_VALIDATE_URL)
            ) {
                continue;
            }

            $pictures[] = $url;
        }

        return array_values(array_unique($pictures));
    }

    /**
     * Получить категорию с учётом сопоставлений.
     */
    private function get_category($category_id)
    {
        $category = $this->resolve_category($category_id);

        if ($category === '') {
            return '';
        }

        if ($this->use_global_mappings) {
            $mapping = $this->mapping_resolver->resolve_category(
                'lightstar',
                $category
            );
        } else {
            $mapping = $this->mapping_repository->find(
                'lightstar',
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

    /**
     * Получить полный путь категории WooCommerce.
     */
    private function get_category_path($term_id)
    {
        $term = get_term($term_id, 'product_cat');

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

        return implode(' / ', array_reverse($parts));
    }

    /**
     * Получить полный путь категории поставщика.
     */
    private function resolve_category($category_id)
    {
        $category_id = $this->scalar($category_id);

        if ($category_id === '') {
            return '';
        }

        $category = $this->categories[$category_id] ?? null;

        if (is_array($category)) {
            return $category['path'] ?? $category['name'] ?? '';
        }

        return is_scalar($category) ? (string) $category : '';
    }

    /**
     * Получить остаток товара.
     */
    private function get_stock(array $attributes, array $record)
    {
        $available = isset($attributes['available'])
            ? strtolower(trim((string) $attributes['available']))
            : null;

        if ($available === 'false' || $available === '0') {
            return 0;
        }

        if ($available !== 'true' && $available !== '1') {
            return null;
        }

        foreach (['quantity_in_stock', 'stock'] as $field) {
            if (!array_key_exists($field, $record)) {
                continue;
            }

            $count = $this->scalar($record[$field]);

            if ($count === '' || !is_numeric($count)) {
                continue;
            }

            $count = (float) $count;

            if (!is_finite($count) || $count < 0) {
                continue;
            }

            return floor($count) === $count
                ? (int) $count
                : $count;
        }

        return null;
    }

    /**
     * Получить строковое значение из XMLReader-конвертера.
     */
    private function scalar($value)
    {
        if (is_array($value)) {
            if (!array_key_exists('@value', $value)) {
                return '';
            }

            return $this->scalar($value['@value']);
        }

        if (!is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * Привести одиночное значение или список к массиву.
     */
    private function as_list($value)
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            return [$value];
        }

        if (
            array_key_exists('@attributes', $value)
            || array_key_exists('@value', $value)
        ) {
            return [$value];
        }

        return $value;
    }

    /**
     * Нормализовать название характеристики.
     */
    private function normalize_param_name($name)
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower(trim($name), 'UTF-8')
            : strtolower(trim($name));
    }
}
