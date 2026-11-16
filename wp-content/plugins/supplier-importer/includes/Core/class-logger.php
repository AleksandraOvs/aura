<?php

namespace Supplier_Importer\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Logger
{
    public static function info($message, $context = [])
    {
        self::write('INFO', $message, $context);
    }

    public static function warning($message, $context = [])
    {
        self::write('WARNING', $message, $context);
    }

    public static function error($message, $context = [])
    {
        self::write('ERROR', $message, $context);
    }

    private static function write($level, $message, $context = [])
    {
        $message = (string) $message;

        /*
         * Сохраняем прежнюю запись в общий error_log.
         */
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

        /*
         * В персональный журнал записываем только события,
         * у которых определён ID импорта.
         */
        $import_id = isset($context['import_id'])
            ? absint($context['import_id'])
            : 0;

        if (!$import_id) {
            return;
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'supplier_import_logs';

        $context_json = !empty($context)
            ? wp_json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
            : null;

        try {
            $result = $wpdb->insert(
                $table_name,
                [
                    'import_id' => $import_id,
                    'level'     => $level,
                    'message'   => $message,
                    'context'   => $context_json,
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                ]
            );

            if ($result === false) {
                error_log(
                    '[Supplier Importer] Failed to save import log: ' .
                        $wpdb->last_error
                );
            }
        } catch (\Throwable $e) {
            error_log(
                '[Supplier Importer] Log database exception: ' .
                    $e->getMessage()
            );
        }
    }
}
