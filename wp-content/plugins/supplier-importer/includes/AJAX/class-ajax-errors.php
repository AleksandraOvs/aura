<?php

namespace Supplier_Importer\AJAX;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Import\Import_Error_Repository;

class Ajax_Errors
{
    public function __construct()
    {
        add_action(
            'wp_ajax_supplier_import_get_errors',
            [$this, 'get_errors']
        );
    }

    public function get_errors()
    {
        if (
            !current_user_can('manage_woocommerce')
        ) {
            wp_send_json_error([
                'message' => 'Недостаточно прав.',
            ], 403);
        }

        check_ajax_referer(
            'supplier_import',
            'nonce'
        );

        $import_id = isset($_POST['import_id'])
            ? absint($_POST['import_id'])
            : 0;

        if (!$import_id) {
            wp_send_json_error([
                'message' => 'Не указан ID импорта.',
            ], 400);
        }

        $repository =
            new Import_Error_Repository();

        $errors = $repository->get_by_import(
            $import_id
        );

        $result = [];

        foreach ($errors as $error) {
            $result[] = [
                'id'         => (int) $error['id'],
                'csv_row'    => (int) $error['csv_row'],
                'sku'        => $error['sku'],
                'error_type' => $error['error_type'],
                'message'    => $error['message'],
                'raw_data'   => $error['raw_data'],
                'created_at' => $error['created_at'],
            ];
        }

        wp_send_json_success([
            'errors' => $result,
            'count'  => count($result),
        ]);
    }
}
