<?php

namespace Supplier_Importer\AJAX;

if (!defined('ABSPATH')) {
    exit;
}

use RuntimeException;

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
            'wp_ajax_supplier_mapping_get_attributes',
            [$this, 'get_attributes']
        );

        add_action(
            'wp_ajax_supplier_mapping_save_attributes',
            [$this, 'save_attributes']
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
            'wp_ajax_supplier_mapping_get_attribute_values',
            [$this, 'get_attribute_values']
        );

        add_action(
            'wp_ajax_supplier_mapping_get_attribute_labels',
            [$this, 'get_attribute_labels']
        );

        add_action(
            'wp_ajax_supplier_mapping_save_attribute_labels',
            [$this, 'save_attribute_labels']
        );

        add_action(
            'wp_ajax_supplier_mapping_get_category_tree',
            [$this, 'get_category_tree']
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

            if ($supplier === '') {
                throw new \RuntimeException(
                    'Не указан поставщик.'
                );
            }

            /*
         * Получаем полный список исходных категорий
         * из текущего анализа фида.
         */
            if (!isset($_POST['sources'])) {
                throw new \RuntimeException(
                    'Не передан список исходных категорий.'
                );
            }

            $sources = json_decode(
                wp_unslash($_POST['sources']),
                true
            );

            if (!is_array($sources)) {
                throw new \RuntimeException(
                    'Некорректный список исходных категорий.'
                );
            }

            /*
         * Проверяем и нормализуем список источников.
         */
            $normalized_sources = [];

            foreach ($sources as $source) {

                if (!is_string($source)) {
                    throw new \RuntimeException(
                        'Некорректное название исходной категории.'
                    );
                }

                $source = sanitize_text_field($source);

                if ($source === '') {
                    continue;
                }

                $normalized_sources[$source] = true;
            }

            /*
         * Получаем выбранные сопоставления.
         */
            $mappings = isset($_POST['mappings'])
                ? json_decode(
                    wp_unslash($_POST['mappings']),
                    true
                )
                : null;

            if (!is_array($mappings)) {
                throw new \RuntimeException(
                    'Некорректные данные сопоставления.'
                );
            }

            $repository =
                new \Supplier_Importer\Import\Mapping_Repository();

            $valid_mappings = [];
            $selected_sources = [];

            /*
         * Сначала проверяем все сопоставления.
         * До завершения проверки база не изменяется.
         */
            foreach ($mappings as $mapping) {

                if (
                    !is_array($mapping)
                    || !isset($mapping['source_value'])
                    || !isset($mapping['target_id'])
                ) {
                    throw new \RuntimeException(
                        'Некорректная запись сопоставления.'
                    );
                }

                $source_value = sanitize_text_field(
                    $mapping['source_value']
                );

                $target_id = absint(
                    $mapping['target_id']
                );

                if (
                    $source_value === ''
                    || !$target_id
                ) {
                    throw new \RuntimeException(
                        'Не указана исходная или целевая категория.'
                    );
                }

                /*
             * Разрешаем сопоставлять только категории,
             * присутствующие в текущем анализе фида.
             */
                if (!isset($normalized_sources[$source_value])) {
                    throw new \RuntimeException(
                        'Сопоставление содержит неизвестную категорию: '
                            . $source_value
                    );
                }

                /*
             * Один источник — одно сопоставление.
             */
                if (isset($selected_sources[$source_value])) {
                    throw new \RuntimeException(
                        'Повторяется исходная категория: '
                            . $source_value
                    );
                }

                $term = get_term(
                    $target_id,
                    'product_cat'
                );

                if (
                    !$term
                    || is_wp_error($term)
                    || $term->taxonomy !== 'product_cat'
                ) {
                    throw new \RuntimeException(
                        'Выбрана несуществующая категория сайта.'
                    );
                }

                $valid_mappings[] = [
                    'source_value' => $source_value,
                    'target_id' => $target_id,
                ];

                $selected_sources[$source_value] = true;
            }

            /*
         * Сохраняем выбранные сопоставления.
         */
            $saved = 0;

            foreach ($valid_mappings as $mapping) {

                $result = $repository->save(
                    $supplier,
                    'category',
                    $mapping['source_value'],
                    $mapping['target_id'],
                    ''
                );

                if ($result === false) {
                    throw new \RuntimeException(
                        'Не удалось сохранить сопоставление категории: '
                            . $mapping['source_value']
                    );
                }

                $saved++;
            }

            /*
         * Удаляем только очищенные сопоставления
         * категорий из текущего анализа.
         *
         * Категории, отсутствующие в текущем фиде,
         * не затрагиваем.
         */
            $deleted = 0;

            foreach ($normalized_sources as $source_value => $unused) {

                if (isset($selected_sources[$source_value])) {
                    continue;
                }

                $result = $repository->delete(
                    $supplier,
                    'category',
                    $source_value,
                    ''
                );

                if ($result) {
                    $deleted++;
                }
            }

            wp_send_json_success([
                'saved' => $saved,
                'deleted' => $deleted,
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

    /**
     * Сохраняет и синхронизирует сопоставления значений атрибутов.
     */
    public function save_attribute_values()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $supplier = isset($_POST['supplier'])
                ? sanitize_key(wp_unslash($_POST['supplier']))
                : '';

            if ($supplier === '' || $supplier === '*') {
                throw new RuntimeException(
                    'Не указан корректный поставщик.'
                );
            }

            if (
                !isset($_POST['sources'])
                || !isset($_POST['mappings'])
            ) {
                throw new RuntimeException(
                    'Не переданы исходные данные сопоставлений.'
                );
            }

            $sources = json_decode(
                wp_unslash($_POST['sources']),
                true
            );

            $mappings = json_decode(
                wp_unslash($_POST['mappings']),
                true
            );

            if (!is_array($sources) || !is_array($mappings)) {
                throw new RuntimeException(
                    'Некорректный формат данных сопоставлений.'
                );
            }

            $repository =
                new \Supplier_Importer\Import\Mapping_Repository();

            // Нормализуем полный список исходных пар.
            $normalized_sources = [];

            foreach ($sources as $source) {
                if (
                    !is_array($source)
                    || !isset($source['source_parent'], $source['source_value'])
                ) {
                    throw new RuntimeException(
                        'Некорректная исходная пара атрибута.'
                    );
                }

                $source_parent = sanitize_text_field(
                    $source['source_parent']
                );

                $source_value = sanitize_text_field(
                    $source['source_value']
                );

                if ($source_parent === '' || $source_value === '') {
                    continue;
                }

                $key = serialize([
                    $source_parent,
                    $source_value,
                ]);

                $normalized_sources[$key] = [
                    'source_parent' => $source_parent,
                    'source_value'  => $source_value,
                ];
            }

            // Проверяем выбранные сопоставления.
            $selected_mappings = [];

            foreach ($mappings as $mapping) {
                if (
                    !is_array($mapping)
                    || !isset(
                        $mapping['source_parent'],
                        $mapping['source_value'],
                        $mapping['target_id'],
                        $mapping['taxonomy']
                    )
                ) {
                    throw new RuntimeException(
                        'Некорректная запись сопоставления значения.'
                    );
                }

                $source_parent = sanitize_text_field(
                    $mapping['source_parent']
                );

                $source_value = sanitize_text_field(
                    $mapping['source_value']
                );

                $taxonomy = sanitize_key($mapping['taxonomy']);
                $target_id = absint($mapping['target_id']);

                if (strpos($taxonomy, 'pa_') !== 0) {
                    $taxonomy = 'pa_' . $taxonomy;
                }

                $key = serialize([
                    $source_parent,
                    $source_value,
                ]);

                if (
                    !isset($normalized_sources[$key])
                    || !$target_id
                    || !taxonomy_exists($taxonomy)
                ) {
                    throw new RuntimeException(
                        'Некорректное сопоставление значения атрибута.'
                    );
                }

                if (isset($selected_mappings[$key])) {
                    throw new RuntimeException(
                        'Повторяется сопоставление значения атрибута.'
                    );
                }

                $term = get_term($target_id, $taxonomy);

                if (
                    !$term
                    || is_wp_error($term)
                    || $term->taxonomy !== $taxonomy
                ) {
                    throw new RuntimeException(
                        'Выбранный термин не принадлежит указанному атрибуту.'
                    );
                }

                // Проверяем, что таксономия соответствует
                // выбранному целевому атрибуту.
                $attribute_mapping = $repository->find_with_fallback(
                    $supplier,
                    'attribute',
                    $source_parent,
                    ''
                );

                if (!$attribute_mapping || empty($attribute_mapping['target_id'])) {
                    throw new RuntimeException(
                        'Сначала необходимо сопоставить название атрибута: '
                            . $source_parent
                    );
                }

                $attribute = wc_get_attribute(
                    absint($attribute_mapping['target_id'])
                );

                if (
                    !$attribute
                    || is_wp_error($attribute)
                ) {
                    throw new RuntimeException(
                        'Не найден целевой атрибут: ' . $source_parent
                    );
                }

                $expected_taxonomy = 'pa_' . $attribute->slug;

                if ($taxonomy !== $expected_taxonomy) {
                    throw new RuntimeException(
                        'Таксономия выбранного значения не соответствует целевому атрибуту: '
                            . $source_parent
                    );
                }

                $selected_mappings[$key] = [
                    'source_parent' => $source_parent,
                    'source_value'  => $source_value,
                    'target_id'     => $target_id,
                ];
            }

            $saved = 0;
            $deleted = 0;

            foreach ($normalized_sources as $key => $source) {
                if (isset($selected_mappings[$key])) {
                    $mapping = $selected_mappings[$key];

                    $result = $repository->save(
                        $supplier,
                        'attribute_value',
                        $mapping['source_value'],
                        $mapping['target_id'],
                        $mapping['source_parent']
                    );

                    if ($result === false) {
                        throw new RuntimeException(
                            'Не удалось сохранить значение атрибута: '
                                . $mapping['source_value']
                        );
                    }

                    $saved++;
                } else {
                    $result = $repository->delete(
                        $supplier,
                        'attribute_value',
                        $source['source_value'],
                        $source['source_parent']
                    );

                    if ($result === false) {
                        throw new RuntimeException(
                            'Не удалось удалить сопоставление значения: '
                                . $source['source_value']
                        );
                    }

                    $deleted += (int) $result;
                }
            }

            wp_send_json_success([
                'saved'   => $saved,
                'deleted' => $deleted,
                'message' => sprintf(
                    'Сохранено: %d, удалено: %d.',
                    $saved,
                    $deleted
                ),
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ], 400);
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

            $rows = $repository->get_by_supplier_with_fallback(
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
     * Возвращает сохранённые сопоставления значений атрибутов.
     */
    public function get_attribute_values()
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

            // Таксономии текущих атрибутов из анализа фида.
            $taxonomies_json = isset($_POST['taxonomies'])
                ? wp_unslash($_POST['taxonomies'])
                : '';

            $taxonomies = json_decode(
                $taxonomies_json,
                true
            );

            if (!is_array($taxonomies)) {
                throw new RuntimeException(
                    'Не переданы таксономии атрибутов.'
                );
            }

            $repository =
                new \Supplier_Importer\Import\Mapping_Repository();

            $rows = $repository->get_by_supplier_with_fallback(
                $supplier,
                'attribute_value'
            );

            $saved_mappings = [];

            foreach ($rows as $row) {
                $source_parent = (string) $row['source_parent'];
                $source_value = (string) $row['source_value'];
                $target_id = absint($row['target_id']);

                if (!isset($saved_mappings[$source_parent])) {
                    $saved_mappings[$source_parent] = [];
                }

                // NULL индивидуального правила сохраняем как 0:
                // оно перекрывает глобальное правило.
                $saved_mappings[$source_parent][$source_value] = $target_id;
            }

            $mappings = [];

            foreach ($saved_mappings as $source_parent => $values) {

                if (!isset($taxonomies[$source_parent])) {
                    continue;
                }

                $taxonomy = sanitize_key(
                    $taxonomies[$source_parent]
                );

                if ($taxonomy === '') {
                    continue;
                }

                if (strpos($taxonomy, 'pa_') !== 0) {
                    $taxonomy = 'pa_' . $taxonomy;
                }

                if (!taxonomy_exists($taxonomy)) {
                    continue;
                }

                foreach ($values as $source_value => $target_id) {

                    $target_id = absint($target_id);

                    if (!$target_id) {
                        continue;
                    }

                    $term = get_term(
                        $target_id,
                        $taxonomy
                    );

                    if (!$term || is_wp_error($term)) {
                        continue;
                    }

                    $mappings[$source_parent][$source_value] = [
                        'target_id'   => $target_id,
                        'target_name' => $term->name,
                    ];
                }
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
     * Возвращает сохранённые сопоставления названий атрибутов.
     */
    public function get_attributes()
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

            $rows = $repository->get_by_supplier_with_fallback(
                $supplier,
                'attribute'
            );

            $mappings = [];

            foreach ($rows as $row) {

                $target_id = absint(
                    $row['target_id']
                );

                if (!$target_id) {
                    continue;
                }

                $attribute = wc_get_attribute($target_id);

                if (!$attribute || is_wp_error($attribute)) {
                    continue;
                }

                $mappings[$row['source_value']] = [
                    'target_id'   => $target_id,
                    'target_name' => $attribute->name,
                    'target_slug' => $attribute->slug,
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
     * Сохраняет и синхронизирует сопоставления названий атрибутов.
     */
    public function save_attributes()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $supplier = isset($_POST['supplier'])
                ? sanitize_key(wp_unslash($_POST['supplier']))
                : '';

            if ($supplier === '' || $supplier === '*') {
                throw new RuntimeException(
                    'Не указан корректный поставщик.'
                );
            }

            if (
                !isset($_POST['sources'])
                || !isset($_POST['mappings'])
            ) {
                throw new RuntimeException(
                    'Не переданы исходные данные сопоставлений.'
                );
            }

            $sources = json_decode(
                wp_unslash($_POST['sources']),
                true
            );

            $mappings = json_decode(
                wp_unslash($_POST['mappings']),
                true
            );

            if (!is_array($sources) || !is_array($mappings)) {
                throw new RuntimeException(
                    'Некорректный формат данных сопоставлений.'
                );
            }

            if (
                !function_exists('wc_get_attribute')
                || !function_exists('wc_get_attribute_taxonomies')
            ) {
                throw new RuntimeException(
                    'WooCommerce недоступен.'
                );
            }

            $attributes = wc_get_attribute_taxonomies();
            $valid_attribute_ids = [];

            foreach ($attributes as $attribute) {
                $valid_attribute_ids[] = (int) $attribute->attribute_id;
            }

            // Нормализуем полный список исходных названий.
            $normalized_sources = [];

            foreach ($sources as $source) {
                if (!is_string($source)) {
                    throw new RuntimeException(
                        'Некорректное исходное название атрибута.'
                    );
                }

                $source = sanitize_text_field($source);

                if ($source !== '') {
                    $normalized_sources[$source] = true;
                }
            }

            // Проверяем выбранные сопоставления.
            $selected_mappings = [];

            foreach ($mappings as $mapping) {
                if (
                    !is_array($mapping)
                    || !isset($mapping['source_value'], $mapping['target_id'])
                ) {
                    throw new RuntimeException(
                        'Некорректная запись сопоставления атрибута.'
                    );
                }

                $source = sanitize_text_field($mapping['source_value']);
                $target_id = absint($mapping['target_id']);

                if (
                    $source === ''
                    || !isset($normalized_sources[$source])
                    || !$target_id
                    || !in_array($target_id, $valid_attribute_ids, true)
                ) {
                    throw new RuntimeException(
                        'Обнаружено некорректное сопоставление атрибута.'
                    );
                }

                if (isset($selected_mappings[$source])) {
                    throw new RuntimeException(
                        'Повторяется исходный атрибут: ' . $source
                    );
                }

                $selected_mappings[$source] = $target_id;
            }

            $repository =
                new \Supplier_Importer\Import\Mapping_Repository();

            $saved = 0;
            $deleted = 0;

            foreach ($normalized_sources as $source => $unused) {
                if (isset($selected_mappings[$source])) {
                    $result = $repository->save(
                        $supplier,
                        'attribute',
                        $source,
                        $selected_mappings[$source],
                        ''
                    );

                    if (!$result) {
                        throw new RuntimeException(
                            'Не удалось сохранить сопоставление атрибута: '
                                . $source
                        );
                    }

                    $saved++;
                } else {
                    $result = $repository->delete(
                        $supplier,
                        'attribute',
                        $source,
                        ''
                    );

                    if ($result === false) {
                        throw new RuntimeException(
                            'Не удалось удалить сопоставление атрибута: '
                                . $source
                        );
                    }

                    $deleted += (int) $result;
                }
            }

            wp_send_json_success([
                'saved'   => $saved,
                'deleted' => $deleted,
                'message' => sprintf(
                    'Сохранено: %d, удалено: %d.',
                    $saved,
                    $deleted
                ),
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    /**
     * Возвращает список глобальных атрибутов WooCommerce.
     */
    public function get_attribute_labels()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            if (!function_exists('wc_get_attribute_taxonomies')) {
                throw new RuntimeException(
                    'WooCommerce недоступен.'
                );
            }

            $attributes = wc_get_attribute_taxonomies();
            $items = [];

            foreach ($attributes as $attribute) {
                $items[] = [
                    'id'    => (int) $attribute->attribute_id,
                    'slug'  => (string) $attribute->attribute_name,
                    'label' => (string) $attribute->attribute_label,
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

    /**
     * Обновляет отображаемые названия глобальных атрибутов.
     * Slug и настройки атрибутов сохраняются.
     */
    public function save_attribute_labels()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            if (
                !function_exists('wc_get_attribute_taxonomies')
                || !function_exists('wc_update_attribute')
            ) {
                throw new RuntimeException(
                    'WooCommerce недоступен.'
                );
            }

            $json = isset($_POST['labels'])
                ? wp_unslash($_POST['labels'])
                : '';

            if ($json === '') {
                throw new RuntimeException(
                    'Не переданы названия атрибутов.'
                );
            }

            $labels = json_decode($json, true);

            if (!is_array($labels)) {
                throw new RuntimeException(
                    'Некорректный формат данных.'
                );
            }

            $attributes = wc_get_attribute_taxonomies();
            $attributes_by_id = [];

            foreach ($attributes as $attribute) {
                $attributes_by_id[(int) $attribute->attribute_id] = $attribute;
            }

            $saved = 0;
            $errors = [];

            foreach ($labels as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $attribute_id = isset($item['attribute_id'])
                    ? absint($item['attribute_id'])
                    : 0;

                $label = isset($item['label'])
                    ? sanitize_text_field($item['label'])
                    : '';

                if ($attribute_id === 0 || $label === '') {
                    continue;
                }

                if (!isset($attributes_by_id[$attribute_id])) {
                    $errors[] = sprintf(
                        'Атрибут с ID %d не найден.',
                        $attribute_id
                    );
                    continue;
                }

                $attribute = $attributes_by_id[$attribute_id];

                // Не меняем slug, тип, сортировку или архивы.
                $result = wc_update_attribute(
                    $attribute_id,
                    [
                        'name'         => $label,
                        'slug'         => $attribute->attribute_name,
                        'type'         => $attribute->attribute_type,
                        'order_by'     => $attribute->attribute_orderby,
                        'has_archives' => (int) $attribute->attribute_public,
                    ]
                );

                if (is_wp_error($result)) {
                    $errors[] = sprintf(
                        '%s: %s',
                        $attribute->attribute_label,
                        $result->get_error_message()
                    );
                    continue;
                }

                if ($result === false) {
                    $errors[] = sprintf(
                        'Не удалось обновить атрибут «%s».',
                        $attribute->attribute_label
                    );
                    continue;
                }

                $saved++;
            }

            wp_send_json_success([
                'saved'   => $saved,
                'errors'  => $errors,
                'message' => sprintf(
                    'Обновлено названий атрибутов: %d',
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

    public function get_category_tree()
    {
        try {
            $this->check_permissions();
            $this->check_nonce();

            $terms = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (is_wp_error($terms)) {
                throw new \RuntimeException(
                    $terms->get_error_message()
                );
            }

            $items = [];

            foreach ($terms as $term) {
                $items[] = [
                    'id'        => (int) $term->term_id,
                    'name'      => $term->name,
                    'parent_id' => (int) $term->parent,
                    'count'     => (int) $term->count,
                ];
            }

            wp_send_json_success([
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
