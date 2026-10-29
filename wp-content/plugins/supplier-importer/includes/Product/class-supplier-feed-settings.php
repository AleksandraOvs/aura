<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

class Supplier_Feed_Settings
{
    private const OPTION_NAME = 'supplier_importer_feed_settings';

    /**
     * Форматы, поддерживаемые настройками фида.
     */
    private const FORMATS = [
        'csv',
        'yml',
        'xml',
    ];

    /**
     * Получить настройки одного поставщика.
     *
     * @param string $supplier_id
     * @return array
     */
    public function get($supplier_id)
    {
        $settings = get_option(self::OPTION_NAME, []);
        $defaults = $this->get_defaults();

        if (!is_array($settings)) {
            $settings = [];
        }

        $supplier_settings = $settings[$supplier_id] ?? [];

        if (!is_array($supplier_settings)) {
            $supplier_settings = [];
        }

        return wp_parse_args(
            $supplier_settings,
            $defaults
        );
    }

    /**
     * Получить настройки всех поставщиков.
     *
     * @return array
     */
    public function all()
    {
        $settings = get_option(self::OPTION_NAME, []);

        return is_array($settings) ? $settings : [];
    }

    /**
     * Сохранить настройки поставщика.
     *
     * @param string $supplier_id
     * @param array $data
     * @return bool
     */
    public function save($supplier_id, $data)
    {
        $supplier_id = sanitize_key($supplier_id);

        if ($supplier_id === '' || !is_array($data)) {
            return false;
        }

        $format = strtolower(
            sanitize_text_field(
                $data['format'] ?? 'csv'
            )
        );

        if (!in_array($format, self::FORMATS, true)) {
            return false;
        }

        $url = esc_url_raw(
            trim((string) ($data['url'] ?? ''))
        );

        $enabled = !empty($data['enabled']);

        $schedule = sanitize_key(
            $data['schedule'] ?? 'daily'
        );

        $allowed_schedules = [
            'hourly',
            'twicedaily',
            'daily',
            'weekly',
        ];

        if (
            !in_array(
                $schedule,
                $allowed_schedules,
                true
            )
        ) {
            return false;
        }

        $time = sanitize_text_field(
            $data['time'] ?? '03:00'
        );

        if (
            !preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $time
            )
        ) {
            return false;
        }

        $settings = $this->all();

        $settings[$supplier_id] = [
            'url'      => $url,
            'format'   => $format,
            'enabled'  => $enabled,
            'schedule' => $schedule,
            'time'     => $time,
        ];

        return update_option(
            self::OPTION_NAME,
            $settings,
            false
        );
    }

    /**
     * Значения по умолчанию.
     *
     * @return array
     */
    private function get_defaults()
    {
        return [
            'url'      => '',
            'format'   => 'csv',
            'enabled'  => false,
            'schedule' => 'daily',
            'time'     => '03:00',
        ];
    }
}
