<?php

namespace Supplier_Importer\Import;

use Supplier_Importer\CSV\Csv_Reader;
use Supplier_Importer\Core\Logger;
use Supplier_Importer\Suppliers\Supplier;

class Import_Processor
{
    private $session;
    private $chunk;
    private $import_manager;
    private $mapper;
    private $repository;
    private $error_repository;

    public function __construct(
        Import_Session $session,
        Csv_Reader $reader,
        Supplier $supplier
    ) {
        $this->session = $session;
        $this->chunk = new Import_Chunk($reader);
        $this->import_manager = new Import_Manager();
        $this->mapper = $supplier->get_mapper();
        $this->repository = new Import_Repository();
        $this->error_repository = new Import_Error_Repository();
    }

    public function process()
    {


        $chunk_start = microtime(true);

        $this->session->start();

        Logger::info(
            'CHUNK DEBUG: START',
            [
                'import_id' => $this->session->get_import_id(),
                'offset'    => $this->session->get_offset(),
                'total'     => $this->session->get_total(),
            ]
        );
        $rows = $this->chunk->read(
            $this->session->get_offset()
        );

        if (empty($rows)) {
            $this->session->complete();

            if ($this->session->get_import_id()) {
                $this->repository->update($this->session);
            }

            return $this->get_result();
        }

        /*
         * Храним товар вместе с исходной строкой CSV.
         *
         * Это позволяет при ошибке импорта точно определить,
         * из какой строки CSV был создан этот товар.
         */
        $products = [];

        foreach ($rows as $row) {

            try {

                $mapped_product = $this->mapper->map(
                    $row['data']
                );

                $products[] = [
                    'product' => $mapped_product,
                    'row'     => $row,
                ];
            } catch (\Throwable $e) {

                $this->session
                    ->get_progress()
                    ->increment_errors();

                $this->save_error(
                    $row,
                    $e,
                    'mapping'
                );
            }

            $this->session
                ->get_progress()
                ->increment_processed();
        }

        /*
         * Импортируем товары по одному.
         */
        if (!empty($products)) {

            foreach ($products as $item) {

                $product = $item['product'];
                $row = $item['row'];

                try {

                    $product_start = microtime(true);

                    Logger::info(
                        'CHUNK DEBUG: PRODUCT START',
                        [
                            'import_id' => $this->session->get_import_id(),
                            'offset'    => $this->session->get_offset(),
                            'sku'       => $product->get_sku(),
                        ]
                    );

                    $single_result = $this->import_manager->import(
                        [$product]
                    );

                    Logger::info(
                        'CHUNK DEBUG: PRODUCT END',
                        [
                            'import_id' => $this->session->get_import_id(),
                            'sku'       => $product->get_sku(),
                            'time'      => round(
                                microtime(true) - $product_start,
                                2
                            ),
                        ]
                    );

                    /*
 * Import_Manager перехватывает исключение
 * и возвращает его как errors => 1.
 *
 * Поэтому здесь отдельно сохраняем
 * подробную информацию об ошибке.
 */
                    if (
                        !empty($single_result['items'])
                        && is_array($single_result['items'])
                    ) {
                        foreach ($single_result['items'] as $item) {

                            if (
                                isset($item['action'])
                                && $item['action'] === 'error'
                            ) {
                                $this->save_product_error(
                                    $row,
                                    $product,
                                    new \RuntimeException(
                                        isset($item['message'])
                                            ? $item['message']
                                            : 'Неизвестная ошибка импорта.'
                                    )
                                );
                            }
                        }
                    }

                    $this->update_progress(
                        $single_result
                    );
                } catch (\Throwable $e) {

                    Logger::info(
                        'Ошибка импорта товара: ' . $e->getMessage()
                    );

                    /*
                     * Сохраняем ошибку с ТОЧНОЙ строкой CSV.
                     */
                    $this->save_product_error(
                        $row,
                        $product,
                        $e
                    );

                    $this->session
                        ->get_progress()
                        ->increment_errors();
                }
            }
        }

        /*
         * Смещаем offset на количество реально прочитанных строк.
         */
        $this->session->set_offset(
            $this->session->get_offset() + count($rows)
        );

        /*
         * Проверяем завершение импорта.
         */
        if (
            $this->session->get_offset()
            >= $this->session->get_total()
        ) {
            $this->session->complete();
        }

        /*
         * Сохраняем прогресс.
         */
        if ($this->session->get_import_id()) {
            $this->repository->update(
                $this->session
            );
        }

        $chunk_time = microtime(true) - $chunk_start;

        Logger::info(
            'CHUNK DEBUG: END',
            [
                'import_id' => $this->session->get_import_id(),
                'offset'    => $this->session->get_offset(),
                'rows'      => count($rows),
                'time'      => round($chunk_time, 2),
                'status'    => $this->session->get_status(),
            ]
        );
        return $this->get_result();
    }

