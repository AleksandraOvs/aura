<?php

namespace Supplier_Importer\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Logger
{
    /**
     * Записать информационное сообщение.
     *
     * @param string $message
     * @param array  $context
     */
    public static function info($message, $context = [])
    {
        self::write('INFO', $message, $context);
    }

    /**
     * Записать предупреждение.
     *
     * @param string $message
     * @param array  $context
     */
    public static function warning($message, $context = [])
    {
        self::write('WARNING', $message, $context);
    }

    /**
     * Записать ошибку.
     *
     * @param string $message
     * @param array  $context
     */
    public static function error($message, $context = [])
    {
        self::write('ERROR', $message, $context);
    }

    /**
     * Записывает сообщение в лог.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    private static function write($level, $message, $context = [])
    {
        $log_message = sprintf(
            '[Supplier Importer] [%s] %s',
            $level,
            $message
        );

        if (!empty($context)) {
            $log_message .= ' ' . wp_json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        error_log($log_message);
    }
}
