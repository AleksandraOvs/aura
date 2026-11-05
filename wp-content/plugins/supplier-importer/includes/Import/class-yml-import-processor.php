<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Feed_Mapper;
use Supplier_Importer\Core\Logger;

class Yml_Import_Processor
{
    const BATCH_SIZE = 3;

    private $session;
    private $mapper;
    private $reader;
    private $product_importer;
    private $repository;
    private $error_repository;
    private $on_success;
    private $attribute_mapper;

    public function __construct(
        Import_Session $session,
        Feed_Mapper $mapper,
        callable $on_success = null
    ) {
        if (!in_array($session->get_format(), ['yml', 'xml'], true)) {
            throw new \InvalidArgumentException(
                'Yml_Import_Processor поддерживает только YML и XML.'
            );
        }

        $this->session = $session;
        $this->mapper = $mapper;

        $this->reader = new Jsonl_Reader();
        $this->product_importer = new Product_Importer();

        $this->attribute_mapper = new Feed_Attribute_Mapper();

        $this->repository = new Import_Repository();
        $this->error_repository = new Import_Error_Repository();

        $this->on_success = $on_success;
    }

    /**
     * Обработать одну порцию YML-товаров.
     *
     * @param int|null $batch_size
     *
     * @return array
     */
    public function process($batch_size = null)
    {
        if ($this->session->is_finished()) {
            return $this->get_result();
        }

        $batch_size = max(
            1,
            (int) ($batch_size ?: self::BATCH_SIZE)
        );

        $chunk_start = microtime(true);

        $this->session->start();

        try {
            $batch = $this->reader->read_batch(
                $this->session->get_staging_file(),
                $this->session->get_cursor(),
                $batch_size
            );

            $items = $batch['items'];

            Logger::info(
                'YML JSONL DEBUG',
                [
                    'import_id' => $this->session->get_import_id(),
                    'cursor_before' => $this->session->get_cursor(),
                    'cursor_after' => $batch['cursor'],
                    'items_count' => count($items),
                    'finished' => $batch['finished'],
                    'total' => $this->session->get_total(),
                ]
            );


            foreach ($items as $record) {
                Logger::info(
                    'YML RECORD DEBUG',
                    [
                        'record' => $record,
                    ]
                );
                $row_number = $this->session
                    ->get_progress()
                    ->get_processed() + 1;

                $this->process_record(
                    $record,
                    $row_number
                );

                $this->session
                    ->get_progress()
                    ->increment_processed();
            }

            // Сохраняем новую позицию чтения JSONL.
            $this->session->set_cursor(
                $batch['cursor']
            );

            // Offset для YML — количество обработанных записей.
            $this->session->set_offset(
                $this->session->get_progress()->get_processed()
            );

            /*
             * Если staging-файл полностью прочитан,
             * переходим к следующей фазе.
             *
             * Сам импорт пока НЕ завершаем:
             * дальше должна выполняться синхронизация
             * отсутствующих товаров.
             */
            if ($batch['finished']) {
                $progress = $this->session->get_progress();

                $errors = $progress->get_errors();
                $processed = $progress->get_processed();
                $total = $this->session->get_total();

                if (
                    $errors > 0
                    || $total <= 0
                    || $processed !== $total
                ) {
                    $this->session->fail();
                } else {
                    $this->session->set_phase('sync');
                }
            }

            $this->repository->update(
                $this->session
            );
        } catch (\Throwable $e) {
            $this->session->fail();

            try {
                $this->repository->update(
                    $this->session
                );
            } catch (\Throwable $storage_error) {
                Logger::error(
                    'Не удалось сохранить статус failed после ошибки YML-импорта.',
                    [
                        'import_id' => $this->session->get_import_id(),
                        'message'   => $storage_error->getMessage(),
                    ]
                );
            }

            return $this->get_result([
                'fatal_error' => $e->getMessage(),
            ]);
        }

        Logger::info(
            'YML BATCH: END',
            [
                'import_id' => $this->session->get_import_id(),
                'processed' => $this->session
                    ->get_progress()
                    ->get_processed(),
                'cursor'    => $this->session->get_cursor(),
                'phase'     => $this->session->get_phase(),
                'time'      => round(
                    microtime(true) - $chunk_start,
                    2
                ),
            ]
        );

        return $this->get_result();
    }

