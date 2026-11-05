<?php

namespace Supplier_Importer\Suppliers\Citilux;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Supplier;

class Citilux implements Supplier
{
    public function get_id()
    {
        return 'citilux';
    }

    public function get_name()
    {
        return 'CITILUX';
    }
}
