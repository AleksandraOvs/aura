<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Core\Plugin;
use Supplier_Importer\Core\Logger;
use Supplier_Importer\Import\Import_Manager;
use Supplier_Importer\Import\Import_Repository;
use Supplier_Importer\Import\Xml_Reader;
use Supplier_Importer\Import\Yml_Validator;
use Supplier_Importer\Product\Product_Meta;

class Feed_Sync
{
    /**
     * Запускает полный цикл импорта по URL.
     *
     * Запись истории создаётся до скачивания,
     * поэтому ошибки загрузки тоже фиксируются.
     *
     * @param string $supplier_id ID поставщика.
     * @param array  $settings    Настройки фида.
     * @param string $source      Источник запуска.
     *
     * @return array
     */
    public function run_from_url(
        $supplier_id,
        $settings,
        $source = 'cron'
    ) {
        $supplier_id = sanitize_key($supplier_id);

        $source = $source === 'cron'
            ? 'cron'
            : 'manual';

        $repository = new Import_Repository();

        $file_label = $this->get_file_label(
            '',
            $settings,
            $source,
            ''
        );

        $import_id = $repository->create_feed_import(
            $supplier_id,
            $file_label,
            $source === 'manual'
                ? get_current_user_id()
                : 0
        );

        $downloader = new Supplier_Feed_Downloader();
        $file = '';

        try {
            if (
                !is_array($settings)
                || empty($settings['url'])
            ) {
                throw new \RuntimeException(
                    'Не указан URL фида.'
                );
            }

            $file = $downloader->download(
                $settings['url']
            );

            if (is_wp_error($file)) {
                throw new \RuntimeException(
                    $file->get_error_message()
                );
            }

            return $this->process_feed_file(
                $supplier_id,
                $file,
                $settings,
                $source,
                $file_label,
                $import_id
            );
        } catch (\Throwable $e) {
            /*
             * Если файл не был получен,
             * сохраняем ошибку загрузки.
             */
            if (!$file || is_wp_error($file)) {
                try {
                    $repository->save_feed_errors(
                        $import_id,
                        [
                            [
                                'index'   => 0,
                                'sku'     => '',
                                'message' => $e->getMessage(),
                            ],
                        ]
                    );
                } catch (\Throwable $storage_error) {
                    Logger::error(
                        'Не удалось сохранить ошибку загрузки фида.',
                        [
                            'import_id' => $import_id,
                            'message'   => $storage_error->getMessage(),
                        ]
                    );
                }
            }

            /*
             * Если обработка неожиданно завершилась исключением
             * до записи итогового статуса, пытаемся закрыть историю.
             *
             * Если process_feed_file() уже завершил запись,
             * повторного обновления не будет.
             */
            $this->ensure_failed_import(
                $repository,
                $import_id
            );

            throw $e;
        } finally {
            if ($file && !is_wp_error($file)) {
                $downloader->delete_temp_file($file);
            }
        }
    }