    /**
     * Сохраняет ошибку маппинга.
     */
    private function save_error(
        $row,
        \Throwable $e,
        $error_type = 'mapping'
    ) {
        $import_id = $this->session->get_import_id();

        if (!$import_id) {
            return;
        }

        $data = isset($row['data']) && is_array($row['data'])
            ? $row['data']
            : [];

        $sku = $this->get_sku_from_row($data);

        $this->error_repository->add(
            $import_id,
            isset($row['row_number'])
                ? (int) $row['row_number']
                : 0,
            $sku,
            $error_type,
            $e->getMessage(),
            $data
        );
    }

    /**
     * Сохраняет ошибку непосредственно при импорте товара.
     */
    private function save_product_error(
        $row,
        $product,
        \Throwable $e
    ) {
        $import_id = $this->session->get_import_id();

        if (!$import_id) {
            return;
        }

        $data = isset($row['data']) && is_array($row['data'])
            ? $row['data']
            : [];

        $sku = $this->get_sku_from_row($data);

        /*
         * Если SKU удалось получить из объекта товара,
         * используем его как дополнительный источник.
         */
        if (
            !$sku
            && is_object($product)
            && method_exists($product, 'get_sku')
        ) {
            $sku = (string) $product->get_sku();
        }

        $this->error_repository->add(
            $import_id,
            isset($row['row_number'])
                ? (int) $row['row_number']
                : 0,
            $sku,
            'product',
            $e->getMessage(),
            $data
        );
    }

    /**
     * Пытается найти SKU в исходной строке CSV.
     */
    private function get_sku_from_row($data)
    {
        if (!is_array($data)) {
            return '';
        }

        $possible_keys = [
            'Артикул',
            'Артикул поставщика',
            'Код',
            'Код товара',
            'КодНоменклатуры',
            'vendorCode',
            'VendorCode',
            'sku',
            'SKU',
        ];

        foreach ($possible_keys as $key) {

            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = trim(
                (string) $data[$key]
            );

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Обновляет статистику по результату импорта товара.
     */
    private function update_progress($result)
    {
        $progress = $this->session->get_progress();

        for (
            $i = 0;
            $i < (int) $result['created'];
            $i++
        ) {
            $progress->increment_created();
        }

        for (
            $i = 0;
            $i < (int) $result['updated'];
            $i++
        ) {
            $progress->increment_updated();
        }

        for (
            $i = 0;
            $i < (int) $result['skipped'];
            $i++
        ) {
            $progress->increment_skipped();
        }

        for (
            $i = 0;
            $i < (int) $result['errors'];
            $i++
        ) {
            $progress->increment_errors();
        }
    }

    /**
     * Формирует результат AJAX-запроса.
     */
    private function get_result()
    {
        return [
            'session' => $this->session->to_array(),
            'progress' => $this->session
                ->get_progress()
                ->to_array(),
        ];
    }
}
