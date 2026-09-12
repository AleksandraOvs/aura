<?php

if (!defined('ABSPATH')) {
    exit;
}


register_shutdown_function(function () {

    $error = error_get_last();

    if (!$error) {
        return;
    }

    $fatal_types = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
    ];

    if (!in_array($error['type'], $fatal_types, true)) {
        return;
    }

    supplier_import_log(
        'FATAL ERROR: ' .
            $error['message'] .
            ' | file=' .
            $error['file'] .
            ' | line=' .
            $error['line']
    );

    supplier_import_log(
        'Memory end: ' .
            memory_get_usage(true)
    );
});

use SupplierImporter\Parsers\CsvParser;
use SupplierImporter\Parsers\YmlParser;
use SupplierImporter\Normalizer\ProductNormalizer;

use SupplierImporter\Import\ProductImporter;


/**
 * =========================================================
 * НАСТРОЙКИ ТЕСТОВЫХ ФАЙЛОВ
 * =========================================================
 */

$suppliers = [

    'denkirs' => [
        'name' => 'Denkirs',
        'type' => 'csv',
        'file' => SUPPLIER_IMPORTER_PATH . 'suppliers/denkirs/denkirs.csv',
        'config' => SUPPLIER_IMPORTER_PATH . 'configs/denkirs.php',
    ],

    'maytoni' => [
        'name' => 'Maytoni',
        'type' => 'csv',
        'file' => SUPPLIER_IMPORTER_PATH . 'suppliers/maytoni/maytoni.csv',
        'config' => SUPPLIER_IMPORTER_PATH . 'configs/maytoni.php',
    ],

    'crystal-lux' => [
        'name' => 'Crystal Lux',
        'type' => 'yml',
        'file' => SUPPLIER_IMPORTER_PATH . 'suppliers/crystal-lux/crystal-lux.xml',
        'config' => SUPPLIER_IMPORTER_PATH . 'configs/crystal-lux.php',
    ],

    'citilux' => [
        'name' => 'CITILUX',
        'type' => 'yml',
        'file' => SUPPLIER_IMPORTER_PATH . 'suppliers/citilux/citilux.xml',
        'config' => SUPPLIER_IMPORTER_PATH . 'configs/citilux.php',
    ],

    'lightstar' => [
        'name' => 'Lightstar',
        'type' => 'csv',
        'file' => SUPPLIER_IMPORTER_PATH . 'suppliers/lightstar/lightstar.csv',
        'config' => SUPPLIER_IMPORTER_PATH . 'configs/lightstar.php',
    ],

];


/**
 * =========================================================
 * ВЫБОР ПОСТАВЩИКА
 * =========================================================
 */

$selected_supplier = isset($_GET['supplier'])
    ? sanitize_key($_GET['supplier'])
    : 'denkirs';

if (!isset($suppliers[$selected_supplier])) {
    $selected_supplier = 'denkirs';
}

$supplier = $suppliers[$selected_supplier];


/**
 * =========================================================
 * РЕЗУЛЬТАТ
 * =========================================================
 */

$result = null;
$error = null;

$import_result = null;
$import_error = null;

/**
 * =========================================================
 * ИМПОРТ ТЕСТОВЫХ ТОВАРОВ
 * =========================================================
 */