    /**
     * Обработать одну исходную запись.
     */
    private function process_record(array $record, $row_number)
    {
        $product_data = null;

        try {
            /*
             * Преобразуем исходный offer в Product_Data.
             */
            $product_data = $this->mapper->map($record);

            if (
                !$product_data instanceof \Supplier_Importer\Product\Product_Data
            ) {
                throw new \UnexpectedValueException(
                    'YML-маппер не вернул Product_Data.'
                );
            }

            /*
 * Преобразуем исходные параметры фида
 * в глобальные атрибуты WooCommerce.
 */
            if ($product_data->get_supplier() === 'citilux') {
                $product_attributes = $this->attribute_mapper->map(
                    $product_data->get_attributes(),
                    $product_data->get_supplier()
                );

                $product_data = \Supplier_Importer\Product\Product_Data::from_array(
                    array_merge(
                        $product_data->to_array(),
                        [
                            'attributes' => $product_attributes,
                        ]
                    )
                );
            }
        } catch (\Throwable $e) {
            $this->save_error(
                $row_number,
                $record,
                '',
                'mapping',
                $e
            );

            $this->session
                ->get_progress()
                ->increment_errors();

            return;
        }

        $sku = $product_data->get_sku();

        try {
            /*
             * Используем существующий Product_Importer.
             * Логика создания и обновления товаров не дублируется.
             */
            $result = $this->product_importer->import(
                $product_data
            );

            if (
                !is_array($result)
                || !isset($result['action'])
                || !isset($result['product'])
                || !is_object($result['product'])
            ) {
                throw new \UnexpectedValueException(
                    'Product_Importer вернул некорректный результат.'
                );
            }

            $action = $result['action'];
            $product = $result['product'];

            if (
                !in_array(
                    $action,
                    ['created', 'updated', 'skipped'],
                    true
                )
            ) {
                throw new \UnexpectedValueException(
                    'Неизвестное действие Product_Importer.'
                );
            }

            /*
 * Дополнительные действия после импорта.
 *
 * Callback устанавливает _supplier_feed_run
 * для последующей синхронизации товаров.
 */
            if ($this->on_success !== null) {
                call_user_func(
                    $this->on_success,
                    $product,
                    $product_data,
                    $action
                );
            }

            $progress = $this->session->get_progress();

            if ($action === 'created') {
                $progress->increment_created();
            } elseif ($action === 'updated') {
                $progress->increment_updated();
            } else {
                $progress->increment_skipped();
            }
        } catch (\Throwable $e) {
            $this->save_error(
                $row_number,
                $record,
                $sku,
                'product',
                $e
            );

            $this->session
                ->get_progress()
                ->increment_errors();
        }
    }

    /**
     * Сохранить ошибку.
     */
    private function save_error(
        $row_number,
        array $record,
        $sku,
        $error_type,
        \Throwable $e
    ) {
        $this->error_repository->add(
            $this->session->get_import_id(),
            $row_number,
            $sku,
            $error_type,
            $e->getMessage(),
            $record
        );

        Logger::error(
            'Ошибка обработки товара YML.',
            [
                'import_id' => $this->session->get_import_id(),
                'row'       => $row_number,
                'sku'       => $sku,
                'type'      => $error_type,
                'message'   => $e->getMessage(),
            ]
        );
    }

    /**
     * Результат для AJAX.
     */
    private function get_result($extra = [])
    {
        return array_merge(
            [
                'session' => $this->session->to_array(),
                'progress' => $this->session
                    ->get_progress()
                    ->to_array(),
                'phase' => $this->session->get_phase(),
            ],
            $extra
        );
    }
}
