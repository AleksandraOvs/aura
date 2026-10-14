<?php

namespace Supplier_Importer;

if (!defined('ABSPATH')) {
    exit;
}

class Import_Logger
{
    private const SOURCE = 'supplier-importer';

    public static function info($message, array $context = [])
    {
        self::write('info', $message, $context);
    }

    public static function warning($message, array $context = [])
    {
        self::write('warning', $message, $context);
    }

    public static function error($message, array $context = [])
    {
        self::write('error', $message, $context);
    }

    private static function write($level, $message, array $context = [])
    {
        if (!function_exists('wc_get_logger')) {
            error_log('[Supplier Importer][' . strtoupper($level) . '] ' . $message);
            return;
        }

        wc_get_logger()->log($level, $message, array_merge(
            ['source' => self::SOURCE],
            $context
        ));
    }
}
