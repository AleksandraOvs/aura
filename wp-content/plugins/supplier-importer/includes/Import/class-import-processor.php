<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use Supplier_Importer\CSV\Csv_Reader;
use Supplier_Importer\Suppliers\Supplier;

class Import_Processor
{
    private $session;
    private $chunk;
    private $import_manager;
    private $mapper;
    private $repository;
    private $error_repository;

    public function __construct(
        Import_Session $session,
        Csv_Reader $reader,
        Supplier $supplier
    ) {
        $this->session = $session;

        $this->chunk = new Import_Chunk(
            $reader
        );

        $this->import_manager = new Import_Manager();

        $this->mapper = $supplier->get_mapper();

        $this->repository = new Import_Repository();

        $this->error_repository =
            new Import_Error_Repository();
    }

    public function process()
    {
        if ($this->session->is_finished()) {
            throw new InvalidArgumentException(
                'Импорт уже завершён.'
            );
        }

        if ($this->session->get_status() === 'pending') {
            $this->session->start();
        }

        $rows = $this->chunk->read(
            $this->session->get_offset()
        );

        if (empty($rows)) {
            $this->session->complete();

            return $this->get_result();
        }

        $products = [];

        foreach ($rows as $row) {
            try {
                $products[] = $this->mapper->map(
                    $row['data']
                );
            } catch (\Throwable $e) {

                $this->session
                    ->get_progress()
                    ->increment_errors();

                $this->save_error(
                    $row,
                    $e
                );
            }

            $this->session
                ->get_progress()
                ->increment_processed();
        }

        if (!empty($products)) {
            $import_result = $this->import_manager->import(
                $products
            );

            $this->update_progress(
                $import_result
            );
        }

        $this->session->set_offset(
            $this->session->get_offset()
                + count($rows)
        );

        if (
            $this->session->get_offset()
            >= $this->session->get_total()
        ) {
            $this->session->complete();
        }

        if ($this->session->get_import_id()) {
            $this->repository->update(
                $this->session
            );
        }

        return $this->get_result();
    }

    /**
     * Сохранить ошибку обработки строки CSV.
     */
    private function save_error($row, \Throwable $e)
    {
        $import_id = $this->session->get_import_id();

        if (!$import_id) {
            return;
        }

        $data = isset($row['data'])
            && is_array($row['data'])
            ? $row['data']
            : [];

        $sku = '';

        if (isset($data['Артикул'])) {
            $sku = trim(
                (string) $data['Артикул']
            );
        }

        $this->error_repository->add(
            $import_id,
            $row['row_number'],
            $sku,
            'mapping',
            $e->getMessage(),
            $data
        );
    }

    private function update_progress($result)
    {
        $progress = $this->session->get_progress();

        for ($i = 0; $i < $result['created']; $i++) {
            $progress->increment_created();
        }

        for ($i = 0; $i < $result['updated']; $i++) {
            $progress->increment_updated();
        }

        for ($i = 0; $i < $result['skipped']; $i++) {
            $progress->increment_skipped();
        }

        for ($i = 0; $i < $result['errors']; $i++) {
            $progress->increment_errors();
        }
    }

    private function get_result()
    {
        return [
            'session' => $this->session->to_array(),
            'progress' => $this->session
                ->get_progress()
                ->to_array(),
        ];
    }
}
