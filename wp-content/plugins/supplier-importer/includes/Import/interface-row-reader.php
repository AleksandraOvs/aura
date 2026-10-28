<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Общий интерфейс чтения записей из файла поставщика.
 *
 * Реализации: CSV, YML, XML.
 */
interface Row_Reader
{
    /**
     * Открывает источник для чтения.
     */
    public function open();

    /**
     * Возвращает следующую запись.
     *
     * Формат:
     * [
     *     'row_number' => 1,
     *     'data'       => [...],
     * ]
     *
     * Возвращает null, если записи закончились.
     */
    public function read();

    /**
     * Закрывает источник.
     */
    public function close();
}
