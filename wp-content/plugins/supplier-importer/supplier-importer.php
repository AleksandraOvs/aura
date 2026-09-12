<?php

/**
 * Plugin Name: Supplier Importer
 * Description: Универсальный импорт товаров поставщиков для WooCommerce.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define(
    'SUPPLIER_IMPORTER_PATH',
    plugin_dir_path(__FILE__)
);

$required_files = [
    'src/Parsers/CsvParser.php',
    'src/Parsers/YmlParser.php',
    'src/Normalizer/ValueCleaner.php',
    'src/Normalizer/ProductNormalizer.php',
    'src/Import/ProductImporter.php',
    'src/Import/AttributeMapper.php',
    'src/Import/ImageImporter.php',
];


foreach ($required_files as $file) {

    $path = SUPPLIER_IMPORTER_PATH . $file;

    if (!file_exists($path)) {
        add_action('admin_notices', function () use ($file) {

            echo '<div class="notice notice-error">';
            echo '<p>';
            echo '<strong>Supplier Importer:</strong> ';
            echo 'Не найден файл: ';
            echo '<code>' . esc_html($file) . '</code>';
            echo '</p>';
            echo '</div>';
        });

        continue;
    }

    require_once $path;
}

/**
 * AJAX: импорт одной порции товаров.
 */
add_action(
    'wp_ajax_supplier_import_chunk',
    function () {

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => 'Недостаточно прав.',
            ]);
        }

        check_ajax_referer(
            'supplier_import_chunk',
            'nonce'
        );

        $supplier_key = isset($_POST['supplier'])
            ? sanitize_key($_POST['supplier'])
            : '';

        $offset = isset($_POST['offset'])
            ? max(0, (int) $_POST['offset'])
            : 0;

        $limit = isset($_POST['limit'])
            ? max(1, min(50, (int) $_POST['limit']))
            : 25;

        $suppliers = [
            'denkirs' => [
                'type' => 'csv',
                'file' => SUPPLIER_IMPORTER_PATH .
                    'suppliers/denkirs/denkirs.csv',
                'config' => SUPPLIER_IMPORTER_PATH .
                    'configs/denkirs.php',
            ],

            'maytoni' => [
                'type' => 'csv',
                'file' => SUPPLIER_IMPORTER_PATH .
                    'suppliers/maytoni/maytoni.csv',
                'config' => SUPPLIER_IMPORTER_PATH .
                    'configs/maytoni.php',
            ],

            'crystal-lux' => [
                'type' => 'yml',
                'file' => SUPPLIER_IMPORTER_PATH .
                    'suppliers/crystal-lux/crystal-lux.xml',
                'config' => SUPPLIER_IMPORTER_PATH .
                    'configs/crystal-lux.php',
            ],

            'citilux' => [
                'type' => 'yml',
                'file' => SUPPLIER_IMPORTER_PATH .
                    'suppliers/citilux/citilux.xml',
                'config' => SUPPLIER_IMPORTER_PATH .
                    'configs/citilux.php',
            ],

            'lightstar' => [
                'type' => 'csv',
                'file' => SUPPLIER_IMPORTER_PATH .
                    'suppliers/lightstar/lightstar.csv',
                'config' => SUPPLIER_IMPORTER_PATH .
                    'configs/lightstar.php',
            ],
        ];

        if (!isset($suppliers[$supplier_key])) {
            wp_send_json_error([
                'message' => 'Неизвестный поставщик.',
            ]);
        }

        $supplier = $suppliers[$supplier_key];

        try {

            if (!file_exists($supplier['config'])) {
                throw new \RuntimeException(
                    'Конфигурация не найдена.'
                );
            }

            if (!file_exists($supplier['file'])) {
                throw new \RuntimeException(
                    'Файл поставщика не найден.'
                );
            }

            $config = require $supplier['config'];

            /*
             * Сейчас порционный импорт делаем для CSV.
             */
            if ($supplier['type'] !== 'csv') {
                throw new \RuntimeException(
                    'Порционный импорт пока реализован для CSV.'
                );
            }

            $parser = new \SupplierImporter\Parsers\CsvParser(';');

            /*
             * Получаем только текущую порцию.
             */
            $parsed = $parser->parseChunk(
                $supplier['file'],
                $offset,
                $limit
            );

            $rows = $parsed['rows'];

            supplier_import_log(
                sprintf(
                    'CHUNK START | supplier=%s | offset=%d | limit=%d | rows=%d',
                    $supplier_key,
                    $offset,
                    $limit,
                    count($rows)
                )
            );

            if (empty($rows)) {

                supplier_import_log(
                    'CHUNK EMPTY | import finished'
                );

                wp_send_json_success([
                    'finished' => true,
                    'offset' => $offset,
                    'next_offset' => $offset,
                    'processed' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'errors' => 0,
                    'results' => [],
                ]);
            }

            $normalizer =
                new \SupplierImporter\Normalizer\ProductNormalizer();

            $importer =
                new \SupplierImporter\Import\ProductImporter();

            $created = 0;
            $updated = 0;
            $errors = 0;

            $results = [];

            foreach ($rows as $index => $row) {

                $absolute_index =
                    $offset + $index + 1;
                $product = [];
                try {

                    $product =
                        $normalizer->normalize(
                            $row,
                            $config
                        );

                    supplier_import_log(
                        sprintf(
                            '[%d] START | external_id=%s | sku=%s | name=%s',
                            $absolute_index,
                            $product['external_id'] ?? '',
                            $product['sku'] ?? '',
                            $product['name'] ?? ''
                        )
                    );

                    $result =
                        $importer->import($product);

                    if (
                        ($result['action'] ?? '') === 'created'
                    ) {
                        $created++;
                    } elseif (
                        ($result['action'] ?? '') === 'updated'
                    ) {
                        $updated++;
                    }

                    supplier_import_log(
                        sprintf(
                            '[%d] SUCCESS | action=%s | product_id=%s',
                            $absolute_index,
                            $result['action'] ?? '',
                            $result['product_id'] ?? ''
                        )
                    );

                    $results[] = [
                        'success' => true,
                        'action' => $result['action'] ?? '',
                        'product_id' =>
                        $result['product_id'] ?? 0,
                        'external_id' =>
                        $product['external_id'] ?? '',
                        'sku' =>
                        $product['sku'] ?? '',
                        'name' =>
                        $product['name'] ?? '',
                    ];
                } catch (\Throwable $e) {

                    $errors++;

                    supplier_import_log(
                        sprintf(
                            '[%d] ERROR | external_id=%s | sku=%s | %s',
                            $absolute_index,
                            $product['external_id'] ?? '',
                            $product['sku'] ?? '',
                            $e->getMessage()
                        )
                    );

                    $results[] = [
                        'success' => false,
                        'action' => 'error',
                        'product_id' => 0,
                        'external_id' =>
                        $product['external_id'] ?? '',
                        'sku' =>
                        $product['sku'] ?? '',
                        'name' =>
                        $product['name'] ?? '',
                        'error' =>
                        $e->getMessage(),
                    ];
                }
            }

            $next_offset =
                $offset + count($rows);

            /*
             * Если получили меньше limit,
             * значит CSV закончился.
             */
            $finished =
                count($rows) < $limit;

            supplier_import_log(
                sprintf(
                    'CHUNK END | offset=%d | next_offset=%d | processed=%d | created=%d | updated=%d | errors=%d | finished=%s | memory=%d',
                    $offset,
                    $next_offset,
                    count($rows),
                    $created,
                    $updated,
                    $errors,
                    $finished ? 'yes' : 'no',
                    memory_get_usage(true)
                )
            );

            wp_send_json_success([
                'finished' => $finished,

                'offset' =>
                $offset,

                'next_offset' =>
                $next_offset,

                'processed' =>
                count($rows),

                'created' =>
                $created,

                'updated' =>
                $updated,

                'errors' =>
                $errors,

                'results' =>
                $results,
            ]);
        } catch (\Throwable $e) {

            supplier_import_log(
                sprintf(
                    '[%d] ERROR | external_id=%s | sku=%s | %s | file=%s | line=%d',
                    $absolute_index,
                    $product['external_id'] ?? '',
                    $product['sku'] ?? '',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );

            wp_send_json_error([
                'message' =>
                $e->getMessage(),
            ]);
        }
    }
);

