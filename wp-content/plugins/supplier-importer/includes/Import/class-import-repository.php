<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;

class Import_Repository
{
    private $table_name;
    private $errors_table_name;

    public function __construct()
    {
        global $wpdb;

        $this->table_name =
            $wpdb->prefix . 'supplier_imports';

        $this->errors_table_name =
            $wpdb->prefix . 'supplier_import_errors';
    }

    /**
     * Создаёт CSV-импорт.
     */
    public function create(Import_Session $session)
    {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_name,
            [
                'supplier'    => $session->get_supplier(),
                'file'        => $session->get_file(),
                'status'      => $session->get_status(),
                'total'       => $session->get_total(),
                'processed'   => 0,
                'created'     => 0,
                'updated'     => 0,
                'skipped'     => 0,
                'errors'      => 0,
                'offset'      => $session->get_offset(),
                'started_at'  => $session->get_started_at(),
                'finished_at' => $session->get_finished_at(),
                'created_by'  => get_current_user_id(),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%d',
            ]
        );

        if ($result === false) {
            throw new InvalidArgumentException(
                'Не удалось создать запись импорта: '
                    . $wpdb->last_error
            );
        }

        $session->set_import_id(
            $wpdb->insert_id
        );

        return $session->get_import_id();
    }

    /**
     * Создаёт запись о запуске фидового импорта.
     *
     * @param string $supplier_id ID поставщика.
     * @param string $file        URL или имя файла.
     * @param int    $created_by  ID пользователя. 0 — Cron.
     *
     * @return int ID импорта.
     */
    public function create_feed_import(
        $supplier_id,
        $file,
        $created_by = 0
    ) {
        global $wpdb;

        $supplier_id = sanitize_key($supplier_id);
        $file = trim((string) $file);
        $created_by = absint($created_by);

        if ($supplier_id === '') {
            throw new InvalidArgumentException(
                'Не указан поставщик фида.'
            );
        }

        if ($file === '') {
            throw new InvalidArgumentException(
                'Не указан источник фида.'
            );
        }

        $result = $wpdb->insert(
            $this->table_name,
            [
                'supplier'    => $supplier_id,
                'file'        => $file,
                'status'      => 'running',
                'total'       => 0,
                'processed'   => 0,
                'created'     => 0,
                'updated'     => 0,
                'skipped'     => 0,
                'errors'      => 0,
                'offset'      => 0,
                'started_at'  => current_time('mysql'),
                'finished_at' => null,
                'created_by'  => $created_by,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%d',
            ]
        );

        if ($result === false) {
            throw new InvalidArgumentException(
                'Не удалось создать запись фидового импорта: '
                    . $wpdb->last_error
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Завершает фидовый импорт и сохраняет статистику.
     *
     * @param int    $import_id ID импорта.
     * @param array  $result    Результат Import_Manager.
     * @param string $status    completed или failed.
     *
     * @return bool
     */
    public function finish_feed_import(
        $import_id,
        $result,
        $status
    ) {
        global $wpdb;

        $import_id = absint($import_id);

        if (!$import_id) {
            throw new InvalidArgumentException(
                'Не указан ID фидового импорта.'
            );
        }

        if (!is_array($result)) {
            throw new InvalidArgumentException(
                'Результат фидового импорта должен быть массивом.'
            );
        }

        $allowed_statuses = [
            'completed',
            'failed',
        ];

        if (!in_array($status, $allowed_statuses, true)) {
            throw new InvalidArgumentException(
                'Недопустимый статус фидового импорта.'
            );
        }

        $total = max(
            0,
            (int) ($result['total'] ?? 0)
        );

        $created = max(
            0,
            (int) ($result['created'] ?? 0)
        );

        $updated = max(
            0,
            (int) ($result['updated'] ?? 0)
        );

        $skipped = max(
            0,
            (int) ($result['skipped'] ?? 0)
        );

        $errors = max(
            0,
            (int) ($result['errors'] ?? 0)
        );

        $processed = $created + $updated + $skipped;

        $db_result = $wpdb->update(
            $this->table_name,
            [
                'status'      => $status,
                'total'       => $total,
                'processed'   => $processed,
                'created'     => $created,
                'updated'     => $updated,
                'skipped'     => $skipped,
                'errors'      => $errors,
                'finished_at' => current_time('mysql'),
            ],
            [
                'id' => $import_id,
            ],
            [
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($db_result === false) {
            throw new InvalidArgumentException(
                'Не удалось сохранить статистику фидового импорта: '
                    . $wpdb->last_error
            );
        }

        return true;
    }

    /**
     * Сохраняет ошибки фидового импорта.
     *
     * @param int   $import_id ID импорта.
     * @param array $errors    Массив ошибок.
     *
     * @return void
     */
    public function save_feed_errors($import_id, $errors)
    {
        global $wpdb;

        $import_id = absint($import_id);

        if (!$import_id || !is_array($errors)) {
            return;
        }

        foreach ($errors as $error) {
            if (!is_array($error)) {
                continue;
            }

            $result = $wpdb->insert(
                $this->errors_table_name,
                [
                    'import_id' => $import_id,
                    'csv_row'   => max(
                        0,
                        (int) ($error['index'] ?? 0)
                    ),
                    'sku'       => sanitize_text_field(
                        $error['sku'] ?? ''
                    ),
                    'error_type' => 'feed_import',
                    'message'    => sanitize_textarea_field(
                        $error['message'] ?? 'Неизвестная ошибка.'
                    ),
                    'raw_data'   => null,
                ],
                [
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                ]
            );

            if ($result === false) {
                throw new InvalidArgumentException(
                    'Не удалось сохранить ошибку фидового импорта: '
                        . $wpdb->last_error
                );
            }
        }
    }

    /**
     * Обновляет CSV-импорт.
     */
    public function update(Import_Session $session)
    {
        global $wpdb;

        $import_id = $session->get_import_id();

        if (!$import_id) {
            throw new InvalidArgumentException(
                'Нельзя обновить импорт без ID.'
            );
        }

        $progress = $session->get_progress();

        $result = $wpdb->update(
            $this->table_name,
            [
                'supplier'    => $session->get_supplier(),
                'file'        => $session->get_file(),
                'status'      => $session->get_status(),
                'total'       => $session->get_total(),
                'processed'   => $progress->get_processed(),
                'created'     => $progress->get_created(),
                'updated'     => $progress->get_updated(),
                'skipped'     => $progress->get_skipped(),
                'errors'      => $progress->get_errors(),
                'offset'      => $session->get_offset(),
                'started_at'  => $session->get_started_at(),
                'finished_at' => $session->get_finished_at(),
            ],
            [
                'id' => $import_id,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
            ],
            [
                '%d',
            ]
        );

        if ($result === false) {
            throw new InvalidArgumentException(
                'Не удалось обновить импорт: '
                    . $wpdb->last_error
            );
        }

        return true;
    }

    public function get($import_id)
    {
        global $wpdb;

        $import_id = absint($import_id);

        if (!$import_id) {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $import_id
            ),
            ARRAY_A
        );
    }

    public function load_session($import_id)
    {
        $data = $this->get($import_id);

        if (!$data) {
            throw new InvalidArgumentException(
                sprintf(
                    'Импорт с ID %d не найден.',
                    absint($import_id)
                )
            );
        }

        return Import_Session::from_array($data);
    }

    public function get_history()
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT
                id,
                supplier,
                file,
                status,
                total,
                processed,
                created,
                updated,
                skipped,
                errors,
                started_at,
                finished_at
            FROM {$this->table_name}
            ORDER BY id DESC",
            ARRAY_A
        );
    }

    public function reset_history()
    {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        $errors_result = $wpdb->query(
            "DELETE FROM {$this->errors_table_name}"
        );

        if ($errors_result === false) {
            $wpdb->query('ROLLBACK');

            throw new InvalidArgumentException(
                'Не удалось очистить ошибки импорта: '
                    . $wpdb->last_error
            );
        }

        $imports_result = $wpdb->query(
            "DELETE FROM {$this->table_name}"
        );

        if ($imports_result === false) {
            $wpdb->query('ROLLBACK');

            throw new InvalidArgumentException(
                'Не удалось сбросить историю импорта: '
                    . $wpdb->last_error
            );
        }

        $wpdb->query('COMMIT');

        return true;
    }
}
