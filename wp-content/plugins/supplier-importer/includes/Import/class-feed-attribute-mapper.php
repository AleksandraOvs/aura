<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Feed_Attribute_Mapper
{
    private $resolver;

    public function __construct(
        Mapping_Resolver $resolver = null
    ) {
        $this->resolver = $resolver
            ?: new Mapping_Resolver();
    }

    /**
     * Преобразовать исходные параметры фида
     * в формат Product_Attributes.
     *
     * Вход:
     * [
     *     [
     *         'name' => 'ЦветАбажура',
     *         'value' => 'Белый',
     *     ],
     * ]
     *
     * @param array  $params
     * @param string $supplier
     *
     * @return array
     */
    public function map(
        array $params,
        $supplier
    ) {
        $attributes = [];

        foreach ($params as $param) {
            if (!is_array($param)) {
                continue;
            }

            $source_name = isset($param['name'])
                ? trim((string) $param['name'])
                : '';

            $source_value = isset($param['value'])
                ? trim((string) $param['value'])
                : '';

            if (
                $source_name === ''
                || $source_value === ''
            ) {
                continue;
            }

            $attribute = $this->resolve_attribute(
                $supplier,
                $source_name
            );

            if (!$attribute) {
                continue;
            }

            $taxonomy = $attribute['taxonomy'];
            $label = $attribute['label'];

            $value = $this->resolve_attribute_value(
                $supplier,
                $source_name,
                $source_value,
                $taxonomy
            );

            if ($value === '') {
                continue;
            }

            if (!isset($attributes[$taxonomy])) {
                $attributes[$taxonomy] = [
                    'label' => $label,
                    'values' => [],
                ];
            }

            // Удаляем повторы после применения сопоставлений.
            $attributes[$taxonomy]['values'][$value] = $value;
        }

        $result = [];

        foreach ($attributes as $taxonomy => $attribute) {
            $values = array_values($attribute['values']);

            if (empty($values)) {
                continue;
            }

            $result[$taxonomy] = [
                'label' => $attribute['label'],
                'value' => implode(', ', $values),
            ];
        }

        return $result;
    }

    /**
     * Определить целевой WooCommerce-атрибут.
     *
     * Если сопоставления нет, используем
     * исходное название параметра.
     *
     * @return array|null
     */
    private function resolve_attribute(
        $supplier,
        $source_name
    ) {
        $mapping = $this->resolver->resolve_attribute(
            $supplier,
            $source_name
        );

        if (
            is_array($mapping)
            && !empty($mapping['target_id'])
            && function_exists('wc_get_attribute')
        ) {
            $target_attribute = wc_get_attribute(
                (int) $mapping['target_id']
            );

            if (
                $target_attribute
                && !empty($target_attribute->slug)
            ) {
                $slug = wc_sanitize_taxonomy_name(
                    $target_attribute->slug
                );

                if ($slug !== '') {
                    return [
                        'taxonomy' => $slug,
                        'label' => !empty($target_attribute->name)
                            ? $target_attribute->name
                            : $source_name,
                    ];
                }
            }
        }

        return [
            'taxonomy' => $source_name,
            'label' => $source_name,
        ];
    }

    /**
     * Получить значение атрибута с учётом сопоставления.
     *
     * @return string
     */
    private function resolve_attribute_value(
        $supplier,
        $source_name,
        $source_value,
        $taxonomy
    ) {
        $mapping = $this->resolver->resolve_attribute_value(
            $supplier,
            $source_value,
            $source_name
        );

        if (
            !is_array($mapping)
            || empty($mapping['target_id'])
        ) {
            return $source_value;
        }

        $term = get_term(
            (int) $mapping['target_id'],
            'pa_' . $taxonomy
        );

        if (!$term || is_wp_error($term)) {
            return $source_value;
        }

        return $term->name;
    }
}
