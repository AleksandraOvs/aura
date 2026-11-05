<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Базовый контракт поставщика.
 *
 * Содержит только общие методы, необходимые
 * для регистрации и идентификации поставщика.
 */
interface Supplier
{
    /**
     * Уникальный идентификатор поставщика.
     *
     * @return string
     */
    public function get_id();

    /**
     * Название поставщика.
     *
     * @return string
     */
    public function get_name();
}
