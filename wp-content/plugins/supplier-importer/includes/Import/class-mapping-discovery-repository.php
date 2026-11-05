<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Mapping_Discovery_Repository
{
    /**
     * Сохранить результаты анализа фида.
     *
     * @param string $supplier ID поставщика.
     * @param array  $analysis Результат Yml_Analyzer.
     *
     * @return void
     */
    public function save($supplier, array $analysis)
    {
        $supplier = sanitize_key($supplier);

        if ($supplier === '') {
            throw new \InvalidArgumentException(
                'Не указан поставщик.'
            );
        }

        $data = [
            'categories' => isset($analysis['categories'])
                && is_array($analysis['categories'])
                ? $analysis['categories']
                : [],

            'attributes' => isset($analysis['attributes'])
                && is_array($analysis['attributes'])
                ? $analysis['attributes']
                : [],

            'updated_at' => current_time('mysql'),
        ];

        update_option(
            $this->get_option_name($supplier),
            $data,
            false
        );
    }

    /**
     * Получить последние результаты анализа поставщика.
     *
     * @param string $supplier ID поставщика.
     *
     * @return array
     */
    public function get($supplier)
    {
        $supplier = sanitize_key($supplier);

        if ($supplier === '') {
            return [];
        }

        $data = get_option(
            $this->get_option_name($supplier),
            []
        );

        return is_array($data) ? $data : [];
    }

    /**
     * Сформировать имя option.
     *
     * @param string $supplier ID поставщика.
     *
     * @return string
     */
    private function get_option_name($supplier)
    {
        return 'supplier_importer_mapping_discovery_' . $supplier;
    }
}
