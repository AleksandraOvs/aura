<?php

namespace Supplier_Importer\CSV;

if (!defined('ABSPATH')) {
    exit;
}

use RuntimeException;

class Csv_Analyzer
{
    /**
     * Анализировать CSV-файл.
     *
     * @param Csv_Reader $reader
     * @param array      $config
     *
     * @return array
     */
    public function analyze(
        Csv_Reader $reader,
        $config
    ) {
        $category_fields = isset(
            $config['category_fields']
        )
            ? (array) $config['category_fields']
            : [];

        $attribute_fields = isset(
            $config['attribute_fields']
        )
            ? (array) $config['attribute_fields']
            : [];

        $categories = [];
        $attributes = [];

        while ($row = $reader->read()) {
            $data = $row['data'];

            $this->collect_categories(
                $data,
                $category_fields,
                $categories
            );

            $this->collect_attributes(
                $data,
                $attribute_fields,
                $attributes
            );
        }

        return [
            'categories' => $this->format_categories(
                $categories
            ),

            'attributes' => $this->format_attributes(
                $attributes
            ),
        ];
    }



    /**
     * Собрать категории.
     */
    private function collect_categories(
        $data,
        $fields,
        &$categories
    ) {
        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = trim(
                (string) $data[$field]
            );

            if ($value === '') {
                continue;
            }

            if (!isset($categories[$value])) {
                $categories[$value] = [
                    'source' => $value,
                    'count'  => 0,
                ];
            }

            $categories[$value]['count']++;
        }
    }

    /**
     * Собрать атрибуты и их значения.
     */
    private function collect_attributes(
        $data,
        $fields,
        &$attributes
    ) {
        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = trim(
                (string) $data[$field]
            );

            if ($value === '') {
                continue;
            }

            if (!isset($attributes[$field])) {
                $attributes[$field] = [
                    'source' => $field,
                    'count'  => 0,
                    'values' => [],
                ];
            }

            $attributes[$field]['count']++;

            if (
                !isset(
                    $attributes[$field]['values'][$value]
                )
            ) {
                $attributes[$field]['values'][$value] = 0;
            }

            $attributes[$field]['values'][$value]++;
        }
    }

    /**
     * Подготовить категории для результата.
     */
    private function format_categories(
        $categories
    ) {
        return array_values($categories);
    }

    /**
     * Подготовить атрибуты для результата.
     */
    private function format_attributes(
        $attributes
    ) {
        $result = [];

        foreach ($attributes as $attribute) {
            $values = [];

            foreach (
                $attribute['values']
                as $value => $count
            ) {
                $values[] = [
                    'value' => $value,
                    'count' => $count,
                ];
            }

            $attribute['values'] = $values;

            $result[] = $attribute;
        }

        return $result;
    }
}
