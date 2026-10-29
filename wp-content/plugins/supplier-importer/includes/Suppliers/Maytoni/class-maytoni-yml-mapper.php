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

    public function __construct(array $categories = [])
    {
        $this->categories = $categories;
    }
    public function get_format()
    {
        return 'yml';
    }

    /**
     * Преобразовать YML offer в существующий формат Maytoni CSV mapper.
     */
    public function map(array $record)
    {
        $attributes = $record['@attributes'] ?? [];

        $params = $this->extract_params($record['param'] ?? []);

        // Приводим известные названия параметров YML
        // к ключам, которые ожидает существующий Maytoni_Mapper.
        $param_aliases = [
            'Коллекция' => 'collection_name',
            'Серия' => 'series_name',
            'Гарантия (лет)' => 'Гарантия',
            'Мощность (W)' => 'Мощность',
            'Цветовая температура (K)' => 'ЦветоваяТемпература',
            'IP' => 'ingress_protection_rating',
        ];

        foreach ($param_aliases as $source => $target) {
            if (
                isset($params[$source])
                && !isset($params[$target])
            ) {
                $params[$target] = $params[$source];
            }
        }

        $row = [];

        $row['id'] = $attributes['id'] ?? '';
        $row['vendorCode'] = $this->scalar($record['vendorCode'] ?? '');
        $row['name'] = $this->scalar($record['name'] ?? '');
        $row['url'] = $this->scalar($record['url'] ?? '');
        $row['price'] = $this->scalar($record['price'] ?? '');
        $row['description'] = $this->scalar(
            $record['description'] ?? ''
        );
        $row['barcode'] = $this->scalar($record['barcode'] ?? '');

        $row['brand_name'] = $params['Бренд']
            ?? $this->scalar($record['vendor'] ?? '');

        $category_id = $this->scalar(
            $record['categoryId'] ?? ''
        );

        $row['Категория'] = $this->categories[$category_id]
            ?? '';

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

        // Сохраняем изображения в том порядке,
        // в котором они указаны в фиде.
        $pictures = $this->as_list($record['picture'] ?? []);

        foreach ($pictures as $index => $picture) {
            $row['Фото' . ($index + 1)] = $this->scalar($picture);
        }

        // Переносим параметры в поля, которые уже
        // использует существующий Maytoni_Mapper.
        foreach ($params as $name => $value) {
            if (!array_key_exists($name, $row)) {
                $row[$name] = $value;
            }
        }

        // Цена может отсутствовать у отдельных записей.
        // Не подставляем искусственный ноль.
        if ($row['price'] === '') {
            $row['price'] = null;
        }

        $mapper = new Maytoni_Mapper();
        $product_data = $mapper->map($row);

        if (!$product_data instanceof Product_Data) {
            throw new \UnexpectedValueException(
                'Maytoni mapper должен возвращать Product_Data.'
            );
        }

        return $product_data;
    }

    /**
     * Собрать параметры из элементов param.
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
                ? trim((string) $attributes['name'])
                : '';

            if ($name === '') {
                continue;
            }

            $value = $this->scalar($item);

            if ($value !== '') {
                $params[$name] = $value;
            }
        }

        return $params;
    }

    /**
     * Получить остаток товара.
     */
    private function get_stock(
        array $params,
        array $record,
        array $attributes
    ) {
        // Явно недоступный товар считаем отсутствующим на складе.
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

        // Массив XML-элемента с атрибутами — это один элемент,
        // а не список повторяющихся элементов.
        if (
            array_key_exists('@attributes', $value)
            || array_key_exists('@value', $value)
        ) {
            return [$value];
        }

        return $value;
    }
}
