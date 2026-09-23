<?php

namespace Supplier_Importer\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    public function __construct()
    {
        add_action(
            'admin_menu',
            [$this, 'register_menu']
        );

        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueue_assets']
        );
    }

    public function register_menu()
    {
        add_menu_page(
            'Импорт поставщиков',
            'Импорт поставщиков',
            'manage_woocommerce',
            'supplier-importer',
            [$this, 'render_page'],
            'dashicons-database-import',
            56
        );
    }

    public function enqueue_assets($hook)
    {
        if ($hook !== 'toplevel_page_supplier-importer') {
            return;
        }

        $css_path = SUPPLIER_IMPORTER_PATH
            . 'assets/css/admin.css';

        $js_path = SUPPLIER_IMPORTER_PATH
            . 'assets/js/admin.js';

        wp_enqueue_style(
            'supplier-importer-admin',
            SUPPLIER_IMPORTER_URL
                . 'assets/css/admin.css',
            [],
            file_exists($css_path)
                ? filemtime($css_path)
                : SUPPLIER_IMPORTER_VERSION
        );

        wp_enqueue_script(
            'supplier-importer-admin',
            SUPPLIER_IMPORTER_URL
                . 'assets/js/admin.js',
            [],
            file_exists($js_path)
                ? filemtime($js_path)
                : SUPPLIER_IMPORTER_VERSION,
            true
        );

        wp_localize_script(
            'supplier-importer-admin',
            'supplierImporter',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce(
                    'supplier_import'
                ),
            ]
        );
    }

    public function render_page()
    {
?>
        <div class="wrap supplier-importer">
            <h1>Импорт поставщиков</h1>

            <div class="supplier-importer__card">

                <h2>Загрузка CSV</h2>

                <p>
                    Выберите CSV-файл поставщика.
                </p>

                <form
                    id="supplier-import-form"
                    enctype="multipart/form-data">

                    <div class="supplier-importer__field">
                        <label for="supplier-import-file">
                            CSV-файл
                        </label>

                        <input
                            type="file"
                            id="supplier-import-file"
                            name="file"
                            accept=".csv,text/csv"
                            required>
                    </div>

                    <div
                        id="supplier-import-info"
                        class="supplier-importer__info"
                        hidden></div>

                    <button
                        type="submit"
                        class="button button-primary"
                        id="supplier-import-start">
                        Начать импорт
                    </button>

                </form>

            </div>

            <div
                id="supplier-import-mapping"
                class="supplier-importer__mapping"
                hidden>
            </div>

            <div
                id="supplier-import-progress"
                class="supplier-importer__card"
                hidden>
                <h2>Импорт</h2>

                <div class="supplier-importer__progress">
                    <div
                        id="supplier-import-progress-bar"
                        class="supplier-importer__progress-bar"></div>
                </div>

                <div class="supplier-importer__progress-info">
                    <strong id="supplier-import-progress-count">
                        0 / 0
                    </strong>

                    <span id="supplier-import-progress-percent">
                        0%
                    </span>
                </div>

                <div
                    id="supplier-import-progress-stats"
                    class="supplier-importer__stats"></div>
            </div>

            <div
                id="supplier-import-errors"
                class="supplier-importer__card"
                hidden>
                <h2>Ошибки импорта</h2>

                <div
                    id="supplier-import-errors-list"></div>
            </div>

        </div>
<?php
    }
}