if (
    isset($_GET['import']) &&
    $_GET['import'] === '1'
) {

    supplier_import_log('========================================');
    supplier_import_log('IMPORT START');
    supplier_import_log('Supplier: ' . ($supplier_key ?? 'unknown'));
    supplier_import_log('Memory start: ' . memory_get_usage(true));

    try {

        if (!file_exists($supplier['config'])) {
            throw new RuntimeException(
                'Конфигурация не найдена.'
            );
        }

        $config = require $supplier['config'];

        if (!file_exists($supplier['file'])) {
            throw new RuntimeException(
                'Файл поставщика не найден.'
            );
        }

        /**
         * Парсер.
         */
        if ($supplier['type'] === 'csv') {

            $parser = new CsvParser(';');
        } elseif ($supplier['type'] === 'yml') {

            $parser = new YmlParser();
        } else {

            throw new RuntimeException(
                'Неизвестный тип файла.'
            );
        }

        /**
         * Парсим.
         */
        $parsed = $parser->parse(
            $supplier['file']
        );

        supplier_import_log(
            'Parser finished. Rows: ' . count($parsed['rows'])
        );

        /**
         * Только тестовые товары.
         */
        $test_rows = $parsed['rows'];

        supplier_import_log(
            'Rows selected for import: ' . count($test_rows)
        );

        /**
         * Нормализуем.
         */
        $normalizer = new ProductNormalizer();

        $normalized_products = [];

        foreach ($test_rows as $row) {

            $normalized_products[] =
                $normalizer->normalize(
                    $row,
                    $config
                );
        }

        supplier_import_log(
            'Normalized products: ' . count($normalized_products)
        );


        /**
         * Импортируем.
         */



        $importer = new ProductImporter();

        $import_result = [];

        foreach (
            $normalized_products as $index => $product
        ) {

            supplier_import_log(
                sprintf(
                    '[%d/%d] START | external_id=%s | sku=%s | name=%s',
                    $index + 1,
                    count($normalized_products),
                    $product['external_id'] ?? '',
                    $product['sku'] ?? '',
                    $product['name'] ?? ''
                )
            );

            try {

                $import_result[] =
                    $importer->import(
                        $product
                    );

                supplier_import_log(
                    sprintf(
                        '[%d/%d] SUCCESS | external_id=%s | product_id=%s',
                        $index + 1,
                        count($normalized_products),
                        $product['external_id'] ?? '',
                        $import_result[count($import_result) - 1]['product_id'] ?? ''
                    )
                );
            } catch (\Throwable $e) {
                supplier_import_log(
                    sprintf(
                        '[%d/%d] ERROR | external_id=%s | sku=%s | %s',
                        $index + 1,
                        count($normalized_products),
                        $product['external_id'] ?? '',
                        $product['sku'] ?? '',
                        $e->getMessage()
                    )
                );
                $import_result[] = [
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
    } catch (\Throwable $e) {

        $import_error = $e->getMessage();
    }
}

if (
    isset($_GET['run']) &&
    $_GET['run'] === '1'
) {

    try {

        /**
         * -------------------------------------------------
         * Загружаем конфиг
         * -------------------------------------------------
         */

        if (!file_exists($supplier['config'])) {
            throw new RuntimeException(
                'Конфигурация не найдена: ' .
                    $supplier['config']
            );
        }

        $config = require $supplier['config'];

        if (!is_array($config)) {
            throw new RuntimeException(
                'Конфигурация поставщика должна возвращать массив.'
            );
        }


        /**
         * -------------------------------------------------
         * Проверяем файл
         * -------------------------------------------------
         */

        if (!file_exists($supplier['file'])) {
            throw new RuntimeException(
                'Файл поставщика не найден: ' .
                    $supplier['file']
            );
        }


        /**
         * -------------------------------------------------
         * Выбираем парсер
         * -------------------------------------------------
         */

        if ($supplier['type'] === 'csv') {

            $parser = new CsvParser(';');
        } elseif ($supplier['type'] === 'yml') {

            $parser = new YmlParser();
        } else {

            throw new RuntimeException(
                'Неизвестный тип файла: ' .
                    $supplier['type']
            );
        }


        /**
         * -------------------------------------------------
         * Парсим файл
         * -------------------------------------------------
         */

        $parsed = $parser->parse(
            $supplier['file']
        );

        // echo '<pre>';
        // print_r($parsed['rows'][0]);
        // echo '</pre>';


        /**
         * -------------------------------------------------
         * Нормализуем товары
         * -------------------------------------------------
         */

        $normalizer = new ProductNormalizer();

        $normalized_products = [];

        /*
 * Для теста нормализуем только первые 7 товаров.
 * Сам парсер при этом уже прочитал весь файл.
 */
        $test_rows = array_slice(
            $parsed['rows'],
            0,
            1
        );

        foreach ($test_rows as $row) {

            $normalized_products[] =
                $normalizer->normalize(
                    $row,
                    $config
                );
        }


        /**
         * -------------------------------------------------
         * Сохраняем результат
         * -------------------------------------------------
         */

        $result = [
            'supplier' => $supplier,
            'config' => $config,
            'parsed' => $parsed,
            'normalized' => $normalized_products,
        ];
    } catch (\Throwable $e) {

        $error = $e->getMessage();
    }
}


/**
 * =========================================================
 * URL
 * =========================================================
 */

$base_url = admin_url('admin.php');

?>

<div class="wrap">

    <h1>Supplier Importer — тест парсера</h1>


    <form method="get">

        <input
            type="hidden"
            name="page"
            value="supplier-importer">

        <input
            type="hidden"
            name="run"
            value="1">


        <table class="form-table">

            <tr>

                <th scope="row">
                    <label for="supplier">
                        Поставщик
                    </label>
                </th>

                <td>

                    <select
                        name="supplier"
                        id="supplier">

                        <?php foreach ($suppliers as $key => $item): ?>

                            <option
                                value="<?php echo esc_attr($key); ?>"
                                <?php selected(
                                    $selected_supplier,
                                    $key
                                ); ?>>
                                <?php echo esc_html(
                                    $item['name']
                                ); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </td>

            </tr>

        </table>


        <p>

            <button
                type="submit"
                class="button button-primary">
                Протестировать
            </button>

        </p>

    </form>


    <form method="get" style="margin-top:10px;">

        <input
            type="hidden"
            name="page"
            value="supplier-importer">

        <input
            type="hidden"
            name="supplier"
            value="<?php echo esc_attr($selected_supplier); ?>">

        <input
            type="hidden"
            name="import"
            value="1">



        <div id="supplier-import">

            <button
                type="button"
                id="supplier-import-start"
                class="button button-primary">
                Импортировать все товары
            </button>

            <div
                id="supplier-import-status"
                style="
            display:none;
            margin-top:20px;
            padding:15px;
            background:#fff;
            border:1px solid #ccd0d4;
        ">

                <div
                    id="supplier-import-progress"
                    style="font-size:16px;font-weight:600;">
                    Подготовка импорта...
                </div>

                <div
                    style="
                margin-top:12px;
                height:20px;
                background:#eee;
                border-radius:4px;
                overflow:hidden;
            ">
                    <div
                        id="supplier-import-progress-bar"
                        style="
                    width:0%;
                    height:100%;
                    background:#2271b1;
                    transition:width .2s;
                "></div>
                </div>

                <div
                    id="supplier-import-message"
                    style="margin-top:12px;"></div>

            </div>

        </div>

    </form>


    <?php if ($import_error): ?>

        <div class="notice notice-error">

            <p>
                <strong>Ошибка импорта:</strong>
                <?php echo esc_html($import_error); ?>
            </p>

        </div>

    <?php endif; ?>





    <?php if ($import_result): ?>

        <hr>

        <h2>
            Результат импорта
        </h2>

        <?php foreach ($import_result as $item): ?>

            <?php if ($item['success']): ?>

                <div class="notice notice-success">

                    <p>

                        <strong>
                            <?php
                            echo $item['action'] === 'created'
                                ? 'Создан'
                                : 'Обновлён';
                            ?>
                        </strong>

                        —

                        <?php echo esc_html(
                            $item['name']
                        ); ?>

                        <br>

                        ID:
                        <strong>
                            <?php echo esc_html(
                                $item['product_id']
                            ); ?>
                        </strong>

                        |

                        SKU:
                        <code>
                            <?php echo esc_html(
                                $item['sku']
                            ); ?>
                        </code>

                        |

                        external_id:
                        <code>
                            <?php echo esc_html(
                                $item['external_id']
                            ); ?>
                        </code>

                        <?php if (!empty($item['image_import'])): ?>

                            <br><br>

                            Изображения:

                            импортировано:
                            <strong>
                                <?php echo esc_html(
                                    $item['image_import']['imported']
                                ); ?>
                            </strong>

                            |

                            пропущено:
                            <strong>
                                <?php echo esc_html(
                                    $item['image_import']['skipped']
                                ); ?>
                            </strong>

                            |

                            ошибок:
                            <strong>
                                <?php echo esc_html(
                                    $item['image_import']['failed']
                                ); ?>
                            </strong>

                        <?php endif; ?>

                    </p>

                </div>

            <?php else: ?>

                <div class="notice notice-error">

                    <p>

                        <strong>
                            Ошибка:
                        </strong>

                        <?php echo esc_html(
                            $item['name']
                        ); ?>

                        <br>

                        <?php echo esc_html(
                            $item['error']
                        ); ?>

                    </p>

                </div>

            <?php endif; ?>

        <?php endforeach; ?>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="notice notice-error">

            <p>
                <strong>Ошибка:</strong>
                <?php echo esc_html($error); ?>
            </p>

        </div>

    <?php endif; ?>


    <?php if ($result): ?>

        <?php

        $parsed = $result['parsed'];
        $normalized = $result['normalized'];
        $config = $result['config'];

        ?>


        <!-- =================================================
             ИНФОРМАЦИЯ О ФАЙЛЕ
        ================================================== -->

        <hr>

        <h2>
            <?php echo esc_html(
                $supplier['name']
            ); ?>
        </h2>


        <table class="widefat striped">

            <tbody>

                <tr>

                    <td width="200">
                        Тип
                    </td>

                    <td>
                        <code>
                            <?php echo esc_html(
                                strtoupper(
                                    $supplier['type']
                                )
                            ); ?>
                        </code>
                    </td>

                </tr>


                <tr>

                    <td>
                        Файл
                    </td>

                    <td>
                        <code>
                            <?php echo esc_html(
                                basename(
                                    $supplier['file']
                                )
                            ); ?>
                        </code>
                    </td>

                </tr>


                <tr>

                    <td>
                        Товаров в файле
                    </td>

                    <td>
                        <strong>
                            <?php echo esc_html(
                                count($parsed['rows'])
                            ); ?>
                        </strong>
                    </td>

                </tr>

                <tr>

                    <td>
                        Товаров в тесте
                    </td>

                    <td>
                        <strong>
                            <?php echo esc_html(
                                count($normalized)
                            ); ?>
                        </strong>
                    </td>

                </tr>

            </tbody>

        </table>


        <!-- =================================================
             НОРМАЛИЗОВАННЫЕ ТОВАРЫ
        ================================================== -->

        <hr>

        <h2>
            Нормализованные товары
        </h2>

        <p>
            Ниже результат, который будет передаваться
            следующему этапу импорта WooCommerce.
        </p>


        <?php foreach (
            $normalized as $index => $product
        ): ?>

            <details
                style="
                    margin-bottom:20px;
                    border:1px solid #ccd0d4;
                    background:#fff;
                ">

                <summary
                    style="
                        cursor:pointer;
                        padding:15px;
                        font-size:16px;
                        font-weight:600;
                    ">

                    Товар №<?php echo $index + 1; ?>

                    <?php if (!empty($product['name'])): ?>

                        —
                        <?php echo esc_html(
                            $product['name']
                        ); ?>

                    <?php endif; ?>

                </summary>


                <div style="padding:0 20px 20px;">


                    <h3>
                        Основные данные
                    </h3>

                    <pre style="
                        background:#f6f7f7;
                        padding:15px;
                        overflow:auto;
                    "><?php
                        echo esc_html(
                            print_r(
                                [
                                    'supplier'          => $product['supplier'],
                                    'external_id'       => $product['external_id'],
                                    'sku'               => $product['sku'],
                                    'barcode'            => $product['barcode'],
                                    'name'              => $product['name'],
                                    'description'       => $product['description'],
                                    'short_description' => $product['short_description'],
                                    'price'             => $product['price'],
                                    'old_price'         => $product['old_price'],
                                    'stock'             => $product['stock'],
                                    'available'         => $product['available'],
                                    'brand'             => $product['brand'],
                                    'source_url'        => $product['source_url'],
                                    'currency'          => $product['currency'],
                                ],
                                true
                            )
                        );
                        ?></pre>


                    <h3>
                        Категории
                    </h3>

                    <pre style="
                        background:#f6f7f7;
                        padding:15px;
                        overflow:auto;
                    "><?php
                        echo esc_html(
                            print_r(
                                $product['categories'],
                                true
                            )
                        );
                        ?></pre>


                    <h3>
                        Изображения
                    </h3>

                    <pre style="
                        background:#f6f7f7;
                        padding:15px;
                        overflow:auto;
                    "><?php
                        echo esc_html(
                            print_r(
                                $product['images'],
                                true
                            )
                        );
                        ?></pre>


                    <h3>
                        Атрибуты
                    </h3>

                    <pre style="
                        background:#f6f7f7;
                        padding:15px;
                        overflow:auto;
                    "><?php
                        echo esc_html(
                            print_r(
                                $product['attributes'],
                                true
                            )
                        );
                        ?></pre>


                    <h3>
                        Meta
                    </h3>

                    <pre style="
                        background:#f6f7f7;
                        padding:15px;
                        overflow:auto;
                    "><?php
                        echo esc_html(
                            print_r(
                                $product['meta'],
                                true
                            )
                        );
                        ?></pre>


                    <h3>
                        Полный normalized product
                    </h3>

                    <pre style="
                        background:#1d2327;
                        color:#fff;
                        padding:15px;
                        overflow:auto;
                    "><?php
                        echo esc_html(
                            print_r(
                                $product,
                                true
                            )
                        );
                        ?></pre>

                </div>

            </details>

        <?php endforeach; ?>


        <!-- =================================================
             RAW PARSED DATA
        ================================================== -->

        <hr>

        <details>

            <summary
                style="
                    cursor:pointer;
                    font-size:16px;
                    font-weight:600;
                    margin-bottom:15px;
                ">
                Показать исходные данные после парсера
            </summary>


            <pre style="
                background:#1d2327;
                color:#fff;
                padding:20px;
                overflow:auto;
                max-height:700px;
            "><?php
                echo esc_html(
                    print_r(
                        array_slice(
                            $parsed['rows'],
                            0,
                            20
                        ),
                        true
                    )
                );
                ?></pre>

        </details>


        <!-- =================================================
             CONFIG
        ================================================== -->

        <hr>

        <details>

            <summary
                style="
                    cursor:pointer;
                    font-size:16px;
                    font-weight:600;
                    margin-bottom:15px;
                ">
                Показать конфигурацию поставщика
            </summary>


            <pre style="
                background:#f6f7f7;
                padding:20px;
                overflow:auto;
                max-height:700px;
            "><?php
                echo esc_html(
                    print_r(
                        $config,
                        true
                    )
                );
                ?></pre>

        </details>


    <?php endif; ?>

</div>