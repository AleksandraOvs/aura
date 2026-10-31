<?php

namespace Supplier_Importer\Core;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\AJAX\Ajax_Errors;
use Supplier_Importer\AJAX\Ajax_Import;
use Supplier_Importer\AJAX\Ajax_Process;
use Supplier_Importer\AJAX\Ajax_Mapping;
use Supplier_Importer\AJAX\Ajax_Feed_Import;

use Supplier_Importer\Admin\Admin;

use Supplier_Importer\Suppliers\Denkirs\Denkirs;
use Supplier_Importer\Suppliers\Maytoni\Maytoni;
use Supplier_Importer\Suppliers\CrystalLux\Crystal_Lux;
use Supplier_Importer\Suppliers\Supplier_Manager;
use Supplier_Importer\Suppliers\Supplier_Feed_Scheduler;
use Supplier_Importer\Suppliers\Feed_Sync;
use Supplier_Importer\AJAX\Ajax_Yml_Upload;


class Plugin
{
    private static $supplier_manager = null;

    private static $feed_sync = null;

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
    }

    public static function plugins_loaded()
    {
        self::register_suppliers();
        self::register_ajax();

        new Admin();

        self::$feed_sync = new Feed_Sync();

        new Supplier_Feed_Scheduler();
    }

    private static function register_suppliers()
    {
        self::$supplier_manager = new Supplier_Manager();

        self::$supplier_manager->register(
            new Denkirs()
        );

        self::$supplier_manager->register(
            new Maytoni()
        );

        self::$supplier_manager->register(
            new Crystal_Lux()
        );
    }

    public static function get_supplier_manager()
    {
        return self::$supplier_manager;
    }

    public static function get_feed_sync()
    {
        return self::$feed_sync;
    }

    private static function register_ajax()
    {
        new Ajax_Import();
        new Ajax_Process();
        new Ajax_Errors();
        new Ajax_Mapping();
        new Ajax_Feed_Import();
        new Ajax_Yml_Upload();
    }
}
