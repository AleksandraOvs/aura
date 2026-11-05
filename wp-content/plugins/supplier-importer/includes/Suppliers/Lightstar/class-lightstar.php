<?php

namespace Supplier_Importer\Suppliers\Lightstar;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Supplier;

class Lightstar implements Supplier
{
    /**
     * Уникальный ID поставщика.
     */
    public function get_id()
    {
        return 'lightstar';
    }

    /**
     * Название поставщика.
     */
    public function get_name()
    {
        return 'Lightstar';
    }
}
