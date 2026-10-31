<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Core\Logger;

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
     * Запускает импорт фида через Feed_Sync.
     *
     * Загрузка, обработка и запись истории находятся
     * в одном сервисе Feed_Sync.
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

        try {
            $feed_sync = new Feed_Sync();

            $feed_sync->run_from_url(
                $supplier_id,
                $settings,
                'cron'
            );
        } catch (\Throwable $exception) {
            Logger::error(
                'Ошибка автоматического импорта фида.',
                [
                    'supplier' => $supplier_id,
                    'message'  => $exception->getMessage(),
                ]
            );
        } finally {
            /*
             * Одноразовое задание завершается.
             * Планируем следующее выполнение.
             */
            $this->clear_schedule($supplier_id);
            $this->ensure_schedule($supplier_id);
        }
    }
}
