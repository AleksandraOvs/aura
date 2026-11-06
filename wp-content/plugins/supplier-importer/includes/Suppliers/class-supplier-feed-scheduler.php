<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Core\Logger;
use Supplier_Importer\Import\Staging_Cleaner;

class Supplier_Feed_Scheduler
{
    private const CRON_HOOK = 'supplier_importer_run_supplier_feed';
    private $batch_worker;
    private const LOCK_PREFIX = 'supplier_feed_';

    public function __construct()
    {
        add_action('init', [$this, 'sync_schedules']);

        add_action(
            self::CRON_HOOK,
            [$this, 'run_supplier_feed'],
            10,
            1
        );

        add_action(
            'supplier_importer_feed_settings_saved',
            [$this, 'reschedule_supplier'],
            10,
            1
        );

        $this->batch_worker = new Yml_Feed_Cron_Worker();
    }

    /**
     * Проверяет расписание всех зарегистрированных поставщиков.
     */
    public function sync_schedules()
    {
        $manager = \Supplier_Importer\Core\Plugin::get_supplier_manager();

        if (!$manager) {
            return;
        }

        foreach ($manager->all() as $supplier) {
            $this->ensure_schedule($supplier->get_id());
        }
    }

    /**
     * Обеспечивает наличие или отсутствие задания поставщика.
     */
    private function ensure_schedule($supplier_id)
    {
        $settings = Supplier_Feed_Settings::get($supplier_id);

        if (
            empty($settings['enabled'])
            || empty($settings['url'])
        ) {
            $this->clear_schedule($supplier_id);
            return;
        }

        if (!$this->is_valid_feed_url($settings['url'])) {
            $this->clear_schedule($supplier_id);
            return;
        }

        if (wp_next_scheduled(self::CRON_HOOK, [$supplier_id])) {
            return;
        }

        wp_schedule_single_event(
            $this->get_next_run_timestamp($settings),
            self::CRON_HOOK,
            [$supplier_id]
        );
    }

    /**
     * Пересоздаёт задание после изменения настроек.
     */
    public function reschedule_supplier($supplier_id)
    {
        $this->clear_schedule($supplier_id);
        $this->ensure_schedule($supplier_id);
    }

    /**
     * Удаляет задания поставщика.
     */
    private function clear_schedule($supplier_id)
    {
        wp_clear_scheduled_hook(
            self::CRON_HOOK,
            [$supplier_id]
        );
    }

    /**
     * Проверяет URL фида.
     */
    private function is_valid_feed_url($url)
    {
        $url = esc_url_raw($url);

        if (!$url || !wp_http_validate_url($url)) {
            return false;
        }

        $scheme = wp_parse_url($url, PHP_URL_SCHEME);

        return in_array(
            strtolower((string) $scheme),
            ['http', 'https'],
            true
        );
    }

    /**
     * Рассчитывает время следующего запуска.
     */
    private function get_next_run_timestamp($settings)
    {
        $now = current_datetime();
        $schedule = $settings['schedule'] ?? 'daily';

        if ($schedule === 'hourly') {
            return $now->getTimestamp() + HOUR_IN_SECONDS;
        }

        if ($schedule === 'twicedaily') {
            return $now->getTimestamp() + (12 * HOUR_IN_SECONDS);
        }

        $time = $settings['time'] ?? '03:00';

        [$hour, $minute] = array_map(
            'intval',
            explode(':', $time)
        );

        $next = $now->setTime($hour, $minute, 0);

        if ($next <= $now) {
            $days = $schedule === 'weekly' ? 7 : 1;

            $next = $next->modify('+' . $days . ' days');
        }

        return $next->getTimestamp();
    }


