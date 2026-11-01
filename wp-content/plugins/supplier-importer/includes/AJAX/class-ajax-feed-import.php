<?php

namespace Supplier_Importer\AJAX;

if (!defined('ABSPATH')) {
    exit;
}

use RuntimeException;
use Supplier_Importer\Core\Logger;
use Supplier_Importer\Core\Plugin;
use Supplier_Importer\Suppliers\Feed_Sync;
use Supplier_Importer\Suppliers\Supplier_Feed_Settings;

class Ajax_Feed_Import
{
    public function __construct()
    {
        add_action(
            'wp_ajax_supplier_import_feed_start',
            [$this, 'start_import']
        );
    }

    /**
     * Подготавливает импорт фида по сохранённому URL.
     */
    public function start_import()
    {
        $supplier_id = '';

        try {
            $this->check_permissions();
            $this->check_nonce();

            $supplier_id = isset($_POST['supplier_id'])
                ? sanitize_key(wp_unslash($_POST['supplier_id']))
                : '';

            if ($supplier_id === '') {
                throw new RuntimeException(
                    'Не указан поставщик.'
                );
            }

            $manager = Plugin::get_supplier_manager();

            if (!$manager || !$manager->has($supplier_id)) {
                throw new RuntimeException(
                    'Поставщик не зарегистрирован.'
                );
            }

            $settings = Supplier_Feed_Settings::get(
                $supplier_id
            );

            if (
                !is_array($settings)
                || empty($settings['url'])
            ) {
                throw new RuntimeException(
                    'Для поставщика не указан URL фида. Сначала сохраните настройки.'
                );
            }

            /*
         * Ручной запуск не зависит от настройки расписания.
         */
            $feed_sync = new Feed_Sync();

            $result = $feed_sync->prepare_yml_import_from_url(
                $supplier_id,
                $settings,
                'manual'
            );

            Logger::info(
                'Фид поставщика подготовлен к импорту.',
                [
                    'import_id' => $result['import_id'] ?? 0,
                    'supplier'  => $supplier_id,
                    'total'     => $result['total'] ?? 0,
                ]
            );

            wp_send_json_success([
                'import_id' => $result['import_id'],
                'run_id'    => $result['run_id'],
                'supplier'  => $supplier_id,
                'status'    => $result['status'],
                'phase'     => $result['phase'],
                'total'     => $result['total'],
                'analysis'  => $result['analysis'] ?? [],
            ]);
        } catch (\Throwable $e) {
            Logger::error(
                'Не удалось подготовить ручной импорт фида.',
                [
                    'supplier' => $supplier_id,
                    'message'  => $e->getMessage(),
                ]
            );

            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    /**
     * Проверяет права пользователя.
     */
    private function check_permissions()
    {
        if (!current_user_can('manage_woocommerce')) {
            throw new RuntimeException(
                'Недостаточно прав для запуска импорта.'
            );
        }
    }

    /**
     * Проверяет nonce.
     */
    private function check_nonce()
    {
        $nonce = isset($_POST['nonce'])
            ? sanitize_text_field(wp_unslash($_POST['nonce']))
            : '';

        if (
            $nonce === ''
            || !wp_verify_nonce($nonce, 'supplier_import')
        ) {
            throw new RuntimeException(
                'Ошибка проверки безопасности.'
            );
        }
    }
}
