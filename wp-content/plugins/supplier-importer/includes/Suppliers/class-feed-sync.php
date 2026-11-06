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
use Supplier_Importer\Import\Import_Session;
use Supplier_Importer\Import\Jsonl_Stager;
use Supplier_Importer\Import\Yml_Category_Reader;
use Supplier_Importer\Import\Yml_Category_Storage;
use Supplier_Importer\Import\Yml_Import_Processor;
use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Import\Mapping_Discovery_Repository;
use Supplier_Importer\Import\Yml_Analyzer;
use Supplier_Importer\Suppliers\Citilux\Citilux_Yml_Category_Reader;
use Supplier_Importer\Suppliers\Citilux\Citilux_Xml_Normalizer;
use Supplier_Importer\Import\Staging_Cleaner;

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

        $normalized_file = '';

        try {

            /*
 * Для CITILUX удаляем HTML-теги из description
 * во временной копии XML-файла.
 */
            $normalized_file = $this->prepare_supplier_file(
                $supplier_id,
                $file
            );

            if ($normalized_file !== '') {
                $file = $normalized_file;
            }
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
 * Анализируем YML и сохраняем обнаруженные значения.
 */
            $discovery = null;

            if (in_array($format, ['yml', 'xml'], true)) {
                $discovery = $this->analyze_and_save_discovery(
                    $supplier_id,
                    $file
                );
            }

            /*
 * Создаём mapper поставщика.
 *
 * Передаём уже прочитанный справочник категорий,
 * чтобы фабрика не читала его повторно.
 */
            $factory = new Feed_Mapper_Factory();

            $mapper = $factory->create(
                $supplier_id,
                $format,
                $file,
                $discovery['categories'] ?? null
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
 * Удаляем временный нормализованный файл.
 */
        $this->delete_normalized_file(
            $normalized_file
        );

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

        $supported_formats = [
            'maytoni' => 'yml',
            'citilux' => 'xml',
            'lightstar' => 'yml',
            'crystal_lux' => 'yml',
        ];


        Logger::info(
            'Проверка формата фида.',
            [
                'supplier_id'       => $supplier_id,
                'settings_format'   => $settings['format'] ?? '(пусто)',
                'normalized_format' => $format,
                'supported_format'  => $supported_formats[$supplier_id] ?? '(не задан)',
            ]
        );

        if (
            !isset($supported_formats[$supplier_id])
            || $supported_formats[$supplier_id] !== $format
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Формат %s для поставщика %s не поддерживается.',
                    $format ?: '(не указан)',
                    $supplier_id
                )
            );
        }


        /*
 * Поддерживаемые фиды используют структуру YML:
 * yml_catalog → shop → offers → offer.
 *
 * CITILUX предварительно нормализуется в эту структуру.
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

    /**
     * Скачать YML-фид и подготовить его к пакетному импорту.
     *
     * @param string $supplier_id ID поставщика.
     * @param array  $settings    Настройки фида.
     * @param string $source      Источник запуска.
     *
     * @return array
     */
    public function prepare_yml_import_from_url(
        $supplier_id,
        $settings,
        $source = 'manual'
    ) {
        if (
            !is_array($settings)
            || empty($settings['url'])
        ) {
            throw new \RuntimeException(
                'Не указан URL фида.'
            );
        }

        $downloader = new Supplier_Feed_Downloader();
        $file = '';

        try {
            $file = $downloader->download(
                $settings['url']
            );

            if (is_wp_error($file)) {
                throw new \RuntimeException(
                    $file->get_error_message()
                );
            }

            return $this->prepare_yml_import(
                $supplier_id,
                $file,
                $settings,
                $source
            );
        } finally {
            if ($file && !is_wp_error($file)) {
                $downloader->delete_temp_file($file);
            }
        }
    }

    /**
     * Подготовить YML-фид к пакетному импорту.
     *
     * Категории читаются один раз.
     * Товары преобразуются в JSONL.
     * Создаётся одна сессия на весь запуск.
     *
     * @param string $supplier_id ID поставщика.
     * @param string $file        Путь к исходному YML.
     * @param array  $settings    Настройки фида.
     * @param string $source      Источник запуска.
     * @param string $file_label  Название файла.
     *
     * @return array
     */
    public function prepare_yml_import(
        $supplier_id,
        $file,
        $settings,
        $source = 'manual',
        $file_label = ''
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

        $staging_file = '';
        $categories_file = '';

        $normalized_file = '';

        $import_id = 0;

        try {
            /*
 * Нормализуем только фид CITILUX.
 */
            $normalized_file = $this->prepare_supplier_file(
                $supplier_id,
                $file
            );

            if ($normalized_file !== '') {
                $file = $normalized_file;
            }
            /*
         * Проверяем исходный фид.
         */
            $this->validate_feed(
                $supplier_id,
                $file,
                $settings
            );

            /*
         * Создаём каталог временных файлов.
         */
            $upload_dir = wp_upload_dir();

            if (!empty($upload_dir['error'])) {
                throw new \RuntimeException(
                    $upload_dir['error']
                );
            }

            $import_dir = trailingslashit(
                $upload_dir['basedir']
            ) . 'supplier-importer';

            if (
                !is_dir($import_dir)
                && !wp_mkdir_p($import_dir)
            ) {
                throw new \RuntimeException(
                    'Не удалось создать каталог импорта.'
                );
            }

            /*
         * Генерируем единый идентификатор запуска.
         */
            $run_id = wp_generate_uuid4();

            $file_prefix = sanitize_file_name($run_id);

            $staging_file = trailingslashit($import_dir)
                . $file_prefix . '.jsonl';

            $categories_file = $staging_file
                . '.categories.json';

            $discovery = $this->analyze_and_save_discovery(
                $supplier_id,
                $file
            );

            $categories = $discovery['categories'];
            $analysis = $discovery['analysis'];

            $category_storage = new Yml_Category_Storage();

            $category_storage->save(
                $categories_file,
                $categories
            );

            /*
         * Преобразуем товары в JSONL.
         */
            $stager = new Jsonl_Stager();

            $total = $stager->stage(
                $file,
                $staging_file
            );

            if ($total <= 0) {
                throw new \RuntimeException(
                    'Фид не содержит ни одной записи товара.'
                );
            }

            /*
         * Создаём сессию пакетного импорта.
         */
            $session = new Import_Session(
                $supplier_id,
                $file_label,
                $total
            );

            $session->set_run_id($run_id);
            $format = strtolower(
                sanitize_key($settings['format'] ?? '')
            );

            $session->set_format($format);
            $session->set_source($source);
            $session->set_phase('products');
            $session->set_staging_file($staging_file);

            $session->start();

            /*
         * Создаём запись истории.
         */
            $import_id = $repository->create($session);

            if (!$import_id) {
                throw new \RuntimeException(
                    'Не удалось создать сессию импорта.'
                );
            }

            Logger::info(
                sprintf(
                    'YML IMPORT PREPARED: supplier=%s, source=%s, import_id=%d, run_id=%s, total=%d',
                    $supplier_id,
                    $source,
                    $import_id,
                    $run_id,
                    $total
                )
            );

            $this->delete_normalized_file(
                $normalized_file
            );

            return [
                'import_id' => $import_id,
                'run_id'    => $run_id,
                'status'    => $session->get_status(),
                'phase'     => $session->get_phase(),
                'total'     => $total,
                'analysis' => $analysis,
            ];
        } catch (\Throwable $e) {
            /*
 * Если сессия уже создана, но подготовка завершилась
 * исключением, закрываем её со статусом failed.
 */
            $session_closed = !$import_id;

            if ($import_id) {
                try {
                    $failed_session = $repository->load_session($import_id);

                    if (
                        $failed_session instanceof Import_Session
                        && !$failed_session->is_finished()
                    ) {
                        $failed_session->fail();
                        $repository->update($failed_session);
                    }

                    $session_closed = (
                        $failed_session instanceof Import_Session
                        && $failed_session->is_finished()
                    );
                } catch (\Throwable $storage_error) {
                    Logger::error(
                        'Не удалось закрыть сессию после ошибки подготовки YML.',
                        [
                            'import_id' => $import_id,
                            'message'   => $storage_error->getMessage(),
                        ]
                    );
                }
            }
            /*
         * Удаляем временные файлы,
         * если подготовка не завершилась.
         */
            if ($session_closed) {
                if ($staging_file && is_file($staging_file)) {
                    @unlink($staging_file);
                }

                if ($categories_file && is_file($categories_file)) {
                    @unlink($categories_file);
                }
            }

            Logger::error(
                'Ошибка подготовки YML-импорта.',
                [
                    'supplier' => $supplier_id,
                    'source'   => $source,
                    'message'  => $e->getMessage(),
                ]
            );

            throw $e;
        } finally {
            $this->delete_normalized_file(
                $normalized_file
            );
        }
    }

    /**
     * Обработать очередной пакет YML-товаров.
     *
     * @param int $import_id  ID импорта.
     * @param int $batch_size Размер пакета.
     *
     * @return array
     */
    public function process_yml_batch(
        $import_id,
        $batch_size = 3
    ) {
        $repository = new Import_Repository();


        $session = $repository->load_session($import_id);

        if (!$session instanceof Import_Session) {
            throw new \RuntimeException(
                'Сессия импорта не найдена.'
            );
        }

        $format = $session->get_format();

        if (!in_array($format, ['yml', 'xml'], true)) {
            throw new \InvalidArgumentException(
                'Сессия имеет неподдерживаемый формат.'
            );
        }

        if ($session->get_phase() !== 'products') {
            throw new \RuntimeException(
                'Сессия не находится на этапе импорта товаров.'
            );
        }

        if (
            !in_array(
                $session->get_status(),
                ['pending', 'running'],
                true
            )
        ) {
            throw new \RuntimeException(
                'Сессия не находится в активном состоянии.'
            );
        }

        /*
     * Восстанавливаем справочник категорий.
     */
        $staging_file = $session->get_staging_file();

        $categories_file = $staging_file
            . '.categories.json';

        $category_storage = new Yml_Category_Storage();

        $categories = $category_storage->load(
            $categories_file
        );

        /*
     * Создаём mapper без повторного чтения YML.
     *
     * В качестве file_path передаём staging-файл:
     * фабрике достаточно проверить его доступность,
     * поскольку категории уже переданы.
     */
        $factory = new Feed_Mapper_Factory();

        $mapper = $factory->create(
            $session->get_supplier(),
            $format,
            $staging_file,
            $categories
        );

        /*
     * Callback сохраняет метку текущего запуска
     * на успешно импортированном товаре.
     */
        $run_id = $session->get_run_id();
        $supplier_id = $session->get_supplier();

        $product_meta = new Product_Meta();

        $on_success = function (
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

            if (!$product_data instanceof Product_Data) {
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
        };

        /*
     * Запускаем обработку пакета.
     */
        $processor = new Yml_Import_Processor(
            $session,
            $mapper,
            $on_success
        );

        return $processor->process(
            max(1, absint($batch_size))
        );
    }

    /**
     * Обработать очередную порцию синхронизации товаров.
     *
     * @param int $import_id  ID импорта.
     * @param int $batch_size Размер порции.
     *
     * @return array
     */
    public function process_yml_sync_batch(
        $import_id,
        $batch_size = 100
    ) {
        $repository = new Import_Repository();

        $session = $repository->load_session($import_id);

        if (!$session instanceof Import_Session) {
            throw new \RuntimeException(
                'Сессия импорта не найдена.'
            );
        }

        if (!in_array($session->get_format(), ['yml', 'xml'], true)) {
            throw new \InvalidArgumentException(
                'Сессия имеет неподдерживаемый формат.'
            );
        }

        if ($session->get_phase() !== 'sync') {
            throw new \RuntimeException(
                'Сессия не находится на этапе синхронизации.'
            );
        }

        if (
            !in_array(
                $session->get_status(),
                ['pending', 'running'],
                true
            )
        ) {
            throw new \RuntimeException(
                'Сессия не находится в активном состоянии.'
            );
        }

        /*
 * Дополнительная защита:
 * синхронизация запрещена, если были ошибки импорта товаров.
 */
        $progress = $session->get_progress();

        if (
            $progress->get_errors() > 0
            || $session->get_total() <= 0
            || $progress->get_processed() !== $session->get_total()
        ) {
            $session->fail();

            try {
                $repository->update($session);
            } catch (\Throwable $storage_error) {
                Logger::error(
                    'Не удалось сохранить статус failed: импорт товаров обработан не полностью.',
                    [
                        'import_id' => $session->get_import_id(),
                        'message'   => $storage_error->getMessage(),
                    ]
                );

                throw $storage_error;
            }

            Staging_Cleaner::clean($session);

            throw new \RuntimeException(
                'Синхронизация отменена: импорт товаров завершился с ошибками или обработан не полностью.'
            );
        }

        $session->start();

        try {
            $sync = new Feed_Product_Sync();

            $batch = $sync->sync_missing_products_batch(
                $session->get_supplier(),
                $session->get_run_id(),
                $session->get_sync_cursor(),
                max(1, absint($batch_size))
            );

            if (!is_array($batch)) {
                throw new \RuntimeException(
                    'Метод синхронизации вернул некорректный результат.'
                );
            }

            $required_keys = [
                'last_product_id',
                'checked',
                'unpublished',
                'republished',
                'finished',
            ];

            foreach ($required_keys as $key) {
                if (!array_key_exists($key, $batch)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Результат синхронизации не содержит поле "%s".',
                            $key
                        )
                    );
                }
            }

            if (
                !is_numeric($batch['last_product_id'])
                || !is_numeric($batch['checked'])
                || !is_numeric($batch['unpublished'])
                || !is_numeric($batch['republished'])
                || !is_bool($batch['finished'])
            ) {
                throw new \RuntimeException(
                    'Метод синхронизации вернул некорректные данные.'
                );
            }

            if (
                (float) $batch['last_product_id'] < 0
                || (float) $batch['checked'] < 0
                || (float) $batch['unpublished'] < 0
                || (float) $batch['republished'] < 0
                || (float) $batch['last_product_id'] != (int) $batch['last_product_id']
                || (float) $batch['checked'] != (int) $batch['checked']
                || (float) $batch['unpublished'] != (int) $batch['unpublished']
                || (float) $batch['republished'] != (int) $batch['republished']
            ) {
                throw new \RuntimeException(
                    'Метод синхронизации вернул некорректные числовые значения.'
                );
            }

            if (
                (int) $batch['last_product_id']
                < (int) $session->get_sync_cursor()
            ) {
                throw new \RuntimeException(
                    'Курсор синхронизации не может уменьшаться.'
                );
            }

            /*
         * Сохраняем курсор и накопленную статистику.
         */
            $session->set_sync_cursor(
                $batch['last_product_id']
            );

            $session->increment_sync_checked(
                $batch['checked']
            );

            $session->increment_sync_unpublished(
                $batch['unpublished']
            );

            $session->increment_sync_republished(
                $batch['republished']
            );

            /*
         * Синхронизация закончена — завершаем импорт.
         */
            if ($batch['finished']) {
                $session->complete();
            }

            $repository->update($session);

            /*
 * Удаляем временные файлы после полного завершения импорта.
 */
            if (
                $batch['finished']
                && $session->get_status() === 'completed'
            ) {
                Staging_Cleaner::clean($session);
            }

            Logger::info(
                'YML SYNC BATCH: END',
                [
                    'import_id'   => $session->get_import_id(),
                    'cursor'      => $session->get_sync_cursor(),
                    'checked'     => $session->get_sync_checked(),
                    'unpublished' => $session->get_sync_unpublished(),
                    'republished' => $session->get_sync_republished(),
                    'finished'    => $batch['finished'],
                ]
            );

            return [
                'session' => $session->to_array(),
                'phase'   => $session->get_phase(),
                'sync'    => [
                    'checked'     => $session->get_sync_checked(),
                    'unpublished' => $session->get_sync_unpublished(),
                    'republished' => $session->get_sync_republished(),
                    'last_product_id' => $session->get_sync_cursor(),
                    'finished'    => $batch['finished'],
                ],
            ];
        } catch (\Throwable $e) {
            $session->fail();

            $session_saved = false;

            try {
                $repository->update($session);
                $session_saved = true;
            } catch (\Throwable $storage_error) {
                Logger::error(
                    'Не удалось сохранить статус failed после ошибки синхронизации.',
                    [
                        'import_id' => $session->get_import_id(),
                        'message'   => $storage_error->getMessage(),
                    ]
                );
            }

            if ($session_saved) {
                Staging_Cleaner::clean($session);
            }

            Logger::error(
                'Ошибка пакетной синхронизации товаров.',
                [
                    'import_id' => $session->get_import_id(),
                    'message'   => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }


    /**
     * Анализирует фид и сохраняет обнаруженные категории и атрибуты.
     *
     * @param string $supplier_id ID поставщика.
     * @param string $file        Путь к файлу.
     *
     * @return array
     */
    private function analyze_and_save_discovery(
        $supplier_id,
        $file
    ) {
        if ($supplier_id === 'citilux') {
            $category_reader = new Citilux_Yml_Category_Reader();
        } else {
            $category_reader = new Yml_Category_Reader();
        }

        $categories = $category_reader->read($file);

        $analyzer = new Yml_Analyzer();

        $analysis = $analyzer->analyze(
            $file,
            $categories
        );

        $discovery_repository = new Mapping_Discovery_Repository();

        $discovery_repository->save(
            $supplier_id,
            $analysis
        );

        return [
            'categories' => $categories,
            'analysis'   => $analysis,
        ];
    }


    /**
     * Подготовить файл поставщика к обработке.
     *
     * Нормализация применяется только к CITILUX.
     *
     * @param string $supplier_id
     * @param string $file
     *
     * @return string Путь к временному файлу или исходному файлу.
     */
    private function prepare_supplier_file($supplier_id, $file)
    {
        if ($supplier_id !== 'citilux') {
            return '';
        }

        $normalizer = new Citilux_Xml_Normalizer();

        return $normalizer->normalize($file);
    }

    /**
     * Удалить временный нормализованный файл.
     *
     * @param string $file
     *
     * @return void
     */
    private function delete_normalized_file($file)
    {
        if (
            $file
            && is_file($file)
            && !@unlink($file)
        ) {
            Logger::warning(
                'Не удалось удалить временный нормализованный XML-файл.',
                [
                    'file' => $file,
                ]
            );
        }
    }
}
