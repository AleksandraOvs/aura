<?php

namespace Supplier_Importer\Suppliers\CrystalLux;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Feed_Mapper;
use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Repository;
use Supplier_Importer\Import\Mapping_Resolver;

class Crystal_Lux_Yml_Mapper implements Feed_Mapper
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

        $params = $this->extract_params(
            $record['param'] ?? []
        );

        $sku = $this->get_param_value($params, 'Артикул');

        if ($sku === '') {
            throw new \InvalidArgumentException(
                'В строке Crystal Lux отсутствует артикул.'
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
            $brand = 'Crystal Lux';
        }

        $attributes = [];

        foreach ($params as $name => $value) {
            $normalized_name = $this->normalize_param_name($name);

            // Эти значения передаются отдельными полями.
            if (in_array(
                $normalized_name,
                ['артикул', 'штрихкод', 'код', 'stock'],
                true
            )) {
                continue;
            }

            $attributes[] = [
                'name'  => $name,
                'value' => $value,
            ];
        }

        $barcode = $this->get_param_value(
            $params,
            'Штрихкод'
        );

        $stock_value = $this->get_param_value(
            $params,
            'stock'
        );

        return Product_Data::from_array([
            'supplier'    => 'crystal_lux',
            'supplier_id' => $supplier_id,
            'sku'         => $sku,
            'name'        => $this->scalar($record['model'] ?? ''),
            'brand'       => $brand,
            'barcode'     => $barcode,
            'category'    => $this->get_category(
                $record['categoryId'] ?? ''
            ),
            'url'         => $this->scalar($record['url'] ?? ''),
            'description' => $this->scalar(
                $record['description'] ?? ''
            ),
            'price'       => $this->scalar(
                $record['price'] ?? null
            ),
            'stock'       => $this->get_stock(
                $offer_attributes,
                $stock_value
            ),
            'images'      => $this->extract_pictures(
                $record['picture'] ?? []
            ),
            'attributes'  => $attributes,
        ]);
    }

    /**
     * Собрать характеристики из param.
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

            $params[$name] = $value;
        }

        return $params;
    }

    /**
     * Найти характеристику по названию.
     */
    private function get_param_value(array $params, $param_name)
    {
        $expected = $this->normalize_param_name($param_name);

        foreach ($params as $name => $value) {
            if ($this->normalize_param_name($name) === $expected) {
                return $value;
            }
        }

        return '';
    }

    /**
     * Получить изображения.
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
        $category_id = $this->scalar($category_id);

        if ($category_id === '') {
            return '';
        }

        $category = $this->categories[$category_id] ?? null;

        if (is_array($category)) {
            $category = $category['path'] ?? $category['name'] ?? '';
        }

        if (!is_scalar($category) || $category === '') {
            return '';
        }

        $category = (string) $category;

        if ($this->use_global_mappings) {
            $mapping = $this->mapping_resolver->resolve_category(
                'crystal_lux',
                $category
            );
        } else {
            $mapping = $this->mapping_repository->find(
                'crystal_lux',
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

        return implode(' / ', array_reverse($parts));
    }

    /**
     * Получить остаток.
     */
    private function get_stock(array $attributes, $stock)
    {
        $available = strtolower(trim(
            (string) ($attributes['available'] ?? '')
        ));

        if (in_array($available, ['false', '0'], true)) {
            return 0;
        }

        if (!is_numeric($stock)) {
            return null;
        }

        $stock = (float) $stock;

        if (!is_finite($stock) || $stock < 0) {
            return null;
        }

        return floor($stock) === $stock
            ? (int) $stock
            : $stock;
    }

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

    private function normalize_param_name($name)
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower(trim($name), 'UTF-8')
            : strtolower(trim($name));
    }
}
