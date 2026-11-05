<?php

namespace Supplier_Importer\Suppliers\Maytoni;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Feed_Mapper;
use Supplier_Importer\Product\Product_Data;

class Maytoni_Yml_Mapper implements Feed_Mapper
{
    /**
     * Справочник категорий поставщика.
     *
     * @var array
     */
    private $categories = [];

    private $mapper;

    public function __construct(array $categories = [])
    {
        $this->categories = $categories;
        $this->mapper = new Maytoni_Mapper(true);
    }

    public function get_format()
    {
        return 'yml';
    }

    /**
     * Преобразовать YML offer в существующий формат Maytoni mapper.
     */
    public function map(array $record)
    {
        $attributes = $record['@attributes'] ?? [];

        $params = $this->extract_params(
            $record['param'] ?? []
        );
        $source_params = $params;



        // Приводим известные названия параметров YML
        // к ключам, которые ожидает Maytoni_Mapper.
        $param_aliases = [
            'Коллекция' => 'collection_name',
            'Серия' => 'series_name',
            'Гарантия (лет)' => 'Гарантия',
            'Мощность (W)' => 'Мощность',
            'Цветовая температура (K)' => 'ЦветоваяТемпература',
            'IP' => 'ingress_protection_rating',
        ];

        foreach ($param_aliases as $source => $target) {
            if (!isset($params[$source])) {
                continue;
            }

            if (isset($params[$target])) {
                $params[$target] = $this->merge_values(
                    $params[$target],
                    $params[$source]
                );
            } else {
                $params[$target] = $params[$source];
            }
        }

        $row = [];

        $row['_supplier_attributes'] = $source_params;

        $row['id'] = $attributes['id'] ?? '';
        $row['vendorCode'] = $this->scalar(
            $record['vendorCode'] ?? ''
        );
        $row['name'] = $this->scalar(
            $record['name'] ?? ''
        );
        $row['url'] = $this->scalar(
            $record['url'] ?? ''
        );
        $row['price'] = $this->scalar(
            $record['price'] ?? ''
        );
        $row['description'] = $this->scalar(
            $record['description'] ?? ''
        );
        $row['barcode'] = $this->scalar(
            $record['barcode'] ?? ''
        );

        $row['brand_name'] = $params['Бренд']
            ?? $this->scalar($record['vendor'] ?? '');

        $category_id = $this->scalar(
            $record['categoryId'] ?? ''
        );

        $category = $this->categories[$category_id] ?? null;

        if (is_array($category)) {
            $row['Категория'] = $category['path']
                ?? $category['name']
                ?? '';
        } elseif (is_scalar($category)) {
            // Совместимость со старым форматом справочника.
            $row['Категория'] = (string) $category;
        } else {
            $row['Категория'] = '';
        }

        \Supplier_Importer\Core\Logger::info(
            'MAYTONI YML ROW DEBUG',
            [
                'id' => $row['id'],
                'vendorCode' => $row['vendorCode'],
                'name' => $row['name'],
                'price' => $row['price'],
                'category' => $row['Категория'],
            ]
        );

        $row['Stock'] = $this->get_stock(
            $params,
            $record,
            $attributes
        );

        $row['Полное наименование'] =
            $params['Полное наименование'] ?? '';

        $row['Инструкция'] = $this->scalar(
            $record['instruction'] ?? ''
        );

        // Сохраняем изображения в исходном порядке.
        $pictures = $this->as_list(
            $record['picture'] ?? []
        );

        foreach ($pictures as $index => $picture) {
            $row['Фото' . ($index + 1)] = $this->scalar(
                $picture
            );
        }

        // Переносим параметры в поля Maytoni_Mapper.
        foreach ($params as $name => $value) {
            if (!array_key_exists($name, $row)) {
                $row[$name] = $value;
            }
        }


        // Не подставляем искусственный ноль,
        // если цена отсутствует.
        if ($row['price'] === '') {
            $row['price'] = null;
        }

        $product_data = $this->mapper->map($row);

        if (!$product_data instanceof Product_Data) {
            throw new \UnexpectedValueException(
                'Maytoni mapper должен возвращать Product_Data.'
            );
        }

        return $product_data;
    }

    /**
     * Собрать параметры из элементов param.
     *
     * Повторяющиеся параметры объединяются через запятую.
     */
    private function extract_params($source)
    {
        $params = [];


        foreach ($this->as_list($source) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $attributes = $item['@attributes'] ?? [];

            $name = isset($attributes['name'])
                && is_scalar($attributes['name'])
                ? trim((string) $attributes['name'])
                : '';

            if ($name === '') {
                continue;
            }

            $value = $this->scalar($item);

            if ($value === '') {
                continue;
            }

            if (!isset($params[$name])) {
                $params[$name] = $value;
                continue;
            }

            $params[$name] = $this->merge_values(
                $params[$name],
                $value
            );
        }

        return $params;
    }

    /**
     * Объединить значения через запятую без повторов.
     */
    private function merge_values($first, $second)
    {
        $values = [];

        foreach ([$first, $second] as $source) {
            foreach (explode(',', (string) $source) as $value) {
                $value = trim($value);

                if ($value === '') {
                    continue;
                }

                if (!in_array($value, $values, true)) {
                    $values[] = $value;
                }
            }
        }

        return implode(', ', $values);
    }

    /**
     * Получить остаток товара.
     */
    private function get_stock(
        array $params,
        array $record,
        array $attributes
    ) {
        if (
            isset($attributes['available'])
            && strtolower((string) $attributes['available']) === 'false'
        ) {
            return 0;
        }

        if (
            isset($params['Остаток'])
            && $params['Остаток'] !== ''
        ) {
            return $params['Остаток'];
        }

        $outlets = $record['outlets']['outlet'] ?? [];

        foreach ($this->as_list($outlets) as $outlet) {
            if (
                is_array($outlet)
                && isset($outlet['@attributes']['instock'])
                && $outlet['@attributes']['instock'] !== ''
            ) {
                return $outlet['@attributes']['instock'];
            }
        }

        return null;
    }

    /**
     * Привести значение к строке.
     */
    private function scalar($value)
    {
        if (is_array($value)) {
            return isset($value['@value'])
                ? trim((string) $value['@value'])
                : '';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    /**
     * Привести одиночный элемент или массив к списку.
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
}
