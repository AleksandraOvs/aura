<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

class Supplier_Feed_Settings
{
    private const OPTION_NAME = 'supplier_importer_feed_settings';

    /**
     * Получить настройки всех поставщиков.
     */
    public static function get_all()
    {
        $settings = get_option(self::OPTION_NAME, []);

        return is_array($settings) ? $settings : [];
    }

    /**
     * Получить настройки конкретного поставщика.
     */
    public static function get($supplier_id)
    {
        $supplier_id = sanitize_key($supplier_id);
        $all = self::get_all();

        $defaults = [
            'url'      => '',
            'format'   => 'yml',
            'enabled'  => false,
            'schedule' => 'daily',
            'time'     => '03:00',
        ];

        $settings = $all[$supplier_id] ?? [];

        return wp_parse_args(
            is_array($settings) ? $settings : [],
            $defaults
        );
    }

    /**
     * Сохранить настройки поставщика.
     */
    public static function save($supplier_id, $data)
    {
        $supplier_id = sanitize_key($supplier_id);

        if ($supplier_id === '' || !is_array($data)) {
            return false;
        }

        $url = isset($data['url'])
            ? esc_url_raw(trim($data['url']))
            : '';

        if ($url !== '' && !wp_http_validate_url($url)) {
            return false;
        }

        if (!empty($data['enabled']) && $url === '') {
            return false;
        }
        $format = isset($data['format'])
            ? sanitize_key($data['format'])
            : 'yml';

        $schedule = isset($data['schedule'])
            ? sanitize_key($data['schedule'])
            : 'daily';

        $time = isset($data['time'])
            ? sanitize_text_field($data['time'])
            : '03:00';

        if (!in_array($format, ['csv', 'yml', 'xml'], true)) {
            return false;
        }

        if (!in_array(
            $schedule,
            ['hourly', 'twicedaily', 'daily', 'weekly'],
            true
        )) {
            return false;
        }

        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
            return false;
        }

        $all = self::get_all();

        $all[$supplier_id] = [
            'url'      => $url,
            'format'   => $format,
            'enabled' => !empty($data['enabled']),
            'schedule' => $schedule,
            'time'     => $time,
        ];

        update_option(self::OPTION_NAME, $all);

        // true означает, что данные прошли проверку и сохранены,
        // даже если значения не изменились.
        return true;
    }
}
