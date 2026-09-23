<?php

namespace Supplier_Importer\AJAX;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use RuntimeException;
use Supplier_Importer\Core\Logger;
use Supplier_Importer\Core\Plugin;
use Supplier_Importer\CSV\Csv_Reader;
use Supplier_Importer\Import\Import_Repository;
use Supplier_Importer\Import\Import_Session;
use Supplier_Importer\CSV\Csv_Analyzer;



class Ajax_Import
{
    private $repository;

    public function __construct()
    {
        $this->repository = new Import_Repository();

        add_action(
            'wp_ajax_supplier_import_start',
            [$this, 'start_import']
        );
    }

    public function start_import()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            if (
                !isset($_FILES['file'])
                || empty($_FILES['file']['tmp_name'])
            ) {
                throw new InvalidArgumentException(
                    'CSV-файл не загружен.'
                );
            }

            $file = $_FILES['file'];

            if (
                !empty($file['error'])
                && $file['error'] !== UPLOAD_ERR_OK
            ) {
                throw new InvalidArgumentException(
                    'Ошибка загрузки CSV-файла.'
                );
            }

            $file_path = $this->save_uploaded_file(
                $file
            );

            /*
         * Открываем CSV и определяем поставщика.
         */
            $reader = new Csv_Reader(
                $file_path
            );

            $reader->open();

            $headers = $reader->get_headers();

            $supplier_manager =
                Plugin::get_supplier_manager();

            $supplier = $supplier_manager->detect(
                $headers
            );

            if (!$supplier) {
                $reader->close();

                throw new InvalidArgumentException(
                    'Не удалось определить поставщика по структуре CSV.'
                );
            }

            /*
         * Анализируем CSV.
         */
            $analyzer = new Csv_Analyzer();

            $analysis = $analyzer->analyze(
                $reader,
                $supplier->get_csv_analysis_config()
            );

            $reader->close();

            /*
         * Открываем CSV заново,
         * потому что после анализа reader дочитан до конца.
         */
            $reader = new Csv_Reader(
                $file_path
            );

            $reader->open();

            $total = $this->count_rows(
                $reader
            );

            $reader->close();

            if ($total === 0) {
                throw new InvalidArgumentException(
                    'CSV-файл не содержит товаров.'
                );
            }

            /*
         * Создаём сессию импорта.
         */
            $session = new Import_Session(
                $supplier->get_id(),
                $file_path,
                $total
            );

            $import_id = $this->repository->create(
                $session
            );

            Logger::info(
                'Импорт создан через AJAX.',
                [
                    'import_id' => $import_id,
                    'supplier'  => $supplier->get_id(),
                    'file'      => $file_path,
                    'total'     => $total,
                ]
            );

            wp_send_json_success([
                'import_id' => $import_id,

                'supplier' => [
                    'id'   => $supplier->get_id(),
                    'name' => $supplier->get_name(),
                ],

                'total' => $total,

                'status' => $session->get_status(),

                'analysis' => $analysis,
            ]);
        } catch (\Throwable $e) {
            Logger::error(
                'Не удалось создать импорт через AJAX.',
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
                'Недостаточно прав для запуска импорта.'
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

    private function save_uploaded_file($file)
    {
        $upload_dir = wp_upload_dir();

        $directory = trailingslashit(
            $upload_dir['basedir']
        ) . 'supplier-importer';

        if (!wp_mkdir_p($directory)) {
            throw new RuntimeException(
                'Не удалось создать директорию для импортов.'
            );
        }

        $file_name = sanitize_file_name(
            $file['name']
        );

        if ($file_name === '') {
            $file_name = 'supplier-import.csv';
        }

        $file_name = wp_unique_filename(
            $directory,
            $file_name
        );

        $destination = trailingslashit(
            $directory
        ) . $file_name;

        if (!move_uploaded_file(
            $file['tmp_name'],
            $destination
        )) {
            throw new RuntimeException(
                'Не удалось сохранить CSV-файл.'
            );
        }

        return $destination;
    }

    private function count_rows(Csv_Reader $reader)
    {
        $total = 0;

        while ($reader->read() !== null) {
            $total++;
        }

        return $total;
    }
}
