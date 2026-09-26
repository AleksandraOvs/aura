<?php

namespace Supplier_Importer\Admin;

use Supplier_Importer\Import\Import_Repository;

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

        add_action(
            'wp_ajax_supplier_importer_reset_statistics',
            [$this, 'reset_statistics']
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
        $current_tab = isset($_GET['tab'])
            ? sanitize_key($_GET['tab'])
            : 'import';

        if (!in_array($current_tab, ['import', 'statistics'], true)) {
            $current_tab = 'import';
        }

?>
        <div class="wrap supplier-importer">

            <h1>Импорт поставщиков</h1>

            <nav class="nav-tab-wrapper">
                <a
                    href="<?php echo esc_url(
                                admin_url(
                                    'admin.php?page=supplier-importer&tab=import'
                                )
                            ); ?>"
                    class="nav-tab <?php echo $current_tab === 'import' ? 'nav-tab-active' : ''; ?>">
                    Импорт
                </a>

                <a
                    href="<?php echo esc_url(
                                admin_url(
                                    'admin.php?page=supplier-importer&tab=statistics'
                                )
                            ); ?>"
                    class="nav-tab <?php echo $current_tab === 'statistics' ? 'nav-tab-active' : ''; ?>">
                    Статистика
                </a>
            </nav>

            <?php if ($current_tab === 'statistics') : ?>

                <?php $this->render_statistics(); ?>

            <?php else : ?>

                <?php $this->render_import(); ?>

            <?php endif; ?>

        </div>
    <?php
    }

    private function render_import()
    {
    ?>
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
                    hidden>
                </div>

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
                    class="supplier-importer__progress-bar">
                </div>

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
                class="supplier-importer__stats">
            </div>

        </div>

        <div
            id="supplier-import-errors"
            class="supplier-importer__card"
            hidden>

            <h2>Ошибки импорта</h2>

            <div id="supplier-import-errors-list"></div>

        </div>
    <?php
    }

    private function render_statistics()
    {
        $repository = new Import_Repository();

        $history = $repository->get_history();

    ?>

        <div class="supplier-importer__card">

            <div class="supplier-importer__statistics-header">

                <div>
                    <h2>Статистика импорта</h2>

                    <p>
                        История загрузок поставщиков.
                    </p>
                </div>

                <?php if (!empty($history)) : ?>

                    <button
                        type="button"
                        class="button"
                        id="supplier-importer-reset-statistics">
                        Сбросить статистику
                    </button>

                <?php endif; ?>

            </div>

            <div
                id="supplier-importer-statistics-message"
                class="supplier-importer__message"
                hidden>
            </div>

            <?php if (empty($history)) : ?>

                <p>
                    История импорта пока пуста.
                </p>

            <?php else : ?>

                <div class="supplier-importer__table-wrapper">

                    <table class="widefat striped">

                        <thead>
                            <tr>

                                <th>
                                    Дата
                                </th>

                                <th>
                                    Поставщик
                                </th>

                                <th>
                                    Файл
                                </th>

                                <th>
                                    Всего
                                </th>

                                <th>
                                    Загружено
                                </th>

                                <th>
                                    Создано
                                </th>

                                <th>
                                    Обновлено
                                </th>

                                <th>
                                    Пропущено
                                </th>

                                <th>
                                    Ошибки
                                </th>

                                <th>
                                    Статус
                                </th>

                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($history as $item) : ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            $this->format_date(
                                                $item['started_at']
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $item['supplier']
                                            );
                                            ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            wp_basename(
                                                $item['file']
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                (int) $item['total']
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                (int) $item['processed']
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                (int) $item['created']
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                (int) $item['updated']
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                (int) $item['skipped']
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                (int) $item['errors']
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            $this->get_status_label(
                                                $item['status']
                                            )
                                        );
                                        ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

<?php
    }

    private function format_date($date)
    {
        if (empty($date)) {
            return '—';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return $date;
        }

        return wp_date(
            'd.m.Y H:i',
            $timestamp
        );
    }

    private function get_status_label($status)
    {
        $statuses = [
            'pending'   => 'Ожидание',
            'running'   => 'В процессе',
            'completed' => 'Завершён',
            'failed'    => 'Ошибка',
            'cancelled' => 'Отменён',
        ];

        return $statuses[$status] ?? $status;
    }

    public function reset_statistics()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(
                [
                    'message' => 'Недостаточно прав.',
                ],
                403
            );
        }

        check_ajax_referer(
            'supplier_import',
            'nonce'
        );

        try {

            $repository = new Import_Repository();

            $repository->reset_history();

            wp_send_json_success(
                [
                    'message' => 'Статистика успешно сброшена.',
                ]
            );
        } catch (\Throwable $e) {

            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                ],
                500
            );
        }
    }
}
