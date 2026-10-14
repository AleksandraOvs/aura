<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Attribute_Config_Repository
{
    private const OPTION = 'supplier_importer_attribute_config';

    private const MIGRATION_OPTION =
    'supplier_importer_attribute_config_migrated_v1';

    /**
     * Получить настройки атрибутов поставщика.
     */
    public static function get($supplier, $defaults = [])
    {
        $all_configs = get_option(self::OPTION, []);

        if (!is_array($all_configs)) {
            $all_configs = [];
        }

        $saved = (
            isset($all_configs[$supplier])
            && is_array($all_configs[$supplier])
        )
            ? $all_configs[$supplier]
            : [];

        /*
         * Однократная миграция старых настроек.
         *
         * Ранее пустая taxonomy могла сохраниться из-за
         * некорректного выбора значения по умолчанию
         * в интерфейсе.
         */
        $migrations = get_option(
            self::MIGRATION_OPTION,
            []
        );

        if (!is_array($migrations)) {
            $migrations = [];
        }

        if (
            !empty($defaults)
            && empty($migrations[$supplier])
            && !empty($saved)
        ) {
            foreach ($defaults as $source => $default_item) {
                if (!is_array($default_item)) {
                    continue;
                }

                if (
                    !isset($saved[$source])
                    || !is_array($saved[$source])
                ) {
                    $saved[$source] = $default_item;
                    continue;
                }

                $saved_taxonomy = trim(
                    (string) ($saved[$source]['taxonomy'] ?? '')
                );

                if ($saved_taxonomy === '') {
                    $saved[$source]['taxonomy'] =
                        $default_item['taxonomy'] ?? '';
                }

                if (
                    trim((string) ($saved[$source]['label'] ?? ''))
                    === ''
                ) {
                    $saved[$source]['label'] =
                        $default_item['label'] ?? $source;
                }
            }

            $all_configs[$supplier] = $saved;

            update_option(
                self::OPTION,
                $all_configs,
                false
            );

            $migrations[$supplier] = 1;

            update_option(
                self::MIGRATION_OPTION,
                $migrations,
                false
            );
        } elseif (empty($migrations[$supplier])) {
            $migrations[$supplier] = 1;

            update_option(
                self::MIGRATION_OPTION,
                $migrations,
                false
            );
        }

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
