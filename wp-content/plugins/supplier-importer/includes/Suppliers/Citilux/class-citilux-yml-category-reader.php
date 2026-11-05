<?php

namespace Supplier_Importer\Suppliers\Citilux;

if (!defined('ABSPATH')) {
    exit;
}

class Citilux_Yml_Category_Reader
{
    /**
     * Прочитать категории CITILUX и построить полные пути.
     *
     * @param string $file_path
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
                'XML-файл CITILUX не найден или недоступен для чтения.'
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
                    'Не удалось открыть XML-файл CITILUX.'
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
                                    'Ошибка разбора XML CITILUX на строке %d: %s',
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

                $parent_id = $parent_id !== null
                    ? trim($parent_id)
                    : '';

                $name = trim($reader->readString());

                if ($name === '') {
                    continue;
                }

                /*
                 * При повторном ID сохраняем первую запись.
                 */
                if (isset($categories[$id])) {
                    if (
                        $categories[$id]['name'] !== $name
                        || $categories[$id]['parent_id'] !== $parent_id
                    ) {
                        \Supplier_Importer\Core\Logger::info(
                            sprintf(
                                'CITILUX XML WARNING: категория ID %s имеет разные данные. Сохранена первая запись.',
                                $id
                            )
                        );
                    }

                    continue;
                }

                $categories[$id] = [
                    'name'      => $name,
                    'parent_id' => $parent_id,
                ];
            }
        } finally {
            $reader->close();

            libxml_clear_errors();

            libxml_use_internal_errors(
                $previous_internal_errors
            );
        }

        if (empty($categories)) {
            throw new \RuntimeException(
                'В XML-файле CITILUX не найден справочник категорий.'
            );
        }

        return $this->build_paths($categories);
    }

    /**
     * Построить полные пути категорий.
     *
     * @param array $categories
     *
     * @return array
     */
    private function build_paths(array $categories)
    {
        $paths = [];

        foreach ($categories as $id => $category) {
            $path = $this->build_path(
                $id,
                $categories,
                []
            );

            $paths[$id] = $path !== null
                ? $path
                : $category['name'];
        }

        return $paths;
    }

    /**
     * Рекурсивно собрать путь категории.
     *
     * @param string $id
     * @param array  $categories
     * @param array  $visited
     *
     * @return string|null
     */
    private function build_path(
        $id,
        array $categories,
        array $visited
    ) {
        if (!isset($categories[$id])) {
            return '';
        }

        if (isset($visited[$id])) {
            \Supplier_Importer\Core\Logger::info(
                'CITILUX XML WARNING: обнаружен цикл категорий: ' . $id
            );

            return null;
        }

        $visited[$id] = true;

        $name = $categories[$id]['name'];
        $parent_id = $categories[$id]['parent_id'];

        if ($parent_id === '') {
            return $name;
        }

        if (!isset($categories[$parent_id])) {
            \Supplier_Importer\Core\Logger::info(
                sprintf(
                    'CITILUX XML WARNING: родительская категория %s для категории %s не найдена.',
                    $parent_id,
                    $id
                )
            );

            return $name;
        }

        $parent_path = $this->build_path(
            $parent_id,
            $categories,
            $visited
        );

        if ($parent_path === null) {
            return null;
        }

        return $parent_path !== ''
            ? $parent_path . ' / ' . $name
            : $name;
    }
}