    /**
     * Обрабатывает файл фида.
     *
     * Общая логика для автоматического и ручного импорта.
     *
     * @param string $supplier_id ID поставщика.
     * @param string $file        Путь к файлу.
     * @param array  $settings    Настройки фида.
     * @param string $source      Источник запуска.
     * @param string $file_label  Название файла для истории.
     * @param int    $import_id   ID существующей записи истории.
     *
     * @return array
     */
    public function process_feed_file(
        $supplier_id,
        $file,
        $settings,
        $source = 'manual',
        $file_label = '',
        $import_id = 0
    ) {
        $supplier_id = sanitize_key($supplier_id);

        $source = $source === 'cron'
            ? 'cron'
            : 'manual';

        $repository = new Import_Repository();

        $file_label = $this->get_file_label(
            $file,
            $settings,
            $source,
            $file_label
        );

        /*
         * Если запись не была создана ранее,
         * создаём её перед обработкой файла.
         */
        if (!$import_id) {
            $import_id = $repository->create_feed_import(
                $supplier_id,
                $file_label,
                $source === 'manual'
                    ? get_current_user_id()
                    : 0
            );
        }

        $result = [
            'total'   => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors'  => 0,
            'items'   => [],
        ];

        $failure_message = '';

        /*
         * Показывает, учтена ли критическая ошибка
         * в счётчике и списке ошибок.
         */
        $failure_counted = false;
        $failure_details_recorded = false;

        try {
            /*
             * Проверяем поставщика, формат и структуру фида.
             */
            $this->validate_feed(
                $supplier_id,
                $file,
                $settings
            );

            $format = strtolower(
                sanitize_key($settings['format'] ?? '')
            );

            /*
             * Создаём mapper поставщика.
             */
            $factory = new Feed_Mapper_Factory();

            $mapper = $factory->create(
                $supplier_id,
                $format,
                $file
            );

            $reader = new Xml_Reader(
                $file,
                'offer'
            );

            $import_manager = new Import_Manager();

            $run_id = wp_generate_uuid4();

            $product_meta = new Product_Meta();

            /*
             * Импортируем товары потоково.
             */
            $result = $import_manager->import_stream(
                $reader,
                [$mapper, 'map'],
                function (
                    $product,
                    $product_data,
                    $action
                ) use (
                    $run_id,
                    $supplier_id,
                    $product_meta
                ) {
                    if (!$product instanceof \WC_Product) {
                        throw new \RuntimeException(
                            'Импортёр вернул некорректный объект товара.'
                        );
                    }

                    if (
                        !$product_data instanceof \Supplier_Importer\Product\Product_Data
                    ) {
                        throw new \RuntimeException(
                            'Импортёр вернул некорректные данные товара.'
                        );
                    }

                    if (
                        $product_data->get_supplier() !== $supplier_id
                    ) {
                        throw new \RuntimeException(
                            'Поставщик товара не совпадает с поставщиком фида.'
                        );
                    }

                    $product_meta->set_feed_run(
                        $product,
                        $run_id
                    );

                    if (
                        $product_meta->get_feed_run($product) !== $run_id
                    ) {
                        throw new \RuntimeException(
                            sprintf(
                                'Не удалось сохранить метку запуска фида для товара ID %d.',
                                $product->get_id()
                            )
                        );
                    }
                }
            );

            /*
             * Критическая ошибка чтения или закрытия reader.
             */
            if (!empty($result['fatal_error'])) {
                throw new \RuntimeException(
                    'Критическая ошибка потокового импорта: '
                        . $result['fatal_error']
                );
            }

            /*
             * Записываем статистику импорта.
             */
            Logger::info(
                sprintf(
                    'FEED SYNC: supplier=%s, source=%s, import_id=%d, total=%d, created=%d, updated=%d, skipped=%d, errors=%d',
                    $supplier_id,
                    $source,
                    $import_id,
                    $result['total'],
                    $result['created'],
                    $result['updated'],
                    $result['skipped'],
                    $result['errors']
                )
            );

            /*
             * Пустой фид не считается успешным.
             */
            if ((int) $result['total'] === 0) {
                throw new \RuntimeException(
                    'Фид не содержит ни одной записи товара.'
                );
            }

            /*
             * При ошибках товаров не синхронизируем
             * отсутствующие товары.
             */
            if ((int) $result['errors'] > 0) {
                $failure_counted = true;
                $failure_details_recorded = true;

                throw new \RuntimeException(
                    sprintf(
                        'Импорт %s завершён с ошибками: %d из %d записей.',
                        $supplier_id,
                        $result['errors'],
                        $result['total']
                    )
                );
            }

            /*
             * Синхронизируем товары только после
             * полностью успешного импорта.
             */
            $sync = new Feed_Product_Sync();

            $sync_result = $sync->sync_missing_products(
                $supplier_id,
                $run_id
            );

            Logger::info(
                sprintf(
                    'FEED PRODUCT SYNC: supplier=%s, checked=%d, unpublished=%d, republished=%d',
                    $supplier_id,
                    $sync_result['checked'],
                    $sync_result['unpublished'],
                    $sync_result['republished']
                )
            );
        } catch (\Throwable $e) {
            $failure_message = $e->getMessage();

            /*
             * Критическая ошибка учитывается отдельно.
             * Ошибки отдельных товаров уже учитываются
             * в result['errors'].
             */
            if (!$failure_counted) {
                $result['errors'] = (int) ($result['errors'] ?? 0) + 1;
            }

            Logger::error(
                'Ошибка обработки фида.',
                [
                    'supplier'  => $supplier_id,
                    'source'    => $source,
                    'import_id' => $import_id,
                    'message'   => $failure_message,
                ]
            );
        }

        /*
         * Собираем ошибки отдельных товаров.
         */
        $errors = [];

        foreach ($result['items'] ?? [] as $item) {
            if (($item['action'] ?? '') !== 'error') {
                continue;
            }

            $errors[] = $item;

            Logger::error(
                'Ошибка импорта товара из фида.',
                [
                    'supplier' => $supplier_id,
                    'sku'      => $item['sku'] ?? '',
                    'index'    => $item['index'] ?? 0,
                    'message'  => $item['message'] ?? 'Неизвестная ошибка.',
                ]
            );
        }

        /*
         * Критическая ошибка сохраняется отдельно
         * от ошибок отдельных товаров.
         */
        if (
            $failure_message !== ''
            && !$failure_details_recorded
        ) {
            $errors[] = [
                'index'   => 0,
                'sku'     => '',
                'message' => $failure_message,
            ];
        }

        /*
         * Сохраняем ошибки в таблицу.
         */
        try {
            $repository->save_feed_errors(
                $import_id,
                $errors
            );
        } catch (\Throwable $e) {
            Logger::error(
                'Не удалось сохранить ошибки фидового импорта.',
                [
                    'import_id' => $import_id,
                    'message'   => $e->getMessage(),
                ]
            );

            if ($failure_message === '') {
                $failure_message = $e->getMessage();
                $result['errors'] = (int) $result['errors'] + 1;
            }
        }

        /*
         * Определяем итоговый статус.
         */
        $status = $failure_message === ''
            ? 'completed'
            : 'failed';

        /*
         * Сохраняем итоговую статистику.
         */
        try {
            $repository->finish_feed_import(
                $import_id,
                $result,
                $status
            );
        } catch (\Throwable $e) {
            /*
             * Если не удалось записать completed,
             * запуск уже нельзя считать успешным.
             *
             * Если не удалось записать failed,
             * также предпринимаем повторную попытку.
             */
            if ($failure_message === '') {
                $failure_message = $e->getMessage();
                $result['errors'] = (int) $result['errors'] + 1;
            }

            Logger::error(
                'Не удалось завершить запись фидового импорта.',
                [
                    'import_id' => $import_id,
                    'message'   => $e->getMessage(),
                ]
            );

            /*
             * Повторная попытка завершить историю
             * со статусом failed.
             */
            try {
                $repository->finish_feed_import(
                    $import_id,
                    $result,
                    'failed'
                );
            } catch (\Throwable $retry_error) {
                Logger::error(
                    'Повторная попытка завершить историю фидового импорта не удалась.',
                    [
                        'import_id' => $import_id,
                        'message'   => $retry_error->getMessage(),
                    ]
                );
            }
        }

        /*
         * Передаём ошибку вызывающему обработчику.
         */
        if ($failure_message !== '') {
            throw new \RuntimeException(
                $failure_message
            );
        }

        return [
            'import_id' => $import_id,
            'status'    => $status,
            'result'    => $result,
        ];
    }

