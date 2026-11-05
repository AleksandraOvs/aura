<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Import\Yml_Category_Reader;
use Supplier_Importer\Suppliers\Maytoni\Maytoni_Yml_Mapper;
use Supplier_Importer\Suppliers\Citilux\Citilux_Mapper;
use Supplier_Importer\Suppliers\Citilux\Citilux_Yml_Category_Reader;

class Feed_Mapper_Factory
{
    /**
     * Создаёт маппер для фида поставщика.
     *
     * @param string     $supplier_id ID поставщика.
     * @param string     $format      Формат фида.
     * @param string     $file_path   Путь к файлу фида.
     * @param array|null $categories  Готовый справочник категорий.
     *
     * @return Feed_Mapper
     */
    public function create(
        $supplier_id,
        $format,
        $file_path,
        $categories = null
    ) {
        if (!is_file($file_path) || !is_readable($file_path)) {
            throw new \InvalidArgumentException(
                'Файл фида не найден или недоступен для чтения.'
            );
        }

        $supplier_id = sanitize_key($supplier_id);
        $format = strtolower(sanitize_key($format));

        /*
         * MAYTONI — YML.
         */
        if ($supplier_id === 'maytoni' && $format === 'yml') {

            if ($categories === null) {
                $category_reader = new Yml_Category_Reader();

                $categories = $category_reader->read($file_path);
            }

            if (!is_array($categories) || empty($categories)) {
                throw new \UnexpectedValueException(
                    'В YML-фиде Maytoni не найдены категории.'
                );
            }

            return new Maytoni_Yml_Mapper($categories);
        }

        /*
         * CITILUX — XML.
         */
        if ($supplier_id === 'citilux' && $format === 'xml') {

            if ($categories === null) {
                $category_reader = new Citilux_Yml_Category_Reader();

                $categories = $category_reader->read($file_path);
            }

            if (!is_array($categories) || empty($categories)) {
                throw new \UnexpectedValueException(
                    'В XML-фиде CITILUX не найдены категории.'
                );
            }

            return new Citilux_Mapper($categories);
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Для поставщика "%s" не настроен формат "%s".',
                $supplier_id,
                $format
            )
        );
    }
}
