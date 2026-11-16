<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Core\Logger;
use Supplier_Importer\Import\Import_Repository;
use Supplier_Importer\Import\Import_Session;
use Supplier_Importer\Import\Staging_Cleaner;

class Yml_Feed_Cron_Worker
{
    private const CRON_HOOK = 'supplier_importer_process_feed_batch';

    private const PRODUCTS_BATCH_SIZE = 50;

    private const SYNC_BATCH_SIZE = 100;

    private const NEXT_BATCH_DELAY = 5;

    private const LOCK_PREFIX = 'supplier_import_';

    private const LOCK_RETRY_DELAY = 15;

    public function __construct()
    {
        add_action(
            self::CRON_HOOK,
            [$this, 'process_batch'],
            10,
            2
        );
    }

    /**
     * Обработать одну порцию автоматического YML-импорта.
     *
     * @param int $import_id ID импорта.
     * @param int $sequence Номер порции.
     */
    public function process_batch($import_id, $sequence = 0)
    {
        $import_id = absint($import_id);
        $sequence = absint($sequence);

        if (!$import_id) {
            return;
        }

        if (!$this->acquire_lock($import_id)) {

            $repository = new Import_Repository();
            $session = $repository->load_session($import_id);

            if (
                !$session instanceof Import_Session
                || !in_array(
                    $session->get_status(),
                    ['pending', 'running'],
                    true
                )
            ) {
                return;
            }

            Logger::info(
                'YML CRON BATCH: блокировка занята или недоступна, повторяем попытку.',
                [
                    'import_id' => $import_id,
                    'sequence'  => $sequence,
                ]
            );

            $scheduled = wp_schedule_single_event(
                time() + self::LOCK_RETRY_DELAY,
                self::CRON_HOOK,
                [
                    $import_id,
                    $sequence,
                ]
            );

            if ($scheduled === false || is_wp_error($scheduled)) {
                Logger::error(
                    'Не удалось повторно запланировать YML-порцию после занятой блокировки.',
                    [
                        'import_id' => $import_id,
                        'sequence'  => $sequence,
                        'error'     => is_wp_error($scheduled)
                            ? $scheduled->get_error_message()
                            : 'wp_schedule_single_event вернул false.',
                    ]
                );
            }

            return;
        }

        try {
            $repository = new Import_Repository();

            try {
                $session = $repository->load_session($import_id);

                if (!$session instanceof Import_Session) {
                    throw new \RuntimeException(
                        'Сессия Cron-импорта не найдена.'
                    );
                }

                if (!in_array($session->get_format(), ['yml', 'xml'], true)) {
                    throw new \RuntimeException(
                        'Cron Worker получил сессию неподдерживаемого формата.'
                    );
                }

                if ($session->get_source() !== 'cron') {
                    Logger::warning(
                        'YML CRON WORKER: пропущена сессия с неподходящим источником.',
                        [
                            'import_id' => $import_id,
                            'source'    => $session->get_source(),
                        ]
                    );

                    return;
                }

                if ($session->is_finished()) {
                    if ($session->get_status() === 'cancelled') {
                        Staging_Cleaner::clean($session);

                        Logger::info(
                            'YML CRON WORKER: staging очищен после отмены импорта.',
                            [
                                'import_id' => $import_id,
                            ]
                        );
                    }

                    return;
                }

                if (
                    !in_array(
                        $session->get_status(),
                        ['pending', 'running'],
                        true
                    )
                ) {
                    return;
                }

                $feed_sync = new Feed_Sync();

                if ($session->get_phase() === 'products') {
                    $result = $feed_sync->process_yml_batch(
                        $import_id,
                        self::PRODUCTS_BATCH_SIZE
                    );
                } elseif ($session->get_phase() === 'sync') {
                    $result = $feed_sync->process_yml_sync_batch(
                        $import_id,
                        self::SYNC_BATCH_SIZE
                    );
                } else {
                    throw new \RuntimeException(
                        'Неизвестный этап Cron YML-импорта: '
                            . $session->get_phase()
                    );
                }

                /*
             * Методы Feed_Sync сохраняют сессию самостоятельно.
             * Перечитываем её, чтобы получить актуальную фазу
             * и статус после обработки порции.
             */
                $session = $repository->load_session($import_id);

                if (!$session instanceof Import_Session) {
                    throw new \RuntimeException(
                        'Не удалось повторно загрузить сессию импорта.'
                    );
                }

                Logger::info(
                    'YML CRON BATCH PROCESSED',
                    [
                        'import_id' => $import_id,
                        'sequence'  => $sequence,
                        'phase'     => $session->get_phase(),
                        'status'    => $session->get_status(),
                        'result'    => $result,
                    ]
                );

                /*
             * Импорт завершён или завершился ошибкой.
             * Следующую порцию не планируем.
             */
                if ($session->is_finished()) {
                    if ($session->get_status() === 'cancelled') {
                        Staging_Cleaner::clean($session);

                        Logger::info(
                            'YML CRON WORKER: staging очищен после отмены импорта.',
                            [
                                'import_id' => $import_id,
                            ]
                        );
                    }

                    Logger::info(
                        'YML CRON IMPORT FINISHED',
                        [
                            'import_id' => $import_id,
                            'status'    => $session->get_status(),
                        ]
                    );

                    return;
                }

                /*
             * Планируем следующую порцию.
             */
                $this->schedule_next_batch(
                    $import_id,
                    $sequence + 1
                );
            } catch (\Throwable $e) {
                Logger::error(
                    'Ошибка Cron Worker при обработке YML.',
                    [
                        'import_id' => $import_id,
                        'sequence'  => $sequence,
                        'message'   => $e->getMessage(),
                    ]
                );

                /*
             * При неожиданной ошибке пытаемся завершить
             * активную сессию со статусом failed.
             */
                try {
                    $session = $repository->load_session($import_id);

                    if ($session instanceof Import_Session) {
                        if ($session->get_status() === 'cancelled') {
                            Staging_Cleaner::clean($session);
                        } elseif (!$session->is_finished()) {
                            $session->fail();
                            $repository->update($session);

                            Staging_Cleaner::clean($session);
                        }
                    }
                } catch (\Throwable $storage_error) {
                    Logger::error(
                        'Не удалось завершить сессию после ошибки Cron Worker.',
                        [
                            'import_id' => $import_id,
                            'message'   => $storage_error->getMessage(),
                        ]
                    );
                }
            }
        } finally {
            $this->release_lock($import_id);
        }
    }

