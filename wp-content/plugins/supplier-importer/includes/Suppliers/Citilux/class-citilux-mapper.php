<?php

namespace Supplier_Importer\Suppliers\Citilux;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Feed_Mapper;
use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Repository;
use Supplier_Importer\Import\Mapping_Resolver;

class Citilux_Mapper implements Feed_Mapper
{
    /**
     * Справочник категорий.
     *
     * @var array
     */
    private $categories = [];

    private $mapping_repository;

    private $mapping_resolver;

    private $use_global_mappings = false;

    /**
     * @param array $categories Справочник категорий.
     */
    public function __construct(
        array $categories = [],
        $use_global_mappings = false
    ) {
        $this->categories = $categories;

        $this->mapping_repository = new \Supplier_Importer\Import\Mapping_Repository();

        $this->mapping_resolver = new \Supplier_Importer\Import\Mapping_Resolver(
            $this->mapping_repository
        );

        $this->use_global_mappings = (bool) $use_global_mappings;
    }

    /**
     * Формат фида.
     *
     * @return string
     */
    public function get_format()
    {
        return 'xml';
    }

    /**
     * Преобразовать XML offer в Product_Data.
     *
     * @param array $record
     *
     * @return Product_Data
     */
    public function map(array $record)
    {
        $offer_attributes = $record['@attributes'] ?? [];

        $supplier_id = $this->scalar(
            $offer_attributes['id'] ?? ''
        );

        $sku = $this->scalar(
            $record['vendorCode'] ?? ''
        );

        $name = $this->scalar(
            $record['name'] ?? ''
        );

        $data = [
            'supplier'     => 'citilux',
            'supplier_id'  => $supplier_id,
            'sku'          => $sku,
            'name'         => $name,
            'brand'        => $this->scalar(
                $record['vendor'] ?? ''
            ),
            'barcode'      => $this->scalar(
                $record['barcode'] ?? ''
            ),
            'category' => $this->get_category(
                $record['categoryId'] ?? ''
            ),
            'url'          => $this->scalar(
                $record['url'] ?? ''
            ),
            'description'  => $this->scalar(
                $record['description'] ?? ''
            ),
            'price'        => $this->scalar(
                $record['price'] ?? null
            ),
            'stock'        => $this->get_stock(
                $offer_attributes,
                $record
            ),
            'images'       => $this->extract_pictures(
                $record['picture'] ?? []
            ),
            'attributes'   => $this->extract_params(
                $record['param'] ?? []
            ),
        ];

        return Product_Data::from_array($data);
    }

    /**
     * Извлечь параметры товара.
     *
     * Каждый параметр сохраняется отдельно,
     * без объединения значений.
     *
     * @param mixed $source
     *
     * @return array
     */
    private function extract_params($source)
    {
        $params = [];

        foreach ($this->as_list($source) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $attributes = $item['@attributes'] ?? [];

            $name = $this->scalar(
                $attributes['name'] ?? ''
            );

            $value = $this->scalar($item);

            if ($name === '' || $value === '') {
                continue;
            }

            /*
             * Штрихкод берём из отдельного поля <barcode>.
             * Не создаём для него атрибут товара.
             */
            if ($this->normalize_param_name($name) === 'штрихкод') {
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
     * Извлечь URL изображений.
     *
     * @param mixed $source
     *
     * @return array
     */
    private function extract_pictures($source)
    {
        $pictures = [];

        foreach ($this->as_list($source) as $picture) {
            $url = $this->scalar($picture);

            if ($url === '') {
                continue;
            }

            $pictures[] = $url;
        }

        return array_values(array_unique($pictures));
    }

    /**
     * Получить категорию с учётом сохранённого сопоставления.
     *
     * @param mixed $category_id ID категории из фида.
     *
     * @return string
     */
    private function get_category($category_id)
    {
        // Сначала получаем исходный путь категории CITILUX.
        $category = $this->resolve_category($category_id);

        if ($category === '') {
            return '';
        }

        // Ищем сопоставление категории.
        if ($this->use_global_mappings) {
            $mapping = $this->mapping_resolver->resolve_category(
                'citilux',
                $category
            );
        } else {
            $mapping = $this->mapping_repository->find(
                'citilux',
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

        // Если сопоставления нет, используем исходную категорию.
        return $category;
    }

    /**
     * Получить полный путь категории WooCommerce по ID термина.
     *
     * @param int $term_id ID категории.
     *
     * @return string
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
     * Получить название категории по ID.
     *
     * @param mixed $category_id
     *
     * @return string
     */
    private function resolve_category($category_id)
    {
        $category_id = $this->scalar($category_id);

        if ($category_id === '') {
            return '';
        }

        $category = $this->categories[$category_id] ?? null;

        \Supplier_Importer\Core\Logger::info(
            'CITILUX CATEGORY DEBUG: ' . wp_json_encode([
                'category_id' => $category_id,
                'category'    => $category,
                'categories_count' => count($this->categories),
            ], JSON_UNESCAPED_UNICODE)
        );

        if (is_array($category)) {
            return $category['path']
                ?? $category['name']
                ?? '';
        }

        return is_scalar($category)
            ? (string) $category
            : '';
    }

    /**
     * Определить остаток товара.
     *
     * available="false"              → 0
     * available="true" + count      → count
     * available="true" без count    → null
     *
     * @param array $attributes Атрибуты offer.
     * @param array $record     Данные offer.
     *
     * @return int|float|null
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

        if (!array_key_exists('count', $record)) {
            return null;
        }

        $count = $this->scalar($record['count']);

        if ($count === '' || !is_numeric($count)) {
            return null;
        }

        $count = (float) $count;

        if (!is_finite($count) || $count < 0) {
            return null;
        }

        return floor($count) === $count
            ? (int) $count
            : $count;
    }

    /**
     * Привести значение XML к строке.
     *
     * Xml_Reader может возвращать как строку,
     * так и массив с ключом @value.
     *
     * @param mixed $value
     *
     * @return string
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
     * Привести значение к списку.
     *
     * @param mixed $value
     *
     * @return array
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
     * Нормализовать название параметра.
     *
     * @param string $name
     *
     * @return string
     */
    private function normalize_param_name($name)
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower(trim($name), 'UTF-8')
            : strtolower(trim($name));
    }
}
