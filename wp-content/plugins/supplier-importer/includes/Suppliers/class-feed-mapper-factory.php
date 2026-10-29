<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Import\Yml_Category_Reader;
use Supplier_Importer\Suppliers\Maytoni\Maytoni_Yml_Mapper;

class Feed_Mapper_Factory
{
    /**
     * Создаёт маппер для фида поставщика.
     *
     * @param string $supplier_id ID поставщика.
     * @param string $format      Формат фида.
     * @param string $file_path   Путь к скачанному файлу.
     *
     * @return Feed_Mapper
     */
    public function create($supplier_id, $format, $file_path)
    {
        if (!is_file($file_path) || !is_readable($file_path)) {
            throw new \InvalidArgumentException(
                'Файл фида не найден или недоступен для чтения.'
            );
        }

        $supplier_id = sanitize_key($supplier_id);
        $format = strtolower(sanitize_key($format));

        if ($supplier_id === 'maytoni' && $format === 'yml') {
            $category_reader = new Yml_Category_Reader();

            $categories = $category_reader->read($file_path);

            if (empty($categories)) {
                throw new \UnexpectedValueException(
                    'В YML-фиде Maytoni не найдены категории.'
                );
            }

            return new Maytoni_Yml_Mapper($categories);
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
