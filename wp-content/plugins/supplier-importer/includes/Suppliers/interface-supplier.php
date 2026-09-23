<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

interface Supplier
{
    /**
     * Уникальный идентификатор поставщика.
     *
     * @return string
     */
    public function get_id();

    /**
     * Человеческое название поставщика.
     *
     * @return string
     */
    public function get_name();

    /**
     * Проверяет, подходит ли CSV этому поставщику.
     *
     * @param array $headers
     *
     * @return bool
     */
    public function supports($headers);

    /**
     * Возвращает конфигурацию анализа CSV.
     *
     * @return array
     */
    public function get_csv_analysis_config();

    /**
     * Создаёт mapper поставщика.
     *
     * @return mixed
     */
    public function get_mapper();
}
