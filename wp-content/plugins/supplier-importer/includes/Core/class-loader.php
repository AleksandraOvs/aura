<?php

namespace Supplier_Importer\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Loader
{
    /**
     * Регистрирует автозагрузчик.
     */
    public static function register()
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Автоматически подключает класс.
     *
     * Пример:
     *
     * Supplier_Importer\Core\Logger
     *
     * превращается в:
     *
     * includes/Core/class-logger.php
     */
    public static function autoload($class)
    {
        $prefix = 'Supplier_Importer\\';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative_class = substr($class, strlen($prefix));

        $parts = explode('\\', $relative_class);

        if (empty($parts)) {
            return;
        }

        $class_name = array_pop($parts);

        $directory = '';

        if (!empty($parts)) {
            $directory = implode('/', $parts) . '/';
        }

        $file_name = strtolower(
            str_replace('_', '-', $class_name)
        );

        $base_path = SUPPLIER_IMPORTER_PATH
            . 'includes/'
            . $directory;

        $possible_files = [
            $base_path . 'class-' . $file_name . '.php',
            $base_path . 'interface-' . $file_name . '.php',
        ];

        foreach ($possible_files as $file) {
            if (file_exists($file)) {
                require_once $file;

                return;
            }
        }
    }
}
