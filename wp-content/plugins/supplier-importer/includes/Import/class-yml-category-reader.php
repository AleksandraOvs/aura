<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Yml_Category_Reader
{
    /**
     * Прочитать категории из YML-файла.
     *
     * Результат:
     * [
     *     ID => [
     *         'id' => ID,
     *         'name' => название,
     *         'parent_id' => ID родителя или null,
     *         'path' => полный путь,
     *     ],
     * ]
     *
     * @param string $file_path Путь к YML-файлу.
     *
     * @return array
     */
    public function read($file_path)
    {
        if (!class_exists('\XMLReader')) {
            throw new \RuntimeException(
                'Расширение PHP XMLReader недоступно.'
            );
        }

        if (!is_file($file_path) || !is_readable($file_path)) {
            throw new \RuntimeException(
                'YML-файл не найден или недоступен для чтения.'
            );
        }

        $previous_internal_errors = libxml_use_internal_errors(true);

        libxml_clear_errors();

        $reader = new \XMLReader();
        $categories = [];

        try {
            if (!$reader->open(
                $file_path,
                null,
                LIBXML_NONET | LIBXML_COMPACT
            )) {
                throw new \RuntimeException(
                    'Не удалось открыть YML-файл.'
                );
            }

            while (true) {
                $status = $reader->read();

                if (!$status) {
                    $errors = libxml_get_errors();

                    foreach ($errors as $error) {
                        if ($error->level >= LIBXML_ERR_ERROR) {
                            throw new \RuntimeException(
                                sprintf(
                                    'Ошибка разбора YML на строке %d: %s',
                                    $error->line,
                                    trim($error->message)
                                )
                            );
                        }
                    }

                    break;
                }

                if (
                    $reader->nodeType !== \XMLReader::ELEMENT
                    || $reader->localName !== 'category'
                ) {
                    continue;
                }

                $id = $reader->getAttribute('id');

                if ($id === null || trim($id) === '') {
                    continue;
                }

                $id = trim($id);

                $parent_id = $reader->getAttribute('parentId');

                if ($parent_id !== null) {
                    $parent_id = trim($parent_id);

                    if ($parent_id === '') {
                        $parent_id = null;
                    }
                }

                $name = trim($reader->readString());

                if ($name === '') {
                    continue;
                }

                if (!array_key_exists($id, $categories)) {
                    $categories[$id] = [
                        'id' => $id,
                        'name' => $name,
                        'parent_id' => $parent_id,
                        'path' => '',
                    ];

                    continue;
                }

                if (
                    $categories[$id]['name'] !== $name
                    || $categories[$id]['parent_id'] !== $parent_id
                ) {
                    \Supplier_Importer\Core\Logger::info(
                        sprintf(
                            'YML WARNING: category ID %s имеет разные данные. Сохранена первая запись.',
                            $id
                        )
                    );
                }
            }
        } finally {
            $reader->close();

            libxml_clear_errors();

            libxml_use_internal_errors(
                $previous_internal_errors
            );
        }

        if (!$categories) {
            throw new \RuntimeException(
                'В YML-файле не найден справочник категорий.'
            );
        }

        $this->build_category_paths($categories);

        return $categories;
    }

    /**
     * Построить полные пути категорий.
     *
     * @param array $categories Справочник категорий.
     *
     * @return void
     */
    private function build_category_paths(array &$categories)
    {
        foreach ($categories as $id => &$category) {
            $category['path'] = $this->get_category_path(
                $id,
                $categories
            );
        }

        unset($category);
    }

    /**
     * Получить полный путь категории.
     *
     * @param string $category_id ID категории.
     * @param array  $categories  Справочник категорий.
     * @param array  $visited     Уже обработанные ID.
     *
     * @return string
     */
    private function get_category_path(
        $category_id,
        array &$categories,
        array $visited = []
    ) {
        if (!isset($categories[$category_id])) {
            return '';
        }

        if (isset($visited[$category_id])) {
            \Supplier_Importer\Core\Logger::info(
                sprintf(
                    'YML WARNING: обнаружена циклическая ссылка в дереве категорий. ID: %s.',
                    $category_id
                )
            );

            return $categories[$category_id]['name'];
        }

        $visited[$category_id] = true;

        $category = $categories[$category_id];
        $name = $category['name'];
        $parent_id = $category['parent_id'];

        if (
            $parent_id === null
            || !isset($categories[$parent_id])
        ) {
            if (
                $parent_id !== null
                && !isset($categories[$parent_id])
            ) {
                \Supplier_Importer\Core\Logger::info(
                    sprintf(
                        'YML WARNING: родительская категория %s для категории %s не найдена.',
                        $parent_id,
                        $category_id
                    )
                );
            }

            return $name;
        }

        $parent_path = $this->get_category_path(
            $parent_id,
            $categories,
            $visited
        );

        if ($parent_path === '') {
            return $name;
        }

        return $parent_path . ' / ' . $name;
    }
}
