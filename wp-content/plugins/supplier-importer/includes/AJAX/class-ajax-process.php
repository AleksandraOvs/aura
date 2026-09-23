<?php

namespace Supplier_Importer\AJAX;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use RuntimeException;
use Supplier_Importer\Core\Logger;
use Supplier_Importer\Core\Plugin;
use Supplier_Importer\Import\Import_Processor;
use Supplier_Importer\Import\Import_Repository;
use Supplier_Importer\CSV\Csv_Reader;

class Ajax_Process
{
    private $repository;

    public function __construct()
    {
        $this->repository = new Import_Repository();

        add_action(
            'wp_ajax_supplier_import_process',
            [$this, 'process_import']
        );
    }

    public function process_import()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $import_id = isset($_POST['import_id'])
                ? absint($_POST['import_id'])
                : 0;

            if (!$import_id) {
                throw new InvalidArgumentException(
                    'Не указан ID импорта.'
                );
            }

            $session = $this->repository->load_session(
                $import_id
            );

            if ($session->is_finished()) {
                throw new RuntimeException(
                    'Импорт уже завершён.'
                );
            }

            $supplier_manager = Plugin::get_supplier_manager();

            $supplier = $supplier_manager->get(
                $session->get_supplier()
            );

            if (!$supplier) {
                throw new RuntimeException(
                    sprintf(
                        'Поставщик "%s" не найден.',
                        $session->get_supplier()
                    )
                );
            }

            $reader = new Csv_Reader(
                $session->get_file()
            );

            try {
                $reader->open();

                $processor = new Import_Processor(
                    $session,
                    $reader,
                    $supplier
                );

                $result = $processor->process();
            } finally {
                $reader->close();
            }

            Logger::info(
                'Чанк импорта обработан через AJAX.',
                [
                    'import_id' => $import_id,
                    'offset'    => $session->get_offset(),
                    'status'    => $session->get_status(),
                ]
            );

            wp_send_json_success([
                'import_id' => $import_id,
                'status'    => $session->get_status(),
                'offset'    => $session->get_offset(),
                'total'     => $session->get_total(),
                'progress'  => $session->get_progress()->to_array(),
                'finished'  => $session->is_finished(),
            ]);
        } catch (\Throwable $e) {
            Logger::error(
                'Не удалось обработать чанк импорта через AJAX.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    private function check_permissions()
    {
        if (!current_user_can('manage_woocommerce')) {
            throw new RuntimeException(
                'Недостаточно прав для обработки импорта.'
            );
        }
    }

    private function check_nonce()
    {
        if (
            !isset($_POST['nonce'])
            || !wp_verify_nonce(
                $_POST['nonce'],
                'supplier_import'
            )
        ) {
            throw new RuntimeException(
                'Ошибка проверки безопасности.'
            );
        }
    }
}
