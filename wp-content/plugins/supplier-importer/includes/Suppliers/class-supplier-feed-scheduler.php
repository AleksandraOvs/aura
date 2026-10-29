<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

class Supplier_Feed_Scheduler
{
    private const CRON_HOOK = 'supplier_importer_run_supplier_feed';

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

        // Не создаём дубликаты заданий.
        if (wp_next_scheduled(self::CRON_HOOK, [$supplier_id])) {
            return;
        }

        $timestamp = $this->get_next_run_timestamp($settings);

        wp_schedule_single_event(
            $timestamp,
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
        [$hour, $minute] = array_map('intval', explode(':', $time));

        $next = $now->setTime($hour, $minute, 0);

        if ($next <= $now) {
            $days = $schedule === 'weekly' ? 7 : 1;
            $next = $next->modify('+' . $days . ' days');
        }

        return $next->getTimestamp();
    }

    /**
     * Точка входа планировщика.
     *
     * Сам импорт подключим следующим этапом.
     */
    public function run_supplier_feed($supplier_id)
    {
        $settings = Supplier_Feed_Settings::get($supplier_id);

        if (
            empty($settings['enabled'])
            || empty($settings['url'])
            || !$this->is_valid_feed_url($settings['url'])
        ) {
            $this->clear_schedule($supplier_id);
            return;
        }

        $downloader = new Feed_Downloader();
        $file = $downloader->download($settings['url']);

        if (is_wp_error($file)) {
            error_log(
                sprintf(
                    '[Supplier Importer] Ошибка загрузки фида %s: %s',
                    $supplier_id,
                    $file->get_error_message()
                )
            );

            $this->clear_schedule($supplier_id);
            $this->ensure_schedule($supplier_id);

            return;
        }

        try {
            /**
             * Передаём скачанный файл следующему этапу.
             *
             * Аргументы:
             * 1. ID поставщика.
             * 2. Путь к временному файлу.
             * 3. Настройки поставщика.
             */
            do_action(
                'supplier_importer_process_downloaded_feed',
                $supplier_id,
                $file,
                $settings
            );
        } catch (\Throwable $exception) {
            error_log(
                sprintf(
                    '[Supplier Importer] Ошибка обработки фида %s: %s',
                    $supplier_id,
                    $exception->getMessage()
                )
            );
        } finally {
            if (is_file($file)) {
                wp_delete_file($file);
            }

            $this->clear_schedule($supplier_id);
            $this->ensure_schedule($supplier_id);
        }
    }
}
