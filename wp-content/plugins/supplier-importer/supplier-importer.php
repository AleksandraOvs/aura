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

define(
    'SUPPLIER_IMPORTER_URL',
    plugin_dir_url(__FILE__)
);

$required_files = [
    'src/Parsers/CsvParser.php',
    'src/Parsers/YmlParser.php',
    'src/Normalizer/ValueCleaner.php',
    'src/Normalizer/ProductNormalizer.php',
    'src/Import/ProductImporter.php',
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
});
