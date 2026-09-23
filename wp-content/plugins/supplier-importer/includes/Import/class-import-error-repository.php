<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Import_Error_Repository
{
    private $table;

    public function __construct()
    {
        global $wpdb;

        $this->table =
            $wpdb->prefix
            . 'supplier_import_errors';
    }

    /**
     * Добавить ошибку импорта.
     */
    public function add(
        $import_id,
        $csv_row,
        $sku,
        $error_type,
        $message,
        $raw_data = []
    ) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table,
            [
                'import_id'  => (int) $import_id,
                'csv_row'    => (int) $csv_row,
                'sku'        => (string) $sku,
                'error_type' => (string) $error_type,
                'message'    => (string) $message,
                'raw_data'   => wp_json_encode(
                    $raw_data,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                ),
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Получить все ошибки конкретного импорта.
     */
    public function get_by_import($import_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->table}
                WHERE import_id = %d
                ORDER BY csv_row ASC, id ASC",
                (int) $import_id
            ),
            ARRAY_A
        );
    }

    /**
     * Получить количество ошибок конкретного импорта.
     */
    public function count_by_import($import_id)
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$this->table}
                WHERE import_id = %d",
                (int) $import_id
            )
        );
    }

    /**
     * Удалить ошибки конкретного импорта.
     */
    public function delete_by_import($import_id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->table,
            [
                'import_id' => (int) $import_id,
            ],
            [
                '%d',
            ]
        );
    }
}
