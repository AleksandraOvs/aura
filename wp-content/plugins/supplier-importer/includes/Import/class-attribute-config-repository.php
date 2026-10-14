<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Attribute_Config_Repository
{
    private const OPTION = 'supplier_importer_attribute_config';

    /**
     * Получить настройки атрибутов поставщика.
     */
    public static function get($supplier, $defaults = [])
    {
        $all_configs = get_option(self::OPTION, []);

        $saved = (
            is_array($all_configs)
            && isset($all_configs[$supplier])
            && is_array($all_configs[$supplier])
        )
            ? $all_configs[$supplier]
            : [];

        return array_replace_recursive($defaults, $saved);
    }

    /**
     * Сохранить настройки атрибутов поставщика.
     */
    public static function save($supplier, $config)
    {
        $all_configs = get_option(self::OPTION, []);

        if (!is_array($all_configs)) {
            $all_configs = [];
        }

        $all_configs[$supplier] = $config;

        return update_option(
            self::OPTION,
            $all_configs,
            false
        );
    }
}
