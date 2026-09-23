<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;

class Import_Repository
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;

        $this->table_name = $wpdb->prefix . 'supplier_imports';
    }

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
}