    /**
     * Запускает подготовку YML-фида и планирует пакетную обработку.
     */
    public function run_supplier_feed($supplier_id)
    {
        $supplier_id = sanitize_key($supplier_id);

        $settings = Supplier_Feed_Settings::get($supplier_id);

        if (
            empty($settings['enabled'])
            || empty($settings['url'])
            || !$this->is_valid_feed_url($settings['url'])
        ) {
            $this->clear_schedule($supplier_id);
            return;
        }

        /*
     * Защищаем поставщика от параллельного запуска.
     */
        if (!$this->acquire_supplier_lock($supplier_id)) {
            Logger::info(
                'YML CRON IMPORT SKIPPED: поставщик уже обрабатывается.',
                [
                    'supplier' => $supplier_id,
                ]
            );

            $this->ensure_schedule($supplier_id);

            return;
        }

        $import_id = 0;

        try {
            $repository = new \Supplier_Importer\Import\Import_Repository();

            /*
         * Проверяем, нет ли незавершённого автоматического импорта.
         */
            $active_import = $repository->find_active_yml_cron_import(
                $supplier_id
            );

            if ($active_import) {
                Logger::info(
                    'YML CRON IMPORT SKIPPED: предыдущий импорт ещё выполняется.',
                    [
                        'supplier'  => $supplier_id,
                        'import_id' => (int) $active_import['id'],
                        'status'    => $active_import['status'],
                        'phase'     => $active_import['phase'],
                    ]
                );

                return;
            }

            /*
         * Скачиваем фид, проверяем его и создаём
         * сессию пакетного YML-импорта.
         */
            $feed_sync = new Feed_Sync();

            $result = $feed_sync->prepare_yml_import_from_url(
                $supplier_id,
                $settings,
                'cron'
            );

            $import_id = absint(
                $result['import_id'] ?? 0
            );

            if (!$import_id) {
                throw new \RuntimeException(
                    'Не удалось создать сессию автоматического YML-импорта.'
                );
            }

            /*
         * Планируем первую порцию обработки.
         */
            $scheduled = wp_schedule_single_event(
                time() + 5,
                'supplier_importer_process_feed_batch',
                [
                    $import_id,
                    0,
                ]
            );

            if ($scheduled === false || is_wp_error($scheduled)) {
                throw new \RuntimeException(
                    'Не удалось запланировать обработку YML-импорта.'
                );
            }

            Logger::info(
                'YML CRON IMPORT SCHEDULED',
                [
                    'supplier'  => $supplier_id,
                    'import_id' => $import_id,
                    'total'     => $result['total'] ?? 0,
                ]
            );
        } catch (\Throwable $exception) {
            Logger::error(
                'Ошибка автоматического импорта фида.',
                [
                    'supplier'  => $supplier_id,
                    'import_id' => $import_id,
                    'message'   => $exception->getMessage(),
                ]
            );

            /*
         * Если сессия уже создана, но запуск Worker
         * не удалось запланировать, завершаем её с ошибкой.
         */
            if ($import_id) {
                try {
                    $repository = new \Supplier_Importer\Import\Import_Repository();

                    $session = $repository->load_session($import_id);

                    if (!$session instanceof \Supplier_Importer\Import\Import_Session) {
                        throw new \RuntimeException(
                            'Сессия импорта не найдена.'
                        );
                    }

                    if (!$session->is_finished()) {
                        $session->fail();
                        $repository->update($session);

                        Staging_Cleaner::clean($session);
                    }
                } catch (\Throwable $storage_error) {
                    Logger::error(
                        'Не удалось завершить сессию после ошибки запуска Cron.',
                        [
                            'import_id' => $import_id,
                            'message'   => $storage_error->getMessage(),
                        ]
                    );
                }
            }
        } finally {
            /*
         * Освобождаем блокировку поставщика.
         */
            $this->release_supplier_lock($supplier_id);

            /*
         * Планируем следующее выполнение фида.
         */
            $this->clear_schedule($supplier_id);
            $this->ensure_schedule($supplier_id);
        }
    }

    /**
     * Получает блокировку автоматического импорта поставщика.
     */
    private function acquire_supplier_lock($supplier_id)
    {
        global $wpdb;

        // Имя блокировки не должно превышать 64 символа.
        $lock_name = self::LOCK_PREFIX . md5($supplier_id);

        $result = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT GET_LOCK(%s, 0)',
                $lock_name
            )
        );

        if ($result === null) {
            Logger::error(
                'Ошибка получения блокировки импорта поставщика.',
                [
                    'supplier' => $supplier_id,
                    'db_error' => $wpdb->last_error,
                ]
            );

            return false;
        }

        return (string) $result === '1';
    }

    /**
     * Освобождает блокировку автоматического импорта поставщика.
     */
    private function release_supplier_lock($supplier_id)
    {
        global $wpdb;

        $lock_name = self::LOCK_PREFIX . md5($supplier_id);

        $wpdb->get_var(
            $wpdb->prepare(
                'SELECT RELEASE_LOCK(%s)',
                $lock_name
            )
        );
    }
}
