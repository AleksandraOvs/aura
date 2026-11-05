<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Yml_Analyzer
{
    /**
     * Максимальное количество значений одного атрибута.
     */
    private const MAX_ATTRIBUTE_VALUES = 100;
    /**
     * Проанализировать YML-файл.
     *
     * @param string $file_path Путь к YML-файлу.
     * @param array  $categories Справочник категорий.
     *
     * @return array
     */

    public function analyze($file_path, array $categories)
    {
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

        $category = $categories[$category_id] ?? null;

        if (is_array($category)) {
            $category_name = $category['path']
                ?? $category['name']
                ?? '';
        } elseif (is_scalar($category)) {
            // Временная совместимость со старым форматом справочника.
            $category_name = (string) $category;
        } else {
            $category_name = $category_id;
        }

        $category_name = trim($category_name);

        if ($category_name === '') {
            return;
        }

        if (!isset($category_counts[$category_name])) {
            $category_counts[$category_name] = [
                'category_id' => $category_id,
                'count' => 0,
            ];
        }

        $category_counts[$category_name]['count']++;
    }

    /**
     * Посчитать значения характеристик.
     */
    private function count_attributes(
        array $record,
        array &$attribute_counts
    ) {
        if (!isset($record['param'])) {
            return;
        }

        $params = $record['param'];

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

            $source = trim((string) $name);

            if ($source === '') {
                continue;
            }

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
                    'count' => 0,
                    'values' => [],
                ];
            }

            if (!isset($counted_sources[$source])) {
                $attribute_counts[$source]['count']++;
                $counted_sources[$source] = true;
            }
            $values = &$attribute_counts[$source]['values'];

            if (isset($values[$value])) {
                $values[$value]++;
            } elseif (
                count($values) < self::MAX_ATTRIBUTE_VALUES
            ) {
                $values[$value] = 1;
            } else {
                $min_count = min($values);

                if ($min_count > 1) {
                    $min_value = array_search(
                        $min_count,
                        $values,
                        true
                    );

                    if ($min_value !== false) {
                        unset($values[$min_value]);
                        $values[$value] = 1;
                    }
                }
            }

            unset($values);
        }
    }

    /**
     * Подготовить категории для JS.
     */
    private function format_categories(array $category_counts)
    {
        $result = [];

        foreach ($category_counts as $source => $data) {
            $result[] = [
                'source' => $source,
                'category_id' => $data['category_id'],
                'count' => $data['count'],
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
