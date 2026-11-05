<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Контракт поставщика с поддержкой CSV.
 *
 * Расширяет базовый Supplier дополнительными
 * методами для определения и обработки CSV-фидов.
 */
interface Csv_Supplier extends Supplier
{
    /**
     * Проверяет, соответствует ли CSV-файл поставщику.
     *
     * @param array $headers Заголовки CSV.
     * @return bool
     */
    public function supports($headers);

    /**
     * Возвращает настройки анализа CSV.
     *
     * @return array
     */
    public function get_csv_analysis_config();

    /**
     * Возвращает CSV-маппер поставщика.
     *
     * @return object
     */
    public function get_mapper();
}
