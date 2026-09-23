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
        $source_parent = null
    ) {
        global $wpdb;

        if ($source_parent === null) {
            return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->table}
                    WHERE supplier = %s
                    AND type = %s
                    AND source_parent IS NULL
                    AND source_value = %s
                    LIMIT 1",
                    $supplier,
                    $type,
                    $source_value
                ),
                ARRAY_A
            );
        }

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
                $source_parent,
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
}
