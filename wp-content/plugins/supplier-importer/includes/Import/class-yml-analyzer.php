<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use RuntimeException;
use Supplier_Importer\Suppliers\Maytoni\Maytoni_Mapper;

class Yml_Analyzer
{
    /**
     * Алиасы названий параметров из YML.
     *
     * Ключ — название из YML.
     * Значение — исходный ключ, используемый Maytoni_Mapper.
     */
    private $parameter_aliases = [
        'Коллекция' => 'collection_name',
        'Серия' => 'series_name',
        'Гарантия (лет)' => 'Гарантия',
        'Мощность (W)' => 'Мощность',
        'Цветовая температура (K)' => 'ЦветоваяТемпература',
        'IP' => 'ingress_protection_rating',
    ];

    /**
     * Проанализировать YML-файл.
     *
     * @param string $file_path Путь к YML-файлу.
     * @param array  $categories Категории [ID => название].
     *
     * @return array
     */
    public function analyze($file_path, array $categories)
    {
        $maytoni_mapper = new Maytoni_Mapper();

        $attribute_mapping = $maytoni_mapper->get_attribute_mapping();

        $category_counts = [];
        $attribute_counts = [];

        $reader = new Xml_Reader($file_path, 'offer');

        $reader->open();

        try {
            while (($record = $reader->read()) !== null) {
                if (!is_array($record)) {
                    continue;
                }

                $this->count_category(
                    $record,
                    $categories,
                    $category_counts
                );

                $this->count_attributes(
                    $record,
                    $attribute_mapping,
                    $attribute_counts
                );
            }
        } finally {
            $reader->close();
        }

        return [
            'categories' => $this->format_categories(
                $category_counts
            ),
            'attributes' => $this->format_attributes(
                $attribute_counts
            ),
        ];
    }

    /**
     * Посчитать товары по категориям.
     */
    private function count_category(
        array $record,
        array $categories,
        array &$category_counts
    ) {
        if (!isset($record['categoryId'])) {
            return;
        }

        $category_id = $record['categoryId'];

        if (is_array($category_id)) {
            $category_id = $category_id['@value'] ?? '';
        }

        if (!is_scalar($category_id)) {
            return;
        }

        $category_id = trim((string) $category_id);

        if ($category_id === '') {
            return;
        }

        $category = $categories[$category_id] ?? $category_id;

        // Категория может быть представлена массивом.
        if (is_array($category)) {
            $category = $category['name']
                ?? $category['@value']
                ?? '';
        }

        if (!is_scalar($category)) {
            return;
        }

        $category_name = trim((string) $category);

        if ($category_name === '') {
            return;
        }

        if (!isset($category_counts[$category_name])) {
            $category_counts[$category_name] = 0;
        }

        $category_counts[$category_name]++;
    }

    /**
     * Посчитать значения характеристик.
     */
    private function count_attributes(
        array $record,
        array $attribute_mapping,
        array &$attribute_counts
    ) {
        if (!isset($record['param'])) {
            return;
        }

        $params = $record['param'];

        // Один param может быть представлен ассоциативным массивом,
        // несколько param — массивом таких массивов.
        if (
            !is_array($params)
            || !isset($params[0])
        ) {
            $params = [$params];
        }

        $counted_sources = [];

        foreach ($params as $param) {
            if (!is_array($param)) {
                continue;
            }

            $name = $param['@attributes']['name'] ?? '';

            if (!is_scalar($name)) {
                continue;
            }

            $source = $this->normalize_parameter_name(
                trim((string) $name)
            );

            if (
                $source === ''
                || !isset($attribute_mapping[$source])
            ) {
                continue;
            }

            $taxonomy = $attribute_mapping[$source];

            $value = $param['@value'] ?? '';

            if (!is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            if (!isset($attribute_counts[$source])) {
                $attribute_counts[$source] = [
                    'taxonomy' => $taxonomy,
                    'count' => 0,
                    'values' => [],
                ];
            }

            // Количество товаров с характеристикой.
            // Один товар учитывается для source только один раз.
            if (!isset($counted_sources[$source])) {
                $attribute_counts[$source]['count']++;
                $counted_sources[$source] = true;
            }

            if (!isset(
                $attribute_counts[$source]['values'][$value]
            )) {
                $attribute_counts[$source]['values'][$value] = 0;
            }

            $attribute_counts[$source]['values'][$value]++;
        }
    }

    /**
     * Нормализовать название параметра.
     */
    private function normalize_parameter_name($name)
    {
        return $this->parameter_aliases[$name] ?? $name;
    }

    /**
     * Подготовить категории для JS.
     */
    private function format_categories(array $category_counts)
    {
        $result = [];

        foreach ($category_counts as $source => $count) {
            $result[] = [
                'source' => $source,
                'count' => $count,
            ];
        }

        usort($result, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $result;
    }

    /**
     * Подготовить характеристики для JS.
     */
    private function format_attributes(array $attribute_counts)
    {
        $result = [];

        foreach ($attribute_counts as $source => $attribute) {
            $values = [];

            foreach ($attribute['values'] as $value => $count) {
                $values[] = [
                    'value' => $value,
                    'count' => $count,
                ];
            }

            usort($values, function ($a, $b) {
                return $b['count'] <=> $a['count'];
            });

            $result[] = [
                'source' => $source,
                'taxonomy' => $attribute['taxonomy'],
                'count' => $attribute['count'],
                'values' => $values,
            ];
        }

        usort($result, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $result;
    }
}
