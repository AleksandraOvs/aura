<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Mapping_Resolver
{
    private $repository;

    public function __construct(
        Mapping_Repository $repository = null
    ) {
        $this->repository = $repository
            ?: new Mapping_Repository();
    }

    /**
     * Найти сопоставление категории.
     *
     * @param string $supplier
     * @param string $source_category
     *
     * @return array|null
     */
    public function resolve_category(
        $supplier,
        $source_category
    ) {
        return $this->resolve(
            $supplier,
            'category',
            $source_category,
            ''
        );
    }

    /**
     * Найти сопоставление названия атрибута.
     *
     * @param string $supplier
     * @param string $source_attribute
     *
     * @return array|null
     */
    public function resolve_attribute(
        $supplier,
        $source_attribute
    ) {
        return $this->resolve(
            $supplier,
            'attribute',
            $source_attribute,
            ''
        );
    }

    /**
     * Найти сопоставление значения атрибута.
     *
     * @param string $supplier
     * @param string $source_value
     * @param string $source_attribute
     *
     * @return array|null
     */
    public function resolve_attribute_value(
        $supplier,
        $source_value,
        $source_attribute
    ) {
        return $this->resolve(
            $supplier,
            'attribute_value',
            $source_value,
            $source_attribute
        );
    }

    /**
     * Универсальный поиск сопоставления.
     *
     * @param string $supplier
     * @param string $type
     * @param string $source_value
     * @param string $source_parent
     *
     * @return array|null
     */
    private function resolve(
        $supplier,
        $type,
        $source_value,
        $source_parent = ''
    ) {
        $supplier = trim((string) $supplier);
        $source_value = trim((string) $source_value);
        $source_parent = trim((string) $source_parent);

        if (
            $supplier === ''
            || $source_value === ''
        ) {
            return null;
        }

        return $this->repository->find_with_fallback(
            $supplier,
            $type,
            $source_value,
            $source_parent
        );
    }
}
