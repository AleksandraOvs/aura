<?php

namespace Supplier_Importer\Core;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\AJAX\Ajax_Errors;
use Supplier_Importer\Suppliers\Denkirs\Denkirs;
use Supplier_Importer\Suppliers\Supplier_Manager;
use Supplier_Importer\AJAX\Ajax_Import;
use Supplier_Importer\AJAX\Ajax_Process;
use Supplier_Importer\Admin\Admin;
use Supplier_Importer\AJAX\Ajax_Mapping;
//use Supplier_Importer\CSV\Csv_Reader;
//use Supplier_Importer\Import\Import_Processor;
//use Supplier_Importer\Import\Import_Repository;
//use Supplier_Importer\Import\Import_Session;
//use Supplier_Importer\CSV\Csv_Reader;
//use Supplier_Importer\Import\Import_Manager;
//use Supplier_Importer\Suppliers\Denkirs\Denkirs_Mapper;


class Plugin
{
    private static $supplier_manager = null;

    public static function init()
    {
        self::register_autoloader();
        self::register_hooks();
    }

    public static function activate()
    {
        Installer::activate();
    }

    private static function register_autoloader()
    {
        Loader::register();
    }

    private static function register_hooks()
    {
        add_action(
            'plugins_loaded',
            [self::class, 'plugins_loaded']
        );

        add_action(
            'plugins_loaded',
            [self::class, 'plugins_loaded']
        );
    }

    public static function plugins_loaded()
    {
        self::register_suppliers();
        self::register_ajax();

        new Admin();

        Logger::info(
            'Плагин Supplier Importer успешно загружен.'
        );
    }

    private static function register_suppliers()
    {
        self::$supplier_manager = new Supplier_Manager();

        self::$supplier_manager->register(
            new Denkirs()
        );
    }

    public static function get_supplier_manager()
    {
        return self::$supplier_manager;
    }

    private static function register_ajax()
    {
        new Ajax_Import();
        new Ajax_Process();
        new Ajax_Errors();
        new Ajax_Mapping();
    }
}
