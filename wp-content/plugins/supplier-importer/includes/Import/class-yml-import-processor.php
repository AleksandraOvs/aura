<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Feed_Mapper;
use Supplier_Importer\Core\Logger;
use Supplier_Importer\Product\Product_Finder;

class Yml_Import_Processor
{
    const BATCH_SIZE = 3;

    private $session;
    private $mapper;
    private $reader;
    private $product_importer;
    private $product_finder;
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
        $this->product_finder = new Product_Finder();

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

        try {
            $this->session->start();
            Logger::info(
                'Начата обработка порции YML.',
                [
                    'import_id'  => $this->session->get_import_id(),
                    'phase'      => $this->session->get_phase(),
                    'cursor'     => $this->session->get_cursor(),
                    'batch_size' => $batch_size,
                ]
            );

            $batch = $this->reader->read_batch(
                $this->session->get_staging_file(),
                $this->session->get_cursor(),
                $batch_size
            );

            $items = $batch['items'];

            $processed_before_batch = $this->session
                ->get_progress()
                ->get_processed();

            foreach ($items as $index => $item) {
                $row_number = $processed_before_batch + $index + 1;

                $this->process_record(
                    $item,
                    $row_number
                );
            }

            Logger::info(
                'Получена порция записей из JSONL.',
                [
                    'import_id'    => $this->session->get_import_id(),
                    'cursor_before' => $this->session->get_cursor(),
                    'cursor_after'  => $batch['cursor'],
                    'items_count'   => count($items),
                    'finished'      => $batch['finished'],
                    'total'         => $this->session->get_total(),
                ]
            );

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

                $processed = $progress->get_processed();
                $total = $this->session->get_total();

                if (
                    $this->session->get_sync_unprotected_errors() > 0
                    || $total <= 0
                    || $processed !== $total
                ) {
                    $this->session->fail();

                    Logger::warning(
                        'YML-импорт не перешёл к синхронизации.',
                        [
                            'import_id'              => $this->session->get_import_id(),
                            'total'                  => $total,
                            'processed'              => $processed,
                            'sync_unprotected_errors' => $this->session->get_sync_unprotected_errors(),
                        ]
                    );
                } else {
                    $this->session->set_phase('sync');

                    Logger::info(
                        'Обработка товаров завершена. Начинается синхронизация каталога.',
                        [
                            'import_id' => $this->session->get_import_id(),
                            'total'     => $total,
                            'processed' => $processed,
                        ]
                    );
                }
            }

            $this->repository->update(
                $this->session
            );

            if ($this->session->is_finished()) {
                Staging_Cleaner::clean($this->session);
            }
        } catch (\Throwable $e) {
            $this->session->fail();
            Logger::error(
                'Критическая ошибка пакетного YML-импорта.',
                [
                    'import_id' => $this->session->get_import_id(),
                    'phase'     => $this->session->get_phase(),
                    'message'   => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]
            );

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

                return $this->get_result([
                    'fatal_error' => $e->getMessage(),
                ]);
            }

            Staging_Cleaner::clean($this->session);

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
     * Обрабатывает одну запись и гарантированно учитывает её в прогрессе.
     */
    private function process_record(array $record, $row_number)
    {
        try {
            $this->process_record_internal(
                $record,
                $row_number
            );
        } finally {
            $this->session
                ->get_progress()
                ->increment_processed();
        }
    }

    /**
     * Обработать одну исходную запись.
     */
    private function process_record_internal(array $record, $row_number)
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
            if (
                in_array(
                    $product_data->get_supplier(),
                    ['citilux', 'lightstar'],
                    true
                )
            ) {
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
            $this->session->increment_sync_unprotected_errors();
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

            Logger::info(
                'Товар успешно обработан.',
                [
                    'import_id'   => $this->session->get_import_id(),
                    'action'      => $action,
                    'product_id'  => $product->get_id(),
                    'supplier'    => $product_data->get_supplier(),
                    'supplier_id' => $product_data->get_supplier_id(),
                    'sku'         => $sku,
                ]
            );
        } catch (\Throwable $e) {
            $this->save_error(
                $row_number,
                $record,
                $sku,
                'product',
                $e
            );

            /*
     * Пытаемся подтвердить существующий товар
     * только по точной паре поставщик + ID поставщика.
     *
     * Совпадения SKU или штрихкода недостаточно.
     */
            try {
                $existing_product =
                    $this->product_finder->find_confirmed_supplier_product(
                        $product_data->get_supplier(),
                        $product_data->get_supplier_id()
                    );

                if ($existing_product instanceof \WC_Product) {
                    $this->session->add_sync_protected_id(
                        $existing_product->get_id()
                    );

                    Logger::warning(
                        'Товар защищён от снятия с публикации из-за ошибки импорта.',
                        [
                            'import_id'   => $this->session->get_import_id(),
                            'product_id'  => $existing_product->get_id(),
                            'supplier'    => $product_data->get_supplier(),
                            'supplier_id' => $product_data->get_supplier_id(),
                        ]
                    );
                } else {
                    $this->session->increment_sync_unprotected_errors();
                }
            } catch (\Throwable $protection_error) {
                $this->session->increment_sync_unprotected_errors();

                Logger::error(
                    'Не удалось защитить товар от синхронизации после ошибки импорта.',
                    [
                        'import_id' => $this->session->get_import_id(),
                        'supplier'  => $product_data->get_supplier(),
                        'message'   => $protection_error->getMessage(),
                    ]
                );
            }

            $this->session
                ->get_progress()
                ->increment_errors();
        }
    }

    private function save_error(
        $row_number,
        array $record,
        $sku,
        $error_type,
        \Throwable $e
    ) {
        $error_id = $this->error_repository->add(
            $this->session->get_import_id(),
            $row_number,
            $sku,
            $error_type,
            $e->getMessage(),
            $record
        );

        if (!$error_id) {
            Logger::error(
                'Не удалось сохранить подробности ошибки импорта.',
                [
                    'import_id' => $this->session->get_import_id(),
                    'row'       => $row_number,
                    'sku'       => $sku,
                    'type'      => $error_type,
                    'message'   => $e->getMessage(),
                ]
            );
        }

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
