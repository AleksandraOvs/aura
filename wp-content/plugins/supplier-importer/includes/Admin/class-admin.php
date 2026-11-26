<?php

namespace Supplier_Importer\Admin;

use Supplier_Importer\Import\Import_Repository;
use Supplier_Importer\Suppliers\Supplier_Feed_Settings;
use Supplier_Importer\Core\Plugin;
use Supplier_Importer\Admin\Admin_Mappings;

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

        add_action(
            'wp_ajax_supplier_importer_get_logs',
            [$this, 'get_import_logs']
        );

        add_action(
            'wp_ajax_supplier_importer_cancel_import',
            [$this, 'cancel_import']
        );

        add_action(
            'admin_post_supplier_importer_save_feed_settings',
            [$this, 'save_feed_settings']
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
            ? sanitize_key(wp_unslash($_GET['tab']))
            : 'import';

        if (!in_array(
            $current_tab,
            ['import', 'statistics', 'feeds', 'mappings'],
            true
        )) {
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

                <a
                    href="<?php echo esc_url(
                                admin_url('admin.php?page=supplier-importer&tab=feeds')
                            ); ?>"
                    class="nav-tab <?php echo $current_tab === 'feeds' ? 'nav-tab-active' : ''; ?>">
                    Настройки фидов
                </a>

                <a
                    href="<?php echo esc_url(
                                admin_url('admin.php?page=supplier-importer&tab=mappings')
                            ); ?>"
                    class="nav-tab <?php echo $current_tab === 'mappings' ? 'nav-tab-active' : ''; ?>">
                    Сопоставления
                </a>
            </nav>

            <?php if ($current_tab === 'statistics') : ?>

                <?php $this->render_statistics(); ?>

            <?php elseif ($current_tab === 'feeds') : ?>

                <?php $this->render_feed_settings(); ?>

            <?php elseif ($current_tab === 'mappings') : ?>

                <?php (new Admin_Mappings())->render(); ?>

            <?php else : ?>

                <?php $this->render_import(); ?>

            <?php endif; ?>

        </div>
    <?php
    }

    private function render_import()
    {
    ?>
        <!-- CSV -->
        <div class="supplier-importer__card">

            <h2>Загрузка CSV</h2>

            <p>
                Выберите CSV-файл поставщика для импорта.
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

                <div class="supplier-importer__field">
                    <label>
                        <input
                            type="checkbox"
                            name="skip_attributes"
                            value="1">
                        Не учитывать атрибуты товаров
                    </label>
                    <p class="description">
                        Существующие атрибуты товаров останутся без изменений.
                        Новые товары будут созданы без атрибутов из фида.
                    </p>
                </div>

                <div class="supplier-importer__field">
                    <label>
                        <input
                            type="checkbox"
                            name="skip_categories"
                            value="1">
                        Не учитывать категории товаров
                    </label>
                    <p class="description">
                        Существующие категории товаров останутся без изменений.
                        Новые товары не получат категории из фида.
                    </p>
                </div>

                <button
                    type="submit"
                    class="button button-primary"
                    id="supplier-import-start">
                    Начать импорт
                </button>

            </form>

        </div>

        <!-- YML -->
        <div class="supplier-importer__card">

            <h2>Загрузка YML</h2>

            <p>
                Загрузите YML-файл поставщика с компьютера.
            </p>

            <div class="notice notice-warning inline">
                <p>
                    Импорт выполняет полную синхронизацию каталога.
                    Товары, отсутствующие в файле, будут сняты с публикации.
                    Загружайте только полный актуальный файл поставщика.
                </p>
            </div>

            <form
                id="supplier-yml-upload-form"
                enctype="multipart/form-data">

                <div class="supplier-importer__field">

                    <label for="supplier-yml-upload-file">
                        YML-файл
                    </label>

                    <input
                        type="file"
                        id="supplier-yml-upload-file"
                        name="file"
                        accept=".yml,.yaml,application/xml,text/xml"
                        required>

                </div>

                <div
                    id="supplier-yml-upload-info"
                    class="supplier-importer__info"
                    hidden>
                </div>

                <div class="supplier-importer__field">
                    <label>
                        <input
                            type="checkbox"
                            name="skip_attributes"
                            value="1">
                        Не учитывать атрибуты товаров
                    </label>
                    <p class="description">
                        Существующие атрибуты товаров останутся без изменений.
                        Новые товары будут созданы без атрибутов из фида.
                    </p>
                </div>

                <div class="supplier-importer__field">
                    <label>
                        <input
                            type="checkbox"
                            name="skip_categories"
                            value="1">
                        Не учитывать категории товаров
                    </label>
                    <p class="description">
                        Существующие категории товаров останутся без изменений.
                        Новые товары не получат категории из фида.
                    </p>
                </div>

                <button
                    type="submit"
                    class="button button-primary"
                    id="supplier-yml-upload-start">
                    Начать импорт YML
                </button>

            </form>

        </div>

        <!-- Ручной запуск фида -->
        <div class="supplier-importer__card">

            <h2>Запуск фида по URL</h2>

            <p>
                Запустите импорт по URL, сохранённому в настройках фида.
            </p>

            <form id="supplier-feed-run-form">

                <div class="supplier-importer__field">

                    <label for="supplier-feed-run-supplier">
                        Поставщик
                    </label>

                    <select
                        id="supplier-feed-run-supplier"
                        name="supplier_id"
                        required>

                        <option value="denkirs">
                            Denkirs
                        </option>

                        <option value="maytoni">
                            Maytoni
                        </option>

                        <option value="crystal_lux">
                            Crystal Lux
                        </option>

                        <option value="citilux">
                            CITILUX
                        </option>

                        <option value="lightstar">
                            Lightstar
                        </option>

                    </select>

                </div>

                <div
                    id="supplier-feed-run-info"
                    class="supplier-importer__info"
                    hidden>
                </div>

                <div class="supplier-importer__field">
                    <label>
                        <input
                            type="checkbox"
                            name="skip_attributes"
                            value="1">
                        Не учитывать атрибуты товаров
                    </label>
                    <p class="description">
                        Существующие атрибуты товаров останутся без изменений.
                        Новые товары будут созданы без атрибутов из фида.
                    </p>
                </div>

                <div class="supplier-importer__field">
                    <label>
                        <input
                            type="checkbox"
                            name="skip_categories"
                            value="1">
                        Не учитывать категории товаров
                    </label>
                    <p class="description">
                        Существующие категории товаров останутся без изменений.
                        Новые товары не получат категории из фида.
                    </p>
                </div>

                <button
                    type="submit"
                    class="button button-primary"
                    id="supplier-feed-run-start">
                    Запустить фид
                </button>

            </form>

            <p class="description">
                URL и параметры автоматической синхронизации
                настраиваются во вкладке «Настройки фидов».
            </p>

        </div>

        <!-- Существующие блоки CSV: не изменять -->
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

                                <th>
                                    Журнал
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
                                        <span class="supplier-importer__status-label">
                                            <?php
                                            echo esc_html(
                                                $this->get_status_label($item['status'])
                                            );
                                            ?>
                                        </span>

                                        <?php if (
                                            in_array($item['status'], ['pending', 'running'], true)
                                            && ($item['source'] ?? '') === 'cron'
                                            && in_array($item['format'] ?? '', ['yml', 'xml'], true)
                                        ) : ?>
                                            <button
                                                type="button"
                                                class="button button-small js-importer-cancel"
                                                data-import-id="<?php echo esc_attr($item['id']); ?>">
                                                Отменить импорт
                                            </button>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="button button-small js-importer-show-logs"
                                            data-import-id="<?php echo esc_attr($item['id']); ?>">
                                            Подробности
                                        </button>
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

        $date = new \DateTimeImmutable(
            $date,
            wp_timezone()
        );

        return $date->format('d.m.Y H:i');
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

    public function cancel_import()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(
                ['message' => 'Недостаточно прав.'],
                403
            );
        }

        check_ajax_referer('supplier_import', 'nonce');

        $import_id = isset($_POST['import_id'])
            ? absint($_POST['import_id'])
            : 0;

        if (!$import_id) {
            wp_send_json_error(
                ['message' => 'Некорректный ID импорта.'],
                400
            );
        }

        try {
            $repository = new Import_Repository();
            $import = $repository->get($import_id);

            if (!$import) {
                wp_send_json_error(
                    ['message' => 'Импорт не найден.'],
                    404
                );
            }

            if (
                ($import['source'] ?? '') !== 'cron'
                || !in_array($import['format'] ?? '', ['yml', 'xml'], true)
            ) {
                wp_send_json_error(
                    ['message' => 'Можно отменить только автоматический YML/XML-импорт.'],
                    400
                );
            }

            if (!$repository->cancel_active($import_id)) {
                wp_send_json_error(
                    ['message' => 'Импорт уже завершён или отменён.'],
                    409
                );
            }

            // Удаляем запланированные пакетные события только этого импорта.
            $hook = 'supplier_importer_process_feed_batch';
            $cron = _get_cron_array();

            foreach ($cron as $timestamp => $hooks) {
                if (empty($hooks[$hook])) {
                    continue;
                }

                foreach ($hooks[$hook] as $event) {
                    $args = $event['args'] ?? [];

                    if (
                        isset($args[0])
                        && (int) $args[0] === $import_id
                    ) {
                        wp_unschedule_event(
                            (int) $timestamp,
                            $hook,
                            $args,
                            true
                        );
                    }
                }
            }

            wp_send_json_success([
                'message' => 'Импорт отменён.',
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error(
                ['message' => $e->getMessage()],
                500
            );
        }
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

    public function get_import_logs()
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

        global $wpdb;

        $import_id = isset($_POST['import_id'])
            ? absint($_POST['import_id'])
            : 0;

        if (!$import_id) {
            wp_send_json_error(
                [
                    'message' => 'Некорректный ID импорта.',
                ],
                400
            );
        }

        $repository = new Import_Repository();

        if (!$repository->get($import_id)) {
            wp_send_json_error(
                [
                    'message' => 'Импорт не найден.',
                ],
                404
            );
        }

        $table_name = $wpdb->prefix . 'supplier_import_logs';

        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                id,
                level,
                message,
                context,
                created_at
            FROM {$table_name}
            WHERE import_id = %d
            ORDER BY id ASC",
                $import_id
            ),
            ARRAY_A
        );

        if ($wpdb->last_error) {
            wp_send_json_error(
                [
                    'message' => 'Не удалось получить журнал: '
                        . $wpdb->last_error,
                ],
                500
            );
        }

        foreach ($logs as &$log) {
            if (empty($log['context'])) {
                $log['context'] = null;
                continue;
            }

            $decoded_context = json_decode(
                $log['context'],
                true
            );

            if (json_last_error() === JSON_ERROR_NONE) {
                $log['context'] = $decoded_context;
            }
        }

        unset($log);

        unset($log);

        wp_send_json_success(
            [
                'logs' => $logs,
            ]
        );
    }

    public function save_feed_settings()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Недостаточно прав.');
        }

        check_admin_referer(
            'supplier_importer_save_feed_settings'
        );

        $supplier_id = isset($_POST['supplier_id'])
            ? sanitize_key(wp_unslash($_POST['supplier_id']))
            : '';

        $manager = Plugin::get_supplier_manager();

        if (!$manager || !$manager->has($supplier_id)) {
            wp_die('Неизвестный поставщик.');
        }

        $data = [
            'url' => isset($_POST['url'])
                ? wp_unslash($_POST['url'])
                : '',

            'format' => isset($_POST['format'])
                ? sanitize_text_field(wp_unslash($_POST['format']))
                : 'csv',

            'enabled' => isset($_POST['enabled']),
            'process_categories' => empty($_POST['skip_categories']),
            'process_attributes' => empty($_POST['skip_attributes']),
            'schedule' => isset($_POST['schedule'])
                ? sanitize_text_field(wp_unslash($_POST['schedule']))
                : 'daily',

            'time' => isset($_POST['time'])
                ? sanitize_text_field(wp_unslash($_POST['time']))
                : '03:00',

        ];

        if (!Supplier_Feed_Settings::save($supplier_id, $data)) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page' => 'supplier-importer',
                        'tab' => 'feeds',
                        'feed_saved' => '0',
                    ],
                    admin_url('admin.php')
                )
            );
            exit;
        }

        do_action(
            'supplier_importer_feed_settings_saved',
            $supplier_id
        );

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'supplier-importer',
                    'tab' => 'feeds',
                    'feed_saved' => '1',
                ],
                admin_url('admin.php')
            )
        );
        exit;
    }

    private function render_feed_settings()
    {
        $manager = Plugin::get_supplier_manager();


        if (!$manager) {
            echo '<p>Список поставщиков недоступен.</p>';
            return;
        }

        $settings = new Supplier_Feed_Settings();

        if (isset($_GET['feed_saved'])) {
            $feed_saved = sanitize_text_field(
                wp_unslash($_GET['feed_saved'])
            );

            if ($feed_saved === '1') {
                echo '<div class="notice notice-success is-dismissible"><p>Настройки сохранены.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Не удалось сохранить настройки. Проверьте введённые значения.</p></div>';
            }
        }

        foreach ($manager->all() as $supplier) {
            $id = $supplier->get_id();
            $data = Supplier_Feed_Settings::get($id);
        ?>
            <div class="supplier-importer__card">
                <h2><?php echo esc_html($supplier->get_name()); ?></h2>

                <form
                    method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input
                        type="hidden"
                        name="action"
                        value="supplier_importer_save_feed_settings">

                    <input
                        type="hidden"
                        name="supplier_id"
                        value="<?php echo esc_attr($id); ?>">

                    <?php wp_nonce_field('supplier_importer_save_feed_settings'); ?>

                    <p>
                        <label for="<?php echo esc_attr($id); ?>-url">
                            URL фида
                        </label><br>

                        <input
                            class="regular-text"
                            type="url"
                            id="<?php echo esc_attr($id); ?>-url"
                            name="url"
                            value="<?php echo esc_attr($data['url']); ?>"
                            placeholder="https://example.com/feed.csv">
                    </p>

                    <p>
                        <label for="<?php echo esc_attr($id); ?>-format">
                            Формат
                        </label><br>

                        <select
                            id="<?php echo esc_attr($id); ?>-format"
                            name="format">
                            <?php foreach (['csv', 'yml', 'xml'] as $format) : ?>
                                <option
                                    value="<?php echo esc_attr($format); ?>"
                                    <?php selected($data['format'], $format); ?>>
                                    <?php echo esc_html(strtoupper($format)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>

                    <p>
                        <label>
                            <input
                                type="checkbox"
                                name="enabled"
                                value="1"
                                <?php checked($data['enabled']); ?>>
                            Включить автоматическую синхронизацию
                        </label>
                    </p>

                    <p>
                        <label>
                            <input
                                type="checkbox"
                                name="skip_attributes"
                                value="1"
                                <?php checked(!$data['process_attributes']); ?>>
                            Не учитывать атрибуты товаров
                        </label>
                        <br>
                        <span class="description">
                            При автоматическом импорте атрибуты не будут обновляться
                            и назначаться новым товарам.
                        </span>
                    </p>

                    <p>
                        <label>
                            <input
                                type="checkbox"
                                name="skip_categories"
                                value="1"
                                <?php checked(!$data['process_categories']); ?>>
                            Не учитывать категории товаров
                        </label>
                        <br>
                        <span class="description">
                            При автоматическом импорте категории не будут обновляться
                            и назначаться новым товарам.
                        </span>
                    </p>

                    <p>
                        <label for="<?php echo esc_attr($id); ?>-schedule">
                            Периодичность
                        </label><br>

                        <select
                            id="<?php echo esc_attr($id); ?>-schedule"
                            name="schedule">
                            <?php
                            $schedules = [
                                'hourly' => 'Каждый час',
                                'twicedaily' => 'Два раза в день',
                                'daily' => 'Ежедневно',
                                'weekly' => 'Еженедельно',
                            ];

                            foreach ($schedules as $value => $label) :
                            ?>
                                <option
                                    value="<?php echo esc_attr($value); ?>"
                                    <?php selected($data['schedule'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>

                    <p>
                        <label for="<?php echo esc_attr($id); ?>-time">
                            Время запуска
                        </label><br>

                        <input
                            type="time"
                            id="<?php echo esc_attr($id); ?>-time"
                            name="time"
                            value="<?php echo esc_attr($data['time']); ?>">
                    </p>

                    <?php submit_button('Сохранить настройки'); ?>
                </form>
            </div>
<?php
        }
    }
}