    /**
     * Запланировать следующую порцию.
     */
    private function schedule_next_batch($import_id, $sequence)
    {
        $scheduled = wp_schedule_single_event(
            time() + self::NEXT_BATCH_DELAY,
            self::CRON_HOOK,
            [
                absint($import_id),
                absint($sequence),
            ]
        );

        if ($scheduled === false || is_wp_error($scheduled)) {
            Logger::error(
                'Не удалось запланировать следующую порцию YML-импорта.',
                [
                    'import_id' => $import_id,
                    'sequence'  => $sequence,
                    'error'     => is_wp_error($scheduled)
                        ? $scheduled->get_error_message()
                        : 'wp_schedule_single_event вернул false.',
                ]
            );

            throw new \RuntimeException(
                'Не удалось запланировать следующую порцию YML-импорта.'
            );
        }
    }

    /**
     * Получает блокировку конкретного импорта.
     */
    private function acquire_lock($import_id)
    {
        global $wpdb;

        $lock_name = self::LOCK_PREFIX . absint($import_id);

        $result = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT GET_LOCK(%s, 0)',
                $lock_name
            )
        );

        if ($result === null) {
            Logger::error(
                'Ошибка получения блокировки YML-импорта.',
                [
                    'import_id' => $import_id,
                    'db_error'  => $wpdb->last_error,
                ]
            );

            return false;
        }

        return (string) $result === '1';
    }

    /**
     * Освобождает блокировку импорта.
     */
    private function release_lock($import_id)
    {
        global $wpdb;

        $lock_name = self::LOCK_PREFIX . absint($import_id);

        $wpdb->get_var(
            $wpdb->prepare(
                'SELECT RELEASE_LOCK(%s)',
                $lock_name
            )
        );
    }
}
