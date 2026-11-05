<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Mapping_Repository
{
    private $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix . 'supplier_mappings';
    }

    /**
     * Найти mapping.
     */
    public function find(
        $supplier,
        $type,
        $source_value,
        $source_parent = ''
    ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
            FROM {$this->table}
            WHERE supplier = %s
            AND type = %s
            AND source_parent = %s
            AND source_value = %s
            LIMIT 1",
                $supplier,
                $type,
                (string) $source_parent,
                $source_value
            ),
            ARRAY_A
        );
    }

    /**
     * Получить mappings поставщика.
     */
    public function get_by_supplier(
        $supplier,
        $type = ''
    ) {
        global $wpdb;

        if ($type !== '') {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->table}
                    WHERE supplier = %s
                    AND type = %s
                    ORDER BY source_parent ASC, source_value ASC",
                    $supplier,
                    $type
                ),
                ARRAY_A
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->table}
                WHERE supplier = %s
                ORDER BY type ASC, source_parent ASC, source_value ASC",
                $supplier
            ),
            ARRAY_A
        );
    }

    /**
     * Сохранить mapping.
     */
    public function save(
        $supplier,
        $type,
        $source_value,
        $target_id,
        $source_parent = ''
    ) {
        global $wpdb;

        $source_parent = (string) $source_parent;

        $existing = $this->find(
            $supplier,
            $type,
            $source_value,
            $source_parent
        );

        if ($existing) {
            $result = $wpdb->update(
                $this->table,
                [
                    'target_id' => $target_id
                        ? (int) $target_id
                        : null,
                ],
                [
                    'id' => (int) $existing['id'],
                ],
                [
                    $target_id ? '%d' : null,
                ],
                [
                    '%d',
                ]
            );

            if ($result === false) {
                return 0;
            }

            return (int) $existing['id'];
        }

        $data = [
            'supplier'      => (string) $supplier,
            'type'          => (string) $type,
            'source_parent' => $source_parent,
            'source_value'  => (string) $source_value,
            'target_id'     => $target_id
                ? (int) $target_id
                : null,
        ];

        $formats = [
            '%s',
            '%s',
            '%s',
            '%s',
            $target_id ? '%d' : null,
        ];

        $result = $wpdb->insert(
            $this->table,
            $data,
            $formats
        );

        if ($result === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Удалить mapping.
     */
    public function delete(
        $supplier,
        $type,
        $source_value,
        $source_parent = ''
    ) {
        global $wpdb;

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table}
            WHERE supplier = %s
            AND type = %s
            AND source_parent = %s
            AND source_value = %s",
                $supplier,
                $type,
                (string) $source_parent,
                $source_value
            )
        );
    }

    /**
     * Получить сопоставления значений атрибутов поставщика.
     *
     * Результат:
     * [
     *     'source_parent' => [
     *         'исходное значение' => ID термина WooCommerce,
     *     ],
     * ]
     *
     * @param string $supplier
     *
     * @return array
     */
    public function get_attribute_value_mappings($supplier)
    {
        $rows = $this->get_by_supplier(
            $supplier,
            'attribute_value'
        );

        $mappings = [];

        foreach ($rows as $row) {
            $source_parent = (string) $row['source_parent'];
            $source_value  = (string) $row['source_value'];
            $target_id     = (int) $row['target_id'];

            if ($target_id <= 0) {
                continue;
            }

            if (!isset($mappings[$source_parent])) {
                $mappings[$source_parent] = [];
            }

            $mappings[$source_parent][$source_value] = $target_id;
        }

        return $mappings;
    }


    /**
     * Найти сопоставление с учётом глобальных правил.
     *
     * Приоритет:
     * 1. Индивидуальное правило поставщика.
     * 2. Глобальное правило (*).
     *
     * @param string $supplier
     * @param string $type
     * @param string $source_value
     * @param string $source_parent
     *
     * @return array|null
     */
    public function find_with_fallback(
        $supplier,
        $type,
        $source_value,
        $source_parent = ''
    ) {
        // Сначала ищем индивидуальное правило.
        $mapping = $this->find(
            $supplier,
            $type,
            $source_value,
            $source_parent
        );

        if ($mapping) {
            return $mapping;
        }

        // Если индивидуального нет — ищем глобальное.
        return $this->find(
            '*',
            $type,
            $source_value,
            $source_parent
        );
    }

    /**
     * Получить индивидуальные и глобальные сопоставления.
     *
     * Индивидуальные правила имеют приоритет над глобальными.
     *
     * @param string $supplier
     * @param string $type
     *
     * @return array
     */
    public function get_by_supplier_with_fallback(
        $supplier,
        $type = ''
    ) {
        $global_mappings = $this->get_by_supplier(
            '*',
            $type
        );

        $supplier_mappings = $this->get_by_supplier(
            $supplier,
            $type
        );

        $result = [];

        // Сначала добавляем глобальные правила.
        foreach ($global_mappings as $mapping) {
            $key = serialize([
                $mapping['type'],
                $mapping['source_parent'],
                $mapping['source_value'],
            ]);

            $result[$key] = $mapping;
        }

        // Индивидуальные правила перекрывают глобальные.
        foreach ($supplier_mappings as $mapping) {
            $key = serialize([
                $mapping['type'],
                $mapping['source_parent'],
                $mapping['source_value'],
            ]);

            $result[$key] = $mapping;
        }

        return array_values($result);
    }
}
