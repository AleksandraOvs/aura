<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Product\Product_Data;

interface Feed_Mapper
{
    /**
     * Формат входного фида: csv, yml или xml.
     *
     * @return string
     */
    public function get_format();

    /**
     * Преобразует одну запись фида в данные товара.
     *
     * @param array $record Запись из Row_Reader.
     *
     * @return Product_Data
     */
    public function map(array $record);
}
