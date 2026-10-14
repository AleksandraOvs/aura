<?php

namespace Supplier_Importer\AJAX;

if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/../Import/class-attribute-config-repository.php';

use RuntimeException;
use Supplier_Importer\Product\Product_Attributes;

class Ajax_Mapping
{
    public function __construct()
    {
        add_action(
            'wp_ajax_supplier_mapping_search_categories',
            [$this, 'search_categories']
        );

        add_action(
            'wp_ajax_supplier_mapping_search_attribute_terms',
            [$this, 'search_attribute_terms']
        );

        add_action(
            'wp_ajax_supplier_mapping_save_attribute_values',
            [$this, 'save_attribute_values']
        );

        add_action(
            'wp_ajax_supplier_mapping_save',
            [$this, 'save_mappings']
        );

        add_action(
            'wp_ajax_supplier_mapping_get',
            [$this, 'get_mappings']
        );

        add_action(
            'wp_ajax_supplier_mapping_get_attribute_config',
            [$this, 'get_attribute_config']
        );

        add_action(
            'wp_ajax_supplier_mapping_save_attribute_config',
            [$this, 'save_attribute_config']
        );
    }

    public function search_categories()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $search = isset($_POST['search'])
                ? sanitize_text_field(
                    wp_unslash($_POST['search'])
                )
                : '';

            if ($search === '') {
                wp_send_json_success([
                    'items' => [],
                ]);
            }

