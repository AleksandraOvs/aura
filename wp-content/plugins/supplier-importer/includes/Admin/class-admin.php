<?php

namespace Supplier_Importer\Admin;

use Supplier_Importer\Import\Import_Repository;
use Supplier_Importer\Suppliers\Supplier_Feed_Settings;
use Supplier_Importer\Core\Plugin;

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
            ? sanitize_key($_GET['tab'])
            : 'import';

        if (!in_array($current_tab, ['import', 'statistics', 'feeds'], true)) {
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
            </nav>

            <?php if ($current_tab === 'statistics') : ?>

                <?php $this->render_statistics(); ?>

            <?php elseif ($current_tab === 'feeds') : ?>

                <?php $this->render_feed_settings(); ?>

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

            'schedule' => isset($_POST['schedule'])
                ? sanitize_text_field(wp_unslash($_POST['schedule']))
                : 'daily',

            'time' => isset($_POST['time'])
                ? sanitize_text_field(wp_unslash($_POST['time']))
                : '03:00',
        ];

        $settings = new Supplier_Feed_Settings();

        if (!$settings->save($supplier_id, $data)) {
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
            if ($_GET['feed_saved'] === '1') {
                echo '<div class="notice notice-success is-dismissible"><p>Настройки сохранены.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Не удалось сохранить настройки. Проверьте введённые значения.</p></div>';
            }
        }

        foreach ($manager->all() as $supplier) {
            $id = $supplier->get_id();
            $data = $settings->get($id);
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
