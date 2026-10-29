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
     * Результат: [ID категории => название].
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
                $name = trim($reader->readString());

                if ($name === '') {
                    continue;
                }

                if (!array_key_exists($id, $categories)) {
                    $categories[$id] = $name;
                    continue;
                }

                if ($categories[$id] !== $name) {
                    \Supplier_Importer\Core\Logger::info(
                        sprintf(
                            'YML WARNING: category ID %s имеет разные названия: "%s" и "%s". Сохранено первое.',
                            $id,
                            $categories[$id],
                            $name
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

        return $categories;
    }
}