            $terms = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'search'     => $search,
                'number'     => 20,
            ]);

            if (is_wp_error($terms)) {
                throw new RuntimeException(
                    $terms->get_error_message()
                );
            }

            $items = [];

            foreach ($terms as $term) {
                $items[] = [
                    'id'    => (int) $term->term_id,
                    'name'  => $term->name,
                    'count' => (int) $term->count,
                ];
            }

            wp_send_json_success([
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    private function check_permissions()
    {
        if (!current_user_can('manage_woocommerce')) {
            throw new RuntimeException(
                'Недостаточно прав.'
            );
        }
    }

    private function check_nonce()
    {
        if (
            !isset($_POST['nonce'])
            || !wp_verify_nonce(
                $_POST['nonce'],
                'supplier_import'
            )
        ) {
            throw new RuntimeException(
                'Ошибка проверки безопасности.'
            );
        }
    }

    public function save_mappings()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $supplier = isset($_POST['supplier'])
                ? sanitize_key(
                    wp_unslash($_POST['supplier'])
                )
                : '';

            $mappings = isset($_POST['mappings'])
                ? json_decode(
                    wp_unslash($_POST['mappings']),
                    true
                )
                : [];

            if ($supplier === '') {
                throw new RuntimeException(
                    'Не указан поставщик.'
                );
            }

            if (!is_array($mappings)) {
                throw new RuntimeException(
                    'Некорректные данные сопоставления.'
                );
            }

            $repository =
                new \Supplier_Importer\Import\Mapping_Repository();

            $saved = 0;

            foreach ($mappings as $mapping) {

                $source_value = isset(
                    $mapping['source_value']
                )
                    ? sanitize_text_field(
                        $mapping['source_value']
                    )
                    : '';

                $target_id = isset(
                    $mapping['target_id']
                )
                    ? absint(
                        $mapping['target_id']
                    )
                    : 0;

                if (
                    $source_value === ''
                    || !$target_id
                ) {
                    continue;
                }

                $result = $repository->save(
                    $supplier,
                    'category',
                    $source_value,
                    $target_id,
                    ''
                );

                if ($result) {
                    $saved++;
                }
            }

            wp_send_json_success([
                'saved' => $saved,
            ]);
        } catch (\Throwable $e) {

            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    public function search_attribute_terms()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $taxonomy = isset($_POST['taxonomy'])
                ? sanitize_key(
                    wp_unslash($_POST['taxonomy'])
                )
                : '';

            $search = isset($_POST['search'])
                ? sanitize_text_field(
                    wp_unslash($_POST['search'])
                )
                : '';

            if ($taxonomy === '') {
                throw new RuntimeException(
                    'Не указана таксономия.'
                );
            }

            if (strpos($taxonomy, 'pa_') !== 0) {
                $taxonomy = 'pa_' . $taxonomy;
            }

            if (!taxonomy_exists($taxonomy)) {
                throw new RuntimeException(
                    'Таксономия не существует.'
                );
            }

            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'search'     => $search,
                'number'     => 20,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (is_wp_error($terms)) {
                throw new RuntimeException(
                    $terms->get_error_message()
                );
            }

            $items = [];

            foreach ($terms as $term) {
                $items[] = [
                    'id'   => (int) $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }

            wp_send_json_success([
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    public function save_attribute_values()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $supplier = isset($_POST['supplier'])
                ? sanitize_key(
                    wp_unslash($_POST['supplier'])
                )
                : '';

            $mappings_json = isset($_POST['mappings'])
                ? wp_unslash($_POST['mappings'])
                : '';

            if ($supplier === '') {
                throw new RuntimeException(
                    'Не указан поставщик.'
                );
            }

            if ($mappings_json === '') {
                throw new RuntimeException(
                    'Не переданы сопоставления.'
                );
            }

            $mappings = json_decode(
                $mappings_json,
                true
            );

            if (!is_array($mappings)) {
                throw new RuntimeException(
                    'Некорректный формат сопоставлений.'
                );
            }

            $repository =
                new \Supplier_Importer\Import\Mapping_Repository();

            $saved = 0;

            foreach ($mappings as $mapping) {
                if (!is_array($mapping)) {
                    continue;
                }

                $taxonomy = isset($mapping['taxonomy'])
                    ? sanitize_key(
                        $mapping['taxonomy']
                    )
                    : '';

                $source_parent = isset(
                    $mapping['source_parent']
                )
                    ? sanitize_text_field(
                        $mapping['source_parent']
                    )
                    : '';

                $source_value = isset(
                    $mapping['source_value']
                )
                    ? sanitize_text_field(
                        $mapping['source_value']
                    )
                    : '';

                $target_id = isset(
                    $mapping['target_id']
                )
                    ? absint(
                        $mapping['target_id']
                    )
                    : 0;

                if (
                    $taxonomy === ''
                    || $source_parent === ''
                    || $source_value === ''
                    || $target_id === 0
                ) {
                    continue;
                }

                if (strpos($taxonomy, 'pa_') !== 0) {
                    $taxonomy = 'pa_' . $taxonomy;
                }

                if (!taxonomy_exists($taxonomy)) {
                    continue;
                }

                $term = get_term(
                    $target_id,
                    $taxonomy
                );

                if (!$term || is_wp_error($term)) {
                    continue;
                }

                $id = $repository->save(
                    $supplier,
                    'attribute_value',
                    $source_value,
                    $target_id,
                    $source_parent
                );

                if ($id) {
                    $saved++;
                }
            }

            wp_send_json_success([
                'saved' => $saved,
                'message' => sprintf(
                    'Сохранено сопоставлений: %d',
                    $saved
                ),
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    public function get_mappings()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $supplier = isset($_POST['supplier'])
                ? sanitize_key(
                    wp_unslash($_POST['supplier'])
                )
                : '';

            if ($supplier === '') {
                throw new RuntimeException(
                    'Не указан поставщик.'
                );
            }

            $repository =
                new \Supplier_Importer\Import\Mapping_Repository();

            $rows = $repository->get_by_supplier(
                $supplier,
                'category'
            );

            $mappings = [];

            foreach ($rows as $row) {

                $target_id = absint(
                    $row['target_id']
                );

                if (!$target_id) {
                    continue;
                }

                $term = get_term(
                    $target_id,
                    'product_cat'
                );

                if (
                    !$term
                    || is_wp_error($term)
                ) {
                    continue;
                }

                $mappings[$row['source_value']] = [
                    'target_id' => $target_id,
                    'target_name' => $term->name,
                ];
            }

            wp_send_json_success([
                'mappings' => $mappings,
            ]);
        } catch (\Throwable $e) {

            wp_send_json_error(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    /**
     * Получить конфигурацию атрибутов поставщика.
     */
    /**
     * Получить конфигурацию атрибутов поставщика.
     */
    public function get_attribute_config()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $supplier = isset($_POST['supplier'])
                ? sanitize_key(wp_unslash($_POST['supplier']))
                : '';

            if ($supplier === '') {
                throw new RuntimeException(
                    'Не указан поставщик.'
                );
            }

            // Получаем конфигурацию по умолчанию.
            $defaults = [];

            // Создаём отсутствующие глобальные атрибуты WooCommerce.
            if ($supplier === 'maytoni') {
                $attribute_manager = new Product_Attributes();

                foreach ($defaults as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $taxonomy = sanitize_key(
                        $item['taxonomy'] ?? ''
                    );

                    $label = sanitize_text_field(
                        $item['label'] ?? ''
                    );

                    if ($taxonomy === '') {
                        continue;
                    }

                    $taxonomy = preg_replace(
                        '/^pa_/',
                        '',
                        $taxonomy
                    );

                    $attribute_manager->ensure_attribute_taxonomy(
                        'pa_' . $taxonomy,
                        $label !== '' ? $label : $taxonomy
                    );
                }
            }



            if ($supplier === 'maytoni') {
                $mapper = new \Supplier_Importer\Suppliers\Maytoni\Maytoni_Mapper();

                $defaults = $mapper->get_default_attribute_config();
            }

            // Объединяем настройки по умолчанию с сохранёнными.
            $config = \Supplier_Importer\Import\Attribute_Config_Repository::get(
                $supplier,
                $defaults
            );

            // Получаем существующие глобальные атрибуты WooCommerce.
            $attributes = [];

            if (function_exists('wc_get_attribute_taxonomies')) {
                $taxonomies = wc_get_attribute_taxonomies();

                foreach ((array) $taxonomies as $attribute) {
                    $slug = $attribute->attribute_name ?? '';
                    $label = $attribute->attribute_label ?? '';

                    if ($slug === '') {
                        continue;
                    }

                    $attributes[] = [
                        'taxonomy' => 'pa_' . $slug,
                        'slug'     => $slug,
                        'label'    => $label,
                    ];
                }
            }

            wp_send_json_success([
                'config'     => $config,
                'attributes' => $attributes,
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Сохранить конфигурацию атрибутов поставщика.
     */
    public function save_attribute_config()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $supplier = isset($_POST['supplier'])
                ? sanitize_key(wp_unslash($_POST['supplier']))
                : '';

            $json = isset($_POST['config'])
                ? wp_unslash($_POST['config'])
                : '';

            if ($supplier === '') {
                throw new RuntimeException(
                    'Не указан поставщик.'
                );
            }

            $config = json_decode($json, true);

            if (!is_array($config)) {
                throw new RuntimeException(
                    'Некорректная конфигурация атрибутов.'
                );
            }

            $sanitized = [];

            foreach ($config as $source => $item) {
                if (!is_string($source) || !is_array($item)) {
                    continue;
                }

                $source = sanitize_text_field($source);
                $taxonomy = isset($item['taxonomy'])
                    ? sanitize_key($item['taxonomy'])
                    : '';

                $label = isset($item['label'])
                    ? sanitize_text_field($item['label'])
                    : '';

                // Разрешаем пустой taxonomy для отключённых полей.
                if ($taxonomy !== '') {
                    $taxonomy = preg_replace(
                        '/^pa_/',
                        '',
                        $taxonomy
                    );

                    if (
                        !taxonomy_exists('pa_' . $taxonomy)
                        || !wc_attribute_taxonomy_id_by_name($taxonomy)
                    ) {
                        throw new RuntimeException(
                            sprintf(
                                'Атрибут «%s» не существует в WooCommerce.',
                                $source
                            )
                        );
                    }
                }

                $sanitized[$source] = [
                    'taxonomy' => $taxonomy,
                    'label'    => $label,
                ];
            }

            \Supplier_Importer\Import\Attribute_Config_Repository::save(
                $supplier,
                $sanitized
            );

            wp_send_json_success([
                'message' => 'Настройки атрибутов сохранены.',
                'config'  => $sanitized,
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