    /**
     * Пытается завершить незакрытую запись истории
     * со статусом failed.
     *
     * Существующие счётчики сохраняются.
     *
     * @param Import_Repository $repository Репозиторий.
     * @param int               $import_id  ID импорта.
     *
     * @return void
     */
    private function ensure_failed_import(
        $repository,
        $import_id
    ) {
        try {
            $import = $repository->get($import_id);

            if (!$import) {
                return;
            }

            /*
             * Не изменяем уже завершённую запись.
             */
            if (
                in_array(
                    $import['status'],
                    ['completed', 'failed'],
                    true
                )
            ) {
                return;
            }

            $result = [
                'total'   => (int) ($import['total'] ?? 0),
                'created' => (int) ($import['created'] ?? 0),
                'updated' => (int) ($import['updated'] ?? 0),
                'skipped' => (int) ($import['skipped'] ?? 0),
                'errors'  => max(
                    1,
                    (int) ($import['errors'] ?? 0)
                ),
            ];

            $repository->finish_feed_import(
                $import_id,
                $result,
                'failed'
            );
        } catch (\Throwable $e) {
            Logger::error(
                'Не удалось принудительно завершить запись фидового импорта.',
                [
                    'import_id' => $import_id,
                    'message'   => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Проверяет возможность обработки фида.
     *
     * Пока поддерживается только Maytoni YML.
     *
     * @param string $supplier_id ID поставщика.
     * @param string $file        Путь к файлу.
     * @param mixed  $settings    Настройки фида.
     *
     * @return void
     */
    private function validate_feed(
        $supplier_id,
        $file,
        $settings
    ) {
        if ($supplier_id === '') {
            throw new \RuntimeException(
                'Не указан поставщик фида.'
            );
        }

        if (!is_file($file) || !is_readable($file)) {
            throw new \RuntimeException(
                'Файл фида недоступен для чтения.'
            );
        }

        if (!is_array($settings)) {
            throw new \InvalidArgumentException(
                'Настройки фида должны быть массивом.'
            );
        }

        $manager = Plugin::get_supplier_manager();

        if (!$manager || !$manager->has($supplier_id)) {
            throw new \RuntimeException(
                'Поставщик не зарегистрирован: ' . $supplier_id
            );
        }

        $format = strtolower(
            sanitize_key($settings['format'] ?? '')
        );

        /*
         * Пока поддерживается только Maytoni YML.
         */
        if (
            $supplier_id !== 'maytoni'
            || $format !== 'yml'
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Формат %s для поставщика %s пока не поддерживается.',
                    $format,
                    $supplier_id
                )
            );
        }

        /*
         * Проверяем структуру YML один раз.
         */
        $validator = new Yml_Validator();

        $validator->validate($file);
    }

    /**
     * Формирует название источника для истории импорта.
     *
     * Полный URL не сохраняется, чтобы не записывать
     * возможные токены и параметры авторизации.
     *
     * @param string $file       Путь к файлу.
     * @param mixed  $settings   Настройки.
     * @param string $source     Источник запуска.
     * @param string $file_label Название файла.
     *
     * @return string
     */
    private function get_file_label(
        $file,
        $settings,
        $source,
        $file_label
    ) {
        if ($file_label !== '') {
            $label = sanitize_file_name(
                wp_basename($file_label)
            );
        } elseif (
            is_array($settings)
            && !empty($settings['url'])
        ) {
            $path = wp_parse_url(
                $settings['url'],
                PHP_URL_PATH
            );

            $label = $path
                ? sanitize_file_name(wp_basename($path))
                : 'feed';
        } else {
            $label = sanitize_file_name(
                wp_basename($file)
            );
        }

        if ($label === '') {
            $label = 'feed';
        }

        return sprintf(
            '%s (%s)',
            $label,
            $source === 'cron'
                ? 'Cron'
                : 'ручной запуск'
        );
    }
}
