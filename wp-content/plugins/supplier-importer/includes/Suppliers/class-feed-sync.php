<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Core\Plugin;
use Supplier_Importer\Import\Import_Manager;
use Supplier_Importer\Import\Xml_Reader;
use Supplier_Importer\Import\Yml_Validator;
use Supplier_Importer\Product\Product_Meta;

class Feed_Sync
{
    public function __construct()
    {
        add_action(
            'supplier_importer_process_downloaded_feed',
            [$this, 'process_downloaded_feed'],
            10,
            3
        );
    }

    /**
     * Обрабатывает скачанный фид поставщика.
     *
     * @param string $supplier_id ID поставщика.
     * @param string $file        Путь к временному файлу.
     * @param array  $settings    Настройки фида.
     *
     * @return void
     */
    public function process_downloaded_feed(
        $supplier_id,
        $file,
        $settings
    ) {
        $supplier_id = sanitize_key($supplier_id);

        if (!is_file($file) || !is_readable($file)) {
            throw new \RuntimeException(
                'Скачанный файл фида недоступен для чтения.'
            );
        }

        $manager = Plugin::get_supplier_manager();

        if (!$manager || !$manager->has($supplier_id)) {
            throw new \RuntimeException(
                'Поставщик не зарегистрирован: ' . $supplier_id
            );
        }

        if (!is_array($settings)) {
            throw new \InvalidArgumentException(
                'Настройки фида должны быть массивом.'
            );
        }

        $format = strtolower(
            sanitize_key($settings['format'] ?? 'csv')
        );

        /*
         * Пока подключён только YML-фид Maytoni.
         * Другие форматы подключим отдельно.
         */
        if ($supplier_id !== 'maytoni' || $format !== 'yml') {
            throw new \RuntimeException(
                sprintf(
                    'Формат %s для поставщика %s пока не поддерживается автоматическим импортом.',
                    $format,
                    $supplier_id
                )
            );
        }

        $validator = new Yml_Validator();
        $validator->validate($file);

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

        $result = $import_manager->import_stream(
            $reader,
            [$mapper, 'map'],
            function ($product, $product_data, $action) use (
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

                if ($product_data->get_supplier() !== $supplier_id) {
                    throw new \RuntimeException(
                        'Поставщик товара не совпадает с поставщиком фида.'
                    );
                }

                $product_meta->set_feed_run(
                    $product,
                    $run_id
                );

                if ($product_meta->get_feed_run($product) !== $run_id) {
                    throw new \RuntimeException(
                        sprintf(
                            'Не удалось сохранить метку запуска фида для товара ID %d.',
                            $product->get_id()
                        )
                    );
                }
            }
        );

        \Supplier_Importer\Core\Logger::info(
            sprintf(
                'FEED SYNC: supplier=%s, total=%d, created=%d, updated=%d, skipped=%d, errors=%d',
                $supplier_id,
                $result['total'],
                $result['created'],
                $result['updated'],
                $result['skipped'],
                $result['errors']
            )
        );

        /*
         * Подробно записываем ошибки отдельных товаров.
         */
        foreach ($result['items'] as $item) {
            if (($item['action'] ?? '') !== 'error') {
                continue;
            }

            \Supplier_Importer\Core\Logger::info(
                sprintf(
                    'FEED SYNC ERROR: supplier=%s, sku=%s, index=%d, message=%s',
                    $supplier_id,
                    $item['sku'] ?? '',
                    $item['index'] ?? 0,
                    $item['message'] ?? 'Неизвестная ошибка.'
                )
            );
        }

        /*
         * Пустой фид не должен считаться успешной синхронизацией.
         */
        if ($result['total'] === 0) {
            throw new \RuntimeException(
                'Фид не содержит ни одной записи товара.'
            );
        }

        /*
         * Не запускаем синхронизацию каталога,
         * если хотя бы одна запись завершилась ошибкой.
         */
        if ($result['errors'] > 0) {
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
         * Обрабатываем товары, отсутствующие в фиде,
         * и возвращаем ранее снятые синхронизацией товары.
         */
        $sync = new Feed_Product_Sync();

        $sync_result = $sync->sync_missing_products(
            $supplier_id,
            $run_id
        );

        \Supplier_Importer\Core\Logger::info(
            sprintf(
                'FEED PRODUCT SYNC: supplier=%s, checked=%d, unpublished=%d, republished=%d',
                $supplier_id,
                $sync_result['checked'],
                $sync_result['unpublished'],
                $sync_result['republished']
            )
        );
    }
}