function supplier_import_log(string $message): void
{
    $log_dir =
        WP_CONTENT_DIR .
        '/uploads/supplier-importer';

    if (!is_dir($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    $log_file =
        $log_dir .
        '/import.log';

    $time = date('Y-m-d H:i:s');

    file_put_contents(
        $log_file,
        '[' . $time . '] ' .
            $message .
            PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

/**
 * Подключение JS для импорта товаров.
 */
add_action('admin_enqueue_scripts', function ($hook) {

    if ($hook !== 'toplevel_page_supplier-importer') {
        return;
    }

    $script_path = SUPPLIER_IMPORTER_PATH . 'js/import.js';
    $script_url  = plugins_url('js/import.js', __FILE__);

    if (!file_exists($script_path)) {
        return;
    }

    wp_enqueue_script(
        'supplier-importer',
        $script_url,
        [],
        filemtime($script_path),
        true
    );

    wp_localize_script(
        'supplier-importer',
        'supplierImportData',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('supplier_import_chunk'),
            'supplier' => isset($_GET['supplier'])
                ? sanitize_key($_GET['supplier'])
                : 'denkirs',
            'chunkSize' => 25,
        ]
    );
});

//require_once SUPPLIER_IMPORTER_PATH . 'test-parser.php';

add_action('admin_menu', function () {

    add_menu_page(
        'Supplier Importer',
        'Supplier Importer',
        'manage_woocommerce',
        'supplier-importer',
        function () {
            require SUPPLIER_IMPORTER_PATH . 'test-parser.php';
        },
        'dashicons-database-import',
        56
    );


    add_submenu_page(
        'supplier-importer',
        'XML диагностика',
        'XML диагностика',
        'manage_woocommerce',
        'supplier-importer-xml-test',
        function () {
            require SUPPLIER_IMPORTER_PATH . 'test-xml.php';
        }
    );

    add_submenu_page(
        'supplier-importer',
        'Документация',
        'Документация',
        'manage_woocommerce',
        'supplier-importer-docs',
        function () {
            require SUPPLIER_IMPORTER_PATH . 'documentation.php';
        }
    );
});
